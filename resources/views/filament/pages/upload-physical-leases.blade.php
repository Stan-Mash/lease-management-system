<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Instructions --}}
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex gap-3">
                <x-heroicon-o-information-circle class="w-6 h-6 text-amber-600 flex-shrink-0" />
                <div class="text-sm text-amber-800">
                    <p class="font-medium mb-1">How to upload historical physical leases:</p>
                    <ol class="list-decimal list-inside space-y-1 text-amber-700">
                        <li>Select the <strong>Property</strong> and <strong>Unit</strong> (e.g., 314E-01)</li>
                        <li>Select the <strong>Tenant</strong> from the list</li>
                        <li>If an existing lease is found, it will be auto-selected</li>
                        <li>If no lease exists, fill in the rent details to create one</li>
                        <li>Enter the <strong>date on the physical document</strong></li>
                        <li>Upload the <strong>scanned PDF or image</strong></li>
                        <li>Click <strong>Upload & Save</strong></li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form wire:submit="upload">
            {{ $this->form }}

            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button type="button" color="gray" wire:click="$refresh">
                    Reset Form
                </x-filament::button>
                <x-filament::button type="submit" color="primary">
                    <x-heroicon-m-arrow-up-tray class="w-5 h-5 mr-2" />
                    Upload & Save
                </x-filament::button>
            </div>
        </form>

        {{-- Recent Uploads --}}
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Uploads (Today)</h3>
            @php
                $recentDocs = \App\Models\LeaseDocument::with(['lease.tenant', 'lease.unit'])
                    ->whereDate('created_at', today())
                    ->where('uploaded_by', auth()->id())
                    ->latest()
                    ->take(10)
                    ->get();
            @endphp

            @if($recentDocs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lease</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentDocs as $doc)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $doc->title }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->lease?->reference_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->lease?->tenant?->full_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->lease?->unit?->unit_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $doc->file_size_for_humans }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $doc->created_at->format('H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">No documents uploaded today yet.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
