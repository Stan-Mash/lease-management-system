<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Already Signed - {{ $lease->reference_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-2xl w-full">
        <!-- Success Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Lease Already Signed</h1>
            <p class="text-lg text-gray-600 mb-8">
                This lease agreement has already been digitally signed and cannot be signed again.
            </p>

            <!-- Lease Details -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Lease Information</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Reference Number</p>
                        <p class="font-semibold">{{ $lease->reference_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Tenant Name</p>
                        <p class="font-semibold">{{ $lease->tenant->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Property Type</p>
                        <p class="font-semibold">{{ ucfirst($lease->lease_type) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Monthly Rent</p>
                        <p class="font-semibold">{{ number_format($lease->monthly_rent, 2) }} {{ $lease->currency }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Start Date</p>
                        <p class="font-semibold">{{ $lease->start_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Current Status</p>
                        <p class="font-semibold">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucwords(str_replace('_', ' ', $lease->workflow_state)) }}
                            </span>
                        </p>
                    </div>
                </div>

                @if($lease->digitalSignatures->isNotEmpty())
                    @php
                        $signature = $lease->digitalSignatures->first();
                    @endphp
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <strong>Signed on:</strong> {{ $signature->signed_at->format('d M Y, h:i A') }}
                        </p>
                        @if($signature->signature_latitude && $signature->signature_longitude)
                        <p class="text-sm text-gray-600 mt-1">
                            <strong>Location:</strong> {{ number_format($signature->signature_latitude, 6) }}, {{ number_format($signature->signature_longitude, 6) }}
                        </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- What's Next -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">What happens next?</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• You will receive a confirmation email with the signed lease document</li>
                    <li>• A copy will be sent to your registered phone via SMS</li>
                    <li>• The lease is now active and legally binding</li>
                    <li>• Please keep the confirmation for your records</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                @if($lease->document_path)
                <a href="{{ route('tenant.view-lease', ['lease' => $lease->id, 'signature' => request()->query('signature')]) }}"
                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition text-center">
                    View Lease Document
                </a>
                @endif
                <button onclick="window.print()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-6 rounded-lg transition">
                    Print This Page
                </button>
            </div>

            <!-- Support Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600">
                    Need help or have questions?
                </p>
                <p class="text-sm text-gray-600 mt-1">
                    Contact Chabrin Support at
                    <a href="mailto:support@chabrin.com" class="text-blue-600 hover:underline">support@chabrin.com</a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 mt-6">
            <p>© {{ date('Y') }} Chabrin Lease Management System</p>
        </div>
    </div>

    <style>
        @media print {
            body {
                background: white;
            }
            .bg-white {
                box-shadow: none;
            }
            button {
                display: none;
            }
        }
    </style>
</body>
</html>
