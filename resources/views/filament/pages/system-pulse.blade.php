<x-filament-panels::page>
    {{-- Auto-refresh indicator --}}
    <div class="flex justify-between items-center mb-6" wire:poll.30s>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" wire:loading />
            <span>Auto-refreshes every 30 seconds</span>
        </div>
        <x-filament::button size="sm" color="gray" wire:click="$refresh">
            <x-heroicon-o-arrow-path class="w-4 h-4 mr-1" />
            Refresh Now
        </x-filament::button>
    </div>

    {{-- System Health Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php
            $statusColors = [
                'healthy' => 'bg-success-100 dark:bg-success-900/20 border-success-300 dark:border-success-700',
                'warning' => 'bg-warning-100 dark:bg-warning-900/20 border-warning-300 dark:border-warning-700',
                'critical' => 'bg-danger-100 dark:bg-danger-900/20 border-danger-300 dark:border-danger-700',
            ];
            $statusTextColors = [
                'healthy' => 'text-success-700 dark:text-success-400',
                'warning' => 'text-warning-700 dark:text-warning-400',
                'critical' => 'text-danger-700 dark:text-danger-400',
            ];
            $statusIcons = [
                'healthy' => 'heroicon-o-check-circle',
                'warning' => 'heroicon-o-exclamation-triangle',
                'critical' => 'heroicon-o-x-circle',
            ];
        @endphp

        {{-- Overall Health --}}
        <div class="rounded-xl border-2 p-4 {{ $statusColors[$systemHealth['overall']] }}">
            <div class="flex items-center gap-3">
                <x-dynamic-component :component="$statusIcons[$systemHealth['overall']]" class="w-8 h-8 {{ $statusTextColors[$systemHealth['overall']] }}" />
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">System Status</div>
                    <div class="text-xl font-bold {{ $statusTextColors[$systemHealth['overall']] }}">
                        {{ ucfirst($systemHealth['overall']) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Queue Status --}}
        <div class="rounded-xl border-2 p-4 {{ $statusColors[$queueStats['status']] }}">
            <div class="flex items-center gap-3">
                <x-heroicon-o-queue-list class="w-8 h-8 {{ $statusTextColors[$queueStats['status']] }}" />
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Queue Depth</div>
                    <div class="text-xl font-bold {{ $statusTextColors[$queueStats['status']] }}">
                        {{ number_format($queueStats['totalPending']) }} jobs
                    </div>
                </div>
            </div>
        </div>

        {{-- Failed Jobs --}}
        <div class="rounded-xl border-2 p-4 {{ $statusColors[$failedJobs['status']] }}">
            <div class="flex items-center gap-3">
                <x-heroicon-o-exclamation-circle class="w-8 h-8 {{ $statusTextColors[$failedJobs['status']] }}" />
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Failed (24h)</div>
                    <div class="text-xl font-bold {{ $statusTextColors[$failedJobs['status']] }}">
                        {{ number_format($failedJobs['last24Hours']) }} jobs
                    </div>
                </div>
            </div>
        </div>

        {{-- Storage --}}
        <div class="rounded-xl border-2 p-4 {{ $statusColors[$storageStats['status']] }}">
            <div class="flex items-center gap-3">
                <x-heroicon-o-server class="w-8 h-8 {{ $statusTextColors[$storageStats['status']] }}" />
                <div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">Storage Used</div>
                    <div class="text-xl font-bold {{ $statusTextColors[$storageStats['status']] }}">
                        {{ $storageStats['usedPercentage'] }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Health Checks --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-heart class="w-5 h-5" />
                    Health Checks
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($systemHealth['checks'] as $name => $check)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <x-dynamic-component
                                :component="$statusIcons[$check['status']]"
                                class="w-5 h-5 {{ $statusTextColors[$check['status']] }}"
                            />
                            <span class="font-medium capitalize">{{ str_replace('_', ' ', $name) }}</span>
                        </div>
                        <span class="text-sm {{ $statusTextColors[$check['status']] }}">
                            {{ $check['message'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Queue Stats by Queue --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-queue-list class="w-5 h-5" />
                    Queue Breakdown
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($queueStats['queues'] as $queue)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <x-dynamic-component
                                :component="$statusIcons[$queue['status']]"
                                class="w-5 h-5 {{ $statusTextColors[$queue['status']] }}"
                            />
                            <span class="font-medium">{{ $queue['name'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono {{ $queue['pending'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500' }}">
                                {{ number_format($queue['pending']) }} pending
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Storage Details --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-server-stack class="w-5 h-5" />
                    Storage
                </div>
            </x-slot>

            <div class="space-y-4">
                {{-- Storage Bar --}}
                <div>
                    <div class="flex justify-between text-sm mb-2">
                        <span>{{ $storageStats['used'] }} used</span>
                        <span>{{ $storageStats['free'] }} free</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                        <div
                            class="h-4 rounded-full transition-all {{ $storageStats['usedPercentage'] > 90 ? 'bg-danger-500' : ($storageStats['usedPercentage'] > 75 ? 'bg-warning-500' : 'bg-success-500') }}"
                            style="width: {{ $storageStats['usedPercentage'] }}%"
                        ></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $storageStats['total'] }}</div>
                        <div class="text-sm text-gray-500">Total Space</div>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-2xl font-bold text-info-600 dark:text-info-400">{{ $storageStats['documents'] }}</div>
                        <div class="text-sm text-gray-500">Documents</div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Database Stats --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-circle-stack class="w-5 h-5" />
                    Database
                </div>
            </x-slot>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    @foreach($databaseStats['tables'] as $table => $count)
                        <div class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800">
                            <span class="text-sm capitalize">{{ str_replace('_', ' ', $table) }}</span>
                            <span class="font-mono text-sm font-medium">{{ number_format($count) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-2 border-t dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Database Size</span>
                        <span class="font-medium">{{ $databaseStats['databaseSize'] }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Failed Jobs Section --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                    Failed Jobs
                    @if($failedJobs['total'] > 0)
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">
                            {{ $failedJobs['total'] }} total
                        </span>
                    @endif
                </div>
                @if($failedJobs['total'] > 0)
                    <div class="flex gap-2">
                        <x-filament::button size="xs" color="warning" wire:click="retryAllFailedJobs">
                            Retry All
                        </x-filament::button>
                        <x-filament::button size="xs" color="danger" wire:click="flushFailedJobs" wire:confirm="Are you sure you want to delete all failed jobs?">
                            Clear All
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-slot>

        @if(count($failedJobs['jobs']) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-3 font-medium">Job</th>
                            <th class="text-left py-2 px-3 font-medium">Queue</th>
                            <th class="text-left py-2 px-3 font-medium">Failed At</th>
                            <th class="text-left py-2 px-3 font-medium">Exception</th>
                            <th class="text-right py-2 px-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs['jobs'] as $job)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="py-2 px-3 font-mono text-xs">{{ $job['job'] }}</td>
                                <td class="py-2 px-3">
                                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700">
                                        {{ $job['queue'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-500">
                                    {{ \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() }}
                                </td>
                                <td class="py-2 px-3 text-xs text-danger-600 dark:text-danger-400 max-w-xs truncate" title="{{ $job['exception'] }}">
                                    {{ $job['exception'] }}
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <x-filament::button size="xs" color="gray" wire:click="retryFailedJob('{{ $job['id'] }}')">
                                        Retry
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-check-badge class="w-12 h-12 mx-auto mb-2 text-success-500" />
                <p>No failed jobs. Everything is running smoothly!</p>
            </div>
        @endif
    </x-filament::section>

    {{-- Recent Job Batches --}}
    @if(count($recentActivity['batches']) > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-rectangle-stack class="w-5 h-5" />
                    Recent Job Batches
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($recentActivity['batches'] as $batch)
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium">{{ $batch['name'] }}</span>
                            <span class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($batch['created_at'])->diffForHumans() }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2">
                            <div
                                class="h-2 rounded-full {{ $batch['failed_jobs'] > 0 ? 'bg-danger-500' : 'bg-success-500' }}"
                                style="width: {{ $batch['progress'] }}%"
                            ></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $batch['total_jobs'] - $batch['pending_jobs'] }}/{{ $batch['total_jobs'] }} completed</span>
                            @if($batch['failed_jobs'] > 0)
                                <span class="text-danger-600">{{ $batch['failed_jobs'] }} failed</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
