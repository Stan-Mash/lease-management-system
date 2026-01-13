<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Verification - Chabrin Lease System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Lease Verification</h1>
                <p class="text-gray-600">Verify the authenticity of your lease document</p>
            </div>

            <!-- Verification Card -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                @if($verified && $lease)
                    <!-- Success State -->
                    <div class="text-center mb-6">
                        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-green-600 mb-2">Document Verified!</h2>
                        <p class="text-gray-600">This is an authentic lease document from Chabrin Lease System</p>
                    </div>

                    <!-- Document Details -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Information</h3>
                        <dl class="space-y-3">
                            @if($lease->serial_number)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Serial Number:</dt>
                                <dd class="font-medium text-gray-900">{{ $lease->serial_number }}</dd>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Reference Number:</dt>
                                <dd class="font-medium text-gray-900">{{ $lease->reference_number }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Lease Type:</dt>
                                <dd class="font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $lease->lease_type)) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Status:</dt>
                                <dd>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                        {{ $lease->workflow_state === 'active' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucwords(str_replace('_', ' ', $lease->workflow_state)) }}
                                    </span>
                                </dd>
                            </div>
                            @if($lease->start_date && $lease->end_date)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Lease Period:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $lease->start_date->format('M d, Y') }} - {{ $lease->end_date->format('M d, Y') }}
                                </dd>
                            </div>
                            @endif
                            @if($lease->property)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Property:</dt>
                                <dd class="font-medium text-gray-900">{{ $lease->property->name }}</dd>
                            </div>
                            @endif
                            @if($lease->tenant)
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Tenant:</dt>
                                <dd class="font-medium text-gray-900">{{ $lease->tenant->full_name }}</dd>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Verified At:</dt>
                                <dd class="font-medium text-gray-900">{{ now()->format('M d, Y H:i:s') }}</dd>
                            </div>
                        </dl>
                    </div>

                @elseif($error)
                    <!-- Error State -->
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-red-600 mb-2">Verification Failed</h2>
                        <p class="text-gray-700 mb-4">{{ $error }}</p>
                        @if($serialNumber)
                        <p class="text-sm text-gray-600">Serial/Reference: <span class="font-mono">{{ $serialNumber }}</span></p>
                        @endif
                    </div>

                @else
                    <!-- Initial State - No verification attempted -->
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Scan QR Code</h2>
                        <p class="text-gray-600">Scan the QR code on your lease document to verify its authenticity</p>
                    </div>
                @endif

                <!-- Security Notice -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-medium mb-1">Security Notice</p>
                            <p>This verification system uses cryptographic hashing to ensure document authenticity.
                            Each QR code contains a unique verification signature that cannot be forged.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-600 text-sm">
                <p>&copy; {{ date('Y') }} Chabrin Digital Lease Management System</p>
                <p class="mt-2">For questions or support, please contact your property manager</p>
            </div>
        </div>
    </div>
</body>
</html>
