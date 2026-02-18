<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_time',
        'client_id',
        'reference_number',
        'description',
        'lat_long',
        'photos_and_documents',
        'zone_id',
        'zone_area_id',
        'property_name',
        'lr_number',
        'usage_type_id',
        'current_status_id',
        'acquisition_date',
        'created_by',
        'bank_account_id',
        'field_officer_id',
        'zone_supervisor_id',
        'zone_manager_id',
        'parent_property_id',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'acquisition_date' => 'date',
    ];
    
    // Backward-compat: legacy attribute helpers (kept minimal to avoid duplication)

    /**
     * Accessor: name → property_name (backward compatibility after schema restructure).
     */
    public function getNameAttribute(): ?string
    {
        return $this->property_name;
    }

    /**
     * Calculate occupancy rate as a percentage of occupied units.
     */
    public function occupancyRate(): float
    {
        $totalUnits = $this->units()->count();

        if ($totalUnits === 0) {
            return 0.0;
        }

        $occupiedUnits = $this->units()->whereHas('leases', function ($query) {
            $query->where('workflow_state', 'active');
        })->count();

        return round(($occupiedUnits / $totalUnits) * 100, 1);
    }

    /**
     * Calculate total monthly rent from active leases on this property.
     */
    public function totalMonthlyRent(): float
    {
        return (float) $this->leases()
            ->where('workflow_state', 'active')
            ->sum('monthly_rent');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function fieldOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_officer_id');
    }

    public function zoneSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_supervisor_id');
    }

    public function zoneManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_manager_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'parent_property_id');
    }

    public function childProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'parent_property_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
