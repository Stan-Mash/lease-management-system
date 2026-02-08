<x-filament-panels::page>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        @foreach ($statsCards as $key => $stat)
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-4">
                    <div @class([
                        'flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center shadow-lg',
                        'bg-primary-100 dark:bg-primary-900/30 shadow-primary-500/20' => $stat['color'] === 'primary',
                        'bg-warning-100 dark:bg-warning-900/30 shadow-warning-500/20' => $stat['color'] === 'warning',
                        'bg-danger-100 dark:bg-danger-900/30 shadow-danger-500/20' => $stat['color'] === 'danger',
                        'bg-success-100 dark:bg-success-900/30 shadow-success-500/20' => $stat['color'] === 'success',
                    ])>
                        <x-filament::icon
                            :icon="$stat['icon']"
                            @class([
                                'w-6 h-6',
                                'text-primary-600 dark:text-primary-400' => $stat['color'] === 'primary',
                                'text-warning-600 dark:text-warning-400' => $stat['color'] === 'warning',
                                'text-danger-600 dark:text-danger-400' => $stat['color'] === 'danger',
                                'text-success-600 dark:text-success-400' => $stat['color'] === 'success',
                            ])
                        />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p @class([
                            'text-3xl font-bold',
                            'text-primary-600 dark:text-primary-400' => $stat['color'] === 'primary',
                            'text-warning-600 dark:text-warning-400' => $stat['color'] === 'warning',
                            'text-danger-600 dark:text-danger-400' => $stat['color'] === 'danger',
                            'text-success-600 dark:text-success-400' => $stat['color'] === 'success',
                        ])>
                            {{ number_format($stat['value']) }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">{{ $stat['description'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

        {{-- Activity by Category --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-chart-pie" class="w-5 h-5 text-gray-400" />
                    Activity by Category
                </h3>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $totalCategoryCount = array_sum(array_column($activityByCategory, 'count'));
                @endphp
                @foreach ($activityByCategory as $categoryKey => $category)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <x-filament::icon
                                    :icon="$category['icon']"
                                    @class([
                                        'w-5 h-5',
                                        'text-info-500' => $category['color'] === 'info',
                                        'text-warning-500' => $category['color'] === 'warning',
                                        'text-success-500' => $category['color'] === 'success',
                                        'text-danger-500' => $category['color'] === 'danger',
                                    ])
                                />
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $category['label'] }}</span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($category['count']) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                            <div
                                @class([
                                    'h-2 rounded-full transition-all duration-500',
                                    'bg-info-500' => $category['color'] === 'info',
                                    'bg-warning-500' => $category['color'] === 'warning',
                                    'bg-success-500' => $category['color'] === 'success',
                                    'bg-danger-500' => $category['color'] === 'danger',
                                ])
                                style="width: {{ $totalCategoryCount > 0 ? round(($category['count'] / $totalCategoryCount) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach

                @if ($totalCategoryCount === 0)
                    <div class="text-center py-6">
                        <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No audit activity recorded yet</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Top Uploaders --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-5 h-5 text-gray-400" />
                    Top Uploaders
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($topUploaders as $index => $uploader)
                    <div class="flex items-center gap-3 px-6 py-3">
                        <div @class([
                            'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => $index === 0,
                            'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $index === 1,
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' => $index === 2,
                            'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400' => $index > 2,
                        ])>
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $uploader['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $uploader['email'] }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-900/30 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-300">
                                {{ number_format($uploader['upload_count']) }} uploads
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <x-filament::icon icon="heroicon-o-users" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No uploads recorded yet</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Action Breakdown --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-queue-list" class="w-5 h-5 text-gray-400" />
                    Actions Breakdown
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @php
                    $actionColors = \App\Models\DocumentAudit::getActionColors();
                    $actionLabels = \App\Models\DocumentAudit::getActionLabels();
                    $actionIcons = \App\Models\DocumentAudit::getActionIcons();
                @endphp
                @forelse ($actionBreakdown as $action => $count)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center gap-2.5">
                            <x-filament::icon
                                :icon="$actionIcons[$action] ?? 'heroicon-o-document'"
                                @class([
                                    'w-4 h-4',
                                    'text-info-500' => ($actionColors[$action] ?? 'gray') === 'info',
                                    'text-gray-400' => ($actionColors[$action] ?? 'gray') === 'gray',
                                    'text-warning-500' => ($actionColors[$action] ?? 'gray') === 'warning',
                                    'text-success-500' => ($actionColors[$action] ?? 'gray') === 'success',
                                    'text-danger-500' => ($actionColors[$action] ?? 'gray') === 'danger',
                                    'text-primary-500' => ($actionColors[$action] ?? 'gray') === 'primary',
                                ])
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $actionLabels[$action] ?? ucfirst($action) }}</span>
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                            'bg-info-50 text-info-700 dark:bg-info-900/30 dark:text-info-300' => ($actionColors[$action] ?? 'gray') === 'info',
                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => ($actionColors[$action] ?? 'gray') === 'gray',
                            'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300' => ($actionColors[$action] ?? 'gray') === 'warning',
                            'bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300' => ($actionColors[$action] ?? 'gray') === 'success',
                            'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300' => ($actionColors[$action] ?? 'gray') === 'danger',
                            'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' => ($actionColors[$action] ?? 'gray') === 'primary',
                        ])>
                            {{ number_format($count) }}
                        </span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No actions recorded yet</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Activity Table --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-gray-400" />
                Recent Activity
            </h3>
            <span class="text-xs text-gray-400 dark:text-gray-500">Last 50 entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($recentActivity as $entry)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $entry['created_at']->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['created_at']->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry['user_name'] }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-info-50 text-info-700 dark:bg-info-900/30 dark:text-info-300' => $entry['action_color'] === 'info',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $entry['action_color'] === 'gray',
                                    'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300' => $entry['action_color'] === 'warning',
                                    'bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300' => $entry['action_color'] === 'success',
                                    'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300' => $entry['action_color'] === 'danger',
                                    'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' => $entry['action_color'] === 'primary',
                                ])>
                                    <x-filament::icon :icon="$entry['action_icon']" class="w-3.5 h-3.5" />
                                    {{ $entry['action_label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-sm text-gray-900 dark:text-white max-w-xs truncate block">{{ $entry['document_title'] }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-sm text-gray-500 dark:text-gray-400 max-w-sm truncate block">{{ \Illuminate\Support\Str::limit($entry['description'], 60) }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <code class="text-xs font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $entry['ip_address'] ?? '-' }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <x-filament::icon icon="heroicon-o-inbox" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No audit activity recorded</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Activity will appear here as users interact with documents</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
