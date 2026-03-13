<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseLawyerTracking;
use App\Notifications\LeaseSentToLawyerNotification;
use App\Notifications\LeaseTenantSignedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Enforces the two signing routes and advances lease state after each signature.
 *
 * Route 1 (landlord): tenant → advocate → landlord → advocate → fully_executed
 * Route 2 (manager):  tenant → advocate → manager  → advocate → fully_executed
 *
 * Witness signatures are captured in the same portal step as the respective
 * party and do not occupy a separate sequence slot.
 */
class SigningWorkflowService
{
    /** Signer role constants used throughout the workflow */
    public const SIGNER_PM          = 'pm';
    public const SIGNER_LANDLORD_PM = 'landlord_pm';
    public const SIGNER_TENANT      = 'tenant';
    public const SIGNER_ADVOCATE    = 'advocate';
    public const SIGNER_WITNESS     = 'witness';
    public const SIGNER_LANDLORD    = 'landlord';
    public const SIGNER_MANAGER     = 'manager';

    /**
     * Get the signing sequence for a lease based on its signing_route.
     * Falls back to a legacy lease-type-based sequence for old leases that
     * have no signing_route set.
     *
     * @return array<int, string>
     */
    public static function getSequence(Lease $lease): array
    {
        $route = $lease->signing_route ?? 'manager';

        // New route-based sequences (preferred)
        $sequences = LeaseWorkflowState::signingSequenceByRoute();
        if (isset($sequences[$route])) {
            return $sequences[$route];
        }

        // Legacy fallback for leases created before signing_route was introduced
        $key = match ($lease->lease_type ?? 'residential_major') {
            'commercial'        => 'commercial',
            'residential_micro' => 'residential_micro',
            default             => 'residential_major',
        };
        $legacy = LeaseWorkflowState::signingSequenceByLeaseType();

        return $legacy[$key] ?? $legacy['residential_major'];
    }

    /**
     * Map "next party" role to the workflow state that waits for that party.
     * null (end of sequence) → FULLY_EXECUTED (second advocate just certified).
     */
    public static function stateForNextParty(?string $nextParty): ?LeaseWorkflowState
    {
        if ($nextParty === null) {
            return LeaseWorkflowState::FULLY_EXECUTED;
        }

        return match ($nextParty) {
            'witness'                               => LeaseWorkflowState::PENDING_WITNESS,
            'advocate'                              => LeaseWorkflowState::PENDING_ADVOCATE,
            'landlord', 'landlord_pm', 'pm', 'manager' => LeaseWorkflowState::PENDING_LANDLORD_PM,
            default                                => null,
        };
    }

