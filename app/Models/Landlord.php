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
        'name',
        'phone',
        'email',
        'id_number',
        'kra_pin',
        'bank_name',
        'account_number',
        'is_active',
        'zone_id',
        'date_created',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_created' => 'datetime',
    ];

    /**
     * Auto-generate LAN ID on create (LAN-00001 format).
     */
    protected static function booted(): void
    {
        static::creating(function (Landlord $landlord) {
            if (empty($landlord->lan_id)) {
                $lastLanId = static::query()
                    ->where('lan_id', 'like', 'LAN-%')
                    ->orderByRaw("CAST(SUBSTRING(lan_id FROM 5) AS INTEGER) DESC")
                    ->value('lan_id');

                if ($lastLanId) {
                    $lastNumber = (int) substr($lastLanId, 4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    // Start from highest existing ID to not conflict
                    $maxId = static::max('id') ?? 0;
                    $nextNumber = $maxId + 1;
                }

                $landlord->lan_id = 'LAN-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
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
