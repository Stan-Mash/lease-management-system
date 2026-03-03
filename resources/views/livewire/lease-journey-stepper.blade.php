@php
    $macroSteps = $this->macroSteps;
    $detailSteps = $this->detailSteps;
    $progress = $this->progress;
    $currentStateLabel = $this->currentStateLabel;
    $currentStateColor = $this->currentStateColor;
    $badgeColorClasses = [
        'gray'    => 'bg-gray-100 text-gray-600',
        'warning' => 'bg-amber-100 text-amber-800',
        'info'    => 'bg-blue-100 text-blue-800',
        'success' => 'bg-green-100 text-green-700',
        'danger'  => 'bg-red-100 text-red-700',
        'primary' => 'bg-indigo-100 text-indigo-800',
    ];
    $borderColorClasses = [
        'done'           => 'border-t-4 border-[#DAA520]',
        'active'         => 'border-t-4 border-[#1a365d]',
        'pending'        => 'border-t-4 border-gray-200',
        'skipped'        => 'border-t-4 border-gray-200',
        'action_required'=> 'border-t-4 border-red-400',
    ];
    $statusBadgeClasses = [
        'done'           => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
        'active'         => 'bg-[#1a365d] text-white animate-pulse',
        'pending'        => 'bg-gray-100 text-gray-500',
        'skipped'        => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
        'action_required'=> 'bg-red-50 text-red-700 ring-1 ring-red-200',
    ];
    $statusLabels = [
        'done'           => 'Done',
        'active'         => 'Active',
        'pending'        => 'Pending',
        'skipped'        => 'Skipped',
        'action_required'=> 'Action Required',
    ];
@endphp

<div
    class="overflow-hidden rounded-xl"
    style="background: linear-gradient(135deg, #faf8f4 0%, #fff9e8 100%); border: 1.5px solid rgba(218,165,32,0.35); border-left: 5px solid #DAA520;"
>
    {{-- Progress bar at top --}}
    <div class="h-1 w-full overflow-hidden" style="background:rgba(218,165,32,0.15);">
        <div
            class="h-full transition-all duration-700 ease-out"
            style="width: {{ $progress }}%; background: linear-gradient(to right, #DAA520, #92700a);"
        ></div>
    </div>

    <div class="p-5">
        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-sm" style="background:#DAA520;"></span>
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color:#92700a;">Lease Journey</h3>
            </div>
            @php
                $health = $this->health;
                $score = $health['score'];
                $grade = $health['grade'];
                $ringColor = $grade === 'A' ? '#DAA520' : ($grade === 'B' ? '#f59e0b' : '#ef4444');
            @endphp
            <div class="flex items-center gap-2" title="Lease health: {{ $score }}/100 ({{ $grade }})">
                <div class="relative h-10 w-10">
                    <svg class="h-10 w-10 -rotate-90" viewBox="0 0 36 36">
                        <path stroke="rgba(218,165,32,0.25)" stroke-width="3" fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path stroke="{{ $ringColor }}" stroke-width="3"
                            stroke-dasharray="{{ $score }}, 100" stroke-linecap="round" fill="none"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-bold"
                        style="color:{{ $ringColor }};">{{ $grade }}</span>
                </div>
                <span class="text-xs font-medium" style="color:#92700a;">{{ $score }}/100</span>
            </div>
        </div>

        {{-- Hero track: 7 macro phases --}}
        <div class="flex flex-wrap items-end justify-between gap-2">
            @foreach ($macroSteps as $index => $step)
                <div
                    class="flex flex-col items-center"
                    x-data="{ showTooltip: false }"
                    @mouseenter="showTooltip = true"
                    @mouseleave="showTooltip = false"
                >
                    <div class="relative flex items-center justify-center">
                        @if ($step['disputed'])
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @elseif ($step['completed'])
                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-white"
                                style="background:#DAA520;">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @elseif ($step['current'])
                            <div class="relative">
                                <span class="absolute inline-flex h-8 w-8 animate-ping rounded-full opacity-50"
                                    style="background:rgba(218,165,32,0.5);"></span>
                                <span class="relative inline-flex h-8 w-8 items-center justify-center rounded-full text-white"
                                    style="background:#1a365d;">
                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                </span>
                            </div>
                        @else
                            <div class="h-8 w-8 rounded-full border-2 border-dashed bg-white"
                                style="border-color:rgba(218,165,32,0.3);"></div>
                        @endif

                        @if ($step['timestamp'] && ($step['completed'] || $step['current']))
                            <div
                                x-show="showTooltip"
                                x-transition
                                class="absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded px-2 py-1 text-xs text-white"
                                style="background:#1a365d;"
                            >
                                {{ $step['timestamp'] }}
                            </div>
                        @endif
                    </div>

                    <span class="mt-1 text-center text-xs font-medium {{ $step['current'] ? 'font-bold' : '' }}"
                        style="color: {{ $step['current'] ? '#1a365d' : ($step['completed'] ? '#92700a' : '#9ca3af') }};">
                        {{ $step['label'] }}
                    </span>
                </div>

                @if ($index < count($macroSteps) - 1)
                    <div class="flex-1 min-w-[8px] self-center">
                        @php
                            $next = $macroSteps[$index + 1] ?? null;
                            $prevCompleted = $step['completed'];
                            $nextCompleted = $next && $next['completed'];
                            $nextCurrent   = $next && $next['current'];
                        @endphp
                        <div class="h-0.5 w-full {{ ($prevCompleted && $nextCompleted) ? '' : (($prevCompleted && $nextCurrent) ? '' : '') }}"
                            style="
                                @if ($prevCompleted && $nextCompleted)
                                    background:#DAA520;
                                @elseif ($prevCompleted && $nextCurrent)
                                    background: linear-gradient(to right, #DAA520, #1a365d);
                                @else
                                    border-top: 1.5px dashed rgba(218,165,32,0.3);
                                @endif
                            "
                        ></div>
                    </div>
                @endif
            @endforeach

            {{-- Current state pill --}}
            <div class="ml-auto shrink-0">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
                    {{ $badgeColorClasses[$currentStateColor] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $currentStateLabel }}
                </span>
            </div>
        </div>

        {{-- Divider --}}
        <div class="my-5 h-px" style="background: linear-gradient(to right, rgba(218,165,32,0.4), transparent);"></div>

        {{-- Detail grid: 4-column cards --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($detailSteps as $step)
                <div
                    class="relative rounded-lg p-4 transition-shadow duration-200 hover:shadow-md {{ $borderColorClasses[$step['status']] ?? 'border-t-4 border-gray-200' }}"
                    style="background:#fff; border: 1px solid rgba(218,165,32,0.18); {{ $step['status'] === 'active' ? 'box-shadow:0 0 0 2px rgba(26,54,93,0.1);' : '' }}"
                >
                    {{-- Step number --}}
                    <div class="absolute left-3 top-3 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                        style="{{ $step['status'] === 'done' ? 'background:rgba(218,165,32,0.12); color:#92700a;' : 'background:#f3f4f6; color:#6b7280;' }}">
                        {{ $step['number'] }}
                    </div>

                    {{-- Status badge --}}
                    <div class="absolute right-3 top-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $statusBadgeClasses[$step['status']] ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $statusLabels[$step['status']] ?? $step['status'] }}
                        </span>
                    </div>

                    <div class="pt-5">
                        <h4 class="font-semibold" style="color:#1a365d; font-size:0.8rem;">{{ $step['title'] }}</h4>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $step['description'] }}</p>
                        @if ($step['timestamp'])
                            <p class="mt-2 text-xs" style="color:#b8960a;">{{ $step['timestamp'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
