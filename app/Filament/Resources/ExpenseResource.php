<?php

namespace App\Filament\Resources;

use App\Enums\ExpenseSubType;
use App\Enums\ExpenseType;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Widgets;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('Expense');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Expenses');
    }

    public static function getNavigationLabel(): string
    {
        return __('Expenses');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإدارة المالية';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Expense'))
                    ->icon('heroicon-o-receipt-percent')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('Expense Type'))
                            ->options(collect(ExpenseType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('custom_type_name')
                            ->label('اسم المصروف المخصص')
                            ->required()
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get): bool => $get('type') === ExpenseType::Custom->value),

                        Forms\Components\Select::make('sub_type')
                            ->label(__('Sub Type'))
                            ->options(collect(ExpenseSubType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                            ->visible(fn (Forms\Get $get): bool => $get('type') === ExpenseType::Personal->value),

                        Forms\Components\Placeholder::make('salary_info')
                            ->label('')
                            ->content('سيتم إضافة دفعتين تلقائياً: واحدة لحيدر وواحدة لذو الفقار بنفس المبلغ')
                            ->visible(fn (Forms\Get $get): bool => $get('type') === ExpenseType::Salary->value),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('Amount'))
                            ->required()
                            ->numeric()
                            ->suffix(__('IQD')),

                        Forms\Components\DatePicker::make('spent_at')
                            ->label(__('Spent At'))
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                    ])->columns(2),

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
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Expense Type'))
                    ->badge()
                    ->formatStateUsing(fn (ExpenseType $state, Expense $record): string => $state === ExpenseType::Custom && $record->custom_type_name
                        ? $record->custom_type_name
                        : $state->label())
                    ->color(fn (ExpenseType $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_type')
                    ->label(__('Sub Type'))
                    ->formatStateUsing(fn (?ExpenseSubType $state): string => $state?->label() ?? '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->formatStateUsing(fn (int $state): string => Number::iqd($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('spent_at')
                    ->label(__('Spent At'))
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('spent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Expense Type'))
                    ->options(collect(ExpenseType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('sub_type')
                    ->label(__('Sub Type'))
                    ->options(collect(ExpenseSubType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                    ->multiple(),

                Tables\Filters\Filter::make('this_month')
                    ->label('هذا الشهر')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereMonth('spent_at', now()->month)->whereYear('spent_at', now()->year)),

                Tables\Filters\Filter::make('last_2_months')
                    ->label('آخر شهرين')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('spent_at', '>=', now()->subMonths(2)->startOfMonth())),

                Tables\Filters\Filter::make('date_range')
                    ->label('تاريخ محدد')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من')
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                        Forms\Components\DatePicker::make('to')
                            ->label('إلى')
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->where('spent_at', '>=', $date))
                            ->when($data['to'], fn (Builder $q, $date) => $q->where('spent_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('من ' . \Carbon\Carbon::parse($data['from'])->format('Y/m/d'))
                                ->removeField('from');
                        }
                        if ($data['to'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('إلى ' . \Carbon\Carbon::parse($data['to'])->format('Y/m/d'))
                                ->removeField('to');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ExpenseStatsOverview::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