    /**
     * After a party signs, advance the lease to the next workflow state
     * and notify the next party in the sequence.
     *
     * @param string $signerRole One of: tenant, advocate, witness, landlord, manager, landlord_pm, pm
     */
    public static function advanceAfterSignature(Lease $lease, string $signerRole): void
    {
        $sequence = self::getSequence($lease);

        // Normalise legacy role aliases
        $normalized = match ($signerRole) {
            'landlord_pm', 'pm' => $lease->usesLandlordRoute() ? 'landlord' : 'manager',
            default             => $signerRole,
        };

        // Find the current signer's position in the sequence.
        // When advocate appears twice, find the LAST occurrence that has not yet
        // been satisfied (i.e. current state corresponds to PENDING_ADVOCATE after
        // the lessor has signed).
        $idx = false;
        if ($normalized === 'advocate') {
            // Walk from the end so the second advocate cert is found when applicable.
            for ($i = count($sequence) - 1; $i >= 0; $i--) {
                if ($sequence[$i] === 'advocate') {
                    $idx = $i;
                    break;
                }
            }
            // If lessor has NOT yet signed, the FIRST advocate cert is the current step.
            // Detect this by checking whether the workflow state is PENDING_LANDLORD_PM or beyond.
            $postLessorStates = [
                LeaseWorkflowState::PENDING_LANDLORD_PM->value,
                LeaseWorkflowState::FULLY_EXECUTED->value,
                LeaseWorkflowState::ACTIVE->value,
            ];
            $lessorHasSigned = in_array($lease->workflow_state, $postLessorStates, true)
                || $lease->countersigned_at !== null
                || ($lease->usesLandlordRoute() && $lease->leaseApprovals()->where('status', 'approved')->exists());
            if (! $lessorHasSigned) {
                // First advocate cert — find the FIRST occurrence
                for ($i = 0; $i < count($sequence); $i++) {
                    if ($sequence[$i] === 'advocate') {
                        $idx = $i;
                        break;
                    }
                }
            }
        } else {
            $idx = array_search($normalized, $sequence, true);
        }

        if ($idx === false) {
            Log::warning('SigningWorkflowService: signer not found in sequence', [
                'lease_id'    => $lease->id,
                'signer_role' => $signerRole,
                'normalized'  => $normalized,
                'sequence'    => $sequence,
            ]);
            return;
        }

        $nextParty = $sequence[$idx + 1] ?? null;
        $newState  = self::stateForNextParty($nextParty);

        if ($newState && $lease->canTransitionTo($newState)) {
            $lease->transitionTo($newState);

            // After FULLY_EXECUTED, stamp the timestamp and auto-activate if start_date passed
            if ($newState === LeaseWorkflowState::FULLY_EXECUTED) {
                $lease->update(['fully_executed_at' => now()]);
                $lease->activateIfStartDatePassed();
            }

            Log::info('SigningWorkflowService: advanced after signature', [
                'lease_id'   => $lease->id,
                'signer'     => $signerRole,
                'next_party' => $nextParty,
                'new_state'  => $newState->value,
            ]);
        }

        // Determine which side the next advocate certification belongs to.
        // After tenant signs → lessee advocate. After lessor signs → lessor advocate.
        $advocateSide = in_array($normalized, ['manager', 'landlord'], true) ? 'lessor' : 'lessee';

        self::notifyNextParty($lease, $nextParty, $advocateSide);
    }

    /**
     * Send the appropriate notification/link to the next party in the sequence.
     *
     * @param  string  $advocateSide  'lessee'|'lessor' — used when nextParty is 'advocate'
     */
    protected static function notifyNextParty(Lease $lease, ?string $nextParty, string $advocateSide = 'lessee'): void
    {
        if ($nextParty === null) {
            // End of sequence — lease is FULLY_EXECUTED (or ACTIVE if activated).
            return;
        }

        // Lessor signed → notify manager/zone manager that it's their turn
        // OR tenant signed → notify manager/zone manager
        if (in_array($nextParty, ['manager', 'landlord', 'landlord_pm', 'pm'], true)) {
            self::notifyAdminOrZoneManager($lease);
            return;
        }

        // Advocate needs to certify — send a fresh portal link for the correct side
        if ($nextParty === 'advocate') {
            self::sendAdvocatePortalLink($lease, $advocateSide);
            return;
        }

        if ($nextParty === 'witness') {
            Log::info('SigningWorkflowService: witness step — witness signed with tenant on same portal', [
                'lease_id' => $lease->id,
            ]);
        }
    }

    /**
     * Notify the zone manager (or fall back to admins) that action is needed.
     */
    protected static function notifyAdminOrZoneManager(Lease $lease): void
    {
        $zoneManager = $lease->assignedZone?->zoneManager;
        if ($zoneManager instanceof \App\Models\User) {
            $zoneManager->notify(new LeaseTenantSignedNotification($lease));
            return;
        }
        /** @var \App\Models\User $admin */
        foreach (\App\Models\User::whereIn('role', ['super_admin', 'admin'])->get() as $admin) {
            $admin->notify(new LeaseTenantSignedNotification($lease));
        }
    }

