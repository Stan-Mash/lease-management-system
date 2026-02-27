{{-- Chabrin Agencies — Login Page --}}

<style nonce="{{ $cspNonce }}">
@verbatim

/* ── Body reset ───────────────────────────────────────────────────── */
html, html.dark, html[class~="dark"] { color-scheme: light !important; }
body.fi-body {
    margin: 0 !important; padding: 0 !important;
    color-scheme: light !important;
    min-height: 100vh;
}

/* ── Full-page photo — THE background of the entire page ─────────── */
.cb-page {
    position: fixed; inset: 0; z-index: 0;
    background-image: url('/images/nairobi-bg.jpg');
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    pointer-events: none;
}

/* Single unified dark overlay across the whole photo */
.cb-page-overlay {
    position: absolute; inset: 0;
    background: rgba(5, 10, 22, 0.62);
    pointer-events: none;
}

/* ── Content layer sits above photo ──────────────────────────────── */
.cb-content {
    position: fixed; inset: 0; z-index: 10;
    display: flex;
    align-items: stretch;
}

/* ════════════════════════════
   LEFT — brand content
   ════════════════════════════ */
.cb-left {
    flex: 1 1 0%;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.cb-logo-wrap { padding: 2.5rem 3rem 0; flex-shrink: 0; }
.cb-logo-pill {
    display: inline-block;
    background: rgba(255,255,255,0.93);
    border-radius: 12px;
    padding: 10px 22px 10px 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.55), 0 6px 28px rgba(0,0,0,0.35);
}
.cb-logo-img { height: 46px; width: auto; display: block; }

.cb-lms-badge {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: 12px;
    background: rgba(218,165,32,0.20);
    border: 1px solid rgba(218,165,32,0.70);
    border-radius: 99px; padding: 5px 16px;
    font-size: 0.60rem; font-weight: 700;
    letter-spacing: 0.17em; text-transform: uppercase;
    color: #fde68a;
    box-shadow: 0 2px 10px rgba(0,0,0,0.4);
}
.cb-pulse-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #DAA520; display: inline-block; flex-shrink: 0;
}

.cb-headline {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; padding: 0 3rem 2rem;
}
.cb-rule {
    width: 50px; height: 4px; border-radius: 99px; margin-bottom: 1.8rem;
    background: linear-gradient(90deg, #DAA520, #f0c040);
}
.cb-h1 {
    font-size: 2.9rem; font-weight: 800; line-height: 1.13;
    letter-spacing: -0.03em; margin: 0 0 1.1rem;
    font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,1), 0 3px 14px rgba(0,0,0,0.9);
}
.cb-h1-gold {
    background: linear-gradient(135deg,#f5c842,#DAA520,#fde68a);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.cb-sub {
    font-size: 0.93rem; line-height: 1.85; margin: 0 0 2.2rem;
    color: rgba(255,255,255,0.88); max-width: 360px;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 10px rgba(0,0,0,0.8);
}
.cb-features { display: flex; flex-direction: column; gap: 10px; }
.cb-feat     { display: flex; align-items: center; gap: 13px; }
.cb-feat-ico {
    flex-shrink: 0; width: 37px; height: 37px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.98rem;
    background: rgba(218,165,32,0.16);
    border: 1px solid rgba(218,165,32,0.50);
    box-shadow: 0 2px 8px rgba(0,0,0,0.35);
}
.cb-feat-txt {
    font-size: 0.87rem; font-weight: 600; color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 8px rgba(0,0,0,0.8);
}

.cb-left-foot {
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 3rem;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(218,165,32,0.22);
}
.cb-foot-txt { font-size: 0.70rem; color: rgba(255,255,255,0.50); }
.cb-online   { display: flex; align-items: center; gap: 7px; font-size: 0.70rem; color: rgba(255,255,255,0.50); }
.cb-green-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }

/* ════════════════════════════
   RIGHT — glass card column
   No background on the column itself — photo shows through completely
   ════════════════════════════ */
.cb-right {
    width: 480px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 2rem;
    overflow-y: auto;
    /* NO background — photo shows through 100% */
}

/* Mobile logo */
.cb-mob-logo     { margin-bottom: 1.5rem; text-align: center; display: none; }
.cb-mob-logo-img { height: 40px; width: auto; margin: 0 auto; }

