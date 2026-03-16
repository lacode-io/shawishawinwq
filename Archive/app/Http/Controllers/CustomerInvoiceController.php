<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class CustomerInvoiceController
{
    public function __invoke(Customer $customer)
    {
        $customer->load('payments.receivedBy');
        $settings = Setting::instance();

        $pdf = LaravelMpdf::loadView('pdf.customer-invoice', [
            'customer' => $customer,
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

        return $pdf->stream("invoice-{$customer->id}.pdf");
    }
}
