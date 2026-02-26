<div class="cb-login-wrap">

    {{-- LEFT PANEL — Nairobi photo with brand overlay --}}
    <div class="cb-left-panel">

        {{-- Photo fills left side --}}
        <div class="cb-left-photo" aria-hidden="true">
            <div class="cb-left-photo-overlay"></div>
        </div>

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

    {{-- RIGHT PANEL — original plain white --}}
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
body.fi-body { background: #fff !important; margin: 0 !important; padding: 0 !important; color-scheme: light !important; }

/* ── Outer wrapper ── */
.cb-login-wrap { display: flex; min-height: 100vh; }

/* ════════════════════════════════════════
   LEFT PANEL — full photo + brand overlay
   ════════════════════════════════════════ */
.cb-left-panel {
    display: none;
    width: 52%;
    position: relative;
    overflow: hidden;
    flex-direction: column;
}
@media (min-width: 1024px) {
    .cb-left-panel  { display: flex !important; }
    .cb-mobile-logo { display: none !important; }
}

/* Photo fills the entire left panel */
.cb-left-photo {
    position: absolute; inset: 0; z-index: 0;
    background-image: url('/images/nairobi-bg.jpg');
    background-size: cover;
    background-position: center center;
}
/* Gradient overlay — very light so the photo shows through vividly */
.cb-left-photo-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(0,0,0,0.18) 0%,
        rgba(0,0,0,0.08) 40%,
        rgba(0,0,0,0.08) 60%,
        rgba(0,0,0,0.45) 100%
    );
}

/* Content sits above photo */
.cb-panel-content {
    position: relative; z-index: 10;
    display: flex; flex-direction: column; height: 100%;
}

.cb-logo-area  { padding: 2.5rem 3rem; flex-shrink: 0; }
.cb-logo-img   { height: 52px; width: auto; }
.cb-logo-badge { margin-top: .9rem; display: inline-flex; align-items: center; gap: 6px;
                 background: rgba(0,0,0,0.35); border: 1px solid rgba(218,165,32,.55);
                 border-radius: 99px; padding: 3px 12px;
                 backdrop-filter: blur(4px); }
.cb-badge-dot  { width: 6px; height: 6px; border-radius: 50%; background: #DAA520; display: inline-block; flex-shrink: 0; }
.cb-badge-text { font-size: .6rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #fde68a; }

.cb-brand-content { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 0 3rem 2rem; }
.cb-accent-line   { display: flex; align-items: center; gap: 10px; margin-bottom: 1.6rem; }
.cb-accent-bar    { height: 3px; width: 40px; border-radius: 99px; background: linear-gradient(90deg,#DAA520,#f0c040); }
.cb-accent-fade   { height: 1px; flex: 1; background: linear-gradient(90deg,rgba(218,165,32,.6),transparent); }

.cb-heading     { font-size: 2.5rem; font-weight: 800; line-height: 1.18; color: #ffffff;
                  margin: 0 0 .9rem; letter-spacing: -.025em;
                  font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
                  text-shadow: 0 2px 12px rgba(0,0,0,0.5); }
.cb-heading-gold { background: linear-gradient(135deg,#f0c040,#DAA520,#fde68a);
                   -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.cb-subheading  { color: rgba(255,255,255,0.88); font-size: .94rem; line-height: 1.75; max-width: 340px; margin: 0 0 2.2rem;
                  text-shadow: 0 1px 6px rgba(0,0,0,0.5); }

.cb-features    { display: flex; flex-direction: column; gap: .65rem; }
.cb-feature-row { display: flex; align-items: center; gap: .75rem; }
.cb-feature-icon { flex-shrink: 0; width: 34px; height: 34px; border-radius: 9px;
                   display: flex; align-items: center; justify-content: center; font-size: .9rem;
                   background: rgba(0,0,0,0.30); border: 1px solid rgba(218,165,32,.45);
                   backdrop-filter: blur(4px); }
.cb-feature-text { color: rgba(255,255,255,0.92); font-size: .87rem; font-weight: 500;
                   text-shadow: 0 1px 4px rgba(0,0,0,0.5); }

.cb-left-footer { flex-shrink: 0; padding: 1.1rem 3rem;
                  display: flex; align-items: center; justify-content: space-between;
                  background: rgba(0,0,0,0.30);
                  backdrop-filter: blur(6px);
                  border-top: 1px solid rgba(218,165,32,.25); }
.cb-footer-copy { color: rgba(255,255,255,0.65); font-size: .72rem; margin: 0; }
.cb-online      { display: flex; align-items: center; gap: .4rem; }
.cb-online-dot  { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
.cb-online-text { color: rgba(255,255,255,0.65); font-size: .72rem; }

/* ════════════════════════════════════════
   RIGHT PANEL — original plain white
   ════════════════════════════════════════ */
.cb-right-panel    { flex: 1; display: flex; flex-direction: column;
                     align-items: center; justify-content: center;
                     background: #fff; overflow-y: auto; padding: 3rem 1.5rem; }

.cb-mobile-logo    { margin-bottom: 2rem; text-align: center; }
.cb-mobile-logo-img { height: 44px; width: auto; margin: 0 auto; }

.cb-form-wrap      { width: 100%; max-width: 360px; }

.cb-form-header    { margin-bottom: 1.75rem; }
.cb-portal-badge   { display: inline-flex; align-items: center; gap: .4rem;
                     border-radius: 99px; padding: .3rem .85rem;
                     font-size: .72rem; font-weight: 700; letter-spacing: .05em;
                     background: rgba(218,165,32,.09); color: #8a6a00;
                     border: 1px solid rgba(218,165,32,.25); margin-bottom: .9rem; }
.cb-welcome        { font-size: 1.7rem; font-weight: 800; color: #1a1a1a;
                     margin: 0 0 .35rem; letter-spacing: -.025em;
                     font-family: 'Century Gothic','Gill Sans',Arial,sans-serif; }
.cb-welcome-sub    { font-size: .875rem; color: #888; margin: 0; line-height: 1.5; }

.cb-card           { border-radius: 16px; background: #fff;
                     box-shadow: 0 2px 4px rgba(0,0,0,.04), 0 12px 40px rgba(218,165,32,.1);
                     border: 1px solid rgba(218,165,32,.2);
                     border-top: 3px solid #DAA520; overflow: hidden; }
.cb-card-inner     { padding: 1.75rem; }

.cb-meta           { margin-top: 1.75rem; display: flex; align-items: center;
                     justify-content: center; gap: .75rem; font-size: .72rem; color: #aaa; }
.cb-meta-item      { display: flex; align-items: center; gap: .3rem; }
.cb-meta-sep       { width: 1px; height: 12px; background: #e5e7eb; display: inline-block; }
.cb-mobile-copy    { margin-top: 1.25rem; text-align: center;
                     font-size: .72rem; color: #aaa; display: none; }
@media (max-width: 1023px) { .cb-mobile-copy { display: block; } }

/* ── Animations ── */
@keyframes chabrin-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
.chabrin-pulse { animation: chabrin-pulse 2.5s ease-in-out infinite; }

@endverbatim
</style>

<script nonce="{{ $cspNonce }}">
    localStorage.setItem('theme', 'light');
    document.documentElement.classList.remove('dark');
</script>
