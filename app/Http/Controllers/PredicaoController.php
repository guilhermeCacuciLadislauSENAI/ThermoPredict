<?php

namespace App\Http\Controllers;

use App\Services\ReportCsvService;
use App\Services\ReportPayloadService;
use App\Services\ReportPdfService;
use App\Services\TelemetriaAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredicaoController extends Controller
{
    public function index(Request $request, TelemetriaAnalyticsService $analytics)
    {
        $empresa = Auth::user()->empresa;
        $prediction = $analytics->prediction($empresa, $request->all());

        return view('predicao.index', compact('empresa', 'prediction'));
    }

    public function pdf(Request $request, TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportPdfService $pdf)
    {
        $empresa = Auth::user()->empresa;
        $prediction = $analytics->prediction($empresa, $request->all());
        $payload = $payloads->prediction($empresa, $prediction);

        return response($pdf->render($payload), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('relatorio-predicao', 'pdf').'"',
        ]);
    }

    public function csv(Request $request, TelemetriaAnalyticsService $analytics, ReportPayloadService $payloads, ReportCsvService $csv)
    {
        $empresa = Auth::user()->empresa;
        $prediction = $analytics->prediction($empresa, $request->all());
        $payload = $payloads->prediction($empresa, $prediction);

        return response($csv->render($payloads->csvSections($payload)), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('relatorio-predicao', 'csv').'"',
        ]);
    }

    private function filename(string $prefix, string $extension): string
    {
        return $prefix.'-'.now()->format('Y-m-d').'.'.$extension;
    }
}
