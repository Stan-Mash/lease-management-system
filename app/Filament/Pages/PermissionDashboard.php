<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\RoleAuditLog;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Permission Dashboard';

    protected static ?string $title = 'Permission & Role Governance';

    protected static string|UnitEnum|null $navigationGroup = 'Security';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.permission-dashboard';

    public string $activeTab = 'matrix';

    public ?string $selectedRole = null;

    public ?string $searchPermission = null;

    public function mount(): void
    {
        $this->selectedRole = Role::first()?->name;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'system_admin']) ?? false;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setSelectedRole(string $role): void
    {
        $this->selectedRole = $role;
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
        ];
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
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $matrix = [];

        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $matrix[$role->name] = [];

            foreach ($permissions as $permission) {
                $matrix[$role->name][$permission->name] = in_array($permission->name, $rolePermissions);
            }
        }

        return $matrix;
    }

    protected function getRoleStats(): array
    {
        $roles = Role::withCount('users', 'permissions')->get();

        return $roles->map(fn($role) => [
            'name' => $role->name,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'color' => $this->getRoleColor($role->name),
        ])->toArray();
    }

    protected function getRoleColor(string $roleName): string
    {
        return match ($roleName) {
            'super_admin' => 'danger',
            'system_admin' => 'warning',
            'property_manager', 'manager' => 'primary',
            'zone_manager' => 'info',
            'field_officer', 'senior_field_officer' => 'success',
            'audit' => 'gray',
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
        if (!$this->selectedRole) {
            return collect();
        }

        return User::role($this->selectedRole)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email', 'last_login_at', 'is_active']);
    }

    protected function getPermissionsByCategory(): array
    {
        $permissions = Permission::orderBy('name')->get();
        $categories = [];

        foreach ($permissions as $permission) {
            $parts = explode('_', $permission->name);
            $category = $parts[0] ?? 'general';

            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $permission->name;
        }

        ksort($categories);

        return $categories;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportMatrix')
                ->label('Export Matrix')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // Export logic here
                }),

            \Filament\Actions\Action::make('auditReport')
                ->label('Full Audit Report')
                ->icon('heroicon-o-document-chart-bar')
                ->url(fn() => route('filament.admin.pages.permission-dashboard', ['tab' => 'audit']))
                ->color('warning'),
        ];
    }
}
