<?php

namespace App\Observers;

use App\Models\Lease;
use App\Services\QRCodeService;
use App\Services\SerialNumberService;
use Illuminate\Support\Facades\Log;

class LeaseObserver
{
    /**
     * Handle the Lease "creating" event.
     * Generate serial number when lease is being created.
     *
     * @param Lease $lease
     * @return void
     */
    public function creating(Lease $lease): void
    {
        // Auto-generate serial number if enabled and not already set
        if (config('lease.serial_number.auto_generate', true) && empty($lease->serial_number)) {
            try {
                $prefix = config('lease.serial_number.prefix', 'LSE');
                $lease->serial_number = SerialNumberService::generateUnique($prefix);

                Log::info('Serial number generated for lease', [
                    'serial_number' => $lease->serial_number,
                    'reference_number' => $lease->reference_number,
                ]);
            } catch (\Exception $e) {
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
     *
     * @param Lease $lease
     * @return void
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
     *
     * @param Lease $lease
     * @return void
     */
    public function updated(Lease $lease): void
    {
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
                    } catch (\Exception $e) {
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
    }

    /**
     * Generate QR code for a lease.
     *
     * @param Lease $lease
     * @return void
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
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for lease', [
                'lease_id' => $lease->id,
                'serial_number' => $lease->serial_number,
                'error' => $e->getMessage(),
            ]);
            // Don't block lease operations if QR generation fails
        }
    }

    /**
     * Handle the Lease "deleting" event.
     * Clean up QR code file when lease is deleted.
     *
     * @param Lease $lease
     * @return void
     */
    public function deleting(Lease $lease): void
    {
        // Delete QR code file if exists
        if ($lease->qr_code_path) {
            try {
                \Storage::disk('public')->delete($lease->qr_code_path);

                Log::info('QR code file deleted for lease', [
                    'lease_id' => $lease->id,
                    'qr_code_path' => $lease->qr_code_path,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to delete QR code file', [
                    'lease_id' => $lease->id,
                    'qr_code_path' => $lease->qr_code_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
