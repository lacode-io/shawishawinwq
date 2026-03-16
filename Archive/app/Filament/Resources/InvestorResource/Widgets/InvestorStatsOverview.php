<?php

namespace App\Filament\Resources\InvestorResource\Widgets;

use App\Enums\InvestorStatus;
use App\Models\Investor;
use App\Services\FinanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class InvestorStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeInvestors = Investor::where('status', InvestorStatus::Active)->get();

        $totalActive = $activeInvestors->count();

        $monthlyTargetTotal = $activeInvestors->sum('monthly_target_amount');

        // How much was actually paid out this month
        $achievedThisMonth = (int) \App\Models\InvestorPayout::whereHas('investor', fn ($q) => $q->where('status', InvestorStatus::Active))
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $missingThisMonth = max(0, $monthlyTargetTotal - $achievedThisMonth);

        $behindCount = Investor::behindTarget()->count();

        $totalRemainingBalance = $activeInvestors->sum(fn (Investor $i) => $i->remaining_balance);

        $coverage = app(FinanceService::class)->investorCoverageAnalysis();

        return [
            Stat::make('المستثمرين النشطين', $totalActive)
                ->descriptionIcon('heroicon-o-user-group')
                ->description('مستثمر نشط حالياً')
                ->color('primary'),

            Stat::make('الهدف الشهري', Number::iqd($monthlyTargetTotal))
                ->descriptionIcon('heroicon-o-flag')
                ->description(
                    'المحقق: '.Number::iqd($achievedThisMonth)
                )
                ->color($achievedThisMonth >= $monthlyTargetTotal ? 'success' : 'warning'),

            Stat::make('المتبقي هذا الشهر', Number::iqd($missingThisMonth))
                ->descriptionIcon('heroicon-o-clock')
                ->description($missingThisMonth > 0 ? 'يجب تسديده هذا الشهر' : 'تم تحقيق الهدف!')
                ->color($missingThisMonth > 0 ? 'danger' : 'success'),

            Stat::make('متأخرين عن الهدف', $behindCount)
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->description('مستثمرين لم يتم الوفاء بأهدافهم')
                ->color($behindCount > 0 ? 'danger' : 'success'),

            Stat::make('تغطية من الزبائن', $coverage['coverage_percent'].'%')
                ->descriptionIcon('heroicon-o-scale')
                ->description(
                    $coverage['is_covered']
                        ? 'فائض: '.Number::iqd(abs($coverage['gap']))
                        : 'عجز: '.Number::iqd(abs($coverage['gap']))
                )
                ->color($coverage['is_covered'] ? 'success' : 'danger'),
        ];
    }
}
