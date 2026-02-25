@php
    $logs = $this->logs;

    // Map action keywords to icons + brand colours
    $actionIcon = function(string $action): array {
        $action = strtolower($action);
        if (str_contains($action, 'approv')) {
            return ['path' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'bg' => 'bg-green-100', 'icon' => 'text-green-600'];
        }
        if (str_contains($action, 'sign') || str_contains($action, 'countersign')) {
            return ['path' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10', 'bg' => 'bg-[#f0f9e8]', 'icon' => 'text-[#4a7c3f]'];
        }
        if (str_contains($action, 'reject') || str_contains($action, 'cancel') || str_contains($action, 'terminat')) {
            return ['path' => 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'bg' => 'bg-red-100', 'icon' => 'text-red-500'];
        }
        if (str_contains($action, 'send') || str_contains($action, 'email') || str_contains($action, 'sms') || str_contains($action, 'notif')) {
            return ['path' => 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12zm0 0h7.5', 'bg' => 'bg-sky-100', 'icon' => 'text-sky-600'];
        }
        if (str_contains($action, 'otp') || str_contains($action, 'verif')) {
            return ['path' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3', 'bg' => 'bg-indigo-100', 'icon' => 'text-indigo-600'];
        }
        if (str_contains($action, 'upload') || str_contains($action, 'document') || str_contains($action, 'pdf')) {
            return ['path' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z', 'bg' => 'bg-purple-100', 'icon' => 'text-purple-600'];
        }
        if (str_contains($action, 'activ')) {
            return ['path' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'bg' => 'bg-[#f0f9e8]', 'icon' => 'text-[#4a7c3f]'];
        }
        if (str_contains($action, 'disput')) {
            return ['path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z', 'bg' => 'bg-amber-100', 'icon' => 'text-amber-600'];
        }
        // default
        return ['path' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2', 'bg' => 'bg-gray-100', 'icon' => 'text-gray-400'];
    };
@endphp

<div class="w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md">

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         HEADER BAND — matches Journey Stepper header exactly
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="flex items-center justify-between gap-4 border-b border-gray-100 bg-gradient-to-r from-[#f6fbee] to-white px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm"
                 style="background: linear-gradient(135deg,#8bc34a 0%,#4a7c3f 100%);">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest" style="color:#4a7c3f;">Activity Timeline</p>
                <p class="text-xs text-gray-400">Full audit trail for this lease</p>
            </div>
        </div>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-500">
            {{ $logs->count() }} {{ Str::plural('event', $logs->count()) }}
        </span>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         TIMELINE BODY
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="p-6">
        @forelse ($logs as $log)
            @php
                $iconData = $actionIcon($log->action ?? '');
            @endphp
            <div class="relative flex gap-4 pb-5 last:pb-0">
                {{-- Vertical connecting line --}}
                @if (! $loop->last)
                    <span class="absolute left-[18px] top-9 bottom-0 w-px bg-gradient-to-b from-gray-200 to-transparent" aria-hidden="true"></span>
                @endif

                {{-- Icon dot --}}
                <div class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $iconData['bg'] }} shadow-sm ring-2 ring-white">
                    <svg class="h-4 w-4 {{ $iconData['icon'] }}" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconData['path'] }}"/>
                    </svg>
                </div>

                {{-- Event content --}}
                <div class="min-w-0 flex-1 pt-1">
                    {{-- State transition arrow (if applicable) --}}
                    @if ($log->old_state && $log->new_state)
                        <div class="mb-1.5 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">
                                {{ ucwords(str_replace('_', ' ', $log->old_state)) }}
                            </span>
                            <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                            </svg>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                  style="background:#f0f9e8; color:#4a7c3f; border:1px solid rgba(139,195,74,0.5);">
                                {{ ucwords(str_replace('_', ' ', $log->new_state)) }}
                            </span>
                        </div>
                    @endif

                    {{-- Description text --}}
                    <p class="text-sm font-medium leading-snug text-gray-800">
                        {{ $log->description ?? $log->formatted_description ?? ucwords(str_replace('_', ' ', $log->action ?? 'Action')) }}
                    </p>

                    {{-- Meta row: user · timestamp · role --}}
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            {{ $log->user?->name ?? 'System' }}
                        </span>
                        <span class="text-gray-300">·</span>
                        <span>{{ $log->created_at->format('j M Y, g:i A') }}</span>
                        @if ($log->user_role_at_time)
                            <span class="text-gray-300">·</span>
                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] capitalize text-gray-500">
                                {{ str_replace('_', ' ', $log->user_role_at_time) }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center gap-3 py-10 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full"
                     style="background: linear-gradient(135deg,#f0f9e8,#e7f5d0);">
                    <svg class="h-7 w-7" style="color:#8bc34a;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No activity recorded yet</p>
                <p class="text-xs text-gray-400">Events will appear here as the lease progresses</p>
            </div>
        @endforelse
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         FOOTER — matches Journey Stepper footer style
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if ($logs->count() > 0)
    <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gradient-to-r from-[#f6fbee] to-gray-50 px-6 py-3">
        <p class="text-[11px] text-gray-400">
            Showing most recent <span class="font-semibold text-gray-600">{{ $logs->count() }}</span> {{ Str::plural('event', $logs->count()) }}, newest first
        </p>
        <div class="flex items-center gap-1.5 text-[11px] text-gray-400">
            <span class="h-2 w-2 rounded-full" style="background:#8bc34a;"></span>
            Chabrin Lease Management
        </div>
    </div>
    @endif
</div>
