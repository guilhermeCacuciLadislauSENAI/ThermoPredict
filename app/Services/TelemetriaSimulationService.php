<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Equipamento;
use App\Models\LogTelemetria;
use App\Models\Sensor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TelemetriaSimulationService
{
    private const CACHE_KEY_PREFIX = 'telemetria_simulacao_empresa_';
    private const INTERVAL_SECONDS = 5;
    private const MAX_BATCH_INTERVALS = 12;

    public function state(Empresa $empresa): array
    {
        $state = $this->companyState($empresa);

        return [
            'active' => (bool) ($state['active'] ?? false),
            'started_at' => $this->parseDate($state['started_at'] ?? null),
            'last_run_at' => $this->parseDate($state['last_run_at'] ?? null),
            'readings_created' => (int) ($state['readings_created'] ?? 0),
        ];
    }

    public function start(Empresa $empresa): int
    {
        $created = $this->createCurrentReadings($empresa);
        $now = now();

        $this->putCompanyState($empresa, [
            'active' => true,
            'started_at' => $now->toIso8601String(),
            'last_run_at' => $now->toIso8601String(),
            'readings_created' => $created,
        ]);

        return $created;
    }

    public function stop(Empresa $empresa): void
    {
        $state = $this->state($empresa);

        $this->putCompanyState($empresa, [
            'active' => false,
            'started_at' => $state['started_at']?->toIso8601String(),
            'last_run_at' => $state['last_run_at']?->toIso8601String(),
            'readings_created' => $state['readings_created'],
        ]);
    }

    public function tick(Empresa $empresa): int
    {
        $state = $this->state($empresa);
        if (! $state['active']) {
            return 0;
        }

        $elapsedSeconds = $state['last_run_at']
            ? max(0, $state['last_run_at']->diffInSeconds(now()))
            : self::INTERVAL_SECONDS;

        if ($elapsedSeconds < self::INTERVAL_SECONDS) {
            return 0;
        }

        $intervals = min(self::MAX_BATCH_INTERVALS, max(1, (int) floor($elapsedSeconds / self::INTERVAL_SECONDS)));
        $created = $this->createCurrentReadings($empresa, $intervals);

        $this->putCompanyState($empresa, [
            'active' => true,
            'started_at' => $state['started_at']?->toIso8601String() ?? now()->toIso8601String(),
            'last_run_at' => now()->toIso8601String(),
            'readings_created' => $state['readings_created'] + $created,
        ]);

        return $created;
    }

    private function createCurrentReadings(Empresa $empresa, int $intervals = 1): int
    {
        $created = 0;
        $sensores = $this->sensorsFor($empresa);
        $initialCounts = $sensores->mapWithKeys(fn (Sensor $sensor) => [
            $sensor->id => $sensor->logs()->where('created_at', '>=', now()->subHours(2))->count(),
        ]);

        for ($interval = 0; $interval < $intervals; $interval++) {
            $createdAt = now()->subSeconds(($intervals - $interval - 1) * self::INTERVAL_SECONDS);

            foreach ($sensores as $sensorIndex => $sensor) {
                $created += $this->createReading($sensor, ((int) $initialCounts->get($sensor->id, 0)) + $interval, 48, $createdAt, $sensorIndex);
            }
        }

        return $created;
    }

    private function sensorsFor(Empresa $empresa): Collection
    {
        $equipamentos = $empresa->equipamentos()->with('sensores')->get();
        $sensores = $equipamentos->flatMap(fn (Equipamento $equipamento) => $equipamento->sensores)->values();

        if ($sensores->isNotEmpty()) {
            return $sensores;
        }

        $cooler = Equipamento::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Cooler Inteligente Simulado',
            'localizacao' => 'Ambiente de Testes',
            'status' => 'Ativo',
        ]);

        return collect([
            Sensor::create([
                'equipamento_id' => $cooler->id,
                'tipo' => 'Temperatura Interna',
                'limite_min' => 2.00,
                'limite_max' => 8.00,
            ]),
        ]);
    }

    private function createReading(Sensor $sensor, int $step, int $totalSteps, Carbon $createdAt, int $sensorIndex): int
    {
        $telemetry = $this->telemetrySnapshot($sensor, $step, $totalSteps, $createdAt, $sensorIndex);

        LogTelemetria::create([
            'sensor_id' => $sensor->id,
            'valor_leitura' => $telemetry['internal_temperature'],
            'temperatura_externa' => $telemetry['external_temperature'],
            'umidade_externa' => $telemetry['external_humidity'],
            'tampa_aberta' => $telemetry['lid_open'],
            'nivel_risco' => $this->riskLevel($sensor, $telemetry['internal_temperature']),
            'created_at' => $createdAt,
        ]);

        return 1;
    }

    private function telemetrySnapshot(Sensor $sensor, int $step, int $totalSteps, Carbon $createdAt, int $sensorIndex): array
    {
        $externalTemperature = $this->externalTemperature($createdAt, $step, $sensorIndex);
        $lidOpen = $this->lidOpen($step, $sensorIndex);
        $externalHumidity = $this->externalHumidity($step, $sensorIndex, $lidOpen);
        $internalTemperature = $this->internalTemperature(
            $sensor,
            $step,
            $totalSteps,
            $sensorIndex,
            $externalTemperature,
            $externalHumidity,
            $lidOpen
        );

        return [
            'internal_temperature' => $internalTemperature,
            'external_temperature' => $externalTemperature,
            'external_humidity' => $externalHumidity,
            'lid_open' => $lidOpen,
        ];
    }

    private function internalTemperature(
        Sensor $sensor,
        int $step,
        int $totalSteps,
        int $sensorIndex,
        float $externalTemperature,
        float $externalHumidity,
        bool $lidOpen
    ): float
    {
        $minimum = $sensor->limite_min === null ? 2.0 : (float) $sensor->limite_min;
        $maximum = $sensor->limite_max === null ? 8.0 : (float) $sensor->limite_max;
        $range = max(1.0, $maximum - $minimum);
        $middle = ($minimum + $maximum) / 2;
        $cycleStep = $step % max(1, $totalSteps);
        $phase = ($step + ($sensorIndex * 3)) / 3;
        $noise = mt_rand(-12, 12) / 100;
        $value = $middle + (sin($phase) * $range * 0.18) + $noise;

        $warmupStart = (int) floor($totalSteps * 0.42);
        $warmupEnd = (int) floor($totalSteps * 0.62);
        $recoveryStart = (int) floor($totalSteps * 0.62);
        $recoveryEnd = (int) floor($totalSteps * 0.78);
        $finalStart = (int) floor($totalSteps * 0.86);

        if ($cycleStep >= $warmupStart && $cycleStep <= $warmupEnd) {
            $progress = ($cycleStep - $warmupStart) / max(1, $warmupEnd - $warmupStart);
            $value = $middle + ($range * 0.18) + ($progress * $range * 0.72) + $noise;
        }

        if ($cycleStep > $recoveryStart && $cycleStep <= $recoveryEnd) {
            $progress = ($cycleStep - $recoveryStart) / max(1, $recoveryEnd - $recoveryStart);
            $value = ($maximum + ($range * 0.45)) - ($progress * $range * 0.75) + $noise;
        }

        if ($cycleStep >= $finalStart) {
            $progress = ($cycleStep - $finalStart) / max(1, $totalSteps - $finalStart);
            $value = $middle + ($progress * $range * 0.68) + $noise;
        }

        if ($lidOpen) {
            $value += 0.35 + (max(0, $externalTemperature - 22) * 0.035);
        }

        if ($externalTemperature >= 30) {
            $value += min(0.8, ($externalTemperature - 30) * 0.08);
        }

        if ($externalHumidity >= 75 && $lidOpen) {
            $value += min(0.45, ($externalHumidity - 75) * 0.025);
        }

        return round($value, 2);
    }

    private function externalTemperature(Carbon $createdAt, int $step, int $sensorIndex): float
    {
        $hourFactor = sin((($createdAt->hour + ($createdAt->minute / 60)) - 6) / 24 * 2 * pi());
        $cycle = sin(($step + ($sensorIndex * 4)) / 7);
        $noise = mt_rand(-25, 25) / 100;

        return round(25.5 + ($hourFactor * 4.2) + ($cycle * 2.3) + $noise, 2);
    }

    private function externalHumidity(int $step, int $sensorIndex, bool $lidOpen): float
    {
        $cycle = sin(($step + ($sensorIndex * 2)) / 5);
        $noise = mt_rand(-35, 35) / 10;
        $humidity = 61 + ($cycle * 9) + $noise + ($lidOpen ? 4 : 0);

        return round(max(35, min(92, $humidity)), 2);
    }

    private function lidOpen(int $step, int $sensorIndex): bool
    {
        $cycle = ($step + ($sensorIndex * 5)) % 36;

        return ($cycle >= 8 && $cycle <= 10) || ($cycle >= 24 && $cycle <= 28);
    }

    private function riskLevel(Sensor $sensor, float $value): string
    {
        $minimum = $sensor->limite_min === null ? 2.0 : (float) $sensor->limite_min;
        $maximum = $sensor->limite_max === null ? 8.0 : (float) $sensor->limite_max;
        $criticalMargin = max(1.0, ($maximum - $minimum) * 0.2);

        if ($value < ($minimum - $criticalMargin) || $value > ($maximum + $criticalMargin)) {
            return 'Critico';
        }

        if ($value < $minimum || $value > $maximum) {
            return 'Alerta';
        }

        return 'Normal';
    }

    private function companyState(Empresa $empresa): array
    {
        return Cache::get($this->cacheKey($empresa), []);
    }

    private function putCompanyState(Empresa $empresa, array $state): void
    {
        Cache::forever($this->cacheKey($empresa), $state);
    }

    private function cacheKey(Empresa $empresa): string
    {
        return self::CACHE_KEY_PREFIX.$empresa->id;
    }

    private function parseDate(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
