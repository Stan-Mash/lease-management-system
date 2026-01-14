<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lease Approvals - {{ $landlord->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Lease Approvals</h1>
                    <p class="text-sm text-gray-600 mt-1">Review and approve lease agreements</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-700">{{ $landlord->name }}</p>
                    <p class="text-xs text-gray-500">{{ $landlord->phone ?? $landlord->email }}</p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $pendingLeases->total() }}</p>
                        <p class="text-sm text-gray-600">Pending</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $approvedLeases->count() }}</p>
                        <p class="text-sm text-gray-600">Recently Approved</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $rejectedLeases->count() }}</p>
                        <p class="text-sm text-gray-600">Recently Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Pending Approvals</h2>
                <p class="text-sm text-gray-600 mt-1">Leases requiring your review</p>
            </div>

            @if($pendingLeases->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No pending approvals</h3>
                <p class="mt-1 text-sm text-gray-500">All lease agreements have been reviewed.</p>
            </div>
            @else
            <div class="divide-y divide-gray-200">
                @foreach($pendingLeases as $lease)
                <div class="px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $lease->reference_number }}</h3>
                                <span class="status-badge status-pending">Pending Review</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                <div>
                                    <p class="text-xs text-gray-500">Tenant</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $lease->tenant->name }}</p>
                                    <p class="text-xs text-gray-600">{{ $lease->tenant->phone }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Monthly Rent</p>
                                    <p class="text-sm font-bold text-blue-600">KES {{ number_format($lease->monthly_rent, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Lease Period</p>
                                    <p class="text-sm text-gray-900">
                                        {{ $lease->start_date?->format('M d, Y') }} - {{ $lease->end_date?->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Submitted {{ $lease->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="ml-4">
                            <a href="{{ route('landlord.approvals.show', [$landlord->id, $lease->id]) }}"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                                Review
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $pendingLeases->links() }}
            </div>
            @endif
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Recently Approved -->
            @if($approvedLeases->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                    <h3 class="text-md font-semibold text-green-900">Recently Approved</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($approvedLeases as $lease)
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $lease->reference_number }}</p>
                                <p class="text-xs text-gray-600">{{ $lease->tenant->name }}</p>
                            </div>
                            <span class="status-badge status-approved">Approved</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $lease->approvals->first()->reviewed_at?->diffForHumans() }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recently Rejected -->
            @if($rejectedLeases->isNotEmpty())
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                    <h3 class="text-md font-semibold text-red-900">Recently Rejected</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($rejectedLeases as $lease)
                    <div class="px-6 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $lease->reference_number }}</p>
                                <p class="text-xs text-gray-600">{{ $lease->tenant->name }}</p>
                            </div>
                            <span class="status-badge status-rejected">Rejected</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $lease->approvals->first()->reviewed_at?->diffForHumans() }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} Chabrin Lease Management System</p>
        </div>
    </div>
</body>
</html>
