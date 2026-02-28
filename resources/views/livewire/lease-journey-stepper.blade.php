@php
    $macroSteps = $this->macroSteps;
    $detailSteps = $this->detailSteps;
    $progress = $this->progress;
    $currentStateLabel = $this->currentStateLabel;
    $currentStateColor = $this->currentStateColor;
    $colorClasses = [
        'gray' => 'bg-gray-500',
        'warning' => 'bg-amber-500',
        'info' => 'bg-blue-500',
        'success' => 'bg-green-500',
        'danger' => 'bg-red-500',
        'primary' => 'bg-indigo-500',
    ];
    $badgeColorClasses = [
        'gray' => 'bg-gray-100 text-gray-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'info' => 'bg-blue-100 text-blue-800',
        'success' => 'bg-green-100 text-green-800',
        'danger' => 'bg-red-100 text-red-800',
        'primary' => 'bg-indigo-100 text-indigo-800',
    ];
    $borderColorClasses = [
        'done' => 'border-t-4 border-green-500',
        'active' => 'border-t-4 border-blue-500',
        'pending' => 'border-t-4 border-gray-300',
        'skipped' => 'border-t-4 border-gray-300',
        'action_required' => 'border-t-4 border-red-500',
    ];
    $statusBadgeClasses = [
        'done' => 'bg-green-100 text-green-800',
        'active' => 'bg-blue-100 text-blue-800 animate-pulse',
        'pending' => 'bg-gray-100 text-gray-600',
        'skipped' => 'bg-orange-100 text-orange-800',
        'action_required' => 'bg-red-100 text-red-800',
    ];
    $statusLabels = [
        'done' => 'Done',
        'active' => 'Active',
        'pending' => 'Pending',
        'skipped' => 'Skipped',
        'action_required' => 'Action Required',
    ];
@endphp
<div class="rounded-xl border border-gray-200 bg-white shadow-sm" x-data="{ progress: {{ $progress }} }">
    {{-- Progress bar at top --}}
    <div class="h-1.5 w-full overflow-hidden rounded-t-xl bg-gray-100">
        <div
            class="h-full transition-all duration-500 ease-out"
            style="width: {{ $progress }}%; background: linear-gradient(to right, #8bc34a, #4a7c3f);"
        ></div>
    </div>

    <div class="border-l-4 border-[#8bc34a] p-5">
        {{-- Header with health score --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-block h-3 w-3 rounded-sm bg-[#8bc34a]"></span>
                <h3 class="text-sm font-semibold uppercase tracking-widest text-gray-500">Lease Journey</h3>
            </div>
            @php
                $health = $this->health;
                $score = $health['score'];
                $grade = $health['grade'];
                $ringColor = $grade === 'A' ? 'text-green-500' : ($grade === 'B' ? 'text-amber-500' : 'text-red-500');
            @endphp
            <div class="flex items-center gap-2" title="Lease health: {{ $score }}/100 ({{ $grade }})">
                <div class="relative h-10 w-10">
                    <svg class="h-10 w-10 -rotate-90" viewBox="0 0 36 36">
                        <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="{{ $ringColor }}" stroke="currentColor" stroke-width="3" stroke-dasharray="{{ $score }}, 100" stroke-linecap="round" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-bold {{ $ringColor }}">{{ $grade }}</span>
                </div>
                <span class="text-xs font-medium text-gray-500">{{ $score }}/100</span>
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
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>
                            </div>
                        @elseif ($step['completed'])
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#16a34a] text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd" /></svg>
                            </div>
                        @elseif ($step['current'])
                            <div class="relative">
                                <span class="absolute inline-flex h-8 w-8 animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-500 text-white">
                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                </span>
                            </div>
                        @else
                            <div class="h-8 w-8 rounded-full border-2 border-dashed border-gray-300 bg-white"></div>
                        @endif
                        @if ($step['timestamp'] && ($step['completed'] || $step['current']))
                            <div
                                x-show="showTooltip"
                                x-transition
                                class="absolute bottom-full left-1/2 z-10 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white"
                            >
                                {{ $step['timestamp'] }}
                            </div>
                        @endif
                    </div>
                    <span class="mt-1 text-center text-xs font-medium {{ $step['current'] ? 'font-bold text-gray-900' : ($step['completed'] ? 'text-gray-700' : 'text-gray-400') }}">
                        {{ $step['label'] }}
                    </span>
                </div>
                @if ($index < count($macroSteps) - 1)
                    <div class="flex-1 min-w-[8px] self-center">
                        @php
                            $next = $macroSteps[$index + 1] ?? null;
                            $prevCompleted = $step['completed'];
                            $nextCurrent = $next && $next['current'];
                            $nextCompleted = $next && $next['completed'];
                            $lineGradient = $prevCompleted && $nextCurrent;
                            $lineGreen = $prevCompleted && $nextCompleted;
                        @endphp
                        <div
                            class="h-0.5 w-full {{ $lineGradient ? 'bg-gradient-to-r from-[#16a34a] to-amber-500' : ($lineGreen ? 'bg-[#16a34a]' : 'border-t border-dashed border-gray-300') }}"
                        ></div>
                    </div>
                @endif
            @endforeach
            {{-- Current state pill --}}
            <div class="ml-auto shrink-0">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeColorClasses[$currentStateColor] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $currentStateLabel }}
                </span>
            </div>
        </div>

        {{-- Detail grid: 4-column cards --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($detailSteps as $step)
                <div
                    class="relative rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md {{ $borderColorClasses[$step['status']] ?? 'border-t-4 border-gray-300' }}"
                >
                    {{-- Step number badge --}}
                    <div
                        class="absolute left-3 top-3 flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $step['status'] === 'done' ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}"
                    >
                        {{ $step['number'] }}
                    </div>
                    {{-- Status badge --}}
                    <div class="absolute right-3 top-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadgeClasses[$step['status']] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabels[$step['status']] ?? $step['status'] }}
                        </span>
                    </div>
                    <div class="pt-6">
                        <h4 class="font-medium text-gray-900">{{ $step['title'] }}</h4>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $step['description'] }}</p>
                        @if ($step['timestamp'])
                            <p class="mt-2 text-xs text-gray-400">{{ $step['timestamp'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
