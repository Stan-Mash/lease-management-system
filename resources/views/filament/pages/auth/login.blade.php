<div class="cb-login-wrap">

    {{-- Full-page Nairobi photo background --}}
    <div class="cb-bg-photo" aria-hidden="true">
        <div class="cb-bg-overlay"></div>
    </div>

    {{-- LEFT PANEL — brand content over photo --}}
    <div class="cb-left-panel">

        {{-- Decorative arcs --}}
        <div class="cb-arc-top" aria-hidden="true"></div>
        <div class="cb-arc-bottom" aria-hidden="true"></div>
        {{-- Dot-grid texture --}}
        <div class="cb-dot-grid" aria-hidden="true"></div>

        <div class="cb-panel-content">

            <div class="cb-logo-area">
                <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-logo-img">
                <div class="cb-logo-badge">
                    <span class="cb-badge-dot"></span>
                    <span class="cb-badge-text">Lease Management System</span>
                </div>
            </div>

            <div class="cb-brand-content">
                <div class="cb-accent-line">
                    <div class="cb-accent-bar"></div>
                    <div class="cb-accent-fade"></div>
                </div>
                <h1 class="cb-heading">
                    Lease Management<br>
                    <span class="cb-heading-gold">Made Simple.</span>
                </h1>
                <p class="cb-subheading">
                    Manage properties, tenants, and lease workflows — all in one secure platform built for Chabrin Agencies.
                </p>
                <div class="cb-features">
                    @foreach ([
                        ['icon' => '🏢', 'text' => 'Multi-zone property management'],
                        ['icon' => '✍️', 'text' => 'Digital signing with OTP verification'],
                        ['icon' => '📄', 'text' => 'Automated lease document generation'],
                        ['icon' => '📊', 'text' => 'Real-time occupancy & revenue tracking'],
                    ] as $f)
                    <div class="cb-feature-row">
                        <div class="cb-feature-icon">{{ $f['icon'] }}</div>
                        <span class="cb-feature-text">{{ $f['text'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="cb-left-footer">
                <p class="cb-footer-copy">&copy; {{ date('Y') }} Chabrin Agencies Ltd.</p>
                <div class="cb-online">
                    <span class="chabrin-pulse cb-online-dot"></span>
                    <span class="cb-online-text">System Online</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT PANEL — white card panel --}}
    <div class="cb-right-panel">

        <div class="cb-mobile-logo">
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-mobile-logo-img">
        </div>

        <div class="cb-form-wrap">
            <div class="cb-form-header">
                <span class="cb-portal-badge">
                    <span class="cb-badge-dot"></span>
                    Secure Access Portal
                </span>
                <h2 class="cb-welcome">Welcome back</h2>
                <p class="cb-welcome-sub">Sign in to your Chabrin account to continue</p>
            </div>

            <div class="cb-card">
                <div class="cb-card-inner">
                    {{ $this->content }}
                </div>
            </div>

            <div class="cb-trust-bar">
                <div class="cb-trust-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>SSL Secured</span>
                </div>
                <span class="cb-trust-sep"></span>
                <div class="cb-trust-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>24/7 Access</span>
                </div>
                <span class="cb-trust-sep"></span>
                <div class="cb-trust-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span>Role-Based Access</span>
                </div>
            </div>

            <p class="cb-mobile-copy">&copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.</p>
        </div>
    </div>

</div>

<style nonce="{{ $cspNonce }}">
@verbatim
html body .fi-simple-layout {
    display: block !important; flex-direction: unset !important;
    align-items: unset !important; min-height: unset !important;
    padding: 0 !important; margin: 0 !important;
}
html body .fi-simple-main-ctn,
html body .fi-simple-main,
html body .fi-simple-page {
    display: block !important; width: 100% !important;
    max-width: none !important; padding: 0 !important; margin: 0 !important;
}
html, html.dark, html[class~="dark"] { color-scheme: light !important; }
body.fi-body { background: #f5ead0 !important; margin: 0 !important; padding: 0 !important; color-scheme: light !important; }

/* ── Full-page photo background ── */
.cb-bg-photo {
    position: fixed; inset: 0; z-index: 0;
    background-image: url('/images/nairobi-bg.jpg');
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
}
/* Light warm overlay — keeps photo visible but softens it so text pops */
.cb-bg-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        135deg,
        rgba(255,253,245,0.72) 0%,
        rgba(254,249,231,0.55) 50%,
        rgba(255,250,240,0.65) 100%
    );
}

/* ── Outer wrapper ── */
.cb-login-wrap {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* ════════════════════════════════════════
   LEFT PANEL — cream / gold over photo
   ════════════════════════════════════════ */
.cb-left-panel {
    display: none;
    width: 52%;
    position: relative;
    overflow: hidden;
    flex-direction: column;
    /* Semi-transparent cream so photo shows through warmly */
    background: rgba(255, 252, 240, 0.82);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    border-right: 1px solid rgba(218,165,32,0.2);
}
@media (min-width: 1024px) {
    .cb-left-panel  { display: flex !important; }
    .cb-mobile-logo { display: none !important; }
}

/* Decorative arc — top-right */
.cb-arc-top {
    position: absolute; top: -160px; right: -160px;
    width: 420px; height: 420px; border-radius: 50%;
    background: radial-gradient(circle, rgba(218,165,32,0.22) 0%, rgba(218,165,32,0.07) 60%, transparent 100%);
    pointer-events: none;
}
/* Decorative arc — bottom-left */
.cb-arc-bottom {
    position: absolute; bottom: -140px; left: -140px;
    width: 360px; height: 360px; border-radius: 50%;
    background: radial-gradient(circle, rgba(218,165,32,0.16) 0%, rgba(218,165,32,0.04) 65%, transparent 100%);
    pointer-events: none;
}
/* Dot-grid texture */
.cb-dot-grid {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(218,165,32,0.25) 1px, transparent 1px);
    background-size: 28px 28px; opacity: .5; pointer-events: none;
}

