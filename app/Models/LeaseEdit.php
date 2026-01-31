<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseEdit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'lease_id',
        'edited_by',
        'edit_type',
        'section_affected',
        'original_text',
        'new_text',
        'reason',
        'document_version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the lease that this edit belongs to.
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /**
     * Get the user who made the edit.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Scope to filter by lease.
     */
    public function scopeForLease($query, int $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    /**
     * Scope to filter by document version.
     */
    public function scopeForVersion($query, int $version)
    {
        return $query->where('document_version', $version);
    }

    /**
     * Scope to filter by edit type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('edit_type', $type);
    }

    /**
     * Scope to get edits ordered by version and time.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('document_version', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get a human-readable summary of the edit.
     */
    public function getSummaryAttribute(): string
    {
        $editor = $this->editor ? $this->editor->name : 'Unknown';
        $section = $this->section_affected ? " in {$this->section_affected}" : '';

        return match ($this->edit_type) {
            'clause_added' => "{$editor} added a clause{$section}",
            'clause_removed' => "{$editor} removed a clause{$section}",
            'clause_modified' => "{$editor} modified a clause{$section}",
            default => "{$editor} made changes{$section}",
        };
    }

    /**
     * Check if this edit added content.
     */
    public function isAddition(): bool
    {
        return $this->edit_type === 'clause_added';
    }

    /**
     * Check if this edit removed content.
     */
    public function isRemoval(): bool
    {
        return $this->edit_type === 'clause_removed';
    }

    /**
     * Check if this edit modified content.
     */
    public function isModification(): bool
    {
        return $this->edit_type === 'clause_modified';
    }
}
