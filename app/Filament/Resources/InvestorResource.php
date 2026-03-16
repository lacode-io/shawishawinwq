<?php

namespace App\Filament\Resources;

use App\Enums\InvestorStatus;
use App\Filament\Resources\InvestorResource\Pages;
use App\Filament\Resources\InvestorResource\RelationManagers\PayoutsRelationManager;
use App\Filament\Resources\InvestorResource\Widgets\InvestorStatsOverview;
use App\Jobs\SendInvestorSummary;
use App\Models\Investor;
use App\Models\InvestorPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class InvestorResource extends Resource
{
    protected static ?string $model = Investor::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return __('Investor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Investors');
    }

    public static function getNavigationLabel(): string
    {
        return __('Investors');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة المالية';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', InvestorStatus::Active)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── معلومات المستثمر ──
                Forms\Components\Section::make(__('Investor'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('Full Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                // ── تفاصيل الاستثمار ──
                Forms\Components\Section::make(__('Amount Invested'))
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('amount_invested')
                            ->label(__('Amount Invested'))
                            ->required()
                            ->numeric()
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\')'))
                            ->stripCharacters(['.', ','])
                            ->suffix(__('IQD'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),

                        Forms\Components\TextInput::make('profit_percent_total')
                            ->label(__('Profit Percent'))
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),

                        Forms\Components\TextInput::make('investment_months')
                            ->label(__('Investment Months'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),

                        Forms\Components\TextInput::make('monthly_target_amount')
                            ->label(__('Monthly Target'))
                            ->numeric()
                            ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\')'))
                            ->stripCharacters(['.', ','])
                            ->suffix(__('IQD'))
                            ->helperText('يتم حسابه تلقائياً (الربح فقط) ÷ الأشهر'),
                    ])->columns(2),

                // ── الجدول الزمني ──
                Forms\Components\Section::make(__('Start Date'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),

                        Forms\Components\DatePicker::make('payout_due_date')
                            ->label(__('Payout Due Date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),

                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options(collect(InvestorStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->default(InvestorStatus::Active->value)
                            ->required(),
                    ])->columns(3),

                // ── ملاحظات ──
                Forms\Components\Section::make(__('Notes'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Full Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Investor $record): string => $record->phone ?? ''),

                Tables\Columns\TextColumn::make('amount_invested')
                    ->label(__('Amount Invested'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('profit_percent_total')
                    ->label(__('Profit Percent'))
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_target_amount')
                    ->label(__('Monthly Target'))
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::iqd($state) : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_paid_out')
                    ->label(__('Total Paid Out'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label(__('Remaining Balance'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->color(fn (Investor $record): string => $record->remaining_balance > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('التقدم')
                    ->suffix('%')
                    ->color(fn (Investor $record): string => match (true) {
                        $record->progress_percent >= 100 => 'success',
                        $record->is_behind_target => 'danger',
                        default => 'warning',
                    })
                    ->icon(fn (Investor $record): ?string => $record->is_behind_target ? 'heroicon-o-exclamation-triangle' : null),

                Tables\Columns\TextColumn::make('payout_due_date')
                    ->label(__('Payout Due Date'))
                    ->date('Y/m/d')
                    ->sortable()
                    ->color(fn (Investor $record): string => $record->payout_due_date->isPast() && $record->status === InvestorStatus::Active ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (InvestorStatus $state): string => $state->label())
                    ->color(fn (InvestorStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(InvestorStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->multiple(),

                Tables\Filters\Filter::make('behind_target')
                    ->label('متأخرين عن الهدف')
                    ->toggle()
                    ->query(fn ($query) => $query->behindTarget()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('add_payout')
                        ->label(__('Payout').' - '.__('New'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->modalHeading(__('Payout').' - '.__('New'))
                        ->modalWidth('md')
                        ->form([
                            Forms\Components\DatePicker::make('paid_at')
                                ->label(__('Paid At'))
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('Y/m/d'),

                            Forms\Components\TextInput::make('amount')
                                ->label(__('Amount'))
                                ->required()
                                ->numeric()
                                ->mask(\Filament\Support\RawJs::make('$money($input, \',\', \'.\')'))
                                ->stripCharacters(['.', ','])
                                ->suffix(__('IQD'))
                                ->default(fn (Investor $record): ?int => $record->monthly_target_amount),

                            Forms\Components\Textarea::make('notes')
                                ->label(__('Notes'))
                                ->rows(2),
                        ])
                        ->action(function (Investor $record, array $data): void {
                            if (! auth()->user()->can('create', InvestorPayout::class)) {
                                Notification::make()->title(__('Action not allowed'))->body(__('You do not have permission to perform this action.'))->danger()->send();

                                return;
                            }

                            $record->payouts()->create([
                                'paid_at' => $data['paid_at'],
                                'amount' => $data['amount'],
                                'notes' => $data['notes'],
                            ]);

                            Notification::make()
                                ->title('تم تسجيل الدفعة بنجاح')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Investor $record): bool => $record->status === InvestorStatus::Active
                            && auth()->user()->can('create', InvestorPayout::class)),

                    Tables\Actions\Action::make('mark_completed')
                        ->label('مكتمل')
                        ->icon('heroicon-o-check-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد إتمام الاستثمار')
                        ->modalDescription(fn (Investor $record): string => "هل أنت متأكد من إتمام استثمار {$record->full_name}؟")
                        ->action(function (Investor $record): void {
                            if (! auth()->user()->hasPermissionTo('mark_completed')) {
                                Notification::make()->title(__('Action not allowed'))->body(__('You do not have permission to perform this action.'))->danger()->send();

                                return;
                            }

                            $record->update(['status' => InvestorStatus::Completed]);

                            Notification::make()
                                ->title('تم وضع علامة مكتمل')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Investor $record): bool => $record->status === InvestorStatus::Active
                            && auth()->user()->hasPermissionTo('mark_completed')),

                    Tables\Actions\Action::make('print_statement')
                        ->label('كشف حساب')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn (Investor $record): string => route('investors.statement', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (): bool => auth()->user()->hasPermissionTo('export_pdf')),

                    Tables\Actions\Action::make('whatsapp')
                        ->label('واتساب')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('إرسال ملخص واتساب')
                        ->modalDescription(fn (Investor $record): string => "سيتم إرسال ملخص الاستثمار إلى {$record->full_name} على الرقم {$record->phone}")
                        ->action(function (Investor $record): void {
                            SendInvestorSummary::dispatch($record);

                            Notification::make()
                                ->title('تم إرسال الملخص')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Investor $record): bool => filled($record->phone)
                            && auth()->user()->hasPermissionTo('update_investors')),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Investor'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label(__('Full Name')),
                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('Phone'))
                            ->placeholder('-'),
                    ])->columns(2),

                Infolists\Components\Section::make(__('Amount Invested'))
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount_invested')
                            ->label(__('Amount Invested'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state)),
                        Infolists\Components\TextEntry::make('profit_percent_total')
                            ->label(__('Profit Percent'))
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('total_profit_amount')
                            ->label('مبلغ الربح')
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color('success'),
                        Infolists\Components\TextEntry::make('total_due')
                            ->label(__('Total Due'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->weight('bold'),
                    ])->columns(4),

                Infolists\Components\Section::make('تقدم السداد')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Infolists\Components\TextEntry::make('monthly_target_amount')
                            ->label(__('Monthly Target'))
                            ->formatStateUsing(fn (?int $state): string => $state ? Number::iqd($state) : '-'),
                        Infolists\Components\TextEntry::make('elapsed_months')
                            ->label('الأشهر المنقضية')
                            ->formatStateUsing(fn (int $state, Investor $record): string => "{$state} / {$record->investment_months} شهر"),
                        Infolists\Components\TextEntry::make('expected_payout_so_far')
                            ->label('المتوقع دفعه حتى الآن')
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('total_paid_out')
                            ->label(__('Total Paid Out'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color('success'),
                        Infolists\Components\TextEntry::make('remaining_balance')
                            ->label(__('Remaining Balance'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color(fn (Investor $record): string => $record->remaining_balance > 0 ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('target_gap')
                            ->label('الفجوة عن الهدف')
                            ->formatStateUsing(fn (int $state): string => $state > 0 ? Number::iqd($state) : 'لا يوجد تأخر')
                            ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                            ->icon(fn (int $state): ?string => $state > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),
                        Infolists\Components\TextEntry::make('progress_percent')
                            ->label('نسبة التقدم')
                            ->suffix('%')
                            ->color(fn (Investor $record): string => match (true) {
                                $record->progress_percent >= 100 => 'success',
                                $record->is_behind_target => 'danger',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('paid_payouts_count')
                            ->label('عدد الدفعات')
                            ->formatStateUsing(fn (int $state): string => "{$state} دفعة"),
                    ])->columns(4),

                Infolists\Components\Section::make(__('Start Date'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Infolists\Components\TextEntry::make('start_date')
                            ->label(__('Start Date'))
                            ->date('Y/m/d'),
                        Infolists\Components\TextEntry::make('payout_due_date')
                            ->label(__('Payout Due Date'))
                            ->date('Y/m/d')
                            ->color(fn (Investor $record): string => $record->payout_due_date->isPast() && $record->status === InvestorStatus::Active ? 'danger' : 'gray'),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (InvestorStatus $state): string => $state->label())
                            ->color(fn (InvestorStatus $state): string => $state->color()),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Notes'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])->collapsible()
                    ->collapsed(fn (Investor $record): bool => blank($record->notes)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayoutsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            InvestorStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestors::route('/'),
            'create' => Pages\CreateInvestor::route('/create'),
            'view' => Pages\ViewInvestor::route('/{record}'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'phone'];
    }

    protected static function recalculate(Forms\Get $get, Forms\Set $set): void
    {
        $amount = (int) str_replace(['.', ','], '', (string) $get('amount_invested'));
        $percent = (float) $get('profit_percent_total');
        $months = (int) $get('investment_months');

        if ($amount > 0 && $percent > 0 && $months > 0) {
            $totalProfit = (int) round($amount * ($percent / 100));
            $set('monthly_target_amount', (int) ceil($totalProfit / $months));
        }
    }
}
