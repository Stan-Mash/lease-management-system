<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Landlord extends Model
{
    use HasFactory;

    protected $fillable = [
        'lan_id',
        'names',
        'mobile_number',
        'email_address',
        'national_id',
        'pin_number',
        'bank_name',
        'account_number',
        'is_active',
        'zone_id',
        'date_time',
        // CHIPS columns
        'address',
        'vat_number',
        'bank_id',
        'account_name',
        'username',
        'client_password',
        'uid',
        'group_id',
        'registered_date',
        'reference_number',
        'created_by',
        'nationality_id',
        'passport_number',
        'client_type_id',
        'photo',
        'documents',
        'current_status_id',
        'client_status_id',
        'lead_id',
        'type_id',
        'client_id',
        'second_name',
        'last_name',
        'title',
        'gender',
        'prefered_messages_language_id',
        'property_id',
        'sla_id',
        'unit_id',
        'lease_start_date',
        'lease_years',
        'rent_amount',
        'escalation_rate',
        'frequency',
        'address_2',
        'address_3',
        'promas_id',
        'properties_json',
        'overdraft_penalty',
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
