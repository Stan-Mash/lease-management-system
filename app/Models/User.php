<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'block',
        'sendEmail',
        'registerDate',
        'lastvisitDate',
        'activation',
        'params',
        'lastResetTime',
        'resetCount',
        'otpKey',
        'reset_token',
        'reset_token_expires_at',
        'otep',
        'requireReset',
    ];

    protected $hidden = [
        'password',
        'otpKey',
        'otep',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'block' => 'boolean',
            'sendEmail' => 'boolean',
            'registerDate' => 'datetime',
            'lastvisitDate' => 'datetime',
            'lastResetTime' => 'datetime',
            'resetCount' => 'integer',
            'reset_token_expires_at' => 'datetime',
            'requireReset' => 'boolean',
        ];
    }

    // ── Relationships ──

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function backupOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backup_officer_id');
    }

    public function assignedLeases(): HasMany
    {
        return $this->hasMany(Lease::class, 'assigned_field_officer_id');
    }

    // ── Role checks ──

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isZoneManager(): bool
    {
        return $this->hasRole('zone_manager');
    }

    public function isFieldOfficer(): bool
    {
        return $this->hasRole(['field_officer', 'senior_field_officer']);
    }

    public function hasZoneRestriction(): bool
    {
        return $this->isZoneManager() || $this->isFieldOfficer();
    }

    public function canAccessZone(int|string|null $zoneId): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        if (! $zoneId) {
            return false;
        }

        return (int) $this->zone_id === (int) $zoneId;
    }

    public function getRoleDisplayName(): string
    {
        $role = $this->roles->first();

        if (! $role) {
            return 'No Role';
        }

        return ucwords(str_replace('_', ' ', $role->name));
    }
}
