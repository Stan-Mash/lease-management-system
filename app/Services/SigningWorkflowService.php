<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Notifications\LeaseTenantSignedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Enforces signingSequenceByLeaseType() and advances lease state after each signature.
 */
class SigningWorkflowService
{
    /** Map signer role to sequence key (tenant, advocate, witness, landlord_pm, pm). */
    public const SIGNER_PM = 'pm';
    public const SIGNER_LANDLORD_PM = 'landlord_pm';
    public const SIGNER_TENANT = 'tenant';
    public const SIGNER_ADVOCATE = 'advocate';
    public const SIGNER_WITNESS = 'witness';

    /**
     * Normalize lease_type to a key in signingSequenceByLeaseType().
     */
    public static function sequenceKeyForLease(Lease $lease): string
    {
        $type = $lease->lease_type ?? 'residential_major';
        return match ($type) {
            'commercial' => 'commercial',
            'residential_micro' => 'residential_micro',
            'residential_major', 'residential' => 'residential_major',
            default => 'residential_major',
        };
    }

    /**
     * Get signing sequence for this lease.
     *
     * @return array<int, string>
     */
    public static function getSequence(Lease $lease): array
    {
        $key = self::sequenceKeyForLease($lease);
        $sequences = LeaseWorkflowState::signingSequenceByLeaseType();

        return $sequences[$key] ?? $sequences['residential_major'];
    }

    /**
     * Map "next party" role to workflow state (awaiting that party).
     */
    public static function stateForNextParty(?string $nextParty): ?LeaseWorkflowState
    {
        if ($nextParty === null) {
            return LeaseWorkflowState::ACTIVE;
        }

        return match ($nextParty) {
            'witness' => LeaseWorkflowState::PENDING_WITNESS,
            'advocate' => LeaseWorkflowState::PENDING_ADVOCATE,
            'landlord_pm', 'pm' => LeaseWorkflowState::PENDING_DEPOSIT,
            default => null,
        };
    }

    /**
     * After a party signs, advance lease to next state and notify next party.
     *
     * @param string $signerRole One of: tenant, advocate, witness, landlord_pm, pm
     */
    public static function advanceAfterSignature(Lease $lease, string $signerRole): void
    {
        $sequence = self::getSequence($lease);
        $normalized = $signerRole === 'pm' ? 'pm' : $signerRole;
        $idx = array_search($normalized, $sequence, true);
        if ($idx === false) {
            $idx = array_search($signerRole === self::SIGNER_LANDLORD_PM ? 'landlord_pm' : $signerRole, $sequence, true);
        }
        if ($idx === false) {
            Log::warning('SigningWorkflowService: signer not in sequence', [
                'lease_id' => $lease->id,
                'signer_role' => $signerRole,
                'sequence' => $sequence,
            ]);
            return;
        }

        $nextParty = $sequence[$idx + 1] ?? null;
        $newState = self::stateForNextParty($nextParty);

        if ($newState && $lease->canTransitionTo($newState)) {
            $lease->transitionTo($newState);
            Log::info('SigningWorkflowService: advanced after signature', [
                'lease_id' => $lease->id,
                'signer' => $signerRole,
                'next_party' => $nextParty,
                'new_state' => $newState->value,
            ]);
        }

        self::notifyNextParty($lease, $nextParty);
    }

    /**
     * Send secure link / notification to next party when applicable.
     */
    protected static function notifyNextParty(Lease $lease, ?string $nextParty): void
    {
        if ($nextParty === null) {
            return;
        }

        if ($nextParty === 'pm' || $nextParty === 'landlord_pm') {
            $zoneManager = $lease->assignedZone?->zoneManager;
            if ($zoneManager instanceof \App\Models\User) {
                $zoneManager->notify(new LeaseTenantSignedNotification($lease));
                return;
            }
            /** @var \App\Models\User $admin */
            foreach (\App\Models\User::whereIn('role', ['super_admin', 'admin'])->get() as $admin) {
                $admin->notify(new LeaseTenantSignedNotification($lease));
            }
            return;
        }

        if ($nextParty === 'advocate' || $nextParty === 'witness') {
            Log::info('SigningWorkflowService: next party advocate/witness – send link when implemented', [
                'lease_id' => $lease->id,
                'next_party' => $nextParty,
            ]);
        }
    }
}
