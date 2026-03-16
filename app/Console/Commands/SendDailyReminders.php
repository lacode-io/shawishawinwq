<?php

namespace App\Console\Commands;

use App\Enums\CustomerStatus;
use App\Jobs\SendLatePayerAdminAlert;
use App\Jobs\SendPaymentDueReminder;
use App\Models\Customer;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Console\Command;

class SendDailyReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders';

    protected $description = 'Send daily WhatsApp payment reminders to customers with upcoming/overdue installments';

    public function handle(WhatsAppManager $whatsapp): int
    {
        if (! $whatsapp->isEnabled()) {
            $this->warn('WhatsApp notifications are disabled.');

            return self::SUCCESS;
        }

        $customers = Customer::where('status', CustomerStatus::Active)
            ->whereNotNull('phone')
            ->get();

        $sentReminders = 0;
        $sentAlerts = 0;

        foreach ($customers as $customer) {
            if (! $customer->is_late) {
                // Check if due within 3 days
                $nextDue = $customer->next_due_date;
                if (! $nextDue || $nextDue->diffInDays(now()) > 3) {
                    continue;
                }
            }

            // Dispatch reminder to customer
            SendPaymentDueReminder::dispatch($customer);
            $sentReminders++;

            // If late, also alert admin
            if ($customer->is_late) {
                SendLatePayerAdminAlert::dispatch($customer);
                $sentAlerts++;
            }
        }

        $this->info("Dispatched {$sentReminders} reminders and {$sentAlerts} admin alerts.");

        return self::SUCCESS;
    }
}
