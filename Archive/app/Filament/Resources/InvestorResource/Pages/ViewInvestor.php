<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Enums\InvestorStatus;
use App\Filament\Resources\InvestorResource;
use App\Jobs\SendInvestorSummary;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestor extends ViewRecord
{
    protected static string $resource = InvestorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_payout')
                ->label(__('Payout').' - '.__('New'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
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
                        ->default(fn (): ?int => $this->record->monthly_target_amount),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('Notes'))
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->payouts()->create([
                        'paid_at' => $data['paid_at'],
                        'amount' => $data['amount'],
                        'notes' => $data['notes'],
                    ]);

                    Notification::make()
                        ->title('تم تسجيل الدفعة بنجاح')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => $this->record->status === InvestorStatus::Active),

            Actions\Action::make('mark_completed')
                ->label('مكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('تأكيد إتمام الاستثمار')
                ->modalDescription(fn (): string => "هل أنت متأكد من إتمام استثمار {$this->record->full_name}؟")
                ->action(function (): void {
                    $this->record->update(['status' => InvestorStatus::Completed]);

                    Notification::make()
                        ->title('تم وضع علامة مكتمل')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                })
                ->visible(fn (): bool => $this->record->status === InvestorStatus::Active),

            Actions\Action::make('print_statement')
                ->label('كشف حساب')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('investors.statement', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('whatsapp')
                ->label('واتساب')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('إرسال ملخص واتساب')
                ->modalDescription(fn (): string => "سيتم إرسال ملخص الاستثمار إلى {$this->record->full_name} عبر واتساب")
                ->action(function (): void {
                    SendInvestorSummary::dispatch($this->record);

                    Notification::make()
                        ->title('تم إرسال الملخص')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => filled($this->record->phone)),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
