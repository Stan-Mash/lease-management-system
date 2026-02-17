<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TenantEventType;
use App\Models\Tenant;
use App\Models\TenantEvent;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * TenantEventService - Central service for logging tenant CRM events.
 *
 * Provides a unified API for recording all tenant interactions across
 * the application. Use the static log() method from anywhere in the app.
 *
 * @example Basic usage:
 *   TenantEventService::log($tenant, TenantEventType::SMS, 'SMS Sent', ['message' => 'Your rent is due']);
 * @example With source model:
 *   TenantEventService::log($tenant, TenantEventType::FINANCIAL, 'Payment Received', $paymentData, $payment);
 * @example With external reference:
 *   TenantEventService::log($tenant, TenantEventType::SMS, 'SMS Sent', $data, null, [
 *       'external_reference' => 'ATXid_12345',
 *       'channel' => 'africas_talking',
 *   ]);
 */
class TenantEventService
{
    /**
     * Log a new event to the tenant's timeline.
     *
     * @param Tenant $tenant The tenant this event belongs to
     * @param TenantEventType $type The type of event
     * @param string $title Short descriptive title
     * @param array|null $body Structured event data (varies by event type)
     * @param Model|null $eventable Optional source model (Lease, Payment, etc.)
     * @param array $options Additional options: happened_at, performed_by, is_internal, etc.
     */
    public static function log(
        Tenant $tenant,
        TenantEventType $type,
        string $title,
        ?array $body = null,
        ?Model $eventable = null,
        array $options = [],
    ): TenantEvent {
        $performedBy = $options['performed_by'] ?? Auth::id();

        $event = TenantEvent::create([
            'tenant_id' => $tenant->id,
            'event_type' => $type,
            'title' => $title,
            'body' => $body,
            'eventable_type' => $eventable ? get_class($eventable) : null,
            'eventable_id' => $eventable?->getKey(),
            'happened_at' => $options['happened_at'] ?? now(),
            'performed_by' => $performedBy,
            'is_internal' => $options['is_internal'] ?? false,
            'is_pinned' => $options['is_pinned'] ?? false,
            'requires_follow_up' => $options['requires_follow_up'] ?? $type->requiresFollowUp(),
            'follow_up_at' => $options['follow_up_at'] ?? null,
            'external_reference' => $options['external_reference'] ?? null,
            'channel' => $options['channel'] ?? null,
        ]);

        Log::channel('tenant_crm')->info('Tenant event logged', [
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'event_type' => $type->value,
            'title' => $title,
        ]);

        return $event;
    }

    /**
     * Log an SMS event (sent or received).
     */
    public static function logSMS(
        Tenant $tenant,
        string $message,
        string $direction = 'outbound',
        ?string $messageId = null,
        ?string $status = null,
    ): TenantEvent {
        $title = $direction === 'outbound' ? 'SMS Sent' : 'SMS Received';

        return self::log(
            tenant: $tenant,
            type: TenantEventType::SMS,
            title: $title,
            body: [
                'message' => $message,
                'direction' => $direction,
                'phone_number' => $tenant->phone_number,
                'status' => $status,
                'message_id' => $messageId,
            ],
            options: [
                'external_reference' => $messageId,
                'channel' => 'africas_talking',
            ],
        );
    }

    /**
     * Log an email event.
     */
    public static function logEmail(
        Tenant $tenant,
        string $subject,
        ?string $bodyPreview = null,
        string $direction = 'outbound',
        ?string $messageId = null,
    ): TenantEvent {
        $title = $direction === 'outbound' ? 'Email Sent' : 'Email Received';

        return self::log(
            tenant: $tenant,
            type: TenantEventType::EMAIL,
            title: $title,
            body: [
                'subject' => $subject,
                'body_preview' => $bodyPreview ? mb_substr($bodyPreview, 0, 500) : null,
                'direction' => $direction,
                'email' => $tenant->email,
            ],
            options: [
                'external_reference' => $messageId,
            ],
        );
    }

