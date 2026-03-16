<?php

namespace App\Filament\Resources\AppNoteResource\Pages;

use App\Filament\Resources\AppNoteResource;
use App\Models\AppNote;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAppNote extends ViewRecord
{
    protected static string $resource = AppNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_pin')
                ->label(fn (): string => $this->record->is_pinned ? 'إلغاء التثبيت' : 'تثبيت')
                ->icon(fn (): string => $this->record->is_pinned ? 'heroicon-o-star' : 'heroicon-s-star')
                ->color('warning')
                ->action(function (): void {
                    $this->record->update([
                        'pinned_at' => $this->record->is_pinned ? null : now(),
                    ]);

                    Notification::make()
                        ->title($this->record->is_pinned ? 'تم التثبيت' : 'تم إلغاء التثبيت')
                        ->success()
                        ->send();

                    $this->refreshFormData(['pinned_at']);
                })
                ->visible(fn (): bool => auth()->user()->hasPermissionTo('update_app_notes')),

            Actions\Action::make('toggle_archive')
                ->label(fn (): string => $this->record->is_archived ? 'إلغاء الأرشفة' : 'أرشفة')
                ->icon(fn (): string => $this->record->is_archived ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'archived_at' => $this->record->is_archived ? null : now(),
                    ]);

                    Notification::make()
                        ->title($this->record->is_archived ? 'تمت الأرشفة' : 'تم إلغاء الأرشفة')
                        ->success()
                        ->send();

                    $this->refreshFormData(['archived_at']);
                })
                ->visible(fn (): bool => auth()->user()->hasPermissionTo('update_app_notes')),

            Actions\EditAction::make(),

            Actions\DeleteAction::make(),
        ];
    }
}
