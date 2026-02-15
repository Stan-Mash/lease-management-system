<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'employee_number',
        'password',
        'role',
        'zone_id',
        'phone',
        'avatar_path',
        'is_active',
        'last_login_at',
        'department',
        'bio',
        'availability_status',
        'backup_officer_id',
        'acting_for_user_id',
        'date_created',
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
        return in_array($this->role, [
            'super_admin', 'admin', 'property_manager', 'asst_property_manager',
            'zone_manager', 'senior_field_officer',
        ]);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'property_manager' => 'Property Manager',
            'asst_property_manager' => 'Asst. Property Manager',
            'accountant' => 'Accountant',
            'auditor' => 'Auditor',
            'internal_auditor' => 'Internal Auditor',
            'office_administrator' => 'Office Administrator',
            'office_admin_assistant' => 'Office Admin Assistant',
            'office_assistant' => 'Office Assistant',
            'zone_manager' => 'Zone Manager',
            'senior_field_officer' => 'Senior Field Officer',
            'field_officer' => 'Field Officer',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }

    /**
     * Check if user is a senior field officer.
     */
    public function isSeniorFieldOfficer(): bool
    {
        return $this->role === 'senior_field_officer';
    }

    /**
     * Check if user is currently acting for a zone manager.
     */
    public function isActingForSomeone(): bool
    {
        return $this->acting_for_user_id !== null;
    }

    /**
     * Get the user this officer is a backup for.
     */
    public function backupFor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'backup_officer_id');
    }

    /**
     * Get the user this person is currently acting for.
     */
    public function actingFor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_for_user_id');
    }

    /**
     * Get the backup officer assigned to this zone manager.
     */
    public function backupOfficer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'backup_officer_id');
    }

    /**
     * Check availability status.
     */
    public function isAvailable(): bool
    {
        return $this->availability_status === 'available';
    }

    public function isOnLeave(): bool
    {
        return $this->availability_status === 'on_leave';
    }

    public function isAway(): bool
    {
        return $this->availability_status === 'away';
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
     * Check if user is an internal auditor (sees all zones, read-only).
     */
    public function isInternalAuditor(): bool
    {
        return $this->role === 'internal_auditor';
    }

    /**
     * Check if user is an auditor (zone-restricted) or internal auditor.
     */
    public function isAuditor(): bool
    {
        return in_array($this->role, ['auditor', 'internal_auditor']);
    }

    /**
     * Check if user is a field officer (regular or senior).
     */
    public function isFieldOfficer(): bool
    {
        return in_array($this->role, ['field_officer', 'senior_field_officer']);
    }

    /**
     * Check if user has zone-restricted access.
     * Internal auditor does NOT have zone restriction (sees all zones).
     * Regular auditor IS zone-restricted.
     */
    public function hasZoneRestriction(): bool
    {
        return in_array($this->role, ['zone_manager', 'field_officer', 'senior_field_officer', 'auditor']);
    }

    /**
     * Check if user can access a specific zone.
     */
    public function canAccessZone(int $zoneId): bool
    {
        // Super admins, admins, and internal auditors can access all zones
        if ($this->isAdmin() || $this->isInternalAuditor()) {
            return true;
        }

        // Property managers and asst PMs can access all zones
        if (in_array($this->role, ['property_manager', 'asst_property_manager'])) {
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
            'date_created' => 'datetime',
        ];
    }
}