    /**
     * Log an internal note.
     */
    public static function logNote(
        Tenant $tenant,
        string $title,
        string $content,
        bool $isInternal = true,
        bool $isPinned = false,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::NOTE,
            title: $title,
            body: [
                'content' => $content,
            ],
            options: [
                'is_internal' => $isInternal,
                'is_pinned' => $isPinned,
            ],
        );
    }

    /**
     * Log a financial event (payment, invoice, refund, etc.)
     */
    public static function logFinancial(
        Tenant $tenant,
        string $transactionType,
        float $amount,
        ?string $reference = null,
        ?Model $sourceModel = null,
        ?array $additionalData = null,
    ): TenantEvent {
        $body = [
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'currency' => 'KES',
            'reference' => $reference,
        ];

        if ($additionalData) {
            $body = array_merge($body, $additionalData);
        }

        return self::log(
            tenant: $tenant,
            type: TenantEventType::FINANCIAL,
            title: sprintf('%s: KES %s', $transactionType, number_format($amount, 2)),
            body: $body,
            eventable: $sourceModel,
            options: [
                'external_reference' => $reference,
                'channel' => 'chips',  // CHIPS integration
            ],
        );
    }

    /**
     * Log a system-generated event.
     */
    public static function logSystem(
        Tenant $tenant,
        string $title,
        ?array $data = null,
        ?Model $sourceModel = null,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::SYSTEM,
            title: $title,
            body: $data,
            eventable: $sourceModel,
            options: [
                'performed_by' => null,  // System events have no user
            ],
        );
    }

    /**
     * Log a dispute/complaint.
     */
    public static function logDispute(
        Tenant $tenant,
        string $title,
        string $description,
        ?string $category = null,
        ?DateTimeInterface $followUpAt = null,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::DISPUTE,
            title: $title,
            body: [
                'description' => $description,
                'category' => $category,
                'status' => 'open',
            ],
            options: [
                'requires_follow_up' => true,
                'follow_up_at' => $followUpAt ?? now()->addDays(3),
            ],
        );
    }

    /**
     * Log a phone call.
     */
    public static function logCall(
        Tenant $tenant,
        string $summary,
        string $direction = 'outbound',
        ?int $durationSeconds = null,
        ?string $notes = null,
        ?DateTimeInterface $followUpAt = null,
    ): TenantEvent {
        $title = $direction === 'outbound' ? 'Outbound Call' : 'Inbound Call';

        return self::log(
            tenant: $tenant,
            type: TenantEventType::CALL,
            title: $title,
            body: [
                'summary' => $summary,
                'direction' => $direction,
                'duration_seconds' => $durationSeconds,
                'notes' => $notes,
                'phone_number' => $tenant->phone_number,
            ],
            options: [
                'requires_follow_up' => $followUpAt !== null,
                'follow_up_at' => $followUpAt,
            ],
        );
    }

    /**
     * Log a site visit by field officer.
     */
    public static function logVisit(
        Tenant $tenant,
        string $purpose,
        string $outcome,
        ?string $notes = null,
        ?array $location = null,
        ?DateTimeInterface $followUpAt = null,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::VISIT,
            title: sprintf('Site Visit: %s', $purpose),
            body: [
                'purpose' => $purpose,
                'outcome' => $outcome,
                'notes' => $notes,
                'location' => $location,  // ['lat' => x, 'lng' => y] for GPS
            ],
            options: [
                'requires_follow_up' => $followUpAt !== null,
                'follow_up_at' => $followUpAt,
            ],
        );
    }

    /**
     * Log a document event (upload, signature, etc.)
     */
    public static function logDocument(
        Tenant $tenant,
        string $action,
        string $documentName,
        ?Model $document = null,
        ?array $metadata = null,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::DOCUMENT,
            title: sprintf('%s: %s', $action, $documentName),
            body: [
                'action' => $action,
                'document_name' => $documentName,
                'metadata' => $metadata,
            ],
            eventable: $document,
        );
    }

    /**
     * Log a lease lifecycle event.
     */
    public static function logLeaseEvent(
        Tenant $tenant,
        string $action,
        Model $lease,
        ?array $details = null,
    ): TenantEvent {
        return self::log(
            tenant: $tenant,
            type: TenantEventType::LEASE,
            title: sprintf('Lease %s', $action),
            body: array_merge([
                'action' => $action,
                'lease_reference' => $lease->reference ?? $lease->id,
            ], $details ?? []),
            eventable: $lease,
        );
    }

    /**
     * Bulk log events from CHIPS sync.
     *
     * @param array $transactions Array of transaction data from CHIPS
     *
     * @return int Number of events created
     */
    public static function syncFromChips(Tenant $tenant, array $transactions): int
    {
        $count = 0;

        foreach ($transactions as $transaction) {
            // Skip if already synced (check external_reference)
            $exists = TenantEvent::where('tenant_id', $tenant->id)
                ->where('external_reference', $transaction['reference'] ?? null)
                ->exists();

            if ($exists) {
                continue;
            }

            self::logFinancial(
                tenant: $tenant,
                transactionType: $transaction['type'] ?? 'Payment',
                amount: (float) ($transaction['amount'] ?? 0),
                reference: $transaction['reference'] ?? null,
                additionalData: [
                    'synced_from' => 'chips',
                    'original_date' => $transaction['date'] ?? null,
                ],
            );

            $count++;
        }

        return $count;
    }

    /**
     * Get timeline summary for a tenant.
     */
    public static function getTimelineSummary(Tenant $tenant, int $days = 30): array
    {
        $events = $tenant->events()
            ->lastDays($days)
            ->get();

        return [
            'total_events' => $events->count(),
            'by_type' => $events->groupBy(fn ($e) => $e->event_type->value)
                ->map->count()
                ->all(),
            'pending_follow_ups' => $events->where('requires_follow_up', true)
                ->whereNull('resolved_at')
                ->count(),
            'last_contact' => $events->whereIn('event_type', [
                TenantEventType::SMS,
                TenantEventType::EMAIL,
                TenantEventType::CALL,
            ])->max('happened_at'),
        ];
    }
}
