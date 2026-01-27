<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">
                        {{ \App\Models\LeasePrintLog::today()->sum('copies_printed') }}
                    </div>
                    <div class="text-sm text-gray-500">Printed Today</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-warning-600">
                        {{ \App\Models\LeasePrintLog::thisWeek()->sum('copies_printed') }}
                    </div>
                    <div class="text-sm text-gray-500">This Week</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">
                        {{ \App\Models\LeasePrintLog::thisMonth()->sum('copies_printed') }}
                    </div>
                    <div class="text-sm text-gray-500">This Month</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-600">
                        {{ \App\Models\LeasePrintLog::thisMonth()->distinct('user_id')->count('user_id') }}
                    </div>
                    <div class="text-sm text-gray-500">Active Users</div>
                </div>
            </x-filament::section>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
