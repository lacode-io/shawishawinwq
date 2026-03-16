<?php

namespace App\Services\WhatsApp;

interface WhatsAppContract
{
    /**
     * Send a text message via WhatsApp.
     *
     * @return string|null Provider message ID on success
     */
    public function sendMessage(string $to, string $message): ?string;

    /**
     * Send a template message (for approved templates).
     *
     * @param  array<string, string>  $parameters
     * @return string|null Provider message ID on success
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = []): ?string;

    /**
     * Get the provider name identifier.
     */
    public function providerName(): string;
}
