@extends('layouts.app')

@section('title', 'Suporte | THERMO PREDICT')
@section('subtitle', 'Central de Suporte Técnico e Operacional')

@section('content')
<main class="container">
    <div class="suporte-header">
        <div>
            <h2>Central de Suporte</h2>
            <p>Gerencie chamados técnicos e acompanhe ocorrências do sistema.</p>
        </div>
        <a href="{{ route('chamados.create') }}" class="btn-principal">Abrir Chamado</a>
    </div>

    <section class="lista-chamados">
        <div class="chamado-card">
            <h3>Chamado #2048</h3>
            <p><strong>Equipamento:</strong> Geladeira A</p>
            <p><strong>Problema:</strong> Oscilação de temperatura detectada nas últimas 2 horas.</p>
            <p><strong>Responsável:</strong> Equipe Técnica</p>
            <span class="status-alerta">Em análise</span>
        </div>

        <div class="chamado-card">
            <h3>Chamado #2049</h3>
            <p><strong>Equipamento:</strong> Freezer Laboratório</p>
            <p><strong>Problema:</strong> Porta permaneceu aberta por tempo excessivo.</p>
            <p><strong>Responsável:</strong> Manutenção</p>
            <span class="status-critico">Prioridade Alta</span>
        </div>

        <div class="chamado-card">
            <h3>Chamado #2050</h3>
            <p><strong>Equipamento:</strong> Geladeira B</p>
            <p><strong>Problema:</strong> Sensor backup desconectado temporariamente.</p>
            <p><strong>Responsável:</strong> TI Operacional</p>
            <span class="status-ok">Resolvido</span>
        </div>
    </section>
</main>
@endsection