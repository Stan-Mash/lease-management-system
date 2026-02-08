<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum representing all possible lease workflow states.
 * Centralizes workflow state definitions to avoid magic strings.
 */
enum LeaseWorkflowState: string
{
    case DRAFT = 'draft';
    case RECEIVED = 'received';
    case PENDING_LANDLORD_APPROVAL = 'pending_landlord_approval';
    case APPROVED = 'approved';
    case PRINTED = 'printed';
    case CHECKED_OUT = 'checked_out';
    case SENT_DIGITAL = 'sent_digital';
    case PENDING_OTP = 'pending_otp';
    case PENDING_TENANT_SIGNATURE = 'pending_tenant_signature';
    case RETURNED_UNSIGNED = 'returned_unsigned';
    case TENANT_SIGNED = 'tenant_signed';
    case WITH_LAWYER = 'with_lawyer';
    case PENDING_UPLOAD = 'pending_upload';
    case PENDING_DEPOSIT = 'pending_deposit';
    case ACTIVE = 'active';
    case RENEWAL_OFFERED = 'renewal_offered';
    case RENEWAL_ACCEPTED = 'renewal_accepted';
    case RENEWAL_DECLINED = 'renewal_declined';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    case CANCELLED = 'cancelled';
    case DISPUTED = 'disputed';
    case ARCHIVED = 'archived';

    /**
     * Get valid transitions from this state.
     *
     * @return array<LeaseWorkflowState>
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING_LANDLORD_APPROVAL, self::APPROVED, self::CANCELLED],
            self::RECEIVED => [self::PENDING_LANDLORD_APPROVAL, self::APPROVED, self::CANCELLED],
            self::PENDING_LANDLORD_APPROVAL => [self::APPROVED, self::CANCELLED, self::DRAFT],
            self::APPROVED => [self::PRINTED, self::SENT_DIGITAL, self::CANCELLED],
            self::PRINTED => [self::CHECKED_OUT, self::CANCELLED],
            self::CHECKED_OUT => [self::PENDING_TENANT_SIGNATURE, self::RETURNED_UNSIGNED],
            self::SENT_DIGITAL => [self::PENDING_OTP, self::DISPUTED, self::CANCELLED],
            self::PENDING_OTP => [self::TENANT_SIGNED, self::DISPUTED, self::SENT_DIGITAL],
            self::PENDING_TENANT_SIGNATURE => [self::TENANT_SIGNED, self::DISPUTED, self::RETURNED_UNSIGNED],
            self::RETURNED_UNSIGNED => [self::CHECKED_OUT, self::CANCELLED],
            self::TENANT_SIGNED => [self::WITH_LAWYER, self::PENDING_UPLOAD, self::PENDING_DEPOSIT],
            self::WITH_LAWYER => [self::PENDING_UPLOAD, self::PENDING_DEPOSIT],
            self::PENDING_UPLOAD => [self::PENDING_DEPOSIT],
            self::PENDING_DEPOSIT => [self::ACTIVE],
            self::ACTIVE => [self::RENEWAL_OFFERED, self::EXPIRED, self::TERMINATED],
            self::RENEWAL_OFFERED => [self::RENEWAL_ACCEPTED, self::RENEWAL_DECLINED, self::EXPIRED],
            self::RENEWAL_ACCEPTED => [self::ACTIVE],
            self::RENEWAL_DECLINED => [self::EXPIRED],
            self::EXPIRED => [self::ARCHIVED],
            self::TERMINATED => [self::ARCHIVED],
            self::CANCELLED => [self::ARCHIVED],
            self::DISPUTED => [self::SENT_DIGITAL, self::CANCELLED],  // Can be re-sent or cancelled
            self::ARCHIVED => [],
        };
    }

    /**
     * Check if transition to given state is valid.
     */
    public function canTransitionTo(LeaseWorkflowState $newState): bool
    {
        return in_array($newState, $this->validTransitions(), true);
    }

