<?php

use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\FinanceSummaryPdfController;
use App\Http\Controllers\InvestorStatementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/customers/{customer}/invoice', CustomerInvoiceController::class)
    ->middleware(['auth', 'can:export_pdf'])
    ->name('customers.invoice');

Route::get('/investors/{investor}/statement', InvestorStatementController::class)
    ->middleware(['auth', 'can:export_pdf'])
    ->name('investors.statement');

Route::get('/finance/summary-pdf', FinanceSummaryPdfController::class)
    ->middleware(['auth', 'can:export_pdf'])
    ->name('finance.summary-pdf');
