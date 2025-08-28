<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class RegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('login.register');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validation
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'telefone' => $request->telefone,
            'universidade' => $request->universidade,
            'password' => Hash::make($request->password),
        ]);
        $usuarios = User::all();
        $idUser =DB::table('users')->where('email','=',$request->email)->first();
        //var_dump($idUser->id);
        $createDirectory = $this->createHome($request->email, $idUser->id);
        if ($createDirectory == true) {
            Auth::login($user);
           
  
            return redirect('/');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    //criacao pasta home do usuário
    public function createHome(string $userFolder, string $id)
    {
        //var_dump($id);
        $success = false;
        $dir = 'storage/usuarios/';
        //criando a pasta dos usuários
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
            $nickName = explode("@", $userFolder);
            $diretorio = $nickName[0];
            $local = "storage/usuarios/" . $diretorio .'.'.$id;
            //var_dump($local);
            if (!is_dir($local)) {
                
                mkdir($local, 0700, true);
                $affected = DB::table('users')
                ->where('id', $id)
                ->update(['home' => $local]);
                $success = true;
            }
        } else {
            //caso a pasta usuários já exista
            $nickName = explode("@", $userFolder);
            $diretorio = $nickName[0];
            $local = "storage/usuarios/" . $diretorio .'.'.$id;
            //var_dump($local);
            if (!is_dir($local)) {
                
                mkdir($local, 0700, true);
                $affected = DB::table('users')
                ->where('id', $id)
                ->update(['home' => $local]);
                $success = true;
            }
        }

        return $success;
    }
}
