<div class="space-y-4">
    @forelse($audits as $audit)
        <div class="flex items-start gap-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50">
            {{-- Action Icon --}}
            <div class="flex-shrink-0">
                @php
                    $color = \App\Models\DocumentAudit::getActionColors()[$audit->action] ?? 'gray';
                    $icon = \App\Models\DocumentAudit::getActionIcons()[$audit->action] ?? 'heroicon-o-document';
                    $colorClass = match($color) {
                        'success' => 'bg-success-100 text-success-600 dark:bg-success-900/20 dark:text-success-400',
                        'danger' => 'bg-danger-100 text-danger-600 dark:bg-danger-900/20 dark:text-danger-400',
                        'warning' => 'bg-warning-100 text-warning-600 dark:bg-warning-900/20 dark:text-warning-400',
                        'info' => 'bg-info-100 text-info-600 dark:bg-info-900/20 dark:text-info-400',
                        'primary' => 'bg-primary-100 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400',
                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $colorClass }}">
                    <x-dynamic-component :component="$icon" class="w-5 h-5" />
                </div>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $audit->action_label }}
                    </p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $audit->created_at->diffForHumans() }}
                    </span>
                </div>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $audit->description }}
                </p>

                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    @if($audit->user)
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-user class="w-3 h-3" />
                            {{ $audit->user->name }}
                        </span>
                    @endif

                    <span class="flex items-center gap-1">
                        <x-heroicon-m-clock class="w-3 h-3" />
                        {{ $audit->created_at->format('M j, Y g:i A') }}
                    </span>

                    @if($audit->ip_address)
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-globe-alt class="w-3 h-3" />
                            {{ $audit->ip_address }}
                        </span>
                    @endif

                    @if($audit->integrity_verified !== null)
                        <span class="flex items-center gap-1 {{ $audit->integrity_verified ? 'text-success-600' : 'text-danger-600' }}">
                            @if($audit->integrity_verified)
                                <x-heroicon-m-shield-check class="w-3 h-3" />
                                Verified
                            @else
                                <x-heroicon-m-shield-exclamation class="w-3 h-3" />
                                Failed
                            @endif
                        </span>
                    @endif
                </div>

                {{-- Show changes if edit action --}}
                @if($audit->action === 'edit' && ($audit->old_values || $audit->new_values))
                    <div class="mt-2 p-2 rounded bg-gray-100 dark:bg-gray-700/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Changes:</p>
                        <div class="text-xs space-y-1">
                            @if($audit->old_values)
                                @foreach($audit->old_values as $key => $value)
                                    <div class="flex gap-2">
                                        <span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                        <span class="text-danger-600 line-through">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                        @if(isset($audit->new_values[$key]))
                                            <span class="text-gray-500">&rarr;</span>
                                            <span class="text-success-600">{{ is_array($audit->new_values[$key]) ? json_encode($audit->new_values[$key]) : $audit->new_values[$key] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Show file hash for integrity actions --}}
                @if($audit->action === 'verify' && $audit->file_hash)
                    <div class="mt-2 p-2 rounded bg-gray-100 dark:bg-gray-700/50">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hash at verification:</p>
                        <code class="text-xs text-gray-600 dark:text-gray-400 font-mono break-all">
                            {{ $audit->file_hash }}
                        </code>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No audit history available</p>
        </div>
    @endforelse
</div>
