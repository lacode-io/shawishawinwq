<?php

namespace App\Filament\Widgets;

use App\Enums\ExpenseSubType;
use App\Enums\ExpenseType;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ExpenseStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $haiderTotal = (int) Expense::where('type', ExpenseType::Personal)
            ->where('sub_type', ExpenseSubType::Haider)
            ->sum('amount');

        $thaqrTotal = (int) Expense::where('type', ExpenseType::Personal)
            ->where('sub_type', ExpenseSubType::Thaqr)
            ->sum('amount');

        $otherTotal = (int) Expense::where('type', ExpenseType::Personal)
            ->where('sub_type', ExpenseSubType::Shared)
            ->sum('amount');

        $businessTotal = (int) Expense::where('type', ExpenseType::Business)
            ->sum('amount');

        $salaryTotal = (int) Expense::where('type', ExpenseType::Salary)
            ->sum('amount');

        $commissionTotal = (int) Expense::where('type', ExpenseType::Commission)
            ->sum('amount');

        $grandTotal = $haiderTotal + $thaqrTotal + $otherTotal + $businessTotal + $salaryTotal + $commissionTotal;

        return [
            Stat::make('حيدر', Number::iqd($haiderTotal))
                ->description('إجمالي مصاريف حيدر')
                ->color('info')
                ->icon('heroicon-o-user'),

            Stat::make('ذو الفقار', Number::iqd($thaqrTotal))
                ->description('إجمالي مصاريف ذو الفقار')
                ->color('warning')
                ->icon('heroicon-o-user'),

            Stat::make('مشتركه', Number::iqd($otherTotal))
                ->description('مصاريف شخصية مشتركه')
                ->color('gray')
                ->icon('heroicon-o-ellipsis-horizontal-circle'),

            Stat::make('مصاريف العمل', Number::iqd($businessTotal))
                ->description('إجمالي مصاريف العمل')
                ->color('danger')
                ->icon('heroicon-o-building-storefront'),

            Stat::make('الرواتب', Number::iqd($salaryTotal))
                ->description('حيدر + ذو الفقار')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('كومشن', Number::iqd($commissionTotal))
                ->description('إجمالي الكومشن')
                ->color('danger')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('الإجمالي الكلي', Number::iqd($grandTotal))
                ->description('جميع المصاريف')
                ->color('primary')
                ->icon('heroicon-o-calculator'),
        ];
    }
}
