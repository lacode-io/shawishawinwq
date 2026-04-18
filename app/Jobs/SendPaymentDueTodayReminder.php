<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\WhatsApp\MessageTemplates;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPaymentDueTodayReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Customer $customer,
        public ?string $overridePhone = null,
    ) {}

    public function handle(WhatsAppManager $whatsapp): void
    {
        if (! $whatsapp->isEnabled()) {
            return;
        }

        if ($this->overridePhone === null && $this->customer->is_platform) {
            return;
        }

        $to = $this->overridePhone ?: $this->customer->phone;

        if (! $to) {
            return;
        }

        if ($this->overridePhone === null
            && ! $whatsapp->canSend($to, 'payment_due_today')) {
            return;
        }

        $whatsapp->send(
            to: $to,
            message: MessageTemplates::paymentDueToday($this->customer),
            messageType: 'payment_due_today',
            notifiable: $this->customer,
        );
    }
}
