<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function ($model): void {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'created_by')) {
                $model->created_by ??= Auth::id();
                $model->updated_by ??= Auth::id();
            }
        });

        static::updating(function ($model): void {
            if (Auth::check() && $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
