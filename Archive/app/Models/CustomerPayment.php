<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomerPayment extends Model
{
    use Blameable, HasFactory, LogsActivity;

    protected static function booted(): void
    {
        $flush = function () {
            Cache::forget('finance.cash_capital');
            Cache::forget('finance.capital_installments');
            Cache::forget('finance.total_profit_earned');
            Cache::forget('finance.cash_register');
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    protected $fillable = [
        'customer_id',
        'paid_at',
        'amount',
        'payment_method',
        'received_by',
        'receipt_pdf_path',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'integer',
            'payment_method' => PaymentMethod::class,
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['customer_id', 'amount', 'payment_method', 'paid_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} تسديد");
    }
}
