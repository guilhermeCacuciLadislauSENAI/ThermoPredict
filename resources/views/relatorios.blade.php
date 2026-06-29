@extends('layouts.index')

@section('title', 'Relatorios | THERMO PREDICT')
@section('subtitle', 'Central de Relatorios e Monitoramento Operacional')

@push('scripts-head')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
@endpush

@section('content')
<main class="container">
    <div class="titulo-pagina painel-titulo">
        <div>
            <h2>Relat&oacute;rios Operacionais</h2>
            <p>Filtros e indicadores calculados com as leituras reais da empresa <strong>{{ $empresa->razao_social }}</strong>.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('relatorios.pdf', request()->query()) }}" class="btn-secundario btn-auto" data-export-link>Exportar PDF</a>
            <a href="{{ route('relatorios.csv', request()->query()) }}" class="btn-secundario btn-auto" data-export-link>Exportar CSV</a>
            <a href="{{ route('predicao') }}" class="btn-principal btn-auto">Ver recomenda&ccedil;&otilde;es</a>
        </div>
    </div>

    <form class="filtros-relatorio" method="GET">
        <div class="campo-filtro">
            <label for="equipamento_id">Equipamento</label>
            <select id="equipamento_id" name="equipamento_id">
                <option value="">Todos</option>
                @foreach($report['options']['equipamentos'] as $equipamento)
                    <option value="{{ $equipamento->id }}" @selected((string) $report['filters']['equipamento_id'] === (string) $equipamento->id)>
                        {{ $equipamento->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="sensor_id">Sensor</label>
            <select id="sensor_id" name="sensor_id">
                <option value="">Todos</option>
                @foreach($report['options']['sensores'] as $sensor)
                    <option value="{{ $sensor->id }}" @selected((string) $report['filters']['sensor_id'] === (string) $sensor->id)>
                        {{ $sensor->equipamento?->nome }} / {{ $sensor->tipo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="status">Risco</label>
            <select id="status" name="status">
                <option value="todos" @selected($report['filters']['status'] === 'todos')>Todos</option>
                <option value="normal" @selected($report['filters']['status'] === 'normal')>Normal</option>
                <option value="alerta" @selected($report['filters']['status'] === 'alerta')>Atencao</option>
                <option value="critico" @selected($report['filters']['status'] === 'critico')>Critico</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="periodo">Periodo rapido</label>
            <select id="periodo" name="periodo">
                @foreach([7, 30, 90] as $days)
                    <option value="{{ $days }}" @selected((int) $report['filters']['periodo'] === $days)>{{ $days }} dias</option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="inicio">Inicio</label>
            <input id="inicio" type="date" name="inicio" value="{{ $report['filters']['inicio'] }}">
        </div>

        <div class="campo-filtro">
            <label for="fim">Fim</label>
            <input id="fim" type="date" name="fim" value="{{ $report['filters']['fim'] }}">
        </div>

        <div class="campo-filtro filtro-actions">
            <button type="submit" class="btn-principal">Aplicar</button>
            <a href="{{ route('relatorios') }}" class="btn-secundario">Limpar</a>
        </div>
    </form>

    <section class="cards-relatorio">
        <article class="card-relatorio">
            <h3>Leituras</h3>
            <p>{{ $report['summary']['leituras'] }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Total de Alertas</h3>
            <p>{{ $report['summary']['alertas'] }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Ocorr&ecirc;ncias Cr&iacute;ticas</h3>
            <p>{{ $report['summary']['criticos'] }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Temperatura M&eacute;dia</h3>
            <p>{{ $report['summary']['media'] === null ? '--' : number_format($report['summary']['media'], 1, ',', '.') . ' C' }}</p>
        </article>
    </section>

    <section class="cards-relatorio cards-relatorio-secundarios">
        <article class="card-relatorio">
            <h3>M&iacute;nima</h3>
            <p>{{ $report['summary']['minima'] === null ? '--' : number_format($report['summary']['minima'], 1, ',', '.') . ' C' }}</p>
        </article>
        <article class="card-relatorio">
            <h3>M&aacute;xima</h3>
            <p>{{ $report['summary']['maxima'] === null ? '--' : number_format($report['summary']['maxima'], 1, ',', '.') . ' C' }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Sensores com dados</h3>
            <p>{{ $report['summary']['sensores'] }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Ocorr&ecirc;ncias</h3>
            <p>{{ $report['summary']['ocorrencias'] }}</p>
        </article>
        <article class="card-relatorio">
            <h3>Tempo fora da faixa</h3>
            <p class="texto-menor">{{ intdiv($report['summary']['tempo_fora_faixa'], 60) }}h {{ $report['summary']['tempo_fora_faixa'] % 60 }}min</p>
        </article>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Evolu&ccedil;&atilde;o de temperatura</h3>
            <div class="grafico-wrapper">
                <canvas id="reportDailyAverageChart"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Status das leituras</h3>
            <div class="grafico-wrapper">
                <canvas id="reportRiskDistributionChart"></canvas>
            </div>
        </div>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Comparativo por equipamento</h3>
            <div class="grafico-wrapper">
                <canvas id="reportEquipmentAverageChart"></canvas>
            </div>
        </div>

        <div class="grafico-relatorio">
            <h3>Proje&ccedil;&atilde;o preditiva</h3>
            <p class="grafico-subtitulo" data-report-projection-label></p>
            <div class="grafico-wrapper">
                <canvas id="reportProjectionChart"></canvas>
            </div>
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Indicadores inteligentes</h2>
            <small>Previs&otilde;es calculadas por sensor com historico recente.</small>
        </div>

        <div class="prediction-list-report">
            @forelse($report['predictions'] as $prediction)
                <article class="prediction-item status-{{ $prediction['status'] }}">
                    <div class="prediction-heading">
                        <strong>{{ $prediction['equipamento'] ?? 'Equipamento' }} / {{ $prediction['sensor'] }}</strong>
                        <span>{{ $prediction['label'] }}</span>
                    </div>
                    <p>{{ $prediction['message'] }}</p>
                    <small>{{ $prediction['recommendation'] }}</small>
                </article>
            @empty
                <p class="empty-state">Nenhum sensor disponivel para analise preditiva.</p>
            @endforelse
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Ocorr&ecirc;ncias t&eacute;rmicas</h2>
            <small>Eventos derivados de leituras fora da faixa segura.</small>
        </div>

        <div class="tabela-scroll">
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th>In&iacute;cio</th>
                        <th>Fim</th>
                        <th>Equipamento</th>
                        <th>Sensor</th>
                        <th>M&iacute;n.</th>
                        <th>M&aacute;x.</th>
                        <th>Dura&ccedil;&atilde;o</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['occurrences'] as $occurrence)
                        <tr>
                            <td>{{ $occurrence['started_at']->format('d/m/Y H:i') }}</td>
                            <td>{{ $occurrence['ended_at'] ? $occurrence['ended_at']->format('d/m/Y H:i') : 'Em aberto' }}</td>
                            <td>{{ $occurrence['equipamento'] ?? '--' }}</td>
                            <td>{{ $occurrence['sensor'] ?? '--' }}</td>
                            <td>{{ number_format($occurrence['min_value'], 1, ',', '.') }} C</td>
                            <td>{{ number_format($occurrence['max_value'], 1, ',', '.') }} C</td>
                            <td>{{ intdiv($occurrence['duration_minutes'], 60) }}h {{ $occurrence['duration_minutes'] % 60 }}min</td>
                            <td><span class="risk-badge risk-{{ $occurrence['status'] }}">{{ $occurrence['status_label'] }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Nenhuma ocorr&ecirc;ncia t&eacute;rmica encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Hist&oacute;rico Operacional</h2>
            <small>Mostrando at&eacute; 100 registros filtrados.</small>
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
                    @forelse($report['logs'] as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->sensor?->equipamento?->nome ?? '--' }}</td>
                            <td>{{ $log->sensor?->tipo ?? '--' }}</td>
                            <td>{{ number_format((float) $log->valor_leitura, 1, ',', '.') }} C</td>
                            <td><span class="status-pill">{{ $log->nivel_risco }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Nenhum resultado encontrado para os filtros selecionados.</td>
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
        window.thermoReportCharts = @json($report['charts']);
    </script>
    <script defer src="{{ asset('assets/js/relatorios.js') }}"></script>
    <script src="{{ asset('assets/js/export-feedback.js') }}"></script>
@endpush
