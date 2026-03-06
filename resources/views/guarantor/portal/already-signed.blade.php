<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guarantor — Already signed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-md mx-auto">
        <h1 class="text-xl font-semibold text-gray-900">Already signed</h1>
        <p class="mt-2 text-gray-600">You have already signed as guarantor for lease {{ $lease->reference_number ?? '' }}.</p>
    </div>
</body>
</html>
