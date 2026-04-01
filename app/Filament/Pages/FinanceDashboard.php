<?php

namespace App\Filament\Pages;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorPayout;
use App\Models\Setting;
use App\Services\FinanceService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FinanceDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.finance-dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_finance_dashboard');
    }

    public ?string $exportFrom = null;

    public ?string $exportTo = null;

    public ?int $paymentsMonth = null;

    public ?int $paymentsYear = null;

    public ?int $profitMonth = null;

    public ?int $profitYear = null;

    public function mount(): void
    {
        $this->paymentsMonth = now()->month;
        $this->paymentsYear = now()->year;
        $this->profitMonth = now()->month;
        $this->profitYear = now()->year;
    }

    public static function getNavigationLabel(): string
    {
        return 'لوحة المالية';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة المالية';
    }

    public function getTitle(): string
    {
        return 'لوحة المالية';
    }

    public function getStats(): array
    {
        $finance = app(FinanceService::class);
        $expenses = $finance->monthlyExpenses();

        return [
            // ── Capital Cards ──
            'total_capital' => $finance->totalCapital(),
            'capital_installments' => $finance->capitalInInstallments(),
            'cash_capital' => $finance->cashCapital(),
            'investors_due' => $finance->investorsDueTotal(),
            'effective_capital' => $finance->effectiveCapital(),

            // ── تفاصيل رأس المال الكاش ──
            'manual_cash_capital' => (int) Setting::instance()->cash_capital,
            'extra_capital' => (int) Setting::instance()->extra_capital,
            'total_payments_in' => (int) CustomerPayment::sum('amount'),
            'monthly_payments_in' => (int) CustomerPayment::whereMonth('paid_at', $this->paymentsMonth)->whereYear('paid_at', $this->paymentsYear)->sum('amount'),
            'total_investments' => (int) Investor::sum('amount_invested'),
            'total_expenses_all' => (int) Expense::sum('amount'),
            'total_investor_payouts_all' => (int) InvestorPayout::sum('amount'),
            'total_cost_price' => (int) Customer::whereNotNull('product_cost_price')->sum('product_cost_price'),

            // ── تفاصيل كل بند ──
            'payments_by_customer' => CustomerPayment::with('customer')
                ->selectRaw('customer_id, SUM(amount) as total_amount, COUNT(*) as payments_count')
                ->groupBy('customer_id')
                ->orderByDesc('total_amount')
                ->get()
                ->map(fn ($p) => [
                    'name' => $p->customer?->full_name ?? 'محذوف',
                    'total' => (int) $p->total_amount,
                    'count' => $p->payments_count,
                ])->all(),

            'investors_list' => Investor::orderByDesc('amount_invested')
                ->get()
                ->map(fn ($i) => [
                    'name' => $i->full_name,
                    'amount' => (int) $i->amount_invested,
                ])->all(),

            'expenses_by_type' => Expense::selectRaw('type, SUM(amount) as total_amount, COUNT(*) as expenses_count')
                ->groupBy('type')
                ->orderByDesc('total_amount')
                ->get()
                ->map(fn ($e) => [
                    'type' => $e->type->label(),
                    'total' => (int) $e->total_amount,
                    'count' => $e->expenses_count,
                ])->all(),

            'payouts_by_investor' => Investor::with('payouts')
                ->orderByDesc('amount_invested')
                ->get()
                ->map(fn ($i) => [
                    'name' => $i->full_name,
                    'total_due' => $i->total_due,
                    'total_paid' => $i->total_paid_out,
                    'remaining' => $i->remaining_balance,
                    'count' => $i->payouts->count(),
                ])->all(),

            'total_remaining_investors' => (int) Investor::with('payouts')->get()->sum(fn ($i) => $i->remaining_balance),

            'cost_by_customer' => Customer::whereNotNull('product_cost_price')
                ->where('product_cost_price', '>', 0)
                ->orderByDesc('product_cost_price')
                ->get()
                ->map(fn ($c) => [
                    'name' => $c->full_name,
                    'cost' => (int) $c->product_cost_price,
                ])->all(),

            // ── القاصة ──
            'cash_register' => $finance->cashRegister(),
            'total_profit_earned' => $finance->totalProfitEarned(),
            'total_investor_dues_so_far' => $finance->totalInvestorDuesSoFar(),
            'total_investor_paid_out' => $finance->totalInvestorPaidOut(),

            // ── تفاصيل المستثمرين ──
            'investor_auto_payments' => $finance->investorAutoPaymentDetails(),

            // ── Expenses ──
            'monthly_business_expenses' => $expenses['business'],
            'monthly_personal_expenses' => $expenses['personal'],
            'monthly_total_expenses' => $expenses['total'],

            // ── Monthly Profit (selected month) ──
            'monthly_profit' => $finance->monthlyProfit((int) $this->profitMonth, (int) $this->profitYear),

            // ── Annual ──
            'annual_profit' => $finance->annualProfit(),

            // ── Investor Coverage ──
            'investor_coverage' => $finance->investorCoverageAnalysis(),

            // ── Alerts ──
            'late_customers_count' => Customer::where('status', CustomerStatus::Active)
                ->whereRaw('DATE_ADD(delivery_date, INTERVAL (SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) + 1 MONTH) < NOW()')
                ->count(),
            'behind_target_investors_count' => Investor::behindTarget()->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label(__('Export PDF'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->modalHeading('تصدير تقرير مالي')
                ->modalWidth('md')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('من تاريخ')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false)
                        ->displayFormat('Y/m/d'),

                    Forms\Components\DatePicker::make('to')
                        ->label('إلى تاريخ')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->displayFormat('Y/m/d'),
                ])
                ->action(function (array $data): void {
                    $this->redirect(route('finance.summary-pdf', [
                        'from' => $data['from'],
                        'to' => $data['to'],
                    ]));
                })
                ->visible(fn (): bool => auth()->user()->hasPermissionTo('export_pdf')),

            Actions\Action::make('refresh_cache')
                ->label('تحديث البيانات')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    app(FinanceService::class)->flush();

                    Notification::make()
                        ->title('تم تحديث البيانات')
                        ->success()
                        ->send();
                }),
        ];
    }
}
