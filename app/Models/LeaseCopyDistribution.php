<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseCopyDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'tenant_copy_method',
        'tenant_copy_sent_at',
        'tenant_copy_sent_by',
        'tenant_copy_confirmed',
        'tenant_copy_confirmed_at',
        'landlord_copy_method',
        'landlord_copy_sent_at',
        'landlord_copy_sent_by',
        'landlord_copy_confirmed',
        'landlord_copy_confirmed_at',
        'office_copy_filed',
        'office_copy_filed_at',
        'office_copy_filed_by',
        'notes',
    ];

    protected $casts = [
        'tenant_copy_sent_at' => 'datetime',
        'tenant_copy_confirmed' => 'boolean',
        'tenant_copy_confirmed_at' => 'datetime',
        'landlord_copy_sent_at' => 'datetime',
        'landlord_copy_confirmed' => 'boolean',
        'landlord_copy_confirmed_at' => 'datetime',
        'office_copy_filed' => 'boolean',
        'office_copy_filed_at' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function tenantCopySentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_copy_sent_by');
    }

    public function landlordCopySentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_copy_sent_by');
    }

    public function officeCopyFiledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'office_copy_filed_by');
    }

    public function sendTenantCopy(string $method, int $userId): void
    {
        $this->update([
            'tenant_copy_method' => $method,
            'tenant_copy_sent_at' => now(),
            'tenant_copy_sent_by' => $userId,
        ]);
    }

    public function confirmTenantCopyReceived(): void
    {
        $this->update([
            'tenant_copy_confirmed' => true,
            'tenant_copy_confirmed_at' => now(),
        ]);
    }

    public function sendLandlordCopy(string $method, int $userId): void
    {
        $this->update([
            'landlord_copy_method' => $method,
            'landlord_copy_sent_at' => now(),
            'landlord_copy_sent_by' => $userId,
        ]);
    }

    public function confirmLandlordCopyReceived(): void
    {
        $this->update([
            'landlord_copy_confirmed' => true,
            'landlord_copy_confirmed_at' => now(),
        ]);
    }

    public function fileOfficeCopy(int $userId): void
    {
        $this->update([
            'office_copy_filed' => true,
            'office_copy_filed_at' => now(),
            'office_copy_filed_by' => $userId,
        ]);
    }

    public function isFullyDistributed(): bool
    {
        return $this->tenant_copy_sent_at !== null
            && $this->landlord_copy_sent_at !== null
            && $this->office_copy_filed;
    }

    public function isFullyConfirmed(): bool
    {
        return $this->tenant_copy_confirmed
            && $this->landlord_copy_confirmed
            && $this->office_copy_filed;
    }

    public function getDistributionStatusAttribute(): string
    {
        if ($this->isFullyConfirmed()) {
            return 'complete';
        }

        if ($this->isFullyDistributed()) {
            return 'pending_confirmation';
        }

        $pending = [];
        if (! $this->tenant_copy_sent_at) {
            $pending[] = 'tenant';
        }
        if (! $this->landlord_copy_sent_at) {
            $pending[] = 'landlord';
        }
        if (! $this->office_copy_filed) {
            $pending[] = 'office';
        }

        return 'pending: ' . implode(', ', $pending);
    }
}
