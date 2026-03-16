<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Enums\CustomerStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_payment')
                ->label(__('Payment').' - '.__('New'))
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
                        ->default(fn (): int => $this->record->payment_type === PaymentType::LumpSum
                            ? $this->record->product_sale_total
                            : $this->record->monthly_installment_amount),

                    Forms\Components\Select::make('payment_method')
                        ->label(__('Payment Method'))
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))
                        ->default(PaymentMethod::Cash->value)
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('Notes'))
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->payments()->create([
                        'paid_at' => $data['paid_at'],
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method'],
                        'received_by' => auth()->id(),
                        'meta' => $data['notes'] ? ['notes' => $data['notes']] : null,
                    ]);

                    Notification::make()
                        ->title('تم تسجيل التسديد بنجاح')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => $this->record->status === CustomerStatus::Active),

            Actions\Action::make('mark_completed')
                ->label('مكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('تأكيد إتمام الأقساط')
                ->modalDescription(fn (): string => "هل أنت متأكد من إتمام أقساط {$this->record->full_name}؟")
                ->action(function (): void {
                    $this->record->update(['status' => CustomerStatus::Completed]);

                    Notification::make()
                        ->title('تم وضع علامة مكتمل')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                })
                ->visible(fn (): bool => $this->record->status === CustomerStatus::Active),

            Actions\Action::make('print_invoice')
                ->label(__('Receipt'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn (): string => route('customers.invoice', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->modalHeading('حذف الزبون')
                ->form([
                    Forms\Components\Textarea::make('deletion_reason')
                        ->label('سبب الحذف')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'deletion_reason' => $data['deletion_reason'],
                        'deletion_requested_by' => auth()->id(),
                    ]);
                    $this->record->delete();

                    Notification::make()
                        ->title('تم حذف الزبون')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
