<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Empresa;

class AdminController extends Controller
{
    // Tela Inicial do Admin: Lista empresas ativas
    public function clientes()
    {
        $empresas = Empresa::whereHas('usuarios', function($query) {
            $query->where('status', 'ativo')->where('perfil', 'cliente');
        })->get();

        return view('admin.clientes', compact('empresas'));
    }

    // Tela de Pendências: Lista usuários aguardando aprovação
    public function pendencias()
    {
        $usuariosPendentes = Usuario::with('empresa')->where('status', 'pendente')->get();

        return view('admin.pendencias', compact('usuariosPendentes'));
    }

    // Ação: Aprova o usuário no banco de dados
    public function aprovar($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->status = 'ativo';
        $usuario->save();

        return redirect()->back()->with('sucesso', 'Cliente aprovado com sucesso!');
    }
}