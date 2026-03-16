<?php

namespace App\Filament\Resources;

use App\Enums\CustomerStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\CustomerResource\Widgets\CustomerStatsOverview;
use App\Jobs\SendPaymentDueReminder;
use App\Jobs\SendPaymentReceivedConfirmation;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getModelLabel(): string
    {
        return __('Customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationLabel(): string
    {
        return __('Customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة المالية';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', CustomerStatus::Active)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── معلومات الزبون ──
                Forms\Components\Section::make(__('Customer'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('Full Name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->required()
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address')
                            ->label(__('Address'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),

                // ── معلومات الكفيل ──
                Forms\Components\Section::make(__('Guarantor Name'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('guarantor_name')
                            ->label(__('Guarantor Name'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('guarantor_phone')
                            ->label(__('Guarantor Phone'))
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2)
                    ->collapsible(),

                // ── تفاصيل المنتج والسعر ──
                Forms\Components\Section::make(__('Product Type'))
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        Forms\Components\TextInput::make('product_type')
                            ->label(__('Product Type'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('product_cost_price')
                            ->label(__('Cost Price'))
                            ->numeric()
                            ->suffix(__('IQD')),

                        Forms\Components\TextInput::make('product_sale_total')
                            ->label(__('Sale Total'))
                            ->required()
                            ->numeric()
                            ->suffix(__('IQD'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                self::calculateInstallment($get, $set);
                            }),
                    ])->columns(3),

                // ── جدول الأقساط ──
                Forms\Components\Section::make(__('Monthly Installment'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label(__('Delivery Date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),

                        Forms\Components\TextInput::make('duration_months')
                            ->label(__('Duration (Months)'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(60)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                self::calculateInstallment($get, $set);
                            }),

                        Forms\Components\TextInput::make('monthly_installment_amount')
                            ->label(__('Monthly Installment'))
                            ->required()
                            ->numeric()
                            ->suffix(__('IQD')),

                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options(collect(CustomerStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->default(CustomerStatus::Active->value)
                            ->required(),
                    ])->columns(2),

                // ── معلومات البطاقة ──
                Forms\Components\Section::make('معلومات البطاقة')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->label('رقم البطاقة')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('card_code')
                            ->label('رمز البطاقة')
                            ->maxLength(255),
                    ])->columns(2)
                    ->collapsible(),

                // ── ملاحظات ──
                Forms\Components\Section::make(__('Notes'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('ملاحظات داخلية')
                            ->helperText('تظهر في وصل الزبون')
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
                    ->description(fn (Customer $record): string => $record->phone),

                Tables\Columns\TextColumn::make('product_type')
                    ->label(__('Product Type'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product_sale_total')
                    ->label(__('Sale Total'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('monthly_installment_amount')
                    ->label(__('Monthly Installment'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label(__('Total Paid'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label(__('Remaining Balance'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->color(fn (Customer $record): string => $record->remaining_balance > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label(__('Payout Due Date'))
                    ->date('Y/m/d')
                    ->color(fn (Customer $record): string => $record->is_late ? 'danger' : 'gray')
                    ->icon(fn (Customer $record): ?string => $record->is_late ? 'heroicon-o-exclamation-triangle' : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (CustomerStatus $state): string => $state->label())
                    ->color(fn (CustomerStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('delivery_date')
                    ->label(__('Delivery Date'))
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
                    ->options(collect(CustomerStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->multiple(),

                Tables\Filters\Filter::make('late')
                    ->label('متأخرين')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->where('status', CustomerStatus::Active)
                            ->whereRaw('DATE_ADD(delivery_date, INTERVAL (SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) + 1 MONTH) < NOW()');
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label(__('Status')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('add_payment')
                        ->label(__('Payment'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->modalHeading(__('Payment').' - '.__('New'))
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
                                ->suffix(__('IQD'))
                                ->default(fn (Customer $record): int => $record->monthly_installment_amount),

                            Forms\Components\Select::make('payment_method')
                                ->label(__('Payment Method'))
                                ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                                ->default(PaymentMethod::Cash->value)
                                ->required(),

                            Forms\Components\Textarea::make('notes')
                                ->label(__('Notes'))
                                ->rows(2),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            if (! auth()->user()->can('create', CustomerPayment::class)) {
                                Notification::make()->title(__('Action not allowed'))->body(__('You do not have permission to perform this action.'))->danger()->send();

                                return;
                            }

                            $record->payments()->create([
                                'paid_at' => $data['paid_at'],
                                'amount' => $data['amount'],
                                'payment_method' => $data['payment_method'],
                                'received_by' => auth()->id(),
                                'meta' => $data['notes'] ? ['notes' => $data['notes']] : null,
                            ]);

                            SendPaymentReceivedConfirmation::dispatch($record->fresh(), (int) $data['amount']);

                            Notification::make()
                                ->title('تم تسجيل التسديد بنجاح')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Customer $record): bool => $record->status === CustomerStatus::Active
                            && auth()->user()->can('create', CustomerPayment::class)),

                    Tables\Actions\Action::make('mark_completed')
                        ->label('مكتمل')
                        ->icon('heroicon-o-check-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد إتمام الأقساط')
                        ->modalDescription(fn (Customer $record): string => "هل أنت متأكد من إتمام أقساط {$record->full_name}؟")
                        ->action(function (Customer $record): void {
                            if (! auth()->user()->hasPermissionTo('mark_completed')) {
                                Notification::make()->title(__('Action not allowed'))->body(__('You do not have permission to perform this action.'))->danger()->send();

                                return;
                            }

                            $record->update(['status' => CustomerStatus::Completed]);

                            Notification::make()
                                ->title('تم وضع علامة مكتمل')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Customer $record): bool => $record->status === CustomerStatus::Active
                            && auth()->user()->hasPermissionTo('mark_completed')),

                    Tables\Actions\Action::make('print_invoice')
                        ->label(__('Receipt'))
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn (Customer $record): string => route('customers.invoice', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (): bool => auth()->user()->hasPermissionTo('export_pdf')),

                    Tables\Actions\Action::make('whatsapp_reminder')
                        ->label('تذكير واتساب')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('إرسال تذكير واتساب')
                        ->modalDescription(fn (Customer $record): string => "سيتم إرسال تذكير بموعد القسط إلى {$record->full_name} على الرقم {$record->phone}")
                        ->action(function (Customer $record): void {
                            SendPaymentDueReminder::dispatch($record);

                            Notification::make()
                                ->title('تم إرسال التذكير')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Customer $record): bool => $record->status === CustomerStatus::Active
                            && filled($record->phone)
                            && auth()->user()->hasPermissionTo('update_customers')),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('حذف الزبون')
                        ->form([
                            Forms\Components\Textarea::make('deletion_reason')
                                ->label(__('Deletion Reason'))
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function (Customer $record, array $data): void {
                            $record->update([
                                'deletion_reason' => $data['deletion_reason'],
                                'deletion_requested_by' => auth()->id(),
                                'deletion_approved_by' => auth()->id(),
                            ]);
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الزبون')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Customer'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label(__('Full Name')),
                        Infolists\Components\TextEntry::make('phone')
                            ->label(__('Phone')),
                        Infolists\Components\TextEntry::make('address')
                            ->label(__('Address')),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Guarantor Name'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('guarantor_name')
                            ->label(__('Guarantor Name')),
                        Infolists\Components\TextEntry::make('guarantor_phone')
                            ->label(__('Guarantor Phone')),
                    ])->columns(2),

                Infolists\Components\Section::make(__('Product Type'))
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        Infolists\Components\TextEntry::make('product_type')
                            ->label(__('Product Type')),
                        Infolists\Components\TextEntry::make('product_cost_price')
                            ->label(__('Cost Price'))
                            ->formatStateUsing(fn (?int $state): string => $state ? Number::iqd($state) : '-'),
                        Infolists\Components\TextEntry::make('product_sale_total')
                            ->label(__('Sale Total'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state)),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Monthly Installment'))
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Infolists\Components\TextEntry::make('delivery_date')
                            ->label(__('Delivery Date'))
                            ->date('Y/m/d'),
                        Infolists\Components\TextEntry::make('duration_months')
                            ->label(__('Duration (Months)'))
                            ->suffix(' شهر'),
                        Infolists\Components\TextEntry::make('monthly_installment_amount')
                            ->label(__('Monthly Installment'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state)),
                        Infolists\Components\TextEntry::make('total_paid')
                            ->label(__('Total Paid'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color('success'),
                        Infolists\Components\TextEntry::make('remaining_balance')
                            ->label(__('Remaining Balance'))
                            ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                            ->color(fn (Customer $record): string => $record->remaining_balance > 0 ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('paid_installments_count')
                            ->label('الأقساط المدفوعة')
                            ->formatStateUsing(fn (int $state, Customer $record): string => "{$state} / {$record->duration_months}"),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (CustomerStatus $state): string => $state->label())
                            ->color(fn (CustomerStatus $state): string => $state->color()),
                        Infolists\Components\TextEntry::make('next_due_date')
                            ->label(__('Payout Due Date'))
                            ->date('Y/m/d')
                            ->color(fn (Customer $record): string => $record->is_late ? 'danger' : 'gray')
                            ->placeholder('مكتمل'),
                    ])->columns(4),

                Infolists\Components\Section::make('معلومات البطاقة')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Infolists\Components\TextEntry::make('card_number')
                            ->label('رقم البطاقة')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('card_code')
                            ->label('رمز البطاقة')
                            ->placeholder('-'),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(fn (Customer $record): bool => blank($record->card_number) && blank($record->card_code)),

                Infolists\Components\Section::make(__('Notes'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('internal_notes')
                            ->label('ملاحظات داخلية')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])->collapsible()
                    ->collapsed(fn (Customer $record): bool => blank($record->notes) && blank($record->internal_notes)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CustomerStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'phone', 'guarantor_name', 'product_type'];
    }

    protected static function calculateInstallment(Forms\Get $get, Forms\Set $set): void
    {
        $total = (int) $get('product_sale_total');
        $months = (int) $get('duration_months');

        if ($total > 0 && $months > 0) {
            $set('monthly_installment_amount', (int) ceil($total / $months));
        }
    }
}
