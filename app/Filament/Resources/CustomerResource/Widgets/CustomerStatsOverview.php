<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class CustomerStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalActive = Customer::where('status', CustomerStatus::Active)->count();

        $uniquePhones = Customer::where('status', CustomerStatus::Active)
            ->distinct('phone')
            ->count('phone');

        $repeatedCustomer = Customer::select('phone', 'full_name', DB::raw('COUNT(*) as deals_count'))
            ->where('status', CustomerStatus::Active)
            ->groupBy('phone', 'full_name')
            ->orderByDesc('deals_count')
            ->first();

        $lateCount = Customer::where('status', CustomerStatus::Active)
            ->whereRaw('DATE_ADD(delivery_date, INTERVAL (SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) + 1 MONTH) < NOW()')
            ->count();

        $totalRemaining = Customer::where('status', CustomerStatus::Active)
            ->selectRaw('SUM(product_sale_total - COALESCE((SELECT SUM(amount) FROM customer_payments WHERE customer_payments.customer_id = customers.id), 0)) as remaining')
            ->value('remaining') ?? 0;

        return [
            Stat::make('الزبائن النشطين', $totalActive)
                ->description("({$uniquePhones} زبون فريد)")
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('المبلغ المتبقي', Number::iqd($totalRemaining))
                ->description('إجمالي الأقساط المتبقية')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('متأخرين', $lateCount)
                ->description('زبائن متأخرين عن الدفع')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($lateCount > 0 ? 'danger' : 'success'),

            Stat::make('الأكثر تكراراً', $repeatedCustomer?->full_name ?? '-')
                ->description($repeatedCustomer ? "{$repeatedCustomer->deals_count} صفقات" : 'لا يوجد')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
        ];
    }
}
