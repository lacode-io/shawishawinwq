<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsApp\MessageTemplates;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLatePayerAdminAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Customer $customer,
    ) {}

    public function handle(WhatsAppManager $whatsapp): void
    {
        if (! $whatsapp->isEnabled()) {
            return;
        }

        $config = Setting::instance()->whatsapp_provider_config ?? [];
        $adminPhone = $config['admin_phone'] ?? null;

        if (! $adminPhone) {
            return;
        }

        if (! $whatsapp->canSend($adminPhone, 'late_payer_alert_'.$this->customer->id)) {
            return;
        }

        $message = MessageTemplates::latePayerAdminAlert($this->customer);

        $whatsapp->send(
            to: $adminPhone,
            message: $message,
            messageType: 'late_payer_alert_'.$this->customer->id,
            notifiable: $this->customer,
        );
    }
}
