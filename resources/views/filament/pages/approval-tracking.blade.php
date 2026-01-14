<x-filament-panels::page>
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                        <x-heroicon-o-clock class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_pending'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Pending</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_overdue'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Overdue (>24hrs)</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900">
                        <x-heroicon-o-user-group class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['landlords_with_pending'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Landlords</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <x-heroicon-o-currency-dollar class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_value_pending']) }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">KES Pending Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <div class="mb-6 flex justify-end">
        <x-filament::button wire:click="refreshData" icon="heroicon-o-arrow-path" color="gray">
            Refresh Data
        </x-filament::button>
    </div>

    <!-- Overdue Approvals Section -->
    @if($overdueApprovals->isNotEmpty())
    <div class="mb-6">
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border-l-4 border-red-500">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-4">
                        Overdue Approvals Requiring Immediate Follow-up
                    </h3>
                    <div class="space-y-3">
                        @foreach($overdueApprovals as $lease)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-800">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $lease->reference_number }}</span>
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 text-xs rounded-full">
                                            Overdue {{ $lease->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-600 dark:text-gray-400">Landlord:</p>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $lease->landlord->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600 dark:text-gray-400">Tenant:</p>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $lease->tenant->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600 dark:text-gray-400">Monthly Rent:</p>
                                            <p class="font-medium text-blue-600 dark:text-blue-400">KES {{ number_format($lease->monthly_rent) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <a href="{{ route('filament.admin.resources.leases.view', $lease) }}"
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Pending Approvals by Landlord -->
    <div class="space-y-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Pending Approvals by Landlord</h2>

        @if($pendingByLandlord->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
            <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-green-500 dark:text-green-400" />
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No Pending Approvals</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All leases have been reviewed by landlords.</p>
        </div>
        @else
        @foreach($pendingByLandlord as $landlordId => $data)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Landlord Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $data['landlord']->name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $data['landlord']->phone ?? $data['landlord']->email }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-4">
                            <div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $data['pending_count'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Pending</p>
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">KES {{ number_format($data['total_rent_value']) }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Total Value</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 flex items-center text-xs text-gray-600 dark:text-gray-400">
                    <x-heroicon-o-clock class="h-4 w-4 mr-1" />
                    Oldest pending: {{ $data['oldest_pending']->diffForHumans() }}
                </div>
            </div>

            <!-- Lease List -->
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($data['leases'] as $lease)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $lease->reference_number }}</span>
                                @if($lease->created_at < now()->subHours(24))
                                <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 text-xs rounded-full">
                                    Overdue
                                </span>
                                @elseif($lease->created_at < now()->subHours(12))
                                <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-100 text-xs rounded-full">
                                    Urgent
                                </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400">Tenant</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $lease->tenant->name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400">Monthly Rent</p>
                                    <p class="font-semibold text-blue-600 dark:text-blue-400">KES {{ number_format($lease->monthly_rent) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400">Lease Type</p>
                                    <p class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $lease->lease_type)) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400">Submitted</p>
                                    <p class="text-gray-900 dark:text-white">{{ $lease->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="ml-4 flex gap-2">
                            <a href="{{ route('filament.admin.resources.leases.view', $lease) }}"
                               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                                <x-heroicon-o-eye class="h-4 w-4 mr-1" />
                                View
                            </a>
                            <a href="{{ route('filament.admin.resources.leases.edit', $lease) }}"
                               class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition">
                                <x-heroicon-o-pencil class="h-4 w-4 mr-1" />
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
        @endif
    </div>
</x-filament-panels::page>
