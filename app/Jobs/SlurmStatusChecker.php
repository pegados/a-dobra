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
        Log::info("Executando job SLURM para ID " . $this->id_slurm_job);
        //execução das etapas para finalizar o job
        $status_job = $slurmService->waitForJob($this->id_slurm_job);
        
        //var_dump($status_job);

        //Quando demora demais e não finaliza é dado como erro na execução
        if(!($status_job == 'COMPLETED')){
            $job_local = DB::table('jobs')
                ->where('id_slurm', $this->id_slurm_job)
                ->update(['status' => 'E']);
            return;
        }
        //altera o status para finalizado
        $job_local = DB::table('jobs')
                ->where('id_slurm', $this->id_slurm_job)
                ->update(['status' => 'F']);

        $job_local = DB::table('jobs')
                ->where('id_slurm', $this->id_slurm_job)->first();

        if (!$job_local) {
            Log::error("Job não encontrado para id_slurm: {$this->id_slurm_job}");
            return;
        }

        
        //baixa o resultado da execução do job
        $arquivos_baixados = $slurmService->downloadJobResults($job_local->remote_dir);

        if(count($arquivos_baixados) == 0){
            Log::error('Erro ao baixar os arquivos do Job');
            return;
        }

        //limpar os diretorios do computador remoto
        $limpar_ok = $slurmService->cleanupJob($job_local->remote_dir);
        //return;
    }
}
