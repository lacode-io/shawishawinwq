<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\PaymentType;
use App\Models\Concerns\Blameable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use Blameable, HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        $flush = function () {
            Cache::forget('finance.total_capital');
            Cache::forget('finance.capital_installments');
            Cache::forget('finance.cash_capital');
            Cache::forget('finance.total_profit_earned');
            Cache::forget('finance.cash_register');
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    protected $fillable = [
        'full_name',
        'phone',
        'address',
        'guarantor_name',
        'guarantor_phone',
        'product_type',
        'product_cost_price',
        'product_sale_total',
        'product_price_usd',
        'delivery_date',
        'payment_type',
        'lump_sum_days',
        'payment_due_date',
        'duration_months',
        'monthly_installment_amount',
        'status',
        'notes',
        'card_number',
        'card_code',
        'internal_notes',
        'deletion_reason',
        'deletion_requested_by',
        'deletion_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'payment_type' => PaymentType::class,
            'delivery_date' => 'date',
            'lump_sum_days' => 'integer',
            'payment_due_date' => 'date',
            'product_cost_price' => 'integer',
            'product_sale_total' => 'integer',
            'product_price_usd' => 'integer',
            'monthly_installment_amount' => 'integer',
            'duration_months' => 'integer',
        ];
    }

    // ── Relationships ──

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function deletionRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deletion_requested_by');
    }

    public function deletionApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deletion_approved_by');
    }

    // ── Computed Attributes ──

    public function getTotalPaidAttribute(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): int
    {
        return $this->product_sale_total - $this->total_paid;
    }

    public function getNextDueDateAttribute(): ?Carbon
    {
        // دفعة واحدة: تاريخ التسليم + عدد الأيام
        if ($this->payment_type === PaymentType::LumpSum) {
            if ($this->payments()->exists()) {
                return null; // تم التسديد
            }

            return $this->delivery_date->copy()->addDays($this->lump_sum_days ?? 0);
        }

        // أقساط شهرية
        $paidCount = $this->payments()->count();

        if ($paidCount >= $this->duration_months) {
            return null;
        }

        return $this->delivery_date->copy()->addMonths($paidCount + 1);
    }

    public function getIsLateAttribute(): bool
    {
        if ($this->status !== CustomerStatus::Active) {
            return false;
        }

        $nextDue = $this->next_due_date;

        return $nextDue !== null && $nextDue->isPast();
    }

    public function getMonthsLateAttribute(): int
    {
        if (! $this->is_late) {
            return 0;
        }

        return (int) $this->next_due_date->diffInMonths(now());
    }

    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->payments()->count();
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CustomerStatus::Active);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', CustomerStatus::Completed);
    }

    public function scopeLate(Builder $query): Builder
    {
        return $query->where('status', CustomerStatus::Active)
            ->where(function (Builder $q) {
                // أقساط شهرية متأخرة
                $q->where(function (Builder $q) {
                    $q->where(fn ($q) => $q->whereNull('payment_type')->orWhere('payment_type', PaymentType::Installment))
                        ->whereRaw('DATE_ADD(delivery_date, INTERVAL (SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) + 1 MONTH) < NOW()');
                })
                // دفعة واحدة متأخرة
                ->orWhere(function (Builder $q) {
                    $q->where('payment_type', PaymentType::LumpSum)
                        ->whereRaw('DATE_ADD(delivery_date, INTERVAL lump_sum_days DAY) < NOW()')
                        ->whereRaw('(SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) = 0');
                });
            });
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'phone', 'product_type', 'product_sale_total', 'duration_months', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} زبون");
    }
}
