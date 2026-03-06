<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guarantor signing — {{ $lease->reference_number ?? '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-md mx-auto">
        <h1 class="text-xl font-semibold text-gray-900">Guarantor signing</h1>
        <p class="mt-2 text-gray-600">Lease {{ $lease->reference_number ?? '' }} — {{ $guarantor->name ?? '' }}</p>
        <p class="mt-2 text-sm text-gray-500">OTP-gated signing portal. Request OTP to continue.</p>
    </div>
</body>
</html>
