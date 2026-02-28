@php
    $logs = $this->logs;
@endphp
<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <h4 class="mb-4 text-sm font-semibold uppercase tracking-widest text-gray-500">Activity Timeline</h4>
    <div class="space-y-0">
        @forelse ($logs as $log)
            <div class="relative flex gap-4 pb-6 last:pb-0">
                @if (! $loop->last)
                    <span class="absolute left-[11px] top-6 h-full w-px bg-gray-200" aria-hidden="true"></span>
                @endif
                <span class="relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900">
                        {{ $log->description ?? $log->formatted_description }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $log->user?->name ?? 'System' }}
                        · {{ $log->created_at->format('j M Y, g:i A') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No activity recorded yet.</p>
        @endforelse
    </div>
</div>
