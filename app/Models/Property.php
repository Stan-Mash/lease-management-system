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
        'management_commission',
    ];

    protected $casts = [
        'management_commission' => 'decimal:2',
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
}
