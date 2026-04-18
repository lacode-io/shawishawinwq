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
use App\Models\CashRegisterTransaction;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            $settings = Setting::instance();
            $manualCapital = (int) $settings->cash_capital;
            $extraCapital = (int) $settings->extra_capital;
            $totalPaymentsIn = (int) CustomerPayment::sum('amount');
            $totalExpenses = (int) Expense::sum('amount');
            $totalInvestorPayouts = (int) InvestorPayout::sum('amount');
            $totalCostPrice = (int) Customer::whereNotNull('product_cost_price')->sum('product_cost_price');
            $totalInvestments = (int) Investor::sum('amount_invested');

            return $manualCapital + $extraCapital + $totalPaymentsIn + $totalInvestments - $totalExpenses - $totalInvestorPayouts - $totalCostPrice;
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
                $totalDue = $i->amount_invested + ($i->monthly_target_amount * $elapsed);

                return $totalDue - $i->total_paid_out;
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
     * Monthly total profit = sum of (sale_total - cost_price) for customers added this month
     */
    public function monthlyProfit(?int $month = null, ?int $year = null): int
    {
        $month ??= now()->month;
        $year ??= now()->year;

        $customers = Customer::whereMonth('delivery_date', $month)
            ->whereYear('delivery_date', $year)
            ->get(['product_sale_total', 'product_cost_price']);

        return (int) $customers->sum(fn ($c) => $c->product_sale_total - ($c->product_cost_price ?? 0));
    }

    /**
     * Monthly net profit = gross profit - all expenses - investor monthly dues
     */
    public function monthlyNetProfit(?int $month = null, ?int $year = null): int
    {
        $month ??= now()->month;
        $year ??= now()->year;

        $totalExpenses = (int) Expense::whereMonth('spent_at', $month)
            ->whereYear('spent_at', $year)
            ->sum('amount');

        $monthlyInvestorDues = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount');

        return $this->monthlyProfit($month, $year) - $totalExpenses - $monthlyInvestorDues;
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

        $commission = (int) Expense::where('type', ExpenseType::Commission)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        $custom = (int) Expense::where('type', ExpenseType::Custom)
            ->whereMonth('spent_at', $month->month)
            ->whereYear('spent_at', $month->year)
            ->sum('amount');

        return [
            'business' => $business,
            'personal' => $personal,
            'salary' => $salary,
            'commission' => $commission,
            'custom' => $custom,
            'total' => $business + $personal + $salary + $commission + $custom,
        ];
    }

    /**
     * Annual profit = sum of (sale_total - cost_price) for customers added this year
     */
    public function annualProfit(?int $year = null): int
    {
        $year ??= now()->year;

        $customers = Customer::whereYear('delivery_date', $year)
            ->get(['product_sale_total', 'product_cost_price']);

        return (int) $customers->sum(fn ($c) => $c->product_sale_total - ($c->product_cost_price ?? 0));
    }

    /**
     * Annual net profit = annual gross profit - all expenses - investor dues (12 months)
     */
    public function annualNetProfit(?int $year = null): int
    {
        $year ??= now()->year;

        $totalExpenses = (int) Expense::whereYear('spent_at', $year)
            ->sum('amount');

        $yearlyInvestorDues = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount') * 12;

        return $this->annualProfit($year) - $totalExpenses - $yearlyInvestorDues;
    }

    /**
     * Date-range summary for PDF export
     */
    public function rangeSummary(Carbon $from, Carbon $to): array
    {
        $salesCount = Customer::whereBetween('delivery_date', [$from, $to])->count();

        $grossProfit = (int) Customer::whereBetween('delivery_date', [$from, $to])
            ->get(['product_sale_total', 'product_cost_price'])
            ->sum(fn ($c) => $c->product_sale_total - ($c->product_cost_price ?? 0));

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

        $businessExpenses = (int) Expense::whereIn('type', [ExpenseType::Business, ExpenseType::Custom])
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
     * Monthly profit from all active customers (current snapshot)
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
     * الربح الموزع لشهر معين
     * يجيب كل زبون كان فعال بذاك الشهر:
     * - تاريخ تسليمه قبل أو خلال الشهر المطلوب
     * - نهاية أقساطه (تاريخ التسليم + المدة بالأشهر) بعد بداية الشهر المطلوب
     * ربح كل زبون = (سعر البيع - رأس المال) ÷ عدد الأشهر
     */
    public function distributedMonthlyProfit(int $month, int $year): int
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $customers = Customer::whereNotNull('product_cost_price')
            ->where('product_cost_price', '>', 0)
            ->where('duration_months', '>', 0)
            ->where('delivery_date', '<=', $monthEnd)
            ->get();

        $total = 0;
        foreach ($customers as $customer) {
            // نهاية فترة الأقساط
            $endDate = $customer->delivery_date->copy()->addMonths($customer->duration_months);

            // اذا الزبون خلص أقساطه قبل هذا الشهر، ما ينحسب
            if ($endDate->lt($monthStart)) {
                continue;
            }

            $totalProfit = $customer->product_sale_total - $customer->product_cost_price;
            $monthlyProfit = (int) round($totalProfit / $customer->duration_months);
            $total += $monthlyProfit;
        }

        return $total;
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
     * جدول الأشهر من أول زبون/مستثمر لحد الشهر الحالي
     * لكل شهر: أرباح المبيعات الموزعة − تاركت المستثمرين النشطين بذاك الشهر = الصافي
     * الرصيد التراكمي يرحل من شهر لشهر (العجز ينقص من فائض الشهر القادم)
     */
    public function monthlyTimeline(): array
    {
        $firstCustomer = Customer::whereNotNull('product_cost_price')
            ->where('duration_months', '>', 0)
            ->orderBy('delivery_date')
            ->first();

        $firstInvestor = Investor::orderBy('start_date')->first();

        $dates = array_filter([
            $firstCustomer?->delivery_date,
            $firstInvestor?->start_date,
        ]);

        if (empty($dates)) {
            return [];
        }

        $start = collect($dates)->min()->copy()->startOfMonth();
        $end = now()->endOfMonth();

        $investors = Investor::orderBy('start_date')->get();

        $months = [];
        $running = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            // أرباح الشهر = الإجمالي − رأس المال للزبائن المسلمين بهذا الشهر
            $profit = $this->monthlyProfit($cursor->month, $cursor->year);

            $activeInvestors = $investors->filter(function (Investor $inv) use ($monthStart, $monthEnd) {
                $invStart = $inv->start_date->copy()->startOfMonth();
                $invEnd = $inv->start_date->copy()->addMonths($inv->investment_months)->endOfMonth();

                return $invStart->lte($monthEnd) && $invEnd->gte($monthStart);
            });

            $monthlyInvestorTarget = (int) $activeInvestors->sum('monthly_target_amount');
            $net = $profit - $monthlyInvestorTarget;
            $running += $net;

            // عدد الزبائن المسلمين بهذا الشهر
            $customersCount = Customer::whereMonth('delivery_date', $cursor->month)
                ->whereYear('delivery_date', $cursor->year)
                ->count();

            $months[] = [
                'month' => $cursor->month,
                'year' => $cursor->year,
                'label' => $cursor->translatedFormat('F Y'),
                'monthly_profit' => $profit,
                'monthly_investor_target' => $monthlyInvestorTarget,
                'net' => $net,
                'is_surplus' => $net >= 0,
                'running_balance' => $running,
                'active_investors_count' => $activeInvestors->count(),
                'customers_count' => $customersCount,
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * بيانات تاركت كل مستثمر + الكلي
     * يحسب لكل مستثمر عدد الأشهر الي تغطت من أرباح المبيعات حسب تاريخ بدايته
     */
    public function investorTargets(): array
    {
        $investors = Investor::where('status', InvestorStatus::Active)
            ->with('payouts')
            ->get();

        $timeline = $this->monthlyTimeline();
        // أرباح المبيعات الشهرية = الإجمالي − رأس المال للزبائن المسلمين هذا الشهر
        $monthlyCustomerProfit = $this->monthlyProfit(now()->month, now()->year);
        $totalMonthlyTarget = (int) $investors->sum('monthly_target_amount');

        // current month's active investor total (لتوزيع حصة الشهر الحالي)
        $currentMonthTotal = (int) $investors->filter(function (Investor $inv) {
            $now = now();
            $invEnd = $inv->start_date->copy()->addMonths($inv->investment_months);

            return $inv->start_date->lte($now) && $invEnd->gte($now);
        })->sum('monthly_target_amount');

        $perInvestor = $investors->map(function (Investor $investor) use ($timeline, $currentMonthTotal) {
            $monthlyTarget = $investor->monthly_target_amount;
            $yearlyTarget = $monthlyTarget * 12;
            $totalTarget = $investor->total_due;
            $totalPaid = $investor->total_paid_out;
            $progressPercent = $totalTarget > 0 ? round(($totalPaid / $totalTarget) * 100, 1) : 0;

            $elapsed = min($investor->elapsed_months, $investor->investment_months);

            // حساب تغطية الأشهر من التايملاين
            $investorStart = $investor->start_date->copy()->startOfMonth();
            $investorEnd = $investor->start_date->copy()->addMonths($investor->investment_months)->startOfMonth();

            $cumulativeAllocated = 0;
            $monthsCovered = 0;
            $monthsMissed = 0;
            $thisMonthAchieved = 0;
            $now = now();

            foreach ($timeline as $row) {
                $rowDate = \Carbon\Carbon::create($row['year'], $row['month'], 1);

                // خارج نطاق هذا المستثمر
                if ($rowDate->lt($investorStart) || $rowDate->gte($investorEnd)) {
                    continue;
                }

                // لا نحسب الشهر المقبل لمستثمر ابتدى متأخر (بس اذا مضى)
                if ($rowDate->gt($now)) {
                    continue;
                }

                // حصة هذا المستثمر من ربح هذا الشهر = target/total_active_that_month × profit
                $share = $row['monthly_investor_target'] > 0
                    ? $monthlyTarget / $row['monthly_investor_target']
                    : 0;
                $allocated = (int) round($row['monthly_profit'] * $share);
                $cumulativeAllocated += $allocated;

                if ($allocated >= $monthlyTarget) {
                    $monthsCovered++;
                } else {
                    $monthsMissed++;
                }

                if ($row['month'] === $now->month && $row['year'] === $now->year) {
                    $thisMonthAchieved = $allocated;
                }
            }

            $coveragePercent = $elapsed > 0
                ? round(($monthsCovered / $elapsed) * 100, 1)
                : 0;

            $thisMonthProgress = $monthlyTarget > 0
                ? round(min(100, ($thisMonthAchieved / $monthlyTarget) * 100), 1)
                : 0;

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
                'months_covered' => $monthsCovered,
                'months_missed' => $monthsMissed,
                'coverage_percent' => min($coveragePercent, 100),
                'cumulative_allocated' => $cumulativeAllocated,
                'this_month_achieved' => $thisMonthAchieved,
                'this_month_progress' => $thisMonthProgress,
                'months_progress' => min($monthsProgress, 100),
                'progress_percent' => min($progressPercent, 100),
                'start_date' => $investor->start_date->format('Y/m/d'),
                'payout_due_date' => $investor->payout_due_date->format('Y/m/d'),
            ];
        })
        ->sortByDesc('coverage_percent')
        ->values()
        ->all();

        // المجموع الكلي
        $totalInvested = (int) $investors->sum('amount_invested');
        $totalDue = (int) $investors->sum(fn ($i) => $i->total_due);
        $totalPaidAll = (int) $investors->sum(fn ($i) => $i->total_paid_out);
        $totalMonthly = $totalMonthlyTarget;
        $totalYearly = $totalMonthly * 12;
        $totalProgress = $totalDue > 0 ? round(($totalPaidAll / $totalDue) * 100, 1) : 0;

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
     * التاركت السنوي = التاركت من الإعدادات + مستحقات المستثمرين (12 شهر) + الرواتب المدفوعة هذه السنة
     * التاركت الشهري = (التاركت من الإعدادات ÷ 12) + مستحقات المستثمرين الشهرية + رواتب الشهر
     */
    public function personalTarget(?int $computedBalance = null): array
    {
        $settings = Setting::instance();

        // التاركت الأساسي من الإعدادات
        $settingsYearlyTarget = (int) $settings->yearly_target_amount;
        $settingsMonthlyTarget = $settingsYearlyTarget > 0 ? (int) ceil($settingsYearlyTarget / 12) : 0;

        // مستحقات المستثمرين الشهرية
        $monthlyInvestorDues = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount');

        // مستحقات المستثمرين السنوية (12 شهر)
        $yearlyInvestorDues = $monthlyInvestorDues * 12;

        // رواتب الشهر الحالي
        $monthlySalaries = (int) Expense::where('type', ExpenseType::Salary)
            ->whereMonth('spent_at', now()->month)
            ->whereYear('spent_at', now()->year)
            ->sum('amount');

        // الرواتب المدفوعة هذه السنة
        $yearlySalaries = (int) Expense::where('type', ExpenseType::Salary)
            ->whereYear('spent_at', now()->year)
            ->sum('amount');

        // التاركت الشهري = رواتب الشهر + مستحقات المستثمرين فقط
        $monthlyTarget = $monthlyInvestorDues + $monthlySalaries;

        // التاركت السنوي = الرواتب المدفوعة + مستحقات المستثمرين 12 شهر (+ التاركت الأساسي)
        $yearlyTarget = $settingsYearlyTarget + $yearlyInvestorDues + $yearlySalaries;

        // أرباح الزبائن لهذا الشهر (الإجمالي - رأس المال)
        $monthlyCustomerProfit = $this->monthlyProfit(now()->month, now()->year);

        // نسبة الإنجاز = أرباح الزبائن / التاركت الشهري
        $monthlyProgress = $monthlyTarget > 0 ? round(($monthlyCustomerProfit / $monthlyTarget) * 100, 1) : 0;

        // الفائض الشهري = أرباح الزبائن - التاركت الشهري
        $monthlySurplus = $monthlyCustomerProfit - $monthlyTarget;

        // رصيد القاصة (التراكمي من التايملاين اذا تم تمريره)
        $balance = $computedBalance ?? (int) $settings->cash_register_balance;
        $yearlyProgress = $yearlyTarget > 0 ? round((max(0, $balance) / $yearlyTarget) * 100, 1) : 0;

        return [
            'yearly_target' => $yearlyTarget,
            'monthly_target' => $monthlyTarget,
            'settings_yearly_target' => $settingsYearlyTarget,
            'settings_monthly_target' => $settingsMonthlyTarget,
            'monthly_investor_dues' => $monthlyInvestorDues,
            'yearly_investor_dues' => $yearlyInvestorDues,
            'monthly_salaries' => $monthlySalaries,
            'yearly_salaries' => $yearlySalaries,
            'monthly_customer_profit' => $monthlyCustomerProfit,
            'monthly_surplus' => $monthlySurplus,
            'balance' => $balance,
            'yearly_progress' => min($yearlyProgress, 100),
            'monthly_progress' => min(max($monthlyProgress, 0), 100),
        ];
    }

    // ══════════════════════════════════════════════
    // ── القاصة - Settlement ──
    // ══════════════════════════════════════════════

    /**
     * رصيد القاصة الحالي
     */
    public function cashRegisterBalance(): int
    {
        return (int) Setting::instance()->cash_register_balance;
    }

    /**
     * سجل حركات القاصة
     */
    public function cashRegisterTransactions(int $limit = 20): array
    {
        return CashRegisterTransaction::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'month' => $t->month,
                'year' => $t->year,
                'date' => $t->created_at->format('Y/m/d H:i'),
            ])
            ->all();
    }

    /**
     * تصفية الحسابات لشهر معين
     * أرباح الزبائن (فرق سعر البيع - الشراء) لهذا الشهر − تاركت المستثمرين الشهري
     * الفائض يُضاف للقاصة والعجز يُخصم منها
     */
    public function settleMonth(int $month, int $year): array
    {
        // عدد التصفيات السابقة لهذا الشهر
        $settleCount = CashRegisterTransaction::where('month', $month)
            ->where('year', $year)
            ->where('description', 'like', 'تصفية حسابات شهرية%')
            ->count();

        // أرباح الشهر الموزعة (ربح كل زبون فعال بهذا الشهر ÷ أشهره)
        $monthlyProfit = $this->distributedMonthlyProfit($month, $year);

        // تاركت المستثمرين الشهري
        $monthlyInvestorTarget = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount');

        $difference = $monthlyProfit - $monthlyInvestorTarget;

        $label = $settleCount > 0
            ? ($difference >= 0 ? "تصفية حسابات شهرية - فائض (تحديث " . ($settleCount + 1) . ")" : "تصفية حسابات شهرية - عجز (تحديث " . ($settleCount + 1) . ")")
            : ($difference >= 0 ? 'تصفية حسابات شهرية - فائض' : 'تصفية حسابات شهرية - عجز');

        return DB::transaction(function () use ($difference, $monthlyProfit, $monthlyInvestorTarget, $month, $year, $label) {
            $settings = Setting::instance();
            $currentBalance = (int) $settings->cash_register_balance;
            $newBalance = $currentBalance + $difference;

            // تحديث رصيد القاصة
            $settings->update(['cash_register_balance' => $newBalance]);

            // تسجيل الحركة
            CashRegisterTransaction::create([
                'type' => $difference >= 0 ? 'deposit' : 'withdrawal',
                'amount' => abs($difference),
                'balance_after' => $newBalance,
                'description' => $label,
                'month' => $month,
                'year' => $year,
                'settled_by' => auth()->id(),
            ]);

            $this->flush();

            return [
                'success' => true,
                'monthly_profit' => $monthlyProfit,
                'monthly_investor_target' => $monthlyInvestorTarget,
                'difference' => $difference,
                'new_balance' => $newBalance,
                'message' => $difference >= 0
                    ? "تمت التصفية - فائض " . number_format(abs($difference)) . " د.ع أُضيف للقاصة"
                    : "تمت التصفية - عجز " . number_format(abs($difference)) . " د.ع خُصم من القاصة",
            ];
        });
    }

    /**
     * بيانات تصفية الشهر الحالي (معاينة قبل التنفيذ)
     */
    public function settlementPreview(?int $month = null, ?int $year = null): array
    {
        $month ??= now()->month;
        $year ??= now()->year;

        $monthlyProfit = $this->distributedMonthlyProfit($month, $year);

        $monthlyInvestorTarget = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount');

        $difference = $monthlyProfit - $monthlyInvestorTarget;
        $currentBalance = $this->cashRegisterBalance();

        $settleCount = CashRegisterTransaction::where('month', $month)
            ->where('year', $year)
            ->where('description', 'like', 'تصفية حسابات شهرية%')
            ->count();

        // عدد الزبائن الفعالين بهذا الشهر
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth();
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
        $customersCount = Customer::whereNotNull('product_cost_price')
            ->where('duration_months', '>', 0)
            ->where('delivery_date', '<=', $monthEnd)
            ->whereRaw('DATE_ADD(delivery_date, INTERVAL duration_months MONTH) >= ?', [$monthStart])
            ->count();

        return [
            'month' => $month,
            'year' => $year,
            'monthly_profit' => $monthlyProfit,
            'monthly_investor_target' => $monthlyInvestorTarget,
            'difference' => $difference,
            'is_surplus' => $difference >= 0,
            'current_balance' => $currentBalance,
            'projected_balance' => $currentBalance + $difference,
            'settle_count' => $settleCount,
            'customers_count' => $customersCount,
        ];
    }

    /**
     * تصفية كاملة - كل الأشهر الي فيها فرق غير مصفى
     * يحسب لكل شهر: الربح المتوقع - تاركت المستثمرين - المبلغ المصفى سابقاً = الفرق المتبقي
     * اذا الفرق != 0 يصفيه
     */
    public function settleAll(): array
    {
        $preview = $this->settleAllPreview();

        // فقط الأشهر الي عندها فرق متبقي
        $monthsToSettle = collect($preview['details'])->filter(fn ($d) => $d['remaining'] != 0);

        if ($monthsToSettle->isEmpty()) {
            return ['success' => false, 'message' => 'لا توجد أشهر تحتاج تصفية - كل الحسابات مصفاة', 'count' => 0];
        }

        $settledCount = 0;
        $totalDifference = 0;

        return DB::transaction(function () use ($monthsToSettle, &$settledCount, &$totalDifference) {
            $settings = Setting::instance();
            $currentBalance = (int) $settings->cash_register_balance;

            foreach ($monthsToSettle as $d) {
                $remaining = $d['remaining'];
                $currentBalance += $remaining;

                CashRegisterTransaction::create([
                    'type' => $remaining >= 0 ? 'deposit' : 'withdrawal',
                    'amount' => abs($remaining),
                    'balance_after' => $currentBalance,
                    'description' => $remaining >= 0
                        ? 'تصفية حسابات شهرية - فائض' . ($d['already_settled'] != 0 ? ' (تحديث)' : '')
                        : 'تصفية حسابات شهرية - عجز' . ($d['already_settled'] != 0 ? ' (تحديث)' : ''),
                    'month' => $d['month'],
                    'year' => $d['year'],
                    'settled_by' => auth()->id(),
                ]);

                $settledCount++;
                $totalDifference += $remaining;
            }

            $settings->update(['cash_register_balance' => $currentBalance]);
            $this->flush();

            return [
                'success' => true,
                'count' => $settledCount,
                'total_difference' => $totalDifference,
                'message' => "تمت تصفية {$settledCount} شهر بنجاح",
            ];
        });
    }

    /**
     * معاينة التصفية الكاملة
     * لكل شهر: الربح - تاركت المستثمرين = المتوقع، المتوقع - المصفى سابقاً = المتبقي
     */
    public function settleAllPreview(): array
    {
        // نجمع كل الأشهر من أول زبون لحد الشهر الحالي
        $firstCustomer = Customer::whereNotNull('product_cost_price')
            ->where('duration_months', '>', 0)
            ->orderBy('delivery_date')
            ->first();

        if (! $firstCustomer) {
            return [
                'total_months' => 0, 'pending_count' => 0, 'details' => [],
                'total_remaining' => 0, 'current_balance' => $this->cashRegisterBalance(),
                'projected_balance' => $this->cashRegisterBalance(),
            ];
        }

        $start = $firstCustomer->delivery_date->copy()->startOfMonth();
        $end = now()->endOfMonth();

        $allMonths = collect();
        $current = $start->copy();
        while ($current->lte($end)) {
            $allMonths->push(['month' => $current->month, 'year' => $current->year]);
            $current->addMonth();
        }

        $monthlyInvestorTarget = (int) Investor::where('status', InvestorStatus::Active)
            ->sum('monthly_target_amount');

        $details = $allMonths->map(function ($item) use ($monthlyInvestorTarget) {
            $profit = $this->distributedMonthlyProfit($item['month'], $item['year']);

            // اذا ما اكو ربح بهالشهر ولا تصفية سابقة، نتخطاه
            $alreadySettled = (int) CashRegisterTransaction::where('month', $item['month'])
                ->where('year', $item['year'])
                ->where('description', 'like', 'تصفية حسابات شهرية%')
                ->selectRaw("SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END) as net")
                ->value('net') ?? 0;

            if ($profit == 0 && $alreadySettled == 0 && $monthlyInvestorTarget == 0) {
                return null;
            }

            $expectedSettlement = $profit - $monthlyInvestorTarget;
            $remaining = $expectedSettlement - $alreadySettled;

            return [
                'month' => $item['month'],
                'year' => $item['year'],
                'profit' => $profit,
                'investor_target' => $monthlyInvestorTarget,
                'expected' => $expectedSettlement,
                'already_settled' => $alreadySettled,
                'remaining' => $remaining,
            ];
        })->filter()->values()->all();

        $totalRemaining = collect($details)->sum('remaining');
        $pendingCount = collect($details)->filter(fn ($d) => $d['remaining'] != 0)->count();
        $currentBalance = $this->cashRegisterBalance();

        return [
            'total_months' => count($details),
            'pending_count' => $pendingCount,
            'details' => $details,
            'total_remaining' => $totalRemaining,
            'current_balance' => $currentBalance,
            'projected_balance' => $currentBalance + $totalRemaining,
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
