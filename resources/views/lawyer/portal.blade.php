<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lease for review – {{ $lease->reference_number }}</title>
    <script nonce="{{ $cspNonce }}" src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-10">

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-indigo-700 px-6 py-4">
                <h1 class="text-xl font-bold text-white">Chabrin Agencies – Advocate portal</h1>
                <p class="text-indigo-200 text-sm mt-1">Lease reference: {{ $lease->reference_number }}</p>
            </div>

            <div class="p-6 space-y-6">
                @if(session('success'))
                    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="text-gray-600">
                    This lease has been sent to you for legal review and advocate stamping. Download the PDF below and review it carefully.
                </p>

                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 mb-2">1. Download lease PDF</h2>
                    <a href="{{ $downloadUrl }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download lease PDF
                    </a>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 mb-2">2. Confirm completion</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        After you have completed your review and stamping on your side, click the button below so the Chabrin team can proceed.
                    </p>
                    <form action="{{ route('lawyer.portal.upload', ['token' => $token]) }}" method="post" class="space-y-4">
                        @csrf
                        <input type="hidden" id="geolocation_data" name="geolocation_data" value="">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Mark as completed
                        </button>
                    </form>
                </div>

                @if($expiresAt)
                    <p class="text-xs text-gray-500">This link expires on {{ $expiresAt->format('d M Y') }}.</p>
                @endif
            </div>
        </div>

    </div>
        <p class="mt-4 text-xs text-gray-400 text-center">
            This portal does not accept uploaded PDFs. All final stamping is applied on a secured copy of the lease stored on Chabrin's servers.
        </p>
    </div>

    <script nonce="{{ $cspNonce }}">
        const geoField = document.getElementById('geolocation_data');
        if (navigator.geolocation && geoField) {
            navigator.geolocation.getCurrentPosition(function (pos) {
                geoField.value = pos.coords.latitude + ',' + pos.coords.longitude;
            });
        }

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) { window.location.reload(); }
        });
    </script>
</body>
</html>
