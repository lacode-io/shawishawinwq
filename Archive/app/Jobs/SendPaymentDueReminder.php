<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Number;

class SendPaymentDueReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Customer $customer,
    ) {}

    public function handle(WhatsAppManager $whatsapp): void
    {
        // Log the start of the job
        \Log::info('Starting SendPaymentDueReminder job', ['customer_id' => $this->customer->id]);

        if (! $whatsapp->isEnabled()) {
            \Log::warning('WhatsApp is not enabled', ['customer_id' => $this->customer->id]);
            return;
        }

        if (! $this->customer->phone) {
            \Log::warning('Customer has no phone', ['customer_id' => $this->customer->id]);
            return;
        }

        if (! $whatsapp->canSend($this->customer->phone, 'payment_due_reminder')) {
            \Log::warning('Cannot send to phone', ['phone' => $this->customer->phone]);
            return;
        }

        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        $parameters = [
            $this->customer->full_name,
            $siteName,
            $this->customer->next_due_date?->format('Y/m/d') ?? '-',
            Number::iqd($this->customer->monthly_installment_amount),
            Number::iqd($this->customer->total_paid),
            Number::iqd($this->customer->remaining_balance),
        ];

        \Log::info('Sending payment reminder template', [
            'phone' => $this->customer->phone,
            'parameters' => $parameters,
        ]);

        $response = $whatsapp->sendTemplate(
            to: $this->customer->phone,
            templateName: 'payment_due_reminder',
            parameters: $parameters,
            messageType: 'payment_due_reminder',
            notifiable: $this->customer,
        );

        \Log::info('WhatsApp API Response', ['status' => $response->status]);
    }
}