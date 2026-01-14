<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guarantor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'name',
        'id_number',
        'phone',
        'email',
        'relationship',
        'guarantee_amount',
        'signed',
        'signed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'guarantee_amount' => 'decimal:2',
        'signed' => 'boolean',
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lease that this guarantor belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Mark the guarantor as signed.
     */
    public function markAsSigned(): void
    {
        $this->signed = true;
        $this->signed_at = now();
        $this->save();
    }

    /**
     * Scope to get unsigned guarantors.
     */
    public function scopeUnsigned($query)
    {
        return $query->where('signed', false);
    }

    /**
     * Scope to get signed guarantors.
     */
    public function scopeSigned($query)
    {
        return $query->where('signed', true);
    }

    /**
     * Check if guarantor has signed.
     */
    public function hasSigned(): bool
    {
        return $this->signed;
    }

    /**
     * Get the guaranteed amount or default to lease deposit.
     */
    public function getGuaranteeAmountAttribute($value): float
    {
        return $value ?? $this->lease->deposit_amount ?? 0;
    }
}
