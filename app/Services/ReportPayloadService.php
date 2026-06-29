<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\LogTelemetria;
use Illuminate\Support\Collection;

class ReportPayloadService
{
    public function __construct(private readonly TelemetriaAnalyticsService $analytics) {}

    public function dashboard(Empresa $empresa, array $dashboard): array
    {
        return [
            'title' => 'Relatorio do Dashboard',
            'subtitle' => 'Resumo operacional da cadeia fria',
            'empresa' => $empresa->razao_social,
            'filters' => [['label' => 'Escopo', 'value' => 'Dashboard geral']],
            'summary' => $this->summaryCards([
                'Temperatura media' => $this->temperature($dashboard['stats']['temperatura_media']),
                'Maior temperatura' => $this->temperature($dashboard['stats']['temperatura_maxima'] ?? null),
                'Menor temperatura' => $this->temperature($dashboard['stats']['temperatura_minima'] ?? null),
                'Temp. externa media' => $this->temperature($dashboard['stats']['temperatura_externa_media'] ?? null),
                'Umidade externa media' => $this->percent($dashboard['stats']['umidade_externa_media'] ?? null),
                'Sensores' => $dashboard['stats']['sensores_total'],
                'Alertas' => $dashboard['stats']['alertas_total'],
                'Score geral' => $dashboard['stats']['score_geral'].'/100',
                'Tempo fora da faixa' => $this->minutes($dashboard['stats']['tempo_fora_faixa']),
                'Vacinas em risco' => $dashboard['stats']['vacinas_em_risco'],
            ]),
            'analysis' => $this->predictionRows($dashboard['predictions']->sortByDesc('risk_score')->take(8)),
            'chart_data' => $this->chartTables($dashboard['charts']),
            'tables' => [
                $this->occurrencesTable($dashboard['occurrences'] ?? collect()),
                $this->readingsTable($dashboard['latestLogs'] ?? collect()),
            ],
        ];
    }

    public function operationalReport(Empresa $empresa, array $report): array
    {
        return [
            'title' => 'Relatorio Operacional',
            'subtitle' => 'Temperaturas, ocorrencias e analise preditiva',
            'empresa' => $empresa->razao_social,
            'filters' => $this->analytics->filterLabels($report['filters'], $report['options']['equipamentos'], $report['options']['sensores']),
            'summary' => $this->summaryCards([
                'Leituras' => $report['summary']['leituras'],
                'Alertas' => $report['summary']['alertas'],
                'Criticos' => $report['summary']['criticos'],
                'Temperatura media' => $this->temperature($report['summary']['media']),
                'Minima' => $this->temperature($report['summary']['minima']),
                'Maxima' => $this->temperature($report['summary']['maxima']),
                'Temp. externa media' => $this->temperature($report['summary']['temperatura_externa_media'] ?? null),
                'Umidade externa media' => $this->percent($report['summary']['umidade_externa_media'] ?? null),
                'Aberturas de tampa' => $report['summary']['tampa_aberta'] ?? 0,
                'Ocorrencias' => $report['summary']['ocorrencias'],
                'Tempo fora da faixa' => $this->minutes($report['summary']['tempo_fora_faixa']),
            ]),
            'analysis' => $this->predictionRows(collect($report['predictions'])),
            'chart_data' => $this->chartTables($report['charts']),
            'tables' => [
                $this->occurrencesTable(collect($report['occurrences'] ?? [])),
                $this->readingsTable(collect($report['logs'] ?? [])),
            ],
        ];
    }

    public function prediction(Empresa $empresa, array $prediction): array
    {
        return [
            'title' => 'Relatorio de Inteligencia Termica',
            'subtitle' => 'Score de risco, previsao e prevencao de perda de vacinas',
            'empresa' => $empresa->razao_social,
            'filters' => $this->analytics->filterLabels($prediction['filters'], $prediction['options']['equipamentos'], $prediction['options']['sensores']),
            'summary' => $this->summaryCards([
                'Score geral' => $prediction['stats']['score_geral'].'/100',
                'Maior score' => $prediction['stats']['maior_score'].'/100',
                'Sensores ativos' => $prediction['stats']['sensores_total'],
                'Sem comunicacao' => $prediction['stats']['sensores_sem_comunicacao'],
                'Vacinas em risco' => $prediction['stats']['vacinas_em_risco'],
                'Ocorrencias' => $prediction['stats']['ocorrencias_total'],
                'Tempo fora da faixa' => $this->minutes($prediction['stats']['tempo_fora_faixa']),
                'Temperatura media' => $this->temperature($prediction['stats']['temperatura_media']),
                'Temp. externa media' => $this->temperature($prediction['stats']['temperatura_externa_media'] ?? null),
                'Aberturas de tampa' => $prediction['stats']['tampa_aberta_recente'] ?? 0,
            ]),
            'analysis' => $this->predictionRows(collect($prediction['predictions'])),
            'chart_data' => $this->chartTables($prediction['charts']),
            'tables' => [
                $this->occurrencesTable(collect($prediction['occurrences'] ?? [])),
                $this->readingsTable(collect($prediction['latestLogs'] ?? [])),
            ],
        ];
    }

