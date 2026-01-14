<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Review Lease - {{ $lease->reference_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .info-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.875rem;
        }
        .info-value {
            color: #111827;
            font-size: 0.875rem;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 0;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Review Lease Agreement</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $lease->reference_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-700">{{ $landlord->name }}</p>
                    <a href="{{ route('landlord.approvals.index', $landlord->id) }}"
                       class="text-xs text-blue-600 hover:text-blue-800">
                        &larr; Back to List
                    </a>
                </div>
            </div>

            @if($lease->workflow_state === 'pending_landlord_approval')
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 font-medium">This lease is awaiting your approval</p>
                        <p class="text-xs text-yellow-600 mt-1">Please review carefully before making a decision</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Lease Information -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                <h2 class="text-lg font-semibold text-blue-900">Lease Details</h2>
            </div>
            <div class="px-6 py-4">
                <div class="info-row">
                    <span class="info-label">Lease Type:</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $lease->lease_type)) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Property:</span>
                    <span class="info-value">{{ $lease->property_address ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Monthly Rent:</span>
                    <span class="info-value font-bold text-blue-600">KES {{ number_format($lease->monthly_rent, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Security Deposit:</span>
                    <span class="info-value">KES {{ number_format($lease->security_deposit ?? $lease->deposit_amount ?? 0, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Start Date:</span>
                    <span class="info-value">{{ $lease->start_date?->format('M d, Y') ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">End Date:</span>
                    <span class="info-value">{{ $lease->end_date?->format('M d, Y') ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Duration:</span>
                    <span class="info-value">
                        @if($lease->start_date && $lease->end_date)
                            {{ $lease->start_date->diffInMonths($lease->end_date) }} months
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Tenant Information -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <h2 class="text-lg font-semibold text-green-900">Tenant Information</h2>
            </div>
            <div class="px-6 py-4">
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value">{{ $lease->tenant->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $lease->tenant->phone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $lease->tenant->email ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Number:</span>
                    <span class="info-value">{{ $lease->tenant->id_number ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Guarantors -->
        @if($lease->guarantors->isNotEmpty())
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                <h2 class="text-lg font-semibold text-purple-900">Guarantors</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                @foreach($lease->guarantors as $guarantor)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-500">Name</p>
                            <p class="text-sm font-medium text-gray-900">{{ $guarantor->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="text-sm text-gray-900">{{ $guarantor->phone }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Relationship</p>
                            <p class="text-sm text-gray-900">{{ $guarantor->relationship ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Guarantee Amount</p>
                            <p class="text-sm font-semibold text-gray-900">KES {{ number_format($guarantor->guarantee_amount ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Special Terms -->
        @if($lease->special_terms)
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Special Terms & Conditions</h2>
            </div>
            <div class="px-6 py-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $lease->special_terms }}</p>
            </div>
        </div>
        @endif

        <!-- Approval Status -->
        @if($approval)
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200
                @if($approval->decision === 'approved') bg-green-50
                @elseif($approval->decision === 'rejected') bg-red-50
                @else bg-yellow-50 @endif">
                <h2 class="text-lg font-semibold
                    @if($approval->decision === 'approved') text-green-900
                    @elseif($approval->decision === 'rejected') text-red-900
                    @else text-yellow-900 @endif">
                    Approval Status
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @if($approval->decision === 'approved')
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Approved</span>
                        @elseif($approval->decision === 'rejected')
                            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Rejected</span>
                        @else
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Pending</span>
                        @endif
                    </span>
                </div>
                @if($approval->reviewed_at)
                <div class="info-row">
                    <span class="info-label">Reviewed:</span>
                    <span class="info-value">{{ $approval->reviewed_at->format('M d, Y h:i A') }}</span>
                </div>
                @endif
                @if($approval->comments)
                <div class="info-row">
                    <span class="info-label">Comments:</span>
                    <span class="info-value">{{ $approval->comments }}</span>
                </div>
                @endif
                @if($approval->rejection_reason)
                <div class="info-row">
                    <span class="info-label">Reason:</span>
                    <span class="info-value text-red-600 font-medium">{{ $approval->rejection_reason }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        @if($lease->workflow_state === 'pending_landlord_approval')
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Take Action</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button onclick="openApproveModal()"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition flex items-center justify-center">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Approve Lease
                </button>
                <button onclick="openRejectModal()"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition flex items-center justify-center">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Reject Lease
                </button>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} Chabrin Lease Management System</p>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <h3 class="text-lg font-semibold text-green-900">Approve Lease</h3>
            </div>
            <form action="{{ route('landlord.approvals.approve', [$landlord->id, $lease->id]) }}" method="POST">
                @csrf
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-700 mb-4">Are you sure you want to approve this lease agreement?</p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments (Optional)</label>
                        <textarea name="comments" rows="3" maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                  placeholder="Add any comments about your approval..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" onclick="closeApproveModal()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                        Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-semibold text-red-900">Reject Lease</h3>
            </div>
            <form action="{{ route('landlord.approvals.reject', [$landlord->id, $lease->id]) }}" method="POST">
                @csrf
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-700 mb-4">Please provide a reason for rejecting this lease agreement.</p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <input type="text" name="rejection_reason" required maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                               placeholder="e.g., Rent amount too low, Wrong tenant details...">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Comments (Optional)</label>
                        <textarea name="comments" rows="3" maxlength="1000"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                  placeholder="Provide more details..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 rounded-b-lg">
                    <button type="button" onclick="closeRejectModal()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal() {
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        function openRejectModal() {
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const approveModal = document.getElementById('approveModal');
            const rejectModal = document.getElementById('rejectModal');
            if (event.target == approveModal) {
                closeApproveModal();
            }
            if (event.target == rejectModal) {
                closeRejectModal();
            }
        }
    </script>
</body>
</html>
