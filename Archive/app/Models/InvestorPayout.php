<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvestorPayout extends Model
{
    use Blameable, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        $flush = function () {
            Cache::forget('finance.cash_capital');
            Cache::forget('finance.investors_due');
            Cache::forget('finance.investor_paid_out');
            Cache::forget('finance.investor_dues_so_far');
            Cache::forget('finance.cash_register');
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    protected $fillable = [
        'investor_id',
        'paid_at',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['investor_id', 'amount', 'paid_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} دفعة مستثمر");
    }
}