    public function csvSections(array $payload): array
    {
        $sections = [
            [
                'title' => 'Resumo',
                'headers' => ['Indicador', 'Valor'],
                'rows' => collect($payload['summary'])->map(fn (array $item) => [$item['label'], $item['value']])->all(),
            ],
            [
                'title' => 'Filtros aplicados',
                'headers' => ['Filtro', 'Valor'],
                'rows' => collect($payload['filters'])->map(fn (array $item) => [$item['label'], $item['value']])->all(),
            ],
            [
                'title' => 'Analise Preditiva',
                'headers' => ['Equipamento', 'Sensor', 'Status', 'Score', 'Tendencia', 'Previsao 2h', 'Previsao 4h', 'Recomendacao'],
                'rows' => $payload['analysis'],
            ],
        ];

        foreach ($payload['chart_data'] as $chart) {
            $sections[] = [
                'title' => 'Grafico - '.$chart['title'],
                'headers' => $chart['headers'],
                'rows' => $chart['rows'],
            ];
        }

        foreach ($payload['tables'] as $table) {
            $sections[] = $table;
        }

        return $sections;
    }

    private function summaryCards(array $items): array
    {
        return collect($items)
            ->map(fn ($value, string $label) => ['label' => $label, 'value' => (string) $value])
            ->values()
            ->all();
    }

    private function predictionRows(Collection $predictions): array
    {
        return $predictions->map(fn (array $prediction) => [
            $prediction['equipamento'] ?? '',
            $prediction['sensor'] ?? '',
            $prediction['label'] ?? '',
            ($prediction['risk_score'] ?? 0).'/100',
            $prediction['trend_per_hour'] === null ? 'Sem tendencia' : number_format((float) $prediction['trend_per_hour'], 2, ',', '.').' C/h',
            $this->temperature($prediction['predicted_2h'] ?? null),
            $this->temperature($prediction['predicted_4h'] ?? null),
            $prediction['recommendation'] ?? '',
        ])->values()->all();
    }

    private function chartTables(array $charts): array
    {
        $tables = [];

        foreach ([
            'daily_average' => 'Evolucao de temperatura',
            'equipment_average' => 'Comparacao por equipamento',
            'score_by_sensor' => 'Score por sensor',
            'occurrences_by_day' => 'Ocorrencias por dia',
            'out_of_range_by_sensor' => 'Tempo fora da faixa por sensor',
        ] as $key => $title) {
            if (empty($charts[$key]['labels']) || empty($charts[$key]['values'])) {
                continue;
            }

            $tables[] = [
                'title' => $title,
                'headers' => ['Item', 'Valor'],
                'rows' => collect($charts[$key]['labels'])->map(function ($label, $index) use ($charts, $key) {
                    return [(string) $label, (string) ($charts[$key]['values'][$index] ?? '')];
                })->all(),
            ];
        }

        if (! empty($charts['projection']['labels']) && ! empty($charts['projection']['values'])) {
            $tables[] = [
                'title' => 'Previsao de tendencia - '.($charts['projection']['label'] ?? ''),
                'headers' => ['Periodo', 'Temperatura'],
                'rows' => collect($charts['projection']['labels'])->map(function ($label, $index) use ($charts) {
                    return [(string) $label, $this->temperature($charts['projection']['values'][$index] ?? null)];
                })->all(),
            ];
        }

        return $tables;
    }

    private function occurrencesTable(Collection $occurrences): array
    {
        return [
            'title' => 'Historico de Ocorrencias Termicas',
            'headers' => ['Inicio', 'Fim', 'Equipamento', 'Sensor', 'Min', 'Max', 'Duracao', 'Status', 'Recomendacao'],
            'rows' => $occurrences->map(fn (array $occurrence) => [
                $occurrence['started_at']->format('d/m/Y H:i'),
                $occurrence['ended_at'] ? $occurrence['ended_at']->format('d/m/Y H:i') : 'Em aberto',
                $occurrence['equipamento'] ?? '',
                $occurrence['sensor'] ?? '',
                $this->temperature($occurrence['min_value'] ?? null),
                $this->temperature($occurrence['max_value'] ?? null),
                $this->minutes($occurrence['duration_minutes'] ?? 0),
                $occurrence['status_label'] ?? '',
                $occurrence['recommendation'] ?? '',
            ])->values()->all(),
        ];
    }

    private function readingsTable(Collection $logs): array
    {
        return [
            'title' => 'Leituras Exportadas',
            'headers' => ['Data', 'Equipamento', 'Sensor', 'Temp. interna', 'Temp. externa', 'Umidade externa', 'Tampa', 'Risco'],
            'rows' => $logs->map(fn (LogTelemetria $log) => [
                $log->created_at->format('d/m/Y H:i'),
                $log->sensor?->equipamento?->nome ?? '',
                $log->sensor?->tipo ?? '',
                $this->temperature($log->valor_leitura),
                $this->temperature($log->temperatura_externa),
                $this->percent($log->umidade_externa),
                $log->tampa_aberta ? 'Aberta' : 'Fechada',
                $log->nivel_risco,
            ])->values()->all(),
        ];
    }

    private function temperature(mixed $value): string
    {
        return $value === null ? '--' : number_format((float) $value, 1, ',', '.').' C';
    }

    private function percent(mixed $value): string
    {
        return $value === null ? '--' : number_format((float) $value, 1, ',', '.').'%';
    }

    private function minutes(int|float $minutes): string
    {
        $minutes = (int) round($minutes);
        if ($minutes < 60) {
            return $minutes.' min';
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'min';
    }
}
