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

    public function sendDigitalSigningLink(): void
    {
        // Implementation for sending signing link
        $this->transitionTo('sent_digital');
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
}
