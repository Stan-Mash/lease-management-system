<x-filament-panels::layout.base :livewire="$this">
<div class="min-h-screen flex" style="background: #f8f7f4;">

    {{-- LEFT PANEL — Chabrin brand panel (desktop only) --}}
    <div class="hidden lg:flex lg:w-[52%] flex-col relative overflow-hidden"
         style="background: linear-gradient(160deg, #1a365d 0%, #0f2240 55%, #0a1628 100%);">

        {{-- Subtle geometric grid --}}
        <div class="absolute inset-0"
             style="background-image:
                 repeating-linear-gradient(45deg, rgba(218,165,32,0.06) 0px, rgba(218,165,32,0.06) 1px, transparent 1px, transparent 70px),
                 repeating-linear-gradient(-45deg, rgba(218,165,32,0.06) 0px, rgba(218,165,32,0.06) 1px, transparent 1px, transparent 70px);
             ">
        </div>

        {{-- Gold radial glow --}}
        <div class="absolute w-[500px] h-[500px] rounded-full"
             style="background: radial-gradient(circle, rgba(218,165,32,0.12) 0%, transparent 70%);
                    top: 30%; left: 50%; transform: translate(-50%, -50%);">
        </div>

        {{-- Top: Logo --}}
        <div class="relative z-10 p-10 flex-shrink-0">
            <img src="{{ asset('images/chabrin-logo.png') }}"
                 alt="Chabrin Agencies"
                 class="h-12 w-auto drop-shadow-lg"
                 onerror="this.src='{{ asset('images/Chabrin-Logo-background.png') }}'">
        </div>

        {{-- Centre: Brand content --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center px-14 pb-8">
            <div class="mb-5 h-[3px] w-12 rounded-full" style="background: linear-gradient(90deg, #DAA520, #f0c040);"></div>

            <h1 class="text-[2.6rem] font-bold leading-tight text-white mb-4 tracking-tight">
                Lease Management<br>
                <span style="color: #DAA520;">Made Simple.</span>
            </h1>

            <p class="text-slate-300 text-base leading-relaxed max-w-xs mb-10">
                Manage properties, tenants, and lease workflows — all in one secure platform built for Chabrin Agencies.
            </p>

            {{-- Feature list --}}
            <div class="space-y-3">
                @foreach ([
                    ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                    ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                    ['icon' => '📄', 'text' => 'Automated lease document generation'],
                    ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                ] as $feature)
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm"
                         style="background: rgba(218,165,32,0.12); border: 1px solid rgba(218,165,32,0.25);">
                        {{ $feature['icon'] }}
                    </div>
                    <span class="text-slate-300 text-sm">{{ $feature['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Bottom footer --}}
        <div class="relative z-10 flex-shrink-0 px-14 py-6"
             style="border-top: 1px solid rgba(218,165,32,0.15);">
            <div class="flex items-center justify-between">
                <p class="text-slate-500 text-xs">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</p>
                <div class="flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 chabrin-pulse"></span>
                    <span class="text-slate-500 text-xs">System Online</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL — Login form --}}
    <div class="flex w-full lg:w-[48%] flex-col items-center justify-center bg-white overflow-y-auto px-6 py-12">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 text-center">
            <img src="{{ asset('images/chabrin-logo.png') }}"
                 alt="Chabrin Agencies"
                 class="h-10 w-auto mx-auto"
                 onerror="this.src='{{ asset('images/Chabrin-Logo-background.png') }}'">
        </div>

        <div class="w-full max-w-sm">

            {{-- Heading --}}
            <div class="mb-7">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium mb-3"
                      style="background: rgba(218,165,32,0.08); color: #a07818; border: 1px solid rgba(218,165,32,0.2);">
                    <span class="h-1.5 w-1.5 rounded-full" style="background:#DAA520;"></span>
                    Secure Access Portal
                </span>
                <h2 class="text-2xl font-bold text-slate-800">Welcome back</h2>
                <p class="mt-1 text-sm text-slate-500">Sign in to your Chabrin account to continue</p>
            </div>

            {{-- Card with gold top accent --}}
            <div class="rounded-2xl bg-white"
                 style="box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 8px 32px rgba(0,0,0,0.06);
                        border: 1px solid rgba(0,0,0,0.06);
                        border-top: 3px solid #DAA520;">
                <div class="p-7">
                    {{-- Filament renders the form via $this->content --}}
                    {{ $this->content }}
                </div>
            </div>

            {{-- Bottom meta --}}
            <div class="mt-7 flex items-center justify-center gap-3 text-xs text-slate-400">
                <span class="flex items-center gap-1">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    SSL Secured
                </span>
                <span class="h-3 w-px bg-slate-200"></span>
                <span>Chabrin Agencies Ltd</span>
                <span class="h-3 w-px bg-slate-200"></span>
                <span>{{ date('Y') }}</span>
            </div>

            <p class="lg:hidden mt-5 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.
            </p>
        </div>
    </div>
</div>

<style>
    @keyframes chabrin-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.35; }
    }
    .chabrin-pulse { animation: chabrin-pulse 2s ease-in-out infinite; }

    /* Override Filament simple-page centering so our layout takes full control */
    .fi-simple-layout,
    .fi-simple-main-ctn,
    .fi-simple-main,
    .fi-simple-page {
        display: contents !important;
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
</style>
</x-filament-panels::layout.base>