    /**
     * Create a new LeaseLawyerTracking record and send the advocate a fresh portal link.
     *
     * This is called:
     * (a) After tenant signs — first advocate certification.
     * (b) After lessor (landlord/manager) signs — second advocate certification.
     *     The advocate gets a new token so they can view the updated PDF
     *     (which now includes both tenant and lessor signatures).
     */
    protected static function sendAdvocatePortalLink(Lease $lease, string $side = 'lessee'): void
    {
        // Resolve advocate contact based on which side needs certifying.
        // lessee side → tenant_advocate_email / lessee_advocate_phone
        // lessor side → lessor_advocate_email / lessor_advocate_phone
        if ($side === 'lessor') {
            $advocateEmail = $lease->lessor_advocate_email;
            $advocateName  = $lease->lessor_advocate_name;
            $advocatePhone = $lease->lessor_advocate_phone;
        } else {
            $advocateEmail = $lease->tenant_advocate_email;
            $advocateName  = $lease->tenant_advocate_name;
            $advocatePhone = $lease->lessee_advocate_phone;
        }

        // Fall back to a linked Lawyer record (Chabrin's advocate) if no own-advocate contact
        $existingTracking = LeaseLawyerTracking::where('lease_id', $lease->id)
            ->where('side', $side)
            ->whereNotNull('lawyer_id')
            ->latest()
            ->first();

        $lawyerModel = null;
        if ($existingTracking?->lawyer) {
            $lawyerModel   = $existingTracking->lawyer;
            $advocateEmail = $advocateEmail ?: $lawyerModel->email;
            $advocateName  = $advocateName  ?: $lawyerModel->name;
            $advocatePhone = $advocatePhone ?: $lawyerModel->phone;
        }

        // Ultimate fallback: any Chabrin advocate linked to this lease
        if (empty($advocateEmail)) {
            $anyTracking = LeaseLawyerTracking::where('lease_id', $lease->id)
                ->whereNotNull('lawyer_id')
                ->latest()
                ->first();
            if ($anyTracking?->lawyer) {
                $lawyerModel   = $anyTracking->lawyer;
                $advocateEmail = $lawyerModel->email;
                $advocateName  = $lawyerModel->name;
                $advocatePhone = $lawyerModel->phone;
            }
        }

        if (empty($advocateEmail)) {
            Log::warning('SigningWorkflowService: cannot send advocate portal link — no email on record', [
                'lease_id' => $lease->id,
                'side'     => $side,
            ]);
            return;
        }

        // Create a fresh tracking record (new token each time)
        $token = LeaseLawyerTracking::generateToken();

        /** @var LeaseLawyerTracking $tracking */
        $tracking = LeaseLawyerTracking::create([
            'lease_id'               => $lease->id,
            'lawyer_id'              => $lawyerModel?->id,
            'side'                   => $side,
            'sent_method'            => 'email',
            'sent_by'                => null,
            'sent_notes'             => 'Auto-sent by workflow after signing step (' . $side . ' side).',
            'status'                 => 'sent',
            'sent_at'                => now(),
            'lawyer_link_token'      => $token,
            'lawyer_link_expires_at' => now()->addDays(14),
            'sent_via_portal_link'   => true,
            'advocate_email'         => $advocateEmail,
            'advocate_phone'         => $advocatePhone,
        ]);

        // Dev redirect override
        $targetEmail = config('app.env') === 'production'
            ? $advocateEmail
            : 'stanely.macharia@chabrinagencies.co.ke';

        $dummyLawyer = $lawyerModel ?? new \App\Models\Lawyer(['name' => $advocateName ?? 'Advocate']);

        $notification = new LeaseSentToLawyerNotification(
            $lease,
            $dummyLawyer,
            $tracking,
            false, // portal link mode — no PDF attachment, advocate downloads from portal
        );

        try {
            Notification::route('mail', $targetEmail)->notifyNow($notification);

            Log::info('SigningWorkflowService: advocate portal link sent', [
                'lease_id'       => $lease->id,
                'tracking_id'    => $tracking->id,
                'email_masked'   => substr($advocateEmail, 0, 3) . '***',
            ]);
        } catch (\Throwable $e) {
            Log::error('SigningWorkflowService: failed to send advocate portal link', [
                'lease_id' => $lease->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
