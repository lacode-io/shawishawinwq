<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class FinanceSummaryPdfController
{
    public function __invoke(Request $request, FinanceService $finance)
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()));
        $to = Carbon::parse($request->query('to', now()));
        $settings = Setting::instance();

        $summary = $finance->rangeSummary($from, $to);

        $pdf = LaravelMpdf::loadView('pdf.finance-summary', [
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
            'settings' => $settings,
            'finance' => $finance,
        ], [], [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'default_font' => 'ibmplexsansarabic',
            'direction' => 'rtl',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);

        return $pdf->stream('finance-summary-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.pdf');
    }
}
