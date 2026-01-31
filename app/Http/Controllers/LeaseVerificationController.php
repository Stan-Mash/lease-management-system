<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Services\QRCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for public lease verification via QR codes
 */
class LeaseVerificationController extends Controller
{
    /**
     * Display lease verification page
     */
    public function show(Request $request): View
    {
        $serialNumber = $request->get('serial');
        $hash = $request->get('hash');

        $lease = null;
        $verified = false;
        $error = null;

        if ($serialNumber) {
            // Try to find lease by serial number or reference number
            $lease = Lease::where('serial_number', $serialNumber)
                ->orWhere('reference_number', $serialNumber)
                ->first();

            if ($lease) {
                // Verify hash
                if ($hash && QRCodeService::verifyHash($lease, $hash)) {
                    $verified = true;
                } else {
                    $error = 'Invalid verification code. This document may have been tampered with.';
                }
            } else {
                $error = 'Lease document not found in our system.';
            }
        }

        return view('lease.verify', [
            'lease' => $lease,
            'verified' => $verified,
            'error' => $error,
            'serialNumber' => $serialNumber,
        ]);
    }

    /**
     * API endpoint for programmatic verification
     */
    public function api(Request $request): JsonResponse
    {
        $request->validate([
            'serial' => 'required|string',
            'hash' => 'required|string',
        ]);

        $serialNumber = $request->get('serial');
        $hash = $request->get('hash');

        $lease = Lease::where('serial_number', $serialNumber)
            ->orWhere('reference_number', $serialNumber)
            ->first();

        if (! $lease) {
            return response()->json([
                'verified' => false,
                'error' => 'Lease document not found.',
            ], 404);
        }

        $verified = QRCodeService::verifyHash($lease, $hash);

        if (! $verified) {
            return response()->json([
                'verified' => false,
                'error' => 'Invalid verification code.',
            ], 401);
        }

        return response()->json([
            'verified' => true,
            'data' => [
                'serial_number' => $lease->serial_number,
                'reference_number' => $lease->reference_number,
                'lease_type' => $lease->lease_type,
                'workflow_state' => $lease->workflow_state,
                'start_date' => $lease->start_date?->format('Y-m-d'),
                'end_date' => $lease->end_date?->format('Y-m-d'),
                'property' => $lease->property?->name,
                'tenant' => $lease->tenant?->full_name,
                'verified_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
