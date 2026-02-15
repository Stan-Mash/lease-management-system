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
        'name',
        'property_code',
        'zone',
        'location',
        'landlord_id',
        'commission',
        'field_officer_id',
        'zone_manager_id',
        'date_created',
    ];

    protected $casts = [
        'commission' => 'decimal:2',
        'date_created' => 'datetime',
    ];

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
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

    public function zoneManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zone_manager_id');
    }
}
