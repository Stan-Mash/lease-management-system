<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PreferredLanguage;
use App\Enums\TenantEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Tenant extends Model
{
    use HasFactory;
    use Notifiable;

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }

    protected $fillable = [
        'full_name',
        'id_number',
        'phone_number',
        'email',
        'notification_preference',
        'preferred_language',
        'kra_pin',
        'occupation',
        'employer_name',
        'next_of_kin_name',
        'next_of_kin_phone',
        'field_officer_id',
        'zone_manager_id',
        'zone_id',
        'date_created',
    ];

    protected $casts = [
        'preferred_language' => PreferredLanguage::class,
        'date_created' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function fieldOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function zoneManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_manager_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * All CRM timeline events for this tenant.
     */
    public function events(): HasMany
    {
        return $this->hasMany(TenantEvent::class);
    }

    /**
     * Get pinned events for this tenant.
     */
    public function pinnedEvents(): HasMany
    {
        return $this->events()->pinned()->latestFirst();
    }

    /**
     * Get events requiring follow-up.
     */
    public function pendingFollowUps(): HasMany
    {
        return $this->events()->requiresFollowUp()->latestFirst();
    }

    // =========================================================================
    // CRM HELPER METHODS
    // =========================================================================

    /**
     * Get the latest event of a specific type.
     */
    public function latestEventOfType(TenantEventType $type): ?TenantEvent
    {
        return $this->events()
            ->ofType($type)
            ->latestFirst()
            ->first();
    }

    /**
     * Get the count of unresolved disputes.
     */
    public function unresolvedDisputesCount(): int
    {
        return $this->events()
            ->ofType(TenantEventType::DISPUTE)
            ->unresolved()
            ->count();
    }

    /**
     * Check if tenant has any pending follow-ups.
     */
    public function hasPendingFollowUps(): bool
    {
        return $this->events()->requiresFollowUp()->exists();
    }

    /**
     * Get a summary of recent activity (last 30 days).
     */
    public function getRecentActivitySummary(): array
    {
        return $this->events()
            ->lastDays(30)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->all();
    }
}
