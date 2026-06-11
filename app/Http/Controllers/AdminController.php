<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Equipamento;
use App\Models\Sensor;

class AdminController extends Controller
{
    // Lista as empresas aprovadas
    public function clientes()
    {
        $empresas = Empresa::whereHas('usuarios', function($query) {
            $query->where('status', 'ativo')->where('perfil', 'cliente');
        })->get();

        return view('admin.clientes', compact('empresas'));
    }

    // Lista usuários esperando liberação
    public function pendencias()
    {
        $usuariosPendentes = Usuario::with('empresa')->where('status', 'pendente')->get();
        return view('admin.pendencias', compact('usuariosPendentes'));
    }

    // Aprova um cliente
    public function aprovar($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->status = 'ativo';
        $usuario->save();

        return redirect()->back()->with('sucesso', 'Cliente aprovado com sucesso!');
    }

    // Visão do Admin abrindo o Dashboard do cliente
    public function verGeladeiras($id)
    {
        $empresa = Empresa::findOrFail($id);
        $equipamentos = $empresa->equipamentos()->with('sensores')->get();
        return view('dashboard', compact('empresa', 'equipamentos'));
    }

    // Formulário para criar a "carcaça" do Cooler
    public function createCooler($id)
    {
        $empresa = Empresa::findOrFail($id);
        return view('admin.create_cooler', compact('empresa'));
    }

    // Salva o Cooler e manda o Admin para a tela de acoplar sensores
    public function storeCooler(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        $cooler = Equipamento::create([
            'empresa_id' => $id,
            'nome' => $request->nome,
            'localizacao' => $request->localizacao,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.cooler.manage', $cooler->id)->with('sucesso', 'Cooler cadastrado! Agora acople os sensores neste dispositivo.');
    }

    // Tela Profissional de Gestão do Hardware
    public function gerenciarCooler($id)
    {
        $cooler = Equipamento::with(['empresa', 'sensores'])->findOrFail($id);
        return view('admin.gerenciar_cooler', compact('cooler'));
    }

    // Salva um novo sensor atrelado ao Cooler
    public function storeSensor(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|string|max:100',
            'limite_min' => 'required|numeric',
            'limite_max' => 'required|numeric',
        ]);

        Sensor::create([
            'equipamento_id' => $id,
            'tipo' => $request->tipo,
            'limite_min' => $request->limite_min,
            'limite_max' => $request->limite_max,
        ]);

        return redirect()->back()->with('sucesso', 'Sensor adicionado e calibrado com sucesso!');
    }

    // Remove um sensor do sistema
    public function destroySensor($id)
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->delete();
        
        return redirect()->back()->with('sucesso', 'Sensor removido do dispositivo com sucesso.');
    }
}