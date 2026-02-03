<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="CHIPS Database" title="Financial System" details="Connection health">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        <div class="grid gap-3 mx-px mb-px">
            @if($status === 'not_configured')
                <div class="flex flex-col items-center justify-center p-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Connection not configured</p>
                    <p class="text-xs text-gray-400 mt-1">Configure 'chips_db' in database.php</p>
                </div>
            @else
                {{-- Status indicator --}}
                <div class="flex items-center justify-between px-3 pt-3">
                    <div class="flex items-center gap-2">
                        @if($status === 'connected')
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">Connected</span>
                        @elseif($status === 'down')
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">Down</span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-400"></span>
                            <span class="text-sm font-medium text-gray-500">{{ ucfirst($status) }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-gray-500">Uptime</span>
                        <span class="ml-1 text-sm font-semibold {{ $uptimePercentage >= 99 ? 'text-green-600' : ($uptimePercentage >= 95 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ $uptimePercentage }}%
                        </span>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-3 px-3 py-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-xl font-bold {{ $responseTime > 500 ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">
                            {{ number_format($responseTime, 0) }}ms
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Response Time</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100 truncate">
                            {{ $connection }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Connection</div>
                    </div>
                </div>

                {{-- Error message --}}
                @if($status === 'down' && $message)
                    <div class="mx-3 mb-3 p-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-700 dark:text-red-400">
                        {{ $message }}
                    </div>
                @endif

                {{-- Last check time --}}
                <div class="px-3 pb-3 text-xs text-gray-400 text-right">
                    @if($checkedAt)
                        Checked {{ \Carbon\Carbon::parse($checkedAt)->diffForHumans() }}
                    @endif
                </div>
            @endif
        </div>
    </x-pulse::scroll>
</x-pulse::card>
