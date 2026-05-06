<?php

namespace App\Console\Commands;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\ScheduledNotification;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PlanWhatsAppDay extends Command
{
    protected $signature = 'whatsapp:plan-day
                            {--date= : التاريخ المراد التخطيط له (افتراضي اليوم)}
                            {--dry-run : عرض ما سيتم جدولته دون كتابته}';

    protected $description = 'يحسب اشعارات الواتساب الواجب إرسالها اليوم ويذبهن بانتظار في جدول scheduled_notifications. يلغي الاشعارات المعلقة المنتهية من الأيام السابقة.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $isDryRun = (bool) $this->option('dry-run');

        $this->info("التخطيط ليوم: {$date->format('Y-m-d')}".($isDryRun ? ' (وضع المعاينة)' : ''));

        if (! $isDryRun) {
            $expired = $this->expireStaleEntries($date);
            if ($expired > 0) {
                $this->warn("تم تعليم {$expired} اشعار قديم بأنه منتهٍ.");
            }
        }

        $tomorrow = $date->copy()->addDay();
        $stats = ['tomorrow' => 0, 'today' => 0, 'overdue' => 0, 'admin_alerts' => 0, 'duplicates' => 0];

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active)
            ->where('is_platform', false)
            ->whereNotNull('phone')
            ->get();

        $adminPhone = $this->resolveAdminPhone();

        foreach ($customers as $customer) {
            $due = $customer->next_due_date;

            if (! $due instanceof Carbon) {
                continue;
            }

            // إذا الزبون سدد قسط بهل الشهر التقويمي، تخطّيه كلياً — بدون أي إشعار.
            if ($this->hasPaidThisMonth($customer, $date)) {
                continue;
            }

            $dueDay = $due->copy()->startOfDay();
            $dayOfMonth = $dueDay->day;

            // قبل يوم
            if ($tomorrow->day === $dayOfMonth && $dueDay->gte($date)) {
                $created = $this->schedule(
                    $date,
                    ScheduledNotification::TYPE_DUE_TOMORROW,
                    $customer,
                    $customer->phone,
                    $isDryRun,
                );
                $created ? $stats['tomorrow']++ : $stats['duplicates']++;

                continue;
            }

            // اليوم
            if ($date->day === $dayOfMonth && $dueDay->gte($date)) {
                $created = $this->schedule(
                    $date,
                    ScheduledNotification::TYPE_DUE_TODAY,
                    $customer,
                    $customer->phone,
                    $isDryRun,
                );
                $created ? $stats['today']++ : $stats['duplicates']++;

                continue;
            }

            // متأخر
            if ($dueDay->lt($date) && $customer->is_late) {
                $created = $this->schedule(
                    $date,
                    ScheduledNotification::TYPE_OVERDUE,
                    $customer,
                    $customer->phone,
                    $isDryRun,
                );
                $created ? $stats['overdue']++ : $stats['duplicates']++;

                if ($adminPhone) {
                    $created = $this->schedule(
                        $date,
                        ScheduledNotification::TYPE_ADMIN_ALERT,
                        $customer,
                        $adminPhone,
                        $isDryRun,
                    );
                    $created ? $stats['admin_alerts']++ : $stats['duplicates']++;
                }
            }
        }

        $this->table(
            ['النوع', 'عدد'],
            [
                ['تذكير قبل يوم', $stats['tomorrow']],
                ['تذكير يوم الاستحقاق', $stats['today']],
                ['تنبيه تأخير', $stats['overdue']],
                ['تنبيه إداري', $stats['admin_alerts']],
                ['مكررة (تم تجاهلها)', $stats['duplicates']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * يعلّم الاشعارات القديمة المعلقة كمنتهية كي لا تنرسل اليوم بعد فات وقتها.
     */
    private function expireStaleEntries(Carbon $date): int
    {
        return ScheduledNotification::query()
            ->whereIn('status', [ScheduledNotification::STATUS_PENDING, ScheduledNotification::STATUS_SENDING])
            ->whereDate('scheduled_for', '<', $date->toDateString())
            ->update([
                'status' => ScheduledNotification::STATUS_EXPIRED,
                'expired_reason' => 'تجاوز اليوم المحدد قبل الإرسال',
                'updated_at' => now(),
            ]);
    }

    private function schedule(
        Carbon $date,
        string $messageType,
        Customer $customer,
        string $toPhone,
        bool $isDryRun,
    ): bool {
        if ($isDryRun) {
            $this->line("  + {$messageType} → {$customer->full_name} ({$toPhone})");

            return true;
        }

        // وحدوية: زبون + نوع + يوم → سجل واحد
        $existing = ScheduledNotification::query()
            ->where('notifiable_type', $customer->getMorphClass())
            ->where('notifiable_id', $customer->getKey())
            ->where('message_type', $messageType)
            ->whereDate('scheduled_for', $date->toDateString())
            ->exists();

        if ($existing) {
            return false;
        }

        ScheduledNotification::create([
            'scheduled_for' => $date->toDateString(),
            'message_type' => $messageType,
            'notifiable_type' => $customer->getMorphClass(),
            'notifiable_id' => $customer->getKey(),
            'to_phone' => $toPhone,
            'status' => ScheduledNotification::STATUS_PENDING,
        ]);

        return true;
    }

    private function resolveAdminPhone(): ?string
    {
        $config = Setting::instance()->whatsapp_provider_config ?? [];

        return $config['admin_phone'] ?? null;
    }

    /**
     * يفحص إذا الزبون سدد أي قسط بالشهر التقويمي الحالي.
     * Why: حتى ما يندز تنبيه تأخير لزبون سدد قسط هل الشهر، حتى لو متأخر بأشهر فاتت.
     */
    private function hasPaidThisMonth(Customer $customer, Carbon $date): bool
    {
        return $customer->payments()
            ->whereYear('paid_at', $date->year)
            ->whereMonth('paid_at', $date->month)
            ->exists();
    }
}
