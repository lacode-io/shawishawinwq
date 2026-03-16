<?php

namespace App\Models;

use App\Enums\NotePriority;
use App\Enums\NoteType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AppNote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'type',
        'title',
        'body',
        'tags',
        'priority',
        'related_customer_id',
        'related_investor_id',
        'created_by_user_id',
        'updated_by_user_id',
        'pinned_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
            'priority' => NotePriority::class,
            'tags' => 'array',
            'pinned_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $note) {
            $note->created_by_user_id ??= auth()->id();
            $note->updated_by_user_id ??= auth()->id();
        });

        static::updating(function (self $note) {
            $note->updated_by_user_id = auth()->id();
        });
    }

    // ── Relationships ──

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'related_customer_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class, 'related_investor_id');
    }

    // ── Computed ──

    public function getIsPinnedAttribute(): bool
    {
        return $this->pinned_at !== null;
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }

    // ── Scopes ──

    public function scopePinned(Builder $query): Builder
    {
        return $query->whereNotNull('pinned_at');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeOfType(Builder $query, NoteType $type): Builder
    {
        return $query->where('type', $type);
    }

    // ── Activity Log ──

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'title', 'priority', 'pinned_at', 'archived_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} ملاحظة");
    }
}
