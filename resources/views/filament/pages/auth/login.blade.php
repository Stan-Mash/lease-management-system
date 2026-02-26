<div class="cb-login-wrap">

    {{-- RIGHT PANEL — floating card over full-screen photo --}}
    <div class="cb-right-panel">

        <div class="cb-mobile-logo">
            <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-mobile-logo-img">
        </div>

        <div class="cb-form-wrap">

            <div class="cb-form-header">
                <div class="cb-card-logo">
                    <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-card-logo-img">
                </div>
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

            <div class="cb-meta">
                <span class="cb-meta-item">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    SSL Secured
                </span>
                <span class="cb-meta-sep"></span>
                <span>Chabrin Agencies Ltd</span>
                <span class="cb-meta-sep"></span>
                <span>{{ date('Y') }}</span>
            </div>

            <p class="cb-mobile-copy">&copy; {{ date('Y') }} Chabrin Agencies Ltd. All rights reserved.</p>
        </div>
    </div>

    {{-- Bottom-left brand tagline over the photo --}}
    <div class="cb-photo-brand">
        <div class="cb-photo-brand-line"></div>
        <p class="cb-photo-brand-text">Lease Management Made Simple.</p>
        <p class="cb-photo-brand-sub">Built for Chabrin Agencies · Nairobi, Kenya</p>
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

/* ── Full-screen Nairobi photo background ── */
body.fi-body {
    margin: 0 !important; padding: 0 !important;
    color-scheme: light !important;
    background-image: url('/images/nairobi-bg.jpg') !important;
    background-size: cover !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    background-attachment: fixed !important;
}
/* Dark overlay so card pops */
body.fi-body::before {
    content: '';
    position: fixed; inset: 0; z-index: 0;
    background: linear-gradient(
        135deg,
        rgba(10,15,30,0.62) 0%,
        rgba(15,20,40,0.55) 50%,
        rgba(10,15,30,0.68) 100%
    );
    pointer-events: none;
}

/* ── Outer wrapper ── */
.cb-login-wrap {
    position: relative; z-index: 1;
    display: flex;
    min-height: 100vh;
    align-items: center;
    justify-content: flex-end;
    padding: 2rem 5vw;
}

/* ── Floating login card — right side ── */
.cb-right-panel {
    position: relative; z-index: 10;
    width: 100%;
    max-width: 420px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.cb-mobile-logo     { display: none; }

.cb-form-wrap       { width: 100%; }

/* Card logo */
.cb-card-logo       { margin-bottom: 1.4rem; }
.cb-card-logo-img   { height: 48px; width: auto; }

/* Header */
.cb-form-header     { margin-bottom: 1.5rem; }
.cb-portal-badge    { display: inline-flex; align-items: center; gap: .4rem;
                      border-radius: 99px; padding: .28rem .85rem;
                      font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
                      background: rgba(218,165,32,.15); color: #DAA520;
                      border: 1px solid rgba(218,165,32,.4); margin-bottom: .85rem; }
.cb-badge-dot       { width: 6px; height: 6px; border-radius: 50%; background: #DAA520;
                      display: inline-block; flex-shrink: 0; }
.cb-welcome         { font-size: 1.75rem; font-weight: 800; color: #ffffff;
                      margin: 0 0 .35rem; letter-spacing: -.025em;
                      font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
                      text-shadow: 0 2px 8px rgba(0,0,0,0.4); }
.cb-welcome-sub     { font-size: .875rem; color: rgba(255,255,255,0.7);
                      margin: 0; line-height: 1.5; }

/* ── Card — frosted glass ── */
.cb-card {
    border-radius: 20px;
    background: rgba(255,255,255,0.97);
    box-shadow:
        0 8px 32px rgba(0,0,0,0.28),
        0 2px 8px rgba(0,0,0,0.12),
        0 0 0 1px rgba(218,165,32,0.2);
    border-top: 3px solid #DAA520;
    overflow: hidden;
}
.cb-card-inner { padding: 1.75rem 2rem; }

/* Meta bar */
.cb-meta        { margin-top: 1.4rem; display: flex; align-items: center;
                  justify-content: center; gap: .65rem; font-size: .7rem;
                  color: rgba(255,255,255,0.55); }
.cb-meta-item   { display: flex; align-items: center; gap: .3rem; color: rgba(255,255,255,0.55); }
.cb-meta-item svg { color: rgba(218,165,32,0.7); }
.cb-meta-sep    { width: 1px; height: 11px; background: rgba(255,255,255,0.2); display: inline-block; }

.cb-mobile-copy { margin-top: 1rem; text-align: center; font-size: .7rem;
                  color: rgba(255,255,255,0.4); display: none; }
@media (max-width: 640px) {
    .cb-login-wrap  { justify-content: center; padding: 1.5rem; }
    .cb-mobile-copy { display: block; }
}

/* ── Bottom-left brand tagline ── */
.cb-photo-brand {
    position: fixed; bottom: 2.5rem; left: 3rem;
    z-index: 5; pointer-events: none;
}
.cb-photo-brand-line {
    width: 36px; height: 3px; border-radius: 99px;
    background: linear-gradient(90deg,#DAA520,#f0c040);
    margin-bottom: .65rem;
}
.cb-photo-brand-text {
    font-size: 1.05rem; font-weight: 700; color: #ffffff;
    margin: 0 0 .25rem;
    font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
    text-shadow: 0 2px 10px rgba(0,0,0,0.6);
    letter-spacing: -.01em;
}
.cb-photo-brand-sub {
    font-size: .75rem; color: rgba(255,255,255,0.6);
    margin: 0; letter-spacing: .02em;
    text-shadow: 0 1px 4px rgba(0,0,0,0.5);
}
@media (max-width: 640px) { .cb-photo-brand { display: none; } }

/* ── Animations ── */
@keyframes chabrin-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
