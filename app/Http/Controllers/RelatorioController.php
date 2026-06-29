<?php

namespace App\Http\Controllers;

use App\Services\ReportCsvService;
use App\Services\ReportPayloadService;
use App\Services\ReportPdfService;
use App\Services\TelemetriaAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RelatorioController extends Controller
{
    public function index(Request $request, TelemetriaAnalyticsService $analytics)
    {
        $empresa = Auth::user()->empresa;
        $report = $analytics->report($empresa, $request->all());

        return view('relatorios', compact('empresa', 'report'));
    }

    public function pdf(Request $request, TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportPdfService $pdf)
    {
        $empresa = Auth::user()->empresa;
        $report = $analytics->report($empresa, $request->all());
        $payload = $payloads->operationalReport($empresa, $report);

        return response($pdf->render($payload), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="relatorio-temperatura-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    public function csv(Request $request, TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportCsvService $csv)
    {
        $empresa = Auth::user()->empresa;
        $report = $analytics->report($empresa, $request->all());
        $payload = $payloads->operationalReport($empresa, $report);

        return response($csv->render($payloads->csvSections($payload)), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="relatorio-temperatura-'.now()->format('Y-m-d').'.csv"',
        ]);
    }
}
