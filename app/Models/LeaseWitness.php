<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseWitness extends Model
{
    protected $fillable = [
        'lease_id',
        'witnessed_party',
        'witnessed_by_user_id',
        'witnessed_by_name',
        'witnessed_by_title',
        'witness_type',
        'lsk_number',
        'witness_id_number',
        'witness_signature_path',
        'witnessed_at',
        'ip_address',
        'notes',
    ];

    protected $casts = [
        'witnessed_at' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function witnessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witnessed_by_user_id');
    }

    /**
     * Human-readable label for the witnessed party.
     */
    public function witnessedPartyLabel(): string
    {
        return match ($this->witnessed_party) {
            'tenant' => 'Tenant (Lessee)',
            'lessor' => 'Lessor / Managing Agent',
            default  => ucfirst($this->witnessed_party),
        };
    }

    /**
     * Human-readable label for the witness type.
     */
    public function witnessTypeLabel(): string
    {
        return match ($this->witness_type) {
            'staff'    => 'Chabrin Staff',
            'advocate' => 'LSK Advocate',
            'external' => 'External Witness',
            default    => ucfirst($this->witness_type ?? ''),
        };
    }
}
