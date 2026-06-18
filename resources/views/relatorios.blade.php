<!--- ===================================================================== RELATÓRIO DOS EQUIPAMENTOS ===================================================================== --->
@extends('layouts.index')

@section('title', 'Relatórios | THERMO PREDICT')
@section('subtitle', 'Central de Relatórios e Monitoramento Operacional')

@push('scripts-head')
    <script src="[https://cdn.jsdelivr.net/npm/chart.js](https://cdn.jsdelivr.net/npm/chart.js)"></script>
@endpush

@section('content')
<main class="container">

    <section class="filtros-relatorio">
        <div class="campo-filtro"> 
            <label for="filtroEquipamento">Equipamento</label> <!--- FILTRO DE RELATÓRIO POR EQUIPAMENTO --->
            <select id="filtroEquipamento">
                <option value="todos">Todos</option>
                <option value="Cooler A">Cooler A</option>
                <option value="Cooler B">Cooler B</option>
                <option value="Cooler C">Cooler C</option>
            </select>
        </div>

        <div class="campo-filtro"> 
            <label for="filtroStatus">Status</label> <!--- FILTRO DE RELATÓRIO POR STATUS --->
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
                <option value="Temperatura-interna">Temperatura interna</option>
                <option value="Temperatura-externa">Umidade externo</option>
                <option value="Porta">Porta</option>
                <option value="Energia">Energia</option>
            </select>
        </div> 

        <div class="campo-filtro">
            <label for="filtroPorta">Porta</label> <!--- FILTRO DE RELATÓRIO STATUS PORTA --->
            <select id="filtroPorta">
                <option value="todos">Todos</option>
                <option value="Aberta">Aberta</option>
                <option value="Fechada">Fechada</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="filtroPeriodo">Período</label> <!--- FILTRO DE RELATÓRIO POR PERÍODO --->
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
            <h3>Total de Alertas</h3> <!--- TOTAL DE ALERTAS --->
            <p id="totalAlertas">0</p>
        </div>

        <div class="card-relatorio">
            <h3>Ocorrências Críticas</h3> <!--- TOTAL DE OCORRÊNCIAS CRÍTICAS --->
            <p id="totalCriticos">0</p>
        </div>

        <div class="card-relatorio">
            <h3>Temperatura Média</h3> <!--- MÉDIA DE TEMPERATURA --->
            <p id="mediaTemp">0°C</p>
        </div>

        <div class="card-relatorio">
            <h3>Tempo Médio Porta Aberta</h3> <!--- MÉDIA DE TEMPO DE ABERTURA DE PORTA --->
            <p id="tempoPorta">0 min</p>
        </div>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Temperatura por Equipamento</h3> <!--- TEMPERATURA POR EQUIPAMENTO --->
            <div class="grafico-wrapper">
                <canvas id="graficoTemperatura"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Status Operacional</h3> <!--- STATUS OPERACIONAL --->
            <div class="grafico-wrapper">
                <canvas id="graficoStatus"></canvas>
            </div>
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Histórico Operacional</h2>
        </div>

        <div class="tabela-scroll"> <!--- HISTÓRIO COMO TABELA --->
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
    <script defer src="/assets/js/relatorios.js"></script> <!--- Link do JavaScript dos relatórios --->
@endpush