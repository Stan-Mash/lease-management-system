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

    // Health ring colour
    $healthRing = match(true) {
        $score >= 80 => ['ring' => '#16a34a', 'bg' => 'bg-green-50',  'text' => 'text-green-700'],
        $score >= 55 => ['ring' => '#f59e0b', 'bg' => 'bg-amber-50',  'text' => 'text-amber-700'],
        default      => ['ring' => '#ef4444', 'bg' => 'bg-red-50',    'text' => 'text-red-700'],
    };

    // Current-state badge colour
    $badgeBg = [
        'gray'    => 'bg-gray-100 text-gray-700 ring-1 ring-gray-200',
        'warning' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
        'info'    => 'bg-sky-50 text-sky-800 ring-1 ring-sky-200',
        'success' => 'bg-green-50 text-green-800 ring-1 ring-green-200',
        'danger'  => 'bg-red-50 text-red-800 ring-1 ring-red-200',
        'primary' => 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200',
    ];
    $currentBadge = $badgeBg[$currentColor] ?? $badgeBg['gray'];

    // Macro step label helper
    $macroText = fn($s) => $s['current']
        ? 'text-gray-900 font-extrabold'
        : ($s['completed'] ? 'text-gray-700 font-medium' : 'text-gray-400');

    // Detail card status helpers
    $detailBorder = [
        'done'            => 'border-t-[3px] border-green-500',
        'active'          => 'border-t-[3px] border-[#8bc34a]',
        'pending'         => 'border-t-[3px] border-gray-200',
        'skipped'         => 'border-t-[3px] border-orange-400',
        'action_required' => 'border-t-[3px] border-red-500',
    ];
    $detailBadge = [
        'done'            => 'bg-green-50 text-green-700 ring-1 ring-green-200',
        'active'          => 'bg-[#f0f9e8] text-[#4a7c3f] ring-1 ring-[#8bc34a]',
        'pending'         => 'bg-gray-50 text-gray-400 ring-1 ring-gray-200',
        'skipped'         => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
        'action_required' => 'bg-red-50 text-red-700 ring-1 ring-red-300',
    ];
    $detailStatusLabel = [
        'done'            => 'Complete',
        'active'          => 'In Progress',
        'pending'         => 'Pending',
        'skipped'         => 'Skipped',
        'action_required' => 'Action Required',
    ];
    $detailIconPath = [
        'done'            => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'active'          => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'pending'         => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        'skipped'         => 'M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553z',
        'action_required' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    ];
    $detailIconColor = [
        'done'            => 'text-green-500',
        'active'          => 'text-[#8bc34a]',
        'pending'         => 'text-gray-300',
        'skipped'         => 'text-orange-400',
        'action_required' => 'text-red-500',
    ];
@endphp

<div
    class="w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md"
    x-data="{ activeStep: null, showFlags: false }"
