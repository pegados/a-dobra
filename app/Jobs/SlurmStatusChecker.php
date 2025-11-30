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

class SlurmStatusChecker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $slurmService;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SlurmClusterService $slurmService)
    {
        //inicialização do service
        $this->slurmService = $slurmService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle($id_slurm_job)
    {
        //execução das etapas para finalizar o job
        $status_job = $this->slurmService->waitForJob($id_slurm_job);

        //Quando demora demais e não finaliza é dado como erro na execução
        if(!$status_job == 'COMPLETED'){
            $job_local = DB::table('jobs')
                ->where('id_slurm', $id_slurm_job)
                ->update(['status' => 'E']);
            return;
        }
        //altera o status para finalizado
        $job_local = DB::table('jobs')
                ->where('id_slurm', $id_slurm_job)
                ->update(['status' => 'F']);

        //baixa o resultado da execução do job
        
    }
}
