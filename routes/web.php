<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordLinkController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\jobController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AutorizacaoMiddleware;
use RealRashid\SweetAlert\Facades\Alert;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Rota para chamar a tela de login
 */
Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store'])->name('enter');

Route::get('/register', [RegisterController::class, 'create']);

Route::post('/register', [RegisterController::class, 'store']);

//solicitar a troca de senha
Route::get('/forgot-password', [ForgotPasswordLinkController::class, 'create']);

Route::post('/forgot-password', [ForgotPasswordLinkController::class, 'store']);

//trocar a senha
Route::get('/forgot-password/{token}', [ForgotPasswordController::class, 'create'])->name('password.reset');

Route::post('/forgot-password/{token}', [ForgotPasswordController::class, 'reset'])->name('password.reset');
/**
 * Rotas protegidas pelo Middleware Autorizacao
 */
Route::middleware('Autorizacao')->group(function () {
    Route::get('/job', function () {
        return view('/job/job');
    });
    Route::post('/job', [jobController::class, 'salvar'])->name('job.salvar');
    Route::get('/jobs/{id_usuario}', 'App\Http\Controllers\jobController@listarJobUsuarios')->name('job.lista_usuario')->where('id_usuario', '[0-9]+');
    Route::get('/jobs/files/{id_job}', 'App\Http\Controllers\jobController@listarFilesJob')->name('job.lista_files')->where('id_job', '[0-9]+');
    Route::get('/jobs/download', [JobController::class, 'download'])->name('jobs.download');

    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
});










Route::get('/usuarios', 'userController@listar');
