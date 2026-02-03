<x-filament-panels::page>
    <x-filament-panels::form wire:submit="upload">
        {{ $this->form }}

        <div class="mt-6">
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
        </div>
    </x-filament-panels::form>

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
                    <div class="mt-4 flex justify-end">
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Resources\LeaseDocumentResource::getUrl('index', ['activeTab' => 'pending']) }}"
                            color="primary"
                            outlined
                        >
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
