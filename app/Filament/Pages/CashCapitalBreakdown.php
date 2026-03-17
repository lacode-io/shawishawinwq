<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorPayout;
use App\Models\Setting;
use App\Services\FinanceService;
use Filament\Pages\Page;

class CashCapitalBreakdown extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.cash-capital-breakdown';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'تفاصيل رأس المال الكاش';
    }

    public function getTitle(): string
    {
        return 'تفاصيل معادلة رأس المال الكاش';
    }

    public function getStats(): array
    {
        $manualCapital = (int) Setting::instance()->cash_capital;
        $totalPaymentsIn = (int) CustomerPayment::sum('amount');
        $totalInvestments = (int) Investor::sum('amount_invested');
        $totalExpenses = (int) Expense::sum('amount');
        $totalInvestorPayouts = (int) InvestorPayout::sum('amount');
        $totalCostPrice = (int) Customer::whereNotNull('product_cost_price')->sum('product_cost_price');

        $cashCapital = $manualCapital + $totalPaymentsIn + $totalInvestments - $totalExpenses - $totalInvestorPayouts - $totalCostPrice;

        return [
            'manual_capital' => $manualCapital,
            'total_payments_in' => $totalPaymentsIn,
            'total_investments' => $totalInvestments,
            'total_expenses' => $totalExpenses,
            'total_investor_payouts' => $totalInvestorPayouts,
            'total_cost_price' => $totalCostPrice,
            'cash_capital' => $cashCapital,

            // الداخل
            'total_in' => $manualCapital + $totalPaymentsIn + $totalInvestments,
            // الخارج
            'total_out' => $totalExpenses + $totalInvestorPayouts + $totalCostPrice,
        ];
    }
}
