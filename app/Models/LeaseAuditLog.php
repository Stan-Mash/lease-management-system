<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseAuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'action',
        'old_state',
        'new_state',
        'user_id',
        'user_role_at_time',
        'ip_address',
        'additional_data',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'additional_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lease that this audit log belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by lease.
     */
    public function scopeForLease($query, int $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get a human-readable description of the audit event.
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        // Generate default description
        $user = $this->user ? $this->user->name : 'System';

        if ($this->old_state && $this->new_state) {
            return "{$user} changed state from {$this->old_state} to {$this->new_state}";
        }

        return "{$user} performed {$this->action}";
    }
}
