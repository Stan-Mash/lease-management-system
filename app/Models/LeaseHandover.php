<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseHandover extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'field_officer_id',
        'checked_out_by',
        'checked_out_at',
        'checkout_status',
        'delivered_at',
        'delivery_status',
        'delivery_notes',
        'signed_at',
        'signature_obtained',
        'signature_notes',
        'returned_at',
        'received_by',
        'return_condition',
        'return_notes',
        'delivery_attempts',
        'mileage',
        'issues_encountered',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checked_out_at' => 'datetime',
        'delivered_at' => 'datetime',
        'signed_at' => 'datetime',
        'returned_at' => 'datetime',
        'signature_obtained' => 'boolean',
        'mileage' => 'decimal:2',
        'delivery_attempts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lease that this handover belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the field officer assigned.
     */
    public function fieldOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    /**
     * Get the user who checked out the document.
     */
    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    /**
     * Get the user who received the document back.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Mark the handover as checked out.
     */
    public function markAsCheckedOut(): void
    {
        $this->checked_out_at = now();
        $this->checkout_status = 'checked_out';
        $this->save();
    }

    /**
     * Record a delivery attempt.
     */
    public function recordDelivery(string $status, ?string $notes = null): void
    {
        $this->delivery_attempts++;
        $this->delivered_at = now();
        $this->delivery_status = $status;
        $this->delivery_notes = $notes;
        $this->checkout_status = 'delivered';
        $this->save();
    }

    /**
     * Record signature obtained.
     */
    public function recordSignature(?string $notes = null): void
    {
        $this->signature_obtained = true;
        $this->signed_at = now();
        $this->signature_notes = $notes;
        $this->save();
    }

    /**
     * Mark as returned to office.
     */
    public function markAsReturned(string $condition, ?string $notes = null): void
    {
        $this->returned_at = now();
        $this->return_condition = $condition;
        $this->return_notes = $notes;
        $this->checkout_status = 'returned';
        $this->received_by = auth()->id();
        $this->save();
    }

    /**
     * Scope to get checked out handovers.
     */
    public function scopeCheckedOut($query)
    {
        return $query->where('checkout_status', 'checked_out');
    }

    /**
     * Scope to get delivered handovers.
     */
    public function scopeDelivered($query)
    {
        return $query->where('checkout_status', 'delivered');
    }

    /**
     * Scope to get returned handovers.
     */
    public function scopeReturned($query)
    {
        return $query->where('checkout_status', 'returned');
    }

    /**
     * Scope to get handovers for a specific field officer.
     */
    public function scopeForFieldOfficer($query, int $fieldOfficerId)
    {
        return $query->where('field_officer_id', $fieldOfficerId);
    }

    /**
     * Scope to get active handovers (not returned).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('checkout_status', ['pending', 'checked_out', 'delivered']);
    }

    /**
     * Check if handover is still active (not returned).
     */
    public function isActive(): bool
    {
        return in_array($this->checkout_status, ['pending', 'checked_out', 'delivered']);
    }

    /**
     * Check if delivery was successful.
     */
    public function wasDeliverySuccessful(): bool
    {
        return $this->delivery_status === 'successful';
    }

    /**
     * Get the duration the handover was active (days).
     */
    public function getDurationInDays(): ?float
    {
        if (!$this->checked_out_at) {
            return null;
        }

        $endDate = $this->returned_at ?? now();
        return $this->checked_out_at->diffInDays($endDate);
    }
}
