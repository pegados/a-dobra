<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class userController extends Controller
{
    /**
     * lista usuários
     */
    
    public function listar(){
        $usuarios = User::all();
    }

    /**
     * Salva os dados de usuário
     */
    public function salvar(Request $request)
    {
        $usuario = new User();

        $usuario->name = $request->input('nome');
        $usuario->email = $request->input('email');
        $usuario->password = Hash::make($request->password);
        $usuario->telefone = $request->input('telefone');
        $usuario->universidade = $request->input('universidade');
        $usuario->save();

        Alert::success('Registred User', 'User Registred');

    }
    /**
     * Edição do usuário
     */
    public function editar(Request $request, $id)
    {
        $usuario = User::find($id);

        $usuario->name = $request->input('nome');
        $usuario->email = $request->input('email');
        $usuario->password = Hash::make($request->password);
        $usuario->telefone = $request->input('telefone');
        $usuario->universidade = $request->input('universidade');
        $usuario->save();
    }

    public function excluir($id)
    {
        $usuario = User::find($id);

        $usuario->delete();
    }
}
