<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Tenant extends Model
{
    use HasFactory;
    use Notifiable;

    protected $table = 'tenants';

    protected $fillable = [
        'date_time',
        'names',
        'address',
        'vat_number',
        'pin_number',
        'mobile_number',
        'email_address',
        'bank_id',
        'account_name',
        'account_number',
        'username',
        'client_password',
        'uid',
        'group_id',
        'registered_date',
        'reference_number',
        'created_by',
        'nationality_id',
        'national_id',
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
        'properties',
        'overdraft_penalty',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'registered_date' => 'date',
        'lease_start_date' => 'date',
        'lease_years' => 'integer',
        'rent_amount' => 'decimal:2',
        'escalation_rate' => 'decimal:4',
        'overdraft_penalty' => 'decimal:2',
    ];

    /**
     * Accessor: full_name → names (backward compatibility after schema restructure).
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->names;
    }

    /**
     * Backward-compat: allow `phone_number` attribute to read/write `mobile_number`.
     */
    public function getPhoneNumberAttribute(): ?string
    {
        return $this->mobile_number ?? null;
    }

    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['mobile_number'] = $value;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->email_address ?? null;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email_address'] = $value;
    }

    public function getIdNumberAttribute(): ?string
    {
        return $this->national_id ?? null;
    }

    public function setIdNumberAttribute($value): void
    {
        $this->attributes['national_id'] = $value;
    }

    public function setFullNameAttribute($value): void
    {
        $this->attributes['names'] = $value;
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->email_address;
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
