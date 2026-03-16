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
            'total_payments_in' => (int) CustomerPayment::sum('amount'),
            'total_expenses_all' => (int) Expense::sum('amount'),
            'total_investor_payouts_all' => (int) InvestorPayout::sum('amount'),

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

            // ── Annual ──
            'annual_profit' => $finance->annualProfit(),
            'annual_net_profit' => $finance->annualNetProfit(),

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
