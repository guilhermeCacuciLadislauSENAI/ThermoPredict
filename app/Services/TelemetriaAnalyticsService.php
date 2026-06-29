<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\LogTelemetria;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TelemetriaAnalyticsService
{
    public function __construct(private readonly TelemetriaPredictionService $predictions) {}

    public function dashboard(Empresa $empresa): array
    {
        $equipamentos = $empresa->equipamentos()
            ->with(['sensores.equipamento', 'sensores.logs' => fn ($query) => $query->orderBy('created_at')])
            ->orderBy('nome')
            ->get();
        $sensores = $equipamentos->flatMap(fn ($equipamento) => $equipamento->sensores)->values();
        $sensorPredictions = $this->buildPredictions($sensores);
        $predictionSummary = $this->predictions->summarize($sensorPredictions->values());
        $allLogs = $this->logsFor($empresa)->with('sensor.equipamento')->get();
        $occurrences = $this->buildOccurrences($allLogs);

        return [
            'empresa' => $empresa,
            'equipamentos' => $equipamentos,
            'predictions' => $sensorPredictions,
            'predictionSummary' => $predictionSummary,
            'stats' => $this->buildStats($equipamentos, $sensores, $allLogs, $sensorPredictions->values(), $occurrences),
            'charts' => $this->buildCharts($equipamentos, $sensores, $sensorPredictions, $allLogs, $occurrences),
            'latestLogs' => $allLogs->sortByDesc('created_at')->take(10)->values(),
            'occurrences' => $occurrences,
        ];
    }

    public function prediction(Empresa $empresa, array $filters = []): array
    {
        [$start, $end, $period] = $this->resolvePeriod($filters);
        $equipamentos = $empresa->equipamentos()
            ->with(['sensores.equipamento', 'sensores.logs' => fn ($query) => $query->whereBetween('created_at', [$start, $end])->orderBy('created_at')])
            ->orderBy('nome')
            ->get();
        $sensores = $equipamentos->flatMap(fn ($equipamento) => $equipamento->sensores)
            ->when(! empty($filters['equipamento_id']), fn (Collection $items) => $items->where('equipamento_id', (int) $filters['equipamento_id']))
            ->when(! empty($filters['sensor_id']), fn (Collection $items) => $items->where('id', (int) $filters['sensor_id']))
            ->values();

        $logs = $this->logsFor($empresa)
            ->with('sensor.equipamento')
            ->whereBetween('created_at', [$start, $end])
            ->when(! empty($filters['equipamento_id']), function (Builder $query) use ($filters) {
                $query->whereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('equipamento_id', (int) $filters['equipamento_id']));
            })
            ->when(! empty($filters['sensor_id']), fn (Builder $query) => $query->where('sensor_id', (int) $filters['sensor_id']))
            ->orderBy('created_at')
            ->get();

        $sensorPredictions = $this->buildPredictions($sensores)->values();
        if (! empty($filters['status']) && $filters['status'] !== 'todos') {
            $sensorPredictions = $sensorPredictions->filter(fn (array $prediction) => $prediction['status'] === $filters['status'])->values();
        }

        $occurrences = $this->buildOccurrences($logs);
        if (! empty($filters['status']) && $filters['status'] !== 'todos') {
            $occurrences = $occurrences->filter(fn (array $occurrence) => $occurrence['status'] === $filters['status'])->values();
        }

        $summary = $this->predictions->summarize($sensorPredictions);
        $stats = $this->buildStats($equipamentos, $sensores, $logs, $sensorPredictions, $occurrences);

        return [
            'empresa' => $empresa,
            'filters' => [
                'periodo' => $period,
                'inicio' => $start->format('Y-m-d'),
                'fim' => $end->format('Y-m-d'),
                'equipamento_id' => $filters['equipamento_id'] ?? '',
                'sensor_id' => $filters['sensor_id'] ?? '',
                'status' => $filters['status'] ?? 'todos',
            ],
            'period' => ['start' => $start, 'end' => $end],
            'options' => [
                'equipamentos' => $equipamentos,
                'sensores' => $equipamentos->flatMap(fn ($equipamento) => $equipamento->sensores)->values(),
            ],
            'predictions' => $sensorPredictions->sortByDesc('risk_score')->values(),
            'predictionSummary' => $summary,
            'stats' => $stats,
            'charts' => $this->buildPredictionCharts($sensores, $sensorPredictions, $logs, $occurrences),
            'occurrences' => $occurrences->sortByDesc('started_at')->values(),
            'latestLogs' => $logs->sortByDesc('created_at')->take(50)->values(),
        ];
    }

    public function report(Empresa $empresa, array $filters): array
    {
        [$start, $end, $period] = $this->resolvePeriod($filters);
        $equipamentos = $empresa->equipamentos()->with('sensores.equipamento')->orderBy('nome')->get();
        $sensores = $equipamentos->flatMap(fn ($equipamento) => $equipamento->sensores)->values();

        $logs = $this->logsFor($empresa)
            ->with('sensor.equipamento')
            ->whereBetween('created_at', [$start, $end])
            ->when(! empty($filters['equipamento_id']), function (Builder $query) use ($filters) {
                $query->whereHas('sensor', fn (Builder $sensorQuery) => $sensorQuery->where('equipamento_id', (int) $filters['equipamento_id']));
            })
            ->when(! empty($filters['sensor_id']), fn (Builder $query) => $query->where('sensor_id', (int) $filters['sensor_id']))
            ->orderBy('created_at')
            ->get();

        if (! empty($filters['status']) && $filters['status'] !== 'todos') {
            $logs = $logs->filter(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco) === $filters['status'])->values();
        }

        $filteredSensors = $sensores
            ->when(! empty($filters['equipamento_id']), fn (Collection $items) => $items->where('equipamento_id', (int) $filters['equipamento_id']))
            ->when(! empty($filters['sensor_id']), fn (Collection $items) => $items->where('id', (int) $filters['sensor_id']))
            ->values();
        $sensorPredictions = $this->buildPredictions($filteredSensors)->values();
        $occurrences = $this->buildOccurrences($logs);

        return [
            'filters' => [
                'periodo' => $period,
                'inicio' => $start->format('Y-m-d'),
                'fim' => $end->format('Y-m-d'),
                'equipamento_id' => $filters['equipamento_id'] ?? '',
                'sensor_id' => $filters['sensor_id'] ?? '',
                'status' => $filters['status'] ?? 'todos',
            ],
            'period' => ['start' => $start, 'end' => $end],
            'options' => [
                'equipamentos' => $equipamentos,
                'sensores' => $sensores,
            ],
            'summary' => $this->buildReportSummary($logs, $occurrences),
            'charts' => $this->buildReportCharts($equipamentos, $logs, $sensorPredictions, $occurrences),
            'logs' => $logs->sortByDesc('created_at')->take(100)->values(),
            'predictions' => $sensorPredictions->sortByDesc('risk_score')->take(8)->values(),
            'occurrences' => $occurrences->sortByDesc('started_at')->take(50)->values(),
        ];
    }

    public function filterLabels(array $filters, Collection $equipamentos, Collection $sensores): array
    {
        $labels = [
            ['label' => 'Data inicial', 'value' => $filters['inicio'] ?? 'Geral'],
            ['label' => 'Data final', 'value' => $filters['fim'] ?? 'Geral'],
            ['label' => 'Status', 'value' => $this->statusLabel($filters['status'] ?? 'todos')],
        ];

        if (! empty($filters['equipamento_id'])) {
            $equipamento = $equipamentos->firstWhere('id', (int) $filters['equipamento_id']);
            $labels[] = ['label' => 'Equipamento', 'value' => $equipamento?->nome ?? 'Selecionado'];
        }

        if (! empty($filters['sensor_id'])) {
            $sensor = $sensores->firstWhere('id', (int) $filters['sensor_id']);
            $labels[] = ['label' => 'Sensor', 'value' => $sensor ? $this->sensorLabel($sensor) : 'Selecionado'];
        }

        return $labels;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'normal' => 'Normal',
            'atencao' => 'Atencao',
            'risco' => 'Risco',
            'critico' => 'Critico',
            'perda_provavel' => 'Perda provavel',
            'desatualizado' => 'Dados desatualizados',
            'sem_comunicacao' => 'Sem comunicacao',
            default => 'Todos',
        };
    }

    private function buildPredictions(Collection $sensores): Collection
    {
        return $sensores->mapWithKeys(function (Sensor $sensor) {
            $logs = $sensor->relationLoaded('logs') ? $sensor->logs : null;

            return [$sensor->id => $this->predictions->analyze($sensor, $logs)];
        });
    }

    private function buildStats(Collection $equipamentos, Collection $sensores, Collection $logs, Collection $predictions, Collection $occurrences): array
    {
        $values = $logs->pluck('valor_leitura')->map(fn ($value) => (float) $value);
        $alertas = $logs->filter(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco) !== 'normal');
        $criticos = $logs->filter(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco) === 'critico');
        $sensoresSemLeitura = $sensores->filter(fn (Sensor $sensor) => ! $sensor->logs || $sensor->logs->isEmpty());
        $latest = $logs->sortByDesc('created_at')->first();

        return [
            'equipamentos_total' => $equipamentos->count(),
            'sensores_total' => $sensores->count(),
            'sensores_sem_leitura' => $sensoresSemLeitura->count(),
            'sensores_sem_comunicacao' => $predictions->whereIn('status', ['desatualizado', 'sem_comunicacao'])->count(),
            'leituras_total' => $logs->count(),
            'alertas_total' => $alertas->count(),
            'criticos_total' => $criticos->count(),
            'temperatura_media' => $values->isEmpty() ? null : round((float) $values->avg(), 1),
            'temperatura_minima' => $values->isEmpty() ? null : round((float) $values->min(), 1),
            'temperatura_maxima' => $values->isEmpty() ? null : round((float) $values->max(), 1),
            'ultima_leitura' => $latest?->created_at,
            'score_geral' => $predictions->isEmpty() ? 0 : (int) round($predictions->avg('risk_score')),
            'maior_score' => $predictions->max('risk_score') ?? 0,
            'vacinas_em_risco' => $predictions->whereIn('status', ['risco', 'critico', 'perda_provavel'])->count(),
            'tempo_fora_faixa' => (int) $occurrences->sum('duration_minutes'),
            'ocorrencias_total' => $occurrences->count(),
            'ocorrencias_abertas' => $occurrences->where('status_operacional', 'em_aberto')->count(),
            'previsao_risco' => $predictions->sortByDesc('risk_score')->first()['message'] ?? 'Sem previsao disponivel.',
        ];
    }

    private function buildCharts(Collection $equipamentos, Collection $sensores, Collection $predictions, Collection $logs, Collection $occurrences): array
    {
        $recentStart = now()->subDays(6)->startOfDay();
        $recentLogs = $logs->filter(fn (LogTelemetria $log) => $log->created_at->gte($recentStart))->values();
        $highestPrediction = $predictions->sortByDesc('risk_score')->first();

        return [
            'daily_average' => $this->dailyAverage($recentLogs, 7),
            'risk_distribution' => $this->riskDistribution($recentLogs),
            'equipment_average' => $this->equipmentAverage($equipamentos, $logs),
            'readings_by_sensor' => $this->readingsBySensor($sensores),
            'projection' => $this->projectionChart($highestPrediction),
            'sensor_series' => $sensores->mapWithKeys(fn (Sensor $sensor) => [$sensor->id => $this->sensorSeries($sensor, $predictions->get($sensor->id))]),
            'score_by_sensor' => $this->scoreBySensor($predictions),
            'occurrences_by_day' => $this->occurrencesByDay($occurrences),
        ];
    }

    private function buildPredictionCharts(Collection $sensores, Collection $predictions, Collection $logs, Collection $occurrences): array
    {
        return [
            'score_by_sensor' => $this->scoreBySensor($predictions),
            'risk_distribution' => [
                'labels' => ['Normal', 'Atencao', 'Risco', 'Critico', 'Perda provavel'],
                'values' => [
                    $predictions->where('status', 'normal')->count(),
                    $predictions->where('status', 'atencao')->count(),
                    $predictions->where('status', 'risco')->count(),
                    $predictions->where('status', 'critico')->count(),
                    $predictions->where('status', 'perda_provavel')->count(),
                ],
            ],
            'daily_average' => $this->dailyAverage($logs, null),
            'occurrences_by_day' => $this->occurrencesByDay($occurrences),
            'out_of_range_by_sensor' => $this->outOfRangeBySensor($sensores, $occurrences),
            'projection' => $this->projectionChart($predictions->sortByDesc('risk_score')->first()),
            'temperature_range' => $this->temperatureRangeChart($sensores, $predictions),
        ];
    }

    private function buildReportSummary(Collection $logs, Collection $occurrences): array
    {
        $values = $logs->pluck('valor_leitura')->map(fn ($value) => (float) $value);

        return [
            'leituras' => $logs->count(),
            'alertas' => $logs->filter(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco) !== 'normal')->count(),
            'criticos' => $logs->filter(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco) === 'critico')->count(),
            'media' => $values->isEmpty() ? null : round((float) $values->avg(), 1),
            'minima' => $values->isEmpty() ? null : round((float) $values->min(), 1),
            'maxima' => $values->isEmpty() ? null : round((float) $values->max(), 1),
            'sensores' => $logs->pluck('sensor_id')->unique()->count(),
            'ocorrencias' => $occurrences->count(),
            'tempo_fora_faixa' => (int) $occurrences->sum('duration_minutes'),
        ];
    }

    private function buildReportCharts(Collection $equipamentos, Collection $logs, Collection $predictions, Collection $occurrences): array
    {
        return [
            'daily_average' => $this->dailyAverage($logs, null),
            'risk_distribution' => $this->riskDistribution($logs),
            'equipment_average' => $this->equipmentAverage($equipamentos, $logs),
            'projection' => $this->projectionChart($predictions->sortByDesc('risk_score')->first()),
            'score_by_sensor' => $this->scoreBySensor($predictions),
            'occurrences_by_day' => $this->occurrencesByDay($occurrences),
        ];
    }

    private function buildOccurrences(Collection $logs): Collection
    {
        $occurrences = collect();

        $logs->groupBy('sensor_id')->each(function (Collection $sensorLogs) use ($occurrences) {
            $sensorLogs = $sensorLogs->sortBy('created_at')->values();
            $current = null;

            foreach ($sensorLogs as $log) {
                $sensor = $log->sensor;
                if (! $sensor) {
                    continue;
                }

                $minimum = $sensor->limite_min === null ? 2.0 : (float) $sensor->limite_min;
                $maximum = $sensor->limite_max === null ? 8.0 : (float) $sensor->limite_max;
                $value = (float) $log->valor_leitura;
                $out = $value < $minimum || $value > $maximum;

                if ($out) {
                    if (! $current) {
                        $current = [
                            'sensor_id' => $sensor->id,
                            'sensor' => $sensor->tipo,
                            'equipamento' => $sensor->equipamento?->nome,
                            'local' => $sensor->equipamento?->localizacao,
                            'started_at' => $log->created_at,
                            'ended_at' => null,
                            'min_value' => $value,
                            'max_value' => $value,
                            'readings' => 0,
                            'limit_min' => $minimum,
                            'limit_max' => $maximum,
                        ];
                    }

                    $current['readings']++;
                    $current['min_value'] = min($current['min_value'], $value);
                    $current['max_value'] = max($current['max_value'], $value);

                    continue;
                }

                if ($current) {
                    $current['ended_at'] = $log->created_at;
                    $occurrences->push($this->finalizeOccurrence($current));
                    $current = null;
                }
            }

            if ($current) {
                $occurrences->push($this->finalizeOccurrence($current));
            }
        });

        return $occurrences->filter(fn (array $occurrence) => $occurrence['readings'] >= 1)->values();
    }

    private function finalizeOccurrence(array $occurrence): array
    {
        $end = $occurrence['ended_at'] ?? now();
        $duration = max(0, $occurrence['started_at']->diffInMinutes($end));
        $status = 'risco';

        if ($duration >= 240 || $occurrence['max_value'] >= 12 || $occurrence['min_value'] <= 0) {
            $status = 'perda_provavel';
        } elseif ($duration >= 60 || $occurrence['readings'] >= 3) {
            $status = 'critico';
        } elseif ($occurrence['readings'] < 2) {
            $status = 'atencao';
        }

        return array_merge($occurrence, [
            'duration_minutes' => $duration,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_operacional' => $occurrence['ended_at'] ? 'recuperada' : 'em_aberto',
            'responsavel' => 'Responsavel tecnico',
            'acao_tomada' => $occurrence['ended_at'] ? 'Monitoramento e recuperacao registrados' : 'Acao corretiva pendente',
            'observacao' => $occurrence['readings'] < 2
                ? 'Evento ainda tratado como atencao para evitar falso positivo.'
                : 'Excursao termica detectada por leituras consecutivas.',
            'recommendation' => $status === 'perda_provavel'
                ? 'Quarentena do lote e avaliacao manual obrigatoria.'
                : 'Verificar tampa, energia, refrigeracao e registrar acao corretiva.',
        ]);
    }

    private function dailyAverage(Collection $logs, ?int $fixedDays): array
    {
        if ($fixedDays !== null) {
            $days = collect(range($fixedDays - 1, 0))->map(fn (int $offset) => Carbon::today()->subDays($offset));
        } else {
            $dates = $logs->map(fn (LogTelemetria $log) => $log->created_at->toDateString())->unique()->sort()->values();
            $days = $dates->isEmpty()
                ? collect([Carbon::today()])
                : $dates->map(fn (string $date) => Carbon::parse($date));
        }

        $grouped = $logs->groupBy(fn (LogTelemetria $log) => $log->created_at->toDateString());

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('d/m'))->values(),
            'values' => $days->map(function (Carbon $day) use ($grouped) {
                $items = $grouped->get($day->toDateString(), collect());

                return $items->avg('valor_leitura') === null ? null : round((float) $items->avg('valor_leitura'), 1);
            })->values(),
        ];
    }

    private function riskDistribution(Collection $logs): array
    {
        $counts = $logs->countBy(fn (LogTelemetria $log) => $this->riskLevel($log->nivel_risco));

        return [
            'labels' => ['Normal', 'Atencao', 'Critico'],
            'values' => [
                (int) $counts->get('normal', 0),
                (int) $counts->get('alerta', 0),
                (int) $counts->get('critico', 0),
            ],
        ];
    }

    private function equipmentAverage(Collection $equipamentos, Collection $logs): array
    {
        return [
            'labels' => $equipamentos->pluck('nome')->values(),
            'values' => $equipamentos->map(function ($equipamento) use ($logs) {
                $values = $logs->filter(fn (LogTelemetria $log) => $log->sensor?->equipamento_id === $equipamento->id);

                return $values->avg('valor_leitura') === null ? null : round((float) $values->avg('valor_leitura'), 1);
            })->values(),
        ];
    }

    private function readingsBySensor(Collection $sensores): array
    {
        $lastDay = now()->subDay();

        return [
            'labels' => $sensores->map(fn (Sensor $sensor) => $this->sensorLabel($sensor))->values(),
            'values' => $sensores->map(fn (Sensor $sensor) => $sensor->logs->filter(fn (LogTelemetria $log) => $log->created_at->gte($lastDay))->count())->values(),
        ];
    }

    private function scoreBySensor(Collection $predictions): array
    {
        $ordered = $predictions->sortByDesc('risk_score')->values();

        return [
            'labels' => $ordered->map(fn (array $prediction) => trim(($prediction['equipamento'] ? $prediction['equipamento'].' / ' : '').$prediction['sensor']))->values(),
            'values' => $ordered->pluck('risk_score')->values(),
            'statuses' => $ordered->pluck('status')->values(),
        ];
    }

    private function occurrencesByDay(Collection $occurrences): array
    {
        $grouped = $occurrences->groupBy(fn (array $occurrence) => $occurrence['started_at']->toDateString());
        $days = collect(range(6, 0))->map(fn (int $offset) => Carbon::today()->subDays($offset));

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('d/m'))->values(),
            'values' => $days->map(fn (Carbon $day) => $grouped->get($day->toDateString(), collect())->count())->values(),
        ];
    }

    private function outOfRangeBySensor(Collection $sensores, Collection $occurrences): array
    {
        return [
            'labels' => $sensores->map(fn (Sensor $sensor) => $this->sensorLabel($sensor))->values(),
            'values' => $sensores->map(fn (Sensor $sensor) => (int) $occurrences->where('sensor_id', $sensor->id)->sum('duration_minutes'))->values(),
        ];
    }

    private function projectionChart(?array $prediction): array
    {
        if (! $prediction || $prediction['predicted_4h'] === null) {
            return [
                'label' => 'Sem previsao disponivel',
                'labels' => [],
                'values' => [],
            ];
        }

        return [
            'label' => ($prediction['equipamento'] ?: 'Equipamento').' - '.$prediction['sensor'],
            'labels' => ['Atual', '+2h', '+4h'],
            'values' => [$prediction['current'], $prediction['predicted_2h'], $prediction['predicted_4h']],
        ];
    }

    private function temperatureRangeChart(Collection $sensores, Collection $predictions): array
    {
        $top = $predictions->sortByDesc('risk_score')->first();
        if (! $top) {
            return ['label' => 'Sem dados', 'labels' => [], 'values' => [], 'limit_min' => 2, 'limit_max' => 8];
        }

        $sensor = $sensores->firstWhere('id', $top['sensor_id']);
        if (! $sensor) {
            return ['label' => $top['sensor'], 'labels' => [], 'values' => [], 'limit_min' => $top['limit_min'], 'limit_max' => $top['limit_max']];
        }

        $series = $this->sensorSeries($sensor, $top);

        return [
            'label' => $series['label'],
            'labels' => $series['labels'],
            'values' => $series['values'],
            'projection' => $series['projection'],
            'limit_min' => $series['limit_min'],
            'limit_max' => $series['limit_max'],
        ];
    }

    private function sensorSeries(Sensor $sensor, ?array $prediction): array
    {
        $logs = $sensor->logs->sortBy('created_at')->take(-30)->values();
        $values = $logs->map(fn (LogTelemetria $log) => (float) $log->valor_leitura)->values();
        $labels = $logs->map(fn (LogTelemetria $log) => $log->created_at->format('H:i'))->values();
        $projectionValues = [];

        if ($prediction && $prediction['predicted_4h'] !== null && $values->isNotEmpty()) {
            $projectionValues = array_fill(0, max(0, $values->count() - 1), null);
            $projectionValues[] = $prediction['current'];
            $projectionValues[] = $prediction['predicted_2h'];
            $projectionValues[] = $prediction['predicted_4h'];
        }

        return [
            'label' => $this->sensorLabel($sensor),
            'labels' => $projectionValues ? $labels->concat(['+2h', '+4h'])->values() : $labels,
            'values' => $projectionValues ? $values->concat([null, null])->values() : $values,
            'projection' => $projectionValues,
            'limit_min' => $sensor->limite_min === null ? 2.0 : (float) $sensor->limite_min,
            'limit_max' => $sensor->limite_max === null ? 8.0 : (float) $sensor->limite_max,
        ];
    }

    private function resolvePeriod(array $filters): array
    {
        $requestedPeriod = (int) ($filters['periodo'] ?? 30);
        $period = in_array($requestedPeriod, [7, 30, 90], true)
            ? $requestedPeriod
            : 30;
        $start = Carbon::today()->subDays($period - 1)->startOfDay();
        $end = Carbon::today()->endOfDay();

        if (! empty($filters['inicio'])) {
            $start = Carbon::parse($filters['inicio'])->startOfDay();
        }

        if (! empty($filters['fim'])) {
            $end = Carbon::parse($filters['fim'])->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end, $period];
    }

    private function logsFor(Empresa $empresa): Builder
    {
        return LogTelemetria::query()
            ->whereHas('sensor.equipamento', fn (Builder $query) => $query->where('empresa_id', $empresa->id));
    }

    private function sensorLabel(Sensor $sensor): string
    {
        $equipamento = $sensor->equipamento?->nome;

        return trim(($equipamento ? $equipamento.' / ' : '').$sensor->tipo);
    }

    private function riskLevel(?string $risk): string
    {
        $value = Str::ascii(Str::lower((string) $risk));

        if (str_contains($value, 'perda')) {
            return 'perda_provavel';
        }

        if (str_contains($value, 'critic')) {
            return 'critico';
        }

        if (str_contains($value, 'alert') || str_contains($value, 'medio') || str_contains($value, 'aten')) {
            return 'alerta';
        }

        return 'normal';
    }
}
