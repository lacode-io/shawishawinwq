<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Services\WhatsApp\MessageTemplates;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchNextWhatsAppNotification extends Command
{
    protected $signature = 'whatsapp:dispatch-next
                            {--limit=1 : عدد الاشعارات التي ترسل في كل تشغيل}';

    protected $description = 'يأخذ اشعار واحد من اشعارات اليوم المعلقة ويرسله. يتجاهل اشعارات الأيام الماضية ويعلمها كمنتهية.';

    public function handle(WhatsAppManager $whatsapp): int
    {
        if (! $whatsapp->isEnabled()) {
            $this->warn('WhatsApp غير مفعل — لا شيء للإرسال.');

            return self::SUCCESS;
        }

        $today = now()->toDateString();
        $limit = max(1, (int) $this->option('limit'));
        $sent = 0;

        for ($i = 0; $i < $limit; $i++) {
            $notification = $this->claimNext($today);

            if (! $notification) {
                $this->info($i === 0 ? 'لا يوجد اشعارات معلقة لليوم.' : "تم إرسال {$sent} اشعار(ات).");

                return self::SUCCESS;
            }

            $this->processOne($notification, $whatsapp);
            $sent++;
        }

        $this->info("تم إرسال {$sent} اشعار(ات).");

        return self::SUCCESS;
    }

    /**
     * يحجز اشعار واحد للإرسال داخل ترانزكشن مع قفل للصف لمنع التسابق.
     */
    private function claimNext(string $today): ?ScheduledNotification
    {
        return DB::transaction(function () use ($today) {
            $next = ScheduledNotification::query()
                ->where('status', ScheduledNotification::STATUS_PENDING)
                ->whereDate('scheduled_for', $today)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                return null;
            }

            $next->update([
                'status' => ScheduledNotification::STATUS_SENDING,
                'attempts' => $next->attempts + 1,
            ]);

            return $next->fresh();
        });
    }

    private function processOne(ScheduledNotification $notification, WhatsAppManager $whatsapp): void
    {
        $notifiable = $notification->notifiable;

        if (! $notifiable) {
            $notification->update([
                'status' => ScheduledNotification::STATUS_SKIPPED,
                'error' => 'الكيان المرتبط لم يعد موجوداً',
            ]);
            $this->warn("تخطٍ: الاشعار #{$notification->id} — الزبون محذوف.");

            return;
        }

        $message = $this->buildMessage($notification, $notifiable);

        if ($message === null) {
            $notification->update([
                'status' => ScheduledNotification::STATUS_SKIPPED,
                'error' => 'نوع الرسالة غير معروف',
            ]);
            $this->warn("تخطٍ: نوع رسالة غير معروف ({$notification->message_type}).");

            return;
        }

        // فحص إعادة التحقق: هل الزبون لازال يستحق هذه الرسالة؟
        $skipReason = $this->shouldSkip($notification, $notifiable);

        if ($skipReason !== null) {
            $notification->update([
                'status' => ScheduledNotification::STATUS_SKIPPED,
                'error' => $skipReason,
            ]);
            $this->warn("تخطٍ: الاشعار #{$notification->id} — {$skipReason}");

            return;
        }

        try {
            $log = $whatsapp->send(
                to: $notification->to_phone,
                message: $message,
                messageType: $notification->message_type,
                notifiable: $notifiable,
            );

            $notification->update([
                'status' => $log->status === 'sent'
                    ? ScheduledNotification::STATUS_SENT
                    : ScheduledNotification::STATUS_FAILED,
                'notification_log_id' => $log->id,
                'error' => $log->error,
                'sent_at' => $log->sent_at,
            ]);

            $this->info("→ {$notification->message_type} : {$notification->to_phone} : {$log->status}");
        } catch (\Throwable $e) {
            $notification->update([
                'status' => ScheduledNotification::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
            $this->error("فشل إرسال الاشعار #{$notification->id}: {$e->getMessage()}");
        }
    }

    private function buildMessage(ScheduledNotification $notification, $notifiable): ?string
    {
        return match ($notification->message_type) {
            ScheduledNotification::TYPE_DUE_TOMORROW => MessageTemplates::paymentDueTomorrow($notifiable),
            ScheduledNotification::TYPE_DUE_TODAY => MessageTemplates::paymentDueToday($notifiable),
            ScheduledNotification::TYPE_OVERDUE => MessageTemplates::overduePayment($notifiable),
            ScheduledNotification::TYPE_ADMIN_ALERT => MessageTemplates::latePayerAdminAlert($notifiable),
            default => null,
        };
    }

    /**
     * إعادة فحص قبل الإرسال للتأكد من أن الزبون لازال بنفس الحالة (يمكن أن يكون قد سدد بين التخطيط والإرسال).
     */
    private function shouldSkip(ScheduledNotification $notification, $notifiable): ?string
    {
        if (! method_exists($notifiable, 'getAttribute')) {
            return null;
        }

        // زبون
        if ($notifiable instanceof \App\Models\Customer) {
            if ($notifiable->is_platform) {
                return 'الزبون داخلي (منصة)';
            }

            if ($notifiable->status !== \App\Enums\CustomerStatus::Active) {
                return 'الزبون لم يعد فعالاً';
            }

            // إذا الاشعار من نوع "متأخر" بس الزبون ما عاد متأخر
            if ($notification->message_type === ScheduledNotification::TYPE_OVERDUE && ! $notifiable->is_late) {
                return 'الزبون لم يعد متأخراً';
            }

            // إذا نوع "تذكير اليوم" أو "قبل يوم" بس الموعد فات أو ما يطابق
            if (in_array($notification->message_type, [
                ScheduledNotification::TYPE_DUE_TODAY,
                ScheduledNotification::TYPE_DUE_TOMORROW,
            ], true)) {
                $due = $notifiable->next_due_date;
                if (! $due || $due->lt(now()->startOfDay())) {
                    return 'موعد الاستحقاق فات أو غير محدد';
                }
            }
        }

        return null;
    }
}
