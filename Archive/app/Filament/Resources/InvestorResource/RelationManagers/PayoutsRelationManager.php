<?php

namespace App\Filament\Resources\InvestorResource\RelationManagers;

use App\Models\InvestorPayout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('Payouts');
    }

    protected static function getModelLabel(): ?string
    {
        return __('Payout');
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
                    ->default(fn (): ?int => $this->ownerRecord->monthly_target_amount),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(2)
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('حذف دفعة المستثمر')
                    ->form([
                        Forms\Components\Textarea::make('deletion_reason')
                            ->label(__('Deletion Reason'))
                            ->required()
                            ->rows(2),
                    ])
                    ->before(function (array $data, InvestorPayout $record): void {
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->withProperties([
                                'reason' => $data['deletion_reason'],
                                'deleted_payout' => [
                                    'amount' => $record->amount,
                                    'paid_at' => $record->paid_at->toDateString(),
                                    'investor_id' => $record->investor_id,
                                ],
                            ])
                            ->log('تم حذف دفعة مستثمر مع سبب');
                    }),
            ])
            ->bulkActions([]);
    }
}
