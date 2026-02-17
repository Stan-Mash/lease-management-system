<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
