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

class SendPaymentReceivedConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Customer $customer,
        public int $amountPaid,
    ) {}

    public function handle(WhatsAppManager $whatsapp): void
    {
        if (! $whatsapp->isEnabled()) {
            return;
        }

        if (! $this->customer->phone) {
            return;
        }

        $message = MessageTemplates::paymentReceivedConfirmation(
            $this->customer,
            $this->amountPaid,
        );

        $whatsapp->send(
            to: $this->customer->phone,
            message: $message,
            messageType: 'payment_received',
            notifiable: $this->customer,
        );
    }
}
