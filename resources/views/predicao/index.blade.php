@extends('layouts.index')

@section('title', 'Inteligencia Termica | THERMO PREDICT')
@section('subtitle', 'Predicao e Prevencao de Perda de Vacinas')

@push('scripts-head')
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
@endpush

@section('content')
<main class="container">
    <div class="titulo-pagina painel-titulo">
        <div>
            <h2>Intelig&ecirc;ncia T&eacute;rmica</h2>
            <p>Score de risco, alertas preventivos e recomenda&ccedil;&otilde;es para evitar perda de vacinas em <strong>{{ $empresa->razao_social }}</strong>.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('predicao.pdf', request()->query()) }}" class="btn-secundario btn-auto" data-export-link>Exportar PDF</a>
            <a href="{{ route('predicao.csv', request()->query()) }}" class="btn-secundario btn-auto" data-export-link>Exportar CSV</a>
            <a href="{{ route('relatorios', request()->query()) }}" class="btn-principal btn-auto">Gerar relat&oacute;rio de risco</a>
        </div>
    </div>

    <form class="filtros-relatorio" method="GET">
        <div class="campo-filtro">
            <label for="equipamento_id">Equipamento</label>
            <select id="equipamento_id" name="equipamento_id">
                <option value="">Todos</option>
                @foreach($prediction['options']['equipamentos'] as $equipamento)
                    <option value="{{ $equipamento->id }}" @selected((string) $prediction['filters']['equipamento_id'] === (string) $equipamento->id)>
                        {{ $equipamento->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="sensor_id">Sensor</label>
            <select id="sensor_id" name="sensor_id">
                <option value="">Todos</option>
                @foreach($prediction['options']['sensores'] as $sensor)
                    <option value="{{ $sensor->id }}" @selected((string) $prediction['filters']['sensor_id'] === (string) $sensor->id)>
                        {{ $sensor->equipamento?->nome }} / {{ $sensor->tipo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="todos" @selected($prediction['filters']['status'] === 'todos')>Todos</option>
                <option value="normal" @selected($prediction['filters']['status'] === 'normal')>Normal</option>
                <option value="atencao" @selected($prediction['filters']['status'] === 'atencao')>Aten&ccedil;&atilde;o</option>
                <option value="risco" @selected($prediction['filters']['status'] === 'risco')>Risco</option>
                <option value="critico" @selected($prediction['filters']['status'] === 'critico')>Cr&iacute;tico</option>
                <option value="perda_provavel" @selected($prediction['filters']['status'] === 'perda_provavel')>Perda prov&aacute;vel</option>
                <option value="desatualizado" @selected($prediction['filters']['status'] === 'desatualizado')>Dados desatualizados</option>
            </select>
        </div>

        <div class="campo-filtro">
            <label for="periodo">Per&iacute;odo</label>
            <select id="periodo" name="periodo">
                @foreach([7, 30, 90] as $days)
                    <option value="{{ $days }}" @selected((int) $prediction['filters']['periodo'] === $days)>{{ $days }} dias</option>
                @endforeach
            </select>
        </div>

        <div class="campo-filtro">
            <label for="inicio">In&iacute;cio</label>
            <input id="inicio" type="date" name="inicio" value="{{ $prediction['filters']['inicio'] }}">
        </div>

        <div class="campo-filtro">
            <label for="fim">Fim</label>
            <input id="fim" type="date" name="fim" value="{{ $prediction['filters']['fim'] }}">
        </div>

        <div class="campo-filtro filtro-actions">
            <button type="submit" class="btn-principal">Aplicar</button>
            <a href="{{ route('predicao') }}" class="btn-secundario">Limpar</a>
        </div>
    </form>

    <section class="score-panel risk-{{ $prediction['predictionSummary']['highest']['status'] ?? 'normal' }}">
        <div class="score-main">
            <span>&Iacute;ndice de Risco T&eacute;rmico</span>
            <strong>{{ $prediction['stats']['score_geral'] }}</strong>
            <small>0 a 30 seguro, 31 a 60 aten&ccedil;&atilde;o, 61 a 80 risco, 81 a 100 cr&iacute;tico.</small>
        </div>
        <div class="score-copy">
            <h3>Previs&atilde;o de risco</h3>
            <p>{{ $prediction['stats']['previsao_risco'] }}</p>
        </div>
    </section>

    <section class="cards-relatorio">
        <article class="card-relatorio"><h3>Temperatura m&eacute;dia</h3><p>{{ $prediction['stats']['temperatura_media'] === null ? '--' : number_format($prediction['stats']['temperatura_media'], 1, ',', '.') . ' C' }}</p></article>
        <article class="card-relatorio"><h3>Maior temperatura</h3><p>{{ $prediction['stats']['temperatura_maxima'] === null ? '--' : number_format($prediction['stats']['temperatura_maxima'], 1, ',', '.') . ' C' }}</p></article>
        <article class="card-relatorio"><h3>Menor temperatura</h3><p>{{ $prediction['stats']['temperatura_minima'] === null ? '--' : number_format($prediction['stats']['temperatura_minima'], 1, ',', '.') . ' C' }}</p></article>
        <article class="card-relatorio"><h3>Temp. externa m&eacute;dia</h3><p>{{ $prediction['stats']['temperatura_externa_media'] === null ? '--' : number_format($prediction['stats']['temperatura_externa_media'], 1, ',', '.') . ' C' }}</p></article>
        <article class="card-relatorio"><h3>Umidade externa</h3><p>{{ $prediction['stats']['umidade_externa_media'] === null ? '--' : number_format($prediction['stats']['umidade_externa_media'], 1, ',', '.') . '%' }}</p></article>
        <article class="card-relatorio"><h3>Tampa aberta hoje</h3><p>{{ $prediction['stats']['tampa_aberta_recente'] }}</p></article>
        <article class="card-relatorio"><h3>Vacinas em risco</h3><p>{{ $prediction['stats']['vacinas_em_risco'] }}</p></article>
        <article class="card-relatorio"><h3>Sensores sem comunica&ccedil;&atilde;o</h3><p>{{ $prediction['stats']['sensores_sem_comunicacao'] }}</p></article>
        <article class="card-relatorio"><h3>Ocorr&ecirc;ncias</h3><p>{{ $prediction['stats']['ocorrencias_total'] }}</p></article>
        <article class="card-relatorio"><h3>Tempo fora da faixa</h3><p class="texto-menor">{{ intdiv($prediction['stats']['tempo_fora_faixa'], 60) }}h {{ $prediction['stats']['tempo_fora_faixa'] % 60 }}min</p></article>
        <article class="card-relatorio"><h3>Maior score</h3><p>{{ $prediction['stats']['maior_score'] }}/100</p></article>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Score de risco por sensor</h3>
            <div class="grafico-wrapper"><canvas id="predictionScoreChart"></canvas></div>
        </div>
        <div class="grafico-relatorio">
            <h3>Status dos sensores</h3>
            <div class="grafico-wrapper"><canvas id="predictionStatusChart"></canvas></div>
        </div>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Temperatura e faixa segura</h3>
            <p class="grafico-subtitulo" data-temperature-range-label></p>
            <div class="grafico-wrapper"><canvas id="temperatureRangeChart"></canvas></div>
        </div>
        <div class="grafico-relatorio">
            <h3>Tempo fora da faixa por sensor</h3>
            <div class="grafico-wrapper"><canvas id="outOfRangeChart"></canvas></div>
        </div>
    </section>

    <section class="linha-relatorios">
        <div class="grafico-relatorio">
            <h3>Ocorr&ecirc;ncias por dia</h3>
            <div class="grafico-wrapper"><canvas id="occurrencesDayChart"></canvas></div>
        </div>
        <div class="grafico-relatorio">
            <h3>Previs&atilde;o de tend&ecirc;ncia</h3>
            <p class="grafico-subtitulo" data-prediction-projection-label></p>
            <div class="grafico-wrapper"><canvas id="predictionProjectionChart"></canvas></div>
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>An&aacute;lise Preditiva</h2>
            <small>Recomenda&ccedil;&otilde;es autom&aacute;ticas por sensor.</small>
        </div>

        <div class="prediction-list-report">
            @forelse($prediction['predictions'] as $item)
                <article class="prediction-item status-{{ $item['status'] }}">
                    <div class="prediction-heading">
                        <strong>{{ $item['equipamento'] ?? 'Equipamento' }} / {{ $item['sensor'] }}</strong>
                        <span>{{ $item['risk_score'] }}/100</span>
                    </div>
                    <p><strong>{{ $item['label'] }}:</strong> {{ $item['message'] }}</p>
                    <div class="prediction-metrics">
                        <span>Atual <b>{{ $item['current'] === null ? '--' : number_format($item['current'], 1, ',', '.') . ' C' }}</b></span>
                        <span>Externa <b>{{ $item['external_temperature_current'] === null ? '--' : number_format($item['external_temperature_current'], 1, ',', '.') . ' C' }}</b></span>
                        <span>Umid. <b>{{ $item['external_humidity_current'] === null ? '--' : number_format($item['external_humidity_current'], 1, ',', '.') . '%' }}</b></span>
                        <span>Tampa <b>{{ $item['lid_open_current'] ? 'Aberta' : 'Fechada' }}</b></span>
                        <span>2h <b>{{ $item['predicted_2h'] === null ? '--' : number_format($item['predicted_2h'], 1, ',', '.') . ' C' }}</b></span>
                        <span>4h <b>{{ $item['predicted_4h'] === null ? '--' : number_format($item['predicted_4h'], 1, ',', '.') . ' C' }}</b></span>
                        <span>Chance <b>{{ $item['chance_excursion'] }}%</b></span>
                    </div>
                    <small>{{ $item['recommendation'] }}</small>
                </article>
            @empty
                <p class="empty-state">Nenhum sensor dispon&iacute;vel para an&aacute;lise.</p>
            @endforelse
        </div>
    </section>

    <section class="historico-relatorio">
        <div class="topo-historico">
            <h2>Hist&oacute;rico de ocorr&ecirc;ncias t&eacute;rmicas</h2>
            <small>Rastreabilidade para auditoria e a&ccedil;&otilde;es corretivas.</small>
        </div>

        <div class="tabela-scroll">
            <table class="tabela-alertas">
                <thead>
                    <tr>
                        <th>Sensor</th>
                        <th>Local</th>
                        <th>Temp. m&iacute;n.</th>
                        <th>Temp. m&aacute;x.</th>
                        <th>In&iacute;cio</th>
                        <th>Fim</th>
                        <th>Dura&ccedil;&atilde;o</th>
                        <th>Status</th>
                        <th>A&ccedil;&atilde;o</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prediction['occurrences'] as $occurrence)
                        <tr>
                            <td>{{ $occurrence['sensor'] }}</td>
                            <td>{{ $occurrence['local'] ?? '--' }}</td>
                            <td>{{ number_format($occurrence['min_value'], 1, ',', '.') }} C</td>
                            <td>{{ number_format($occurrence['max_value'], 1, ',', '.') }} C</td>
                            <td>{{ $occurrence['started_at']->format('d/m/Y H:i') }}</td>
                            <td>{{ $occurrence['ended_at'] ? $occurrence['ended_at']->format('d/m/Y H:i') : 'Em aberto' }}</td>
                            <td>{{ intdiv($occurrence['duration_minutes'], 60) }}h {{ $occurrence['duration_minutes'] % 60 }}min</td>
                            <td><span class="risk-badge risk-{{ $occurrence['status'] }}">{{ $occurrence['status_label'] }}</span></td>
                            <td>{{ $occurrence['acao_tomada'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Nenhuma ocorr&ecirc;ncia t&eacute;rmica encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection

@push('scripts')
    <script>
        window.thermoPredictionCharts = @json($prediction['charts']);
    </script>
    <script src="{{ asset('assets/js/predicao.js') }}"></script>
    <script src="{{ asset('assets/js/export-feedback.js') }}"></script>
@endpush
