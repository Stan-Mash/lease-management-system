<div class="cb-wrap">

    {{-- Full-page Nairobi photo --}}
    <div class="cb-bg" aria-hidden="true">
        <div class="cb-bg-overlay"></div>
    </div>

    {{-- LEFT — brand panel over photo --}}
    <div class="cb-left">
        <div class="cb-left-inner">

            {{-- Logo --}}
            <div class="cb-logo-wrap">
                <div class="cb-logo-pill">
                    <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-logo-img">
                </div>
                <div class="cb-sys-badge">
                    <span class="cb-pulse-dot"></span>
                    <span>Lease Management System</span>
                </div>
            </div>

            {{-- Headline --}}
            <div class="cb-headline-area">
                <div class="cb-rule"></div>
                <h1 class="cb-h1">Lease Management<br><span class="cb-gold-text">Made Simple.</span></h1>
                <p class="cb-sub">Manage properties, tenants, and lease workflows —<br>all in one secure platform built for Chabrin Agencies.</p>
            </div>

            {{-- Feature chips --}}
            <div class="cb-chips">
                @foreach ([
                    ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                    ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                    ['icon' => '📄', 'text' => 'Automated lease document generation'],
                    ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                ] as $f)
                <div class="cb-chip">
                    <div class="cb-chip-icon">{{ $f['icon'] }}</div>
                    <span class="cb-chip-text">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="cb-left-foot">
                <span class="cb-foot-copy">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</span>
                <span class="cb-online"><span class="cb-green-dot chabrin-pulse"></span>System Online</span>
            </div>

        </div>
    </div>

    {{-- RIGHT — white login panel --}}
    <div class="cb-right">

        {{-- Mobile logo --}}
        <div class="cb-mob-logo">
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-mob-logo-img">
        </div>

        <div class="cb-form-area">

            {{-- Header --}}
            <div class="cb-form-head">
                <div class="cb-secure-tag">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Secure Access Portal
                </div>
                <h2 class="cb-welcome">Welcome back</h2>
                <p class="cb-welcome-sub">Sign in to your Chabrin account to continue</p>
            </div>

            {{-- Filament form --}}
            <div class="cb-form-card">
                {{ $this->content }}
            </div>

            {{-- Trust bar --}}
            <div class="cb-trust-bar">
                <span class="cb-trust-item">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    SSL Encrypted
                </span>
                <span class="cb-trust-sep"></span>
                <span>Chabrin Agencies Ltd</span>
                <span class="cb-trust-sep"></span>
                <span>{{ date('Y') }}</span>
            </div>

        </div>
    </div>

</div>

<style nonce="{{ $cspNonce }}">
@verbatim
/* ── Reset Filament simple-layout wrappers ────────────────────────────── */
html body .fi-simple-layout,
html body .fi-simple-main-ctn,
html body .fi-simple-main,
html body .fi-simple-page {
    display: block !important;
    width: 100% !important; max-width: none !important;
    padding: 0 !important; margin: 0 !important;
    min-height: unset !important; flex-direction: unset !important; align-items: unset !important;
}
html, html.dark, html[class~="dark"] { color-scheme: light !important; }
body.fi-body { background: #07111f !important; margin: 0; padding: 0; color-scheme: light !important; }

/* ── Photo background (full page, fixed) ─────────────────────────────── */
.cb-bg {
    position: fixed; inset: 0; z-index: 0;
    background-image: url('/images/nairobi-bg.jpg');
    background-size: cover; background-position: center;
}
.cb-bg-overlay {
    position: absolute; inset: 0;
    /* Heavier on the left so text pops; lighter on the right so city shows */
    background: linear-gradient(
        100deg,
        rgba(4, 8, 18, 0.88) 0%,
        rgba(4, 8, 18, 0.72) 45%,
        rgba(4, 8, 18, 0.52) 100%
    );
}

/* ── Outer shell ──────────────────────────────────────────────────────── */
.cb-wrap {
    position: relative; z-index: 1;
    display: flex; min-height: 100vh;
}

/* ══════════════════════════════════════
   LEFT PANEL
   ══════════════════════════════════════ */
.cb-left {
    display: none; /* hidden on mobile */
    flex: 1;
    position: relative;
    flex-direction: column;
}
@media (min-width: 1024px) {
    .cb-left    { display: flex !important; }
    .cb-mob-logo { display: none !important; }
}

.cb-left-inner {
    display: flex; flex-direction: column;
    height: 100%; padding: 0;
}

/* — Logo area — */
.cb-logo-wrap { padding: 2.75rem 3.5rem 0; flex-shrink: 0; }

.cb-logo-pill {
    display: inline-block;
    background: rgba(255, 255, 255, 0.93);
    border-radius: 12px;
    padding: 10px 22px 10px 18px;
    box-shadow:
        0 2px 8px rgba(0,0,0,0.45),
        0 8px 32px rgba(0,0,0,0.35);
}
.cb-logo-img { height: 48px; width: auto; display: block; }

.cb-sys-badge {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: 14px;
    background: rgba(218,165,32,0.20);
    border: 1px solid rgba(218,165,32,0.70);
    border-radius: 99px; padding: 5px 18px;
    font-size: 0.62rem; font-weight: 700;
    letter-spacing: 0.18em; text-transform: uppercase;
    color: #fde68a;
    box-shadow: 0 2px 12px rgba(0,0,0,0.4);
}
.cb-pulse-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #DAA520; flex-shrink: 0; display: inline-block;
}

