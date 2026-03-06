<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guarantor;
use App\Models\Lease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\SMSService;

/**
 * Isolated guarantor signing portal. Secured by signed URL + OTP.
 * Does not route to main dashboard.
 */
class GuarantorPortalController extends Controller
{
    public function show(Request $request, Lease $lease, Guarantor $guarantor): View
    {
        $this->verifySignedUrlAndGuarantor($request, $lease, $guarantor);

        if ($guarantor->signed) {
            return view('guarantor.portal.already-signed', compact('lease', 'guarantor'));
        }

        return view('guarantor.portal.signing', [
            'lease' => $lease,
            'guarantor' => $guarantor,
        ]);
    }

    public function requestOTP(Request $request, Lease $lease, Guarantor $guarantor): JsonResponse
    {
        $this->verifySignedUrlAndGuarantor($request, $lease, $guarantor);

        if ($guarantor->signed) {
            return response()->json(['success' => false, 'message' => 'You have already signed.'], 400);
        }

        $expiryMinutes = 15;
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $guarantor->update([
            'otp_token' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        $phone = $guarantor->phone ?? $guarantor->lease?->tenant?->mobile_number;
        if ($phone) {
            SMSService::sendOTP($phone, $code, $lease->reference_number ?? 'Lease', $expiryMinutes);
        }

        if ($guarantor->email) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Your guarantor verification code is: {$code}. Valid for {$expiryMinutes} minutes. Ref: " . ($lease->reference_number ?? ''),
                    fn ($m) => $m->to($guarantor->email)->subject('Guarantor verification code - Chabrin Agencies'),
                );
            } catch (\Throwable $e) {
                Log::warning('Guarantor OTP email failed', ['guarantor_id' => $guarantor->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('Guarantor OTP sent', ['lease_id' => $lease->id, 'guarantor_id' => $guarantor->id]);
        return response()->json(['success' => true, 'message' => 'OTP sent.', 'expires_in_minutes' => $expiryMinutes]);
    }

    public function verifyOTP(Request $request, Lease $lease, Guarantor $guarantor): JsonResponse
    {
        $this->verifySignedUrlAndGuarantor($request, $lease, $guarantor);

        $code = $request->input('code', '');
        if (strlen($code) !== 6 || ! $guarantor->otp_token || ! $guarantor->otp_expires_at) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP. Request a new code.'], 400);
        }
        if ($guarantor->otp_expires_at->isPast()) {
            $guarantor->update(['otp_token' => null, 'otp_expires_at' => null]);
            return response()->json(['success' => false, 'message' => 'OTP has expired. Request a new code.'], 400);
        }
        if (! Hash::check($code, $guarantor->otp_token)) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code.'], 400);
        }

        return response()->json(['success' => true, 'message' => 'OTP verified. You can now sign.']);
    }

    public function submitSignature(Request $request, Lease $lease, Guarantor $guarantor): JsonResponse
    {
        $this->verifySignedUrlAndGuarantor($request, $lease, $guarantor);

        if ($guarantor->signed) {
            return response()->json(['success' => false, 'message' => 'Already signed.'], 400);
        }

        if (! $guarantor->otp_token || ! $guarantor->otp_expires_at || $guarantor->otp_expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Please verify OTP before signing.'], 400);
        }

        $payload = $request->input('signature_data', '');
        if (! is_string($payload) || ! str_starts_with($payload, 'data:image/png;base64,')) {
            return response()->json(['success' => false, 'message' => 'Invalid signature payload.'], 422);
        }

        $decoded = base64_decode(str_replace('data:image/png;base64,', '', $payload), true);
        if ($decoded === false || strlen($decoded) < 100) {
            return response()->json(['success' => false, 'message' => 'Invalid signature image.'], 422);
        }

        $dir = 'guarantor-signatures/lease-' . $lease->id;
        $path = $dir . '/guarantor-' . $guarantor->id . '-' . Str::random(8) . '.png';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, $decoded);

        $guarantor->update([
            'signature_path' => $path,
            'otp_token' => null,
            'otp_expires_at' => null,
            'signed' => true,
            'signed_at' => now(),
        ]);

        Log::info('Guarantor signed', [
            'lease_id' => $lease->id,
            'guarantor_id' => $guarantor->id,
            'signature_path' => $path,
        ]);

        event(new \App\Events\GuarantorSigned($guarantor, $lease));

        return response()->json(['success' => true, 'message' => 'Signature saved.']);
    }

    public function viewLease(Request $request, Lease $lease, Guarantor $guarantor)
    {
        $this->verifySignedUrlAndGuarantor($request, $lease, $guarantor);
        // Stream lease PDF for review (implement via LeasePdfService when needed)
        return response()->noContent(200);
    }

    private function verifySignedUrlAndGuarantor(Request $request, Lease $lease, Guarantor $guarantor): void
    {
        $routeUrl = route('guarantor.portal.sign', ['lease' => $lease->id, 'guarantor' => $guarantor->id]);
        $baseUrl = explode('?', $routeUrl)[0];
        $signingParams = array_filter($request->only(['expires', 'signature', 'guarantor']));
        ksort($signingParams);
        $canonicalUrl = $baseUrl . '?' . http_build_query($signingParams);
        $fakeRequest = \Illuminate\Http\Request::create($canonicalUrl, 'GET');

        if (! app('url')->hasValidSignature($fakeRequest)) {
            abort(403, 'This signing link has expired or is invalid.');
        }

        if ((int) $request->get('guarantor') !== $guarantor->id || $guarantor->lease_id !== $lease->id) {
            Log::warning('Guarantor/lease mismatch in guarantor portal', [
                'lease_id' => $lease->id,
                'guarantor_id' => $guarantor->id,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Unauthorized access.');
        }
    }
}
