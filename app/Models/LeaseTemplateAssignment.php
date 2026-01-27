<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseTemplateAssignment extends Model
{
    protected $fillable = [
        'lease_id',
        'lease_template_id',
        'template_version_used',
        'assigned_by',
        'assigned_at',
        'render_metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'render_metadata' => 'array',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LeaseTemplate::class, 'lease_template_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
