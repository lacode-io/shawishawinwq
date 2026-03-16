<?php

namespace App\Filament\Resources\AppNoteResource\Pages;

use App\Filament\Resources\AppNoteResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAppNotes extends ListRecords
{
    protected static string $resource = AppNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('archived_at'))
                ->icon('heroicon-o-rectangle-stack'),

            'notes' => Tab::make('ملاحظات')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'note')->whereNull('archived_at'))
                ->icon('heroicon-o-document-text'),

            'inventory' => Tab::make('الجرد')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'inventory')->whereNull('archived_at'))
                ->icon('heroicon-o-clipboard-document-list'),

            'pinned' => Tab::make('المثبتة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('pinned_at')->whereNull('archived_at'))
                ->icon('heroicon-o-star'),

            'archived' => Tab::make('الأرشيف')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('archived_at'))
                ->icon('heroicon-o-archive-box'),
        ];
    }
}
