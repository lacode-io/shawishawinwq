<?php

namespace App\Models;

use App\Enums\InvestorStatus;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Investor extends Model
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
        'full_name',
        'phone',
        'amount_invested',
        'amount_usd',
        'investment_months',
        'profit_percent_total',
        'start_date',
        'payout_due_date',
        'total_payout_date',
        'monthly_target_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvestorStatus::class,
            'start_date' => 'date',
            'payout_due_date' => 'date',
            'total_payout_date' => 'date',
            'amount_invested' => 'integer',
            'amount_usd' => 'integer',
            'monthly_target_amount' => 'integer',
            'profit_percent_total' => 'decimal:2',
            'investment_months' => 'integer',
        ];
    }

    // ── Relationships ──

    public function payouts(): HasMany
    {
        return $this->hasMany(InvestorPayout::class);
    }

    // ── Core Computed ──

    public function getTotalProfitAmountAttribute(): int
    {
        return (int) round($this->amount_invested * ($this->profit_percent_total / 100));
    }

    public function getTotalDueAttribute(): int
    {
        return $this->amount_invested + $this->total_profit_amount;
    }

    public function getTotalPaidOutAttribute(): int
    {
        return (int) $this->payouts()->sum('amount');
    }

    public function getRemainingBalanceAttribute(): int
    {
        return $this->total_due - $this->total_paid_out;
    }

    // ── Progress Tracking ──

    public function getElapsedMonthsAttribute(): int
    {
        if ($this->start_date->isFuture()) {
            return 0;
        }

        return (int) $this->start_date->diffInMonths(now());
    }

    public function getExpectedProfitSoFarAttribute(): int
    {
        if ($this->investment_months <= 0) {
            return 0;
        }

        $elapsed = min($this->elapsed_months, $this->investment_months);

        return (int) round($this->total_profit_amount * ($elapsed / $this->investment_months));
    }

    public function getExpectedPayoutSoFarAttribute(): int
    {
        if ($this->monthly_target_amount <= 0) {
            return 0;
        }

        $elapsed = min($this->elapsed_months, $this->investment_months);

        return $elapsed * $this->monthly_target_amount;
    }

    public function getIsBehindTargetAttribute(): bool
    {
        if ($this->status !== InvestorStatus::Active) {
            return false;
        }

        return $this->total_paid_out < $this->expected_payout_so_far;
    }

    public function getTargetGapAttribute(): int
    {
        $gap = $this->expected_payout_so_far - $this->total_paid_out;

        return max(0, $gap);
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_due <= 0) {
            return 0;
        }

        return round(($this->total_paid_out / $this->total_due) * 100, 1);
    }

    public function getPaidPayoutsCountAttribute(): int
    {
        return $this->payouts()->count();
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InvestorStatus::Active);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', InvestorStatus::Completed);
    }

    public function scopeBehindTarget(Builder $query): Builder
    {
        return $query->where('status', InvestorStatus::Active)
            ->whereRaw('COALESCE((SELECT SUM(amount) FROM investor_payouts WHERE investor_payouts.investor_id = investors.id), 0) < (LEAST(TIMESTAMPDIFF(MONTH, start_date, NOW()), investment_months) * monthly_target_amount)');
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'amount_invested', 'profit_percent_total', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} مستثمر");
    }
}
