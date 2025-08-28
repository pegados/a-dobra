<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SlurmClusterService
{
    private $ssh;
    private $sftp;
    private $host;
    private $username;
    private $privateKeyPath;
    private $remoteWorkDir;

    public function __construct()
    {
        $this->host = config('slurm.host');
        $this->username = config('slurm.username');
        $this->privateKeyPath = config('slurm.private_key_path');
        $this->remoteWorkDir = config('slurm.remote_work_dir', '/tmp/laravel_jobs');
        
        // Validação das configurações
        $this->validateConfig();
    }

    /**
     * Valida as configurações necessárias
     */
    private function validateConfig()
    {
        if (empty($this->host)) {
            throw new \Exception('SLURM_HOST não está configurado no .env');
        }
        
        if (empty($this->username)) {
            throw new \Exception('SLURM_USERNAME não está configurado no .env');
        }
        
        if (empty($this->privateKeyPath)) {
            throw new \Exception('SLURM_PRIVATE_KEY_PATH não está configurado no .env');
        }
        
        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception("Chave privada não encontrada em: {$this->privateKeyPath}");
        }
        
        if (!is_readable($this->privateKeyPath)) {
            throw new \Exception("Chave privada não pode ser lida: {$this->privateKeyPath}");
        }
    }

    /**
     * Estabelece conexão SSH com o cluster
     */
    private function connect()
    {
        ini_set('max_execution_time', 300); // 5 minutos
        if ($this->ssh && $this->ssh->isConnected()) {
            return true;
        }

        try {
            $this->ssh = new SSH2($this->host);
            $this->sftp = new SFTP($this->host);

            // Carrega a chave privada
            $key = PublicKeyLoader::load(file_get_contents($this->privateKeyPath));
            //var_dump($key);
            // Conecta via SSH
            if (!$this->ssh->login($this->username, $key)) {
                throw new \Exception('Falha na autenticação SSH');
            }

            // Conecta via SFTP
            if (!$this->sftp->login($this->username, $key)) {
                throw new \Exception('Falha na autenticação SFTP');
            }

            // Cria diretório de trabalho se não existir
            $this->ssh->exec("mkdir -p {$this->remoteWorkDir}");

            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao conectar no cluster: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submete um job para o Slurm
     */
    public function submitJob($jobScript = null, $inputFiles = [], $jobName = null)
    {
        $this->connect();

        $jobId = uniqid('laravel_job_');
        $jobName = $jobName ?: $jobId;
        $remoteJobDir = "{$this->remoteWorkDir}/{$jobId}";

        try {
            // Cria diretório específico para o job
            $this->ssh->exec("mkdir -p {$remoteJobDir}");

            // Upload dos arquivos de entrada
            foreach ($inputFiles as $localFile => $remoteFile) {
                $remotePath = "{$remoteJobDir}/{$remoteFile}";
                if (!$this->sftp->put($remotePath, $localFile, SFTP::SOURCE_LOCAL_FILE)) {
                    throw new \Exception("Falha ao enviar arquivo: {$localFile}");
                }
            }

            // Cria o script Slurm
            /*$slurmScript = $this->createSlurmScript($jobScript, $jobName, $remoteJobDir);
            $scriptPath = "{$remoteJobDir}/job.slurm";
            
            if (!$this->sftp->put($scriptPath, $slurmScript)) {
                throw new \Exception('Falha ao enviar script Slurm');
            }*/

            // Submete o job
            // local onde iremos indicar como executar o script via slurm 
            $submitCommand = "cd {$remoteJobDir} && ssh veredas sbatch /home/alphafold/scripts/exec_alphafold3_manual.sh";
            $output = $this->ssh->exec($submitCommand);

            // Extrai o ID do job do Slurm
            if (preg_match('/Submitted batch job (\d+)/', $output, $matches)) {
                $slurmJobId = $matches[1];
                
                Log::info("Job submetido: Laravel ID: {$jobId}, Slurm ID: {$slurmJobId}");
                
                return [
                    'laravel_job_id' => $jobId,
                    'slurm_job_id' => $slurmJobId,
                    'remote_dir' => $remoteJobDir,
                    'status' => 'submitted'
                ];
            } else {
                throw new \Exception('Falha ao submeter job: ' . $output);
            }

        } catch (\Exception $e) {
            Log::error("Erro ao submeter job {$jobId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica o status de um job
     */
    public function getJobStatus($slurmJobId)
    {
        $this->connect();

        $command = "squeue -j {$slurmJobId} --format='%T' --noheader 2>/dev/null || echo 'COMPLETED'";
        $status = trim($this->ssh->exec($command));

        return $status ?: 'COMPLETED';
    }

    /**
     * Aguarda conclusão do job
     */
    public function waitForJob($slurmJobId, $maxWaitTime = 3600, $checkInterval = 30)
    {
        $startTime = time();
        
        while (time() - $startTime < $maxWaitTime) {
            $status = $this->getJobStatus($slurmJobId);
            
            if (in_array($status, ['COMPLETED', 'FAILED', 'CANCELLED', 'TIMEOUT'])) {
                return $status;
            }
            
            sleep($checkInterval);
        }
        
        return 'TIMEOUT';
    }

    /**
     * Baixa arquivos de saída do job
     */
    public function downloadJobResults($remoteJobDir, $outputFiles = ['*.out', '*.err', '*.log'])
    {
        $this->connect();
        
        $downloadedFiles = [];
        
        try {
            // Lista arquivos no diretório remoto
            $files = $this->sftp->nlist($remoteJobDir);
            
            foreach ($outputFiles as $pattern) {
                $matchingFiles = $this->getMatchingFiles($files, $pattern);
                
                foreach ($matchingFiles as $file) {
                    $remotePath = "{$remoteJobDir}/{$file}";
                    $localPath = storage_path("app/slurm_results/{$file}");
                    
                    // Cria diretório local se não existir
                    $localDir = dirname($localPath);
                    if (!is_dir($localDir)) {
                        mkdir($localDir, 0755, true);
                    }
                    
                    // Baixa o arquivo
                    if ($this->sftp->get($remotePath, $localPath)) {
                        $downloadedFiles[] = $localPath;
                        Log::info("Arquivo baixado: {$file}");
                    }
                }
            }
            
            return $downloadedFiles;
            
        } catch (\Exception $e) {
            Log::error("Erro ao baixar resultados: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Limpa arquivos remotos do job
     */
    public function cleanupJob($remoteJobDir)
    {
        $this->connect();
        
        $command = "rm -rf {$remoteJobDir}";
        $this->ssh->exec($command);
        
        Log::info("Diretório remoto limpo: {$remoteJobDir}");
    }

    /**
     * Cria o script Slurm
     */
    private function createSlurmScript($jobScript, $jobName, $workDir)
    {
        return "#!/bin/bash
                #SBATCH --job-name={$jobName}
                #SBATCH --output={$workDir}/slurm-%j.out
                #SBATCH --error={$workDir}/slurm-%j.err
                #SBATCH --time=01:00:00
                #SBATCH --ntasks=1
                #SBATCH --cpus-per-task=1
                #SBATCH --mem=1G

                # Navega para o diretório de trabalho
                cd {$workDir}

                # Executa o script do usuário
                {$jobScript}
                ";
    }

    /**
     * Filtra arquivos por padrão
     */
    private function getMatchingFiles($files, $pattern)
    {
        $pattern = str_replace('*', '.*', $pattern);
        return array_filter($files, function($file) use ($pattern) {
            return preg_match("/^{$pattern}$/", $file);
        });
    }

    /**
     * Fecha conexões
     */
    public function disconnect()
    {
        if ($this->ssh) {
            $this->ssh->disconnect();
        }
        if ($this->sftp) {
            $this->sftp->disconnect();
        }
    }
}