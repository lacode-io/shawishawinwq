<?php

namespace App\Filament\Resources\AppNoteResource\Pages;

use App\Filament\Resources\AppNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppNote extends EditRecord
{
    protected static string $resource = AppNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
