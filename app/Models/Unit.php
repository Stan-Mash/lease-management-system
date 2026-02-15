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
        'property_id',
        'zone_id',
        'unit_number',
        'unit_code',
        'type',
        'market_rent',
        'deposit_required',
        'status',
        'field_officer_id',
        'zone_manager_id',
        'date_created',
    ];

    protected $casts = [
        'market_rent' => 'decimal:2',
        'deposit_required' => 'decimal:2',
        'date_created' => 'datetime',
    ];

    /**
     * Boot the model — auto-generate unit_code on create.
     */
    protected static function booted(): void
    {
        static::creating(function (Unit $unit) {
            if (empty($unit->unit_code) && $unit->property_id && $unit->unit_number) {
                $property = Property::find($unit->property_id);
                if ($property) {
                    $unit->unit_code = $property->property_code . '-' . $unit->unit_number;
                }
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
