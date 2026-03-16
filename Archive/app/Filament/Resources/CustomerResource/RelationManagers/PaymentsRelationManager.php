<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\PaymentMethod;
use App\Models\CustomerPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('Payments');
    }

    protected static function getModelLabel(): ?string
    {
        return __('Payment');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->default(fn (): int => $this->ownerRecord->monthly_installment_amount),

                Forms\Components\Select::make('payment_method')
                    ->label(__('Payment Method'))
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                    ->default(PaymentMethod::Cash->value)
                    ->required(),

                Forms\Components\Hidden::make('received_by')
                    ->default(fn () => auth()->id()),

                Forms\Components\Textarea::make('meta.notes')
                    ->label(__('Notes'))
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Paid At'))
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label())
                    ->color(fn (PaymentMethod $state): string => $state->color()),

                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label(__('Received By')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['received_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('حذف التسديد')
                    ->form([
                        Forms\Components\Textarea::make('deletion_reason')
                            ->label(__('Deletion Reason'))
                            ->required()
                            ->rows(2),
                    ])
                    ->before(function (array $data, CustomerPayment $record): void {
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'reason' => $data['deletion_reason'],
                                'deleted_payment' => [
                                    'amount' => $record->amount,
                                    'paid_at' => $record->paid_at->toDateString(),
                                    'customer_id' => $record->customer_id,
                                ],
                            ])
                            ->log('تم حذف تسديد مع سبب');
                    }),
            ])
            ->bulkActions([]);
    }
}
