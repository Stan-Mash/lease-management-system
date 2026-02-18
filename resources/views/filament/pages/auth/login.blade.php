<div style="display:flex; min-height:100vh; background:#f8f7f4;">

    {{-- ═══════════════════════════════════════════════════════
         LEFT PANEL — Chabrin brand panel (desktop only)
    ═══════════════════════════════════════════════════════ --}}
    <div style="
        display:none;
        width:52%;
        flex-direction:column;
        position:relative;
        overflow:hidden;
        background:linear-gradient(160deg, #1a365d 0%, #0f2240 55%, #0a1628 100%);
    " class="chabrin-left-panel">

        {{-- Geometric gold grid --}}
        <div style="
            position:absolute; inset:0; pointer-events:none;
            background-image:
                repeating-linear-gradient(45deg,  rgba(218,165,32,.07) 0px, rgba(218,165,32,.07) 1px, transparent 1px, transparent 70px),
                repeating-linear-gradient(-45deg, rgba(218,165,32,.07) 0px, rgba(218,165,32,.07) 1px, transparent 1px, transparent 70px);
        "></div>

        {{-- Gold radial glow --}}
        <div style="
            position:absolute; width:520px; height:520px; border-radius:50%; pointer-events:none;
            background:radial-gradient(circle, rgba(218,165,32,.13) 0%, transparent 70%);
            top:35%; left:50%; transform:translate(-50%,-50%);
        "></div>

        {{-- TOP: Logo --}}
        <div style="position:relative; z-index:10; padding:2.5rem; flex-shrink:0;">
            <img src="{{ asset('images/chabrin-logo.png') }}"
                 alt="Chabrin Agencies"
                 style="height:48px; width:auto; filter:drop-shadow(0 2px 8px rgba(0,0,0,.4));"
                 onerror="this.style.display='none';">
            <p style="color:#DAA520; font-size:.65rem; letter-spacing:.18em; text-transform:uppercase; margin-top:.6rem; font-weight:600;">
                Lease Management System
            </p>
        </div>

        {{-- CENTRE: Brand content --}}
        <div style="position:relative; z-index:10; flex:1; display:flex; flex-direction:column; justify-content:center; padding:0 3.5rem 2rem;">

            {{-- Gold rule --}}
            <div style="height:3px; width:48px; border-radius:99px; background:linear-gradient(90deg,#DAA520,#f0c040); margin-bottom:1.5rem;"></div>

            <h1 style="font-size:2.5rem; font-weight:800; line-height:1.15; color:#fff; margin:0 0 1rem; letter-spacing:-.02em;">
                Lease Management<br>
                <span style="color:#DAA520;">Made Simple.</span>
            </h1>

            <p style="color:#94a3b8; font-size:.95rem; line-height:1.7; max-width:300px; margin:0 0 2.5rem;">
                Manage properties, tenants, and lease workflows — all in one secure platform built for Chabrin Agencies.
            </p>

            {{-- Feature bullets --}}
            <div style="display:flex; flex-direction:column; gap:.75rem;">
                @foreach ([
                    ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                    ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                    ['icon' => '📄', 'text' => 'Automated lease document generation'],
                    ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                ] as $f)
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <div style="
                        flex-shrink:0; width:34px; height:34px; border-radius:8px;
                        display:flex; align-items:center; justify-content:center; font-size:.85rem;
                        background:rgba(218,165,32,.1); border:1px solid rgba(218,165,32,.22);
                    ">{{ $f['icon'] }}</div>
                    <span style="color:#cbd5e1; font-size:.88rem;">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- BOTTOM: Footer --}}
        <div style="
            position:relative; z-index:10; flex-shrink:0;
            padding:1.25rem 3.5rem;
            border-top:1px solid rgba(218,165,32,.15);
            display:flex; align-items:center; justify-content:space-between;
        ">
            <p style="color:#475569; font-size:.72rem; margin:0;">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</p>
            <div style="display:flex; align-items:center; gap:.4rem;">
                <span class="chabrin-pulse" style="
                    display:inline-block; width:7px; height:7px; border-radius:50%;
                    background:#34d399;
                "></span>
                <span style="color:#475569; font-size:.72rem;">System Online</span>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         RIGHT PANEL — Login form
    ═══════════════════════════════════════════════════════ --}}
    <div style="
        flex:1;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        background:#fff; overflow-y:auto; padding:3rem 1.5rem;
    ">

        {{-- Mobile logo --}}
        <div class="chabrin-mobile-logo" style="margin-bottom:2rem; text-align:center;">
            <img src="{{ asset('images/chabrin-logo.png') }}"
                 alt="Chabrin Agencies"
                 style="height:40px; width:auto; margin:0 auto;"
                 onerror="this.style.display='none';">
        </div>

        <div style="width:100%; max-width:360px;">

            {{-- Badge + heading --}}
            <div style="margin-bottom:1.75rem;">
                <span style="
                    display:inline-flex; align-items:center; gap:.4rem;
                    border-radius:99px; padding:.3rem .85rem;
                    font-size:.72rem; font-weight:600;
                    background:rgba(218,165,32,.08); color:#a07818;
                    border:1px solid rgba(218,165,32,.22);
                    margin-bottom:.85rem;
                ">
                    <span style="width:6px;height:6px;border-radius:50%;background:#DAA520;flex-shrink:0;"></span>
                    Secure Access Portal
                </span>

                <h2 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0 0 .35rem; letter-spacing:-.02em;">
                    Welcome back
                </h2>
                <p style="font-size:.875rem; color:#64748b; margin:0;">
                    Sign in to your Chabrin account to continue
                </p>
            </div>

            {{-- Login card --}}
            <div style="
                border-radius:16px; background:#fff;
                box-shadow:0 1px 3px rgba(0,0,0,.07), 0 8px 32px rgba(0,0,0,.07);
                border:1px solid rgba(0,0,0,.06);
                border-top:3px solid #DAA520;
                overflow:hidden;
            ">
                <div style="padding:1.75rem;">
                    {{ $this->content }}
                </div>
            </div>

            {{-- Footer meta --}}
            <div style="
                margin-top:1.75rem;
                display:flex; align-items:center; justify-content:center; gap:.75rem;
                font-size:.72rem; color:#94a3b8;
            ">
                <span style="display:flex; align-items:center; gap:.3rem;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    SSL Secured
                </span>
                <span style="width:1px;height:12px;background:#e2e8f0;"></span>
                <span>Chabrin Agencies Ltd</span>
                <span style="width:1px;height:12px;background:#e2e8f0;"></span>
                <span>{{ date('Y') }}</span>
            </div>

            <p class="chabrin-mobile-logo" style="margin-top:1.25rem; text-align:center; font-size:.72rem; color:#94a3b8;">
                &copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.
            </p>
        </div>
    </div>

</div>

<style>
    /* Pulse animation */
    @keyframes chabrin-pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.3; }
    }
    .chabrin-pulse { animation: chabrin-pulse 2s ease-in-out infinite; }

    /* Show left panel only on desktop */
    @media (min-width: 1024px) {
        .chabrin-left-panel  { display: flex !important; }
        .chabrin-mobile-logo { display: none !important; }
    }

    /* Force body background */
    body.fi-body { background: #f8f7f4 !important; margin: 0 !important; padding: 0 !important; }

    /* Strip ALL Filament simple-page wrapper constraints */
    .fi-simple-layout,
    .fi-simple-main-ctn,
    .fi-simple-main,
    .fi-simple-page {
        all: unset !important;
        display: contents !important;
    }
</style>
