<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Preview - {{ $lease->reference_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .pdf-container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .pdf-toolbar {
            background: #1f2937;
            color: white;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pdf-viewer {
            flex: 1;
            overflow: auto;
            background: #f3f4f6;
        }
        embed, iframe {
            border: none;
        }
        @media print {
            .pdf-toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <!-- Toolbar -->
        <div class="pdf-toolbar">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-semibold">{{ $lease->reference_number }}</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="hidden sm:inline">Lease Agreement</span>
                <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded text-xs">
                    Print
                </button>
            </div>
        </div>

        <!-- PDF Viewer -->
        <div class="pdf-viewer">
            @if($lease->document_path)
                @php
                    $extension = strtolower(pathinfo($lease->document_path, PATHINFO_EXTENSION));
                    $fullPath = Storage::disk('public')->path($lease->document_path);
                @endphp

                @if($extension === 'pdf')
                    <!-- PDF File -->
                    <embed src="{{ Storage::disk('public')->url($lease->document_path) }}"
                           type="application/pdf"
                           width="100%"
                           height="100%">
                @else
                    <!-- Fallback for non-PDF documents -->
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center max-w-2xl mx-auto p-6">
                            <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-gray-700 mb-2">Document Available</h2>
                            <p class="text-gray-600 mb-4">
                                This document cannot be previewed in the browser.
                            </p>
                            <a href="{{ Storage::disk('public')->url($lease->document_path) }}"
                               download="{{ $lease->reference_number }}.{{ $extension }}"
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition">
                                Download Document
                            </a>
                        </div>
                    </div>
                @endif
            @else
                <!-- No document available - show lease details -->
                <div class="flex items-center justify-center h-full">
                    <div class="max-w-4xl w-full mx-auto p-8">
                        <div class="bg-white rounded-lg shadow-lg p-8">
                            <!-- Header -->
                            <div class="text-center border-b pb-6 mb-6">
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">LEASE AGREEMENT</h1>
                                <p class="text-sm text-gray-600">Reference: {{ $lease->reference_number }}</p>
                                <p class="text-sm text-gray-600">Generated: {{ now()->format('d F Y') }}</p>
                            </div>

                            <!-- Parties -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">PARTIES TO THE AGREEMENT</h2>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="bg-gray-50 p-4 rounded">
                                        <h3 class="font-semibold text-gray-700 mb-2">LANDLORD</h3>
                                        @if($lease->landlord)
                                            <p class="text-sm">{{ $lease->landlord->name }}</p>
                                            <p class="text-sm text-gray-600">ID: {{ $lease->landlord->id_number ?? 'N/A' }}</p>
                                            <p class="text-sm text-gray-600">Phone: {{ $lease->landlord->phone }}</p>
                                        @else
                                            <p class="text-sm text-gray-600">Information not available</p>
                                        @endif
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded">
                                        <h3 class="font-semibold text-gray-700 mb-2">TENANT</h3>
                                        <p class="text-sm">{{ $lease->tenant->names }}</p>
                                        <p class="text-sm text-gray-600">ID: {{ $lease->tenant->national_id ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-600">Phone: {{ $lease->tenant->mobile_number }}</p>
                                        <p class="text-sm text-gray-600">Email: {{ $lease->tenant->email_address ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Property Details -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">PROPERTY DETAILS</h2>
                                <div class="bg-gray-50 p-4 rounded">
                                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="font-semibold">Property Type:</span>
                                            <span class="ml-2">{{ ucfirst($lease->lease_type) }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Zone:</span>
                                            <span class="ml-2">{{ $lease->zone ?? 'N/A' }}</span>
                                        </div>
                                        @if($lease->property_address)
                                        <div class="md:col-span-2">
                                            <span class="font-semibold">Address:</span>
                                            <span class="ml-2">{{ $lease->property_address }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Terms -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">FINANCIAL TERMS</h2>
                                <div class="bg-gray-50 p-4 rounded">
                                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="font-semibold">Monthly Rent:</span>
                                            <span class="ml-2">{{ number_format($lease->monthly_rent, 2) }} {{ $lease->currency }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Security Deposit:</span>
                                            <span class="ml-2">{{ number_format($lease->security_deposit, 2) }} {{ $lease->currency }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Payment Day:</span>
                                            <span class="ml-2">{{ $lease->payment_day ?? 'N/A' }} of each month</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Payment Method:</span>
                                            <span class="ml-2">{{ ucfirst($lease->payment_method ?? 'N/A') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lease Period -->
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold mb-4">LEASE PERIOD</h2>
                                <div class="bg-gray-50 p-4 rounded">
                                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="font-semibold">Start Date:</span>
                                            <span class="ml-2">{{ $lease->start_date->format('d F Y') }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">End Date:</span>
                                            <span class="ml-2">{{ $lease->end_date->format('d F Y') }}</span>
                                        </div>
                                        <div>
                                            <span class="font-semibold">Duration:</span>
                                            <span class="ml-2">{{ $lease->start_date->diffInMonths($lease->end_date) }} months</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Note -->
                            <div class="text-center text-xs text-gray-500 mt-8 pt-6 border-t">
                                <p>This is a computer-generated preview. The full lease document will be provided upon signing.</p>
                                <p class="mt-1">For questions, contact Chabrin Support</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
