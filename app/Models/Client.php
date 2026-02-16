<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

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

    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'client_id');
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
