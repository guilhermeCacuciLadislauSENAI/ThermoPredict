@extends('layouts.app')

@section('title', 'Dashboard | THERMO PREDICT')
@section('subtitle', 'Monitoramento Inteligente da Cadeia Fria de Vacinas')

@push('scripts-head')
    <script src="[https://cdn.jsdelivr.net/npm/chart.js](https://cdn.jsdelivr.net/npm/chart.js)"></script>
@endpush

@section('content')
<main class="container">

    <section class="filtros-box">
        <div class="filtro-item">
            <label for="filtroEquipamento">Equipamento</label>
            <select id="filtroEquipamento">
                <option value="todos">Todos</option>
                <option value="Geladeira A">Geladeira A</option>
                <option value="Geladeira B">Geladeira B</option>
                <option value="Freezer Laboratório">Freezer Laboratório</option>
            </select>
        </div>

        <div class="filtro-item">
            <label for="filtroSensor">Sensor</label>
            <select id="filtroSensor">
                <option value="todos">Todos</option>
                <option value="Sensor Interno">Sensor Interno</option>
                <option value="Sensor Porta">Sensor Porta</option>
                <option value="Sensor Backup">Sensor Backup</option>
            </select>
        </div>
    </section>

    <section class="cards">
        <div class="card temp neutro">
            <h3>Temperatura</h3>
            <p id="tempValor">--</p>
        </div>

        <div class="card risco neutro">
            <h3>Nível de Risco</h3>
            <p id="riscoValor">--</p>
        </div>

        <div class="card porta neutro">
            <h3>Status da Porta</h3>
            <p id="portaValor">--</p>
        </div>

        <div class="card status neutro">
            <h3>Sistema</h3>
            <p id="statusValor">Conectando</p>
        </div>
    </section>

    <div id="alertaBox" class="alerta alerta-conectando">
        🔄 Fazendo conexão com o servidor...
    </div>

    <section class="dashboard">
        <div class="linha-topo">
            <div class="grafico">
                <h3>Temperatura em Tempo Real</h3>
                <canvas id="graficoTemp"></canvas>
            </div>

            <div class="grafico">
                <h3>Predição Inteligente</h3>
                <div id="predicaoTexto">
                    <div class="bloco-ia ia-ok">
                        <h4>Sistema</h4>
                        <p>Aguardando leitura dos sensores...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grafico historico-box">
            <div class="topo-historico">
                <h3>Histórico Operacional</h3>
            </div>

            <div class="filtros-historico">
                <div class="campo-filtro">
                    <label for="filtroHistoricoStatus">Status</label>
                    <select id="filtroHistoricoStatus">
                        <option value="todos">Todos</option>
                        <option value="OK">OK</option>
                        <option value="ALERTA">ALERTA</option>
                        <option value="CRÍTICO">CRÍTICO</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="filtroHistoricoPorta">Porta</label>
                    <select id="filtroHistoricoPorta">
                        <option value="todos">Todos</option>
                        <option value="Aberta">Aberta</option>
                        <option value="Fechada">Fechada</option>
                    </select>
                </div>

                <div class="campo-filtro">
                    <label for="filtroHistoricoTemp">Temperatura</label>
                    <select id="filtroHistoricoTemp">
                        <option value="todos">Todas</option>
                        <option value="baixa">Abaixo de 2°C</option>
                        <option value="ideal">Entre 2°C e 7°C</option>
                        <option value="alta">Acima de 7°C</option>
                    </select>
                </div>
            </div>

            <div class="tabela-container">
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
                    <tbody id="listaAlertas">
                        </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('js/script.js') }}"></script>
@endpush