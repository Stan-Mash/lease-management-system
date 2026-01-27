<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseTemplateVersion extends Model
{
    protected $fillable = [
        'lease_template_id',
        'version_number',
        'blade_content',
        'css_styles',
        'layout_config',
        'branding_config',
        'available_variables',
        'created_by',
        'change_summary',
        'changes_diff',
    ];

    protected $casts = [
        'css_styles' => 'array',
        'layout_config' => 'array',
        'branding_config' => 'array',
        'available_variables' => 'array',
        'changes_diff' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(LeaseTemplate::class, 'lease_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
