{{-- Single root required by Livewire --}}
<div>
<div class="cb-login-wrap" style="background-image: url('{{ asset('images/nairobi-login-bg.png') }}');">

    {{-- Left panel: cream overlay --}}
    <div class="cb-left-panel">

        {{-- Decorative arcs --}}
        <div class="cb-arc-top" aria-hidden="true"></div>
        <div class="cb-arc-bottom" aria-hidden="true"></div>

        {{-- Dot-grid texture --}}
        <div class="cb-dot-grid" aria-hidden="true"></div>

        <div class="cb-panel-content">

            <div class="cb-logo-area">
                <div class="cb-logo-pill">
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

    {{-- Right panel: form on overlay --}}
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
                    <div class="cb-forgot-wrap">
                        <a href="{{ route('password.request') }}" class="cb-forgot-link">Forgot password?</a>
                    </div>
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
                <a href="#" class="cb-meta-link" aria-label="Privacy policy">Privacy</a>
                <span class="cb-meta-sep"></span>
                <a href="#" class="cb-meta-link" aria-label="Terms of service">Terms</a>
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

/* ── Outer wrapper: full-page background image ── */
.cb-login-wrap {
    display: flex; min-height: 100vh; position: relative;
    background-color: #0f172a;
    background-size: cover; background-position: center; background-repeat: no-repeat;
}
.cb-login-wrap::before {
    content: ''; position: absolute; inset: 0;
    background: rgba(0,0,0,.32);
    pointer-events: none;
}
.cb-login-wrap > * { position: relative; z-index: 1; }

/* ── Left panel: no white overlay, photo fully visible ── */
.cb-left-panel {
    display: none;
    width: 52%;
    position: relative;
    overflow: hidden;
    flex-direction: column;
    background: transparent;
}
@media (min-width: 1024px) {
    .cb-left-panel  { display: flex !important; }
    .cb-mobile-logo { display: none !important; }
}

/* Decorative arc — top-right (subtle over photo) */
.cb-arc-top {
    position: absolute;
    top: -160px; right: -160px;
    width: 420px; height: 420px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(218,165,32,0.08) 0%, transparent 60%);
    pointer-events: none;
}
/* Decorative arc — bottom-left */
.cb-arc-bottom {
    position: absolute;
    bottom: -140px; left: -140px;
    width: 360px; height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(218,165,32,0.06) 0%, transparent 65%);
    pointer-events: none;
}
/* Dot-grid texture (very subtle over photo) */
.cb-dot-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: .4;
    pointer-events: none;
}

/* Content */
.cb-panel-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.cb-logo-area      { position: relative; z-index: 10; padding: 2.5rem 3rem; flex-shrink: 0; }
.cb-logo-pill      { display: inline-block; padding: .75rem 1.25rem; border-radius: 9999px;
                     background: rgba(255,255,255,.22); box-shadow: 0 0 24px rgba(255,255,255,.25), 0 2px 8px rgba(0,0,0,.3); }
.cb-logo-img       { height: 52px; width: auto; display: block; filter: drop-shadow(0 1px 2px rgba(0,0,0,.5)); }
.cb-logo-badge     { margin-top: .9rem; display: inline-flex; align-items: center; gap: 6px;
                     background: rgba(0,0,0,.35); border: 1px solid rgba(255,255,255,.4);
                     border-radius: 99px; padding: 3px 10px; }
.cb-badge-dot      { width: 6px; height: 6px; border-radius: 50%; background: #DAA520;
                     display: inline-block; flex-shrink: 0; }
.cb-badge-text     { font-size: .7rem; font-weight: 700; letter-spacing: .14em;
                     text-transform: uppercase; color: #fff;
                     text-shadow: 0 1px 3px rgba(0,0,0,.9), 0 0 8px rgba(0,0,0,.7); }

.cb-brand-content  { position: relative; z-index: 10; flex: 1; display: flex;
                     flex-direction: column; justify-content: center; padding: 0 3rem 2rem; }
