<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Equipamento;
use App\Models\Sensor;
use App\Models\LogTelemetria;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria uma Empresa Cliente
        $empresa = Empresa::create([
            'cnpj' => '12.345.678/0001-99',
            'razao_social' => 'Farmácia São João (Teste)',
        ]);

        // 2. Cria uma Geladeira para essa Empresa
        $geladeira = Equipamento::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Freezer de Vacinas Alpha',
            'localizacao' => 'Laboratório Central',
            'status' => 'Ativo',
        ]);

        // 3. Adiciona um Sensor de Temperatura na Geladeira
        $sensor = Sensor::create([
            'equipamento_id' => $geladeira->id,
            'tipo' => 'Temperatura Interna',
            'limite_min' => 2.00,
            'limite_max' => 8.00,
        ]);

        // 4. Gera 10 leituras de temperatura (simulando as últimas horas)
        $horaAtual = Carbon::now()->subHours(10);

        $temperaturasFicticias = [4.5, 4.8, 5.1, 5.5, 6.2, 7.8, 8.5, 6.0, 5.0, 4.2];

        foreach ($temperaturasFicticias as $temp) {
            
            // Lógica simples de risco baseada nos limites
            $risco = ($temp < 2.00 || $temp > 8.00) ? 'Crítico' : 'Normal';

            LogTelemetria::create([
                'sensor_id' => $sensor->id,
                'valor_leitura' => $temp,
                'nivel_risco' => $risco,
                'created_at' => $horaAtual->addHour(), // Avança 1 hora a cada registro
            ]);
        }
    }
}