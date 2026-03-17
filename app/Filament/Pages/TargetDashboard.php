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

    public function getTargetData(): array
    {
        $finance = app(FinanceService::class);

        return [
            'investor_targets' => $finance->investorTargets(),
            'personal_target' => $finance->personalTarget(),
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
