<div class="space-y-4">
    @if($previewUrl)
        @if(str_starts_with($mimeType, 'image/'))
            {{-- Image Preview --}}
            <div class="flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg p-4">
                <img
                    src="{{ $previewUrl }}"
                    alt="{{ $filename }}"
                    class="max-w-full max-h-[75vh] object-contain rounded shadow-lg"
                    loading="lazy"
                />
            </div>
        @elseif($mimeType === 'application/pdf')
            {{-- PDF Preview in iframe --}}
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                <iframe
                    src="{{ $previewUrl }}"
                    class="w-full border-0 rounded"
                    style="height: 80vh; min-height: 600px;"
                    title="Document Preview: {{ $filename }}"
                    loading="lazy"
                ></iframe>
            </div>
        @endif

        {{-- Document info bar --}}
        <div class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-900 rounded-lg text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <x-heroicon-o-document class="w-4 h-4" />
                <span>{{ $filename }}</span>
            </div>
            <a href="{{ $previewUrl }}" target="_blank" class="flex items-center gap-1 text-primary-600 hover:text-primary-800 dark:text-primary-400">
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                Open in new tab
            </a>
        </div>
    @else
        {{-- No preview available --}}
        <div class="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-document class="w-16 h-16 mb-4 opacity-50" />
            <p class="text-lg font-medium">Preview not available</p>
            <p class="text-sm">This file type cannot be previewed inline.</p>
        </div>
    @endif
</div>
