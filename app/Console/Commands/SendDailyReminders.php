<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDailyReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders';

    protected $description = '[Deprecated] استخدم whatsapp:plan-day بدلها — يحوّل تلقائياً للأمر الجديد للحفاظ على التوافق.';

    public function handle(): int
    {
        $this->warn('whatsapp:send-reminders صار مهجور — يتم تحويله الى whatsapp:plan-day.');

        return $this->call('whatsapp:plan-day');
    }
}
