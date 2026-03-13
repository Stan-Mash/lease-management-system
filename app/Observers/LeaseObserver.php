<?php

namespace App\Observers;

use App\Jobs\SendSignedConfirmationsJob;
use App\Models\Lease;
use App\Models\User;
use App\Notifications\LeaseTenantSignedNotification;
use App\Models\LeaseTemplate;
use App\Services\DashboardStatsService;
use App\Services\QRCodeService;
use App\Services\SerialNumberService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Storage;

class LeaseObserver
{
    /**
     * Handle the Lease "creating" event.
     * Generate serial number when lease is being created.
     */
    public function creating(Lease $lease): void
    {
        if (empty($lease->unit_code) && $lease->unit_id) {
            $unit = \App\Models\Unit::find($lease->unit_id);
            if ($unit?->unit_code) {
                $lease->unit_code = $unit->unit_code;
            }
        }

        // Auto-assign default template when none is set (prevents NULL lease_template_id)
        if (empty($lease->lease_template_id) && $lease->lease_type) {
            $defaultTemplate = LeaseTemplate::where('template_type', $lease->lease_type)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($defaultTemplate) {
                $lease->lease_template_id = $defaultTemplate->id;

                Log::info('Default template auto-assigned to lease', [
                    'lease_type' => $lease->lease_type,
                    'template_id' => $defaultTemplate->id,
                    'template_name' => $defaultTemplate->name,
                ]);
            }
        }

        // Auto-generate serial number if enabled and not already set
        if (config('lease.serial_number.auto_generate', true) && empty($lease->serial_number)) {
            try {
                $prefix = config('lease.serial_number.prefix', 'LSE');
                $lease->serial_number = SerialNumberService::generateUnique($prefix);

                Log::info('Serial number generated for lease', [
                    'serial_number' => $lease->serial_number,
                    'reference_number' => $lease->reference_number,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to generate serial number for lease', [
                    'reference_number' => $lease->reference_number,
                    'error' => $e->getMessage(),
                ]);
                // Don't block lease creation if serial generation fails
            }
        }
    }

    /**
     * Handle the Lease "created" event.
     * Generate QR code after lease is created.
     */
    public function created(Lease $lease): void
    {
        // Auto-generate QR code if enabled
        if (config('lease.qr_codes.auto_generate', true)) {
            $this->generateQRCode($lease);
        }
    }

    /**
     * Handle the Lease "updated" event.
     * Regenerate QR code when workflow state changes to approved.
     */
    public function updated(Lease $lease): void
    {
        // Invalidate dashboard stat caches and navigation badge whenever
        // workflow_state changes — ensures dashboards and sidebar counts stay current.
        if ($lease->wasChanged('workflow_state')) {
            DashboardStatsService::invalidate($lease->zone_id);
            Cache::forget('lease_navigation_badge_count');
        }

        // If workflow state changed to 'approved', ensure QR code exists
        if ($lease->wasChanged('workflow_state') && $lease->workflow_state === 'approved') {
            if (config('lease.qr_codes.auto_generate', true)) {
                // Ensure serial number exists
                if (empty($lease->serial_number)) {
                    try {
                        $prefix = config('lease.serial_number.prefix', 'LSE');
                        $lease->serial_number = SerialNumberService::generateUnique($prefix);
                        $lease->saveQuietly(); // Save without triggering events again

                        Log::info('Serial number generated on approval', [
                            'lease_id' => $lease->id,
                            'serial_number' => $lease->serial_number,
                        ]);
                    } catch (Exception $e) {
                        Log::error('Failed to generate serial number on approval', [
                            'lease_id' => $lease->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Regenerate QR code to ensure it's up to date
                $this->generateQRCode($lease);
            }
        }

        // If serial number was manually changed, regenerate QR code
        if ($lease->wasChanged('serial_number') && config('lease.qr_codes.auto_generate', true)) {
            $this->generateQRCode($lease);
        }

        // When a tenant completes their digital signature, notify the zone manager (or admins)
        // so they know to review and countersign. The tenant does NOT get their copy yet.
        if ($lease->wasChanged('workflow_state') && $lease->workflow_state === 'tenant_signed') {
            try {
                $this->notifyManagerTenantSigned($lease);
            } catch (Exception $e) {
                Log::warning('Failed to notify manager of tenant signing', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // When the final advocate certifies, the lease reaches FULLY_EXECUTED.
        // Immediately attempt to flip to ACTIVE if start_date has already arrived.
        // If the start_date is in the future the scheduler handles the transition daily.
        if ($lease->wasChanged('workflow_state') && $lease->workflow_state === 'fully_executed') {
            try {
                $lease->activateIfStartDatePassed();
            } catch (\Exception $e) {
                Log::warning('LeaseObserver: could not auto-activate after fully_executed', [
                    'lease_id' => $lease->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // When a digitally-signed lease becomes ACTIVE (all parties have signed and
        // the final advocate has certified), dispatch a background job to send the
        // tenant their final confirmation email + PDF.
        if ($lease->wasChanged('workflow_state') && $lease->workflow_state === 'active') {
            $hasDigitalSignature = $lease->digitalSignatures()->exists();
            if ($hasDigitalSignature) {
                SendSignedConfirmationsJob::dispatch($lease->id);

                Log::info('SendSignedConfirmationsJob dispatched for lease', [
                    'lease_id' => $lease->id,
                ]);
            }
        }
    }

    /**
     * Handle the Lease "deleting" event.
     * Clean up QR code file when lease is deleted.
     */
    public function deleting(Lease $lease): void
    {
        // Delete QR code file if exists
        if ($lease->qr_code_path) {
            try {
                Storage::disk('public')->delete($lease->qr_code_path);

                Log::info('QR code file deleted for lease', [
                    'lease_id' => $lease->id,
                    'qr_code_path' => $lease->qr_code_path,
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to delete QR code file', [
                    'lease_id' => $lease->id,
                    'qr_code_path' => $lease->qr_code_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify the zone manager (or all admins as fallback) that a tenant has signed.
     * Mirrors the same pattern used by LeaseDisputeService::notifyResponsibleParties().
     */
    protected function notifyManagerTenantSigned(Lease $lease): void
    {
        $notification = new LeaseTenantSignedNotification($lease);

        // Prefer the zone manager for this lease's zone
        $zoneManager = $lease->assignedZone?->zoneManager;

        if ($zoneManager) {
            $zoneManager->notify($notification);
            Log::info('Zone manager notified of tenant signing', [
                'lease_id' => $lease->id,
                'zone_manager_id' => $zoneManager->id,
            ]);

            return;
        }

        // Fallback: notify all super_admin / admin users
        $admins = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        Log::info('Admins notified of tenant signing (no zone manager set)', [
            'lease_id' => $lease->id,
            'admin_count' => $admins->count(),
        ]);
    }

    /**
     * Generate QR code for a lease.
     */
    protected function generateQRCode(Lease $lease): void
    {
        try {
            QRCodeService::attachToLease($lease);

            Log::info('QR code generated for lease', [
                'lease_id' => $lease->id,
                'serial_number' => $lease->serial_number,
                'reference_number' => $lease->reference_number,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to generate QR code for lease', [
                'lease_id' => $lease->id,
                'serial_number' => $lease->serial_number,
                'error' => $e->getMessage(),
            ]);
            // Don't block lease operations if QR generation fails
        }
    }
}
