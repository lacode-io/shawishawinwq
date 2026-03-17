<?php

namespace App\Filament\Pages;

use App\Services\FinanceService;
use Filament\Actions;
use Filament\Forms;
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
            'cash_register' => [
                'balance' => $finance->cashRegisterBalance(),
                'transactions' => $finance->cashRegisterTransactions(),
                'preview' => $finance->settlementPreview(),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        $finance = app(FinanceService::class);
        $preview = $finance->settlementPreview();

        return [
            Actions\Action::make('settle_month')
                ->label('تصفية الحسابات')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تصفية حسابات شهر ' . $preview['month'] . '/' . $preview['year'])
                ->modalDescription(function () use ($preview): string {
                    if ($preview['already_settled']) {
                        return 'تمت تصفية هذا الشهر مسبقاً!';
                    }

                    $iqd = fn (int $v) => number_format($v, 0, '.', ',') . ' د.ع';
                    $type = $preview['is_surplus'] ? 'فائض' : 'عجز';

                    return "أرباح الشهر: {$iqd($preview['monthly_profit'])}\n"
                        . "تاركت المستثمرين: {$iqd($preview['monthly_investor_target'])}\n"
                        . "الفرق ({$type}): {$iqd(abs($preview['difference']))}\n"
                        . "رصيد القاصة بعد التصفية: {$iqd($preview['projected_balance'])}";
                })
                ->modalSubmitActionLabel('تأكيد التصفية')
                ->disabled($preview['already_settled'])
                ->action(function () use ($preview): void {
                    $result = app(FinanceService::class)->settleMonth(
                        $preview['month'],
                        $preview['year']
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->title($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title($result['message'])
                            ->warning()
                            ->send();
                    }
                }),

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
