<?php

namespace App\Services;

use App\Models\Lease;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

/**
 * Service for generating and managing QR codes for lease documents
 * QR codes contain verification data and can be scanned to verify document authenticity
 */
class QRCodeService
{
    /**
     * Generate QR code for a lease document
     *
     * @param Lease $lease
     * @param bool $saveToStorage Save QR code image to storage
     * @return array{data: string, url: string, svg: string, path: string|null}
     */
    public static function generateForLease(Lease $lease, bool $saveToStorage = true): array
    {
        // Generate verification URL
        $verificationUrl = route('lease.verify', [
            'serial' => $lease->serial_number ?? $lease->reference_number,
            'hash' => self::generateVerificationHash($lease),
        ]);

        // Generate QR code data payload
        $qrData = json_encode([
            'type' => 'lease_verification',
            'serial_number' => $lease->serial_number,
            'reference_number' => $lease->reference_number,
            'verification_url' => $verificationUrl,
            'generated_at' => now()->toIso8601String(),
            'tenant_name' => $lease->tenant?->full_name,
            'property_address' => $lease->property?->address,
        ]);

        // Generate QR code as SVG (scalable for PDFs)
        $qrCodeSvg = QrCode::size(300)
            ->style('round')
            ->eye('circle')
            ->margin(2)
            ->errorCorrection('H')
            ->generate($verificationUrl);

        $qrCodePath = null;

        // Optionally save as PNG to storage
        if ($saveToStorage) {
            $qrCodePng = QrCode::format('png')
                ->size(512)
                ->style('round')
                ->eye('circle')
                ->margin(2)
                ->errorCorrection('H')
                ->generate($verificationUrl);

            $filename = "qrcodes/lease-{$lease->id}-" . time() . '.png';
            Storage::disk('public')->put($filename, $qrCodePng);
            $qrCodePath = $filename;
        }

        return [
            'data' => $qrData,
            'url' => $verificationUrl,
            'svg' => $qrCodeSvg,
            'path' => $qrCodePath,
        ];
    }

    /**
     * Update lease with QR code information
     *
     * @param Lease $lease
     * @return Lease
     */
    public static function attachToLease(Lease $lease): Lease
    {
        $qrCode = self::generateForLease($lease, true);

        $lease->update([
            'qr_code_data' => $qrCode['data'],
            'qr_code_path' => $qrCode['path'],
            'qr_generated_at' => now(),
            'verification_url' => $qrCode['url'],
        ]);

        return $lease->fresh();
    }

    /**
     * Generate verification hash for security
     *
     * @param Lease $lease
     * @return string
     */
    public static function generateVerificationHash(Lease $lease): string
    {
        $data = implode('|', [
            $lease->id,
            $lease->serial_number ?? $lease->reference_number,
            $lease->created_at?->timestamp ?? '',
            config('app.key'),
        ]);

        return substr(hash('sha256', $data), 0, 16);
    }

    /**
     * Verify QR code hash
     *
     * @param Lease $lease
     * @param string $hash
     * @return bool
     */
    public static function verifyHash(Lease $lease, string $hash): bool
    {
        return self::generateVerificationHash($lease) === $hash;
    }

    /**
     * Get QR code as base64 data URI for embedding in PDFs
     *
     * @param Lease $lease
     * @return string
     */
    public static function getBase64DataUri(Lease $lease): string
    {
        $verificationUrl = $lease->verification_url ?? route('lease.verify', [
            'serial' => $lease->serial_number ?? $lease->reference_number,
            'hash' => self::generateVerificationHash($lease),
        ]);

        $qrCodePng = QrCode::format('png')
            ->size(300)
            ->style('round')
            ->eye('circle')
            ->margin(1)
            ->errorCorrection('H')
            ->generate($verificationUrl);

        return 'data:image/png;base64,' . base64_encode($qrCodePng);
    }

    /**
     * Regenerate QR code for a lease (useful if verification data changes)
     *
     * @param Lease $lease
     * @return Lease
     */
    public static function regenerate(Lease $lease): Lease
    {
        // Delete old QR code if exists
        if ($lease->qr_code_path) {
            Storage::disk('public')->delete($lease->qr_code_path);
        }

        return self::attachToLease($lease);
    }
}
