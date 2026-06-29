@extends('layouts.index')

@section('title', 'Dashboard | THERMO PREDICT')
@section('subtitle', 'Monitoramento em Tempo Real - ' . $empresa->razao_social)

@push('scripts-head')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
@endpush

@section('content')
<main class="container">
    <div class="titulo-pagina painel-titulo">
        <div>
            <h2>Vis&atilde;o Geral da Rede de Frio</h2>
            <p>Acompanhe telemetria, tend&ecirc;ncias e riscos dos coolers da empresa <strong>{{ $empresa->razao_social }}</strong>.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('predicao') }}" class="btn-principal btn-auto">Ver an&aacute;lise preditiva</a>
            <a href="{{ route('dashboard.pdf') }}" class="btn-secundario btn-auto" data-export-link>Exportar PDF</a>
            <a href="{{ route('dashboard.csv') }}" class="btn-secundario btn-auto" data-export-link>Exportar CSV</a>
            @if(auth()->user()->perfil === 'admin')
                <a href="{{ route('admin.clientes') }}" class="btn-secundario btn-auto">&larr; Voltar para Clientes</a>
            @endif
        </div>
    </div>

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
        <article class="card status">
            <h3>Predi&ccedil;&otilde;es em aten&ccedil;&atilde;o</h3>
            <p>{{ $predictionSummary['at_risk'] }}</p>
        </article>
    </section>

    <section class="prediction-overview">
        <div>
            <strong>An&aacute;lise preditiva</strong>
            <span>{{ $predictionSummary['stable'] }} est&aacute;veis</span>
            <span>{{ $predictionSummary['at_risk'] }} em aten&ccedil;&atilde;o</span>
            <span>{{ $predictionSummary['insufficient'] + $predictionSummary['stale'] }} sem previs&atilde;o v&aacute;lida</span>
        </div>
        <small>
            Baseada no hist&oacute;rico real de telemetria. Leituras simuladas existem apenas quando carregadas pelo seeder de demonstra&ccedil;&atilde;o.
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
                                <span>Atual <strong>{{ $latestLog ? number_format((float) $latestLog->valor_leitura, 1, ',', '.') . ' C' : '--' }}</strong></span>
                                <span>Risco <strong>{{ $latestLog->nivel_risco ?? 'Sem leitura' }}</strong></span>
                                <span>&Uacute;ltima <strong>{{ $latestLog ? $latestLog->created_at->format('d/m H:i') : '--' }}</strong></span>
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
            <small>{{ $stats['ultima_leitura'] ? 'Atualizado em ' . $stats['ultima_leitura']->format('d/m/Y H:i') : 'Sem leituras registradas' }}</small>
        </div>

        <div class="tabela-scroll">
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th>Hor&aacute;rio</th>
                        <th>Equipamento</th>
                        <th>Sensor</th>
                        <th>Leitura</th>
                        <th>Risco</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->sensor?->equipamento?->nome ?? '--' }}</td>
                            <td>{{ $log->sensor?->tipo ?? '--' }}</td>
                            <td><strong>{{ number_format((float) $log->valor_leitura, 1, ',', '.') }} C</strong></td>
                            <td><span class="status-pill">{{ $log->nivel_risco }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Nenhuma leitura registrada.</td>
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
    <script src="{{ asset('assets/js/dashboard-charts.js') }}"></script>
    <script src="{{ asset('assets/js/export-feedback.js') }}"></script>
@endpush
