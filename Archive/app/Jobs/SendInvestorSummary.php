<?php

namespace App\Jobs;

use App\Models\Investor;
use App\Services\WhatsApp\MessageTemplates;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvestorSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Investor $investor,
    ) {}

    public function handle(WhatsAppManager $whatsapp): void
    {
        if (! $whatsapp->isEnabled()) {
            return;
        }

        if (! $this->investor->phone) {
            return;
        }

        if (! $whatsapp->canSend($this->investor->phone, 'investor_summary')) {
            return;
        }

        $message = MessageTemplates::investorSummary($this->investor);

        $whatsapp->send(
            to: $this->investor->phone,
            message: $message,
            messageType: 'investor_summary',
            notifiable: $this->investor,
        );
    }
}
