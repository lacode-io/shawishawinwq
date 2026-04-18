<?php

namespace App\Console\Commands;

use App\Enums\CustomerStatus;
use App\Jobs\SendLatePayerAdminAlert;
use App\Jobs\SendOverduePaymentReminder;
use App\Jobs\SendPaymentDueTodayReminder;
use App\Jobs\SendPaymentDueTomorrowReminder;
use App\Models\Customer;
use App\Services\WhatsApp\WhatsAppManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders';

    protected $description = 'Dispatch WhatsApp reminders: one day before, on due day, and daily while overdue (year-agnostic, month+day match)';

    public function handle(WhatsAppManager $whatsapp): int
    {
        if (! $whatsapp->isEnabled()) {
            $this->warn('WhatsApp notifications are disabled.');

            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $customers = Customer::query()
            ->where('status', CustomerStatus::Active)
            ->where('is_platform', false)
            ->whereNotNull('phone')
            ->get();

        $stats = ['tomorrow' => 0, 'today' => 0, 'overdue' => 0, 'alerts' => 0];

        foreach ($customers as $customer) {
            $due = $customer->next_due_date;

            if (! $due instanceof Carbon) {
                continue;
            }

            $dueDay = $due->startOfDay();

            if ($this->matchesMonthDay($dueDay, $tomorrow)) {
                SendPaymentDueTomorrowReminder::dispatch($customer);
                $stats['tomorrow']++;

                continue;
            }

            if ($this->matchesMonthDay($dueDay, $today)) {
                SendPaymentDueTodayReminder::dispatch($customer);
                $stats['today']++;

                continue;
            }

            if ($dueDay->lt($today) && $customer->is_late) {
                SendOverduePaymentReminder::dispatch($customer);
                $stats['overdue']++;

                SendLatePayerAdminAlert::dispatch($customer);
                $stats['alerts']++;
            }
        }

        $this->info(sprintf(
            'Dispatched — tomorrow: %d, today: %d, overdue: %d, admin alerts: %d',
            $stats['tomorrow'],
            $stats['today'],
            $stats['overdue'],
            $stats['alerts'],
        ));

        return self::SUCCESS;
    }

    /**
     * Match month and day only, ignoring year.
     */
    private function matchesMonthDay(Carbon $a, Carbon $b): bool
    {
        return $a->month === $b->month && $a->day === $b->day;
    }
}
