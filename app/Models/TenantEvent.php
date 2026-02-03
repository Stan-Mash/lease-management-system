<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TenantEvent - Unified timeline event for Tenant 360 CRM.
 *
 * Aggregates all tenant interactions: SMS, emails, notes, financial events,
 * system events, disputes, calls, visits, and document activities.
 *
 * @property int $id
 * @property int $tenant_id
 * @property TenantEventType $event_type
 * @property string $title
 * @property array|null $body
 * @property string|null $eventable_type
 * @property int|null $eventable_id
 * @property \Carbon\Carbon $happened_at
 * @property int|null $performed_by
 * @property bool $is_internal
 * @property bool $is_pinned
 * @property bool $requires_follow_up
 * @property \Carbon\Carbon|null $follow_up_at
 * @property \Carbon\Carbon|null $resolved_at
 * @property string|null $external_reference
 * @property string|null $channel
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Tenant $tenant
 * @property-read User|null $performer
 * @property-read Model|null $eventable
 */
class TenantEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tenant_events';

    protected $fillable = [
        'tenant_id',
        'event_type',
        'title',
        'body',
        'eventable_type',
        'eventable_id',
        'happened_at',
        'performed_by',
        'is_internal',
        'is_pinned',
        'requires_follow_up',
        'follow_up_at',
        'resolved_at',
        'external_reference',
        'channel',
    ];

    protected $casts = [
        'event_type' => TenantEventType::class,
        'body' => 'array',
        'happened_at' => 'datetime',
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
        'requires_follow_up' => 'boolean',
        'follow_up_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $attributes = [
        'is_internal' => false,
        'is_pinned' => false,
        'requires_follow_up' => false,
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * The tenant this event belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The user who performed/recorded this event.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Polymorphic relationship to the source model (Lease, Payment, etc.)
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Filter by event type.
     */
    public function scopeOfType(Builder $query, TenantEventType|string $type): Builder
    {
        $value = $type instanceof TenantEventType ? $type->value : $type;

        return $query->where('event_type', $value);
    }

    /**
     * Filter by multiple event types.
     */
    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        $values = collect($types)
            ->map(fn ($t) => $t instanceof TenantEventType ? $t->value : $t)
            ->all();

        return $query->whereIn('event_type', $values);
    }

    /**
     * Get events in chronological order (oldest first).
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('happened_at', 'asc');
    }

    /**
     * Get events in reverse chronological order (newest first).
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('happened_at', 'desc');
    }

    /**
     * Filter to pinned events only.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Filter to events requiring follow-up.
     */
    public function scopeRequiresFollowUp(Builder $query): Builder
    {
        return $query->where('requires_follow_up', true)
            ->whereNull('resolved_at');
    }

    /**
     * Filter to unresolved events.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Filter to customer-facing events only (visible to tenant).
     */
    public function scopeCustomerFacing(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    /**
     * Filter to internal events only.
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * Filter events that happened within a date range.
     */
    public function scopeHappenedBetween(Builder $query, string|\DateTimeInterface $start, string|\DateTimeInterface $end): Builder
    {
        return $query->whereBetween('happened_at', [$start, $end]);
    }

    /**
     * Filter events that happened today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('happened_at', today());
    }

    /**
     * Filter events from the last N days.
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('happened_at', '>=', now()->subDays($days));
    }

    /**
     * Search events by title or body content.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'ilike', "%{$term}%")
                ->orWhereRaw("body::text ilike ?", ["%{$term}%"]);
        });
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get a summary of the body content for display.
     */
    public function getBodySummaryAttribute(): ?string
    {
        if (empty($this->body)) {
            return null;
        }

        // Handle different body structures based on event type
        return match ($this->event_type) {
            TenantEventType::SMS => $this->body['message'] ?? null,
            TenantEventType::EMAIL => $this->body['subject'] ?? $this->body['body'] ?? null,
            TenantEventType::NOTE => $this->body['content'] ?? null,
            TenantEventType::FINANCIAL => $this->formatFinancialSummary(),
            TenantEventType::DISPUTE => $this->body['description'] ?? null,
            TenantEventType::CALL => $this->body['notes'] ?? null,
            TenantEventType::VISIT => $this->body['notes'] ?? null,
            default => $this->body['description'] ?? $this->body['message'] ?? null,
        };
    }

    /**
     * Check if this event is overdue for follow-up.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->requires_follow_up || $this->resolved_at) {
            return false;
        }

        if (! $this->follow_up_at) {
            return false;
        }

        return $this->follow_up_at->isPast();
    }

    /**
     * Get human-readable time since event.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->happened_at->diffForHumans();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Mark this event as resolved.
     */
    public function markResolved(): bool
    {
        return $this->update([
            'resolved_at' => now(),
            'requires_follow_up' => false,
        ]);
    }

    /**
     * Pin this event to the top of the timeline.
     */
    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin this event.
     */
    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    /**
     * Format financial data for display.
     */
    protected function formatFinancialSummary(): ?string
    {
        if (empty($this->body)) {
            return null;
        }

        $amount = $this->body['amount'] ?? null;
        $type = $this->body['transaction_type'] ?? 'Transaction';

        if ($amount) {
            return sprintf('%s: KES %s', $type, number_format((float) $amount, 2));
        }

        return $type;
    }
}