.cb-form-area { width: 100%; max-width: 340px; }

/* Form header */
.cb-form-hd { margin-bottom: 1.4rem; }
.cb-sec-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 11px; border-radius: 99px; margin-bottom: 0.85rem;
    background: rgba(34,197,94,0.18);
    border: 1px solid rgba(34,197,94,0.45);
    font-size: 0.67rem; font-weight: 700; letter-spacing: 0.04em;
    color: #86efac;
}
.cb-welcome {
    font-size: 1.70rem; font-weight: 800; color: #ffffff;
    margin: 0 0 0.25rem; letter-spacing: -0.025em;
    font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
    text-shadow: 0 1px 8px rgba(0,0,0,0.6);
}
.cb-welcome-sub {
    font-size: 0.84rem; color: rgba(255,255,255,0.65);
    margin: 0; line-height: 1.5;
}

/* The form card — solid white so fields are perfectly readable */
.cb-form-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.9);
    border-top: 3px solid #DAA520;
    box-shadow:
        0 4px 24px rgba(0,0,0,0.35),
        0 1px 4px rgba(0,0,0,0.15),
        0 0 0 1px rgba(218,165,32,0.12);
    overflow: hidden;
    padding: 1.6rem 1.6rem 1.4rem;
}

/* Trust bar */
.cb-trust {
    margin-top: 1.3rem;
    display: flex; align-items: center; justify-content: center;
    gap: 9px; font-size: 0.68rem; color: rgba(255,255,255,0.45);
}
.cb-trust-item { display: flex; align-items: center; gap: 4px; }
.cb-trust-sep  { width: 1px; height: 10px; background: rgba(255,255,255,0.20); display: inline-block; }

/* Mobile */
@media (max-width: 1023px) {
    .cb-left      { display: none !important; }
    .cb-right     { width: 100% !important; }
    .cb-mob-logo  { display: block !important; }
}

@keyframes chabrin-pulse { 0%,100%{opacity:1} 50%{opacity:.22} }
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

{{-- Full-page photo --}}
<div class="cb-page" aria-hidden="true">
    <div class="cb-page-overlay"></div>
</div>

{{-- All content floats above the photo --}}
<div class="cb-content">

    {{-- LEFT --}}
    <div class="cb-left">
        <div class="cb-logo-wrap">
            <div class="cb-logo-pill">
                <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-logo-img">
            </div>
            <div class="cb-lms-badge">
                <span class="cb-pulse-dot"></span>
                <span>Lease Management System</span>
            </div>
        </div>

        <div class="cb-headline">
            <div class="cb-rule"></div>
            <h1 class="cb-h1">
                Lease Management<br>
                <span class="cb-h1-gold">Made Simple.</span>
            </h1>
            <p class="cb-sub">
                Manage properties, tenants, and lease workflows —<br>
                all in one secure platform built for Chabrin Agencies.
            </p>
            <div class="cb-features">
                @foreach([
                    ['🏢','Multi-zone property management'],
                    ['✍️','Digital signing with OTP verification'],
                    ['📄','Automated lease document generation'],
                    ['📊','Real-time occupancy & revenue tracking'],
                ] as [$ico,$txt])
                <div class="cb-feat">
                    <div class="cb-feat-ico">{{ $ico }}</div>
                    <span class="cb-feat-txt">{{ $txt }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="cb-left-foot">
            <span class="cb-foot-txt">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</span>
            <span class="cb-online">
                <span class="cb-green-dot chabrin-pulse"></span>
                System Online
            </span>
        </div>
    </div>

    {{-- RIGHT --}}
    <div class="cb-right">

        <div class="cb-mob-logo">
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-mob-logo-img">
        </div>

        <div class="cb-form-area">
            <div class="cb-form-hd">
                <div class="cb-sec-tag">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Secure Access Portal
                </div>
                <h2 class="cb-welcome">Welcome back</h2>
                <p class="cb-welcome-sub">Sign in to your Chabrin account to continue</p>
            </div>

            <div class="cb-form-card">
                {{ $this->content }}
            </div>

            <div class="cb-trust">
                <span class="cb-trust-item">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
