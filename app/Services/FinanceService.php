<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\ExpenseType;
use App\Enums\InvestorStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Investor;
use App\Models\InvestorPayout;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FinanceService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * رأس المال الكلي = sum of all product_cost_price
     */
    public function totalCapital(): int
    {
        return Cache::remember('finance.total_capital', self::CACHE_TTL, function () {
            return (int) Customer::whereNotNull('product_cost_price')
                ->sum('product_cost_price');
        });
    }

    /**
     * رأس المال بالأقساط = المتبقي الي ما مدفوع من الزبائن الفعالين
     * (سعر البيع الكلي - المبلغ المدفوع) لكل زبون فعال
     */
    public function capitalInInstallments(): int
    {
        return Cache::remember('finance.capital_installments', self::CACHE_TTL, function () {
            return (int) Customer::where('status', CustomerStatus::Active)
                ->selectRaw('SUM(GREATEST(0, product_sale_total - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_payments.customer_id = customers.id), 0))) as remaining')
                ->value('remaining') ?? 0;
        });
    }

    /**
     * رأس المال الكاش = المبلغ المدخل بالإعدادات + تسديدات الزبائن - المصاريف - دفعات المستثمرين
     * كل ما زبون يسدد ينجمع ويه رأس المال الكاش
     */
    public function cashCapital(): int
    {
        return Cache::remember('finance.cash_capital', self::CACHE_TTL, function () {
            $manualCapital = (int) Setting::instance()->cash_capital;
            $totalPaymentsIn = (int) CustomerPayment::sum('amount');
            $totalExpenses = (int) Expense::sum('amount');
            $totalInvestorPayouts = (int) InvestorPayout::sum('amount');
            $totalCostPrice = (int) Customer::whereNotNull('product_cost_price')->sum('product_cost_price');
            $totalInvestments = (int) Investor::sum('amount_invested');

            return $manualCapital + $totalPaymentsIn + $totalInvestments - $totalExpenses - $totalInvestorPayouts - $totalCostPrice;
        });
    }

    /**
     * إجمالي الأرباح المحققة من مبيعات الزبائن
     * لكل زبون: (سعر البيع - سعر الشراء) = الربح الكلي
     * نسبة الربح من كل دفعة = (الربح / سعر البيع) × المبلغ المدفوع
     */
    public function totalProfitEarned(): int
    {
        return Cache::remember('finance.total_profit_earned', self::CACHE_TTL, function () {
            $customers = Customer::whereNotNull('product_cost_price')
                ->where('product_sale_total', '>', 0)
                ->with('payments')
                ->get();

            $totalProfit = 0;
            foreach ($customers as $customer) {
                $profitMargin = ($customer->product_sale_total - $customer->product_cost_price) / $customer->product_sale_total;
                $totalPaid = $customer->payments->sum('amount');
                $totalProfit += (int) round($totalPaid * $profitMargin);
            }

            return $totalProfit;
        });
    }

    /**
     * إجمالي مستحقات المستثمرين الشهرية المتراكمة حتى الآن
     * لكل مستثمر: monthly_target × عدد الأشهر المنقضية
     */
    public function totalInvestorDuesSoFar(): int
    {
        return Cache::remember('finance.investor_dues_so_far', self::CACHE_TTL, function () {
            $investors = Investor::where('status', InvestorStatus::Active)->get();

            return (int) $investors->sum(function (Investor $investor) {
                $elapsed = min($investor->elapsed_months, $investor->investment_months);
                return $elapsed * $investor->monthly_target_amount;
            });
        });
    }

    /**
     * إجمالي المدفوع للمستثمرين فعلياً
     */
    public function totalInvestorPaidOut(): int
    {
        return Cache::remember('finance.investor_paid_out', self::CACHE_TTL, function () {
            return (int) InvestorPayout::sum('amount');
        });
    }

    /**
     * القاصة = الأرباح المحققة من المبيعات - مستحقات المستثمرين المتراكمة
     * إذا الأرباح أكثر من المستحقات → فائض (رصيد موجب)
     * إذا الأرباح أقل من المستحقات → عجز (رصيد سالب)
     */
    public function cashRegister(): int
    {
        return Cache::remember('finance.cash_register', self::CACHE_TTL, function () {
            $profitEarned = $this->totalProfitEarnedUncached();
            $investorDues = $this->totalInvestorDuesSoFarUncached();

            return $profitEarned - $investorDues;
        });
    }

    /**
     * المستحقات للمستثمرين = مبلغ الاستثمار + (المبلغ الشهري المستهدف × الأشهر المنقضية)
     */
    public function investorsDueTotal(): int
    {
        return Cache::remember('finance.investors_due', self::CACHE_TTL, function () {
            $investors = Investor::where('status', InvestorStatus::Active)->get();

            return (int) $investors->sum(function (Investor $i) {
                $elapsed = min($i->elapsed_months, $i->investment_months);

                return $i->amount_invested + ($i->monthly_target_amount * $elapsed);
            });
        });
    }

    /**
     * الرأسمال الفعلي = (Capital in installments + Cash capital) - Investors due
     */
    public function effectiveCapital(): int
    {
        return ($this->capitalInInstallments() + $this->cashCapital()) - $this->investorsDueTotal();
    }

    /**
     * Monthly sales count (customers created this month)
     */
    public function monthlySalesCount(?Carbon $month = null): int
    {
        $month ??= now();

        return Customer::whereMonth('delivery_date', $month->month)
            ->whereYear('delivery_date', $month->year)
            ->count();
    }

    /**
     * Monthly total profit = sum of (sale_total - cost_price) for sales this month
     */
    public function monthlyProfit(?Carbon $month = null): int
    {
        $month ??= now();

        return (int) Customer::whereNotNull('product_cost_price')
            ->whereMonth('delivery_date', $month->month)
            ->whereYear('delivery_date', $month->year)
            ->selectRaw('SUM(product_sale_total - product_cost_price) as profit')
            ->value('profit') ?? 0;
    }

    /**
     * Monthly net profit = gross profit - business expenses this month
     */
    public function monthlyNetProfit(?Carbon $month = null): int
    {
        $month ??= now();

        $businessExpenses = (int) Expense::where('type', ExpenseType::Business)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        return $this->monthlyProfit($month) - $businessExpenses;
    }

    /**
     * Monthly cash payments received (tracks cash flow into cash bucket)
     */
    public function monthlyCashPaymentsReceived(?Carbon $month = null): int
    {
        $month ??= now();

        return (int) CustomerPayment::where('payment_method', PaymentMethod::Cash)
            ->whereMonth('paid_at', $month->month)
            ->whereYear('paid_at', $month->year)
            ->sum('amount');
    }

    /**
     * Monthly total payments received
     */
    public function monthlyPaymentsReceived(?Carbon $month = null): int
    {
        $month ??= now();

        return (int) CustomerPayment::whereMonth('paid_at', $month->month)
            ->whereYear('paid_at', $month->year)
            ->sum('amount');
    }

    /**
     * Monthly expenses totals
     */
    public function monthlyExpenses(?Carbon $month = null): array
    {
        $month ??= now();

        $business = (int) Expense::where('type', ExpenseType::Business)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        $personal = (int) Expense::where('type', ExpenseType::Personal)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        $salary = (int) Expense::where('type', ExpenseType::Salary)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        return [
            'business' => $business,
            'personal' => $personal,
            'salary' => $salary,
            'total' => $business + $personal + $salary,
        ];
    }

    /**
     * Annual profit (current year)
     */
    public function annualProfit(?int $year = null): int
    {
        $year ??= now()->year;

        return (int) Customer::whereNotNull('product_cost_price')
            ->whereYear('delivery_date', $year)
            ->selectRaw('SUM(product_sale_total - product_cost_price) as profit')
            ->value('profit') ?? 0;
    }

    /**
     * Annual net profit = annual gross profit - annual business expenses
     */
    public function annualNetProfit(?int $year = null): int
    {
        $year ??= now()->year;

        $businessExpenses = (int) Expense::where('type', ExpenseType::Business)
            ->whereYear('spent_at', $year)
            ->sum('amount');

        return $this->annualProfit($year) - $businessExpenses;
    }

    /**
     * Date-range summary for PDF export
     */
    public function rangeSummary(Carbon $from, Carbon $to): array
    {
        $salesCount = Customer::whereBetween('delivery_date', [$from, $to])->count();

        $grossProfit = (int) Customer::whereNotNull('product_cost_price')
            ->whereBetween('delivery_date', [$from, $to])
            ->selectRaw('SUM(product_sale_total - product_cost_price) as profit')
            ->value('profit') ?? 0;

        $totalSales = (int) Customer::whereBetween('delivery_date', [$from, $to])
            ->sum('product_sale_total');

        $totalCost = (int) Customer::whereBetween('delivery_date', [$from, $to])
            ->sum('product_cost_price');

        $paymentsReceived = (int) CustomerPayment::whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $cashPayments = (int) CustomerPayment::where('payment_method', PaymentMethod::Cash)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $transferPayments = (int) CustomerPayment::where('payment_method', PaymentMethod::Transfer)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $businessExpenses = (int) Expense::where('type', ExpenseType::Business)
            ->whereBetween('spent_at', [$from, $to])
            ->sum('amount');

        $personalExpenses = (int) Expense::where('type', ExpenseType::Personal)
            ->whereBetween('spent_at', [$from, $to])
            ->sum('amount');

        $investorPayouts = (int) InvestorPayout::whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        return [
            'sales_count' => $salesCount,
            'total_sales' => $totalSales,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit - $businessExpenses,
            'payments_received' => $paymentsReceived,
            'cash_payments' => $cashPayments,
            'transfer_payments' => $transferPayments,
            'business_expenses' => $businessExpenses,
            'personal_expenses' => $personalExpenses,
            'total_expenses' => $businessExpenses + $personalExpenses,
            'investor_payouts' => $investorPayouts,
        ];
    }

    /**
     * Monthly profit from all active customers
     */
    public function monthlyProfitFromActiveCustomers(): array
    {
        $customers = Customer::where('status', CustomerStatus::Active)
            ->whereNotNull('product_cost_price')
            ->where('duration_months', '>', 0)
            ->get();

        $perCustomer = $customers->map(function (Customer $customer) {
            $totalProfit = $customer->product_sale_total - $customer->product_cost_price;
            $monthlyProfit = (int) round($totalProfit / $customer->duration_months);

            return [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'total_profit' => $totalProfit,
                'duration_months' => $customer->duration_months,
                'monthly_profit' => $monthlyProfit,
            ];
        });

        return [
            'total_monthly_profit' => (int) $perCustomer->sum('monthly_profit'),
            'active_count' => $customers->count(),
            'per_customer' => $perCustomer->all(),
        ];
    }

    /**
     * Investor coverage analysis - how well do active customer profits cover investor obligations?
     */
    public function investorCoverageAnalysis(): array
    {
        $activeInvestors = Investor::where('status', InvestorStatus::Active)->get();
        $monthlyInvestorTarget = (int) $activeInvestors->sum('monthly_target_amount');

        $customerProfits = $this->monthlyProfitFromActiveCustomers();
        $monthlyCustomerProfit = $customerProfits['total_monthly_profit'];

        $coveragePercent = $monthlyInvestorTarget > 0
            ? round(($monthlyCustomerProfit / $monthlyInvestorTarget) * 100, 1)
            : 0;

        $gap = $monthlyCustomerProfit - $monthlyInvestorTarget;

        $perInvestor = $activeInvestors->map(function (Investor $investor) use ($monthlyCustomerProfit, $monthlyInvestorTarget) {
            $share = $monthlyInvestorTarget > 0
                ? $investor->monthly_target_amount / $monthlyInvestorTarget
                : 0;
            $coveredAmount = (int) round($monthlyCustomerProfit * $share);
            $shortfall = max(0, $investor->monthly_target_amount - $coveredAmount);

            return [
                'id' => $investor->id,
                'name' => $investor->full_name,
                'monthly_target' => $investor->monthly_target_amount,
                'covered_from_profit' => $coveredAmount,
                'shortfall' => $shortfall,
                'is_covered' => $coveredAmount >= $investor->monthly_target_amount,
            ];
        })->all();

        return [
            'monthly_investor_target' => $monthlyInvestorTarget,
            'monthly_customer_profit' => $monthlyCustomerProfit,
            'active_customer_count' => $customerProfits['active_count'],
            'per_customer' => $customerProfits['per_customer'],
            'coverage_percent' => $coveragePercent,
            'gap' => $gap,
            'is_covered' => $gap >= 0,
            'active_investor_count' => $activeInvestors->count(),
            'per_investor' => $perInvestor,
        ];
    }

    /**
     * تفاصيل المستثمرين مع التسديد التلقائي
     */
    public function investorAutoPaymentDetails(): array
    {
        $investors = Investor::where('status', InvestorStatus::Active)
            ->with('payouts')
            ->get();

        $customerProfits = $this->monthlyProfitFromActiveCustomers();
        $monthlyProfit = $customerProfits['total_monthly_profit'];
        $totalMonthlyTarget = (int) $investors->sum('monthly_target_amount');

        return $investors->map(function (Investor $investor) use ($monthlyProfit, $totalMonthlyTarget) {
            $elapsed = min($investor->elapsed_months, $investor->investment_months);
            $totalDueSoFar = $elapsed * $investor->monthly_target_amount;

            // حصة المستثمر من الربح الشهري
            $share = $totalMonthlyTarget > 0
                ? $investor->monthly_target_amount / $totalMonthlyTarget
                : 0;
            $monthlyFromProfit = (int) round($monthlyProfit * $share);

            // الفرق: المستحق حتى الآن - المدفوع فعلياً
            $totalPaid = $investor->total_paid_out;
            $gap = $totalDueSoFar - $totalPaid;

            return [
                'id' => $investor->id,
                'name' => $investor->full_name,
                'amount_invested' => $investor->amount_invested,
                'profit_percent' => $investor->profit_percent_total,
                'investment_months' => $investor->investment_months,
                'monthly_target' => $investor->monthly_target_amount,
                'elapsed_months' => $elapsed,
                'total_due_so_far' => $totalDueSoFar,
                'total_paid' => $totalPaid,
                'remaining_balance' => $investor->remaining_balance,
                'monthly_from_profit' => $monthlyFromProfit,
                'gap' => $gap,
                'is_behind' => $gap > 0,
                'progress_percent' => $investor->progress_percent,
            ];
        })->all();
    }

    /**
     * Uncached version for internal use (to avoid nested cache issues)
     */
    private function totalProfitEarnedUncached(): int
    {
        $customers = Customer::whereNotNull('product_cost_price')
            ->where('product_sale_total', '>', 0)
            ->with('payments')
            ->get();

        $totalProfit = 0;
        foreach ($customers as $customer) {
            $profitMargin = ($customer->product_sale_total - $customer->product_cost_price) / $customer->product_sale_total;
            $totalPaid = $customer->payments->sum('amount');
            $totalProfit += (int) round($totalPaid * $profitMargin);
        }

        return $totalProfit;
    }

    private function totalInvestorDuesSoFarUncached(): int
    {
        $investors = Investor::where('status', InvestorStatus::Active)->get();

        return (int) $investors->sum(function (Investor $investor) {
            $elapsed = min($investor->elapsed_months, $investor->investment_months);
            return $elapsed * $investor->monthly_target_amount;
        });
    }

    // ══════════════════════════════════════════════
    // ── Target Calculations ──
    // ══════════════════════════════════════════════

    /**
     * بيانات تاركت كل مستثمر + الكلي
     */
    public function investorTargets(): array
    {
        $investors = Investor::where('status', InvestorStatus::Active)
            ->with('payouts')
            ->get();

        $monthlyCustomerProfit = $this->monthlyProfitFromActiveCustomers()['total_monthly_profit'];
        $totalMonthlyTarget = (int) $investors->sum('monthly_target_amount');

        $perInvestor = $investors->map(function (Investor $investor) {
            $monthlyTarget = $investor->monthly_target_amount;
            $yearlyTarget = $monthlyTarget * 12;
            $totalTarget = $investor->total_due; // مبلغ الاستثمار + الأرباح
            $totalPaid = $investor->total_paid_out;
            $progressPercent = $totalTarget > 0 ? round(($totalPaid / $totalTarget) * 100, 1) : 0;

            $elapsed = min($investor->elapsed_months, $investor->investment_months);
            // نسبة الإنجاز حسب الأشهر المنقضية فقط (الوقت)
            $monthsProgress = $investor->investment_months > 0
                ? round(($elapsed / $investor->investment_months) * 100, 1)
                : 0;

            return [
                'id' => $investor->id,
                'name' => $investor->full_name,
                'amount_invested' => $investor->amount_invested,
                'profit_percent' => $investor->profit_percent_total,
                'investment_months' => $investor->investment_months,
                'total_profit_amount' => $investor->total_profit_amount,
                'total_due' => $totalTarget,
                'monthly_target' => $monthlyTarget,
                'yearly_target' => $yearlyTarget,
                'total_paid' => $totalPaid,
                'remaining' => $investor->remaining_balance,
                'elapsed_months' => $elapsed,
                'months_progress' => min($monthsProgress, 100),
                'progress_percent' => min($progressPercent, 100),
                'start_date' => $investor->start_date->format('Y/m/d'),
                'payout_due_date' => $investor->payout_due_date->format('Y/m/d'),
            ];
        })->all();

        // المجموع الكلي
        $totalInvested = (int) $investors->sum('amount_invested');
        $totalDue = (int) $investors->sum(fn ($i) => $i->total_due);
        $totalPaidAll = (int) $investors->sum(fn ($i) => $i->total_paid_out);
        $totalMonthly = $totalMonthlyTarget;
        $totalYearly = $totalMonthly * 12;
        $totalProgress = $totalDue > 0 ? round(($totalPaidAll / $totalDue) * 100, 1) : 0;

        // تغطية من أرباح المبيعات
        $profitCoverage = $monthlyCustomerProfit - $totalMonthlyTarget;

        return [
            'per_investor' => $perInvestor,
            'combined' => [
                'total_invested' => $totalInvested,
                'total_due' => $totalDue,
                'total_paid' => $totalPaidAll,
                'total_remaining' => $totalDue - $totalPaidAll,
                'monthly_target' => $totalMonthly,
                'yearly_target' => $totalYearly,
                'progress_percent' => min($totalProgress, 100),
            ],
            'coverage' => [
                'monthly_profit' => $monthlyCustomerProfit,
                'monthly_target' => $totalMonthlyTarget,
                'surplus' => $profitCoverage,
                'is_covered' => $profitCoverage >= 0,
            ],
        ];
    }

    /**
     * بيانات التاركت الشخصي (السنوي والشهري)
     * التاركت = الفائض بعد المصاريف والمستثمرين
     */
    public function personalTarget(): array
    {
        $yearlyTarget = (int) Setting::instance()->yearly_target_amount;
        $monthlyTarget = $yearlyTarget > 0 ? (int) ceil($yearlyTarget / 12) : 0;

        // الفائض = الأرباح المحققة - مستحقات المستثمرين المتراكمة - المصاريف الكلية
        $totalProfitEarned = $this->totalProfitEarned();
        $totalInvestorDues = $this->totalInvestorDuesSoFar();
        $totalExpenses = (int) Expense::sum('amount');

        $surplus = $totalProfitEarned - $totalInvestorDues - $totalExpenses;
        $yearlyProgress = $yearlyTarget > 0 ? round((max(0, $surplus) / $yearlyTarget) * 100, 1) : 0;

        // التاركت الشهري - نسبة الإنجاز للشهر الحالي
        $currentMonthProfit = $this->monthlyProfit();
        $currentMonthExpenses = $this->monthlyExpenses();
        $currentMonthInvestorTarget = (int) Investor::where('status', InvestorStatus::Active)->sum('monthly_target_amount');

        $monthlySurplus = $currentMonthProfit - $currentMonthInvestorTarget - $currentMonthExpenses['total'];
        $monthlyProgress = $monthlyTarget > 0 ? round((max(0, $monthlySurplus) / $monthlyTarget) * 100, 1) : 0;

        return [
            'yearly_target' => $yearlyTarget,
            'monthly_target' => $monthlyTarget,
            'total_profit_earned' => $totalProfitEarned,
            'total_investor_dues' => $totalInvestorDues,
            'total_expenses' => $totalExpenses,
            'surplus' => $surplus,
            'yearly_progress' => min($yearlyProgress, 100),
            'monthly_surplus' => $monthlySurplus,
            'monthly_progress' => min($monthlyProgress, 100),
            'current_month_profit' => $currentMonthProfit,
            'current_month_expenses' => $currentMonthExpenses['total'],
            'current_month_investor_target' => $currentMonthInvestorTarget,
        ];
    }

    /**
     * Flush all finance caches
     */
    public function flush(): void
    {
        Cache::forget('finance.total_capital');
        Cache::forget('finance.capital_installments');
        Cache::forget('finance.cash_capital');
        Cache::forget('finance.investors_due');
        Cache::forget('finance.total_profit_earned');
        Cache::forget('finance.investor_dues_so_far');
        Cache::forget('finance.investor_paid_out');
        Cache::forget('finance.cash_register');
    }
}
