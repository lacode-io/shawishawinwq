<?php

namespace App\Filament\Resources\AppNoteResource\Pages;

use App\Filament\Resources\AppNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppNote extends CreateRecord
{
    protected static string $resource = AppNoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
