<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Empresa;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function register(Request $request) // Função de verificação e cadastro de um novo usuário/empresa
    {
        $request->validate([
            'cnpj' => 'nullable|string|max:18',
            'razao_social' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:6',
        ]);

        $empresa = Empresa::firstOrCreate(
            ['cnpj' => $request->cnpj],
            ['razao_social' => $request->razao_social]
        );

        $usuario = Usuario::create([
            'empresa_id' => $empresa->id,
            'nome' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'pendente',
            'perfil' => 'cliente',
        ]);

        Auth::login($usuario);

        return redirect()->route('aguardando.ativacao');
    }

    public function login(Request $request) // Função autenticadora para o login
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->status === 'pendente') {
                return redirect()->route('aguardando.ativacao');
            }

            // Redirecionamento Dinâmico por Perfil
            if ($user->perfil === 'admin') {
                return redirect()->route('admin.clientes');
            }

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'login_error' => 'As credenciais fornecidas estão incorretas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request) // Função para o logout
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}