<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Enums\ExpenseSubType;
use App\Enums\ExpenseType;
use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        if (($data['type'] ?? null) === ExpenseType::Salary->value) {
            $first = Expense::create([
                'type' => ExpenseType::Salary->value,
                'sub_type' => ExpenseSubType::Haider->value,
                'amount' => $data['amount'],
                'spent_at' => $data['spent_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            Expense::create([
                'type' => ExpenseType::Salary->value,
                'sub_type' => ExpenseSubType::Thaqr->value,
                'amount' => $data['amount'],
                'spent_at' => $data['spent_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            Notification::make()
                ->title('تم إضافة راتب حيدر وذو الفقار')
                ->success()
                ->send();

            return $first;
        }

        return parent::handleRecordCreation($data);
    }
}
