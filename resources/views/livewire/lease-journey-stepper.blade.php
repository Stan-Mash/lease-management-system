@php
    $macroSteps   = $this->macroSteps;
    $detailSteps  = $this->detailSteps;
    $progress     = $this->progress;
    $currentLabel = $this->currentStateLabel;
    $currentColor = $this->currentStateColor;
    $health       = $this->health;
    $score        = $health['score'];
    $grade        = $health['grade'];
    $flags        = $health['flags'] ?? [];

    // ── Palette pulled from the Journey Heading panel ──────────────────────
    // Gold:  #DAA520 / #b8960a / #92700a
    // Navy:  #1a365d
    // Cream: #faf8f4 → #fff9e8
    // ───────────────────────────────────────────────────────────────────────

    // Health colours
    $healthRing = match(true) {
        $score >= 80 => ['stroke' => '#059669', 'text' => '#059669', 'bg' => '#f0fdf4'],
        $score >= 55 => ['stroke' => '#DAA520', 'text' => '#92700a', 'bg' => '#fffbeb'],
        default      => ['stroke' => '#ef4444', 'text' => '#b91c1c', 'bg' => '#fef2f2'],
    };

    // Current-state colour chip (maps Filament colour to inline style)
    $stateChipStyle = match($currentColor) {
        'success' => 'background:#d1fae5; color:#065f46; border:1px solid rgba(5,150,105,0.3);',
        'warning' => 'background:#fef3c7; color:#92700a; border:1px solid rgba(218,165,32,0.4);',
        'danger'  => 'background:#fee2e2; color:#991b1b; border:1px solid rgba(239,68,68,0.3);',
        'info'    => 'background:#dbeafe; color:#1e40af; border:1px solid rgba(59,130,246,0.3);',
        'primary' => 'background:#ede9fe; color:#4c1d95; border:1px solid rgba(139,92,246,0.3);',
        default   => 'background:#f3f4f6; color:#374151; border:1px solid rgba(107,114,128,0.3);',
    };

    // Detail card status
    $stepBorderColor = [
        'done'            => '#059669',
        'active'          => '#DAA520',
        'pending'         => '#e5e7eb',
        'skipped'         => '#f97316',
        'action_required' => '#ef4444',
    ];
    $stepBadgeStyle = [
        'done'            => 'background:#d1fae5; color:#065f46;',
        'active'          => 'background:rgba(218,165,32,0.15); color:#92700a;',
        'pending'         => 'background:#f9fafb; color:#9ca3af;',
        'skipped'         => 'background:#fff7ed; color:#c2410c;',
        'action_required' => 'background:#fee2e2; color:#991b1b;',
    ];
    $stepLabel = [
        'done'            => 'Complete',
        'active'          => 'In Progress',
        'pending'         => 'Pending',
        'skipped'         => 'Skipped',
        'action_required' => 'Action Required',
    ];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     LEASE JOURNEY STEPPER
     Palette: Gold (#DAA520 / #b8960a) · Navy (#1a365d) · Cream (#faf8f4)
     Matching the Journey Heading panel on this page.
═══════════════════════════════════════════════════════════════════════════ --}}
<div x-data="{ expanded: null, showFlags: false }"
     style="background: linear-gradient(135deg, #faf8f4 0%, #fffdf5 100%);
            border: 1.5px solid rgba(218,165,32,0.35);
            border-left: 5px solid #DAA520;
            border-radius: 14px;
            overflow: hidden;">

    {{-- ── Progress strip ─────────────────────────────────────────────────── --}}
    <div style="height:3px; background:#f3f0e8;">
        <div style="height:100%; width:{{ $progress }}%; background:linear-gradient(90deg,#DAA520,#b8960a); transition:width .6s ease;"></div>
    </div>

    {{-- ── Header row ──────────────────────────────────────────────────────── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 22px 14px; border-bottom:1px solid rgba(218,165,32,0.18);">

        {{-- Title --}}
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,#DAA520,#b8960a); display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 2px 6px rgba(218,165,32,0.35);">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
                </svg>
            </div>
            <div>
                <div style="font-size:9pt; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#b8960a; line-height:1;">Lease Journey</div>
                <div style="font-size:8.5pt; color:#6b7280; margin-top:2px;">Track every stage of this lease lifecycle</div>
            </div>
        </div>

        {{-- Right: progress chip · state badge · health ring --}}
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
            <span style="font-size:8pt; font-weight:600; background:rgba(218,165,32,0.1); color:#92700a; padding:3px 10px; border-radius:20px; border:1px solid rgba(218,165,32,0.25); white-space:nowrap;">
                {{ $progress }}% complete
            </span>
            <span style="font-size:8pt; font-weight:700; padding:4px 12px; border-radius:20px; white-space:nowrap; {{ $stateChipStyle }}">
                {{ $currentLabel }}
            </span>
            {{-- Health ring --}}
            <button type="button" @click="showFlags = !showFlags"
                    title="Lease Health: {{ $score }}/100 · Grade {{ $grade }}"
                    style="position:relative; width:46px; height:46px; border-radius:50%; background:{{ $healthRing['bg'] }}; border:none; cursor:pointer; flex-shrink:0; transition:transform .15s ease;"
                    onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                <svg style="position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg);" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="3.2"/>
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $healthRing['stroke'] }}" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="{{ $score }},100"/>
                </svg>
                <span style="position:relative; font-size:11pt; font-weight:900; color:{{ $healthRing['text'] }};">{{ $grade }}</span>
            </button>
        </div>
    </div>

    {{-- ── Flags panel ─────────────────────────────────────────────────────── --}}
    @if(count($flags) > 0)
    <div x-show="showFlags" x-transition style="background:#fffbeb; border-bottom:1px solid rgba(218,165,32,0.2); padding:10px 22px;">
        <div style="font-size:8pt; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; color:#92700a; margin-bottom:6px;">Health Flags</div>
        <div style="display:flex; flex-wrap:wrap; gap:6px;">
            @foreach($flags as $flag)
            <span style="font-size:8pt; background:white; color:#92700a; border:1px solid rgba(218,165,32,0.4); border-radius:20px; padding:2px 10px;">
                {{ ucwords(str_replace('_', ' ', $flag)) }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         TIER 1 — HORIZONTAL MACRO PHASE TRACK (7 phases)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div style="padding:24px 22px 20px;">

        <div style="display:flex; align-items:flex-start; position:relative;">
            @foreach ($macroSteps as $index => $step)
                @php
                    $isFirst = $index === 0;
                    $isLast  = $index === count($macroSteps) - 1;
                    $prev    = $index > 0 ? $macroSteps[$index - 1] : null;
                    $lineLeft  = $prev && $prev['completed'] ? '#DAA520' : ($prev && $prev['current'] ? 'linear-gradient(90deg,#DAA520,#e5e7eb)' : '#e5e7eb');
                    $lineRight = $step['completed'] ? '#DAA520' : ($step['current'] ? 'linear-gradient(90deg,#DAA520,#e5e7eb)' : '#e5e7eb');
                @endphp

                <div style="flex:1; display:flex; flex-direction:column; align-items:center; position:relative; min-width:0;"
                     x-data="{ tip: false }" @mouseenter="tip=true" @mouseleave="tip=false">

                    {{-- Connector lines --}}
                    @if(!$isFirst)
                    <div style="position:absolute; top:17px; left:0; width:50%; height:2px;
                                background:{{ ($prev && $prev['completed']) ? '#DAA520' : (($prev && $prev['current']) ? 'linear-gradient(90deg,#DAA520,#e5e7eb)' : '#e5e7eb') }};"></div>
                    @endif
                    @if(!$isLast)
                    <div style="position:absolute; top:17px; right:0; width:50%; height:2px;
                                background:{{ $step['completed'] ? '#DAA520' : ($step['current'] ? 'linear-gradient(90deg,#DAA520,#e5e7eb)' : '#e5e7eb') }};"></div>
                    @endif

                    {{-- Phase circle --}}
                    <div style="position:relative; z-index:1;">
                        @if($step['disputed'])
                            <div style="width:34px; height:34px; border-radius:50%; background:#fee2e2; border:2px solid #ef4444; display:flex; align-items:center; justify-content:center;">
                                <svg width="16" height="16" fill="#ef4444" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/></svg>
                            </div>
                        @elseif($step['completed'])
                            <div style="width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#DAA520,#b8960a); border:2px solid #b8960a; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(218,165,32,0.4);">
                                <svg width="16" height="16" fill="white" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd"/></svg>
                            </div>
                        @elseif($step['current'])
                            <div style="position:relative;">
                                <span style="position:absolute; inset:-4px; border-radius:50%; background:rgba(218,165,32,0.25); animation:ping 1.4s cubic-bezier(0,0,0.2,1) infinite;"></span>
                                <div style="position:relative; width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#DAA520,#b8960a); border:2.5px solid #DAA520; display:flex; align-items:center; justify-content:center; box-shadow:0 0 0 4px rgba(218,165,32,0.2);">
                                    <div style="width:10px; height:10px; border-radius:50%; background:white;"></div>
                                </div>
                            </div>
                        @else
                            <div style="width:34px; height:34px; border-radius:50%; background:white; border:2px dashed #d1d5db; display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:9pt; font-weight:700; color:#d1d5db;">{{ $step['phase'] }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Tooltip --}}
                    @if($step['timestamp'])
                    <div x-show="tip" x-transition
                         style="position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%); white-space:nowrap; background:#1a365d; color:white; font-size:8pt; font-weight:600; padding:5px 10px; border-radius:7px; z-index:50; pointer-events:none; box-shadow:0 4px 12px rgba(26,54,93,0.3);">
                        {{ $step['timestamp'] }}
                        <div style="position:absolute; top:100%; left:50%; transform:translateX(-50%); border:5px solid transparent; border-top-color:#1a365d;"></div>
                    </div>
                    @endif

                    {{-- Label --}}
                    <div style="margin-top:8px; text-align:center; max-width:80px; font-size:8pt; line-height:1.35;
                                font-weight:{{ $step['current'] ? '800' : ($step['completed'] ? '600' : '400') }};
                                color:{{ $step['current'] ? '#1a365d' : ($step['completed'] ? '#374151' : '#9ca3af') }};">
                        {{ $step['label'] }}
                    </div>

                    {{-- Timestamp under label --}}
                    @if($step['timestamp'])
                    <div style="margin-top:2px; text-align:center; font-size:7pt; color:#b8960a; line-height:1.2; max-width:80px;">
                        {{ $step['timestamp'] }}
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         TIER 2 — DETAIL STEP CARDS (horizontal scroll row, then expandable)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div style="border-top:1px solid rgba(218,165,32,0.15); padding:0 22px 20px;">

        {{-- Section label --}}
        <div style="display:flex; align-items:center; gap:12px; padding:16px 0 14px;">
            <div style="height:1px; flex:1; background:linear-gradient(90deg,transparent,rgba(218,165,32,0.25));"></div>
            <span style="font-size:7.5pt; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:#b8960a; white-space:nowrap;">Detailed Steps — click any card to expand</span>
            <div style="height:1px; flex:1; background:linear-gradient(90deg,rgba(218,165,32,0.25),transparent);"></div>
        </div>

        {{-- Cards grid: 5 per row on large, 2 on small --}}
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:10px;">
            @foreach($detailSteps as $step)
                @php
                    $st     = $step['status'];
                    $isDone = $st === 'done';
                    $isAct  = $st === 'active';
                    $isCrit = $st === 'action_required';
                    $borderColor = $stepBorderColor[$st] ?? '#e5e7eb';
                    $badgeStyle  = $stepBadgeStyle[$st] ?? 'background:#f9fafb; color:#9ca3af;';
                    $label       = $stepLabel[$st] ?? $st;
                @endphp
                <button type="button"
                        @click="expanded = (expanded === {{ $loop->index }}) ? null : {{ $loop->index }}"
                        style="text-align:left; width:100%; cursor:pointer; border:none; padding:0;
                               background:white;
                               border-top:3px solid {{ $borderColor }};
                               border-radius:10px;
                               box-shadow:{{ $isAct ? '0 0 0 2px rgba(218,165,32,0.4), 0 2px 8px rgba(218,165,32,0.15)' : '0 1px 4px rgba(0,0,0,0.07)' }};
                               transition:box-shadow .15s, transform .15s;"
                        :style="expanded === {{ $loop->index }} ? 'box-shadow:0 0 0 2px rgba(218,165,32,0.6),0 4px 16px rgba(218,165,32,0.15);transform:translateY(-1px);' : ''"
                        onmouseover="if(!this.classList.contains('exp'))this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'"
                        onmouseout="this.style.boxShadow=''">

                    <div style="padding:12px;">
                        {{-- Top row: number + badge --}}
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; gap:4px;">
                            <span style="width:22px; height:22px; border-radius:50%; font-size:8pt; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;
                                         {{ ($isDone || $isAct) ? 'background:linear-gradient(135deg,#DAA520,#b8960a); color:white;' : 'background:#f3f4f6; color:#9ca3af;' }}">
                                {{ $step['number'] }}
                            </span>
                            <span style="font-size:7pt; font-weight:700; padding:2px 7px; border-radius:20px; white-space:nowrap; {{ $badgeStyle }}
                                         {{ $isCrit ? 'animation:pulse 1.5s infinite;' : '' }}">
                                {{ $label }}
                            </span>
                        </div>

                        {{-- Title --}}
                        <div style="font-size:8.5pt; font-weight:{{ $isDone || $isAct ? '700' : '500' }}; color:{{ $isDone ? '#374151' : ($isAct ? '#1a365d' : '#9ca3af') }}; line-height:1.3; margin-bottom:4px;">
                            {{ $step['title'] }}
                        </div>

                        {{-- Description (always visible, truncated) --}}
                        <div style="font-size:7.5pt; color:#9ca3af; line-height:1.4; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">
                            {{ $step['description'] }}
                        </div>

                        {{-- Expanded: timestamp --}}
                        <div x-show="expanded === {{ $loop->index }}" x-transition style="margin-top:8px; padding-top:8px; border-top:1px solid rgba(218,165,32,0.15);">
                            @if($step['timestamp'])
                                <div style="display:flex; align-items:center; gap:5px; font-size:7.5pt; color:#92700a; background:rgba(218,165,32,0.08); padding:5px 8px; border-radius:6px;">
                                    <svg width="11" height="11" fill="none" stroke="#92700a" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                    {{ $step['timestamp'] }}
                                </div>
                            @else
                                <div style="font-size:7.5pt; color:#d1d5db; font-style:italic;">Not yet reached</div>
                            @endif
                        </div>

                        {{-- Caret --}}
                        <div style="text-align:right; margin-top:6px;">
                            <svg style="display:inline-block; width:11px; height:11px; color:#d1d5db; transition:transform .2s, color .2s;"
                                 :style="expanded === {{ $loop->index }} ? 'transform:rotate(180deg);color:#DAA520;' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── Footer strip ────────────────────────────────────────────────────── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:10px 22px; border-top:1px solid rgba(218,165,32,0.15); background:rgba(218,165,32,0.04);">
        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <span style="display:flex; align-items:center; gap:6px; font-size:8pt; color:#6b7280;">
                <span style="width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg,#DAA520,#b8960a); display:inline-block;"></span>
                Completed
            </span>
            <span style="display:flex; align-items:center; gap:6px; font-size:8pt; color:#6b7280;">
                <span style="width:10px; height:10px; border-radius:50%; border:2.5px dashed #DAA520; display:inline-block; animation:ping 1.4s infinite; opacity:0.7;"></span>
                In Progress
            </span>
            <span style="display:flex; align-items:center; gap:6px; font-size:8pt; color:#6b7280;">
                <span style="width:10px; height:10px; border-radius:50%; border:2px dashed #d1d5db; display:inline-block;"></span>
                Upcoming
            </span>
            <span style="display:flex; align-items:center; gap:6px; font-size:8pt; color:#6b7280;">
                <span style="width:10px; height:10px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
                Action Required
            </span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:8pt; color:#9ca3af;">Lease Health</span>
            <span style="font-size:11pt; font-weight:900; color:{{ $healthRing['text'] }};">{{ $score }}/100</span>
            @if(count($flags) > 0)
            <button type="button" @click="showFlags = !showFlags"
                    style="font-size:7.5pt; font-weight:700; background:rgba(218,165,32,0.12); color:#92700a; border:1px solid rgba(218,165,32,0.35); border-radius:20px; padding:2px 10px; cursor:pointer;">
                {{ count($flags) }} {{ count($flags) === 1 ? 'flag' : 'flags' }}
            </button>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes ping {
    75%, 100% { transform:scale(1.5); opacity:0; }
}
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.6; }
}
</style>
@endpush
