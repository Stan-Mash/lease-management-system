<x-filament-panels::page>
    {{-- Upload Requirements Banner --}}
    <div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="bg-primary-50 dark:bg-primary-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-primary-700 dark:text-primary-400 flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5" />
                Upload Requirements & Limits
            </h3>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Accepted File Types --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-document-check class="w-5 h-5 text-success-500" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Accepted File Types</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">
                            <x-heroicon-o-document class="w-3 h-3 mr-1" />
                            PDF
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400">
                            <x-heroicon-o-document-text class="w-3 h-3 mr-1" />
                            DOC
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400">
                            <x-heroicon-o-document-text class="w-3 h-3 mr-1" />
                            DOCX
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">
                            <x-heroicon-o-photo class="w-3 h-3 mr-1" />
                            JPG
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">
                            <x-heroicon-o-photo class="w-3 h-3 mr-1" />
                            PNG
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                            <x-heroicon-o-photo class="w-3 h-3 mr-1" />
                            TIFF
                        </span>
                    </div>
                </div>

                {{-- Size Limits --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-scale class="w-5 h-5 text-warning-500" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Size Limits</span>
                    </div>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center justify-between">
                            <span>Per file:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">25 MB max</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Files per batch:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">50 files max</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-500">
                            <span>Auto-compress:</span>
                            <span>Files > 5MB</span>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-info-500" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Your Upload Stats</span>
                    </div>
                    @php
                        $userId = auth()->id();
                        $todayCount = \App\Models\LeaseDocument::where('uploaded_by', $userId)->whereDate('created_at', today())->count();
                        $pendingCount = \App\Models\LeaseDocument::where('uploaded_by', $userId)->pendingReview()->count();
                        $totalCount = \App\Models\LeaseDocument::where('uploaded_by', $userId)->count();
                    @endphp
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center justify-between">
                            <span>Uploaded today:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $todayCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Pending review:</span>
                            <span class="font-semibold {{ $pendingCount > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-900 dark:text-white' }}">{{ $pendingCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Total uploads:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $totalCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="upload">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            <x-filament::button
                type="submit"
                size="lg"
                wire:loading.attr="disabled"
                wire:target="upload"
            >
                <span wire:loading.remove wire:target="upload">
                    <x-heroicon-o-cloud-arrow-up class="w-5 h-5 mr-2" />
                    Upload Documents
                </span>
                <span wire:loading wire:target="upload">
                    <x-filament::loading-indicator class="w-5 h-5 mr-2" />
                    Processing...
                </span>
            </x-filament::button>

            <a href="{{ \App\Filament\Resources\LeaseDocumentResource::getUrl('my-uploads') }}"
               class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                <x-heroicon-o-folder-open class="w-4 h-4" />
                View My Uploads
            </a>
        </div>
    </form>

    @if($successCount > 0 || $failedCount > 0)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Upload Results
                </x-slot>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                            {{ $successCount }}
                        </div>
                        <div class="text-sm text-success-700 dark:text-success-300">
                            Successful
                        </div>
                    </div>

                    <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-danger-600 dark:text-danger-400">
                            {{ $failedCount }}
                        </div>
                        <div class="text-sm text-danger-700 dark:text-danger-300">
                            Failed
                        </div>
                    </div>
                </div>

                @if(count($errors) > 0)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-danger-600 dark:text-danger-400 mb-2">
                            Errors:
                        </h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-danger-700 dark:text-danger-300">
                            @foreach($errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($successCount > 0)
                    <div class="mt-4 flex gap-3 justify-end">
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Resources\LeaseDocumentResource::getUrl('my-uploads') }}"
                            color="gray"
                            outlined
                        >
                            <x-heroicon-o-folder-open class="w-4 h-4 mr-1" />
                            View My Uploads
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Resources\LeaseDocumentResource::getUrl('index', ['activeTab' => 'pending']) }}"
                            color="primary"
                            outlined
                        >
                            <x-heroicon-o-clock class="w-4 h-4 mr-1" />
                            View Pending Documents
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>
        </div>
    @endif

    {{-- Tips Section --}}
    <div class="mt-6">
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <x-heroicon-o-light-bulb class="w-5 h-5 mr-2 inline" />
                Upload Tips
            </x-slot>

            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ul>
                    <li><strong>File Naming:</strong> Name your files descriptively (e.g., "John_Doe_Lease_2024.pdf"). The system will use the filename as the document title.</li>
                    <li><strong>Quality:</strong> Ensure scans are clear and legible. Poor quality scans will be flagged during review.</li>
                    <li><strong>Organization:</strong> Upload documents for the same zone/property together to save time.</li>
                    <li><strong>File Size:</strong> Files larger than 5MB will be automatically compressed to save storage space.</li>
                    <li><strong>Supported Formats:</strong> PDF (preferred), DOC, DOCX, JPG, PNG, TIFF</li>
                    <li><strong>After Upload:</strong> Documents go to the review queue. An administrator will review and approve them before they can be linked to lease records.</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