    /**
     * Get a human-readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::RECEIVED => 'Received',
            self::PENDING_LANDLORD_APPROVAL => 'Pending Landlord Approval',
            self::APPROVED => 'Approved',
            self::PRINTED => 'Printed',
            self::CHECKED_OUT => 'Checked Out',
            self::SENT_DIGITAL => 'Sent Digital',
            self::PENDING_OTP => 'Pending OTP Verification',
            self::PENDING_TENANT_SIGNATURE => 'Pending Tenant Signature',
            self::RETURNED_UNSIGNED => 'Returned Unsigned',
            self::TENANT_SIGNED => 'Tenant Signed',
            self::WITH_LAWYER => 'With Lawyer',
            self::PENDING_UPLOAD => 'Pending Upload',
            self::PENDING_DEPOSIT => 'Pending Deposit',
            self::ACTIVE => 'Active',
            self::RENEWAL_OFFERED => 'Renewal Offered',
            self::RENEWAL_ACCEPTED => 'Renewal Accepted',
            self::RENEWAL_DECLINED => 'Renewal Declined',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
            self::CANCELLED => 'Cancelled',
            self::DISPUTED => 'Disputed',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get a color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT, self::RECEIVED => 'gray',
            self::PENDING_LANDLORD_APPROVAL, self::PENDING_OTP,
            self::PENDING_TENANT_SIGNATURE, self::PENDING_UPLOAD,
            self::PENDING_DEPOSIT => 'warning',
            self::APPROVED, self::PRINTED, self::CHECKED_OUT,
            self::SENT_DIGITAL, self::TENANT_SIGNED, self::WITH_LAWYER => 'info',
            self::ACTIVE => 'success',
            self::RENEWAL_OFFERED => 'primary',
            self::RENEWAL_ACCEPTED => 'success',
            self::RENEWAL_DECLINED => 'danger',
            self::RETURNED_UNSIGNED => 'warning',
            self::DISPUTED => 'danger',
            self::EXPIRED, self::TERMINATED, self::CANCELLED => 'danger',
            self::ARCHIVED => 'gray',
        };
    }

    /**
     * Get an icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::RECEIVED => 'heroicon-o-inbox',
            self::PENDING_LANDLORD_APPROVAL => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::PRINTED => 'heroicon-o-printer',
            self::CHECKED_OUT => 'heroicon-o-arrow-right-on-rectangle',
            self::SENT_DIGITAL => 'heroicon-o-paper-airplane',
            self::PENDING_OTP => 'heroicon-o-device-phone-mobile',
            self::PENDING_TENANT_SIGNATURE => 'heroicon-o-pencil-square',
            self::RETURNED_UNSIGNED => 'heroicon-o-arrow-uturn-left',
            self::TENANT_SIGNED => 'heroicon-o-check-badge',
            self::WITH_LAWYER => 'heroicon-o-scale',
            self::PENDING_UPLOAD => 'heroicon-o-cloud-arrow-up',
            self::PENDING_DEPOSIT => 'heroicon-o-banknotes',
            self::ACTIVE => 'heroicon-o-check',
            self::RENEWAL_OFFERED => 'heroicon-o-arrow-path',
            self::RENEWAL_ACCEPTED => 'heroicon-o-arrow-path-rounded-square',
            self::RENEWAL_DECLINED => 'heroicon-o-no-symbol',
            self::EXPIRED => 'heroicon-o-calendar-days',
            self::TERMINATED => 'heroicon-o-x-circle',
            self::CANCELLED => 'heroicon-o-trash',
            self::DISPUTED => 'heroicon-o-exclamation-triangle',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    /**
     * Check if this is an active/in-progress state.
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::RENEWAL_OFFERED,
            self::RENEWAL_ACCEPTED,
        ], true);
    }

    /**
     * Check if this is a terminal/final state.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::EXPIRED,
            self::TERMINATED,
            self::CANCELLED,
            self::ARCHIVED,
        ], true);
    }

    /**
     * Check if this state requires landlord interaction.
     */
    public function requiresLandlordAction(): bool
    {
        return $this === self::PENDING_LANDLORD_APPROVAL;
    }

    /**
     * Check if this state requires tenant interaction.
     */
    public function requiresTenantAction(): bool
    {
        return in_array($this, [
            self::PENDING_OTP,
            self::PENDING_TENANT_SIGNATURE,
            self::PENDING_DEPOSIT,
        ], true);
    }

    /**
     * Check if this state requires admin/manager action.
     */
    public function requiresAdminAction(): bool
    {
        return in_array($this, [
            self::DISPUTED,
            self::RETURNED_UNSIGNED,
        ], true);
    }

    /**
     * Check if the lease is in dispute.
     */
    public function isDisputed(): bool
    {
        return $this === self::DISPUTED;
    }

    /**
     * Get all states as options for form selects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Get states filtered by a condition.
     *
     * @return array<LeaseWorkflowState>
     */
    public static function filter(callable $filter): array
    {
        return array_filter(self::cases(), $filter);
    }

    /**
     * Get all active (non-terminal) states.
     *
     * @return array<LeaseWorkflowState>
     */
    public static function activeStates(): array
    {
        return self::filter(fn (self $state) => ! $state->isTerminal());
    }
}
