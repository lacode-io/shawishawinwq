<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduledNotification extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SKIPPED = 'skipped';

    public const TYPE_DUE_TOMORROW = 'payment_due_tomorrow';

    public const TYPE_DUE_TODAY = 'payment_due_today';

    public const TYPE_OVERDUE = 'payment_overdue';

    public const TYPE_ADMIN_ALERT = 'late_payer_alert';

    protected $fillable = [
        'scheduled_for',
        'message_type',
        'notifiable_type',
        'notifiable_id',
        'to_phone',
        'status',
        'attempts',
        'notification_log_id',
        'error',
        'expired_reason',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function notificationLog(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_for', now()->toDateString());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('scheduled_for', '>=', now()->toDateString());
    }

    public static function messageTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DUE_TOMORROW => 'تذكير قبل يوم',
            self::TYPE_DUE_TODAY => 'تذكير يوم الاستحقاق',
            self::TYPE_OVERDUE => 'تنبيه تأخير',
            self::TYPE_ADMIN_ALERT => 'تنبيه إداري',
            default => $type,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'بانتظار الإرسال',
            self::STATUS_SENDING => 'يتم الإرسال',
            self::STATUS_SENT => 'تم الإرسال',
            self::STATUS_FAILED => 'فشل',
            self::STATUS_EXPIRED => 'منتهي',
            self::STATUS_SKIPPED => 'متخطى',
            default => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_SENDING => 'info',
            self::STATUS_SENT => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_SKIPPED => 'gray',
            default => 'gray',
        };
    }
}
