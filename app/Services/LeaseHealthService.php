<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lease;

/**
 * Fintech-style lease health score (0–100) and grade for ViewLease display.
 */
class LeaseHealthService
{
    /**
     * Score a lease and return grade + flags.
     *
     * @return array{score: int, grade: string, flags: array<string>}
     */
    public static function score(Lease $lease): array
    {
        $score = 0;
        $flags = [];

        $max = 100;
        $step = 20;

        // +20 if tenant signed on time (had OTP verified and signed without dispute)
        if ($lease->digitalSignatures()->where('signer_type', 'tenant')->exists()) {
            $score += $step;
        } else {
            $flags[] = 'tenant_not_signed';
        }

        // +20 if no disputes
        if ($lease->workflow_state !== 'disputed') {
            $score += $step;
        } else {
            $flags[] = 'disputed';
        }

        // +20 if deposit received (or not required / already active)
        if ($lease->deposit_verified || in_array($lease->workflow_state, ['active', 'expired', 'terminated', 'archived'], true)) {
            $score += $step;
        } else {
            $flags[] = 'deposit_pending';
        }

        // +20 if documents uploaded (at least one lease document)
        if ($lease->documents()->exists()) {
            $score += $step;
        } else {
            $flags[] = 'documents_pending';
        }

        // +20 if not expired/terminated/cancelled
        if (! in_array($lease->workflow_state, ['expired', 'terminated', 'cancelled'], true)) {
            $score += $step;
        } else {
            $flags[] = 'ended';
        }

        $grade = match (true) {
            $score >= 80 => 'A',
            $score >= 60 => 'B',
            $score >= 40 => 'C',
            $score >= 20 => 'D',
            default => 'F',
        };

        return [
            'score' => min($score, $max),
            'grade' => $grade,
            'flags' => $flags,
        ];
    }
}
