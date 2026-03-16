<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudApiWhatsAppProvider implements WhatsAppContract
{
    public function __construct(
        private readonly string $phoneNumberId,
        private readonly string $accessToken,
    ) {}

    public function sendMessage(string $to, string $message): ?string
    {
        $to = $this->formatPhone($to);

        try {
            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            Log::info('CloudAPI WhatsApp raw response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $to,
            ]);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');
                $messageStatus = $response->json('messages.0.message_status');

                Log::info('CloudAPI WhatsApp sent', [
                    'message_id' => $messageId,
                    'message_status' => $messageStatus,
                ]);

                return $messageId;
            }

            Log::error('CloudAPI WhatsApp failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('CloudAPI: '.$response->json('error.message', 'Unknown error'));
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CloudAPI WhatsApp exception', ['error' => $e->getMessage()]);
            throw new \RuntimeException('CloudAPI: '.$e->getMessage(), 0, $e);
        }
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): ?string
    {
        $to = $this->formatPhone($to);

        $components = [];
        if ($parameters) {
            $params = [];
            foreach ($parameters as $value) {
                $params[] = ['type' => 'text', 'text' => $value];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $params,
            ];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'ar'],
                        'components' => $components,
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('messages.0.id');
            }

            throw new \RuntimeException('CloudAPI Template: '.$response->json('error.message', 'Unknown error'));
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('CloudAPI Template: '.$e->getMessage(), 0, $e);
        }
    }

    public function providerName(): string
    {
        return 'cloud_api';
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '07')) {
            $phone = '964'.substr($phone, 1);
        }

        return $phone;
    }
}
