<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Equipamento;
use App\Models\LogTelemetria;
use App\Models\Sensor;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::updateOrCreate(
            ['cnpj' => '12.345.678/0001-99'],
            ['razao_social' => 'Farmacia Sao Joao (Demo)']
        );

        Usuario::updateOrCreate(
            ['email' => 'cliente@thermo.test'],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Cliente Demo',
                'password' => Hash::make('password'),
                'status' => 'ativo',
                'perfil' => 'cliente',
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'admin@thermo.test'],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Admin Demo',
                'password' => Hash::make('password'),
                'status' => 'ativo',
                'perfil' => 'admin',
            ]
        );

        $geladeira = Equipamento::updateOrCreate(
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Freezer de Vacinas Alpha',
            ],
            [
                'localizacao' => 'Laboratorio Central',
                'status' => 'Ativo',
            ]
        );

        $sensor = Sensor::updateOrCreate(
            [
                'equipamento_id' => $geladeira->id,
                'tipo' => 'Temperatura Interna',
            ],
            [
                'limite_min' => 2.00,
                'limite_max' => 8.00,
            ]
        );

        $sensor->logs()->delete();

        // Dados simulados apenas para demonstracao local. A predicao usa estes registros
        // do mesmo modo que usara leituras reais recebidas futuramente.
        $horaAtual = Carbon::now()->subHours(10);
        $temperaturasDemo = [4.5, 4.8, 5.1, 5.5, 6.2, 7.8, 8.5, 6.0, 5.0, 4.2];

        foreach ($temperaturasDemo as $temp) {
            LogTelemetria::create([
                'sensor_id' => $sensor->id,
                'valor_leitura' => $temp,
                'nivel_risco' => ($temp < 2.00 || $temp > 8.00) ? 'Critico' : 'Normal',
                'created_at' => $horaAtual->addHour(),
            ]);
        }
    }
}
