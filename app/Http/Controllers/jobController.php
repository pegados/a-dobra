<?php

namespace App\Http\Controllers;

use App\Models\job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SweetAlert2\Laravel\Swal;
use RealRashid\SweetAlert\Facades\Alert;
use App\Services\SlurmClusterService;
use App\Jobs\SlurmStatusChecker;

class jobController extends Controller
{
    private $slurmService;

    public function __construct(SlurmClusterService $slurmService)
    {
        $this->slurmService = $slurmService;
    }

    public function salvar(Request $request)
    {
        
        $job = new job();
        $diretorioUser = $this->diretorioUsuario($request->input('idUsuario'));
        $jobs = DB::table('jobs')->count();
        //var_dump($jobs);
        if ($request->hasFile('fileJob') && $request->file('fileJob')->isValid()) {
            $jobs =  $jobs + 1;
            $filenameComExtensao = $request->file('fileJob')->getClientOriginalName();
            $repository = $this->criarInput($jobs, $filenameComExtensao, $diretorioUser);
            //var_dump($repository);
            $input = $request->file('fileJob')->storeAs($repository, $filenameComExtensao);
            //var_dump($input);
            $output = $this->criarOutput($jobs, $diretorioUser);
            //var_dump($output);
        }

        $job->file = $filenameComExtensao;
        $job->status = "P"; //indicar que o job começa pendente
        $job->id_usuario = $request->input('idUsuario');
        $job->output = $output;
        $job->save();

        //captura o id do job no slurm
        $output_format = explode('/', $output);
        $id_local = $output_format[3];

        //echo $id_local;
        
        $this->submitJob($request->input('idUsuario'), $request->file('fileJob'), "job_submetido", $id_local);
        return redirect()->action('App\Http\Controllers\jobController@listarJobUsuarios', ['id_usuario' => $request->input('idUsuario')]);
    }

    public function submitJob($idUsuario, $inputFile, $jobname, $id_job_local){
        try {
            // Script que será executado no cluster
            // não precisa, pois o script já existe lá 
            //$jobScript = $request->input('script', 'echo "Hello from Slurm!" > output.txt');
            
            // Arquivos de entrada (opcional)
            $inputFiles = [];
            $inputFiles[$inputFile->getPathname()] = $inputFile->getClientOriginalName();
            /*if ($request->hasFile('input_files')) {
                foreach ($request->file('input_files') as $file) {
                    $inputFiles[$file->getPathname()] = $file->getClientOriginalName();
                }
            }*/

            // Submete o job
            $jobInfo = $this->slurmService->submitJob(
                null, // configurado para poder ser null
                $inputFiles,//será substituido por $filenameComExtensao que vem da função de salvar
                $jobname
            );

            //var_dump($jobInfo);
            // Atualiza o job com o id do slurm
            $job_local = Job::find($id_job_local);
            $job_local->id_slurm = $jobInfo['slurm_job_id'];
            $job_local->remote_dir = $jobInfo['remote_dir'];
            $job_local->save();

            // dispara o job (não aguarda)
            dispatch(new SlurmStatusChecker($job_local->id_slurm));

            return response()->json([
                'success' => true,
                'job_info' => $jobInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobStatus($slurmJobId)
    {
        try {
            $status = $this->slurmService->getJobStatus($slurmJobId);
            
            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadResults(Request $request)
    {
        try {
            $remoteJobDir = $request->input('remote_job_dir');
            $outputFiles = $request->input('output_files', ['*.out', '*.err', '*.txt']);

            $downloadedFiles = $this->slurmService->downloadJobResults($remoteJobDir, $outputFiles);

            return response()->json([
                'success' => true,
                'files' => $downloadedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*Disponibiliza os arquivos para downoad na interface web*/
    public function download(Request $request)
    {
        $path = $request->query('path');

        if (!File::exists($path)) {
           abort(404);
        }

        return response()->download($path);
    }

    public function listarJobUsuarios($id_usuario)
    {
        $jobs = DB::table('jobs')
                ->where('id_usuario', '=', $id_usuario)
                ->orderBy('id', 'desc')
                ->get();
        //var_dump($jobs);
        return view('job.jobs')->with('listJobs', $jobs);
    }
    /*
     * Listar os arquivos para mostrar na interface web
     */
    public function listarFilesJob($id_job){
        $job = DB::table('jobs')->where('id', '=', $id_job)->first();
        $output = $job->output;
        
        $files = File::allFiles($output);
        if(sizeof($files)>= 10 && $job->status == 'F'){
            DB::table('jobs')
            ->where('id','=' ,$id_job)
            ->update(['status' => 'F']);
        }
       
        return view('job.file')->with('files', $files);
    }

    public function diretorioUsuario($id)
    {

        $usuario = DB::table('users')->where('id', '=', $id)->first();
        $diretorio = $usuario->home;

        return $diretorio;
    }

    //criar a pasta do input
    public function criarInput($idJob, $file, $diretorioUsuario)
    {
        $path = $diretorioUsuario . '/' . $idJob . '/input';
        if (!is_dir($path)) {

            mkdir($path, 0700, true);
            return $path;
        } else {
            return $path;
        }
    }
    //criar a pasta do output
    public function criarOutput($idJob, $diretorioUsuario)
    {
        $path = $diretorioUsuario . '/' . $idJob . '/output';
        if (!is_dir($path)) {

            mkdir($path, 0700, true);
            return $path;
        } else {
            return $path;
        }
    }
}
