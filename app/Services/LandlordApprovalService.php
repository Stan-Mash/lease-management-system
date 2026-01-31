<?php

namespace App\Services;

use App\Exceptions\LeaseApprovalException;
use App\Models\Lease;
use App\Models\LeaseApproval;
use App\Notifications\LeaseApprovalRequestedNotification;
use App\Notifications\LeaseApprovedNotification;
use App\Notifications\LeaseRejectedNotification;
use Exception;
use Illuminate\Support\Facades\Log;

class LandlordApprovalService
{
    /**
     * Request approval from landlord for a lease.
     *
     * @param string $method Notification method: 'email', 'sms', 'both'
     *
     * @throws LeaseApprovalException
     */
    public static function requestApproval(Lease $lease, string $method = 'email'): array
    {
        if (! $lease->landlord) {
            throw LeaseApprovalException::noLandlord($lease->reference_number);
        }

        try {
            // Create approval request
            $approval = $lease->requestApproval();

            // Send notification to landlord
            $sent = self::sendApprovalRequest($lease, $method);

            Log::info('Landlord approval requested', [
                'lease_id' => $lease->id,
                'landlord_id' => $lease->landlord_id,
                'approval_id' => $approval->id,
                'method' => $method,
            ]);

            return [
                'success' => true,
                'approval' => $approval,
                'notification_sent' => $sent,
                'message' => 'Approval request sent to landlord successfully.',
            ];
        } catch (LeaseApprovalException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to request landlord approval', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send approval request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Approve a lease.
     *
     * @param string|null $comments Optional approval comments
     * @param string $method Notification method
     */
    public static function approveLease(Lease $lease, ?string $comments = null, string $method = 'both'): array
    {
        try {
            // Approve the lease
            $approval = $lease->approve($comments);

            // Send notification to tenant and Chabrin staff
            $sent = self::sendApprovalNotification($lease, $approval, $method);

            Log::info('Lease approved by landlord', [
                'lease_id' => $lease->id,
                'approval_id' => $approval->id,
            ]);

            return [
                'success' => true,
                'approval' => $approval,
                'notification_sent' => $sent,
                'message' => 'Lease approved successfully.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to approve lease', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to approve lease: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reject a lease.
     *
     * @param string $reason Reason for rejection
     * @param string|null $comments Optional additional comments
     * @param string $method Notification method
     */
    public static function rejectLease(
        Lease $lease,
        string $reason,
        ?string $comments = null,
        string $method = 'both',
    ): array {
        try {
            // Reject the lease
            $approval = $lease->reject($reason, $comments);

            // Send notification to tenant and Chabrin staff
            $sent = self::sendRejectionNotification($lease, $approval, $method);

            Log::info('Lease rejected by landlord', [
                'lease_id' => $lease->id,
                'approval_id' => $approval->id,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'approval' => $approval,
                'notification_sent' => $sent,
                'message' => 'Lease rejected successfully.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to reject lease', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject lease: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get approval status for a lease.
     */
    public static function getApprovalStatus(Lease $lease): array
    {
        $latestApproval = $lease->getLatestApproval();

        return [
            'has_approval' => $latestApproval !== null,
            'is_pending' => $lease->hasPendingApproval(),
            'is_approved' => $lease->hasBeenApproved(),
            'is_rejected' => $lease->hasBeenRejected(),
            'latest_approval' => $latestApproval,
            'can_request_approval' => ! $lease->hasPendingApproval() && $lease->workflow_state === 'draft',
            'can_approve' => $lease->hasPendingApproval(),
            'can_reject' => $lease->hasPendingApproval(),
        ];
    }

    /**
     * Send approval request notification to landlord.
     */
    private static function sendApprovalRequest(Lease $lease, string $method): bool
    {
        if (! $lease->landlord) {
            Log::warning('Cannot send approval request - no landlord associated', [
                'lease_id' => $lease->id,
            ]);

            return false;
        }

        try {
            // Send email
            if (in_array($method, ['email', 'both']) && $lease->landlord->email) {
                $lease->landlord->notify(new LeaseApprovalRequestedNotification($lease));
            }

            // Send SMS if method includes 'sms'
            if (in_array($method, ['sms', 'both']) && $lease->landlord->phone) {
                SMSService::sendApprovalRequest(
                    $lease->landlord->phone,
                    $lease->reference_number,
                    $lease->tenant->name ?? 'Unknown',
                    (float) $lease->monthly_rent,
                );
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send approval request notification', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send approval notification.
     */
    private static function sendApprovalNotification(
        Lease $lease,
        LeaseApproval $approval,
        string $method,
    ): bool {
        try {
            // Notify tenant via email
            if (in_array($method, ['email', 'both']) && $lease->tenant?->email) {
                $lease->tenant->notify(new LeaseApprovedNotification($lease, $approval));
            }

            // Notify tenant via SMS
            if (in_array($method, ['sms', 'both']) && $lease->tenant?->phone) {
                SMSService::sendApprovalNotification(
                    $lease->tenant->phone,
                    $lease->reference_number,
                );
            }

            // Mark approval as notified
            $approval->markAsNotified();

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send approval notification', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send rejection notification.
     */
    private static function sendRejectionNotification(
        Lease $lease,
        LeaseApproval $approval,
        string $method,
    ): bool {
        try {
            // Notify tenant via email
            if (in_array($method, ['email', 'both']) && $lease->tenant?->email) {
                $lease->tenant->notify(new LeaseRejectedNotification($lease, $approval));
            }

            // Notify tenant via SMS
            if (in_array($method, ['sms', 'both']) && $lease->tenant?->phone) {
                SMSService::sendRejectionNotification(
                    $lease->tenant->phone,
                    $lease->reference_number,
                    $approval->rejection_reason ?? 'Not specified',
                );
            }

            // Mark approval as notified
            $approval->markAsNotified();

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send rejection notification', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
