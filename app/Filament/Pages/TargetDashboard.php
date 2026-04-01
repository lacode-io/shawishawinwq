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
                ->modalHeading('تصفية الحسابات')
                ->modalWidth('md')
                ->form([
                    Forms\Components\Select::make('settle_month')
                        ->label('الشهر')
                        ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create()->month($m)->translatedFormat('F')]))
                        ->default(now()->month)
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('settle_year')
                        ->label('السنة')
                        ->options(collect(range(now()->year, 2024, -1))->mapWithKeys(fn ($y) => [$y => $y]))
                        ->default(now()->year)
                        ->required()
                        ->live(),

                    Forms\Components\Placeholder::make('settlement_preview')
                        ->label('معاينة التصفية')
                        ->content(function (Forms\Get $get): string {
                            $month = (int) ($get('settle_month') ?: now()->month);
                            $year = (int) ($get('settle_year') ?: now()->year);
                            $preview = app(FinanceService::class)->settlementPreview($month, $year);

                            $iqd = fn (int $v) => number_format($v, 0, '.', ',') . ' د.ع';
                            $type = $preview['is_surplus'] ? 'فائض' : 'عجز';

                            $text = "أرباح الشهر: {$iqd($preview['monthly_profit'])}\n"
                                . "تاركت المستثمرين: {$iqd($preview['monthly_investor_target'])}\n"
                                . "الفرق ({$type}): {$iqd(abs($preview['difference']))}\n"
                                . "رصيد القاصة الحالي: {$iqd($preview['current_balance'])}\n"
                                . "رصيد القاصة بعد التصفية: {$iqd($preview['projected_balance'])}";

                            if ($preview['settle_count'] > 0) {
                                $text .= "\n\n⚠ تمت تصفية هذا الشهر {$preview['settle_count']} مرة سابقاً - ستُضاف تصفية جديدة";
                            }

                            return $text;
                        })
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('تأكيد التصفية')
                ->action(function (array $data): void {
                    $result = app(FinanceService::class)->settleMonth(
                        (int) $data['settle_month'],
                        (int) $data['settle_year']
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
