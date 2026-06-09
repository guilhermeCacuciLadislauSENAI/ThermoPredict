@extends('layouts.app')

@section('title', 'Relatórios | THERMO PREDICT')
@section('subtitle', 'Central de Relatórios e Monitoramento Operacional')

@push('scripts-head')
    <script src="[https://cdn.jsdelivr.net/npm/chart.js](https://cdn.jsdelivr.net/npm/chart.js)"></script>
@endpush

@section('content')
<main class="container">

    <section class="filtros-relatorio">
        <div class="campo-filtro">
            <label for="filtroEquipamento">Equipamento</label>
            <select id="filtroEquipamento">
                <option value="todos">Todos</option>
                <option value="Geladeira 01">Geladeira 01</option>
                <option value="Geladeira 02">Geladeira 02</option>
                <option value="Freezer Central">Freezer Central</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtroStatus">Status</label>
            <select id="filtroStatus">
                <option value="todos">Todos</option>
                <option value="Normal">Normal</option>
                <option value="Alerta">Alerta</option>
                <option value="Crítico">Crítico</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtroSensor">Sensor</label>
            <select id="filtroSensor">
                <option value="todos">Todos</option>
                <option value="Temperatura">Temperatura</option>
                <option value="Porta">Porta</option>
                <option value="Energia">Energia</option>
                <option value="Backup">Backup</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtroPorta">Porta</label>
            <select id="filtroPorta">
                <option value="todos">Todos</option>
                <option value="Aberta">Aberta</option>
                <option value="Fechada">Fechada</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtroPeriodo">Período</label>
            <select id="filtroPeriodo">
                <option value="todos">Todo o Dia</option>
                <option value="manha">Manhã</option>
                <option value="tarde">Tarde</option>
                <option value="noite">Noite</option>
            </select>
        </div>
    </section>

    <section class="cards-relatorio">
        <div class="card-relatorio">
            <h3>Total de Alertas</h3>
            <p id="totalAlertas">0</p>
        </div>

        <div class="card-relatorio">
            <h3>Ocorrências Críticas</h3>
            <p id="totalCriticos">0</p>
        </div>

        <div class="card-relatorio">
            <h3>Temperatura Média</h3>
            <p id="mediaTemp">0°C</p>
        </div>

        <div class="card-relatorio">
            <h3>Tempo Médio Porta Aberta</h3>
            <p id="tempoPorta">0 min</p>
        </div>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Temperatura por Equipamento</h3>
            <div class="grafico-wrapper">
                <canvas id="graficoTemperatura"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Status Operacional</h3>
            <div class="grafico-wrapper">
                <canvas id="graficoStatus"></canvas>
            </div>
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Histórico Operacional</h2>
        </div>

        <div class="tabela-scroll">
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Equipamento</th>
                        <th>Sensor</th>
                        <th>Temperatura</th>
                        <th>Porta</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tabelaHistorico">
                    </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('js/relatorios.js') }}"></script>
@endpush