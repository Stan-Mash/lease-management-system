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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }
}
