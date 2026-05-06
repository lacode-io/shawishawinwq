<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledNotificationResource\Pages;
use App\Models\ScheduledNotification;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduledNotificationResource extends Resource
{
    protected static ?string $model = ScheduledNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'message_type';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermissionTo('view_scheduled_notifications');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermissionTo('manage_scheduled_notifications');
    }

    public static function getModelLabel(): string
    {
        return 'اشعار';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الاشعارات';
    }

    public static function getNavigationLabel(): string
    {
        return 'الاشعارات';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الاشعارات والواتساب';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()
            ->where('status', ScheduledNotification::STATUS_PENDING)
            ->whereDate('scheduled_for', now()->toDateString())
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->label('اليوم المحدد')
                    ->date('Y/m/d')
                    ->sortable()
                    ->description(fn (ScheduledNotification $r): string => $r->scheduled_for->isToday()
                        ? 'اليوم'
                        : ($r->scheduled_for->isTomorrow()
                            ? 'باجر'
                            : $r->scheduled_for->translatedFormat('l'))),

                Tables\Columns\TextColumn::make('notifiable_id')
                    ->label('الزبون')
                    ->placeholder('-')
                    ->getStateUsing(fn (ScheduledNotification $r): ?string => $r->notifiable?->full_name)
                    ->description(fn (ScheduledNotification $r): ?string => $r->notifiable?->phone),

                Tables\Columns\TextColumn::make('to_phone')
                    ->label('رقم الإرسال')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('message_type')
                    ->label('نوع الاشعار')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ScheduledNotification::messageTypeLabel($state))
                    ->color(fn (string $state): string => match ($state) {
                        ScheduledNotification::TYPE_DUE_TOMORROW => 'warning',
                        ScheduledNotification::TYPE_DUE_TODAY => 'primary',
                        ScheduledNotification::TYPE_OVERDUE => 'danger',
                        ScheduledNotification::TYPE_ADMIN_ALERT => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ScheduledNotification::statusLabel($state))
                    ->color(fn (string $state): string => ScheduledNotification::statusColor($state)),

                Tables\Columns\TextColumn::make('attempts')
                    ->label('محاولات')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('وقت الإرسال')
                    ->dateTime('Y/m/d h:i A')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('error')
                    ->label('سبب الفشل')
                    ->wrap()
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('أُنشئ')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_for', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        ScheduledNotification::STATUS_PENDING => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_PENDING),
                        ScheduledNotification::STATUS_SENDING => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_SENDING),
                        ScheduledNotification::STATUS_SENT => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_SENT),
                        ScheduledNotification::STATUS_FAILED => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_FAILED),
                        ScheduledNotification::STATUS_EXPIRED => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_EXPIRED),
                        ScheduledNotification::STATUS_SKIPPED => ScheduledNotification::statusLabel(ScheduledNotification::STATUS_SKIPPED),
                    ]),

                Tables\Filters\SelectFilter::make('message_type')
                    ->label('نوع الاشعار')
                    ->options([
                        ScheduledNotification::TYPE_DUE_TOMORROW => ScheduledNotification::messageTypeLabel(ScheduledNotification::TYPE_DUE_TOMORROW),
                        ScheduledNotification::TYPE_DUE_TODAY => ScheduledNotification::messageTypeLabel(ScheduledNotification::TYPE_DUE_TODAY),
                        ScheduledNotification::TYPE_OVERDUE => ScheduledNotification::messageTypeLabel(ScheduledNotification::TYPE_OVERDUE),
                        ScheduledNotification::TYPE_ADMIN_ALERT => ScheduledNotification::messageTypeLabel(ScheduledNotification::TYPE_ADMIN_ALERT),
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('اليوم فقط')
                    ->toggle()
                    ->query(fn (Builder $q): Builder => $q->whereDate('scheduled_for', now()->toDateString())),

                Tables\Filters\Filter::make('upcoming')
                    ->label('من اليوم وللأمام')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $q): Builder => $q->whereDate('scheduled_for', '>=', now()->toDateString())),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ')
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $d) => $q->whereDate('scheduled_for', '>=', $d))
                            ->when($data['until'], fn (Builder $q, $d) => $q->whereDate('scheduled_for', '<=', $d));
                    })
                    ->columns(2),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('cancel')
                        ->label('إلغاء')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (ScheduledNotification $r): bool => $r->status === ScheduledNotification::STATUS_PENDING
                            && auth()->user()?->hasPermissionTo('manage_scheduled_notifications'))
                        ->action(function (ScheduledNotification $record): void {
                            $record->update([
                                'status' => ScheduledNotification::STATUS_SKIPPED,
                                'error' => 'تم الإلغاء يدوياً بواسطة '.auth()->user()->name,
                            ]);

                            Notification::make()->title('تم إلغاء الاشعار')->success()->send();
                        }),

                    Tables\Actions\Action::make('retry')
                        ->label('إعادة المحاولة')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (ScheduledNotification $r): bool => in_array($r->status, [
                            ScheduledNotification::STATUS_FAILED,
                            ScheduledNotification::STATUS_SKIPPED,
                            ScheduledNotification::STATUS_EXPIRED,
                        ], true) && auth()->user()?->hasPermissionTo('manage_scheduled_notifications'))
                        ->action(function (ScheduledNotification $record): void {
                            $record->update([
                                'status' => ScheduledNotification::STATUS_PENDING,
                                'scheduled_for' => now()->toDateString(),
                                'error' => null,
                                'sent_at' => null,
                            ]);

                            Notification::make()->title('سيتم إعادة الإرسال خلال الدقائق القادمة')->success()->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasPermissionTo('manage_scheduled_notifications')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('cancel_pending')
                        ->label('إلغاء المعلقة')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === ScheduledNotification::STATUS_PENDING) {
                                    $record->update([
                                        'status' => ScheduledNotification::STATUS_SKIPPED,
                                        'error' => 'إلغاء جماعي بواسطة '.auth()->user()->name,
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()->title("تم إلغاء {$count} اشعار")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasPermissionTo('manage_scheduled_notifications')),
                ]),
            ])
            ->emptyStateHeading('لا توجد اشعارات مجدولة')
            ->emptyStateDescription('عند تشغيل التخطيط اليومي ستظهر هنا الاشعارات المعلقة لليوم.')
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduledNotifications::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['notifiable']);
    }
}
