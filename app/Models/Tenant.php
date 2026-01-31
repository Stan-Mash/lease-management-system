<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'id_number',
        'phone_number',
        'email',
        'notification_preference',
        'kra_pin',
        'occupation',
        'employer_name',
        'next_of_kin_name',
        'next_of_kin_phone',
    ];

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }
}
