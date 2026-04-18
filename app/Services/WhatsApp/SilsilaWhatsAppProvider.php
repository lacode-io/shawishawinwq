<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SilsilaWhatsAppProvider implements WhatsAppContract
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ?string $sessionId,
        private readonly ?int $channelId,
    ) {}

    public function sendMessage(string $to, string $message): ?string
    {
        $payload = [
            'to' => $this->formatPhone($to),
            'type' => 'text',
            'content' => $message,
        ];

        if (! empty($this->sessionId)) {
            $payload['session_id'] = $this->sessionId;
        }

        if (! empty($this->channelId)) {
            $payload['channel_id'] = $this->channelId;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->endpoint('/api/v1/whatsapp/messages/send'), $payload);

            Log::info('Silsila WhatsApp raw response', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $payload['to'],
            ]);

            if ($response->successful()) {
                return (string) ($response->json('data.message_id') ?? '');
            }

            throw new \RuntimeException('Silsila: '.$response->json('message', 'Unknown error'));
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Silsila WhatsApp exception', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Silsila: '.$e->getMessage(), 0, $e);
        }
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): ?string
    {
        $body = $templateName;
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $body = str_replace("{{{$key}}}", (string) $value, $body);
            }
        }

        return $this->sendMessage($to, $body);
    }

    public function providerName(): string
    {
        return 'silsila';
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->baseUrl, '/').$path;
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
