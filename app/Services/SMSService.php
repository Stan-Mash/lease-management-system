<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PreferredLanguage;
use App\Jobs\SendSMSJob;
use App\Models\Tenant;
use App\Support\PhoneFormatter;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centralized SMS service using Africa's Talking API.
 * Supports localization (English/Swahili) based on tenant preference.
 */
class SMSService
{
    /**
     * Send an SMS message (raw - no localization).
     *
     * @param string $phone The recipient phone number
     * @param string $message The message to send
     * @param array $context Additional context for logging (no sensitive data)
     *
     * @return bool True if sent successfully
     */
    public static function send(string $phone, string $message, array $context = []): bool
    {
        $apiKey = config('services.africas_talking.api_key');
        $username = config('services.africas_talking.username');

        // Validate phone number
        if (! PhoneFormatter::isValid($phone)) {
            Log::warning('Invalid phone number for SMS', [
                'phone_masked' => PhoneFormatter::mask($phone),
                ...$context,
            ]);

            return false;
        }

        // If not configured, log and return (development mode)
        if (! $apiKey || ! $username) {
            Log::warning('Africa\'s Talking not configured - SMS not sent', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'message_length' => strlen($message),
                ...$context,
            ]);

            return false;
        }

        try {
            $formattedPhone = PhoneFormatter::toInternational($phone);

            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to' => $formattedPhone,
                'message' => $message,
                'from' => config('services.africas_talking.shortcode', 'CHABRIN'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if SMS was accepted
                $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? null;

                if ($status === 'Success') {
                    Log::info('SMS sent successfully', [
                        'phone_masked' => PhoneFormatter::mask($phone),
                        'message_id' => $data['SMSMessageData']['Recipients'][0]['messageId'] ?? null,
                        ...$context,
                    ]);

                    return true;
                }

                Log::warning('SMS not accepted', [
                    'phone_masked' => PhoneFormatter::mask($phone),
                    'status' => $status,
                    ...$context,
                ]);

                return false;
            }

            Log::warning('SMS API request failed', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'status_code' => $response->status(),
                ...$context,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('SMS sending exception', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'error' => $e->getMessage(),
                ...$context,
            ]);

            return false;
        }
    }

    /**
     * Dispatch an SMS to be sent asynchronously via the queue.
     *
     * @param string $phone The recipient phone number
     * @param string $message The message to send
     * @param array $context Additional context for logging
     */
    public static function sendQueued(string $phone, string $message, array $context = []): void
    {
        SendSMSJob::dispatch($phone, $message, $context);
    }

    // =========================================================================
    // LOCALIZATION HELPERS
    // =========================================================================

    /**
     * Send a localized SMS message to a tenant.
     *
     * @param Tenant $tenant The tenant to send to
     * @param string $key The translation key (e.g., 'sms.otp_message')
     * @param array $replace Variables to replace in the message
     * @param array $context Additional logging context
     *
     * @return bool True if sent successfully
     */
    public static function sendLocalized(
        Tenant $tenant,
        string $key,
        array $replace = [],
        array $context = [],
        bool $queued = true,
    ): bool {
        $message = self::translateForTenant($tenant, $key, $replace);

        $mergedContext = array_merge($context, [
            'tenant_id' => $tenant->id,
            'language' => $tenant->preferred_language?->value ?? 'en',
        ]);

        if ($queued) {
            self::sendQueued($tenant->phone_number, $message, $mergedContext);

            return true;
        }

        return self::send($tenant->phone_number, $message, $mergedContext);
    }

    /**
     * Translate a message key for a specific tenant's language preference.
     *
     * @param Tenant $tenant The tenant
     * @param string $key The translation key
     * @param array $replace Variables to replace
     *
     * @return string The translated message
     */
    public static function translateForTenant(Tenant $tenant, string $key, array $replace = []): string
    {
        $locale = $tenant->preferred_language?->value ?? PreferredLanguage::default()->value;

        return self::translateWithLocale($locale, $key, $replace);
    }

    /**
     * Translate a message key with a specific locale.
     * Uses temporary locale switch to avoid affecting the rest of the request.
     *
     * @param string $locale The locale code (en, sw)
     * @param string $key The translation key
     * @param array $replace Variables to replace
     *
     * @return string The translated message
     */
    public static function translateWithLocale(string $locale, string $key, array $replace = []): string
    {
        $originalLocale = App::getLocale();

        try {
            App::setLocale($locale);

            return __($key, $replace);
        } finally {
            // Always restore original locale
            App::setLocale($originalLocale);
        }
    }

    // =========================================================================
    // OTP & VERIFICATION
    // =========================================================================

    /**
     * Send an OTP code via SMS (localized).
     *
     * @param Tenant $tenant The tenant to send to
     * @param string $code The OTP code
     * @param string $reference A reference number (e.g., lease reference)
     * @param int $expiryMinutes How long the OTP is valid
     *
     * @return bool True if sent successfully
     */
    public static function sendOTPToTenant(
        Tenant $tenant,
        string $code,
        string $reference,
        int $expiryMinutes = 10,
    ): bool {
        return self::sendLocalized(
            $tenant,
            'sms.otp_message',
            [
                'code' => $code,
                'minutes' => $expiryMinutes,
                'reference' => $reference,
            ],
            ['type' => 'otp', 'reference' => $reference],
            queued: false, // OTP must be sent synchronously — user is waiting
        );
    }

    /**
     * Send an OTP code via SMS (legacy - uses phone number directly).
     *
     * @param string $phone The recipient phone number
     * @param string $code The OTP code
     * @param string $reference A reference number
     * @param int $expiryMinutes How long the OTP is valid
     * @param string|null $locale Optional locale override
     *
     * @return bool True if sent successfully
     */
    public static function sendOTP(
        string $phone,
        string $code,
        string $reference,
        int $expiryMinutes = 10,
        ?string $locale = null,
    ): bool {
        // Try to find tenant by phone to get their language preference
        $tenant = Tenant::where('phone_number', $phone)->first();

        if ($tenant) {
            return self::sendOTPToTenant($tenant, $code, $reference, $expiryMinutes);
        }

        // Fallback to provided locale or default
        $message = self::translateWithLocale(
            $locale ?? PreferredLanguage::default()->value,
            'sms.otp_message',
            [
                'code' => $code,
                'minutes' => $expiryMinutes,
                'reference' => $reference,
            ],
        );

        return self::send($phone, $message, ['type' => 'otp', 'reference' => $reference]);
    }

    // =========================================================================
    // LEASE LIFECYCLE
    // =========================================================================

    /**
     * Send lease ready notification (localized).
     */
    public static function sendLeaseReady(Tenant $tenant, string $reference): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.lease_ready',
            ['reference' => $reference],
            ['type' => 'lease_ready', 'reference' => $reference],
        );
    }

    /**
     * Send lease created notification (localized).
     */
    public static function sendLeaseCreated(Tenant $tenant, string $reference): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.lease_created',
            [
                'tenant_name' => $tenant->full_name,
                'reference' => $reference,
            ],
            ['type' => 'lease_created', 'reference' => $reference],
        );
    }

    /**
     * Send a lease approval request notification to landlord.
     * Note: Landlords receive messages in English by default.
     */
    public static function sendApprovalRequest(
        string $phone,
        string $reference,
        string $tenantName,
        float $monthlyRent,
    ): bool {
        $message = self::translateWithLocale('en', 'sms.approval_request', [
            'reference' => $reference,
            'tenant_name' => $tenantName,
            'rent' => number_format($monthlyRent),
        ]);

        self::sendQueued($phone, $message, ['type' => 'approval_request', 'reference' => $reference]);

        return true;
    }

    /**
     * Send a lease approval notification to tenant (localized).
     */
    public static function sendApprovalNotification(string $phone, string $reference): bool
    {
        $tenant = Tenant::where('phone_number', $phone)->first();

        if ($tenant) {
            return self::sendLocalized(
                $tenant,
                'sms.lease_approved',
                ['reference' => $reference],
                ['type' => 'approval_notification', 'reference' => $reference],
            );
        }

        // Fallback to English
        $message = self::translateWithLocale('en', 'sms.lease_approved', ['reference' => $reference]);

        self::sendQueued($phone, $message, ['type' => 'approval_notification', 'reference' => $reference]);

        return true;
    }

    /**
     * Send a lease rejection notification to tenant (localized).
     */
    public static function sendRejectionNotification(string $phone, string $reference, string $reason): bool
    {
        $tenant = Tenant::where('phone_number', $phone)->first();

        if ($tenant) {
            return self::sendLocalized(
                $tenant,
                'sms.lease_rejected',
                ['reference' => $reference, 'reason' => $reason],
                ['type' => 'rejection_notification', 'reference' => $reference],
            );
        }

        // Fallback to English
        $message = self::translateWithLocale('en', 'sms.lease_rejected', [
            'reference' => $reference,
            'reason' => $reason,
        ]);

        self::sendQueued($phone, $message, ['type' => 'rejection_notification', 'reference' => $reference]);

        return true;
    }

    /**
     * Send lease signed confirmation (localized).
     */
    public static function sendLeaseSigned(Tenant $tenant, string $reference, string $startDate): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.lease_signed',
            [
                'reference' => $reference,
                'start_date' => $startDate,
            ],
            ['type' => 'lease_signed', 'reference' => $reference],
        );
    }

    /**
     * Send lease expiring reminder (localized).
     */
    public static function sendLeaseExpiring(Tenant $tenant, string $reference, string $expiryDate): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.lease_expiring',
            [
                'reference' => $reference,
                'expiry_date' => $expiryDate,
            ],
            ['type' => 'lease_expiring', 'reference' => $reference],
        );
    }

    // =========================================================================
    // SIGNING & DOCUMENTS
    // =========================================================================

    /**
     * Send a digital signing link via SMS (localized).
     */
    public static function sendSigningLink(string $phone, string $reference, string $link): bool
    {
        $tenant = Tenant::where('phone_number', $phone)->first();

        if ($tenant) {
            return self::sendLocalized(
                $tenant,
                'sms.signing_link',
                [
                    'reference' => $reference,
                    'link' => $link,
                    'hours' => 72,
                ],
                ['type' => 'signing_link', 'reference' => $reference],
            );
        }

        // Fallback to English
        $message = self::translateWithLocale('en', 'sms.signing_link', [
            'reference' => $reference,
            'link' => $link,
            'hours' => 72,
        ]);

        self::sendQueued($phone, $message, ['type' => 'signing_link', 'reference' => $reference]);

        return true;
    }

    /**
     * Send signing reminder (localized).
     */
    public static function sendSigningReminder(Tenant $tenant, string $reference, string $link, int $hoursRemaining): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.signing_reminder',
            [
                'reference' => $reference,
                'link' => $link,
                'hours' => $hoursRemaining,
            ],
            ['type' => 'signing_reminder', 'reference' => $reference],
        );
    }

    // =========================================================================
    // PAYMENTS & FINANCIAL
    // =========================================================================

    /**
     * Send payment received confirmation (localized).
     */
    public static function sendPaymentReceived(
        Tenant $tenant,
        float $amount,
        string $reference,
        float $balance = 0,
    ): bool {
        return self::sendLocalized(
            $tenant,
            'sms.payment_received',
            [
                'amount' => number_format($amount),
                'reference' => $reference,
                'balance' => number_format($balance),
            ],
            ['type' => 'payment_received', 'reference' => $reference],
        );
    }

    /**
     * Send payment reminder (localized).
     */
    public static function sendPaymentReminder(
        Tenant $tenant,
        float $amount,
        string $reference,
        string $dueDate,
        string $paybill,
    ): bool {
        return self::sendLocalized(
            $tenant,
            'sms.payment_reminder',
            [
                'amount' => number_format($amount),
                'reference' => $reference,
                'due_date' => $dueDate,
                'paybill' => $paybill,
            ],
            ['type' => 'payment_reminder', 'reference' => $reference],
        );
    }

    /**
     * Send payment overdue notification (localized).
     */
    public static function sendPaymentOverdue(
        Tenant $tenant,
        float $amount,
        string $reference,
        string $dueDate,
    ): bool {
        return self::sendLocalized(
            $tenant,
            'sms.payment_overdue',
            [
                'amount' => number_format($amount),
                'reference' => $reference,
                'due_date' => $dueDate,
            ],
            ['type' => 'payment_overdue', 'reference' => $reference],
        );
    }

    // =========================================================================
    // GENERAL NOTIFICATIONS
    // =========================================================================

    /**
     * Send welcome message to new tenant (localized).
     */
    public static function sendWelcome(Tenant $tenant): bool
    {
        return self::sendLocalized(
            $tenant,
            'sms.welcome',
            [
                'tenant_name' => $tenant->full_name,
                'tenant_id' => $tenant->id,
            ],
            ['type' => 'welcome'],
        );
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Check if SMS service is configured.
     */
    public static function isConfigured(): bool
    {
        return ! empty(config('services.africas_talking.api_key')) &&
               ! empty(config('services.africas_talking.username'));
    }

    /**
     * Get available languages for SMS.
     */
    public static function getAvailableLanguages(): array
    {
        return PreferredLanguage::options();
    }
}
