<?php

namespace Tests\Feature;

use App\Models\Sensor;
use App\Models\LogTelemetria;
use App\Models\Usuario;
use App\Services\TelemetriaPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_reports_render_real_monitoring_data(): void
    {
        $this->seed();
        $user = Usuario::where('email', 'cliente@thermo.test')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('thermoDashboardCharts', false)
            ->assertSee('prediction-item', false);

        $this->actingAs($user)
            ->get('/relatorios')
            ->assertOk()
            ->assertSee('thermoReportCharts', false)
            ->assertSee('Hist&oacute;rico Operacional', false);

        $this->actingAs($user)
            ->get('/predicao')
            ->assertOk()
            ->assertSee('thermoPredictionCharts', false)
            ->assertSee('Intelig&ecirc;ncia T&eacute;rmica', false);

        $this->actingAs($user)
            ->get('/predicao?status=risco')
            ->assertOk()
            ->assertSee('thermoPredictionCharts', false);
    }

    public function test_monitoring_exports_pdf_and_csv_with_same_scope(): void
    {
        $this->seed();
        $user = Usuario::where('email', 'cliente@thermo.test')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard/exportar/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-1.4', false);

        $this->actingAs($user)
            ->get('/dashboard/exportar/csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Resumo', false);

        $this->actingAs($user)
            ->get('/relatorios/pdf?status=critico')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-1.4', false);

        $this->actingAs($user)
            ->get('/relatorios/csv?status=critico')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Historico de Ocorrencias Termicas', false);

        $this->actingAs($user)
            ->get('/predicao/pdf?status=risco')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertSee('%PDF-1.4', false);

        $this->actingAs($user)
            ->get('/predicao/csv?status=risco')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('Analise Preditiva', false)
            ->assertSee('Resumo', false);
    }

    public function test_dashboard_simulation_can_be_toggled_and_generates_readings(): void
    {
        $this->seed();
        $user = Usuario::where('email', 'cliente@thermo.test')->firstOrFail();
        $initialCount = LogTelemetria::count();

        $this->actingAs($user)
            ->post(route('dashboard.simulation'), ['active' => 1])
            ->assertRedirect(route('dashboard'));

        $this->assertGreaterThan($initialCount, LogTelemetria::count());
        $latest = LogTelemetria::latest('created_at')->firstOrFail();
        $this->assertNotNull($latest->temperatura_externa);
        $this->assertNotNull($latest->umidade_externa);
        $this->assertIsBool($latest->tampa_aberta);
        $countAfterStart = LogTelemetria::count();

        $this->travel(6)->seconds();

        $this->actingAs($user)
            ->post(route('dashboard.simulation.tick'))
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->assertGreaterThan($countAfterStart, LogTelemetria::count());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Desativar simula&ccedil;&atilde;o', false);

        $this->actingAs($user)
            ->post(route('dashboard.simulation'), ['active' => 0])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ativar simula&ccedil;&atilde;o', false);
    }

    public function test_prediction_service_projects_sensor_trend_from_history(): void
    {
        $this->seed();
        $sensor = Sensor::with(['equipamento', 'logs'])->firstOrFail();

        $prediction = app(TelemetriaPredictionService::class)->analyze($sensor, $sensor->logs);

        $this->assertNotNull($prediction['predicted_2h']);
        $this->assertNotNull($prediction['predicted_4h']);
        $this->assertGreaterThanOrEqual(1, $prediction['confidence']);
        $this->assertGreaterThanOrEqual(0, $prediction['risk_score']);
        $this->assertLessThanOrEqual(100, $prediction['risk_score']);
        $this->assertContains($prediction['status'], ['normal', 'critico', 'risco', 'atencao', 'perda_provavel']);
    }
}
