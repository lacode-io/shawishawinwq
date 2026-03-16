<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\Setting;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class InvestorStatementController
{
    public function __invoke(Investor $investor)
    {
        $investor->load('payouts');
        $settings = Setting::instance();

        $pdf = LaravelMpdf::loadView('pdf.investor-statement', [
            'investor' => $investor,
            'settings' => $settings,
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

        return $pdf->stream("investor-statement-{$investor->id}.pdf");
    }
}
