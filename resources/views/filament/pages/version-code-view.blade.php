<div class="space-y-6">
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Blade Template Code</h3>
        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
            <pre class="text-xs font-mono whitespace-pre-wrap">{{ $bladeContent }}</pre>
        </div>
    </div>

    @if($cssStyles && count($cssStyles) > 0)
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">CSS Styles</h3>
        <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto">
            <pre class="text-xs font-mono">@foreach($cssStyles as $key => $value)
{{ $key }}: {{ $value }};
@endforeach</pre>
        </div>
    </div>
    @endif

    <div class="flex justify-end">
        <button type="button"
                onclick="navigator.clipboard.writeText(document.querySelector('pre').textContent)"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
            </svg>
            Copy Code
        </button>
    </div>
</div>
