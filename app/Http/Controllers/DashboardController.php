<?php

namespace App\Http\Controllers;

use App\Services\ReportCsvService;
use App\Services\ReportPayloadService;
use App\Services\ReportPdfService;
use App\Services\TelemetriaAnalyticsService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(TelemetriaAnalyticsService $analytics)
    {
        $empresa = Auth::user()->empresa;

        return view('dashboard', $analytics->dashboard($empresa));
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
