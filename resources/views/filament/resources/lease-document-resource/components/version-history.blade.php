<div class="space-y-3">
    @forelse($versions as $version)
        <div class="flex items-center gap-4 p-3 rounded-lg {{ $version->id === $currentId ? 'bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800' : 'bg-gray-50 dark:bg-gray-800/50' }}">
            {{-- Version Badge --}}
            <div class="flex-shrink-0">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-full {{ $version->id === $currentId ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-400' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }} font-bold text-lg">
                    v{{ $version->version ?? 1 }}
                </span>
            </div>

            {{-- Version Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $version->title }}
                    </p>
                    @if($version->id === $currentId)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-400">
                            Current
                        </span>
                    @endif
                </div>

                <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <x-heroicon-m-calendar class="w-3 h-3" />
                        {{ $version->created_at->format('M j, Y g:i A') }}
                    </span>

                    @if($version->uploader)
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-user class="w-3 h-3" />
                            {{ $version->uploader->name }}
                        </span>
                    @endif

                    <span class="flex items-center gap-1">
                        <x-heroicon-m-document class="w-3 h-3" />
                        {{ $version->file_size_for_humans }}
                    </span>

                    @if($version->integrity_status !== null)
                        <span class="flex items-center gap-1 {{ $version->integrity_status ? 'text-success-600' : 'text-danger-600' }}">
                            @if($version->integrity_status)
                                <x-heroicon-m-shield-check class="w-3 h-3" />
                                Verified
                            @else
                                <x-heroicon-m-shield-exclamation class="w-3 h-3" />
                                Integrity Issue
                            @endif
                        </span>
                    @endif
                </div>

                @if($version->file_hash)
                    <div class="mt-1">
                        <code class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ substr($version->file_hash, 0, 24) }}...
                        </code>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex-shrink-0 flex items-center gap-2">
                @if($version->id !== $currentId)
                    <a href="{{ route('filament.admin.resources.lease-documents.view', $version) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <x-heroicon-m-eye class="w-3 h-3" />
                        View
                    </a>
                @endif

                @if($version->getDownloadUrl())
                    <a href="{{ $version->getDownloadUrl() }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        <x-heroicon-m-arrow-down-tray class="w-3 h-3" />
                        Download
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-document-duplicate class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>This is the original document</p>
        </div>
    @endforelse
</div>
