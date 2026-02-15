<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Lease\MarkLeaseAsPrinted;
use App\Enums\LeaseWorkflowState;
use App\Models\Concerns\HasApprovalWorkflow;
use App\Models\Concerns\HasDigitalSigning;
use App\Models\Concerns\HasLeaseEdits;
use App\Models\Concerns\HasWorkflowState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lease model representing a rental agreement.
 *
 * @property int $id
 * @property string $reference_number
 * @property string $serial_number
 * @property string $workflow_state
 * @property int $tenant_id
 * @property int|null $landlord_id
 * @property int|null $property_id
 * @property int|null $unit_id
 * @property int|null $zone_id
 * @property int|null $created_by
 * @property float $monthly_rent
 * @property float $deposit_amount
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $document_version
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant|null $tenant
 * @property-read Landlord|null $landlord
 * @property-read Property|null $property
 * @property-read Unit|null $unit
 * @property-read User|null $createdBy
 * @property-read Zone|null $assignedZone
 */
class Lease extends Model
{
    use HasApprovalWorkflow;
    use HasDigitalSigning;
    use HasFactory;
    use HasLeaseEdits;
    use HasWorkflowState;

    protected $fillable = [
        'reference_number',
        'serial_number',
        'source',
        'lease_type',
        'lease_template_id',
        'template_version_used',
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
        'lease_reference_number',
        'unit_code',
        'zone_manager_id',
        'date_created',
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
        'date_created' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function zoneManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_manager_id');
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

    public function rentEscalations(): HasMany
    {
        return $this->hasMany(RentEscalation::class);
    }

    public function lawyerTrackings(): HasMany
    {
        return $this->hasMany(LeaseLawyerTracking::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LeaseDocument::class);
    }

    public function copyDistribution()
    {
        return $this->hasOne(LeaseCopyDistribution::class);
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(LeasePrintLog::class);
    }

    public function renewalLease()
    {
        return $this->hasOne(Lease::class, 'renewal_of_lease_id');
    }

    public function originalLease(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'renewal_of_lease_id');
    }

    public function leaseTemplate(): BelongsTo
    {
        return $this->belongsTo(LeaseTemplate::class);
    }

    public function templateAssignments(): HasMany
    {
        return $this->hasMany(LeaseTemplateAssignment::class);
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

    /*
    |--------------------------------------------------------------------------
    | Business Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark the lease as printed.
     *
     * @param string|null $workstation Optional workstation identifier
     */
    public function markAsPrinted(?string $workstation = null): void
    {
        app(MarkLeaseAsPrinted::class)->execute($this, $workstation);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

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

    /**
     * Scope: Filter leases by workflow state.
     */
    public function scopeInState($query, string|LeaseWorkflowState $state)
    {
        $stateValue = $state instanceof LeaseWorkflowState ? $state->value : $state;

        return $query->where('workflow_state', $stateValue);
    }

    /**
     * Scope: Filter active leases.
     */
    public function scopeActive($query)
    {
        return $query->where('workflow_state', LeaseWorkflowState::ACTIVE->value);
    }

    /**
     * Scope: Filter leases expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('workflow_state', LeaseWorkflowState::ACTIVE->value)
            ->where('end_date', '<=', now()->addDays($days));
    }

    /**
     * Scope: Filter leases pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('workflow_state', LeaseWorkflowState::PENDING_LANDLORD_APPROVAL->value);
    }

    /**
     * Scope: Filter leases that are in a pending state.
     */
    public function scopePending($query)
    {
        $pendingStates = [
            LeaseWorkflowState::DRAFT,
            LeaseWorkflowState::RECEIVED,
            LeaseWorkflowState::PENDING_LANDLORD_APPROVAL,
            LeaseWorkflowState::PENDING_OTP,
            LeaseWorkflowState::PENDING_TENANT_SIGNATURE,
            LeaseWorkflowState::PENDING_UPLOAD,
            LeaseWorkflowState::PENDING_DEPOSIT,
        ];

        return $query->whereIn('workflow_state', array_map(fn ($state) => $state->value, $pendingStates));
    }

    /**
     * Scope: Filter non-terminal leases.
     */
    public function scopeNotTerminal($query)
    {
        $terminalStates = array_map(
            fn ($state) => $state->value,
            array_filter(
                LeaseWorkflowState::cases(),
                fn ($state) => $state->isTerminal(),
            ),
        );

        return $query->whereNotIn('workflow_state', $terminalStates);
    }
}
