<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header name="SMS Balance" title="Africa's Talking" details="Balance monitoring">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
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
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">API not configured</p>
                    <p class="text-xs text-gray-400 mt-1">Set credentials in .env</p>
                </div>
            @else
                {{-- Status indicator --}}
                <div class="flex items-center justify-between px-3 pt-3">
                    <div class="flex items-center gap-2">
                        @if($status === 'healthy')
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">Healthy</span>
                        @elseif($status === 'low')
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            </span>
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">Low Balance</span>
                        @else
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ ucfirst($status) }}</span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400">
                        @if($checkedAt)
                            {{ \Carbon\Carbon::parse($checkedAt)->diffForHumans() }}
                        @endif
                    </span>
                </div>

                {{-- Balance display --}}
                <div class="text-center py-4 px-3">
                    <div class="text-3xl font-bold {{ $status === 'low' ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-gray-100' }}">
                        {{ $currency }} {{ number_format($balance, 2) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Current Balance
                    </div>
                    @if($status === 'low')
                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Below threshold ({{ $currency }} {{ number_format($threshold, 2) }})
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-pulse::scroll>
</x-pulse::card>
