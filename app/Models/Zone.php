<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'zone_manager_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the zone manager for this zone.
     */
    public function zoneManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_manager_id');
    }

    /**
     * Get all field officers assigned to this zone.
     */
    public function fieldOfficers(): HasMany
    {
        return $this->hasMany(User::class, 'zone_id')
            ->where('role', 'field_officer');
    }

    /**
     * Get all users in this zone.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'zone_id');
    }

    /**
     * Get all leases in this zone.
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 'zone_id');
    }

    /**
     * Get all landlords in this zone.
     */
    public function landlords(): HasMany
    {
        return $this->hasMany(Landlord::class, 'zone_id');
    }

    /**
     * Scope to get only active zones.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if zone has a manager assigned.
     */
    public function hasManager(): bool
    {
        return ! is_null($this->zone_manager_id);
    }

    /**
     * Get count of field officers in this zone.
     */
    public function getFieldOfficerCountAttribute(): int
    {
        return $this->fieldOfficers()->count();
    }

    /**
     * Get count of active leases in this zone.
     */
    public function getActiveLeaseCountAttribute(): int
    {
        return $this->leases()->whereIn('workflow_state', ['draft', 'pending_landlord_approval', 'approved', 'sent_digital'])->count();
    }
}
