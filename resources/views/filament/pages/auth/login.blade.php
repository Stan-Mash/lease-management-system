<x-filament-panels::page.simple>
    {{-- Full-page split layout --}}
    <div class="fixed inset-0 flex min-h-screen w-full overflow-hidden">

        {{-- LEFT PANEL — Chabrin brand panel (hidden on mobile) --}}
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between relative overflow-hidden"
             style="background: linear-gradient(160deg, #1a365d 0%, #0f2240 55%, #0a1628 100%);">

            {{-- Subtle geometric pattern overlay --}}
            <div class="absolute inset-0 opacity-10"
                 style="background-image: repeating-linear-gradient(
                     45deg,
                     rgba(218,165,32,0.15) 0px,
                     rgba(218,165,32,0.15) 1px,
                     transparent 1px,
                     transparent 60px
                 ), repeating-linear-gradient(
                     -45deg,
                     rgba(218,165,32,0.15) 0px,
                     rgba(218,165,32,0.15) 1px,
                     transparent 1px,
                     transparent 60px
                 );">
            </div>

            {{-- Radial gold glow behind logo --}}
            <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #DAA520 0%, transparent 70%);">
            </div>

            {{-- Top bar with logo --}}
            <div class="relative z-10 p-10">
                <img src="{{ asset('images/Chabrin-Logo-background.png') }}"
                     alt="Chabrin Agencies"
                     class="h-14 w-auto drop-shadow-lg">
            </div>

            {{-- Centre content --}}
            <div class="relative z-10 flex flex-col items-start px-14 pb-4">
                {{-- Gold accent line --}}
                <div class="mb-6 h-1 w-16 rounded-full" style="background: #DAA520;"></div>

                <h1 class="text-4xl font-bold leading-tight text-white mb-4">
                    Lease Management<br>
                    <span style="color: #DAA520;">Made Simple.</span>
                </h1>

                <p class="text-slate-300 text-lg leading-relaxed max-w-sm mb-10">
                    Manage properties, tenants, and lease workflows — all in one secure platform.
                </p>

                {{-- Feature pills --}}
                <div class="flex flex-col gap-3">
                    @foreach ([
                        ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                        ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                        ['icon' => '📄', 'text' => 'Automated lease document generation'],
                        ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                    ] as $feature)
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg text-sm"
                             style="background: rgba(218,165,32,0.15); border: 1px solid rgba(218,165,32,0.3);">
                            {{ $feature['icon'] }}
                        </div>
                        <span class="text-slate-300 text-sm">{{ $feature['text'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Bottom footer --}}
            <div class="relative z-10 px-14 py-8 border-t" style="border-color: rgba(218,165,32,0.2);">
                <div class="flex items-center justify-between">
                    <p class="text-slate-500 text-xs">
                        &copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.
                    </p>
                    <div class="flex items-center gap-1">
                        <div class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <span class="text-slate-500 text-xs">System Online</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT PANEL — Login form --}}
        <div class="flex w-full lg:w-1/2 flex-col justify-center bg-white dark:bg-slate-950 overflow-y-auto">

            {{-- Mobile logo (shown only on small screens) --}}
            <div class="lg:hidden flex justify-center pt-10 px-8">
                <img src="{{ asset('images/Chabrin-Logo-background.png') }}"
                     alt="Chabrin Agencies"
                     class="h-10 w-auto">
            </div>

            <div class="mx-auto w-full max-w-md px-8 py-12 lg:py-0">

                {{-- Heading --}}
                <div class="mb-8">
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                         style="background: rgba(218,165,32,0.1); color: #b8890a; border: 1px solid rgba(218,165,32,0.25);">
                        <span class="h-1.5 w-1.5 rounded-full" style="background:#DAA520;"></span>
                        Secure Access Portal
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                        Welcome back
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Sign in to your Chabrin account
                    </p>
                </div>

                {{-- Gold top-border card wrapper --}}
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm"
                     style="border-top: 3px solid #DAA520;">
                    <div class="p-7 bg-white dark:bg-slate-900">
                        <x-filament-panels::form wire:submit="authenticate">
                            {{ $this->form }}

                            <x-filament::button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full mt-2"
                                style="background: linear-gradient(135deg, #DAA520, #c49118); color: white; border: none; font-weight: 600; letter-spacing: 0.01em;"
                                size="lg">
                                <span wire:loading.remove wire:target="authenticate">
                                    Sign In to Dashboard
                                </span>
                                <span wire:loading wire:target="authenticate" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Signing in...
                                </span>
                            </x-filament::button>
                        </x-filament-panels::form>
                    </div>
                </div>

                {{-- Bottom meta --}}
                <div class="mt-8 flex items-center justify-center gap-4 text-xs text-slate-400">
                    <span class="flex items-center gap-1">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        SSL Secured
                    </span>
                    <span class="h-3 w-px bg-slate-200 dark:bg-slate-700"></span>
                    <span>Chabrin Agencies Ltd</span>
                    <span class="h-3 w-px bg-slate-200 dark:bg-slate-700"></span>
                    <span>{{ date('Y') }}</span>
                </div>

                {{-- Mobile footer --}}
                <p class="lg:hidden mt-6 text-center text-xs text-slate-400">
                    &copy; {{ date('Y') }} Chabrin Agencies Ltd.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