/* Content */
.cb-panel-content {
    position: relative; z-index: 10;
    display: flex; flex-direction: column; height: 100%;
}

.cb-logo-area  { position: relative; z-index: 10; padding: 2.5rem 3rem; flex-shrink: 0; }
.cb-logo-img   { height: 52px; width: auto; }
.cb-logo-badge { margin-top: .9rem; display: inline-flex; align-items: center; gap: 6px;
                 background: rgba(218,165,32,.14); border: 1px solid rgba(218,165,32,.45);
                 border-radius: 99px; padding: 3px 10px; }
.cb-badge-dot  { width: 6px; height: 6px; border-radius: 50%; background: #DAA520; display: inline-block; flex-shrink: 0; }
.cb-badge-text { font-size: .6rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #92670a; }

.cb-brand-content { position: relative; z-index: 10; flex: 1; display: flex;
                    flex-direction: column; justify-content: center; padding: 0 3rem 2rem; }
.cb-accent-line   { display: flex; align-items: center; gap: 10px; margin-bottom: 1.6rem; }
.cb-accent-bar    { height: 3px; width: 40px; border-radius: 99px; background: linear-gradient(90deg,#DAA520,#f0c040); }
.cb-accent-fade   { height: 1px; flex: 1; background: linear-gradient(90deg,rgba(218,165,32,.5),transparent); }

.cb-heading     { font-size: 2.4rem; font-weight: 800; line-height: 1.18; color: #1a1207;
                  margin: 0 0 .9rem; letter-spacing: -.025em;
                  font-family: 'Century Gothic','Gill Sans',Arial,sans-serif; }
.cb-heading-gold { background: linear-gradient(135deg,#b8860b,#DAA520,#d4a017);
                   -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cb-subheading  { color: #5a4a1a; font-size: .94rem; line-height: 1.75; max-width: 340px; margin: 0 0 2.2rem; }

.cb-features    { display: flex; flex-direction: column; gap: .65rem; }
.cb-feature-row { display: flex; align-items: center; gap: .75rem; }
.cb-feature-icon { flex-shrink: 0; width: 34px; height: 34px; border-radius: 9px;
                   display: flex; align-items: center; justify-content: center; font-size: .9rem;
                   background: rgba(218,165,32,.15); border: 1px solid rgba(218,165,32,.38); }
.cb-feature-text { color: #3d2e08; font-size: .87rem; font-weight: 500; }

.cb-left-footer { position: relative; z-index: 10; flex-shrink: 0; padding: 1.1rem 3rem;
                  border-top: 1px solid rgba(218,165,32,.3);
                  display: flex; align-items: center; justify-content: space-between;
                  background: rgba(254,249,231,0.6); }
.cb-footer-copy { color: #7a6020; font-size: .72rem; margin: 0; }
.cb-online      { display: flex; align-items: center; gap: .4rem; }
.cb-online-dot  { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
.cb-online-text { color: #7a6020; font-size: .72rem; }

/* ════════════════════════════════════════
   RIGHT PANEL — crisp white with gold accents
   ════════════════════════════════════════ */
.cb-right-panel {
    flex: 1;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    overflow-y: auto; padding: 3rem 1.5rem;
    /* Clean white — photo visible on left, card pops on right */
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.cb-mobile-logo    { margin-bottom: 2rem; text-align: center; }
.cb-mobile-logo-img { height: 44px; width: auto; margin: 0 auto; }

.cb-form-wrap  { width: 100%; max-width: 370px; }

.cb-form-header { margin-bottom: 1.75rem; }
.cb-portal-badge { display: inline-flex; align-items: center; gap: .4rem;
                   border-radius: 99px; padding: .3rem .85rem;
                   font-size: .72rem; font-weight: 700; letter-spacing: .05em;
                   background: rgba(218,165,32,.09); color: #8a6a00;
                   border: 1px solid rgba(218,165,32,.28); margin-bottom: .9rem; }
.cb-welcome     { font-size: 1.75rem; font-weight: 800; color: #1a1a1a;
                  margin: 0 0 .35rem; letter-spacing: -.025em;
                  font-family: 'Century Gothic','Gill Sans',Arial,sans-serif; }
.cb-welcome-sub { font-size: .875rem; color: #888; margin: 0; line-height: 1.5; }

/* Card — white, gold top border, elevated shadow */
.cb-card {
    border-radius: 18px; background: #fff;
    box-shadow:
        0 2px 6px rgba(0,0,0,.05),
        0 16px 48px rgba(218,165,32,.12),
        0 0 0 1px rgba(218,165,32,.15);
    border-top: 3px solid #DAA520;
    overflow: hidden;
}
.cb-card-inner { padding: 1.75rem 1.85rem; }

/* Trust bar */
.cb-trust-bar {
    margin-top: 1.6rem;
    display: flex; align-items: center; justify-content: center;
    gap: .65rem; flex-wrap: wrap;
}
.cb-trust-item {
    display: flex; align-items: center; gap: .32rem;
    font-size: .72rem; color: #aaa;
}
.cb-trust-item svg { color: #DAA520; flex-shrink: 0; }
.cb-trust-sep { width: 1px; height: 12px; background: #e5e7eb; display: inline-block; }

.cb-mobile-copy { margin-top: 1.25rem; text-align: center; font-size: .72rem; color: #aaa; display: none; }
@media (max-width: 1023px) { .cb-mobile-copy { display: block; } }

/* ── Animations ── */
@keyframes chabrin-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .25; }
}
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
