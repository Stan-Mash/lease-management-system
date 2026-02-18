<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_time',
        'client_id',
        'property_id',
        'unit_type_id',
        'usage_type_id',
        'unit_code',
        'description',
        'created_by_id',
        'unit_uploads',
        'zone_id',
        'occupancy_status_id',
        'unit_name',
        'unit_condition_id',
        'category_id',
        'rent_amount',
        'vat_able',
        'current_status_id',
        'unit_number',
        'initial_water_meter_reading',
        'topology_id',
        'block_owner_tenant_id',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'rent_amount' => 'decimal:2',
        'vat_able' => 'boolean',
        'initial_water_meter_reading' => 'decimal:2',
    ];

    // Backward-compat: `market_rent` ↔ `rent_amount`
    public function getMarketRentAttribute(): ?string
    {
        return $this->attributes['rent_amount'] ?? null;
    }

    public function setMarketRentAttribute($value): void
    {
        $this->attributes['rent_amount'] = $value;
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function blockOwnerTenant(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'block_owner_tenant_id');
    }
}
