<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorPayout;
use App\Models\Setting;
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

        // تفاصيل تسديدات الزبائن - مجمعة حسب الزبون
        $paymentsByCustomer = CustomerPayment::with('customer')
            ->selectRaw('customer_id, SUM(amount) as total_amount, COUNT(*) as payments_count')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->customer?->full_name ?? 'محذوف',
                'total' => (int) $p->total_amount,
                'count' => $p->payments_count,
            ])->all();

        // تفاصيل استثمارات المستثمرين
        $investorsList = Investor::orderByDesc('amount_invested')
            ->get()
            ->map(fn ($i) => [
                'name' => $i->full_name,
                'amount' => (int) $i->amount_invested,
            ])->all();

        // تفاصيل المصاريف - مجمعة حسب النوع
        $expensesByType = Expense::selectRaw('type, SUM(amount) as total_amount, COUNT(*) as expenses_count')
            ->groupBy('type')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($e) => [
                'type' => $e->type->label(),
                'total' => (int) $e->total_amount,
                'count' => $e->expenses_count,
            ])->all();

        // تفاصيل دفعات المستثمرين - مع المستحق المتبقي
        $payoutsByInvestor = Investor::with('payouts')
            ->orderByDesc('amount_invested')
            ->get()
            ->map(fn ($i) => [
                'name' => $i->full_name,
                'total_due' => $i->total_due,
                'total_paid' => $i->total_paid_out,
                'remaining' => $i->remaining_balance,
                'count' => $i->payouts->count(),
            ])->all();

        $totalRemainingInvestors = (int) collect($payoutsByInvestor)->sum('remaining');

        // تفاصيل سعر شراء البضائع - حسب الزبون
        $costByCustomer = Customer::whereNotNull('product_cost_price')
            ->where('product_cost_price', '>', 0)
            ->orderByDesc('product_cost_price')
            ->get()
            ->map(fn ($c) => [
                'name' => $c->full_name,
                'cost' => (int) $c->product_cost_price,
            ])->all();

        return [
            'manual_capital' => $manualCapital,
            'total_payments_in' => $totalPaymentsIn,
            'total_investments' => $totalInvestments,
            'total_expenses' => $totalExpenses,
            'total_investor_payouts' => $totalInvestorPayouts,
            'total_cost_price' => $totalCostPrice,
            'cash_capital' => $cashCapital,
            'total_in' => $manualCapital + $totalPaymentsIn + $totalInvestments,
            'total_out' => $totalExpenses + $totalInvestorPayouts + $totalCostPrice,

            // التفاصيل
            'payments_by_customer' => $paymentsByCustomer,
            'investors_list' => $investorsList,
            'expenses_by_type' => $expensesByType,
            'payouts_by_investor' => $payoutsByInvestor,
            'total_remaining_investors' => $totalRemainingInvestors,
            'cost_by_customer' => $costByCustomer,
        ];
    }
}
