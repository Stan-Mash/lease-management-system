<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseEscalation extends Model
{
    protected $fillable = [
        'lease_id',
        'escalation_type',
        'escalation_level',
        'reason',
        'escalated_by',
        'escalated_to',
        'escalation_date',
        'resolved_date',
        'resolution_notes',
    ];

    protected $casts = [
        'escalation_date' => 'datetime',
        'resolved_date' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }
}
