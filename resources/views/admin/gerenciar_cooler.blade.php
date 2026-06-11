@extends('layouts.app')

@section('title', 'Gerenciar Equipamento | THERMO PREDICT')
@section('subtitle', 'Painel Administrativo - Configuração de Sensores')

@section('content')
<main class="container">
    
    <div class="titulo-pagina">
        <h2>Gerenciar Cooler: {{ $cooler->nome }}</h2>
        <p>Cliente: <strong>{{ $cooler->empresa->razao_social }}</strong></p>
    </div>

    @if(session('sucesso'))
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 0 auto 20px auto; max-width: 700px; text-align: center;">
            <strong>✔ {{ session('sucesso') }}</strong>
        </div>
    @endif

    <div class="form-card" style="max-width: 700px; margin: 0 auto 20px auto;">
        <h3 style="margin-bottom: 15px;">📦 Dados do Equipamento</h3>
        
        <p style="margin-bottom: 10px;"><strong>Identificação (MAC):</strong> {{ $cooler->nome }}</p>
        <p style="margin-bottom: 10px;"><strong>Local de Operação:</strong> {{ $cooler->localizacao ?? 'Não definida' }}</p>
        <p style="margin-bottom: 10px;"><strong>Status Atual:</strong> <span class="status-ok">{{ $cooler->status }}</span></p>
        <p><strong>Data de Registro:</strong> {{ $cooler->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="form-card" style="max-width: 700px; margin: 0 auto 20px auto;">
        <h3 style="margin-bottom: 15px;">🔌 Adicionar Novo Sensor</h3>
        
        <form action="{{ route('admin.sensor.store', $cooler->id) }}" method="POST">
            @csrf
            
            <div class="campo-input">
                <label for="tipo">Tipo de Métrica</label>
                <select id="tipo" name="tipo" required>
                    <option value="Temperatura Interna">Temperatura Interna</option>
                    <option value="Temperatura Externa">Temperatura Externa</option>
                    <option value="Umidade Interna">Umidade Interna (%)</option>
                    <option value="Bateria">Nível de Bateria (V)</option>
                </select>
            </div>

            <div class="campo-input">
                <label for="limite_min">Limite Mínimo</label>
                <input type="number" step="0.1" id="limite_min" name="limite_min" placeholder="Ex: 2.0" required>
            </div>

            <div class="campo-input">
                <label for="limite_max">Limite Máximo</label>
                <input type="number" step="0.1" id="limite_max" name="limite_max" placeholder="Ex: 8.0" required>
            </div>

            <button type="submit" class="btn-primario" style="margin-top: 10px;">Salvar Sensor</button>
        </form>
    </div>

    <div class="form-card" style="max-width: 700px; margin: 0 auto 40px auto;">
        <h3 style="margin-bottom: 15px;">📡 Sensores Acoplados</h3>
        
        @if($cooler->sensores->count() > 0)
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th style="text-align: left;">Tipo de Métrica</th>
                        <th>Min.</th>
                        <th>Máx.</th>
                        <th style="text-align: center;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cooler->sensores as $sensor)
                        <tr>
                            <td><strong>{{ $sensor->tipo }}</strong></td>
                            <td style="text-align: center;">{{ $sensor->limite_min }}</td>
                            <td style="text-align: center;">{{ $sensor->limite_max }}</td>
                            <td style="text-align: center;">
                                <form action="{{ route('admin.sensor.destroy', $sensor->id) }}" method="POST" onsubmit="return confirm('Deseja realmente remover este sensor? Os dados de telemetria dele serão perdidos.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secundario" style="background-color: #e74c3c; color: white; border: none; padding: 5px 15px;">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; color: #666; padding: 10px 0;">Nenhum sensor acoplado a este cooler no momento.</p>
        @endif

        <div style="margin-top: 25px; text-align: center;">
            <a href="{{ route('admin.clientes') }}" class="btn-secundario" style="text-decoration: none;">&larr; Voltar para Clientes</a>
        </div>
    </div>

</main>
@endsection