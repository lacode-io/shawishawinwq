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

    public ?int $settlementMonth = null;

    public ?int $settlementYear = null;

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
        $this->settlementMonth = now()->month;
        $this->settlementYear = now()->year;
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
                'preview' => $finance->settlementPreview($this->settlementMonth, $this->settlementYear),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('settle_month')
                ->label('تصفية الحسابات')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'تصفية حسابات شهر ' . $this->settlementMonth . '/' . $this->settlementYear)
                ->modalDescription(function (): string {
                    $preview = app(FinanceService::class)->settlementPreview($this->settlementMonth, $this->settlementYear);

                    $iqd = fn (int $v) => number_format($v, 0, '.', ',') . ' د.ع';
                    $type = $preview['is_surplus'] ? 'فائض' : 'عجز';
                    $note = $preview['settle_count'] > 0 ? "\n⚠ تمت تصفية هذا الشهر {$preview['settle_count']} مرة سابقاً - ستُضاف تصفية جديدة" : '';

                    return "أرباح الشهر: {$iqd($preview['monthly_profit'])}\n"
                        . "تاركت المستثمرين: {$iqd($preview['monthly_investor_target'])}\n"
                        . "الفرق ({$type}): {$iqd(abs($preview['difference']))}\n"
                        . "رصيد القاصة بعد التصفية: {$iqd($preview['projected_balance'])}"
                        . $note;
                })
                ->modalSubmitActionLabel('تأكيد التصفية')
                ->action(function (): void {
                    $result = app(FinanceService::class)->settleMonth(
                        $this->settlementMonth,
                        $this->settlementYear
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
