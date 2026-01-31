<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseApproval extends Model
{
    protected $fillable = [
        'lease_id',
        'landlord_id',
        'reviewed_by',
        'decision',
        'comments',
        'rejection_reason',
        'reviewed_at',
        'notified_at',
        'ip_address',
        'user_agent',
        'previous_data',
        'metadata',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'notified_at' => 'datetime',
        'previous_data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the lease that was approved/rejected.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the landlord who owns the property.
     */
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    /**
     * Get the user who reviewed the lease.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if the approval was approved.
     */
    public function isApproved(): bool
    {
        return $this->decision === 'approved';
    }

    /**
     * Check if the approval was rejected.
     */
    public function isRejected(): bool
    {
        return $this->decision === 'rejected';
    }

    /**
     * Check if the approval is pending (no decision yet).
     */
    public function isPending(): bool
    {
        return $this->decision === null;
    }

    /**
     * Mark as notified.
     */
    public function markAsNotified(): void
    {
        $this->notified_at = now();
        $this->save();
    }

    /**
     * Get a formatted description of the approval.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $reviewer = $this->reviewer ? $this->reviewer->name : 'Unknown';

        if ($this->isApproved()) {
            return "{$reviewer} approved the lease" . ($this->comments ? ' with comments' : '');
        }

        if ($this->isRejected()) {
            return "{$reviewer} rejected the lease: {$this->rejection_reason}";
        }

        return 'Pending landlord approval';
    }

    /**
     * Scope for approved leases.
     */
    public function scopeApproved($query)
    {
        return $query->where('decision', 'approved');
    }

    /**
     * Scope for rejected leases.
     */
    public function scopeRejected($query)
    {
        return $query->where('decision', 'rejected');
    }

    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->whereNull('decision');
    }

    /**
     * Scope for specific landlord.
     */
    public function scopeForLandlord($query, int $landlordId)
    {
        return $query->where('landlord_id', $landlordId);
    }
}
