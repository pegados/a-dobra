<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SlurmClusterService;
use App\Models\job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlurmStatusChecker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id_slurm_job;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id_slurm_job_param)
    {
        //inicialização do service
        $this->id_slurm_job = $id_slurm_job_param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SlurmClusterService $slurmService)
    {
        Log::info("Checando status do job SLURM {$this->id_slurm_job}");

        $status = $slurmService->getJobStatus($this->id_slurm_job);

        if (!in_array($status, ['COMPLETED', 'FAILED', 'CANCELLED'])) {

            $jobLocal = DB::table('jobs')
                ->where('id_slurm', $this->id_slurm_job)
                ->first();
            //verifica se faz uma hora que esta sendo verificado o status do job no SLURM
            if (now()->diffInSeconds($jobLocal->created_at) > 3600) {
                DB::table('jobs')
                        ->where('id_slurm', $this->id_slurm_job)
                         ->update(['status' => 'T']);
                return;
                // caso passe mais de uma hora vai dar TIMEOUT, por isso vai ser atualizado para T
            }
            // Reagenda o próprio job
            self::dispatch($this->id_slurm_job)
                ->delay(now()->addSeconds(60));

            return;
        }

        // 🔴 DAQUI PRA BAIXO, O JOB FINALIZOU DE VERDADE
        Log::info("Job SLURM {$this->id_slurm_job} finalizado com status {$status}");

        DB::table('jobs')
            ->where('id_slurm', $this->id_slurm_job)
            ->update(['status' => $status === 'COMPLETED' ? 'F' : 'P']);

        if ($status !== 'COMPLETED') {
            return;
        }

        $jobLocal = DB::table('jobs')
            ->where('id_slurm', $this->id_slurm_job)
            ->first();

        if (!$jobLocal) {
            Log::error("Job local não encontrado");
            return;
        }

        // ✅ download SÓ AQUI
        $arquivos = $slurmService->downloadJobResults(
            $this->id_slurm_job,
            '/home/alphafold/outputs'
        );

        if (count($arquivos) === 0) {
            Log::error('Nenhum arquivo baixado');
            return;
        }

        //$slurmService->cleanupJob($jobLocal->remote_dir);
    }
}
