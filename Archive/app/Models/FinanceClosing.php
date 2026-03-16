<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FinanceClosing extends Model
{
    use Blameable, HasFactory, LogsActivity;

    protected $fillable = [
        'closed_at',
        'notes',
        'rules_applied',
        'snapshot_data',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'date',
            'rules_applied' => 'array',
            'snapshot_data' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['closed_at', 'notes'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "تم {$eventName} إغلاق حساب");
    }
}
