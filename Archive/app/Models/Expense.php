<?php

namespace App\Models;

use App\Enums\ExpenseSubType;
use App\Enums\ExpenseType;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use Blameable, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        $flush = function () {
            Cache::forget('finance.cash_capital');
            Cache::forget('finance.cash_register');
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    protected $fillable = [
        'type',
        'sub_type',
        'amount',
        'spent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExpenseType::class,
            'sub_type' => ExpenseSubType::class,
            'spent_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'sub_type', 'amount', 'spent_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} مصروف");
    }
}
