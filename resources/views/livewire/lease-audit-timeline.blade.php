@php
    $logs = $this->logs;

    // ── Palette: same as Journey Heading + Stepper ─────────────────────────
    // Gold: #DAA520 / #b8960a / #92700a
    // Navy: #1a365d
    // ───────────────────────────────────────────────────────────────────────

    // Returns [dotBg, dotColor, svgPath] for the action icon dot
    $actionMeta = function(string $action): array {
        $a = strtolower($action);
        if (str_contains($a, 'approv'))
            return ['#d1fae5', '#059669', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'];
        if (str_contains($a, 'sign') || str_contains($a, 'countersign'))
            return ['rgba(218,165,32,0.15)', '#b8960a', 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487zm0 0L19.5 7.125'];
        if (str_contains($a, 'reject') || str_contains($a, 'cancel') || str_contains($a, 'terminat'))
            return ['#fee2e2', '#dc2626', 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'];
        if (str_contains($a, 'send') || str_contains($a, 'email') || str_contains($a, 'sms') || str_contains($a, 'notif'))
            return ['#dbeafe', '#1d4ed8', 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12zm0 0h7.5'];
        if (str_contains($a, 'otp') || str_contains($a, 'verif'))
            return ['#ede9fe', '#6d28d9', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3'];
        if (str_contains($a, 'upload') || str_contains($a, 'document') || str_contains($a, 'pdf'))
            return ['#f3e8ff', '#7c3aed', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z'];
        if (str_contains($a, 'activ'))
            return ['rgba(218,165,32,0.15)', '#b8960a', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'];
        if (str_contains($a, 'disput'))
            return ['#fef3c7', '#d97706', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'];
        return ['#f3f4f6', '#6b7280', 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2'];
    };
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     ACTIVITY TIMELINE
     Palette: Gold (#DAA520 / #b8960a) · Navy (#1a365d) · Cream (#faf8f4)
     Matching the Journey Heading panel and Lease Journey Stepper above.
═══════════════════════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#faf8f4 0%,#fffdf5 100%);
            border:1.5px solid rgba(218,165,32,0.35);
            border-left:5px solid #DAA520;
            border-radius:14px;
            overflow:hidden;">

    {{-- ── Header row ──────────────────────────────────────────────────────── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 22px 14px; border-bottom:1px solid rgba(218,165,32,0.18);">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#DAA520,#b8960a); display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 2px 6px rgba(218,165,32,0.35);">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
            </div>
            <div>
                <div style="font-size:9pt; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#b8960a; line-height:1;">Activity Timeline</div>
                <div style="font-size:8.5pt; color:#6b7280; margin-top:2px;">Full audit trail for this lease</div>
            </div>
        </div>
        <span style="font-size:8pt; font-weight:700; background:rgba(218,165,32,0.1); color:#92700a; padding:3px 12px; border-radius:20px; border:1px solid rgba(218,165,32,0.25); white-space:nowrap;">
            {{ $logs->count() }} {{ Str::plural('event', $logs->count()) }}
        </span>
    </div>

    {{-- ── Timeline body ────────────────────────────────────────────────────── --}}
    <div style="padding:20px 22px;">
        @forelse ($logs as $log)
            @php [$dotBg, $dotColor, $iconPath] = $actionMeta($log->action ?? ''); @endphp

            <div style="display:flex; gap:14px; padding-bottom:{{ $loop->last ? '0' : '18px' }}; position:relative;">

                {{-- Vertical connecting line --}}
                @if(!$loop->last)
                <div style="position:absolute; left:17px; top:36px; bottom:0; width:1px; background:linear-gradient(180deg,rgba(218,165,32,0.3) 0%,transparent 100%);"></div>
                @endif

                {{-- Icon dot --}}
                <div style="width:36px; height:36px; border-radius:50%; background:{{ $dotBg }}; border:2px solid rgba(218,165,32,0.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; position:relative; z-index:1;">
                    <svg width="15" height="15" fill="none" stroke="{{ $dotColor }}" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                    </svg>
                </div>

                {{-- Content --}}
                <div style="flex:1; min-width:0; padding-top:2px;">

                    {{-- State transition (if present) --}}
                    @if($log->old_state && $log->new_state)
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                        <span style="font-size:7.5pt; font-weight:600; background:#f3f4f6; color:#6b7280; padding:2px 8px; border-radius:20px;">
                            {{ ucwords(str_replace('_', ' ', $log->old_state)) }}
                        </span>
                        <svg width="10" height="10" fill="none" stroke="#9ca3af" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                        <span style="font-size:7.5pt; font-weight:700; background:rgba(218,165,32,0.12); color:#92700a; padding:2px 8px; border-radius:20px; border:1px solid rgba(218,165,32,0.3);">
                            {{ ucwords(str_replace('_', ' ', $log->new_state)) }}
                        </span>
                    </div>
                    @endif

                    {{-- Description --}}
                    <div style="font-size:9pt; font-weight:600; color:#1a365d; line-height:1.4;">
                        {{ $log->description ?? $log->formatted_description ?? ucwords(str_replace('_', ' ', $log->action ?? 'Action')) }}
                    </div>

                    {{-- Meta: user · time · role --}}
                    <div style="display:flex; align-items:center; flex-wrap:wrap; gap:6px; margin-top:4px;">
                        <span style="display:flex; align-items:center; gap:4px; font-size:7.5pt; color:#9ca3af;">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            {{ $log->user?->name ?? 'System' }}
                        </span>
                        <span style="color:#e5e7eb; font-size:7pt;">·</span>
                        <span style="font-size:7.5pt; color:#9ca3af;">{{ $log->created_at->format('j M Y, g:i A') }}</span>
                        @if($log->user_role_at_time)
                        <span style="font-size:7pt; font-weight:600; background:#f3f4f6; color:#6b7280; padding:1px 7px; border-radius:20px; text-transform:capitalize;">
                            {{ str_replace('_', ' ', $log->user_role_at_time) }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div style="display:flex; flex-direction:column; align-items:center; gap:10px; padding:40px 0; text-align:center;">
                <div style="width:52px; height:52px; border-radius:50%; background:rgba(218,165,32,0.1); border:2px solid rgba(218,165,32,0.25); display:flex; align-items:center; justify-content:center;">
                    <svg width="22" height="22" fill="none" stroke="#b8960a" stroke-width="1.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <div style="font-size:9pt; font-weight:600; color:#374151;">No activity recorded yet</div>
                <div style="font-size:8pt; color:#9ca3af;">Events will appear here as the lease progresses</div>
            </div>
        @endforelse
    </div>

    {{-- ── Footer strip ─────────────────────────────────────────────────────── --}}
    @if($logs->count() > 0)
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 22px; border-top:1px solid rgba(218,165,32,0.15); background:rgba(218,165,32,0.04);">
        <span style="font-size:7.5pt; color:#9ca3af;">
            Showing most recent <strong style="color:#374151;">{{ $logs->count() }}</strong> {{ Str::plural('event', $logs->count()) }}, newest first
        </span>
        <span style="display:flex; align-items:center; gap:6px; font-size:7.5pt; color:#9ca3af;">
            <span style="width:8px; height:8px; border-radius:50%; background:linear-gradient(135deg,#DAA520,#b8960a); display:inline-block;"></span>
            Chabrin Lease Management
        </span>
    </div>
    @endif
</div>
