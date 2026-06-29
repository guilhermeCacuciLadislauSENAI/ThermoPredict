<?php

namespace App\Services;

use App\Models\LogTelemetria;
use App\Models\Sensor;
use Illuminate\Support\Collection;

class TelemetriaPredictionService
{
    private const DEFAULT_MINIMUM = 2.0;

    private const DEFAULT_MAXIMUM = 8.0;

    private const MINIMUM_SAMPLES = 6;

    private const HISTORY_HOURS = 48;

    private const MAX_SAMPLES = 96;

    private const STALE_AFTER_MINUTES = 360;

    private const EXCURSION_MINIMUM_READINGS = 2;

    public function analyze(Sensor $sensor, ?Collection $logs = null): array
    {
        $logs = $this->recentLogs($sensor, $logs);
        $minimum = $sensor->limite_min === null ? self::DEFAULT_MINIMUM : (float) $sensor->limite_min;
        $maximum = $sensor->limite_max === null ? self::DEFAULT_MAXIMUM : (float) $sensor->limite_max;

        $base = [
            'sensor_id' => $sensor->id,
            'sensor' => $sensor->tipo,
            'equipamento' => $sensor->equipamento?->nome,
            'sample_count' => $logs->count(),
            'minimum_samples' => self::MINIMUM_SAMPLES,
            'limit_min' => $minimum,
            'limit_max' => $maximum,
            'uses_default_range' => $sensor->limite_min === null || $sensor->limite_max === null,
            'current' => null,
            'external_temperature_current' => null,
            'external_humidity_current' => null,
            'lid_open_current' => false,
            'recent_lid_openings' => 0,
            'external_temperature_avg' => null,
            'external_humidity_avg' => null,
            'context_risk' => 0,
            'predicted_2h' => null,
            'predicted_4h' => null,
            'trend_per_hour' => null,
            'volatility' => null,
            'confidence' => 0,
            'hours_to_limit' => null,
            'latest_at' => null,
            'consecutive_out_of_range' => 0,
            'out_of_range_minutes' => 0,
            'recent_alerts' => 0,
            'chance_excursion' => 0,
            'risk_score' => 0,
            'risk_band' => 'seguro',
            'status' => 'insuficiente',
            'severity' => 0,
            'label' => 'Dados insuficientes',
            'message' => 'Ainda nao ha historico suficiente para calcular uma tendencia confiavel.',
            'recommendation' => 'Mantenha o sensor coletando novas leituras.',
            'source' => 'historico_real',
        ];

        if ($logs->isEmpty()) {
            return array_merge($base, [
                'status' => 'sem_comunicacao',
                'severity' => 3,
                'risk_score' => 45,
                'risk_band' => 'atencao',
                'label' => 'Sem comunicacao',
                'message' => 'Nenhuma leitura de telemetria foi recebida para este sensor.',
                'recommendation' => 'Verifique energia, bateria, sinal e pareamento do dispositivo.',
            ]);
        }

        $latest = $logs->last();
        $current = (float) $latest->valor_leitura;
        $base['current'] = round($current, 1);
        $base['latest_at'] = $latest->created_at;
        $context = $this->contextIndicators($logs);
        $base = array_merge($base, $context);
        $base['recent_alerts'] = $logs
            ->filter(fn (LogTelemetria $log) => $log->created_at->gte(now()->subDay()))
            ->filter(fn (LogTelemetria $log) => $this->isOutOfRange((float) $log->valor_leitura, $minimum, $maximum))
            ->count();

        $staleMinutes = max(0, $latest->created_at->diffInMinutes(now()));
        $consecutive = $this->consecutiveOutOfRange($logs, $minimum, $maximum);
        $base['consecutive_out_of_range'] = $consecutive['count'];
        $base['out_of_range_minutes'] = $consecutive['minutes'];

        if ($logs->count() < self::MINIMUM_SAMPLES) {
            $score = $this->riskScore(
                current: $current,
                slope: 0,
                minimum: $minimum,
                maximum: $maximum,
                consecutiveOut: $consecutive['count'],
                outMinutes: $consecutive['minutes'],
                recentAlerts: $base['recent_alerts'],
                staleMinutes: $staleMinutes,
                hoursToLimit: null,
                volatility: 0,
                contextRisk: $base['context_risk']
            );

            return array_merge($base, [
                'risk_score' => $score,
                'risk_band' => $this->riskBand($score),
                'chance_excursion' => $this->chanceExcursion($score),
                'message' => "Historico com {$logs->count()} de ".self::MINIMUM_SAMPLES.' leituras minimas.',
            ]);
        }

        // Logica real: regressao linear simples sobre horarios e valores historicos do sensor.
        $regression = $this->regression($logs);
        $slope = $regression['slope'];
        $predicted2h = $regression['intercept'] + ($slope * ($regression['last_x'] + 2));
        $predicted4h = $regression['intercept'] + ($slope * ($regression['last_x'] + 4));
        $volatility = $regression['volatility'];
        $confidence = $regression['confidence'];
        $hoursToLimit = $this->hoursToLimit($current, $slope, $minimum, $maximum);
        $score = $this->riskScore(
            current: $current,
            slope: $slope,
            minimum: $minimum,
            maximum: $maximum,
            consecutiveOut: $consecutive['count'],
            outMinutes: $consecutive['minutes'],
            recentAlerts: $base['recent_alerts'],
            staleMinutes: $staleMinutes,
            hoursToLimit: $hoursToLimit,
            volatility: $volatility,
            contextRisk: $base['context_risk']
        );

        $result = array_merge($base, [
            'predicted_2h' => round($predicted2h, 1),
            'predicted_4h' => round($predicted4h, 1),
            'trend_per_hour' => round($slope, 2),
            'volatility' => round($volatility, 2),
            'confidence' => max(1, min(99, $confidence)),
            'hours_to_limit' => $hoursToLimit === null ? null : round($hoursToLimit, 1),
            'risk_score' => $score,
            'risk_band' => $this->riskBand($score),
            'chance_excursion' => $this->chanceExcursion($score),
        ]);

        if ($latest->created_at->lt(now()->subMinutes(self::STALE_AFTER_MINUTES))) {
            return $this->withRiskMessage($result, 'desatualizado', 3, 'Dados desatualizados');
        }

        if (
            $consecutive['minutes'] >= 240
            || ($consecutive['count'] >= 3 && ($current <= 0 || $current >= 12))
            || $score >= 92
        ) {
            return $this->withRiskMessage($result, 'perda_provavel', 6, 'Perda provavel');
        }

        if ($consecutive['count'] >= 3 || $consecutive['minutes'] >= 60 || $score >= 81) {
            return $this->withRiskMessage($result, 'critico', 5, 'Critico');
        }

        if (
            $consecutive['count'] >= self::EXCURSION_MINIMUM_READINGS
            || $predicted2h < $minimum
            || $predicted2h > $maximum
            || ($hoursToLimit !== null && $hoursToLimit <= 2)
            || $score >= 61
        ) {
            return $this->withRiskMessage($result, 'risco', 4, 'Risco');
        }

        $distanceToLimit = min($current - $minimum, $maximum - $current);
        if (
            $distanceToLimit <= .5
            || abs($slope) >= .35
            || $volatility >= 1
            || ($hoursToLimit !== null && $hoursToLimit <= 8)
            || $score >= 31
        ) {
            return $this->withRiskMessage($result, 'atencao', 3, 'Atencao');
        }

        return $this->withRiskMessage($result, 'normal', 1, 'Normal');
    }

