<?php

namespace App\Filament\Pages;

use App\Services\FinanceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TargetDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.target-dashboard';

    public ?int $timelineMonth = null;

    public ?int $timelineYear = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_targets');
    }

    public static function getNavigationLabel(): string
    {
        return 'التاركت';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة المالية';
    }

    public function getTitle(): string
    {
        return 'التاركت';
    }

    public function mount(): void
    {
        $this->timelineMonth = 0;
        $this->timelineYear = now()->year;
    }

    public function getTargetData(): array
    {
        $finance = app(FinanceService::class);
        $timeline = $finance->monthlyTimeline();

        $filteredTimeline = collect($timeline)
            ->when((int) $this->timelineYear > 0, fn ($c) => $c->where('year', (int) $this->timelineYear))
            ->when((int) $this->timelineMonth > 0, fn ($c) => $c->where('month', (int) $this->timelineMonth))
            ->values()
            ->all();

        $timelineSummary = [
            'total_profit' => (int) collect($timeline)->sum('monthly_profit'),
            'total_investor_target' => (int) collect($timeline)->sum('monthly_investor_target'),
            'total_net' => (int) collect($timeline)->sum('net'),
            'surplus_months' => collect($timeline)->where('is_surplus', true)->count(),
            'deficit_months' => collect($timeline)->where('is_surplus', false)->count(),
            'final_balance' => (int) (collect($timeline)->last()['running_balance'] ?? 0),
        ];

        return [
            'investor_targets' => $finance->investorTargets(),
            'personal_target' => $finance->personalTarget($timelineSummary['final_balance']),
            'cash_register' => [
                'timeline' => $filteredTimeline,
                'timeline_summary' => $timelineSummary,
                'available_years' => collect($timeline)->pluck('year')->unique()->sort()->values()->all(),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
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
