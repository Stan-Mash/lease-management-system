<div style="display:flex; min-height:100vh; background:#fff;">

    {{-- ═══════════════════════════════════════════════════════
         LEFT PANEL — Warm champagne / gold brand panel
    ═══════════════════════════════════════════════════════ --}}
    <div style="
        display:none;
        width:52%;
        flex-direction:column;
        position:relative;
        overflow:hidden;
        background: linear-gradient(150deg, #fffdf5 0%, #fef9e7 45%, #fdf3c8 100%);
    " class="chabrin-left-panel">

        {{-- Decorative gold arc top-right --}}
        <div style="
            position:absolute; top:-120px; right:-120px;
            width:420px; height:420px; border-radius:50%; pointer-events:none;
            background: radial-gradient(circle, rgba(218,165,32,0.18) 0%, transparent 70%);
        "></div>

        {{-- Decorative small arc bottom-left --}}
        <div style="
            position:absolute; bottom:-80px; left:-80px;
            width:280px; height:280px; border-radius:50%; pointer-events:none;
            background: radial-gradient(circle, rgba(218,165,32,0.12) 0%, transparent 70%);
        "></div>

        {{-- Fine dot-grid pattern --}}
        <div style="
            position:absolute; inset:0; pointer-events:none; opacity:0.4;
            background-image: radial-gradient(circle, rgba(218,165,32,0.4) 1px, transparent 1px);
            background-size: 28px 28px;
        "></div>

        {{-- TOP: Logo --}}
        <div style="position:relative; z-index:10; padding:2.5rem 3rem; flex-shrink:0;">
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}"
                 alt="Chabrin Agencies"
                 style="height:52px; width:auto;">
            <div style="
                margin-top:0.9rem;
                display:inline-flex; align-items:center; gap:6px;
                background:rgba(218,165,32,0.14); border:1px solid rgba(218,165,32,0.3);
                border-radius:99px; padding:3px 10px;
            ">
                <span style="width:6px;height:6px;border-radius:50%;background:#DAA520;display:inline-block;"></span>
                <span style="font-size:.6rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#8a6a00;">
                    Lease Management System
                </span>
            </div>
        </div>

        {{-- CENTRE: Brand content --}}
        <div style="position:relative; z-index:10; flex:1; display:flex; flex-direction:column; justify-content:center; padding:0 3rem 2rem;">

            {{-- Gold accent line --}}
            <div style="
                display:flex; align-items:center; gap:10px; margin-bottom:1.6rem;
            ">
                <div style="height:3px; width:40px; border-radius:99px; background:linear-gradient(90deg,#DAA520,#f0c040);"></div>
                <div style="height:1px; flex:1; background:linear-gradient(90deg,rgba(218,165,32,0.3),transparent);"></div>
            </div>

            <h1 style="
                font-size:2.4rem; font-weight:800; line-height:1.18;
                color:#1a1a1a; margin:0 0 0.9rem; letter-spacing:-.025em;
                font-family:'Century Gothic','Gill Sans',Arial,sans-serif;
            ">
                Lease Management<br>
                <span style="
                    background:linear-gradient(135deg,#b8860b,#DAA520,#f0c040);
                    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
                    background-clip:text;
                ">Made Simple.</span>
            </h1>

            <p style="color:#5a5a5a; font-size:.94rem; line-height:1.75; max-width:310px; margin:0 0 2.2rem;">
                Manage properties, tenants, and lease workflows — all in one secure platform built for Chabrin Agencies.
            </p>

            {{-- Feature list --}}
            <div style="display:flex; flex-direction:column; gap:.65rem;">
                @foreach ([
                    ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                    ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                    ['icon' => '📄', 'text' => 'Automated lease document generation'],
                    ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                ] as $f)
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <div style="
                        flex-shrink:0; width:34px; height:34px; border-radius:9px;
                        display:flex; align-items:center; justify-content:center; font-size:.9rem;
                        background:#fff; border:1px solid rgba(218,165,32,0.35);
                        box-shadow:0 2px 6px rgba(218,165,32,0.12);
                    ">{{ $f['icon'] }}</div>
                    <span style="color:#444; font-size:.87rem; font-weight:500;">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- BOTTOM: Footer --}}
        <div style="
            position:relative; z-index:10; flex-shrink:0;
            padding:1.1rem 3rem;
            border-top:1px solid rgba(218,165,32,0.2);
            display:flex; align-items:center; justify-content:space-between;
            background:rgba(255,255,255,0.5);
        ">
            <p style="color:#999; font-size:.72rem; margin:0;">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</p>
            <div style="display:flex; align-items:center; gap:.4rem;">
                <span class="chabrin-pulse" style="
                    display:inline-block; width:7px; height:7px; border-radius:50%;
                    background:#22c55e;
                "></span>
                <span style="color:#888; font-size:.72rem;">System Online</span>
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
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}"
                 alt="Chabrin Agencies"
                 style="height:44px; width:auto; margin:0 auto;">
        </div>

        <div style="width:100%; max-width:360px;">

            {{-- Badge + heading --}}
            <div style="margin-bottom:1.75rem;">
                <span style="
                    display:inline-flex; align-items:center; gap:.4rem;
                    border-radius:99px; padding:.3rem .85rem;
                    font-size:.72rem; font-weight:700; letter-spacing:.05em;
                    background:rgba(218,165,32,0.09); color:#8a6a00;
                    border:1px solid rgba(218,165,32,0.25);
                    margin-bottom:.9rem;
                ">
                    <span style="width:6px;height:6px;border-radius:50%;background:#DAA520;flex-shrink:0;"></span>
                    Secure Access Portal
                </span>

                <h2 style="
                    font-size:1.7rem; font-weight:800; color:#1a1a1a;
                    margin:0 0 .35rem; letter-spacing:-.025em;
                    font-family:'Century Gothic','Gill Sans',Arial,sans-serif;
                ">
                    Welcome back
                </h2>
                <p style="font-size:.875rem; color:#888; margin:0; line-height:1.5;">
                    Sign in to your Chabrin account to continue
                </p>
            </div>

            {{-- Login card --}}
            <div style="
                border-radius:16px; background:#fff;
                box-shadow:0 2px 4px rgba(0,0,0,0.04), 0 12px 40px rgba(218,165,32,0.1);
                border:1px solid rgba(218,165,32,0.2);
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
                font-size:.72rem; color:#aaa;
            ">
                <span style="display:flex; align-items:center; gap:.3rem;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    SSL Secured
                </span>
                <span style="width:1px;height:12px;background:#e5e7eb;"></span>
                <span>Chabrin Agencies Ltd</span>
                <span style="width:1px;height:12px;background:#e5e7eb;"></span>
                <span>{{ date('Y') }}</span>
            </div>

            <p class="chabrin-mobile-logo" style="margin-top:1.25rem; text-align:center; font-size:.72rem; color:#aaa;">
                &copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.
            </p>
        </div>
    </div>

</div>

<style>
    /* Force light mode */
    html.dark, html[class~="dark"] { color-scheme: light !important; }
    html { color-scheme: light !important; }

    /* Pulse animation */
    @keyframes chabrin-pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: 0.25; }
    }
    .chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

    /* Show left panel on desktop only */
    @media (min-width: 1024px) {
        .chabrin-left-panel  { display: flex !important; }
        .chabrin-mobile-logo { display: none !important; }
    }

    body.fi-body {
        background: #fff !important;
        margin: 0 !important; padding: 0 !important;
        color-scheme: light !important;
    }

    /* Strip Filament wrapper constraints */
    .fi-simple-layout, .fi-simple-main-ctn,
    .fi-simple-main, .fi-simple-page {
        all: unset !important;
        display: contents !important;
    }
</style>

<script>
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
