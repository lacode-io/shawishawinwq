<?php

namespace App\Services\WhatsApp;

use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WhatsAppManager
{
    private ?WhatsAppContract $provider = null;

    public function provider(): WhatsAppContract
    {
        if ($this->provider) {
            return $this->provider;
        }

        $config = Setting::instance()->whatsapp_provider_config ?? [];
        $providerName = $config['provider'] ?? 'fake';

        $this->provider = match ($providerName) {
            'twilio' => new TwilioWhatsAppProvider(
                accountSid: $config['account_sid'] ?? '',
                authToken: $config['auth_token'] ?? '',
                fromNumber: $config['from_number'] ?? '',
            ),
            'cloud_api' => new CloudApiWhatsAppProvider(
                phoneNumberId: $config['phone_number_id'] ?? '',
                accessToken: $config['access_token'] ?? '',
            ),
            'silsila' => new SilsilaWhatsAppProvider(
                apiKey: $config['silsila_api_key'] ?? '',
                baseUrl: $config['silsila_base_url'] ?? 'https://silsila.lacode.io',
                sessionId: $config['silsila_session_id'] ?? null,
                channelId: isset($config['silsila_channel_id']) && $config['silsila_channel_id'] !== ''
                    ? (int) $config['silsila_channel_id']
                    : null,
            ),
            default => new FakeWhatsAppProvider,
        };

        return $this->provider;
    }

    public function isEnabled(): bool
    {
        $config = Setting::instance()->whatsapp_provider_config ?? [];

        return ($config['enabled'] ?? false) === true
            || ($config['enabled'] ?? '') === '1'
            || ($config['enabled'] ?? '') === 'true';
    }

    /**
     * Send a message and log it.
     */
    public function send(
        string $to,
        string $message,
        string $messageType,
        ?Model $notifiable = null,
    ): NotificationLog {
        $provider = $this->provider();

        $log = NotificationLog::create([
            'to_phone' => $to,
            'message_type' => $messageType,
            'payload' => ['message' => $message],
            'provider' => $provider->providerName(),
            'status' => 'pending',
            'notifiable_type' => $notifiable ? $notifiable->getMorphClass() : null,
            'notifiable_id' => $notifiable?->getKey(),
        ]);

        try {
            $messageId = $provider->sendMessage($to, $message);
            $log->markSent($messageId);
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error('WhatsApp send failed', [
                'to' => $to,
                'type' => $messageType,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send a template message and log it.
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters,
        string $messageType,
        ?Model $notifiable = null,
    ): NotificationLog {
        $provider = $this->provider();

        $log = NotificationLog::create([
            'to_phone' => $to,
            'message_type' => $messageType,
            'payload' => ['template' => $templateName, 'parameters' => $parameters],
            'provider' => $provider->providerName(),
            'status' => 'pending',
            'notifiable_type' => $notifiable ? $notifiable->getMorphClass() : null,
            'notifiable_id' => $notifiable?->getKey(),
        ]);

        try {
            $messageId = $provider->sendTemplate($to, $templateName, $parameters);
            $log->markSent($messageId);
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error('WhatsApp template send failed', [
                'to' => $to,
                'template' => $templateName,
                'type' => $messageType,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Check rate limit: max 1 message per type per phone per day.
     */
    public function canSend(string $phone, string $messageType): bool
    {
        return ! NotificationLog::wasRecentlySent($phone, $messageType, 24);
    }

    /**
     * Reset cached provider (useful after settings change).
     */
    public function reset(): void
    {
        $this->provider = null;
    }
}
