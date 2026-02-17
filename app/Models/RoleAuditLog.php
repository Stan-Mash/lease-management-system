<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RoleAuditLog extends Model
{
    // Action types
    public const ACTION_ROLE_ASSIGNED = 'role_assigned';

    public const ACTION_ROLE_REVOKED = 'role_revoked';

    public const ACTION_ROLE_CHANGED = 'role_changed';

    public const ACTION_PERMISSION_ADDED = 'permission_added';

    public const ACTION_PERMISSION_REMOVED = 'permission_removed';

    public const ACTION_PERMISSION_SYNCED = 'permission_synced';

    public const ACTION_ROLE_CREATED = 'role_created';

    public const ACTION_ROLE_UPDATED = 'role_updated';

    public const ACTION_ROLE_DELETED = 'role_deleted';

    protected $fillable = [
        'uuid',
        'user_id',
        'action',
        'old_role',
        'new_role',
        'old_permissions',
        'new_permissions',
        'performed_by',
        'reason',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_permissions' => 'array',
        'new_permissions' => 'array',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPerformer($query, int $performerId)
    {
        return $query->where('performed_by', $performerId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeRoleChanges($query)
    {
        return $query->whereIn('action', [
            self::ACTION_ROLE_ASSIGNED,
            self::ACTION_ROLE_REVOKED,
            self::ACTION_ROLE_CHANGED,
        ]);
    }

    public function scopePermissionChanges($query)
    {
        return $query->whereIn('action', [
            self::ACTION_PERMISSION_ADDED,
            self::ACTION_PERMISSION_REMOVED,
            self::ACTION_PERMISSION_SYNCED,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function getActionLabel(string $action): string
    {
        return match ($action) {
            self::ACTION_ROLE_ASSIGNED => 'Role Assigned',
            self::ACTION_ROLE_REVOKED => 'Role Revoked',
            self::ACTION_ROLE_CHANGED => 'Role Changed',
            self::ACTION_PERMISSION_ADDED => 'Permission Added',
            self::ACTION_PERMISSION_REMOVED => 'Permission Removed',
            self::ACTION_PERMISSION_SYNCED => 'Permissions Synced',
            self::ACTION_ROLE_CREATED => 'Role Created',
            self::ACTION_ROLE_UPDATED => 'Role Updated',
            self::ACTION_ROLE_DELETED => 'Role Deleted',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    public static function getActionIcon(string $action): string
    {
        return match ($action) {
            self::ACTION_ROLE_ASSIGNED => 'heroicon-o-user-plus',
            self::ACTION_ROLE_REVOKED => 'heroicon-o-user-minus',
            self::ACTION_ROLE_CHANGED => 'heroicon-o-arrows-right-left',
            self::ACTION_PERMISSION_ADDED => 'heroicon-o-plus-circle',
            self::ACTION_PERMISSION_REMOVED => 'heroicon-o-minus-circle',
            self::ACTION_PERMISSION_SYNCED => 'heroicon-o-arrow-path',
            self::ACTION_ROLE_CREATED => 'heroicon-o-plus',
            self::ACTION_ROLE_UPDATED => 'heroicon-o-pencil',
            self::ACTION_ROLE_DELETED => 'heroicon-o-trash',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public static function getActionColor(string $action): string
    {
        return match ($action) {
            self::ACTION_ROLE_ASSIGNED, self::ACTION_PERMISSION_ADDED, self::ACTION_ROLE_CREATED => 'success',
            self::ACTION_ROLE_REVOKED, self::ACTION_PERMISSION_REMOVED, self::ACTION_ROLE_DELETED => 'danger',
            self::ACTION_ROLE_CHANGED, self::ACTION_PERMISSION_SYNCED, self::ACTION_ROLE_UPDATED => 'warning',
            default => 'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Static Logging Methods
    |--------------------------------------------------------------------------
    */

    public static function logRoleAssigned(
        User $user,
        string $roleName,
        ?User $performer = null,
        ?string $reason = null,
    ): self {
        return self::create([
            'user_id' => $user->id,
            'action' => self::ACTION_ROLE_ASSIGNED,
            'new_role' => $roleName,
            'performed_by' => $performer?->id ?? auth()->id(),
            'reason' => $reason,
            'metadata' => [
                'user_email' => $user->email,
                'user_name' => $user->name,
            ],
        ]);
    }

    public static function logRoleRevoked(
        User $user,
        string $roleName,
        ?User $performer = null,
        ?string $reason = null,
    ): self {
        return self::create([
            'user_id' => $user->id,
            'action' => self::ACTION_ROLE_REVOKED,
            'old_role' => $roleName,
            'performed_by' => $performer?->id ?? auth()->id(),
            'reason' => $reason,
            'metadata' => [
                'user_email' => $user->email,
                'user_name' => $user->name,
            ],
        ]);
    }

    public static function logRoleChanged(
        User $user,
        string $oldRole,
        string $newRole,
        ?User $performer = null,
        ?string $reason = null,
    ): self {
        return self::create([
            'user_id' => $user->id,
            'action' => self::ACTION_ROLE_CHANGED,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'performed_by' => $performer?->id ?? auth()->id(),
            'reason' => $reason,
            'metadata' => [
                'user_email' => $user->email,
                'user_name' => $user->name,
            ],
        ]);
    }

    public static function logPermissionChange(
        User $user,
        string $action,
        array $oldPermissions,
        array $newPermissions,
        ?User $performer = null,
        ?string $reason = null,
    ): self {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'old_permissions' => $oldPermissions,
            'new_permissions' => $newPermissions,
            'performed_by' => $performer?->id ?? auth()->id(),
            'reason' => $reason,
            'metadata' => [
                'user_email' => $user->email,
                'user_name' => $user->name,
                'added' => array_diff($newPermissions, $oldPermissions),
                'removed' => array_diff($oldPermissions, $newPermissions),
            ],
        ]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RoleAuditLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = Str::uuid()->toString();
            }

            // Auto-capture request context if available
            if (request()) {
                if (empty($log->ip_address)) {
                    $log->ip_address = request()->ip();
                }
                if (empty($log->user_agent)) {
                    $log->user_agent = request()->userAgent();
                }
            }
        });
    }
}
