<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterTransaction extends Model
{
    protected $fillable = [
        'type',
        'amount',
        'balance_after',
        'description',
        'month',
        'year',
        'settled_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }
}
