<x-filament-panels::page>
    {{-- Stats Bar --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Today's Uploads - Blue gradient --}}
        <div class="rounded-xl p-4 shadow-lg" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium" style="color: rgba(255,255,255,0.8);">Today's Uploads</p>
                    <p class="text-3xl font-bold" style="color: #ffffff;">{{ $stats['today'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(255,255,255,0.2);">
                    <x-heroicon-o-calendar class="w-6 h-6" style="color: #ffffff;" />
                </div>
            </div>
        </div>

        {{-- Pending Review - Amber/Orange gradient --}}
        <div class="rounded-xl p-4 shadow-lg" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium" style="color: rgba(255,255,255,0.9);">Pending Review</p>
                    <p class="text-3xl font-bold" style="color: #ffffff;">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(255,255,255,0.2);">
                    <x-heroicon-o-clock class="w-6 h-6" style="color: #ffffff;" />
                </div>
            </div>
        </div>

        {{-- Total Uploads - Emerald gradient --}}
        <div class="rounded-xl p-4 shadow-lg" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium" style="color: rgba(255,255,255,0.8);">Total Uploads</p>
                    <p class="text-3xl font-bold" style="color: #ffffff;">{{ $stats['total'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(255,255,255,0.2);">
                    <x-heroicon-o-archive-box class="w-6 h-6" style="color: #ffffff;" />
                </div>
            </div>
        </div>

        {{-- Accepted Formats --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2">Accepted Formats</p>
                <div class="flex flex-wrap justify-center gap-1.5">
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">PDF</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">DOC</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">JPG</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">PNG</span>
                    <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400">TIFF</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1.5">Max 25MB per file</p>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button
                    wire:click="setTab('bulk')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'bulk',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'bulk',
                    ])
                >
                    <x-heroicon-o-squares-plus @class([
                        'mr-2 h-5 w-5',
                        'text-primary-500' => $activeTab === 'bulk',
                        'text-gray-400 group-hover:text-gray-500' => $activeTab !== 'bulk',
                    ]) />
                    Bulk Upload
                    <span @class([
                        'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium',
                        'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400' => $activeTab === 'bulk',
                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $activeTab !== 'bulk',
                    ])>50 files</span>
                </button>

                <button
                    wire:click="setTab('single')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'single',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'single',
                    ])
                >
                    <x-heroicon-o-document-plus @class([
                        'mr-2 h-5 w-5',
                        'text-primary-500' => $activeTab === 'single',
                        'text-gray-400 group-hover:text-gray-500' => $activeTab !== 'single',
                    ]) />
                    Single Upload
                </button>

                <button
                    wire:click="setTab('lease')"
                    @class([
                        'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $activeTab === 'lease',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' => $activeTab !== 'lease',
                    ])
                >
                    <x-heroicon-o-link @class([
                        'mr-2 h-5 w-5',
                        'text-primary-500' => $activeTab === 'lease',
                        'text-gray-400 group-hover:text-gray-500' => $activeTab !== 'lease',
                    ]) />
                    Link to Lease
                    <span @class([
                        'ml-2 py-0.5 px-2 rounded-full text-xs font-medium',
                        'bg-success-100 text-success-600 dark:bg-success-900/30 dark:text-success-400' => $activeTab === 'lease',
                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $activeTab !== 'lease',
                    ])>Physical</span>
                </button>
            </nav>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        {{-- BULK UPLOAD TAB --}}
        <div x-data x-show="$wire.activeTab === 'bulk'" x-cloak>
            <form wire:submit="uploadBulk">
                {{ $this->bulkForm }}

                <div class="mt-6 flex items-center justify-between">
                    <x-filament::button type="submit" size="lg" wire:loading.attr="disabled" wire:target="uploadBulk">
                        <span wire:loading.remove wire:target="uploadBulk" class="flex items-center gap-2">
                            <x-heroicon-o-cloud-arrow-up class="w-5 h-5" />
                            Upload All Files
                        </span>
                        <span wire:loading wire:target="uploadBulk" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="w-5 h-5" />
                            Processing...
                        </span>
                    </x-filament::button>
                </div>
            </form>

            {{-- Bulk Upload Results --}}
            @if($bulkSuccessCount > 0 || $bulkFailedCount > 0)
                <div class="mt-6 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Upload Results</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $bulkSuccessCount }}</div>
                                <div class="text-sm text-success-700 dark:text-success-300">Successful</div>
                            </div>
                            <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">{{ $bulkFailedCount }}</div>
                                <div class="text-sm text-danger-700 dark:text-danger-300">Failed</div>
                            </div>
                        </div>

                        @if(count($bulkErrors) > 0)
                            <div class="mt-4 p-3 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                                <h4 class="text-sm font-medium text-danger-700 dark:text-danger-400 mb-2">Errors:</h4>
                                <ul class="list-disc list-inside space-y-1 text-sm text-danger-600 dark:text-danger-300">
                                    @foreach($bulkErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- SINGLE UPLOAD TAB --}}
        <div x-data x-show="$wire.activeTab === 'single'" x-cloak>
            <form wire:submit="uploadSingle">
                {{ $this->singleForm }}

                <div class="mt-6">
                    <x-filament::button type="submit" size="lg" wire:loading.attr="disabled" wire:target="uploadSingle">
                        <span wire:loading.remove wire:target="uploadSingle" class="flex items-center gap-2">
                            <x-heroicon-o-document-arrow-up class="w-5 h-5" />
                            Upload Document
                        </span>
                        <span wire:loading wire:target="uploadSingle" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="w-5 h-5" />
                            Uploading...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- LEASE-LINKED UPLOAD TAB --}}
        <div x-data x-show="$wire.activeTab === 'lease'" x-cloak>
            {{-- Instructions --}}
            <div class="mb-6 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                <div class="flex gap-3">
                    <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-semibold mb-1">Physical Lease Documents</p>
                        <p>Use this tab to upload scanned copies of signed physical leases. Documents will be directly linked to a lease record. If no lease exists, a new one will be created.</p>
                    </div>
                </div>
            </div>

            <form wire:submit="uploadLeaseLinked">
                {{ $this->leaseForm }}

                <div class="mt-6">
                    <x-filament::button type="submit" size="lg" wire:loading.attr="disabled" wire:target="uploadLeaseLinked">
                        <span wire:loading.remove wire:target="uploadLeaseLinked" class="flex items-center gap-2">
                            <x-heroicon-o-link class="w-5 h-5" />
                            Upload & Link to Lease
                        </span>
                        <span wire:loading wire:target="uploadLeaseLinked" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="w-5 h-5" />
                            Processing...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>

    {{-- Recent Uploads --}}
    @if($recentUploads->isNotEmpty())
        <div class="mt-8">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        Recent Uploads
                    </h3>
                    <a href="{{ \App\Filament\Resources\LeaseDocumentResource::getUrl('my-uploads') }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                        View All
                    </a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentUploads as $upload)
                        <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div @class([
                                    'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                                    'bg-success-100 dark:bg-success-900/30' => $upload->status->value === 'approved' || $upload->status->value === 'linked',
                                    'bg-warning-100 dark:bg-warning-900/30' => $upload->status->value === 'pending_review',
                                    'bg-danger-100 dark:bg-danger-900/30' => $upload->status->value === 'rejected',
                                ])>
                                    @if(str_contains($upload->mime_type, 'pdf'))
                                        <x-heroicon-o-document class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                    @elseif(str_contains($upload->mime_type, 'image'))
                                        <x-heroicon-o-photo class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                    @else
                                        <x-heroicon-o-document-text class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ Str::limit($upload->title, 40) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $upload->zone?->name ?? 'No zone' }}
                                        @if($upload->lease)
                                            &bull; <span class="text-primary-600 dark:text-primary-400">{{ $upload->lease->reference_number }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span @class([
                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => $upload->status->value === 'approved' || $upload->status->value === 'linked',
                                    'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => $upload->status->value === 'pending_review',
                                    'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => $upload->status->value === 'rejected',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $upload->status->value)) }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $upload->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tips Section --}}
    <div class="mt-6">
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <span class="flex items-center gap-2">
                    <x-heroicon-o-light-bulb class="w-5 h-5" />
                    Upload Tips & Best Practices
                </span>
            </x-slot>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mt-0">File Naming</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <li>Name files descriptively (e.g., "John_Doe_Lease_2024.pdf")</li>
                            <li>Bulk uploads use filename as document title</li>
                            <li>Avoid special characters in filenames</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mt-0">Quality Guidelines</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <li>Scan at 300 DPI for best results</li>
                            <li>Ensure all text is legible</li>
                            <li>PDF is the preferred format</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mt-0">Bulk Uploads</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <li>Up to 50 files per batch</li>
                            <li>All files share the same metadata</li>
                            <li>Best for multiple documents from same property</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mt-0">Lease Linking</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <li>Use for signed physical lease agreements</li>
                            <li>Creates new lease record if none exists</li>
                            <li>Documents are immediately linked (no review needed)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
