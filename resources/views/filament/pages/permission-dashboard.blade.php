<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
        @foreach($roleStats as $stat)
            <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-3 py-2.5 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                 wire:click="setSelectedRole('{{ $stat['name'] }}')"
                 @class(['ring-2 ring-primary-500' => $selectedRole === $stat['name']])>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ ucwords(str_replace('_', ' ', $stat['name'])) }}</p>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stat['users_count'] }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $stat['permissions_count'] }}p</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                @php
                    $tabs = [
                        'matrix' => ['icon' => 'heroicon-o-table-cells', 'label' => 'Permission Matrix'],
                        'users' => ['icon' => 'heroicon-o-users', 'label' => 'Users by Role'],
                        'manage' => ['icon' => 'heroicon-o-cog-6-tooth', 'label' => 'User Management'],
                        'delegation' => ['icon' => 'heroicon-o-arrow-path', 'label' => 'Delegation'],
                        'audit' => ['icon' => 'heroicon-o-clipboard-document-list', 'label' => 'Audit Trail'],
                        'categories' => ['icon' => 'heroicon-o-tag', 'label' => 'By Category'],
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tab)
                    <button
                        wire:click="setTab('{{ $tabKey }}')"
                        @class([
                            'group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm transition-colors whitespace-nowrap',
                            'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === $tabKey,
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' => $activeTab !== $tabKey,
                        ])
                    >
                        <x-dynamic-component :component="$tab['icon']" class="mr-1.5 h-4 w-4" />
                        {{ $tab['label'] }}
                        @if($tabKey === 'audit' && $recentChanges->count() > 0)
                            <span class="ml-1.5 bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 px-1.5 py-0.5 rounded-full text-xs">
                                {{ $recentChanges->count() }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        {{-- PERMISSION MATRIX TAB --}}
        <div x-data x-show="$wire.activeTab === 'matrix'" x-cloak>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-table-cells class="w-4 h-4" />
                        Role-Permission Matrix
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Click on a role card above to highlight its permissions</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Permission
                                </th>
                                @foreach($roles as $role)
                                    <th class="px-2 py-2 text-center text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($permissions as $permission)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $permission->name }}
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="px-2 py-1.5 text-center">
                                            @if(auth()->user()->isSuperAdmin())
                                                <button
                                                    wire:click="togglePermission('{{ $role->name }}', '{{ $permission->name }}')"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full cursor-pointer transition-all duration-150 hover:scale-110 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500/50 {{ ($permissionMatrix[$role->name][$permission->name] ?? false) ? 'bg-success-100 dark:bg-success-900/30 hover:bg-success-200' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200' }}"
                                                    title="Click to {{ ($permissionMatrix[$role->name][$permission->name] ?? false) ? 'revoke' : 'grant' }} '{{ $permission->name }}' for '{{ $role->name }}'"
                                                >
                                                    @if($permissionMatrix[$role->name][$permission->name] ?? false)
                                                        <x-heroicon-s-check class="w-3.5 h-3.5 text-success-600 dark:text-success-400" />
                                                    @else
                                                        <x-heroicon-s-minus class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" />
                                                    @endif
                                                </button>
                                            @else
                                                @if($permissionMatrix[$role->name][$permission->name] ?? false)
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-success-100 dark:bg-success-900/30">
                                                        <x-heroicon-s-check class="w-3 h-3 text-success-600 dark:text-success-400" />
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800">
                                                        <x-heroicon-s-minus class="w-3 h-3 text-gray-300 dark:text-gray-600" />
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- USERS BY ROLE TAB --}}
        <div x-data x-show="$wire.activeTab === 'users'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Role Selector --}}
                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Select Role</h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                            @foreach($roleStats as $stat)
                                <button
                                    wire:click="setSelectedRole('{{ $stat['name'] }}')"
                                    @class([
                                        'w-full px-4 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left',
                                        'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-500' => $selectedRole === $stat['name'],
                                    ])
                                >
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucwords(str_replace('_', ' ', $stat['name'])) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $stat['users_count'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Users List --}}
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Users with "{{ ucwords(str_replace('_', ' ', $selectedRole ?? 'None')) }}" Role
                            </h3>
                        </div>

                        @if($usersWithRole->isEmpty())
                            <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-users class="w-10 h-10 mx-auto mb-2 opacity-50" />
                                <p class="text-sm">No users with this role</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($usersWithRole as $user)
                                    <div class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <div class="flex items-center gap-3">
                                            <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                                <span class="text-xs font-medium text-primary-700 dark:text-primary-400">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if($user->is_active)
                                                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">Active</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                            @endif
                                            @if($user->last_login_at)
                                                <span class="text-[10px] text-gray-400">{{ $user->last_login_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- USER MANAGEMENT TAB --}}
        <div x-data x-show="$wire.activeTab === 'manage'" x-cloak>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                            User Management
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Activate/deactivate users. No permanent deletions.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Zone</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Availability</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($allUsers as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 @if(!$user->is_active) opacity-50 @endif">
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                <span class="text-[10px] font-medium text-gray-600 dark:text-gray-300">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                                <p class="text-[10px] text-gray-500">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ ucwords(str_replace('_', ' ', $user->role ?? 'none')) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                                        {{ $user->zone?->name ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if(in_array($user->role, ['zone_manager', 'senior_field_officer']))
                                            <span @class([
                                                'px-2 py-0.5 rounded-full text-[10px] font-medium',
                                                'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => ($user->availability_status ?? 'available') === 'available',
                                                'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => $user->availability_status === 'on_leave',
                                                'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => $user->availability_status === 'away',
                                            ])>
                                                {{ ucfirst($user->availability_status ?? 'available') }}
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if($user->is_active)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">Active</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            @if($user->id !== auth()->id())
                                                <button
                                                    wire:click="toggleUserActive({{ $user->id }})"
                                                    wire:confirm="Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} {{ $user->name }}?"
                                                    @class([
                                                        'px-2 py-1 rounded text-[10px] font-medium transition-colors',
                                                        'bg-danger-100 text-danger-700 hover:bg-danger-200 dark:bg-danger-900/30 dark:text-danger-400' => $user->is_active,
                                                        'bg-success-100 text-success-700 hover:bg-success-200 dark:bg-success-900/30 dark:text-success-400' => !$user->is_active,
                                                    ])
                                                >
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            @else
                                                <span class="text-[10px] text-gray-400 italic">You</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- DELEGATION TAB --}}
        <div x-data x-show="$wire.activeTab === 'delegation'" x-cloak>
            <div class="space-y-6">
                {{-- Zone Manager Availability --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                            Zone Manager Delegation
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">When a Zone Manager goes on leave, their Senior Field Officer backup auto-inherits permissions.</p>
                    </div>

                    @if($zoneManagers->isEmpty())
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            <p class="text-sm">No zone managers found</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($zoneManagers as $zm)
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-info-100 dark:bg-info-900/30 flex items-center justify-center">
                                                <span class="text-xs font-bold text-info-700 dark:text-info-400">{{ strtoupper(substr($zm->name, 0, 2)) }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $zm->name }}</p>
                                                <p class="text-xs text-gray-500">Zone: {{ $zm->zone?->name ?? 'Unassigned' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            {{-- Availability Status Buttons --}}
                                            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
                                                @foreach(['available' => 'Available', 'on_leave' => 'On Leave', 'away' => 'Away'] as $statusKey => $statusLabel)
                                                    <button
                                                        wire:click="updateAvailability({{ $zm->id }}, '{{ $statusKey }}')"
                                                        @class([
                                                            'px-2 py-1 rounded text-[10px] font-medium transition-all',
                                                            'bg-white dark:bg-gray-600 shadow-sm text-gray-900 dark:text-white' => ($zm->availability_status ?? 'available') === $statusKey,
                                                            'text-gray-500 dark:text-gray-400 hover:text-gray-700' => ($zm->availability_status ?? 'available') !== $statusKey,
                                                        ])
                                                    >
                                                        {{ $statusLabel }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Backup Officer Info --}}
                                    <div class="mt-2 ml-11 text-xs">
                                        @if($zm->backupOfficer)
                                            <span class="text-gray-500">Backup: </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $zm->backupOfficer->name }}</span>
                                            @if($zm->backupOfficer->isActingForSomeone())
                                                <span class="ml-1 px-1.5 py-0.5 rounded bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 text-[10px]">
                                                    Currently Acting
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-warning-600 dark:text-warning-400">No backup officer assigned</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- AUDIT TRAIL TAB --}}
        <div x-data x-show="$wire.activeTab === 'audit'" x-cloak>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                        Recent Role & Permission Changes
                    </h3>
                </div>

                @if($recentChanges->isEmpty())
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-shield-check class="w-10 h-10 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No recent role changes recorded</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($recentChanges as $change)
                            <div class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <div class="flex items-start gap-3">
                                    <div @class([
                                        'w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0',
                                        'bg-success-100 dark:bg-success-900/30' => in_array($change->action, ['role_assigned', 'permission_added', 'role_created', 'user_activated']),
                                        'bg-danger-100 dark:bg-danger-900/30' => in_array($change->action, ['role_revoked', 'permission_removed', 'role_deleted', 'user_deactivated']),
                                        'bg-warning-100 dark:bg-warning-900/30' => in_array($change->action, ['role_changed', 'permission_synced', 'role_updated', 'delegation_activated', 'delegation_deactivated']),
                                    ])>
                                        @php
                                            $icon = \App\Models\RoleAuditLog::getActionIcon($change->action);
                                        @endphp
                                        <x-dynamic-component :component="$icon" class="w-3.5 h-3.5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $change->user?->name ?? 'Unknown User' }}
                                            </span>
                                            <span @class([
                                                'px-1.5 py-0.5 rounded-full text-[10px] font-medium',
                                                'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => in_array($change->action, ['role_assigned', 'permission_added', 'user_activated']),
                                                'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => in_array($change->action, ['role_revoked', 'permission_removed', 'user_deactivated']),
                                                'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => !in_array($change->action, ['role_assigned', 'permission_added', 'user_activated', 'role_revoked', 'permission_removed', 'user_deactivated']),
                                            ])>
                                                {{ \App\Models\RoleAuditLog::getActionLabel($change->action) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                            @if($change->old_role && $change->new_role)
                                                <span class="line-through text-gray-400">{{ $change->old_role }}</span>
                                                <x-heroicon-s-arrow-right class="w-2.5 h-2.5 inline mx-0.5" />
                                                <span class="font-medium">{{ $change->new_role }}</span>
                                            @elseif($change->new_role)
                                                Assigned: <span class="font-medium">{{ $change->new_role }}</span>
                                            @elseif($change->old_role)
                                                Revoked: <span class="font-medium">{{ $change->old_role }}</span>
                                            @endif
                                        </p>
                                        <div class="flex items-center gap-3 mt-1 text-[10px] text-gray-400">
                                            <span>By: {{ $change->performer?->name ?? 'System' }}</span>
                                            <span>{{ $change->created_at->format('M d, Y H:i') }}</span>
                                            @if($change->ip_address)
                                                <span>IP: {{ $change->ip_address }}</span>
                                            @endif
                                        </div>
                                        @if($change->reason)
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 italic">"{{ $change->reason }}"</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- CATEGORIES TAB --}}
        <div x-data x-show="$wire.activeTab === 'categories'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($permissionsByCategory as $category => $perms)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2.5 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white capitalize flex items-center justify-between">
                                {{ $category }}
                                <span class="text-[10px] font-normal text-gray-500 dark:text-gray-400">{{ count($perms) }}</span>
                            </h3>
                        </div>
                        <div class="p-2.5">
                            <div class="flex flex-wrap gap-1">
                                @foreach($perms as $perm)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $perm }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
