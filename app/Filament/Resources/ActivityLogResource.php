<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?int $navigationSort = 99;

    public static function getModelLabel(): string
    {
        return __('Activity Log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity Log');
    }

    public static function getNavigationLabel(): string
    {
        return __('Activity Log');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'التقارير';
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('view_activity_log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label(__('Log Name'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->limit(40),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label(__('Subject'))
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label(__('Causer'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label(__('Event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Logged At'))
                    ->dateTime('Y/m/d h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label(__('Log Name'))
                    ->options(fn (): array => Activity::query()
                        ->distinct()
                        ->pluck('log_name', 'log_name')
                        ->toArray()),

                Tables\Filters\SelectFilter::make('event')
                    ->label(__('Event'))
                    ->options([
                        'created' => 'إنشاء',
                        'updated' => 'تعديل',
                        'deleted' => 'حذف',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('log_name')
                            ->label(__('Log Name')),

                        Infolists\Components\TextEntry::make('description')
                            ->label(__('Description')),

                        Infolists\Components\TextEntry::make('subject_type')
                            ->label(__('Subject'))
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),

                        Infolists\Components\TextEntry::make('causer.name')
                            ->label(__('Causer')),

                        Infolists\Components\TextEntry::make('event')
                            ->label(__('Event'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Logged At'))
                            ->dateTime('Y/m/d h:i A'),
                    ])->columns(2),

                Infolists\Components\Section::make(__('Properties'))
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('properties.attributes')
                            ->label(__('New'))
                            ->columnSpanFull(),

                        Infolists\Components\KeyValueEntry::make('properties.old')
                            ->label(__('Old'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
