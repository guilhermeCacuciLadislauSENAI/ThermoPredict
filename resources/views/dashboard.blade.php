@extends('layouts.index')

@section('title', 'Dashboard | THERMO PREDICT')
@section('subtitle', 'Monitoramento em Tempo Real - ' . $empresa->razao_social)

@push('scripts-head')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
@endpush

@section('content')
@php
    $simulation = $simulation ?? [
        'active' => false,
        'started_at' => null,
        'last_run_at' => null,
        'readings_created' => 0,
    ];
    $canManageSimulation = auth()->check() && (int) auth()->user()->empresa_id === (int) $empresa->id;
    $highestPrediction = $predictionSummary['highest'] ?? null;
    $dashboardRiskStatus = $highestPrediction['status'] ?? 'normal';
@endphp
<main class="container">
    <div class="titulo-pagina painel-titulo">
        <div>
            <h2>Vis&atilde;o Geral da Rede de Frio</h2>
            <p>Acompanhe telemetria, tend&ecirc;ncias e riscos dos coolers da empresa <strong>{{ $empresa->razao_social }}</strong>.</p>
        </div>

        <div class="page-actions">
            @if($canManageSimulation)
                <form action="{{ route('dashboard.simulation') }}" method="POST" class="simulation-form">
                    @csrf
                    <input type="hidden" name="active" value="{{ $simulation['active'] ? 0 : 1 }}">
                    <button type="submit" class="{{ $simulation['active'] ? 'btn-secundario' : 'btn-principal' }} btn-auto simulation-button">
                        @if($simulation['active'])
                            Desativar simula&ccedil;&atilde;o
                        @else
                            Ativar simula&ccedil;&atilde;o
                        @endif
                    </button>
                </form>
            @endif
            <a href="{{ route('predicao') }}" class="btn-principal btn-auto">Ver an&aacute;lise preditiva</a>
            <a href="{{ route('dashboard.pdf') }}" class="btn-secundario btn-auto" data-export-link>Exportar PDF</a>
            <a href="{{ route('dashboard.csv') }}" class="btn-secundario btn-auto" data-export-link>Exportar CSV</a>
            @if(auth()->user()->perfil === 'admin')
                <a href="{{ route('admin.clientes') }}" class="btn-secundario btn-auto">&larr; Voltar para Clientes</a>
            @endif
        </div>
    </div>

    @if(session('sucesso'))
        <div class="dashboard-message">
            {{ session('sucesso') }}
        </div>
    @endif

    <section class="cards cards-dashboard">
        <article class="card temp">
            <h3>Temperatura m&eacute;dia</h3>
            <p>{{ $stats['temperatura_media'] === null ? '--' : number_format($stats['temperatura_media'], 1, ',', '.') . ' C' }}</p>
        </article>
        <article class="card risco">
            <h3>Alertas no hist&oacute;rico</h3>
            <p>{{ $stats['alertas_total'] }}</p>
        </article>
        <article class="card porta">
            <h3>Sensores monitorados</h3>
            <p>{{ $stats['sensores_total'] }}</p>
        </article>
        <article class="card temp">
            <h3>Temp. externa m&eacute;dia</h3>
            <p>{{ $stats['temperatura_externa_media'] === null ? '--' : number_format($stats['temperatura_externa_media'], 1, ',', '.') . ' C' }}</p>
        </article>
        <article class="card status">
            <h3>Umidade externa</h3>
            <p>{{ $stats['umidade_externa_media'] === null ? '--' : number_format($stats['umidade_externa_media'], 1, ',', '.') . '%' }}</p>
        </article>
        <article class="card risco">
            <h3>Tampa aberta hoje</h3>
            <p>{{ $stats['tampa_aberta_recente'] }}</p>
        </article>
        <article class="card porta">
            <h3>Score t&eacute;rmico</h3>
            <p>{{ $stats['score_geral'] }}/100</p>
        </article>
        <article class="card status">
            <h3>Predi&ccedil;&otilde;es em aten&ccedil;&atilde;o</h3>
            <p>{{ $predictionSummary['at_risk'] }}</p>
        </article>
    </section>

    <section class="score-panel dashboard-score-panel risk-{{ $dashboardRiskStatus }}">
        <div class="score-main">
            <span>&Iacute;ndice de Risco T&eacute;rmico</span>
            <strong>{{ $stats['score_geral'] }}</strong>
            <small>0 a 30 seguro, 31 a 60 aten&ccedil;&atilde;o, 61 a 80 risco, 81 a 100 cr&iacute;tico.</small>
        </div>
        <div class="score-copy">
            <h3>An&aacute;lise preditiva</h3>
            <p>{{ $stats['previsao_risco'] }}</p>
            <div class="score-meta-list">
                <span>{{ $predictionSummary['stable'] }} est&aacute;veis</span>
                <span>{{ $predictionSummary['at_risk'] }} em aten&ccedil;&atilde;o</span>
                <span>{{ $predictionSummary['insufficient'] + $predictionSummary['stale'] }} sem previs&atilde;o v&aacute;lida</span>
                @if($highestPrediction)
                    <span>Maior risco: {{ $highestPrediction['equipamento'] ?? 'Equipamento' }} / {{ $highestPrediction['sensor'] }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="prediction-overview simulation-overview">
        <div>
            <strong>Simula&ccedil;&atilde;o de telemetria</strong>
            <span>{{ $simulation['active'] ? 'Coleta ativa' : 'Coleta parada' }}</span>
            <span data-simulation-created>{{ $simulation['readings_created'] }} leituras geradas nesta sess&atilde;o</span>
            <span data-simulation-live>
                @if($simulation['active'])
                    Gerando novas leituras a cada 5s
                @else
                    Aguardando ativa&ccedil;&atilde;o
                @endif
            </span>
        </div>
        <small>
            @if($simulation['active'])
                Ativa desde {{ $simulation['started_at']?->format('d/m H:i') ?? '--' }}. A tabela e os gr&aacute;ficos s&atilde;o atualizados automaticamente.
            @else
                Ao ativar, o cooler passa a registrar novas leituras simuladas de temperatura interna, ambiente externo, umidade e tampa.
            @endif
        </small>
    </section>

    <section class="linha-relatorios dashboard-insights">
        <div class="grafico-relatorio">
            <h3>M&eacute;dia di&aacute;ria</h3>
            <div class="grafico-wrapper">
                <canvas id="dailyAverageChart"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Distribui&ccedil;&atilde;o de risco</h3>
            <div class="grafico-wrapper">
                <canvas id="riskDistributionChart"></canvas>
            </div>
        </div>
    </section>

    <section class="linha-relatorios dashboard-insights">
        <div class="grafico-relatorio">
            <h3>M&eacute;dia por equipamento</h3>
            <div class="grafico-wrapper">
                <canvas id="equipmentAverageChart"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Proje&ccedil;&atilde;o de maior risco</h3>
            <p class="grafico-subtitulo" data-projection-label></p>
            <div class="grafico-wrapper">
                <canvas id="projectionChart"></canvas>
            </div>
        </div>
    </section>

    <div class="grid-equipamentos">
        @forelse($equipamentos as $cooler)
            <article class="card-equipamento">
                <div class="cabecalho-card">
                    <div>
                        <h3>{{ $cooler->nome }}</h3>
                        <p>{{ $cooler->localizacao ?? 'Local nao especificado' }}</p>
                    </div>
                    <span class="status-chip">{{ $cooler->status }}</span>
                </div>

                <div class="detalhes-sensores">
                    @forelse($cooler->sensores as $sensor)
                        @php($prediction = $predictions->get($sensor->id))
                        @php($latestLog = $sensor->logs->sortByDesc('created_at')->first())

                        <section class="sensor-card">
                            <div class="sensor-header">
                                <div>
                                    <strong>{{ $sensor->tipo }}</strong>
                                    <span>{{ $sensor->logs->count() }} leituras registradas</span>
                                </div>
                                <span class="sensor-limits">
                                    Min {{ number_format((float) $sensor->limite_min, 1, ',', '.') }} |
                                    Max {{ number_format((float) $sensor->limite_max, 1, ',', '.') }}
                                </span>
                            </div>

                            <div class="sensor-meta-grid">
                                <span>Interna <strong>{{ $latestLog ? number_format((float) $latestLog->valor_leitura, 1, ',', '.') . ' C' : '--' }}</strong></span>
                                <span>Externa <strong>{{ $latestLog && $latestLog->temperatura_externa !== null ? number_format((float) $latestLog->temperatura_externa, 1, ',', '.') . ' C' : '--' }}</strong></span>
                                <span>Umidade <strong>{{ $latestLog && $latestLog->umidade_externa !== null ? number_format((float) $latestLog->umidade_externa, 1, ',', '.') . '%' : '--' }}</strong></span>
                                <span>Tampa <strong>{{ $latestLog ? ($latestLog->tampa_aberta ? 'Aberta' : 'Fechada') : '--' }}</strong></span>
                                <span>Risco <strong>{{ $latestLog->nivel_risco ?? 'Sem leitura' }}</strong></span>
                                <span>&Uacute;ltima <strong>{{ $latestLog ? $latestLog->created_at->format('d/m H:i:s') : '--' }}</strong></span>
                            </div>

                            <div class="sensor-chart">
                                <canvas id="graficoSensor{{ $sensor->id }}" data-sensor-chart="{{ $sensor->id }}"></canvas>
                            </div>

                            @if($prediction)
                                <article class="prediction-item status-{{ $prediction['status'] }}">
                                    <div class="prediction-heading">
                                        <strong>{{ $prediction['label'] }}</strong>
                                        @if($prediction['confidence'])
                                            <span>{{ $prediction['confidence'] }}% confian&ccedil;a</span>
                                        @endif
                                    </div>
                                    <p>{{ $prediction['message'] }}</p>

                                    @if($prediction['predicted_4h'] !== null)
                                        <div class="prediction-metrics">
                                            <span>Atual <b>{{ number_format($prediction['current'], 1, ',', '.') }} C</b></span>
                                            <span>Externa <b>{{ $prediction['external_temperature_current'] === null ? '--' : number_format($prediction['external_temperature_current'], 1, ',', '.') . ' C' }}</b></span>
                                            <span>Umid. <b>{{ $prediction['external_humidity_current'] === null ? '--' : number_format($prediction['external_humidity_current'], 1, ',', '.') . '%' }}</b></span>
                                            <span>Tampa <b>{{ $prediction['lid_open_current'] ? 'Aberta' : 'Fechada' }}</b></span>
                                            <span>2h <b>{{ number_format($prediction['predicted_2h'], 1, ',', '.') }} C</b></span>
                                            <span>4h <b>{{ number_format($prediction['predicted_4h'], 1, ',', '.') }} C</b></span>
                                            <span>Tend. <b>{{ number_format($prediction['trend_per_hour'], 2, ',', '.') }} C/h</b></span>
                                        </div>
                                    @endif

                                    <small>{{ $prediction['recommendation'] }}</small>
                                </article>
                            @endif
                        </section>
                    @empty
                        <p class="empty-state">Aguardando calibra&ccedil;&atilde;o de sensores para este cooler.</p>
                    @endforelse
                </div>
            </article>
        @empty
            <section class="empty-panel">
                <h3>Infraestrutura vazia</h3>
                <p>Nenhum cooler inteligente foi alocado para esta empresa pelo suporte t&eacute;cnico.</p>
            </section>
        @endforelse
    </div>

    <section class="historico-relatorio dashboard-history">
        <div class="topo-historico">
            <h2>&Uacute;ltimas leituras recebidas</h2>
            <small>{{ $stats['ultima_leitura'] ? 'Atualizado em ' . $stats['ultima_leitura']->format('d/m/Y H:i:s') : 'Sem leituras registradas' }}</small>
        </div>

        <div class="tabela-scroll">
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th>Hor&aacute;rio</th>
                        <th>Equipamento</th>
                        <th>Sensor</th>
                        <th>Temp. interna</th>
                        <th>Temp. externa</th>
                        <th>Umidade externa</th>
                        <th>Tampa</th>
                        <th>Risco</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->sensor?->equipamento?->nome ?? '--' }}</td>
                            <td>{{ $log->sensor?->tipo ?? '--' }}</td>
                            <td><strong>{{ number_format((float) $log->valor_leitura, 1, ',', '.') }} C</strong></td>
                            <td>{{ $log->temperatura_externa === null ? '--' : number_format((float) $log->temperatura_externa, 1, ',', '.') . ' C' }}</td>
                            <td>{{ $log->umidade_externa === null ? '--' : number_format((float) $log->umidade_externa, 1, ',', '.') . '%' }}</td>
                            <td>{{ $log->tampa_aberta ? 'Aberta' : 'Fechada' }}</td>
                            <td><span class="status-pill">{{ $log->nivel_risco }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Nenhuma leitura registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        window.thermoDashboardCharts = @json($charts);
    </script>
    @if($simulation['active'])
        <script>
            const thermoSimulationUrl = @json(route('dashboard.simulation.tick'));
            const thermoSimulationToken = @json(csrf_token());

            window.setInterval(function () {
                window.fetch(thermoSimulationUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': thermoSimulationToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function (response) {
                    return response.ok ? response.json() : null;
                }).then(function (payload) {
                    if (!payload) return;

                    const created = document.querySelector('[data-simulation-created]');
                    const live = document.querySelector('[data-simulation-live]');

                    if (created) {
                        created.textContent = payload.readings_created + ' leituras geradas nesta sessao';
                    }

                    if (live && payload.created > 0) {
                        live.textContent = 'Ultima coleta: +' + payload.created + ' leitura(s)';
                    }
                }).catch(function () {});
            }, 5000);

            window.setTimeout(function () {
                window.location.reload();
            }, 12000);
        </script>
    @endif
    <script src="{{ asset('assets/js/dashboard-charts.js') }}"></script>
    <script src="{{ asset('assets/js/export-feedback.js') }}"></script>
@endpush
