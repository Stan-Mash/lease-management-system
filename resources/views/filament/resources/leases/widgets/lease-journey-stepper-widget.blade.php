@php
    $macroSteps        = $macroSteps        ?? [];
    $detailSteps       = $detailSteps       ?? [];
    $progress          = $progress          ?? 0;
    $currentStateLabel = $currentStateLabel ?? '';
    $currentStateColor = $currentStateColor ?? 'gray';
    $health            = $health            ?? ['score' => 0, 'grade' => 'F'];

    $statusLabels = [
        'done'            => 'Done',
        'active'          => 'Active',
        'pending'         => 'Pending',
        'skipped'         => 'Skipped',
        'action_required' => 'Action Required',
    ];
    $statusBadgeStyle = [
        'done'            => 'background:rgba(218,165,32,0.15); color:#92700a;',
        'active'          => 'background:#1a365d; color:#fff;',
        'pending'         => 'background:#f3f4f6; color:#6b7280;',
        'skipped'         => 'background:#fff7ed; color:#c2410c;',
        'action_required' => 'background:#fef2f2; color:#b91c1c;',
    ];
    $cardTopBorder = [
        'done'            => 'border-top:4px solid #DAA520;',
        'active'          => 'border-top:4px solid #1a365d;',
        'pending'         => 'border-top:4px solid #e5e7eb;',
        'skipped'         => 'border-top:4px solid #e5e7eb;',
        'action_required' => 'border-top:4px solid #ef4444;',
    ];
    $statePillStyle = [
        'gray'    => 'background:#f3f4f6; color:#374151;',
        'warning' => 'background:#fef3c7; color:#92400e;',
        'info'    => 'background:#dbeafe; color:#1e40af;',
        'success' => 'background:#dcfce7; color:#166534;',
        'danger'  => 'background:#fee2e2; color:#991b1b;',
        'primary' => 'background:#e0e7ff; color:#3730a3;',
    ];

    $score = $health['score'];
    $grade = $health['grade'];
    $ring  = $grade === 'A' ? '#DAA520' : ($grade === 'B' ? '#f59e0b' : '#ef4444');
@endphp

<div style="overflow:hidden; border-radius:12px; background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%); border:1.5px solid rgba(218,165,32,0.35); border-left:5px solid #DAA520;">

    {{-- Progress bar --}}
    <div style="height:4px; width:100%; background:rgba(218,165,32,0.15);">
        <div style="height:100%; width:{{ $progress }}%; background:linear-gradient(to right,#DAA520,#92700a);"></div>
    </div>

    <div style="padding:20px;">

        {{-- Header – match journey heading styling --}}
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:#DAA520;"></span>
                <span style="font-size:9pt; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#b8960a;">
                    Lease Journey
                </span>
            </div>
            <div style="display:flex; align-items:center; gap:8px;" title="Health: {{ $score }}/100 ({{ $grade }})">
                <div style="position:relative; width:40px; height:40px;">
                    <svg width="40" height="40" viewBox="0 0 36 36" style="transform:rotate(-90deg);">
                        <path stroke="rgba(218,165,32,0.2)" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path stroke="{{ $ring }}" stroke-width="3" stroke-dasharray="{{ $score }},100" stroke-linecap="round" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    </svg>
                    <span style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:{{ $ring }};">{{ $grade }}</span>
                </div>
                <span style="font-size:11px; color:#92700a;">{{ $score }}/100</span>
            </div>
        </div>

        {{-- Phase track --}}
        <div style="display:flex; align-items:center; gap:4px; overflow-x:auto;">
            @foreach ($macroSteps as $index => $step)
                <div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">
                    @if ($step['disputed'])
                        <div style="width:32px; height:32px; border-radius:50%; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center;">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/></svg>
                        </div>
                    @elseif ($step['completed'])
                        <div style="width:32px; height:32px; border-radius:50%; background:#DAA520; color:#fff; display:flex; align-items:center; justify-content:center;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd"/></svg>
                        </div>
                    @elseif ($step['current'])
                        <div style="width:32px; height:32px; border-radius:50%; background:#1a365d; color:#fff; display:flex; align-items:center; justify-content:center;">
                            <span style="width:8px; height:8px; border-radius:50%; background:#DAA520;"></span>
                        </div>
                    @else
                        <div style="width:32px; height:32px; border-radius:50%; border:2px dashed rgba(218,165,32,0.4); background:#fff;"></div>
                    @endif
                    <span style="margin-top:4px; font-size:10px; font-weight:{{ $step['current'] ? '700' : '500' }}; color:{{ $step['current'] ? '#1a365d' : ($step['completed'] ? '#92700a' : '#9ca3af') }}; text-align:center; max-width:64px;">
                        {{ $step['label'] }}
                    </span>
                </div>

                @if ($index < count($macroSteps) - 1)
                    @php
                        $nxt  = $macroSteps[$index + 1] ?? null;
                        $line = ($step['completed'] && $nxt && $nxt['completed'])
                            ? 'background:#DAA520;'
                            : (($step['completed'] && $nxt && $nxt['current'])
                                ? 'background:linear-gradient(to right,#DAA520,#1a365d);'
                                : 'border-top:2px dashed rgba(218,165,32,0.3);');
                    @endphp
                    <div style="flex:1; min-width:12px; height:2px; {{ $line }}"></div>
                @endif
            @endforeach

            <div style="margin-left:8px; flex-shrink:0;">
                <span style="display:inline-flex; border-radius:9999px; padding:3px 12px; font-size:11px; font-weight:600; {{ $statePillStyle[$currentStateColor] ?? 'background:#f3f4f6; color:#374151;' }}">
                    {{ $currentStateLabel }}
                </span>
            </div>
        </div>

        {{-- Divider --}}
        <div style="margin:20px 0; height:1px; background:linear-gradient(to right,rgba(218,165,32,0.5),transparent);"></div>

        {{-- Detail cards --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px;">
            @foreach ($detailSteps as $step)
                @php
                    $topColor = ['done'=>'#DAA520','active'=>'#1a365d','pending'=>'#e5e7eb','skipped'=>'#e5e7eb','action_required'=>'#ef4444'][$step['status']] ?? '#e5e7eb';
                @endphp
                <div style="border-radius:8px; padding:12px; background:#fff; border-top:4px solid {{ $topColor }}; border-right:1px solid rgba(218,165,32,0.2); border-bottom:1px solid rgba(218,165,32,0.2); border-left:1px solid rgba(218,165,32,0.2);">
                    {{-- Step number --}}
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; font-size:11px; font-weight:700; margin-bottom:6px; {{ $step['status'] === 'done' ? 'background:rgba(218,165,32,0.15); color:#92700a;' : 'background:#f3f4f6; color:#6b7280;' }}">
                        {{ $step['number'] }}
                    </div>
                    {{-- Status badge --}}
                    <span style="display:inline-block; border-radius:9999px; padding:1px 7px; font-size:10px; font-weight:600; margin-left:4px; {{ $statusBadgeStyle[$step['status']] ?? 'background:#f3f4f6; color:#6b7280;' }}">
                        {{ $statusLabels[$step['status']] ?? $step['status'] }}
                    </span>
                    <div style="font-size:12px; font-weight:600; color:#1a365d; line-height:1.3; margin-top:6px;">{{ $step['title'] }}</div>
                    <div style="font-size:11px; color:#6b7280; margin-top:2px;">{{ $step['description'] }}</div>
                    @if ($step['timestamp'])
                        <div style="font-size:10px; color:#b8960a; margin-top:6px;">{{ $step['timestamp'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>

    </div>
</div>
