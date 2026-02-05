<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $statusColors = [
                'healthy' => 'text-success-600 dark:text-success-400',
                'warning' => 'text-warning-600 dark:text-warning-400',
                'critical' => 'text-danger-600 dark:text-danger-400',
            ];
            $statusBgColors = [
                'healthy' => 'bg-success-50 dark:bg-success-900/20',
                'warning' => 'bg-warning-50 dark:bg-warning-900/20',
                'critical' => 'bg-danger-50 dark:bg-danger-900/20',
            ];
            $statusIcons = [
                'healthy' => 'heroicon-o-check-circle',
                'warning' => 'heroicon-o-exclamation-triangle',
                'critical' => 'heroicon-o-x-circle',
            ];
        @endphp

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                {{-- Pulse Icon with Status --}}
                <div class="relative">
                    <x-heroicon-o-heart class="w-10 h-10 {{ $statusColors[$stats['overallStatus']] }}" />
                    @if($stats['overallStatus'] === 'healthy')
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">System Pulse</h3>
                    <p class="text-sm {{ $statusColors[$stats['overallStatus']] }}">
                        @if($stats['overallStatus'] === 'healthy')
                            All systems operational
                        @elseif($stats['overallStatus'] === 'warning')
                            Some issues detected
                        @else
                            Critical issues detected
                        @endif
                    </p>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="flex items-center gap-6">
                {{-- Queue Depth --}}
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $stats['queueDepth'] > 100 ? 'text-warning-600' : 'text-gray-900 dark:text-white' }}">
                        {{ number_format($stats['queueDepth']) }}
                    </div>
                    <div class="text-xs text-gray-500">Queue</div>
                </div>

                {{-- Failed Jobs --}}
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $stats['failedJobs'] > 0 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                        {{ number_format($stats['failedJobs']) }}
                    </div>
                    <div class="text-xs text-gray-500">Failed (24h)</div>
                </div>

                {{-- Worker Status --}}
                <div class="text-center">
                    <x-dynamic-component
                        :component="$statusIcons[$stats['workerStatus']]"
                        class="w-8 h-8 mx-auto {{ $statusColors[$stats['workerStatus']] }}"
                    />
                    <div class="text-xs text-gray-500">Worker</div>
                </div>

                {{-- View Details Link --}}
                <a
                    href="{{ route('filament.admin.pages.system-pulse') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30 transition"
                >
                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                    View Details
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
