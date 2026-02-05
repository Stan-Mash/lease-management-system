<x-filament-panels::page>
    {{-- Hero Section --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-500 to-primary-700 p-8 text-white shadow-xl mb-8">
        <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,transparent,white)]"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Lease Portfolio</h1>
                    <p class="mt-2 text-primary-100 text-lg">Unified management for all your lease agreements and documents</p>
                </div>
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{ $createLeaseUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-white/20 backdrop-blur-sm px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/30 transition-all duration-200 border border-white/20">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        New Lease
                    </a>
                    <a href="{{ $uploadDocumentUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50 transition-all duration-200 shadow-lg">
                        <x-heroicon-o-cloud-arrow-up class="w-5 h-5" />
                        Upload Documents
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Grid (Mobile) --}}
    <div class="md:hidden grid grid-cols-2 gap-3 mb-6">
        <a href="{{ $createLeaseUrl }}" class="flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                <x-heroicon-o-plus class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">New Lease</span>
        </a>
        <a href="{{ $uploadDocumentUrl }}" class="flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-info-100 dark:bg-info-900/30 flex items-center justify-center">
                <x-heroicon-o-cloud-arrow-up class="w-5 h-5 text-info-600 dark:text-info-400" />
            </div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Upload</span>
        </a>
    </div>

    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Lease Agreements Card --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <x-heroicon-o-document-text class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lease Agreements</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Active contracts & agreements</p>
                    </div>
                </div>
                <a href="{{ $leaseResourceUrl }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                    View All
                    <x-heroicon-o-arrow-right class="w-4 h-4" />
                </a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-xl bg-success-50 dark:bg-success-900/20">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ number_format($leaseStats['active']) }}</div>
                        <div class="text-xs font-medium text-success-700 dark:text-success-300 mt-1">Active</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-warning-50 dark:bg-warning-900/20">
                        <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($leaseStats['pending']) }}</div>
                        <div class="text-xs font-medium text-warning-700 dark:text-warning-300 mt-1">Pending</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <div class="text-3xl font-bold text-gray-600 dark:text-gray-300">{{ number_format($leaseStats['draft']) }}</div>
                        <div class="text-xs font-medium text-gray-700 dark:text-gray-400 mt-1">Draft</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar-days class="w-5 h-5 text-orange-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Expiring in 30 days</span>
                        </div>
                        <span class="text-sm font-semibold {{ $leaseStats['expiring_soon'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500' }}">
                            {{ number_format($leaseStats['expiring_soon']) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-red-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Expired</span>
                        </div>
                        <span class="text-sm font-semibold {{ $leaseStats['expired'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500' }}">
                            {{ number_format($leaseStats['expired']) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-document-duplicate class="w-5 h-5 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Total Leases</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($leaseStats['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Document Vault Card --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <x-heroicon-o-archive-box class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Document Vault</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Scanned documents & files</p>
                    </div>
                </div>
                <a href="{{ $documentResourceUrl }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                    View All
                    <x-heroicon-o-arrow-right class="w-4 h-4" />
                </a>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-xl bg-success-50 dark:bg-success-900/20">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">{{ number_format($documentStats['approved']) }}</div>
                        <div class="text-xs font-medium text-success-700 dark:text-success-300 mt-1">Approved</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-warning-50 dark:bg-warning-900/20">
                        <div class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($documentStats['pending_review']) }}</div>
                        <div class="text-xs font-medium text-warning-700 dark:text-warning-300 mt-1">Pending</div>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-info-50 dark:bg-info-900/20">
                        <div class="text-3xl font-bold text-info-600 dark:text-info-400">{{ number_format($documentStats['linked']) }}</div>
                        <div class="text-xs font-medium text-info-700 dark:text-info-300 mt-1">Linked</div>
                    </div>
                </div>

                <div class="space-y-3">
                    @if($documentStats['pending_review'] > 0)
                    <a href="{{ $reviewQueueUrl }}" class="flex items-center justify-between p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20 hover:bg-warning-100 dark:hover:bg-warning-900/30 transition-colors group">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-warning-600" />
                            <span class="text-sm font-medium text-warning-700 dark:text-warning-300">Review Queue</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-warning-600 dark:text-warning-400">{{ number_format($documentStats['pending_review']) }} pending</span>
                            <x-heroicon-o-arrow-right class="w-4 h-4 text-warning-500 group-hover:translate-x-1 transition-transform" />
                        </div>
                    </a>
                    @endif
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-link-slash class="w-5 h-5 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Unlinked Documents</span>
                        </div>
                        <span class="text-sm font-semibold {{ $documentStats['unlinked'] > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-500' }}">
                            {{ number_format($documentStats['unlinked']) }}
                        </span>
                    </div>
                    @if($documentStats['quality_issues'] > 0)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                            <span class="text-sm text-danger-700 dark:text-danger-300">Quality Issues</span>
                        </div>
                        <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">
                            {{ number_format($documentStats['quality_issues']) }}
                        </span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-archive-box class="w-5 h-5 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Total Documents</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($documentStats['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- My Activity & Quick Links --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- My Uploads Quick Card --}}
        <a href="{{ $myUploadsUrl }}" class="group rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02]">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <x-heroicon-o-folder-open class="w-6 h-6 text-white" />
                </div>
                <x-heroicon-o-arrow-right class="w-5 h-5 text-white/70 group-hover:translate-x-1 group-hover:text-white transition-all" />
            </div>
            <div class="text-3xl font-bold mb-1">{{ number_format($myUploads) }}</div>
            <div class="text-indigo-100 text-sm">My Uploads</div>
            @if($myPendingUploads > 0)
            <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/20 text-xs font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 animate-pulse"></span>
                {{ $myPendingUploads }} pending review
            </div>
            @endif
        </a>

        {{-- Quick Upload Card --}}
        <a href="{{ $uploadDocumentUrl }}" class="group rounded-2xl bg-white dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-all duration-300">
            <div class="flex flex-col items-center justify-center text-center h-full">
                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors">
                    <x-heroicon-o-cloud-arrow-up class="w-6 h-6 text-gray-400 dark:text-gray-500 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                </div>
                <div class="font-semibold text-gray-900 dark:text-white mb-1">Bulk Upload</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Upload multiple documents at once</div>
            </div>
        </a>

        {{-- Review Queue Quick Card --}}
        <a href="{{ $reviewQueueUrl }}" class="group rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                    <x-heroicon-o-clipboard-document-check class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                </div>
                <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 group-hover:translate-x-1 group-hover:text-primary-500 transition-all" />
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($documentStats['pending_review']) }}</div>
            <div class="text-gray-500 dark:text-gray-400 text-sm">Documents to Review</div>
            @if($documentStats['pending_review'] > 0)
            <div class="mt-3 text-xs text-warning-600 dark:text-warning-400 font-medium">Requires attention</div>
            @else
            <div class="mt-3 text-xs text-success-600 dark:text-success-400 font-medium">All caught up!</div>
            @endif
        </a>
    </div>

    {{-- Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Leases --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Leases</h3>
                <a href="{{ $leaseResourceUrl }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentLeases as $lease)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $lease->reference_number }}
                                </span>
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => $lease->workflow_state === 'active',
                                    'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => $lease->workflow_state === 'pending_landlord_approval',
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400' => $lease->workflow_state === 'draft',
                                    'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => in_array($lease->workflow_state, ['expired', 'terminated', 'cancelled']),
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $lease->workflow_state)) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $lease->tenant?->full_name ?? 'No tenant' }} &bull; {{ $lease->property?->name ?? 'No property' }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $lease->updated_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-document-text class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                    <p>No recent leases</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Documents --}}
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Documents</h3>
                <a href="{{ $documentResourceUrl }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentDocuments as $document)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                    {{ Str::limit($document->title, 40) }}
                                </span>
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => $document->status->value === 'approved',
                                    'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => $document->status->value === 'pending_review',
                                    'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => $document->status->value === 'rejected',
                                    'bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400' => $document->status->value === 'linked',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $document->status->value)) }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $document->zone?->name ?? 'No zone' }} &bull; {{ $document->uploader?->name ?? 'Unknown' }}
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $document->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-archive-box class="w-8 h-8 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                    <p>No recent documents</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
