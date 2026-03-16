<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Console\Command;
use Illuminate\Support\Number;

class TestWhatsAppMessage extends Command
{
    protected $signature = 'whatsapp:test
        {phone : Phone number to send to (e.g. 9647733873361)}
        {--template=payment_due_reminder : Template to test (payment_due_reminder, payment_received)}
        {--customer= : Customer ID to use for template data}
        {--dry-run : Only show the parameters, do not send}';

    protected $description = 'Test sending a WhatsApp template message';

    public function handle(WhatsAppManager $whatsapp): int
    {
        $phone = $this->argument('phone');
        $template = $this->option('template');
        $dryRun = $this->option('dry-run');

        if (! $dryRun && ! $whatsapp->isEnabled()) {
            $this->error('WhatsApp is not enabled in settings.');

            return self::FAILURE;
        }

        $data = $this->buildTemplateData($template);

        if ($data === null) {
            return self::FAILURE;
        }

        $this->info("Template: {$data['name']}");
        $this->info("Phone: {$phone}");
        $this->info('Parameters:');
        foreach ($data['parameters'] as $i => $param) {
            $this->line('  {{'.($i + 1).'}}: '.$param);
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('Dry run — message was NOT sent.');

            return self::SUCCESS;
        }

        $this->info('Sending...');

        $log = $whatsapp->sendTemplate(
            to: $phone,
            templateName: $data['name'],
            parameters: $data['parameters'],
            messageType: "test_{$template}",
        );

        if ($log->status === 'sent') {
            $this->info("Sent successfully! Message ID: {$log->provider_message_id}");

            return self::SUCCESS;
        }

        $this->error("Failed: {$log->error}");

        return self::FAILURE;
    }

    private function buildTemplateData(string $template): ?array
    {
        return match ($template) {
            'payment_due_reminder' => $this->paymentDueReminderData(),
            'payment_received' => $this->paymentReceivedData(),
            default => $this->invalidTemplate($template),
        };
    }

    private function paymentDueReminderData(): ?array
    {
        $customer = $this->resolveCustomer();
        if (! $customer) {
            return null;
        }

        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return [
            'name' => 'payment_due_reminder',
            'parameters' => [
                $customer->full_name,
                $siteName,
                $customer->next_due_date?->format('Y/m/d') ?? '-',
                Number::iqd($customer->monthly_installment_amount),
                Number::iqd($customer->total_paid),
                Number::iqd($customer->remaining_balance),
            ],
        ];
    }

    private function paymentReceivedData(): ?array
    {
        $customer = $this->resolveCustomer();
        if (! $customer) {
            return null;
        }

        $siteName = Setting::instance()->site_name ?? 'شوي شوي';

        return [
            'name' => 'payment_received_confirmation',
            'parameters' => [
                $customer->full_name,
                $siteName,
                Number::iqd($customer->monthly_installment_amount),
                Number::iqd($customer->total_paid),
                Number::iqd($customer->remaining_balance),
            ],
        ];
    }

    private function resolveCustomer(): ?Customer
    {
        $id = $this->option('customer') ?: Customer::first()?->id;

        $customer = Customer::find($id);
        if (! $customer) {
            $this->error('Customer not found. Use --customer=ID');

            return null;
        }

        $this->info("Using customer: {$customer->full_name} (#{$customer->id})");

        return $customer;
    }

    private function invalidTemplate(string $template): null
    {
        $this->error("Unknown template: {$template}");
        $this->info('Available: payment_due_reminder, payment_received');

        return null;
    }
}
