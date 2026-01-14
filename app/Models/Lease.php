<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'serial_number',
        'source',
        'lease_type',
        'signing_mode',
        'workflow_state',
        'tenant_id',
        'unit_id',
        'property_id',
        'landlord_id',
        'zone',
        'zone_id',
        'assigned_field_officer_id',
        'monthly_rent',
        'deposit_amount',
        'deposit_verified',
        'deposit_verified_at',
        'start_date',
        'end_date',
        'lease_term_months',
        'is_periodic',
        'requires_lawyer',
        'requires_guarantor',
        'document_version',
        'original_document_path',
        'generated_pdf_path',
        'signed_pdf_path',
        'signed_pdf_hash',
        'qr_code_data',
        'qr_code_path',
        'qr_generated_at',
        'verification_url',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_verified' => 'boolean',
        'deposit_verified_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_periodic' => 'boolean',
        'requires_lawyer' => 'boolean',
        'requires_guarantor' => 'boolean',
        'qr_generated_at' => 'datetime',
    ];

    // Valid workflow transitions
    protected static array $validTransitions = [
        'draft' => ['approved', 'cancelled'],
        'received' => ['pending_landlord_approval', 'approved', 'cancelled'],
        'pending_landlord_approval' => ['approved', 'cancelled'],
        'approved' => ['printed', 'sent_digital', 'cancelled'],
        'printed' => ['checked_out', 'cancelled'],
        'checked_out' => ['pending_tenant_signature', 'returned_unsigned'],
        'sent_digital' => ['pending_otp', 'cancelled'],
        'pending_otp' => ['tenant_signed', 'sent_digital'],
        'pending_tenant_signature' => ['tenant_signed', 'returned_unsigned'],
        'returned_unsigned' => ['checked_out', 'cancelled'],
        'tenant_signed' => ['with_lawyer', 'pending_upload', 'pending_deposit'],
        'with_lawyer' => ['pending_upload', 'pending_deposit'],
        'pending_upload' => ['pending_deposit'],
        'pending_deposit' => ['active'],
        'active' => ['renewal_offered', 'expired', 'terminated'],
        'renewal_offered' => ['active', 'expired'],
        'expired' => ['archived'],
        'terminated' => ['archived'],
        'cancelled' => ['archived'],
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(LeaseEdit::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(LeaseHandover::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(LeaseAuditLog::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(LeaseEscalation::class);
    }

    public function digitalSignatures(): HasMany
    {
        return $this->hasMany(DigitalSignature::class);
    }

    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OTPVerification::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaseApproval::class);
    }

    public function assignedZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function assignedFieldOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_field_officer_id');
    }

    // Workflow Methods
    public function canTransitionTo(string $newState): bool
    {
        $allowedTransitions = self::$validTransitions[$this->workflow_state] ?? [];
        return in_array($newState, $allowedTransitions);
    }

    public function transitionTo(string $newState): bool
    {
        if (!$this->canTransitionTo($newState)) {
            throw new \Exception(
                "Invalid transition from '{$this->workflow_state}' to '{$newState}'"
            );
        }

        $oldState = $this->workflow_state;
        $this->workflow_state = $newState;
        $this->save();

        // Log the transition
        $this->auditLogs()->create([
            'action' => 'state_transition',
            'old_state' => $oldState,
            'new_state' => $newState,
            'user_id' => auth()->id(),
            'user_role_at_time' => auth()->user()?->roles?->first()?->name ?? 'unknown',
            'ip_address' => request()->ip(),
            'description' => "Transitioned from {$oldState} to {$newState}",
        ]);

        return true;
    }

    public function sendDigitalSigningLink(string $method = 'both'): array
    {
        return \App\Services\DigitalSigningService::initiate($this, $method);
    }

    public function hasDigitalSignature(): bool
    {
        return $this->digitalSignatures()->exists();
    }

    public function getLatestDigitalSignature(): ?DigitalSignature
    {
        return $this->digitalSignatures()->orderBy('signed_at', 'desc')->first();
    }

    public function markAsPrinted(): void
    {
        $this->transitionTo('printed');

        // Log print event
        $this->auditLogs()->create([
            'action' => 'printed',
            'old_state' => 'approved',
            'new_state' => 'printed',
            'user_id' => auth()->id(),
            'user_role_at_time' => auth()->user()?->roles?->first()?->name ?? 'unknown',
            'ip_address' => request()->ip(),
            'additional_data' => ['workstation' => gethostname()],
            'description' => "Lease printed at workstation",
        ]);
    }

    /**
     * Record an edit to a landlord lease.
     *
     * @param string $editType One of: clause_added, clause_removed, clause_modified, other
     * @param string|null $sectionAffected Which clause/section was edited
     * @param string|null $originalText Text before edit (null if new)
     * @param string|null $newText Text after edit (null if removed)
     * @param string|null $reason Why the edit was made
     * @return LeaseEdit
     */
    public function recordEdit(
        string $editType,
        ?string $sectionAffected = null,
        ?string $originalText = null,
        ?string $newText = null,
        ?string $reason = null
    ): LeaseEdit {
        // Increment document version if this is the first edit in a new batch
        // For simplicity, we increment on each edit. Could be optimized for batch edits.
        $this->document_version++;
        $this->save();

        return $this->edits()->create([
            'edited_by' => auth()->id(),
            'edit_type' => $editType,
            'section_affected' => $sectionAffected,
            'original_text' => $originalText,
            'new_text' => $newText,
            'reason' => $reason,
            'document_version' => $this->document_version,
        ]);
    }

    // Landlord Approval Methods

    /**
     * Request approval from landlord.
     *
     * @return LeaseApproval
     */
    public function requestApproval(): LeaseApproval
    {
        // Transition to pending_landlord state
        $this->transitionTo('pending_landlord_approval');

        // Create approval record
        $approval = $this->approvals()->create([
            'lease_id' => $this->id,
            'landlord_id' => $this->landlord_id,
            'reviewed_by' => null,
            'decision' => null,
            'previous_data' => [
                'workflow_state' => $this->workflow_state,
                'monthly_rent' => $this->monthly_rent,
                'security_deposit' => $this->security_deposit ?? $this->deposit_amount,
                'start_date' => $this->start_date?->toDateString(),
                'end_date' => $this->end_date?->toDateString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'approval_requested',
            'old_state' => 'draft',
            'new_state' => 'pending_landlord_approval',
            'user_id' => auth()->id(),
            'user_role_at_time' => auth()->user()?->roles?->first()?->name ?? 'system',
            'ip_address' => request()->ip(),
            'description' => 'Lease submitted for landlord approval',
        ]);

        return $approval;
    }

    /**
     * Approve the lease.
     *
     * @param string|null $comments Optional approval comments
     * @return LeaseApproval
     */
    public function approve(?string $comments = null): LeaseApproval
    {
        // Get latest approval or create one
        $approval = $this->approvals()->latest()->first();

        if (!$approval) {
            $approval = $this->requestApproval();
        }

        // Update approval decision
        $approval->update([
            'decision' => 'approved',
            'comments' => $comments,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Transition lease to approved state
        $this->transitionTo('approved');

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'approved',
            'old_state' => 'pending_landlord_approval',
            'new_state' => 'approved',
            'user_id' => auth()->id(),
            'user_role_at_time' => auth()->user()?->roles?->first()?->name ?? 'system',
            'ip_address' => request()->ip(),
            'additional_data' => ['comments' => $comments],
            'description' => 'Lease approved by landlord',
        ]);

        return $approval;
    }

    /**
     * Reject the lease.
     *
     * @param string $reason Reason for rejection
     * @param string|null $comments Optional additional comments
     * @return LeaseApproval
     */
    public function reject(string $reason, ?string $comments = null): LeaseApproval
    {
        // Get latest approval or create one
        $approval = $this->approvals()->latest()->first();

        if (!$approval) {
            $approval = $this->requestApproval();
        }

        // Update approval decision
        $approval->update([
            'decision' => 'rejected',
            'rejection_reason' => $reason,
            'comments' => $comments,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Transition lease back to draft for revision
        $this->transitionTo('cancelled');

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'rejected',
            'old_state' => 'pending_landlord_approval',
            'new_state' => 'cancelled',
            'user_id' => auth()->id(),
            'user_role_at_time' => auth()->user()?->roles?->first()?->name ?? 'system',
            'ip_address' => request()->ip(),
            'additional_data' => ['rejection_reason' => $reason, 'comments' => $comments],
            'description' => "Lease rejected by landlord: {$reason}",
        ]);

        return $approval;
    }

    /**
     * Check if lease has pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvals()->pending()->exists();
    }

    /**
     * Check if lease has been approved.
     */
    public function hasBeenApproved(): bool
    {
        return $this->approvals()->approved()->exists();
    }

    /**
     * Check if lease has been rejected.
     */
    public function hasBeenRejected(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    /**
     * Get latest approval decision.
     */
    public function getLatestApproval(): ?LeaseApproval
    {
        return $this->approvals()->latest()->first();
    }

    /**
     * Scope: Filter leases by zone.
     */
    public function scopeInZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Scope: Filter leases accessible by the authenticated user based on their zone.
     */
    public function scopeAccessibleByUser($query, User $user)
    {
        // Super admins and regular admins can see all leases
        if ($user->isAdmin()) {
            return $query;
        }

        // Zone managers and field officers can only see leases in their zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

        // Default: no leases visible (safety net)
        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope: Filter leases assigned to a specific field officer.
     */
    public function scopeAssignedToFieldOfficer($query, int $fieldOfficerId)
    {
        return $query->where('assigned_field_officer_id', $fieldOfficerId);
    }
}
