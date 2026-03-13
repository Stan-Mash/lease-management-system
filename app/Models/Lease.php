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
        'countersigned_by',
        'countersigned_at',
        'countersign_notes',
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
        'rent_review_years',
        'rent_review_rate',
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
        'signing_link_expires_at',
        'signing_link_expired_alerted_at',
        'lessee_witness_name',
        'lessee_witness_id',
        'lessor_witness_name',
        'lessor_witness_id',
        'tenant_advocate_name',
        'tenant_advocate_email',
        'lessee_advocate_phone',
        'lessor_advocate_name',
        'lessor_advocate_email',
        'lessor_advocate_phone',
        'signing_route',
        'fully_executed_at',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'rent_review_years' => 'integer',
        'rent_review_rate' => 'decimal:2',
        'deposit_verified' => 'boolean',
        'deposit_verified_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_periodic' => 'boolean',
        'requires_lawyer' => 'boolean',
        'requires_guarantor' => 'boolean',
        'qr_generated_at' => 'datetime',
        'date_created' => 'datetime',
        'signing_link_expires_at' => 'datetime',
        'signing_link_expired_alerted_at' => 'datetime',
        'fully_executed_at' => 'datetime',
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

    public function witnesses(): HasMany
    {
        return $this->hasMany(LeaseWitness::class);
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

    /**
     * Duration in months for PDF/view (Grant of Lease). Uses lease_term_months or computes from start_date/end_date.
     */
    public function getDurationMonthsAttribute(): ?int
    {
        if (isset($this->attributes['lease_term_months']) && (int) $this->attributes['lease_term_months'] > 0) {
            return (int) $this->attributes['lease_term_months'];
        }
        $start = $this->start_date;
        $end = $this->end_date;
        if ($start && $end && $end->gte($start)) {
            return (int) $start->diffInMonths($end, false) ?: 1;
        }
        return null;
    }

    /**
     * Human-readable duration for "Grant of Lease" (e.g. "63 months" or "5 years and 3 months").
     */
    public function getComputedDurationForDisplayAttribute(): string
    {
        $months = $this->duration_months;
        if ($months === null || $months < 1) {
            return 'as agreed';
        }
        if ($months >= 12) {
            $years = (int) floor($months / 12);
            $remainder = $months % 12;
            $parts = [$years . ' year' . ($years !== 1 ? 's' : '')];
            if ($remainder > 0) {
                $parts[] = $remainder . ' month' . ($remainder !== 1 ? 's' : '');
            }
            return implode(' and ', $parts);
        }
        return $months . ' month' . ($months !== 1 ? 's' : '');
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
     * Scope: Filter leases accessible by the authenticated user.
     * Mirrors LeasePolicy: admins see all; field officers see assigned only; others by zone.
     */
    public function scopeAccessibleByUser($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $query;
        }

        // Field officers: only leases assigned to them
        if ($user->isFieldOfficer()) {
            return $query->where('assigned_field_officer_id', $user->id);
        }

        // Zone managers, auditors, etc.: leases in their zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

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

    /*
    |--------------------------------------------------------------------------
    | Signing Route Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Route 1: the property owner (landlord) signs as the lessor party.
     * Sequence: Tenant+Witness → Advocate → Landlord+Witness → Advocate → FULLY_EXECUTED
     */
    public function usesLandlordRoute(): bool
    {
        return ($this->signing_route ?? 'manager') === 'landlord';
    }

    /**
     * Route 2 (default): Chabrin manager countersigns as the lessor party.
     * Sequence: Tenant+Witness → Advocate → Manager+Witness → Advocate → FULLY_EXECUTED
     */
    public function usesManagerRoute(): bool
    {
        return ($this->signing_route ?? 'manager') === 'manager';
    }

    /**
     * Returns a human-readable label for the current signing route.
     */
    public function signingRouteLabel(): string
    {
        return $this->usesLandlordRoute() ? 'Landlord Signing' : 'Manager Countersign';
    }

    /**
     * Activate the lease immediately if its start_date has already arrived.
     * Called after the lease reaches FULLY_EXECUTED state.
     * Returns true if the lease was activated.
     */
    public function activateIfStartDatePassed(): bool
    {
        $startDate = $this->start_date;
        if (! $startDate) {
            // No start date set — activate immediately (the tenancy is already live)
            if ($this->canTransitionTo(LeaseWorkflowState::ACTIVE)) {
                $this->transitionTo(LeaseWorkflowState::ACTIVE);
                return true;
            }
            return false;
        }

        $today = now(config('app.timezone'))->startOfDay();
        if ($startDate->lte($today) && $this->canTransitionTo(LeaseWorkflowState::ACTIVE)) {
            $this->transitionTo(LeaseWorkflowState::ACTIVE);
            return true;
        }

        return false;
    }
}