>
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         TOP PROGRESS STRIP
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="relative h-2 w-full overflow-hidden bg-gray-100">
        <div
            class="h-full transition-all duration-700 ease-out"
            style="width: {{ $progress }}%; background: linear-gradient(90deg, #8bc34a 0%, #4a7c3f 100%);"
        ></div>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         HEADER BAND — title · state badge · health ring
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="flex items-center justify-between gap-4 border-b border-gray-100 bg-gradient-to-r from-[#f6fbee] to-white px-6 py-4">

        {{-- Left: icon + title --}}
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-sm" style="background: linear-gradient(135deg,#8bc34a 0%,#4a7c3f 100%);">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 0 1.118 0l7.108-4.061c.75-.43 1.683.113 1.683.977v7.622c0 .864-.933 1.406-1.683.977l-7.108-4.061a1.125 1.125 0 0 0-1.118 0l-7.108 4.061C3.933 17.717 3 17.175 3 16.311V8.69Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v5.25m0 0-2.25-2.25M12 8.25l2.25-2.25"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest" style="color:#4a7c3f;">Lease Journey</p>
                <p class="text-xs text-gray-400">Every stage of this lease's lifecycle</p>
            </div>
        </div>

        {{-- Right: progress chip · state badge · health ring --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="hidden rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-500 sm:inline-flex">
                {{ $progress }}% complete
            </span>

            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $currentBadge }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                {{ $currentLabel }}
            </span>

            {{-- Health ring button --}}
            <button
                type="button"
                @click="showFlags = !showFlags"
                title="Lease Health {{ $score }}/100 — Grade {{ $grade }} — click for details"
                class="group relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full transition-transform hover:scale-110 {{ $healthRing['bg'] }}"
            >
                <svg class="absolute inset-0 h-full w-full -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="3.5"/>
                    <circle
                        cx="18" cy="18" r="15.9155" fill="none"
                        stroke="{{ $healthRing['ring'] }}" stroke-width="3.5"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $score }}, 100"
                    />
                </svg>
                <span class="relative z-10 text-sm font-black {{ $healthRing['text'] }}">{{ $grade }}</span>
            </button>
        </div>
    </div>

    {{-- Health flags panel (collapsible) --}}
    @if(count($flags) > 0)
    <div
        x-show="showFlags"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0"
        class="border-b border-amber-100 bg-amber-50 px-6 py-3"
    >
        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-widest text-amber-700">
            Health Flags ({{ count($flags) }})
        </p>
        <ul class="flex flex-wrap gap-2">
            @foreach($flags as $flag)
            <li class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200">
                <svg class="h-3 w-3 shrink-0 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                {{ $flag }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="p-6">

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             TIER 1 — MACRO PHASE TRACK (7 phases, horizontal rail)
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
        <div class="relative flex items-start justify-between overflow-x-auto pb-1">
            @foreach ($macroSteps as $index => $step)
                <div
                    class="group relative flex min-w-[70px] flex-1 flex-col items-center"
                    x-data="{ tip: false }"
                    @mouseenter="tip = true"
                    @mouseleave="tip = false"
                >
                    {{-- Left connector --}}
                    @if($index > 0)
                        @php $prev = $macroSteps[$index - 1]; @endphp
                        <div class="absolute left-0 top-[15px] h-px w-1/2"
                             style="{{ $prev['completed'] ? 'background:#8bc34a;' : ($prev['current'] ? 'background:linear-gradient(90deg,#8bc34a,#d1d5db);' : 'border-top:1px dashed #d1d5db;background:transparent;') }}">
                        </div>
                    @endif
                    {{-- Right connector --}}
                    @if($index < count($macroSteps) - 1)
                        <div class="absolute right-0 top-[15px] h-px w-1/2"
                             style="{{ $step['completed'] ? 'background:#8bc34a;' : ($step['current'] ? 'background:linear-gradient(90deg,#8bc34a,#d1d5db);' : 'border-top:1px dashed #d1d5db;background:transparent;') }}">
                        </div>
                    @endif

                    {{-- Phase circle --}}
                    <div class="relative z-10">
                        @if ($step['disputed'])
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 ring-2 ring-red-400 ring-offset-2 transition-transform duration-200 group-hover:scale-110">
                                <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @elseif ($step['completed'])
                            <div class="flex h-8 w-8 items-center justify-center rounded-full ring-2 ring-offset-2 ring-green-300 transition-transform duration-200 group-hover:scale-110"
                                 style="background: linear-gradient(135deg,#8bc34a 0%,#4a7c3f 100%);">
                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @elseif ($step['current'])
                            <div class="relative transition-transform duration-200 group-hover:scale-110">
                                <span class="absolute inset-0 animate-ping rounded-full opacity-60" style="background:#8bc34a;"></span>
                                <span class="relative flex h-8 w-8 items-center justify-center rounded-full ring-2 ring-offset-2 ring-[#8bc34a]"
                                      style="background: linear-gradient(135deg,#8bc34a 0%,#4a7c3f 100%);">
                                    <span class="h-2.5 w-2.5 rounded-full bg-white shadow-sm"></span>
                                </span>
                            </div>
                        @else
                            <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-dashed border-gray-300 bg-white transition-transform duration-200 group-hover:scale-110 group-hover:border-gray-400">
                                <span class="text-[10px] font-bold text-gray-300">{{ $step['phase'] }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Tooltip --}}
                    @if ($step['timestamp'])
                    <div
                        x-show="tip"
                        x-transition
                        class="absolute bottom-full left-1/2 z-30 mb-2.5 -translate-x-1/2 whitespace-nowrap rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-medium text-white shadow-xl"
                    >
                        {{ $step['timestamp'] }}
                        <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent" style="border-top-color:#111827;"></div>
                    </div>
                    @endif

                    {{-- Phase label --}}
                    <span class="mt-2 max-w-[72px] text-center text-[11px] leading-tight {{ $macroText($step) }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             DIVIDER
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
        <div class="my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
            <span class="whitespace-nowrap text-[10px] font-bold uppercase tracking-widest text-gray-400">Detailed Steps — click to expand</span>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
        </div>

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             TIER 2 — CLICKABLE DETAIL CARDS (10 steps, 5-column grid)
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($detailSteps as $step)
                @php
                    $st     = $step['status'];
                    $isAct  = $st === 'active';
                    $isDone = $st === 'done';
                    $isCrit = $st === 'action_required';
                    $iconPath  = $detailIconPath[$st]  ?? $detailIconPath['pending'];
                    $iconColor = $detailIconColor[$st] ?? $detailIconColor['pending'];
                @endphp
                <button
                    type="button"
                    @click="activeStep = (activeStep === {{ $loop->index }}) ? null : {{ $loop->index }}"
                    class="group relative flex w-full flex-col rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8bc34a]
                           {{ $detailBorder[$st] ?? '' }}
                           {{ $isAct  ? 'ring-1 ring-[#8bc34a] ring-offset-1 shadow-[0_0_0_3px_rgba(139,195,74,0.15)]' : '' }}
                           {{ $isCrit ? 'ring-1 ring-red-300 ring-offset-1' : '' }}"
                    :class="activeStep === {{ $loop->index }}
                        ? 'ring-2 ring-[#8bc34a] shadow-lg -translate-y-0.5'
                        : ''"
                >
                    {{-- Step number + status badge --}}
                    <div class="mb-3 flex w-full items-center justify-between gap-1">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold leading-none
                                     {{ $isDone || $isAct ? 'text-white' : 'bg-gray-100 text-gray-400' }}"
                              style="{{ $isDone || $isAct ? 'background:linear-gradient(135deg,#8bc34a 0%,#4a7c3f 100%);' : '' }}"
                        >{{ $step['number'] }}</span>

                        <span class="truncate rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $detailBadge[$st] ?? '' }}
                                     {{ $isCrit ? 'animate-pulse' : '' }}">
                            {{ $detailStatusLabel[$st] ?? $st }}
                        </span>
                    </div>

                    {{-- Status icon --}}
                    <svg class="mb-2 h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                    </svg>

                    {{-- Title --}}
                    <h4 class="text-sm font-semibold leading-snug
                               {{ $isDone ? 'text-gray-600' : ($isAct ? 'text-gray-900' : 'text-gray-400') }}">
                        {{ $step['title'] }}
                    </h4>

                    {{-- Always-visible short description --}}
                    <p class="mt-1 text-xs leading-relaxed text-gray-400 line-clamp-2">{{ $step['description'] }}</p>

                    {{-- Expanded detail (click) --}}
                    <div
                        x-show="activeStep === {{ $loop->index }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-y-95"
                        x-transition:enter-end="opacity-100 scale-y-100"
                        class="mt-3 w-full origin-top"
                    >
                        @if ($step['timestamp'])
                            <p class="flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1.5 text-[10px] font-medium text-gray-500">
                                <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $step['timestamp'] }}
                            </p>
                        @else
                            <p class="rounded-md bg-gray-50 px-2 py-1.5 text-[10px] italic text-gray-400">Not yet reached</p>
                        @endif
                    </div>

                    {{-- Caret --}}
                    <div class="mt-auto flex w-full justify-end pt-3">
                        <svg
                            class="h-3.5 w-3.5 text-gray-300 transition-all duration-200 group-hover:text-gray-400"
                            :class="activeStep === {{ $loop->index }} ? 'rotate-180 !text-[#8bc34a]' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             FOOTER — legend + health recap
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gradient-to-r from-[#f6fbee] to-gray-50 px-5 py-3">
            <div class="flex flex-wrap items-center gap-4 text-[11px] text-gray-500">
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full" style="background:linear-gradient(135deg,#8bc34a,#4a7c3f);"></span>
                    Completed
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 animate-ping rounded-full" style="background:#8bc34a;opacity:0.7;"></span>
                    In Progress
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-dashed border-gray-300"></span>
                    Upcoming
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                    Action Required
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-gray-400">Health</span>
                <span class="font-extrabold text-sm {{ $healthRing['text'] }}">{{ $score }}/100</span>
                @if(count($flags) > 0)
                <button
                    type="button"
                    @click="showFlags = !showFlags"
                    class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 hover:bg-amber-100"
                >{{ count($flags) }} flag{{ count($flags) === 1 ? '' : 's' }}</button>
                @endif
            </div>
        </div>
    </div>
</div>
