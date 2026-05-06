<?php

namespace App\Filament\Resources\ScheduledNotificationResource\Pages;

use App\Filament\Resources\ScheduledNotificationResource;
use App\Models\ScheduledNotification;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListScheduledNotifications extends ListRecords
{
    protected static string $resource = ScheduledNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('plan_today')
                ->label('تخطيط اشعارات اليوم')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('تخطيط اشعارات اليوم')
                ->modalDescription('سيتم احتساب الزبائن المستحقين لإشعار اليوم وإدراجهم بانتظار الإرسال. الاشعارات الباقية من أمس ستُلغى.')
                ->modalSubmitActionLabel('تشغيل التخطيط')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('manage_scheduled_notifications'))
                ->action(function (): void {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('whatsapp:plan-day');
                    $output = trim(\Illuminate\Support\Facades\Artisan::output());

                    if ($exitCode !== 0) {
                        Notification::make()
                            ->title('فشل تخطيط الاشعارات')
                            ->body($output ?: 'حدث خطأ غير معروف.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('تم تخطيط اشعارات اليوم')
                        ->body('سيتم إرسالها تدريجياً عبر المهمة المجدولة كل دقيقة.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('dispatch_now')
                ->label('إرسال اشعار الآن')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('إرسال اشعار واحد فوراً')
                ->modalDescription('سيتم إرسال أول اشعار معلق لليوم بشكل فوري دون انتظار الكرون.')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('manage_scheduled_notifications'))
                ->action(function (): void {
                    $exitCode = \Illuminate\Support\Facades\Artisan::call('whatsapp:dispatch-next');
                    $output = trim(\Illuminate\Support\Facades\Artisan::output());

                    Notification::make()
                        ->title($exitCode === 0 ? 'تم' : 'فشل')
                        ->body($output ?: '—')
                        ->{$exitCode === 0 ? 'success' : 'danger'}()
                        ->send();
                }),

            Actions\Action::make('delete_all')
                ->label('حذف كل الاشعارات')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('حذف كل الاشعارات')
                ->modalDescription('سيتم حذف كل سجلات الاشعارات (المعلقة والمرسلة والفاشلة). هذا الإجراء لا يمكن التراجع عنه.')
                ->modalSubmitActionLabel('نعم، احذف الكل')
                ->visible(fn (): bool => auth()->user()?->hasPermissionTo('manage_scheduled_notifications'))
                ->action(function (): void {
                    $count = ScheduledNotification::query()->count();
                    ScheduledNotification::query()->delete();

                    Notification::make()
                        ->title("تم حذف {$count} اشعار")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        return [
            'today' => Tab::make('اليوم')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_for', $today))
                ->badge(fn () => ScheduledNotification::query()
                    ->whereDate('scheduled_for', $today)
                    ->where('status', ScheduledNotification::STATUS_PENDING)
                    ->count() ?: null)
                ->badgeColor('warning'),

            'tomorrow' => Tab::make('باجر')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_for', $tomorrow))
                ->badge(fn () => ScheduledNotification::query()
                    ->whereDate('scheduled_for', $tomorrow)
                    ->count() ?: null),

            'upcoming' => Tab::make('القادمة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_for', '>', $today))
                ->badge(fn () => ScheduledNotification::query()
                    ->whereDate('scheduled_for', '>', $today)
                    ->count() ?: null),

            'sent' => Tab::make('المرسلة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ScheduledNotification::STATUS_SENT)),

            'failed' => Tab::make('الفاشلة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    ScheduledNotification::STATUS_FAILED,
                    ScheduledNotification::STATUS_EXPIRED,
                ]))
                ->badge(fn () => ScheduledNotification::query()
                    ->whereIn('status', [ScheduledNotification::STATUS_FAILED, ScheduledNotification::STATUS_EXPIRED])
                    ->whereDate('scheduled_for', '>=', now()->subDays(7)->toDateString())
                    ->count() ?: null)
                ->badgeColor('danger'),

            'all' => Tab::make('الكل'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'today';
    }
}
