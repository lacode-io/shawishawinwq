<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioWhatsAppProvider implements WhatsAppContract
{
    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $fromNumber,
    ) {}

    public function sendMessage(string $to, string $message): ?string
    {
        $to = $this->formatPhone($to);

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => "whatsapp:{$this->fromNumber}",
                    'To' => "whatsapp:{$to}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return $response->json('sid');
            }

            Log::error('Twilio WhatsApp failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Twilio: '.$response->json('message', 'Unknown error'));
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Twilio WhatsApp exception', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Twilio: '.$e->getMessage(), 0, $e);
        }
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): ?string
    {
        $body = $templateName;
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $body = str_replace("{{{$key}}}", $value, $body);
            }
        }

        return $this->sendMessage($to, $body);
    }

    public function providerName(): string
    {
        return 'twilio';
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '07')) {
            $phone = '964'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