.cb-accent-line    { display: flex; align-items: center; gap: 10px; margin-bottom: 1.6rem; }
.cb-accent-bar     { height: 3px; width: 40px; border-radius: 99px;
                     background: linear-gradient(90deg,#DAA520,#f0c040); }
.cb-accent-fade    { height: 1px; flex: 1;
                     background: linear-gradient(90deg,rgba(218,165,32,.5),transparent); }

.cb-heading        { font-size: 2.4rem; font-weight: 800; line-height: 1.18; color: #fff;
                     margin: 0 0 .9rem; letter-spacing: -.025em;
                     font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
                     text-shadow: 0 1px 4px rgba(0,0,0,.9), 0 0 12px rgba(0,0,0,.8), 0 0 1px #000; }
.cb-heading-gold   { font-weight: 700;
                     background: linear-gradient(135deg,#f0c040,#DAA520,#f0c040);
                     -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                     background-clip: text;
                     filter: drop-shadow(0 0 1px #fff) drop-shadow(0 0 2px rgba(255,255,255,.6)) drop-shadow(0 1px 2px rgba(0,0,0,.4)); }
.cb-subheading     { color: #fff; font-size: 1rem; font-weight: 600; line-height: 1.75;
                     max-width: 340px; margin: 0 0 2.2rem;
                     text-shadow: 0 1px 3px rgba(0,0,0,.9), 0 0 8px rgba(0,0,0,.7); }

.cb-features       { display: flex; flex-direction: column; gap: .65rem; }
.cb-feature-row    { display: flex; align-items: center; gap: .75rem; }
.cb-feature-icon   { flex-shrink: 0; width: 34px; height: 34px; border-radius: 9px;
                     display: flex; align-items: center; justify-content: center; font-size: .9rem;
                     background: rgba(0,0,0,.35); border: 1px solid rgba(255,255,255,.3); }
.cb-feature-text   { color: #fff; font-size: .95rem; font-weight: 600;
                     text-shadow: 0 1px 4px rgba(0,0,0,.95), 0 0 12px rgba(0,0,0,.6), 0 2px 2px rgba(0,0,0,.5); }

.cb-left-footer    { position: relative; z-index: 10; flex-shrink: 0; padding: 1.1rem 3rem;
                     border-top: 1px solid rgba(255,255,255,.25);
                     display: flex; align-items: center; justify-content: space-between;
                     background: transparent; }
.cb-footer-copy    { color: #fff; font-size: .8rem; font-weight: 600; margin: 0;
                     text-shadow: 0 1px 3px rgba(0,0,0,.9), 0 0 6px rgba(0,0,0,.7); }
.cb-online         { display: flex; align-items: center; gap: .4rem; }
.cb-online-dot     { display: inline-block; width: 7px; height: 7px;
                     border-radius: 50%; background: #22c55e; }
.cb-online-text    { color: #fff; font-size: .8rem; font-weight: 600;
                     text-shadow: 0 1px 3px rgba(0,0,0,.9), 0 0 6px rgba(0,0,0,.7); }

/* ── Right panel: transparent over full-page image ── */
.cb-right-panel    { flex: 1; display: flex; flex-direction: column;
                     align-items: center; justify-content: center;
                     position: relative;
                     overflow-y: auto; padding: 3rem 1.5rem; }
.cb-right-panel > * { position: relative; z-index: 1; }

.cb-mobile-logo    { margin-bottom: 2rem; text-align: center; }
.cb-mobile-logo-img { height: 44px; width: auto; margin: 0 auto; }

.cb-form-wrap      { width: 100%; max-width: 360px; }

.cb-form-header    { margin-bottom: 1.75rem; }
.cb-portal-badge   { display: inline-flex; align-items: center; gap: .4rem;
                     border-radius: 99px; padding: .35rem .9rem;
                     font-size: .8rem; font-weight: 700; letter-spacing: .05em;
                     background: rgba(255,255,255,.2); color: #fff;
                     border: 1px solid rgba(255,255,255,.4); margin-bottom: .9rem; }
.cb-welcome        { font-size: 1.75rem; font-weight: 800; color: #fff;
                     margin: 0 0 .4rem; letter-spacing: -.025em;
                     font-family: 'Century Gothic','Gill Sans',Arial,sans-serif;
                     text-shadow: 0 1px 4px rgba(0,0,0,.5), 0 0 12px rgba(0,0,0,.3); }
.cb-welcome-sub    { font-size: .95rem; font-weight: 600; color: #fff; margin: 0; line-height: 1.5;
                     text-shadow: 0 1px 3px rgba(0,0,0,.5); }

.cb-card           { border-radius: 16px; background: #fff;
                     box-shadow: 0 4px 6px rgba(0,0,0,.08), 0 20px 50px rgba(0,0,0,.25);
                     border: 1px solid rgba(255,255,255,.4);
                     border-top: 3px solid #DAA520; overflow: hidden; }
.cb-card-inner     { padding: 1.75rem; }
.cb-form-wrap label, .cb-card label, .cb-card .fi-fo-field-wrp-label { font-weight: 600 !important; font-size: .9375rem !important; color: #1f2937 !important; }
.cb-form-wrap input::placeholder { font-weight: 500; }
.cb-form-wrap button[type="submit"] { font-weight: 700 !important;
    background: linear-gradient(180deg, #e8b923 0%, #c99510 50%, #b8860b 100%) !important;
    box-shadow: 0 2px 8px rgba(184,134,11,.4), inset 0 1px 0 rgba(255,255,255,.25);
    transition: transform .15s ease, box-shadow .15s ease; }
.cb-form-wrap button[type="submit"]:hover { transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(184,134,11,.45), inset 0 1px 0 rgba(255,255,255,.3); }
.cb-form-wrap button[type="submit"]:active { transform: translateY(0); }
.cb-form-wrap [type="password"] + div button, .cb-form-wrap [x-ref="togglePassword"] { color: #1f2937 !important; opacity: 1; }
.cb-card .fi-fo-field-wrp-error-message { margin-top: .35rem; font-size: .875rem; font-weight: 600; color: #dc2626; }
.cb-card [data-validation-error] input, .cb-card .fi-input-error input, .cb-card input:invalid.ring-danger-500 { border-color: #dc2626 !important; box-shadow: 0 0 0 2px rgba(220,38,38,.25); }
.cb-forgot-wrap    { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.cb-forgot-link    { font-size: .9rem; font-weight: 600; color: #b8860b; text-decoration: none; }
.cb-forgot-link:hover { color: #92670a; text-decoration: underline; }
.cb-meta-link      { color: #fff; font-weight: 600; text-decoration: none; text-shadow: 0 1px 2px rgba(0,0,0,.4); }
.cb-meta-link:hover { text-decoration: underline; }
.cb-form-wrap button[type="submit"]:disabled { opacity: .85; cursor: wait; }
.cb-form-wrap button[type="submit"] .fi-btn-loading-icon { margin-right: .5rem; }

.cb-meta           { margin-top: 1.75rem; display: flex; align-items: center;
                     justify-content: center; gap: .75rem; font-size: .8rem; font-weight: 600; color: #fff;
                     text-shadow: 0 1px 2px rgba(0,0,0,.4); }
.cb-meta-item      { display: flex; align-items: center; gap: .3rem; }
.cb-meta-sep       { width: 1px; height: 12px; background: rgba(255,255,255,.5); display: inline-block; }
.cb-mobile-copy    { margin-top: 1.25rem; text-align: center;
                     font-size: .8rem; font-weight: 600; color: #fff; display: none;
                     text-shadow: 0 1px 2px rgba(0,0,0,.4); }
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
</div>
