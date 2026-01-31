<?php

namespace App\Contracts;

/**
 * Interface for SMS providers.
 * Allows swapping SMS implementations (e.g., Africa's Talking, Twilio, etc.)
 */
interface SMSProviderInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $phone The recipient phone number
     * @param string $message The message to send
     * @param array $context Additional context for logging
     *
     * @return bool True if sent successfully
     */
    public function send(string $phone, string $message, array $context = []): bool;

    /**
     * Check if the SMS provider is configured.
     *
     * @return bool True if API credentials are configured
     */
    public function isConfigured(): bool;
}
