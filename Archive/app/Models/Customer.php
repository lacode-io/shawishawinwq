<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Models\Concerns\Blameable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use Blameable, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'address',
        'guarantor_name',
        'guarantor_phone',
        'product_type',
        'product_cost_price',
        'product_sale_total',
        'delivery_date',
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
            'delivery_date' => 'date',
            'product_cost_price' => 'integer',
            'product_sale_total' => 'integer',
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
            ->whereHas('payments', function () {}, '<', function ($q) {
                $q->selectRaw('duration_months');
            })
            ->whereRaw('DATE_ADD(delivery_date, INTERVAL (SELECT COUNT(*) FROM customer_payments WHERE customer_payments.customer_id = customers.id) + 1 MONTH) < NOW()');
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
