<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeaseTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'template_type',
        'source_type',
        'blade_content',
        'css_styles',
        'layout_config',
        'logo_path',
        'branding_config',
        'source_pdf_path',
        'extraction_metadata',
        'available_variables',
        'required_variables',
        'is_active',
        'is_default',
        'version_number',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'css_styles' => 'array',
        'layout_config' => 'array',
        'branding_config' => 'array',
        'extraction_metadata' => 'array',
        'available_variables' => 'array',
        'required_variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LeaseTemplateVersion::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeaseTemplateAssignment::class);
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        // Create version snapshot on update
        static::updated(function ($template) {
            // Only create version if blade_content or css_styles changed (not version_number)
            if (($template->wasChanged('blade_content') || $template->wasChanged('css_styles'))
                && !$template->wasChanged('version_number')) {
                $template->createVersionSnapshot('Template updated');
            }
        });
    }

    // Create version snapshot
    public function createVersionSnapshot(?string $changeSummary = null): LeaseTemplateVersion
    {
        // Increment version without triggering events
        $this->version_number++;
        $this->saveQuietly();

        return $this->versions()->create([
            'version_number' => $this->version_number,
            'blade_content' => $this->blade_content,
            'css_styles' => $this->css_styles,
            'layout_config' => $this->layout_config,
            'branding_config' => $this->branding_config,
            'available_variables' => $this->available_variables,
            'created_by' => auth()->id(),
            'change_summary' => $changeSummary,
        ]);
    }

    // Restore from version
    public function restoreFromVersion(int $versionNumber): bool
    {
        $version = $this->versions()->where('version_number', $versionNumber)->first();

        if (!$version) {
            return false;
        }

        $this->update([
            'blade_content' => $version->blade_content,
            'css_styles' => $version->css_styles,
            'layout_config' => $version->layout_config,
            'branding_config' => $version->branding_config,
            'available_variables' => $version->available_variables,
            'updated_by' => auth()->id(),
        ]);

        return true;
    }

    // Extract variables from blade content
    public function extractVariables(): array
    {
        preg_match_all('/\{\{\s*\$([a-zA-Z0-9_>-]+)\s*\}\}/', $this->blade_content, $matches);
        return array_unique($matches[1] ?? []);
    }

    // Validate required variables are present
    public function validateRequiredVariables(): bool
    {
        $available = $this->extractVariables();
        $required = $this->required_variables ?? [];

        foreach ($required as $requiredVar) {
            if (!in_array($requiredVar, $available)) {
                return false;
            }
        }

        return true;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
