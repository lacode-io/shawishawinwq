<?php

namespace App\Filament\Resources;

use App\Enums\NotePriority;
use App\Enums\NoteType;
use App\Filament\Resources\AppNoteResource\Pages;
use App\Models\AppNote;
use App\Models\Customer;
use App\Models\Investor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AppNoteResource extends Resource
{
    protected static ?string $model = AppNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'ملاحظة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الملاحظات';
    }

    public static function getNavigationLabel(): string
    {
        return 'الملاحظات والجرد';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الملاحظات والجرد';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('archived_at')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('محتوى الملاحظة')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('النوع')
                            ->options(collect(NoteType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                            ->default(NoteType::Note->value)
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options(collect(NotePriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                            ->default(NotePriority::Normal->value)
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body')
                            ->label('المحتوى')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('الوسوم')
                            ->separator(',')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('ربط بسجل')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Forms\Components\Select::make('related_customer_id')
                            ->label('زبون مرتبط')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('related_investor_id')
                            ->label('مستثمر مرتبط')
                            ->relationship('investor', 'full_name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (NoteType $state): string => $state->label())
                    ->color(fn (NoteType $state): string => $state->color()),

                Tables\Columns\IconColumn::make('pinned_at')
                    ->label('مثبت')
                    ->icon(fn ($state): string => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn ($state): string => $state ? 'warning' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AppNote $record): string => Str::limit($record->body, 60))
                    ->wrap(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('الأولوية')
                    ->badge()
                    ->formatStateUsing(fn (NotePriority $state): string => $state->label())
                    ->color(fn (NotePriority $state): string => $state->color()),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('الزبون')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('investor.full_name')
                    ->label('المستثمر')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('بواسطة')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تعديل')
                    ->dateTime('Y/m/d h:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y/m/d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('pinned_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options(collect(NoteType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options(collect(NotePriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])),

                Tables\Filters\Filter::make('pinned')
                    ->label('المثبتة فقط')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('pinned_at')),

                Tables\Filters\Filter::make('archived')
                    ->label('المؤرشفة')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('archived_at')),

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
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->columns(2),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('toggle_pin')
                        ->label(fn (AppNote $record): string => $record->is_pinned ? 'إلغاء التثبيت' : 'تثبيت')
                        ->icon(fn (AppNote $record): string => $record->is_pinned ? 'heroicon-o-star' : 'heroicon-s-star')
                        ->color('warning')
                        ->action(function (AppNote $record): void {
                            $record->update([
                                'pinned_at' => $record->is_pinned ? null : now(),
                            ]);

                            Notification::make()
                                ->title($record->is_pinned ? 'تم التثبيت' : 'تم إلغاء التثبيت')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()->hasPermissionTo('update_app_notes')),

                    Tables\Actions\Action::make('toggle_archive')
                        ->label(fn (AppNote $record): string => $record->is_archived ? 'إلغاء الأرشفة' : 'أرشفة')
                        ->icon(fn (AppNote $record): string => $record->is_archived ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-archive-box')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (AppNote $record): void {
                            $record->update([
                                'archived_at' => $record->is_archived ? null : now(),
                            ]);

                            Notification::make()
                                ->title($record->is_archived ? 'تمت الأرشفة' : 'تم إلغاء الأرشفة')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()->hasPermissionTo('update_app_notes')),

                    Tables\Actions\Action::make('duplicate')
                        ->label('نسخ')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function (AppNote $record): void {
                            $record->replicate(['pinned_at', 'archived_at'])->save();

                            Notification::make()
                                ->title('تم نسخ الملاحظة')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()->hasPermissionTo('create_app_notes')),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('archive')
                    ->label('أرشفة المحدد')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['archived_at' => now()]))
                    ->deselectRecordsAfterCompletion()
                    ->visible(fn (): bool => auth()->user()->hasPermissionTo('update_app_notes')),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('محتوى الملاحظة')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('النوع')
                            ->badge()
                            ->formatStateUsing(fn (NoteType $state): string => $state->label())
                            ->color(fn (NoteType $state): string => $state->color()),
                        Infolists\Components\TextEntry::make('priority')
                            ->label('الأولوية')
                            ->badge()
                            ->formatStateUsing(fn (NotePriority $state): string => $state->label())
                            ->color(fn (NotePriority $state): string => $state->color()),
                        Infolists\Components\TextEntry::make('title')
                            ->label('العنوان')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('body')
                            ->label('المحتوى')
                            ->columnSpanFull()
                            ->prose(),
                        Infolists\Components\TextEntry::make('tags')
                            ->label('الوسوم')
                            ->badge()
                            ->separator(',')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('الارتباطات')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.full_name')
                            ->label('الزبون')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('investor.full_name')
                            ->label('المستثمر')
                            ->placeholder('-'),
                    ])->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('معلومات إضافية')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('أنشئت بواسطة'),
                        Infolists\Components\TextEntry::make('updater.name')
                            ->label('آخر تعديل بواسطة'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y/m/d h:i A'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('آخر تعديل')
                            ->dateTime('Y/m/d h:i A'),
                        Infolists\Components\IconEntry::make('is_pinned')
                            ->label('مثبت')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_archived')
                            ->label('مؤرشف')
                            ->boolean(),
                    ])->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppNotes::route('/'),
            'create' => Pages\CreateAppNote::route('/create'),
            'view' => Pages\ViewAppNote::route('/{record}'),
            'edit' => Pages\EditAppNote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest('pinned_at')->latest('updated_at');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'body'];
    }
}
