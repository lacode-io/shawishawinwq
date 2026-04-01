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
                        ->label('')
                        ->content(function (Forms\Get $get): \Illuminate\Support\HtmlString {
                            $month = (int) ($get('settle_month') ?: now()->month);
                            $year = (int) ($get('settle_year') ?: now()->year);
                            $preview = app(FinanceService::class)->settlementPreview($month, $year);

                            $iqd = fn (int $v) => number_format($v, 0, '.', ',') . ' د.ع';
                            $type = $preview['is_surplus'] ? 'فائض' : 'عجز';
                            $diffColor = $preview['is_surplus'] ? '#16a34a' : '#dc2626';
                            $balanceColor = $preview['projected_balance'] >= 0 ? '#16a34a' : '#dc2626';

                            $warning = '';
                            if ($preview['settle_count'] > 0) {
                                $warning = '
                                <div style="margin-top:12px;padding:8px 12px;background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;font-size:13px;color:#92400e;">
                                    ⚠ تمت تصفية هذا الشهر <strong>' . $preview['settle_count'] . '</strong> مرة سابقاً — ستُضاف تصفية جديدة
                                </div>';
                            }

                            return new \Illuminate\Support\HtmlString('
                                <div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:10px 14px;color:#6b7280;">أرباح الشهر</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;color:#16a34a;">' . $iqd($preview['monthly_profit']) . '</td>
                                        </tr>
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:10px 14px;color:#6b7280;">تاركت المستثمرين</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;color:#ea580c;">' . $iqd($preview['monthly_investor_target']) . '</td>
                                        </tr>
                                        <tr style="border-bottom:1px solid #f3f4f6;background:#f9fafb;">
                                            <td style="padding:10px 14px;font-weight:700;color:#374151;">الفرق (' . $type . ')</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;color:' . $diffColor . ';font-size:15px;">' . $iqd(abs($preview['difference'])) . '</td>
                                        </tr>
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:10px 14px;color:#6b7280;">رصيد القاصة الحالي</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;">' . $iqd($preview['current_balance']) . '</td>
                                        </tr>
                                        <tr style="background:#f0fdf4;">
                                            <td style="padding:10px 14px;font-weight:700;color:#374151;">رصيد القاصة بعد التصفية</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;color:' . $balanceColor . ';font-size:15px;">' . $iqd($preview['projected_balance']) . '</td>
                                        </tr>
                                    </table>
                                </div>
                                ' . $warning . '
                            ');
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

            Actions\Action::make('settle_all')
                ->label('تصفية كاملة')
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->modalHeading('تصفية حسابات كاملة')
                ->modalWidth('lg')
                ->modalDescription('تصفية جميع الأشهر - يحسب الفرق بين المتوقع والمصفى سابقاً')
                ->form([
                    Forms\Components\Placeholder::make('settle_all_preview')
                        ->label('')
                        ->content(function (): \Illuminate\Support\HtmlString {
                            $preview = app(FinanceService::class)->settleAllPreview();
                            $iqd = fn (int $v) => number_format($v, 0, '.', ',') . ' د.ع';

                            if ($preview['pending_count'] === 0) {
                                return new \Illuminate\Support\HtmlString('
                                    <div style="text-align:center;padding:20px;color:#16a34a;font-weight:700;">
                                        ✓ جميع الأشهر مصفاة بالكامل - لا توجد فروقات
                                    </div>
                                ');
                            }

                            // فقط الأشهر الي عندها فرق
                            $pendingDetails = collect($preview['details'])->filter(fn ($d) => $d['remaining'] != 0);

                            $rows = '';
                            foreach ($pendingDetails as $d) {
                                $remainColor = $d['remaining'] >= 0 ? '#16a34a' : '#dc2626';
                                $remainType = $d['remaining'] >= 0 ? 'فائض' : 'عجز';
                                $monthName = \Carbon\Carbon::create()->month($d['month'])->translatedFormat('F');
                                $settledNote = $d['already_settled'] != 0
                                    ? '<br><span style="font-size:11px;color:#6b7280;">مصفى: ' . $iqd($d['already_settled']) . '</span>'
                                    : '';
                                $rows .= "
                                    <tr style='border-bottom:1px solid #f3f4f6;'>
                                        <td style='padding:8px 10px;font-weight:600;'>{$monthName} {$d['year']}</td>
                                        <td style='padding:8px 10px;color:#16a34a;'>{$iqd($d['profit'])}</td>
                                        <td style='padding:8px 10px;color:#ea580c;'>{$iqd($d['investor_target'])}</td>
                                        <td style='padding:8px 10px;color:#6b7280;'>{$iqd($d['expected'])}{$settledNote}</td>
                                        <td style='padding:8px 10px;font-weight:700;color:{$remainColor};'>{$iqd(abs($d['remaining']))} {$remainType}</td>
                                    </tr>";
                            }

                            $totalColor = $preview['total_remaining'] >= 0 ? '#16a34a' : '#dc2626';
                            $balanceColor = $preview['projected_balance'] >= 0 ? '#16a34a' : '#dc2626';

                            return new \Illuminate\Support\HtmlString('
                                <div style="margin-bottom:12px;padding:8px 14px;background:#dbeafe;border-radius:8px;font-size:13px;color:#1e40af;font-weight:600;">
                                    ' . $preview['pending_count'] . ' شهر يحتاج تصفية من أصل ' . $preview['total_months'] . ' شهر
                                </div>
                                <div style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;overflow-x:auto;">
                                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                        <thead>
                                            <tr style="background:#f9fafb;border-bottom:2px solid #e5e7eb;">
                                                <th style="padding:8px 10px;text-align:right;color:#6b7280;font-weight:600;">الشهر</th>
                                                <th style="padding:8px 10px;text-align:right;color:#6b7280;font-weight:600;">الأرباح</th>
                                                <th style="padding:8px 10px;text-align:right;color:#6b7280;font-weight:600;">التاركت</th>
                                                <th style="padding:8px 10px;text-align:right;color:#6b7280;font-weight:600;">المتوقع</th>
                                                <th style="padding:8px 10px;text-align:right;color:#6b7280;font-weight:600;">المتبقي</th>
                                            </tr>
                                        </thead>
                                        <tbody>' . $rows . '</tbody>
                                        <tfoot>
                                            <tr style="background:#f0fdf4;border-top:2px solid #e5e7eb;">
                                                <td colspan="4" style="padding:10px;font-weight:700;">المجموع المتبقي</td>
                                                <td style="padding:10px;font-weight:700;color:' . $totalColor . ';font-size:13px;">' . $iqd(abs($preview['total_remaining'])) . '</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div style="margin-top:12px;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:10px 14px;color:#6b7280;">رصيد القاصة الحالي</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;">' . $iqd($preview['current_balance']) . '</td>
                                        </tr>
                                        <tr style="background:#f0fdf4;">
                                            <td style="padding:10px 14px;font-weight:700;">رصيد القاصة بعد التصفية</td>
                                            <td style="padding:10px 14px;text-align:left;font-weight:700;color:' . $balanceColor . ';font-size:16px;">' . $iqd($preview['projected_balance']) . '</td>
                                        </tr>
                                    </table>
                                </div>
                            ');
                        })
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('تصفية الكل')
                ->action(function (): void {
                    $result = app(FinanceService::class)->settleAll();

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
