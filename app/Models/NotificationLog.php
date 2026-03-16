<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'to_phone',
        'message_type',
        'payload',
        'provider',
        'status',
        'error',
        'provider_message_id',
        'notifiable_type',
        'notifiable_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markSent(?string $providerMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }

    public static function wasRecentlySent(string $phone, string $messageType, int $hoursAgo = 24): bool
    {
        return static::where('to_phone', $phone)
            ->where('message_type', $messageType)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subHours($hoursAgo))
            ->exists();
    }
}
