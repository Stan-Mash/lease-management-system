<x-filament-panels::page>
    @php
        $statusConfig = [
            'healthy' => [
                'bg' => 'bg-emerald-50 dark:bg-emerald-950/30',
                'border' => 'border-emerald-200 dark:border-emerald-800',
                'text' => 'text-emerald-700 dark:text-emerald-400',
                'icon' => 'heroicon-o-check-circle',
                'dot' => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
            ],
            'warning' => [
                'bg' => 'bg-amber-50 dark:bg-amber-950/30',
                'border' => 'border-amber-200 dark:border-amber-800',
                'text' => 'text-amber-700 dark:text-amber-400',
                'icon' => 'heroicon-o-exclamation-triangle',
                'dot' => 'bg-amber-500',
                'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
            ],
            'critical' => [
                'bg' => 'bg-red-50 dark:bg-red-950/30',
                'border' => 'border-red-200 dark:border-red-800',
                'text' => 'text-red-700 dark:text-red-400',
                'icon' => 'heroicon-o-x-circle',
                'dot' => 'bg-red-500',
                'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            ],
        ];
        $overall = $systemHealth['overall'];
    @endphp

    {{-- Top Bar --}}
    <div class="flex items-center justify-between mb-6" wire:poll.30s>
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusConfig[$overall]['dot'] }} opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $statusConfig[$overall]['dot'] }}"></span>
            </span>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                {{ $overall === 'healthy' ? 'All systems operational' : ($overall === 'warning' ? 'Systems degraded' : 'Issues detected') }}
            </span>
        </div>
        <x-filament::button size="xs" color="gray" wire:click="$refresh" icon="heroicon-o-arrow-path">
            Refresh
        </x-filament::button>
    </div>

    {{-- Compact Status Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @php
            $cards = [
                ['label' => 'System', 'value' => ucfirst($overall), 'sub' => collect($systemHealth['checks'])->where('status', 'healthy')->count() . '/' . count($systemHealth['checks']) . ' passing', 'status' => $overall],
                ['label' => 'Queue', 'value' => number_format($queueStats['totalPending']), 'sub' => 'pending jobs', 'status' => $queueStats['status']],
                ['label' => 'Failed 24h', 'value' => number_format($failedJobs['last24Hours']), 'sub' => number_format($failedJobs['total']) . ' total', 'status' => $failedJobs['status']],
                ['label' => 'Disk', 'value' => $storageStats['usedPercentage'] . '%', 'sub' => $storageStats['used'] . ' / ' . $storageStats['total'], 'status' => $storageStats['status']],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="rounded-lg border {{ $statusConfig[$card['status']]['border'] }} {{ $statusConfig[$card['status']]['bg'] }} px-4 py-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                    <x-dynamic-component :component="$statusConfig[$card['status']]['icon']" class="w-4 h-4 {{ $statusConfig[$card['status']]['text'] }}" />
                </div>
                <div class="text-lg font-bold {{ $statusConfig[$card['status']]['text'] }} leading-tight">{{ $card['value'] }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['sub'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Health Checks --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <x-heroicon-o-heart class="w-4 h-4 text-rose-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Health Checks</h3>
            </div>
            <div class="p-3 space-y-1">
                @foreach($systemHealth['checks'] as $name => $check)
                    @php $cs = $check['status']; @endphp
                    <div class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <x-dynamic-component :component="$statusConfig[$cs]['icon']" class="w-4 h-4 {{ $statusConfig[$cs]['text'] }}" />
                            <span class="text-sm text-gray-700 dark:text-gray-200 capitalize">{{ str_replace('_', ' ', $name) }}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusConfig[$cs]['badge'] }}">{{ $check['message'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Queue Breakdown --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <x-heroicon-o-queue-list class="w-4 h-4 text-blue-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Queue Breakdown</h3>
            </div>
            <div class="p-3 space-y-1">
                @foreach($queueStats['queues'] as $queue)
                    @php $qs = $queue['status']; @endphp
                    <div class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <x-dynamic-component :component="$statusConfig[$qs]['icon']" class="w-4 h-4 {{ $statusConfig[$qs]['text'] }}" />
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $queue['name'] }}</span>
                        </div>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-full {{ $queue['pending'] > 0 ? $statusConfig[$qs]['badge'] : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                            {{ number_format($queue['pending']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Storage --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <x-heroicon-o-server-stack class="w-4 h-4 text-violet-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Storage</h3>
            </div>
            <div class="p-4">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                    <span>{{ $storageStats['used'] }} used</span>
                    <span>{{ $storageStats['free'] }} free</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden mb-4">
                    @php $barColor = $storageStats['usedPercentage'] > 90 ? 'bg-red-500' : ($storageStats['usedPercentage'] > 75 ? 'bg-amber-500' : 'bg-emerald-500'); @endphp
                    <div class="h-2 rounded-full transition-all {{ $barColor }}" style="width: {{ min($storageStats['usedPercentage'], 100) }}%"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="text-center p-2.5 rounded-md bg-gray-50 dark:bg-gray-800/50">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $storageStats['total'] }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Total</div>
                    </div>
                    <div class="text-center p-2.5 rounded-md bg-gray-50 dark:bg-gray-800/50">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $storageStats['documents'] }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Documents</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-circle-stack class="w-4 h-4 text-cyan-500" />
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Database</h3>
                </div>
                <span class="text-[11px] text-gray-400 font-mono">{{ $databaseStats['databaseSize'] }}</span>
            </div>
            <div class="p-3">
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach($databaseStats['tables'] as $table => $count)
                        <div class="flex items-center justify-between px-3 py-2 rounded-md bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $table) }}</span>
                            <span class="text-xs font-mono font-semibold text-gray-900 dark:text-white">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between items-center px-3 pt-2.5 mt-2 border-t border-gray-100 dark:border-gray-800">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total</span>
                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">{{ number_format($databaseStats['totalRecords']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Failed Jobs --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Failed Jobs</h3>
                @if($failedJobs['total'] > 0)
                    <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 font-medium">{{ $failedJobs['total'] }}</span>
                @endif
            </div>
            @if($failedJobs['total'] > 0)
                <div class="flex gap-1.5">
                    <x-filament::button size="xs" color="warning" wire:click="retryAllFailedJobs" icon="heroicon-o-arrow-path">
                        Retry All
                    </x-filament::button>
                    <x-filament::button size="xs" color="danger" wire:click="flushFailedJobs" wire:confirm="Delete all failed jobs? This cannot be undone." icon="heroicon-o-trash">
                        Clear
                    </x-filament::button>
                </div>
            @endif
        </div>

        @if(count($failedJobs['jobs']) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/30">
                            <th class="text-left py-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Job</th>
                            <th class="text-left py-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Queue</th>
                            <th class="text-left py-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">When</th>
                            <th class="text-left py-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Error</th>
                            <th class="text-right py-2 px-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($failedJobs['jobs'] as $job)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                                <td class="py-2 px-4 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $job['job'] }}</td>
                                <td class="py-2 px-4"><span class="text-[11px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $job['queue'] }}</span></td>
                                <td class="py-2 px-4 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() }}</td>
                                <td class="py-2 px-4 text-xs text-red-600 dark:text-red-400 max-w-xs truncate" title="{{ $job['exception'] }}">{{ $job['exception'] }}</td>
                                <td class="py-2 px-4 text-right">
                                    <x-filament::button size="xs" color="gray" wire:click="retryFailedJob('{{ $job['id'] }}')" icon="heroicon-o-arrow-path">
                                        Retry
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center">
                <x-heroicon-o-check-badge class="w-8 h-8 mx-auto mb-2 text-emerald-500" />
                <p class="text-xs font-medium text-gray-900 dark:text-gray-100">No failed jobs</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Everything running smoothly</p>
            </div>
        @endif
    </div>

    {{-- Job Batches --}}
    @if(count($recentActivity['batches']) > 0)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mt-4">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                <x-heroicon-o-rectangle-stack class="w-4 h-4 text-indigo-500" />
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent Batches</h3>
            </div>
            <div class="p-3 space-y-2">
                @foreach($recentActivity['batches'] as $batch)
                    <div class="px-3 py-2.5 rounded-md bg-gray-50 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch['name'] }}</span>
                            <span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($batch['created_at'])->diffForHumans() }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden mb-1.5">
                            <div class="h-1.5 rounded-full {{ $batch['failed_jobs'] > 0 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $batch['progress'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-gray-500">
                            <span>{{ $batch['total_jobs'] - $batch['pending_jobs'] }}/{{ $batch['total_jobs'] }} ({{ $batch['progress'] }}%)</span>
                            @if($batch['failed_jobs'] > 0)
                                <span class="text-red-500 font-medium">{{ $batch['failed_jobs'] }} failed</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- SMS Balance & CHIPS Database Widgets --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        @livewire(\App\Filament\Widgets\SmsBalanceWidget::class)
        @livewire(\App\Filament\Widgets\ChipsDatabaseWidget::class)
    </div>
</x-filament-panels::page>
