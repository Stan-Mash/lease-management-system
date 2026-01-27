<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentEscalation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'effective_date',
        'previous_rent',
        'new_rent',
        'increase_percentage',
        'applied',
        'applied_at',
        'applied_by',
        'tenant_notified',
        'tenant_notified_at',
        'landlord_notified',
        'landlord_notified_at',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'previous_rent' => 'decimal:2',
        'new_rent' => 'decimal:2',
        'increase_percentage' => 'decimal:2',
        'applied' => 'boolean',
        'applied_at' => 'datetime',
        'tenant_notified' => 'boolean',
        'tenant_notified_at' => 'datetime',
        'landlord_notified' => 'boolean',
        'landlord_notified_at' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public static function createForLease(
        Lease $lease,
        float $newRent,
        string $effectiveDate,
        ?string $notes = null
    ): self {
        $previousRent = $lease->monthly_rent;
        $increasePercentage = $previousRent > 0
            ? (($newRent - $previousRent) / $previousRent) * 100
            : 0;

        return self::create([
            'lease_id' => $lease->id,
            'effective_date' => $effectiveDate,
            'previous_rent' => $previousRent,
            'new_rent' => $newRent,
            'increase_percentage' => round($increasePercentage, 2),
            'notes' => $notes,
        ]);
    }

    public function apply(int $userId): void
    {
        $this->update([
            'applied' => true,
            'applied_at' => now(),
            'applied_by' => $userId,
        ]);

        // Update the lease's monthly rent
        $this->lease->update([
            'monthly_rent' => $this->new_rent,
        ]);
    }

    public function markTenantNotified(): void
    {
        $this->update([
            'tenant_notified' => true,
            'tenant_notified_at' => now(),
        ]);
    }

    public function markLandlordNotified(): void
    {
        $this->update([
            'landlord_notified' => true,
            'landlord_notified_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('applied', false);
    }

    public function scopeApplied($query)
    {
        return $query->where('applied', true);
    }

    public function scopeDueWithinDays($query, int $days)
    {
        return $query->where('applied', false)
            ->whereBetween('effective_date', [
                now()->startOfDay(),
                now()->addDays($days)->endOfDay(),
            ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('applied', false)
            ->where('effective_date', '<', now()->startOfDay());
    }

    public function isDue(): bool
    {
        return !$this->applied && $this->effective_date->isPast();
    }

    public function isDueSoon(int $days = 30): bool
    {
        return !$this->applied
            && $this->effective_date->isFuture()
            && $this->effective_date->diffInDays(now()) <= $days;
    }

    public function getIncreaseAmountAttribute(): float
    {
        return $this->new_rent - $this->previous_rent;
    }

    public function getFormattedIncreaseAttribute(): string
    {
        $amount = number_format($this->increase_amount, 2);
        $percentage = number_format($this->increase_percentage, 1);

        return "KES {$amount} ({$percentage}%)";
    }
}
