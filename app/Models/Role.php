<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'color',
        'permissions',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'key');
    }

    /**
     * Check if role can be deleted
     */
    public function canBeDeleted(): bool
    {
        // System roles cannot be deleted
        if ($this->is_system) {
            return false;
        }

        // Cannot delete if users are assigned to this role
        if ($this->users()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Scope to get only active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get available badge colors
     */
    public static function getAvailableColors(): array
    {
        return [
            'danger' => 'Danger (Red)',
            'warning' => 'Warning (Orange)',
            'info' => 'Info (Blue)',
            'success' => 'Success (Green)',
            'primary' => 'Primary',
            'gray' => 'Gray',
        ];
    }
}
