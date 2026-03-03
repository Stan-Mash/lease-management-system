@php
    $logs = $this->logs;
@endphp
<div
    class="overflow-hidden rounded-xl"
    style="background: linear-gradient(135deg, #faf8f4 0%, #fff9e8 100%); border: 1.5px solid rgba(218,165,32,0.35); border-left: 5px solid #DAA520;"
>
    <div class="p-5">
        <div class="mb-4 flex items-center gap-2">
            <span class="inline-block h-3 w-3 rounded-sm" style="background:#DAA520;"></span>
            <h4 class="text-xs font-bold uppercase tracking-widest" style="color:#92700a;">Activity Timeline</h4>
        </div>

        <div class="space-y-0">
            @forelse ($logs as $log)
                <div class="relative flex gap-4 pb-5 last:pb-0">
                    @if (! $loop->last)
                        <span
                            class="absolute left-[11px] top-6 h-full w-px"
                            style="background: linear-gradient(to bottom, rgba(218,165,32,0.4), rgba(218,165,32,0.1));"
                            aria-hidden="true"
                        ></span>
                    @endif

                    {{-- Icon dot --}}
                    <span class="relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                        style="background:rgba(218,165,32,0.12); color:#92700a;">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium" style="color:#1a365d;">
                            {{ $log->description ?? $log->formatted_description }}
                        </p>
                        <p class="mt-0.5 text-xs" style="color:#b8960a;">
                            {{ $log->user?->name ?? 'System' }}
                            &middot; {{ $log->created_at->format('j M Y, g:i A') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm" style="color:#92700a;">No activity recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
