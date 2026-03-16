<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class FakeWhatsAppProvider implements WhatsAppContract
{
    /** @var array<int, array{to: string, message: string}> */
    public static array $sent = [];

    public function sendMessage(string $to, string $message): ?string
    {
        $id = 'fake_'.uniqid();

        static::$sent[] = [
            'to' => $to,
            'message' => $message,
            'id' => $id,
        ];

        Log::info('FakeWhatsApp: message sent', ['to' => $to, 'message' => $message]);

        return $id;
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): ?string
    {
        return $this->sendMessage($to, "template:{$templateName} ".json_encode($parameters));
    }

    public function providerName(): string
    {
        return 'fake';
    }

    public static function reset(): void
    {
        static::$sent = [];
    }
}
