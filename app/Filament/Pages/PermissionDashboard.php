<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\RoleAuditLog;
use App\Models\User;
use App\Services\ActingDelegationService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class PermissionDashboard extends Page
{
    public string $activeTab = 'matrix';

    public ?string $selectedRole = null;

    public ?string $searchPermission = null;

    public ?string $userFilter = null;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Rights Allocation';

    protected static ?string $title = 'Rights Allocation Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.permission-dashboard';

    public function mount(): void
    {
        $this->selectedRole = Role::first()?->name;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() || $user?->isAdmin();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setSelectedRole(string $role): void
    {
        $this->selectedRole = $role;
    }

    /**
     * Toggle user active status (Deactivate/Activate).
     */
    public function toggleUserActive(int $userId): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            Notification::make()
                ->title('Cannot deactivate yourself')
                ->danger()
                ->send();

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        // Log the action
        RoleAuditLog::create([
            'user_id' => $user->id,
            'performed_by' => auth()->id(),
            'action' => $user->is_active ? 'user_activated' : 'user_deactivated',
            'new_role' => $user->role,
            'reason' => ($user->is_active ? 'Activated' : 'Deactivated') . ' by ' . auth()->user()->name,
            'ip_address' => request()->ip(),
        ]);

        Notification::make()
            ->title($user->name . ' has been ' . ($user->is_active ? 'activated' : 'deactivated'))
            ->success()
            ->send();
    }

    /**
     * Update user availability status (for zone managers / acting logic).
     */
    public function updateAvailability(int $userId, string $status): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        if (! in_array($status, ['available', 'on_leave', 'away'])) {
            return;
        }

        try {
            $delegationService = app(ActingDelegationService::class);
            $delegationService->handleAvailabilityChange($user, $status);

            Notification::make()
                ->title($user->name . ' availability set to ' . ucfirst($status))
                ->success()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Failed to update availability')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'roles' => $this->getRoles(),
            'permissions' => $this->getPermissions(),
            'permissionMatrix' => $this->getPermissionMatrix(),
            'roleStats' => $this->getRoleStats(),
            'recentChanges' => $this->getRecentChanges(),
            'usersWithRole' => $this->getUsersWithSelectedRole(),
            'permissionsByCategory' => $this->getPermissionsByCategory(),
            'selectedRole' => $this->selectedRole,
            'allUsers' => $this->getAllUsersForManagement(),
            'zoneManagers' => $this->getZoneManagers(),
        ];
    }

    // =====================================================================
    // Permission Toggle
    // =====================================================================

    public function togglePermission(string $roleName, string $permissionName): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            Notification::make()
                ->title('Unauthorized')
                ->body('Only Super Admins can modify permissions.')
                ->danger()
                ->send();

            return;
        }

        $role = Role::findByName($roleName, 'web');
        $permission = Permission::findByName($permissionName, 'web');

        // Use direct pivot table operations to avoid column/relationship conflict.
        // The roles table has a 'permissions' JSON column that shadows Spatie's
        // permissions() BelongsToMany relationship, causing hasPermissionTo() and
        // givePermissionTo() to fail with "contains() on null".
        $pivot = DB::table('role_has_permissions');
        $hasPermission = $pivot
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->exists();

        if ($hasPermission) {
            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->delete();
            $action = 'revoked';
        } else {
            DB::table('role_has_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]);
            $action = 'granted';
        }

        // Log the change
        RoleAuditLog::create([
            'user_id' => $user->id,
            'performed_by' => $user->id,
            'action' => $action === 'granted'
                ? RoleAuditLog::ACTION_PERMISSION_ADDED
                : RoleAuditLog::ACTION_PERMISSION_REMOVED,
            'reason' => "Permission '{$permissionName}' {$action} for role '{$roleName}'",
            'metadata' => [
                'role' => $roleName,
                'permission' => $permissionName,
                'action' => $action,
            ],
        ]);

        // Clear caches
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('permission_matrix');
        Cache::forget('permission_role_stats');

        Notification::make()
            ->title('Permission Updated')
            ->body("'{$permissionName}' {$action} for '{$roleName}'")
            ->success()
            ->send();
    }

    protected function getRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    protected function getPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    protected function getPermissionMatrix(): array
    {
        return Cache::remember('permission_matrix', now()->addMinutes(10), function () {
            $roles = Role::orderBy('name')->get();
            $permissions = Permission::orderBy('name')->get();

            $rolePermissionMap = DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->select('role_has_permissions.role_id', 'permissions.name')
                ->get()
                ->groupBy('role_id')
                ->map(fn ($perms) => $perms->pluck('name')->toArray());

            $matrix = [];

            foreach ($roles as $role) {
                $rolePermissions = $rolePermissionMap->get($role->id, []);
                $matrix[$role->name] = [];

                foreach ($permissions as $permission) {
                    $matrix[$role->name][$permission->name] = in_array($permission->name, $rolePermissions);
                }
            }

            return $matrix;
        });
    }

    protected function getRoleStats(): array
    {
        return Cache::remember('permission_role_stats', now()->addMinutes(10), function () {
            $permissionCounts = DB::table('role_has_permissions')
                ->select('role_id', DB::raw('count(*) as cnt'))
                ->groupBy('role_id')
                ->pluck('cnt', 'role_id');

            $userCounts = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->select('role_id', DB::raw('count(*) as cnt'))
                ->groupBy('role_id')
                ->pluck('cnt', 'role_id');

            $roles = Role::orderBy('name')->get();

            return $roles->map(fn ($role) => [
                'name' => $role->name,
                'users_count' => $userCounts->get($role->id, 0),
                'permissions_count' => $permissionCounts->get($role->id, 0),
                'color' => $this->getRoleColor($role->name),
            ])->toArray();
        });
    }

    protected function getRoleColor(string $roleName): string
    {
        return match ($roleName) {
            'super_admin' => 'danger',
            'admin' => 'warning',
            'property_manager', 'asst_property_manager' => 'primary',
            'zone_manager' => 'info',
            'field_officer', 'senior_field_officer' => 'success',
            'accountant' => 'purple',
            'auditor' => 'gray',
            'office_administrator', 'office_admin_assistant', 'office_assistant' => 'orange',
            default => 'secondary',
        };
    }

    protected function getRecentChanges(): Collection
    {
        return RoleAuditLog::with(['user', 'performer'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
    }

    protected function getUsersWithSelectedRole(): Collection
    {
        if (! $this->selectedRole) {
            return collect();
        }

        return User::role($this->selectedRole)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email', 'last_login_at', 'is_active', 'availability_status', 'zone_id']);
    }

    /**
     * Get all users for the management tab.
     */
    protected function getAllUsersForManagement(): Collection
    {
        $query = User::with('zone')
            ->orderBy('name');

        if ($this->userFilter) {
            $query->where('role', $this->userFilter);
        }

        return $query->limit(100)->get();
    }

    /**
     * Get zone managers with their delegation info.
     */
    protected function getZoneManagers(): Collection
    {
        return User::where('role', 'zone_manager')
            ->with(['zone', 'backupOfficer'])
            ->orderBy('name')
            ->get();
    }

    protected function getPermissionsByCategory(): array
    {
        $permissions = Permission::orderBy('name')->get();
        $categories = [];

        foreach ($permissions as $permission) {
            $parts = explode('_', $permission->name);
            $category = $parts[0] ?? 'general';

            if (! isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $permission->name;
        }

        ksort($categories);

        return $categories;
    }

    // =====================================================================
    // Export methods
    // =====================================================================

    protected function exportMatrixAsExcel(string $timestamp)
    {
        $matrix = $this->getPermissionMatrix();
        $roles = array_keys($matrix);
        $permissions = ! empty($matrix) ? array_keys(reset($matrix)) : [];

        $rows = [];

        // Header row
        $header = ['Permission'];
        foreach ($roles as $role) {
            $header[] = ucwords(str_replace('_', ' ', $role));
        }
        $rows[] = $header;

        // Data rows
        foreach ($permissions as $perm) {
            $row = [ucwords(str_replace('_', ' ', $perm))];
            foreach ($roles as $role) {
                $row[] = ($matrix[$role][$perm] ?? false) ? 'Yes' : 'No';
            }
            $rows[] = $row;
        }

        $filename = "permission_matrix_{$timestamp}.xlsx";

        return Excel::download(
            new \App\Exports\PermissionMatrixExport($rows),
            $filename,
        );
    }

    protected function exportMatrixAsPdf(string $timestamp)
    {
        $matrix = $this->getPermissionMatrix();
        $roles = array_keys($matrix);
        $permissions = ! empty($matrix) ? array_keys(reset($matrix)) : [];

        $roleStats = $this->getRoleStats();

        $pdf = Pdf::loadView('exports.permission-matrix-pdf', [
            'matrix' => $matrix,
            'roles' => $roles,
            'permissions' => $permissions,
            'roleStats' => $roleStats,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => auth()->user()->name,
        ])->setPaper('a3', 'landscape');

        $filename = "permission_matrix_{$timestamp}.pdf";

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    protected function exportMatrixAsCsv(string $timestamp)
    {
        $matrix = $this->getPermissionMatrix();
        $roles = array_keys($matrix);
        $permissions = ! empty($matrix) ? array_keys(reset($matrix)) : [];

        $csv = 'Permission,' . implode(',', array_map(fn ($r) => '"' . ucwords(str_replace('_', ' ', $r)) . '"', $roles)) . "\n";

        foreach ($permissions as $perm) {
            $row = ['"' . ucwords(str_replace('_', ' ', $perm)) . '"'];
            foreach ($roles as $role) {
                $row[] = ($matrix[$role][$perm] ?? false) ? 'Yes' : 'No';
            }
            $csv .= implode(',', $row) . "\n";
        }

        $filename = "permission_matrix_{$timestamp}.csv";

        return response()->streamDownload(
            fn () => print ($csv),
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportMatrix')
                ->label('Export Matrix')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('format')
                        ->label('Export Format')
                        ->options([
                            'excel' => 'Excel (.xlsx)',
                            'pdf' => 'PDF (.pdf)',
                            'csv' => 'CSV (.csv)',
                        ])
                        ->default('excel')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $format = $data['format'];
                    $timestamp = now()->format('Y-m-d_His');

                    return match ($format) {
                        'excel' => $this->exportMatrixAsExcel($timestamp),
                        'pdf' => $this->exportMatrixAsPdf($timestamp),
                        'csv' => $this->exportMatrixAsCsv($timestamp),
                    };
                }),

            \Filament\Actions\Action::make('auditReport')
                ->label('Full Audit Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->action(function () {
                    $this->activeTab = 'audit';

                    Notification::make()
                        ->title('Switched to Audit Trail tab')
                        ->success()
                        ->send();
                }),
        ];
    }
}