    public function summarize(Collection $predictions): array
    {
        $highest = $predictions->sortByDesc('risk_score')->first();

        return [
            'total' => $predictions->count(),
            'available' => $predictions->whereNotIn('status', ['insuficiente', 'sem_comunicacao'])->count(),
            'normal' => $predictions->where('status', 'normal')->count(),
            'atencao' => $predictions->where('status', 'atencao')->count(),
            'risco' => $predictions->where('status', 'risco')->count(),
            'critical' => $predictions->where('status', 'critico')->count(),
            'loss_likely' => $predictions->where('status', 'perda_provavel')->count(),
            'at_risk' => $predictions->whereIn('status', ['atencao', 'risco', 'critico', 'perda_provavel'])->count(),
            'stable' => $predictions->where('status', 'normal')->count(),
            'insufficient' => $predictions->where('status', 'insuficiente')->count(),
            'stale' => $predictions->where('status', 'desatualizado')->count(),
            'without_communication' => $predictions->where('status', 'sem_comunicacao')->count(),
            'highest' => $highest,
            'score_average' => $predictions->isEmpty() ? 0 : (int) round($predictions->avg('risk_score')),
        ];
    }

    public function riskBand(int $score): string
    {
        if ($score >= 81) {
            return 'critico';
        }

        if ($score >= 61) {
            return 'risco';
        }

        if ($score >= 31) {
            return 'atencao';
        }

        return 'seguro';
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
            default => 'Dados insuficientes',
        };
    }

    private function withRiskMessage(array $prediction, string $status, int $severity, string $label): array
    {
        $current = (float) $prediction['current'];
        $minimum = (float) $prediction['limit_min'];
        $maximum = (float) $prediction['limit_max'];
        $trend = (float) ($prediction['trend_per_hour'] ?? 0);
        $direction = $trend > .02 ? 'subindo' : ($trend < -.02 ? 'caindo' : 'estavel');
        $projected = $prediction['predicted_4h'];
        $hoursToLimit = $prediction['hours_to_limit'];
        $range = $this->formatNumber($minimum).' a '.$this->formatNumber($maximum);
        $limitText = $hoursToLimit === null ? '' : ' Limite estimado em '.$hoursToLimit.'h.';
        $contextText = $this->contextMessage($prediction);

        $message = match ($status) {
            'normal' => 'Temperatura em '.$this->formatNumber($current).' dentro da faixa segura '.$range.'. Tendencia '.$direction.'.',
            'atencao' => 'Temperatura em '.$this->formatNumber($current).' com tendencia '.$direction.'. A faixa segura e '.$range.'.'.$limitText,
            'risco' => 'Risco de excursao termica: leitura atual '.$this->formatNumber($current).', projecao de '.$this->formatNumber((float) $projected).' em 4h.'.$limitText,
            'critico' => 'Excursao termica critica detectada ou altamente provavel. Leitura atual '.$this->formatNumber($current).' para faixa '.$range.'.',
            'perda_provavel' => 'Exposicao grave identificada. Existe possibilidade de perda de vacinas; quarentena e avaliacao tecnica sao recomendadas.',
            'desatualizado' => 'Sensor sem comunicacao recente. A ultima leitura valida pode nao representar a temperatura atual.',
            default => $prediction['message'],
        };

        if ($contextText !== '') {
            $message .= ' '.$contextText;
        }

        return array_merge($prediction, [
            'status' => $status,
            'severity' => $severity,
            'label' => $label,
            'message' => $message,
            'recommendation' => $this->recommendation($status, $trend, $current, $minimum, $maximum, $prediction),
        ]);
    }

    private function recommendation(string $status, float $trend, float $current, float $minimum, float $maximum, array $prediction): string
    {
        if (($prediction['lid_open_current'] ?? false) === true) {
            return 'Feche a tampa do cooler, confirme a vedacao e acompanhe a queda da temperatura nas proximas leituras.';
        }

        if (($prediction['recent_lid_openings'] ?? 0) >= 3) {
            return 'Reduza aberturas sucessivas da tampa e confirme se o manuseio das vacinas esta seguindo o procedimento.';
        }

        if ($status === 'perda_provavel') {
            return 'Coloque o lote em quarentena, acione o responsavel tecnico e gere relatorio da ocorrencia antes de liberar uso.';
        }

        if ($status === 'critico') {
            return 'Transfira as vacinas para outro equipamento se possivel, confira energia, tampa, gelo reciclavel e registre acao corretiva.';
        }

        if ($status === 'risco') {
            return $trend > 0
                ? 'Verifique se a tampa esta aberta, exposicao ao sol, energia e necessidade de reforcar elementos refrigerantes.'
                : 'Verifique risco de congelamento, posicionamento do sensor e excesso de refrigeracao.';
        }

        if ($status === 'atencao') {
            return $current > ($maximum - .6)
                ? 'Monitore as proximas leituras e confirme vedacao, transporte e fonte de energia.'
                : 'Monitore as proximas leituras e confirme se nao ha risco de congelamento.';
        }

        if ($status === 'desatualizado' || $status === 'sem_comunicacao') {
            return 'Verifique bateria, sinal, alimentacao e envio de dados do dispositivo.';
        }

        if (($prediction['uses_default_range'] ?? false) === true) {
            return 'Operacao normal. Configure limites especificos do sensor se o ambiente exigir faixa diferente de 2 C a 8 C.';
        }

        return 'Operacao normal. Mantenha monitoramento automatico ativo.';
    }

    private function contextMessage(array $prediction): string
    {
        $parts = [];

        if (($prediction['lid_open_current'] ?? false) === true) {
            $parts[] = 'Tampa aberta na ultima leitura.';
        } elseif (($prediction['recent_lid_openings'] ?? 0) > 0) {
            $parts[] = 'Foram detectadas '.$prediction['recent_lid_openings'].' aberturas recentes da tampa.';
        }

        if (($prediction['external_temperature_current'] ?? null) !== null && $prediction['external_temperature_current'] >= 30) {
            $parts[] = 'Ambiente externo quente em '.$this->formatNumber((float) $prediction['external_temperature_current']).'.';
        }

        if (($prediction['external_humidity_current'] ?? null) !== null && $prediction['external_humidity_current'] >= 75) {
            $parts[] = 'Umidade externa elevada em '.number_format((float) $prediction['external_humidity_current'], 1, ',', '').'%.';
        }

        return implode(' ', $parts);
    }

    private function recentLogs(Sensor $sensor, ?Collection $logs): Collection
    {
        $logs ??= $sensor->logs()
            ->where('created_at', '>=', now()->subHours(self::HISTORY_HOURS))
            ->latest('created_at')
            ->limit(self::MAX_SAMPLES)
            ->get();

        return $logs
            ->filter(fn (LogTelemetria $log) => $log->created_at !== null)
            ->sortBy('created_at')
            ->take(-self::MAX_SAMPLES)
            ->values();
    }

    private function regression(Collection $logs): array
    {
        $firstAt = $logs->first()->created_at;
        $points = $logs->map(fn (LogTelemetria $log) => [
            'x' => $firstAt->diffInSeconds($log->created_at) / 3600,
            'y' => (float) $log->valor_leitura,
        ]);

        $n = $points->count();
        $meanX = $points->avg('x');
        $meanY = $points->avg('y');
        $sumXY = $points->sum(fn (array $point) => ($point['x'] - $meanX) * ($point['y'] - $meanY));
        $sumXX = $points->sum(fn (array $point) => ($point['x'] - $meanX) ** 2);
        $slope = $sumXX > 0 ? $sumXY / $sumXX : 0.0;
        $intercept = $meanY - ($slope * $meanX);
        $lastX = $points->last()['x'];

        $totalVariation = $points->sum(fn (array $point) => ($point['y'] - $meanY) ** 2);
        $residualVariation = $points->sum(function (array $point) use ($intercept, $slope) {
            $estimated = $intercept + ($slope * $point['x']);

            return ($point['y'] - $estimated) ** 2;
        });
        $rSquared = $totalVariation > 0 ? max(0, min(1, 1 - ($residualVariation / $totalVariation))) : 1;
        $volatility = sqrt($points->sum(fn (array $point) => ($point['y'] - $meanY) ** 2) / $n);
        $sampleScore = min(1, $n / 24);
        $volatilityScore = max(.45, 1 - min($volatility / 4, .55));
        $confidence = (int) round(100 * $sampleScore * (.65 + (.35 * $rSquared)) * $volatilityScore);

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'last_x' => $lastX,
            'volatility' => $volatility,
            'confidence' => $confidence,
        ];
    }

    private function consecutiveOutOfRange(Collection $logs, float $minimum, float $maximum): array
    {
        $count = 0;
        $startAt = null;
        $latestAt = null;

        foreach ($logs->reverse() as $log) {
            $value = (float) $log->valor_leitura;
            if (! $this->isOutOfRange($value, $minimum, $maximum)) {
                break;
            }

            $count++;
            $startAt = $log->created_at;
            $latestAt ??= $log->created_at;
        }

        return [
            'count' => $count,
            'minutes' => $count > 1 && $startAt && $latestAt ? max(1, $startAt->diffInMinutes($latestAt)) : 0,
        ];
    }

    private function contextIndicators(Collection $logs): array
    {
        $latest = $logs->last();
        $recent = $logs->filter(fn (LogTelemetria $log) => $log->created_at->gte(now()->subMinutes(30)));
        $externalValues = $logs
            ->pluck('temperatura_externa')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);
        $humidityValues = $logs
            ->pluck('umidade_externa')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);
        $recentLidOpenings = $recent->filter(fn (LogTelemetria $log) => (bool) $log->tampa_aberta)->count();
        $externalCurrent = $latest?->temperatura_externa;
        $humidityCurrent = $latest?->umidade_externa;
        $contextRisk = 0;

        if ((bool) ($latest?->tampa_aberta ?? false)) {
            $contextRisk += 14;
        }

        if ($recentLidOpenings > 0) {
            $contextRisk += min(16, $recentLidOpenings * 3);
        }

        if ($externalCurrent !== null && (float) $externalCurrent >= 30) {
            $contextRisk += min(10, ((float) $externalCurrent - 30) * 2);
        }

        if ($humidityCurrent !== null && (float) $humidityCurrent >= 75) {
            $contextRisk += min(8, ((float) $humidityCurrent - 75) * .7);
        }

        return [
            'external_temperature_current' => $externalCurrent === null ? null : round((float) $externalCurrent, 1),
            'external_humidity_current' => $humidityCurrent === null ? null : round((float) $humidityCurrent, 1),
            'lid_open_current' => (bool) ($latest?->tampa_aberta ?? false),
            'recent_lid_openings' => $recentLidOpenings,
            'external_temperature_avg' => $externalValues->isEmpty() ? null : round((float) $externalValues->avg(), 1),
            'external_humidity_avg' => $humidityValues->isEmpty() ? null : round((float) $humidityValues->avg(), 1),
            'context_risk' => (int) round($contextRisk),
        ];
    }

    private function riskScore(
        float $current,
        float $slope,
        float $minimum,
        float $maximum,
        int $consecutiveOut,
        int $outMinutes,
        int $recentAlerts,
        int $staleMinutes,
        ?float $hoursToLimit,
        float $volatility,
        int $contextRisk
    ): int {
        $score = 0;
        $range = max(.1, $maximum - $minimum);

        if ($current < $minimum) {
            $score += min(35, 18 + (($minimum - $current) / $range) * 30);
        } elseif ($current > $maximum) {
            $score += min(35, 18 + (($current - $maximum) / $range) * 30);
        } else {
            $distance = min($current - $minimum, $maximum - $current);
            if ($distance <= .5) {
                $score += 22;
            } elseif ($distance <= 1) {
                $score += 12;
            }
        }

        $score += min(20, abs($slope) * 18);
        $score += min(20, $consecutiveOut * 8);
        $score += min(20, $outMinutes / 6);
        $score += min(10, $recentAlerts * 2);
        $score += min(10, $volatility * 5);
        $score += min(26, $contextRisk);

        if ($hoursToLimit !== null) {
            if ($hoursToLimit <= 1) {
                $score += 20;
            } elseif ($hoursToLimit <= 2) {
                $score += 15;
            } elseif ($hoursToLimit <= 4) {
                $score += 10;
            } elseif ($hoursToLimit <= 8) {
                $score += 6;
            }
        }

        if ($staleMinutes >= self::STALE_AFTER_MINUTES) {
            $score += 20;
        } elseif ($staleMinutes >= 120) {
            $score += 8;
        }

        return (int) max(0, min(100, round($score)));
    }

    private function chanceExcursion(int $score): int
    {
        return (int) max(0, min(95, round($score * .9)));
    }

    private function hoursToLimit(float $current, float $slope, float $minimum, float $maximum): ?float
    {
        if ($slope > .02) {
            $hours = ($maximum - $current) / $slope;

            return $hours >= 0 ? $hours : 0;
        }

        if ($slope < -.02) {
            $hours = ($minimum - $current) / $slope;

            return $hours >= 0 ? $hours : 0;
        }

        return null;
    }

    private function isOutOfRange(float $value, float $minimum, float $maximum): bool
    {
        return $value < $minimum || $value > $maximum;
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 1, ',', '').' C';
    }
}
