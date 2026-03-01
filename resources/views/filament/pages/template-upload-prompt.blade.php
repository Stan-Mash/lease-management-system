<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload PDF – {{ $template->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30 p-6 shadow-lg">
        <h2 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">
            @if(!empty($message))
                {{ $message }}
            @else
                Upload your PDF to use this template
            @endif
        </h2>
        <p class="text-amber-700 dark:text-amber-300 mb-4">
            The template <strong>{{ $template->name }}</strong>
            @if(!empty($message))
                has a PDF path set but the file was not found. Re-upload the PDF on the PDF Upload tab.
            @else
                has no uploaded PDF. Until you upload one, generated leases will use the fallback layout.
            @endif
        </p>
        <p class="text-sm text-amber-600 dark:text-amber-400 mb-4 font-medium">
            To get output that looks like your uploaded document:
        </p>
        <ol class="list-decimal list-inside space-y-2 text-amber-700 dark:text-amber-300 mb-4">
            <li>Go to the <strong>PDF Upload</strong> tab</li>
            <li>Upload your CHABRIN lease PDF</li>
            <li>Click <strong>Save changes</strong></li>
            <li>Use <strong>Pick positions on PDF</strong> to mark where data goes</li>
        </ol>
        <a href="{{ $editUrl }}" 
           class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
            Go to PDF Upload tab →
        </a>
    </div>
</body>
</html>
