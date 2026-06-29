<?php

namespace App\Http\Controllers;

use App\Services\ReportCsvService;
use App\Services\ReportPayloadService;
use App\Services\ReportPdfService;
use App\Services\TelemetriaAnalyticsService;
use App\Services\TelemetriaSimulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(TelemetriaAnalyticsService $analytics, TelemetriaSimulationService $simulation)
    {
        $empresa = Auth::user()->empresa;
        $simulation->tick($empresa);

        return view('dashboard', array_merge($analytics->dashboard($empresa), [
            'simulation' => $simulation->state($empresa),
        ]));
    }

    public function simulation(Request $request, TelemetriaSimulationService $simulation)
    {
        $empresa = Auth::user()->empresa;

        if ($request->boolean('active')) {
            $created = $simulation->start($empresa);

            return redirect()
                ->route('dashboard')
                ->with('sucesso', "Simulacao ativada. Coleta continua iniciada com {$created} leitura(s) atual(is).");
        }

        $simulation->stop($empresa);

        return redirect()
            ->route('dashboard')
            ->with('sucesso', 'Simulacao desativada. As leituras ja geradas permanecem no historico.');
    }

    public function simulationTick(TelemetriaSimulationService $simulation)
    {
        $empresa = Auth::user()->empresa;
        $created = $simulation->tick($empresa);
        $state = $simulation->state($empresa);

        return response()->json([
            'active' => $state['active'],
            'created' => $created,
            'readings_created' => $state['readings_created'],
            'last_run_at' => $state['last_run_at']?->toIso8601String(),
        ]);
    }

    public function pdf(TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportPdfService $pdf)
    {
        $empresa = Auth::user()->empresa;
        $payload = $payloads->dashboard($empresa, $analytics->dashboard($empresa));

        return response($pdf->render($payload), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="relatorio-dashboard-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function csv(TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportCsvService $csv)
    {
        $empresa = Auth::user()->empresa;
        $payload = $payloads->dashboard($empresa, $analytics->dashboard($empresa));

        return response($csv->render($payloads->csvSections($payload)), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="relatorio-dashboard-'.now()->format('Y-m-d').'.csv"',
        ]);
    }
}
