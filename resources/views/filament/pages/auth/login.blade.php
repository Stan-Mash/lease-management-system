<div class="cb-login-wrap">

    {{-- Full-page photo background --}}
    <div class="cb-bg" aria-hidden="true">
        <div class="cb-bg-overlay"></div>
    </div>

    {{-- LEFT — brand content floating over photo --}}
    <div class="cb-left-panel">
        <div class="cb-panel-content">

            <div class="cb-logo-area">
                <div class="cb-logo-box">
                    <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="Chabrin Agencies" class="cb-logo-img">
                </div>
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

    {{-- RIGHT — solid white login card --}}
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
body.fi-body { background: #0d1526 !important; margin: 0 !important; padding: 0 !important; color-scheme: light !important; }

/* ══════════════════════════════════════
   FULL-PAGE PHOTO BACKGROUND
   ══════════════════════════════════════ */
.cb-bg {
    position: fixed; inset: 0; z-index: 0;
    background-image: url('/images/nairobi-bg.jpg');
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
}
/* Strong dark overlay — city still visible but text pops clearly over photo */
.cb-bg-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        105deg,
        rgba(6,10,22,0.82) 0%,
        rgba(6,10,22,0.65) 50%,
        rgba(6,10,22,0.45) 100%
    );
}

/* ══════════════════════════════════════
   OUTER WRAPPER
   ══════════════════════════════════════ */
.cb-login-wrap {
    position: relative; z-index: 1;
    display: flex;
    min-height: 100vh;
}

/* ══════════════════════════════════════
   LEFT PANEL — transparent, over photo
   ══════════════════════════════════════ */
.cb-left-panel {
    display: none;
    flex: 1;
    position: relative;
    flex-direction: column;
}
@media (min-width: 1024px) {
    .cb-left-panel  { display: flex !important; }
    .cb-mobile-logo { display: none !important; }
}

.cb-panel-content {
    display: flex; flex-direction: column; height: 100%;
}

/* Logo */
.cb-logo-area  { padding: 2.5rem 3rem; flex-shrink: 0; }
/* White frosted pill behind logo so original colours show through clearly */
.cb-logo-box {
    display: inline-block;
    background: rgba(255,255,255,0.92);
    border-radius: 14px;
    padding: 10px 20px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.5), 0 1px 4px rgba(0,0,0,0.4);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.6);
}
.cb-logo-img   {
    height: 52px; width: auto; display: block;
}
.cb-logo-badge {
    margin-top: 1rem; display: inline-flex; align-items: center; gap: 7px;
    background: rgba(218,165,32,0.28); border: 1px solid rgba(218,165,32,.8);
    border-radius: 99px; padding: 5px 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.4);
}
.cb-badge-dot  { width: 7px; height: 7px; border-radius: 50%; background: #DAA520; display: inline-block; flex-shrink: 0; }
.cb-badge-text { font-size: .65rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: #fff3b0; }

/* Brand copy */
.cb-brand-content {
    flex: 1; display: flex; flex-direction: column; justify-content: center;
    padding: 0 3rem 2rem;
}
.cb-accent-line { display: flex; align-items: center; gap: 12px; margin-bottom: 1.8rem; }
.cb-accent-bar  { height: 4px; width: 52px; border-radius: 99px; background: linear-gradient(90deg,#DAA520,#f0c040); }
.cb-accent-fade { height: 1px; flex: 1; background: linear-gradient(90deg,rgba(218,165,32,.7),transparent); }

.cb-heading {
    font-size: 3.2rem; font-weight: 800; line-height: 1.12; color: #ffffff;
    margin: 0 0 1.1rem; letter-spacing: -.03em;
    font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
    text-shadow: 0 2px 4px rgba(0,0,0,1), 0 4px 24px rgba(0,0,0,0.9), 0 8px 40px rgba(0,0,0,0.6);
}
.cb-heading-gold {
    background: linear-gradient(135deg,#f0c040,#DAA520,#fde68a);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.cb-subheading {
    color: rgba(255,255,255,0.95); font-size: .96rem; line-height: 1.8;
    max-width: 380px; margin: 0 0 2.4rem;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 12px rgba(0,0,0,0.9);
}

/* Features */
.cb-features    { display: flex; flex-direction: column; gap: .8rem; }
.cb-feature-row { display: flex; align-items: center; gap: 1rem; }
.cb-feature-icon {
    flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
    background: rgba(218,165,32,0.18); border: 1px solid rgba(218,165,32,.55);
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.cb-feature-text {
    color: #ffffff; font-size: .9rem; font-weight: 600;
    text-shadow: 0 1px 3px rgba(0,0,0,1), 0 2px 10px rgba(0,0,0,0.8);
}

/* Footer */
.cb-left-footer {
    flex-shrink: 0; padding: 1.2rem 3rem;
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(0,0,0,0.45); backdrop-filter: blur(10px);
    border-top: 1px solid rgba(218,165,32,.3);
}
.cb-footer-copy { color: rgba(255,255,255,0.6); font-size: .73rem; margin: 0; }
.cb-online      { display: flex; align-items: center; gap: .45rem; }
.cb-online-dot  { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
.cb-online-text { color: rgba(255,255,255,0.6); font-size: .73rem; }

/* ══════════════════════════════════════
   RIGHT PANEL — solid white card
   ══════════════════════════════════════ */
.cb-right-panel {
    width: 480px;
    flex-shrink: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: #ffffff;
    overflow-y: auto; padding: 3rem 2rem;
    box-shadow: -8px 0 40px rgba(0,0,0,0.3);
}

.cb-mobile-logo    { margin-bottom: 2rem; text-align: center; }
.cb-mobile-logo-img { height: 44px; width: auto; margin: 0 auto; }

.cb-form-wrap  { width: 100%; max-width: 360px; }

.cb-form-header { margin-bottom: 1.75rem; }
.cb-portal-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    border-radius: 99px; padding: .3rem .9rem;
    font-size: .72rem; font-weight: 700; letter-spacing: .05em;
    background: rgba(218,165,32,.09); color: #8a6a00;
    border: 1px solid rgba(218,165,32,.3); margin-bottom: .9rem;
}
.cb-welcome {
    font-size: 1.8rem; font-weight: 800; color: #1a1a1a;
    margin: 0 0 .35rem; letter-spacing: -.025em;
    font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
}
.cb-welcome-sub { font-size: .875rem; color: #888; margin: 0; line-height: 1.5; }

.cb-card {
    border-radius: 16px; background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,.04), 0 12px 40px rgba(218,165,32,.1);
    border: 1px solid rgba(218,165,32,.2);
    border-top: 3px solid #DAA520; overflow: hidden;
}
.cb-card-inner { padding: 1.75rem; }

.cb-meta {
    margin-top: 1.75rem; display: flex; align-items: center;
    justify-content: center; gap: .75rem; font-size: .72rem; color: #aaa;
}
.cb-meta-item  { display: flex; align-items: center; gap: .3rem; }
.cb-meta-sep   { width: 1px; height: 12px; background: #e5e7eb; display: inline-block; }
.cb-mobile-copy { margin-top: 1.25rem; text-align: center; font-size: .72rem; color: #aaa; display: none; }
@media (max-width: 1023px) {
    .cb-right-panel { width: 100%; box-shadow: none; }
    .cb-mobile-copy { display: block; }
}

/* Animations */
@keyframes chabrin-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
