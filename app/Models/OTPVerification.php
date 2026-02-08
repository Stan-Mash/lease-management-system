<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

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
     * Get a masked code representation for display.
     *
     * Since the code is now hashed, we can only show a fixed-length mask.
     */
    protected function maskedCode(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str_repeat('*', (int) config('lease.otp.code_length', 6)),
        );
    }

    /**
     * Get the fully masked code (all asterisks).
     */
    protected function hiddenCode(): Attribute
    {
        return Attribute::make(
            get: fn (): string => str_repeat('*', (int) config('lease.otp.code_length', 6)),
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
     * Verify the provided code matches this OTP (plaintext comparison).
     *
     * @deprecated Use verifyHashed() instead. This method is retained for
     *             backward compatibility with any OTPs stored before hashing was introduced.
     */
    public function verify(string $code, ?string $ipAddress = null): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $this->incrementAttempts();

        if ($this->code !== $code) {
            if ($this->maxAttemptsReached()) {
                $this->markAsExpired();
            }

            return false;
        }

        $this->markAsVerified($ipAddress);

        return true;
    }

    /**
     * Verify the provided plaintext code against the stored hash.
     *
     * Uses Hash::check() for constant-time comparison, preventing
     * timing attacks. Falls back to plaintext comparison for legacy
     * OTPs that were stored before hashing was introduced.
     */
    public function verifyHashed(string $plaintextCode, ?string $ipAddress = null): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $this->incrementAttempts();

        // Check if the stored code is a bcrypt hash (starts with $2y$)
        $storedCode = $this->attributes['code'] ?? '';
        $isHashed = str_starts_with($storedCode, '$2y$');

        $matches = $isHashed
            ? Hash::check($plaintextCode, $storedCode)
            : $storedCode === $plaintextCode; // Legacy fallback

        if (! $matches) {
            if ($this->maxAttemptsReached()) {
                $this->markAsExpired();
            }

            return false;
        }

        $this->markAsVerified($ipAddress);

        return true;
    }

    /**
     * Scope to get valid OTPs.
     */
    public function scopeValid(Builder $query): Builder
    {
        $maxVerifyAttempts = (int) config('lease.otp.max_verification_attempts', 3);

        return $query->where('is_verified', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', $maxVerifyAttempts);
    }

    /**
     * Scope to get verified OTPs.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get expired OTPs.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('is_expired', true)
                ->orWhere('expires_at', '<=', now());
        });
    }

    /**
     * Scope to get OTPs for a specific lease.
     */
    public function scopeForLease(Builder $query, int $leaseId): Builder
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Scope to get recent OTPs (last N hours).
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
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
