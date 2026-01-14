<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Spatie\Permission\Traits\HasRoles; // Temporarily disabled - run 'composer install' on local machine to enable

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable; // HasRoles temporarily disabled

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'zone_id',
        'phone',
        'avatar_path',
        'is_active',
        'last_login_at',
        'department',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is an admin (super_admin or admin)
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    /**
     * Check if user can manage leases
     */
    public function canManageLeases(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'manager', 'agent']);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'zone_manager' => 'Zone Manager',
            'field_officer' => 'Field Officer',
            'manager' => 'Manager',
            'agent' => 'Agent',
            'viewer' => 'Viewer',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    /**
     * Get the zone this user belongs to.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get leases assigned to this field officer.
     */
    public function assignedLeases()
    {
        return $this->hasMany(Lease::class, 'assigned_field_officer_id');
    }

    /**
     * Get zone managed by this zone manager.
     */
    public function managedZone()
    {
        return $this->hasOne(Zone::class, 'zone_manager_id');
    }

    /**
     * Check if user is a zone manager.
     */
    public function isZoneManager(): bool
    {
        return $this->role === 'zone_manager';
    }

    /**
     * Check if user is a field officer.
     */
    public function isFieldOfficer(): bool
    {
        return $this->role === 'field_officer';
    }

    /**
     * Check if user has zone-restricted access.
     */
    public function hasZoneRestriction(): bool
    {
        return in_array($this->role, ['zone_manager', 'field_officer']);
    }

    /**
     * Check if user can access a specific zone.
     */
    public function canAccessZone(int $zoneId): bool
    {
        // Super admins and regular admins can access all zones
        if ($this->isAdmin()) {
            return true;
        }

        // Zone-restricted users can only access their assigned zone
        if ($this->hasZoneRestriction()) {
            return $this->zone_id === $zoneId;
        }

        return false;
    }

    /**
     * Check if user can access a specific lease.
     */
    public function canAccessLease(Lease $lease): bool
    {
        // Super admins and regular admins can access all leases
        if ($this->isAdmin()) {
            return true;
        }

        // Zone managers can access leases in their zone
        if ($this->isZoneManager() && $this->zone_id) {
            return $lease->zone_id === $this->zone_id;
        }

        // Field officers can access leases in their zone or assigned to them
        if ($this->isFieldOfficer() && $this->zone_id) {
            return $lease->zone_id === $this->zone_id;
        }

        return false;
    }

    /**
     * Scope query to only show data from user's zone.
     */
    public function scopeInUserZone($query)
    {
        if ($this->hasZoneRestriction() && $this->zone_id) {
            return $query->where('zone_id', $this->zone_id);
        }

        return $query;
    }
}
