<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalSignature extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'tenant_id',
        'signature_data',
        'signature_type',
        'ip_address',
        'user_agent',
        'signed_at',
        'otp_verification_id',
        'is_verified',
        'verification_hash',
        'signature_latitude',
        'signature_longitude',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'signed_at' => 'datetime',
        'is_verified' => 'boolean',
        'signature_latitude' => 'decimal:8',
        'signature_longitude' => 'decimal:8',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lease that this signature belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the tenant who signed.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the OTP verification (if exists).
     */
    public function otpVerification(): BelongsTo
    {
        return $this->belongsTo(OTPVerification::class, 'otp_verification_id');
    }

    /**
     * Generate verification hash for signature data.
     */
    public static function generateHash(string $signatureData): string
    {
        return hash('sha256', $signatureData);
    }

    /**
     * Verify the signature hash matches the data.
     */
    public function verifyHash(): bool
    {
        if (! $this->verification_hash) {
            return false;
        }

        $computedHash = self::generateHash($this->signature_data);

        return hash_equals($this->verification_hash, $computedHash);
    }

    /**
     * Get the signature as a data URI for display.
     */
    public function getDataUriAttribute(): string
    {
        // If signature_data already has data URI format, return as is
        if (str_starts_with($this->signature_data, 'data:')) {
            return $this->signature_data;
        }

        // Otherwise, wrap it in data URI format
        return 'data:image/png;base64,' . $this->signature_data;
    }

    /**
     * Check if signature has GPS coordinates.
     */
    public function hasLocation(): bool
    {
        return $this->signature_latitude !== null && $this->signature_longitude !== null;
    }

    /**
     * Get formatted location string.
     */
    public function getLocationAttribute(): ?string
    {
        if (! $this->hasLocation()) {
            return null;
        }

        return "{$this->signature_latitude}, {$this->signature_longitude}";
    }

    /**
     * Scope to get verified signatures.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get signatures for a lease.
     */
    public function scopeForLease($query, int $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Scope to get signatures by tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get recent signatures.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('signed_at', '>=', now()->subDays($days));
    }

    /**
     * Create signature from base64 data with automatic hash generation.
     */
    public static function createFromData(array $data): static
    {
        // Generate verification hash
        $data['verification_hash'] = self::generateHash($data['signature_data']);
        $data['signed_at'] = $data['signed_at'] ?? now();

        return static::create($data);
    }
}