/* — Headline area — */
.cb-headline-area {
    flex: 1; display: flex; flex-direction: column; justify-content: center;
    padding: 0 3.5rem 1.5rem;
}
.cb-rule {
    width: 52px; height: 4px; border-radius: 99px; margin-bottom: 2rem;
    background: linear-gradient(90deg, #DAA520, #f0c040);
}
.cb-h1 {
    font-size: 3rem; font-weight: 800; line-height: 1.13;
    letter-spacing: -0.03em; margin: 0 0 1.2rem;
    font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
    color: #ffffff;
    text-shadow:
        0 1px 2px rgba(0,0,0,1),
        0 3px 16px rgba(0,0,0,0.9),
        0 6px 40px rgba(0,0,0,0.6);
}
.cb-gold-text {
    background: linear-gradient(135deg, #f5c842, #DAA520, #fde68a);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.cb-sub {
    font-size: 0.94rem; line-height: 1.85; margin: 0 0 2.5rem;
    color: rgba(255,255,255,0.90); max-width: 370px;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 10px rgba(0,0,0,0.85);
}

/* — Feature chips — */
.cb-chips { display: flex; flex-direction: column; gap: 10px; }
.cb-chip {
    display: flex; align-items: center; gap: 14px;
}
.cb-chip-icon {
    flex-shrink: 0; width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; border-radius: 10px;
    background: rgba(218,165,32,0.16);
    border: 1px solid rgba(218,165,32,0.50);
    box-shadow: 0 2px 8px rgba(0,0,0,0.35);
}
.cb-chip-text {
    font-size: 0.88rem; font-weight: 600; color: #ffffff;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 8px rgba(0,0,0,0.8);
}

/* — Left footer — */
.cb-left-foot {
    flex-shrink: 0; margin-top: 2.5rem;
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.1rem 3.5rem;
    background: rgba(0,0,0,0.50); backdrop-filter: blur(12px);
    border-top: 1px solid rgba(218,165,32,0.25);
}
.cb-foot-copy { font-size: 0.71rem; color: rgba(255,255,255,0.55); }
.cb-online {
    display: flex; align-items: center; gap: 7px;
    font-size: 0.71rem; color: rgba(255,255,255,0.55);
}
.cb-green-dot {
    display: inline-block; width: 7px; height: 7px;
    border-radius: 50%; background: #22c55e; flex-shrink: 0;
}

/* ══════════════════════════════════════
   RIGHT PANEL — clean white
   ══════════════════════════════════════ */
.cb-right {
    width: 460px; flex-shrink: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #f8f9fb;
    overflow-y: auto; padding: 3rem 2rem;
    box-shadow: -12px 0 60px rgba(0,0,0,0.35);
    border-left: 1px solid rgba(255,255,255,0.08);
}

/* Mobile logo */
.cb-mob-logo    { margin-bottom: 2rem; text-align: center; }
.cb-mob-logo-img { height: 42px; width: auto; margin: 0 auto; }

/* Form container */
.cb-form-area { width: 100%; max-width: 340px; }

/* Header */
.cb-form-head { margin-bottom: 1.6rem; }

.cb-secure-tag {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 99px; margin-bottom: 1rem;
    background: #f0f9f0; border: 1px solid #bbf7d0;
    font-size: 0.69rem; font-weight: 700; letter-spacing: 0.04em;
    color: #15803d;
}
.cb-welcome {
    font-size: 1.75rem; font-weight: 800; color: #111827;
    margin: 0 0 0.3rem; letter-spacing: -0.025em;
    font-family: 'Century Gothic', 'Gill Sans', Arial, sans-serif;
}
.cb-welcome-sub { font-size: 0.855rem; color: #6b7280; margin: 0; line-height: 1.5; }

/* Card wrapping Filament's form */
.cb-form-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e9eaf0;
    border-top: 3px solid #DAA520;
    box-shadow:
        0 1px 3px rgba(0,0,0,0.06),
        0 8px 32px rgba(218,165,32,0.10);
    overflow: hidden;
    padding: 1.6rem 1.6rem 1.4rem;
}

/* Trust bar */
.cb-trust-bar {
    margin-top: 1.4rem;
    display: flex; align-items: center; justify-content: center;
    gap: 10px; font-size: 0.70rem; color: #9ca3af;
}
.cb-trust-item { display: flex; align-items: center; gap: 4px; }
.cb-trust-sep  { width: 1px; height: 10px; background: #d1d5db; display: inline-block; }

/* Mobile tweaks */
@media (max-width: 1023px) {
    .cb-right { width: 100%; box-shadow: none; background: #f8f9fb; }
}

/* Pulse animation */
@keyframes chabrin-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.25; } }
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
