<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @foreach($roleStats as $stat)
            <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                 wire:click="setSelectedRole('{{ $stat['name'] }}')"
                 @class(['ring-2 ring-primary-500' => $selectedRole === $stat['name']])>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $stat['name'])) }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['users_count'] }} <span class="text-sm font-normal text-gray-500">users</span></p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $stat['color'] }}-100 text-{{ $stat['color'] }}-800 dark:bg-{{ $stat['color'] }}-900/30 dark:text-{{ $stat['color'] }}-400">
                            {{ $stat['permissions_count'] }} perms
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button
                    wire:click="setTab('matrix')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'matrix',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' => $activeTab !== 'matrix',
                    ])
                >
                    <x-heroicon-o-table-cells class="mr-2 h-5 w-5" />
                    Permission Matrix
                </button>

                <button
                    wire:click="setTab('users')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'users',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' => $activeTab !== 'users',
                    ])
                >
                    <x-heroicon-o-users class="mr-2 h-5 w-5" />
                    Users by Role
                </button>

                <button
                    wire:click="setTab('audit')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'audit',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' => $activeTab !== 'audit',
                    ])
                >
                    <x-heroicon-o-clipboard-document-list class="mr-2 h-5 w-5" />
                    Audit Trail
                    @if($recentChanges->count() > 0)
                        <span class="ml-2 bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 px-2 py-0.5 rounded-full text-xs">
                            {{ $recentChanges->count() }}
                        </span>
                    @endif
                </button>

                <button
                    wire:click="setTab('categories')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'categories',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' => $activeTab !== 'categories',
                    ])
                >
                    <x-heroicon-o-tag class="mr-2 h-5 w-5" />
                    By Category
                </button>
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
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Click on a role card above to highlight its permissions</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Permission
                                </th>
                                @foreach($roles as $role)
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                        {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($permissions as $permission)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $permission->name }}
                                    </td>
                                    @foreach($roles as $role)
                                        <td class="px-3 py-2 text-center">
                                            @if($permissionMatrix[$role->name][$permission->name] ?? false)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-success-100 dark:bg-success-900/30">
                                                    <x-heroicon-s-check class="w-4 h-4 text-success-600 dark:text-success-400" />
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800">
                                                    <x-heroicon-s-minus class="w-4 h-4 text-gray-400" />
                                                </span>
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
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($roleStats as $stat)
                                <button
                                    wire:click="setSelectedRole('{{ $stat['name'] }}')"
                                    @class([
                                        'w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-left',
                                        'bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-500' => $selectedRole === $stat['name'],
                                    ])
                                >
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucwords(str_replace('_', ' ', $stat['name'])) }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $stat['users_count'] }} users
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
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-users class="w-12 h-12 mx-auto mb-3 opacity-50" />
                                <p>No users with this role</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($usersWithRole as $user)
                                    <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                                <span class="text-sm font-medium text-primary-700 dark:text-primary-400">
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
                                                <span class="px-2 py-0.5 rounded-full text-xs bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">Active</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                            @endif
                                            @if($user->last_login_at)
                                                <span class="text-xs text-gray-400">{{ $user->last_login_at->diffForHumans() }}</span>
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
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-shield-check class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>No recent role changes recorded</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($recentChanges as $change)
                            <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <div class="flex items-start gap-3">
                                    <div @class([
                                        'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                                        'bg-success-100 dark:bg-success-900/30' => in_array($change->action, ['role_assigned', 'permission_added', 'role_created']),
                                        'bg-danger-100 dark:bg-danger-900/30' => in_array($change->action, ['role_revoked', 'permission_removed', 'role_deleted']),
                                        'bg-warning-100 dark:bg-warning-900/30' => in_array($change->action, ['role_changed', 'permission_synced', 'role_updated']),
                                    ])>
                                        @php
                                            $icon = \App\Models\RoleAuditLog::getActionIcon($change->action);
                                        @endphp
                                        <x-dynamic-component :component="$icon" class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $change->user?->name ?? 'Unknown User' }}
                                            </span>
                                            <span @class([
                                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                                'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => in_array($change->action, ['role_assigned', 'permission_added']),
                                                'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => in_array($change->action, ['role_revoked', 'permission_removed']),
                                                'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => in_array($change->action, ['role_changed', 'permission_synced']),
                                            ])>
                                                {{ \App\Models\RoleAuditLog::getActionLabel($change->action) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            @if($change->old_role && $change->new_role)
                                                <span class="line-through text-gray-400">{{ $change->old_role }}</span>
                                                <x-heroicon-s-arrow-right class="w-3 h-3 inline mx-1" />
                                                <span class="font-medium">{{ $change->new_role }}</span>
                                            @elseif($change->new_role)
                                                Assigned: <span class="font-medium">{{ $change->new_role }}</span>
                                            @elseif($change->old_role)
                                                Revoked: <span class="font-medium">{{ $change->old_role }}</span>
                                            @endif
                                        </p>
                                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                            <span>
                                                By: {{ $change->performer?->name ?? 'System' }}
                                            </span>
                                            <span>{{ $change->created_at->format('M d, Y H:i') }}</span>
                                            @if($change->ip_address)
                                                <span>IP: {{ $change->ip_address }}</span>
                                            @endif
                                        </div>
                                        @if($change->reason)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">
                                                "{{ $change->reason }}"
                                            </p>
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
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white capitalize flex items-center justify-between">
                                {{ $category }}
                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($perms) }} permissions</span>
                            </h3>
                        </div>
                        <div class="p-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($perms as $perm)
                                    <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
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
