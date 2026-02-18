<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Landlord extends Model
{
    use HasFactory;

    protected $fillable = [
        'landlord_code',
        'name',
        'phone',
        'email',
        'id_number',
        'kra_pin',
        'bank_name',
        'account_number',
        'is_active',
        'zone_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Backward-compat: `name` ↔ `names`.
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['names'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['names'] = $value;
    }

    /**
     * Backward-compat: `phone` ↔ `mobile_number`.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['mobile_number'] ?? null;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['mobile_number'] = $value;
    }

    public function getIdNumberAttribute(): ?string
    {
        return $this->attributes['national_id'] ?? null;
    }

    public function setIdNumberAttribute($value): void
    {
        $this->attributes['national_id'] = $value;
    }

    public function getKraPinAttribute(): ?string
    {
        return $this->attributes['pin_number'] ?? null;
    }

    public function setKraPinAttribute($value): void
    {
        $this->attributes['pin_number'] = $value;
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }
}
