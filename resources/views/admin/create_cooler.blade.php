@extends('layouts.app')

@section('title', 'Novo Cooler | THERMO PREDICT')
@section('subtitle', 'Painel Administrativo - Cadastro de Equipamentos')

@section('content')
<main class="container">
    <div class="form-card" style="max-width: 600px; margin: 0 auto;">
        <div class="titulo-pagina">
            <h2>Cadastrar Cooler Inteligente</h2>
            <p>Vinculando hardware à empresa: <strong>{{ $empresa->razao_social }}</strong></p>
        </div>

        <form action="{{ route('admin.cooler.store', $empresa->id) }}" method="POST" style="margin-top: 25px;">
            @csrf

            <div class="campo-input">
                <label for="nome">Identificação do Cooler</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: Cooler Portátil Vacinas #04" required>
            </div>

            <div class="campo-input">
                <label for="localizacao">Setor / Localização</label>
                <input type="text" id="localizacao" name="localizacao" placeholder="Ex: Ambulância A">
            </div>

            <div class="campo-input">
                <label for="status">Status Inicial</label>
                <select id="status" name="status" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                    <option value="Ativo">Ativo (Em monitoramento)</option>
                    <option value="Em Manutenção">Em Manutenção</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="btn-primario" style="flex: 2;">Salvar Dispositivo</button>
                <a href="{{ route('admin.clientes') }}" class="btn-secundario" style="flex: 1; text-align: center; text-decoration: none; line-height: 40px; padding: 0;">Cancelar</a>
            </div>
        </form>
    </div>
</main>
@endsection