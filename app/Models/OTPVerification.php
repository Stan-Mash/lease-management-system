<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OTPVerification extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'otp_verifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'phone',
        'code',
        'purpose',
        'sent_at',
        'expires_at',
        'verified_at',
        'attempts',
        'is_verified',
        'is_expired',
        'ip_address',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
        'is_verified' => 'boolean',
        'is_expired' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * This prevents the code from being exposed in JSON responses.
     *
     * @var array<string>
     */
    protected $hidden = [
        'code',
    ];

    /**
     * Get the masked version of the OTP code for display.
     * Only shows first and last digit, e.g., "1****6"
     */
    protected function maskedCode(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $code = $this->attributes['code'] ?? '';
                if (strlen($code) < 2) {
                    return str_repeat('*', strlen($code));
                }

                return $code[0] . str_repeat('*', strlen($code) - 2) . $code[strlen($code) - 1];
            }
        );
    }

    /**
     * Get the fully masked code (all asterisks).
     */
    protected function hiddenCode(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str_repeat('*', strlen($this->attributes['code'] ?? ''))
        );
    }

    /**
     * Check if the current request/user is the tenant who should receive this OTP.
     * Used to determine if the code can be displayed.
     */
    public function isViewableByCurrentUser(): bool
    {
        // Code should never be viewable in admin panel or API responses
        // It's only sent via SMS directly to the tenant
        return false;
    }

    /**
     * Get display-safe code representation.
     * Always returns masked version for security.
     */
    public function getDisplayCode(): string
    {
        return $this->hidden_code;
    }

    /**
     * Get the lease that this OTP belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Check if OTP is still valid (not expired and not verified).
     */
    public function isValid(): bool
    {
        return ! $this->is_expired
            && ! $this->is_verified
            && now()->isBefore($this->expires_at)
            && $this->attempts < 3;
    }

    /**
     * Check if OTP has expired.
     */
    public function hasExpired(): bool
    {
        return $this->is_expired || now()->isAfter($this->expires_at);
    }

    /**
     * Check if maximum attempts reached.
     */
    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= 3;
    }

    /**
     * Mark OTP as expired.
     */
    public function markAsExpired(): void
    {
        $this->is_expired = true;
        $this->save();
    }

    /**
     * Increment verification attempts.
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
        $this->save();
    }

    /**
     * Mark OTP as verified.
     */
    public function markAsVerified(?string $ipAddress = null): void
    {
        $this->is_verified = true;
        $this->verified_at = now();
        $this->ip_address = $ipAddress ?? request()->ip();
        $this->save();
    }

    /**
     * Verify the provided code matches this OTP.
     */
    public function verify(string $code, ?string $ipAddress = null): bool
    {
        // Check if OTP is still valid
        if (! $this->isValid()) {
            return false;
        }

        // Increment attempts
        $this->incrementAttempts();

        // Check if code matches
        if ($this->code !== $code) {
            // Mark as expired if max attempts reached
            if ($this->maxAttemptsReached()) {
                $this->markAsExpired();
            }

            return false;
        }

        // Success - mark as verified
        $this->markAsVerified($ipAddress);

        return true;
    }

    /**
     * Scope to get valid OTPs.
     */
    public function scopeValid($query)
    {
        return $query->where('is_verified', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 3);
    }

    /**
     * Scope to get verified OTPs.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get expired OTPs.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_expired', true)
                ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Scope to get OTPs for a specific lease.
     */
    public function scopeForLease($query, int $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Scope to get recent OTPs (last 24 hours).
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('sent_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the number of minutes until expiry.
     */
    public function getMinutesUntilExpiry(): ?int
    {
        if ($this->hasExpired()) {
            return 0;
        }

        return now()->diffInMinutes($this->expires_at, false);
    }
}
