<div class="cb-login-wrap">

    {{-- LEFT PANEL — Nairobi property skyline --}}
    <div class="cb-left-panel">

        {{-- Full-bleed skyline illustration --}}
        <div class="cb-skyline-bg" aria-hidden="true">

            {{-- Sky gradient + atmospheric haze --}}
            <svg class="cb-sky-svg" viewBox="0 0 800 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#0f172a"/>
                        <stop offset="35%"  stop-color="#1e3a5f"/>
                        <stop offset="65%"  stop-color="#b45309"/>
                        <stop offset="82%"  stop-color="#d97706"/>
                        <stop offset="100%" stop-color="#f59e0b"/>
                    </linearGradient>
                    <linearGradient id="glow" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"  stop-color="#f59e0b" stop-opacity="0.6"/>
                        <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="bldA" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#1e3a5f"/>
                        <stop offset="100%" stop-color="#0f1f38"/>
                    </linearGradient>
                    <linearGradient id="bldB" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#243d5c"/>
                        <stop offset="100%" stop-color="#0d1b30"/>
                    </linearGradient>
                    <linearGradient id="bldC" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#162d4a"/>
                        <stop offset="100%" stop-color="#0a1525"/>
                    </linearGradient>
                    <linearGradient id="ground" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#0a1525"/>
                        <stop offset="100%" stop-color="#060e1a"/>
                    </linearGradient>
                    <radialGradient id="sunburst" cx="50%" cy="72%" r="40%">
                        <stop offset="0%"  stop-color="#fbbf24" stop-opacity="0.35"/>
                        <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                    </radialGradient>
                    <filter id="blur2">
                        <feGaussianBlur stdDeviation="2"/>
                    </filter>
                    <filter id="blur4">
                        <feGaussianBlur stdDeviation="4"/>
                    </filter>
                    <filter id="glow-filter">
                        <feGaussianBlur stdDeviation="3" result="blur"/>
                        <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                    </filter>
                    <clipPath id="clip-main">
                        <rect width="800" height="900"/>
                    </clipPath>
                </defs>

                <g clip-path="url(#clip-main)">

                {{-- Sky --}}
                <rect width="800" height="900" fill="url(#sky)"/>

                {{-- Sunburst halo --}}
                <ellipse cx="400" cy="648" rx="320" ry="180" fill="url(#sunburst)"/>

                {{-- Stars (upper sky) --}}
                <g fill="white" opacity="0.6">
                    <circle cx="60"  cy="40"  r="1.2"/><circle cx="140" cy="18"  r="0.9"/>
                    <circle cx="210" cy="55"  r="1.0"/><circle cx="320" cy="22"  r="1.3"/>
                    <circle cx="390" cy="45"  r="0.8"/><circle cx="480" cy="12"  r="1.1"/>
                    <circle cx="560" cy="38"  r="0.9"/><circle cx="640" cy="20"  r="1.4"/>
                    <circle cx="720" cy="50"  r="0.8"/><circle cx="750" cy="90"  r="1.0"/>
                    <circle cx="90"  cy="90"  r="0.7"/><circle cx="170" cy="105" r="1.1"/>
                    <circle cx="270" cy="80"  r="0.8"/><circle cx="430" cy="95"  r="1.0"/>
                    <circle cx="530" cy="70"  r="1.2"/><circle cx="690" cy="80"  r="0.9"/>
                    <circle cx="780" cy="115" r="1.1"/><circle cx="35"  cy="130" r="0.8"/>
                    <circle cx="360" cy="115" r="0.7"/><circle cx="600" cy="100" r="1.0"/>
                </g>

                {{-- ═══ BACKGROUND BUILDINGS (far, silhouetted) ═══ --}}

                {{-- Upperhill towers far background --}}
                <rect x="50"  y="500" width="45" height="200" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="60"  y="470" width="28" height="30"  fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="110" y="520" width="38" height="180" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="160" y="490" width="55" height="210" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="170" y="465" width="35" height="25"  fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="230" y="510" width="42" height="190" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="620" y="505" width="40" height="195" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="670" y="475" width="52" height="225" fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="680" y="450" width="32" height="25"  fill="url(#bldC)" filter="url(#blur2)"/>
                <rect x="730" y="510" width="44" height="190" fill="url(#bldC)" filter="url(#blur2)"/>

                {{-- ═══ MID-GROUND BUILDINGS ═══ --}}

                {{-- KICC-inspired cylinder tower (centre-left) --}}
                <rect   x="288" y="360" width="70" height="340" fill="url(#bldA)"/>
                <ellipse cx="323" cy="360" rx="35" ry="14"     fill="#1a3050"/>
                <rect   x="308" y="340" width="30" height="22"  fill="#1a3050"/>
                <rect   x="316" y="318" width="14" height="30"  fill="#b8960a"/>
                <rect   x="319" y="300" width="8"  height="22"  fill="#DAA520"/>
                {{-- KICC windows --}}
                <g fill="#f59e0b" opacity="0.5">
                    <rect x="298" y="380" width="8" height="5" rx="1"/>
                    <rect x="312" y="380" width="8" height="5" rx="1"/>
                    <rect x="326" y="380" width="8" height="5" rx="1"/>
                    <rect x="340" y="380" width="8" height="5" rx="1"/>
                    <rect x="298" y="395" width="8" height="5" rx="1"/>
                    <rect x="312" y="395" width="8" height="5" rx="1"/>
                    <rect x="340" y="395" width="8" height="5" rx="1"/>
                    <rect x="298" y="410" width="8" height="5" rx="1"/>
                    <rect x="326" y="410" width="8" height="5" rx="1"/>
                    <rect x="340" y="410" width="8" height="5" rx="1"/>
                    <rect x="298" y="425" width="8" height="5" rx="1"/>
                    <rect x="312" y="425" width="8" height="5" rx="1"/>
                    <rect x="326" y="425" width="8" height="5" rx="1"/>
                    <rect x="298" y="440" width="8" height="5" rx="1"/>
                    <rect x="340" y="440" width="8" height="5" rx="1"/>
                    <rect x="312" y="455" width="8" height="5" rx="1"/>
                    <rect x="326" y="455" width="8" height="5" rx="1"/>
                </g>

                {{-- Tall modernist slab (left of centre) --}}
                <rect x="180" y="390" width="88" height="310" fill="url(#bldB)"/>
                <rect x="180" y="370" width="88" height="22"  fill="#1d3557"/>
                <rect x="206" y="355" width="36" height="18"  fill="#1d3557"/>
                {{-- Windows --}}
                <g fill="#fbbf24" opacity="0.45">
                    <rect x="192" y="400" width="10" height="14" rx="1"/>
                    <rect x="208" y="400" width="10" height="14" rx="1"/>
                    <rect x="224" y="400" width="10" height="14" rx="1"/>
                    <rect x="240" y="400" width="10" height="14" rx="1"/>
                    <rect x="192" y="422" width="10" height="14" rx="1"/>
                    <rect x="224" y="422" width="10" height="14" rx="1"/>
                    <rect x="240" y="422" width="10" height="14" rx="1"/>
                    <rect x="208" y="444" width="10" height="14" rx="1"/>
                    <rect x="224" y="444" width="10" height="14" rx="1"/>
                    <rect x="192" y="466" width="10" height="14" rx="1"/>
                    <rect x="240" y="466" width="10" height="14" rx="1"/>
                    <rect x="208" y="488" width="10" height="14" rx="1"/>
                    <rect x="224" y="488" width="10" height="14" rx="1"/>
                    <rect x="192" y="510" width="10" height="14" rx="1"/>
                    <rect x="240" y="510" width="10" height="14" rx="1"/>
                    <rect x="208" y="532" width="10" height="14" rx="1"/>
                    <rect x="224" y="532" width="10" height="14" rx="1"/>
                </g>

                {{-- Iconic glass curtain-wall tower (right of centre) --}}
                <rect x="440" y="330" width="100" height="370" fill="url(#bldA)"/>
                <rect x="440" y="310" width="100" height="22"  fill="#162d48"/>
                <rect x="462" y="290" width="56" height="22"   fill="#162d48"/>
                <rect x="480" y="272" width="20" height="22"   fill="#DAA520"/>
                <rect x="487" y="250" width="6"  height="26"   fill="#b8960a"/>
                {{-- Glass facade stripes --}}
                <g fill="none" stroke="#1e4a7a" stroke-width="1.5" opacity="0.6">
                    <line x1="460" y1="330" x2="460" y2="700"/>
                    <line x1="480" y1="330" x2="480" y2="700"/>
                    <line x1="500" y1="330" x2="500" y2="700"/>
                    <line x1="520" y1="330" x2="520" y2="700"/>
                    <line x1="440" y1="360" x2="540" y2="360"/>
                    <line x1="440" y1="385" x2="540" y2="385"/>
                    <line x1="440" y1="410" x2="540" y2="410"/>
                    <line x1="440" y1="435" x2="540" y2="435"/>
                    <line x1="440" y1="460" x2="540" y2="460"/>
                    <line x1="440" y1="485" x2="540" y2="485"/>
                    <line x1="440" y1="510" x2="540" y2="510"/>
                    <line x1="440" y1="535" x2="540" y2="535"/>
                    <line x1="440" y1="560" x2="540" y2="560"/>
                    <line x1="440" y1="585" x2="540" y2="585"/>
                    <line x1="440" y1="610" x2="540" y2="610"/>
                    <line x1="440" y1="635" x2="540" y2="635"/>
                    <line x1="440" y1="660" x2="540" y2="660"/>
                </g>
                {{-- Lit windows on glass tower --}}
                <g fill="#fbbf24" opacity="0.4">
                    <rect x="445" y="340" width="12" height="16" rx="1"/>
                    <rect x="463" y="340" width="12" height="16" rx="1"/>
                    <rect x="499" y="340" width="12" height="16" rx="1"/>
                    <rect x="521" y="340" width="12" height="16" rx="1"/>
                    <rect x="445" y="366" width="12" height="16" rx="1"/>
                    <rect x="481" y="366" width="12" height="16" rx="1"/>
                    <rect x="521" y="366" width="12" height="16" rx="1"/>
                    <rect x="463" y="392" width="12" height="16" rx="1"/>
                    <rect x="499" y="392" width="12" height="16" rx="1"/>
                    <rect x="445" y="418" width="12" height="16" rx="1"/>
                    <rect x="521" y="418" width="12" height="16" rx="1"/>
                    <rect x="463" y="444" width="12" height="16" rx="1"/>
                    <rect x="481" y="444" width="12" height="16" rx="1"/>
                    <rect x="445" y="470" width="12" height="16" rx="1"/>
                    <rect x="499" y="470" width="12" height="16" rx="1"/>
                    <rect x="521" y="496" width="12" height="16" rx="1"/>
                    <rect x="463" y="522" width="12" height="16" rx="1"/>
                </g>

                {{-- Stepped residential block (far left) --}}
                <rect x="0"   y="560" width="75"  height="240" fill="url(#bldB)"/>
                <rect x="0"   y="520" width="55"  height="42"  fill="url(#bldB)"/>
                <rect x="0"   y="490" width="38"  height="32"  fill="url(#bldC)"/>
                <g fill="#f59e0b" opacity="0.35">
                    <rect x="8"  y="570" width="9" height="12" rx="1"/>
                    <rect x="22" y="570" width="9" height="12" rx="1"/>
                    <rect x="38" y="570" width="9" height="12" rx="1"/>
                    <rect x="55" y="570" width="9" height="12" rx="1"/>
                    <rect x="8"  y="594" width="9" height="12" rx="1"/>
                    <rect x="38" y="594" width="9" height="12" rx="1"/>
                    <rect x="55" y="594" width="9" height="12" rx="1"/>
                    <rect x="22" y="618" width="9" height="12" rx="1"/>
                    <rect x="55" y="618" width="9" height="12" rx="1"/>
                    <rect x="8"  y="642" width="9" height="12" rx="1"/>
                    <rect x="38" y="642" width="9" height="12" rx="1"/>
                </g>

                {{-- Mixed-use podium + tower (right) --}}
                <rect x="590" y="440" width="95" height="260" fill="url(#bldA)"/>
                <rect x="575" y="560" width="125" height="140" fill="url(#bldB)"/>
                <rect x="600" y="420" width="72" height="22"  fill="#162d48"/>
                <rect x="618" y="398" width="36" height="24"  fill="#1a3050"/>
                <rect x="629" y="380" width="14" height="20"  fill="#DAA520"/>
                <g fill="#fbbf24" opacity="0.4">
                    <rect x="600" y="450" width="11" height="15" rx="1"/>
                    <rect x="617" y="450" width="11" height="15" rx="1"/>
                    <rect x="634" y="450" width="11" height="15" rx="1"/>
                    <rect x="651" y="450" width="11" height="15" rx="1"/>
                    <rect x="668" y="450" width="11" height="15" rx="1"/>
                    <rect x="600" y="474" width="11" height="15" rx="1"/>
                    <rect x="634" y="474" width="11" height="15" rx="1"/>
                    <rect x="668" y="474" width="11" height="15" rx="1"/>
                    <rect x="617" y="498" width="11" height="15" rx="1"/>
                    <rect x="651" y="498" width="11" height="15" rx="1"/>
                    <rect x="600" y="522" width="11" height="15" rx="1"/>
                    <rect x="634" y="522" width="11" height="15" rx="1"/>
                    <rect x="668" y="522" width="11" height="15" rx="1"/>
                    {{-- podium windows --}}
                    <rect x="582" y="572" width="14" height="18" rx="1"/>
                    <rect x="602" y="572" width="14" height="18" rx="1"/>
                    <rect x="640" y="572" width="14" height="18" rx="1"/>
                    <rect x="676" y="572" width="14" height="18" rx="1"/>
                    <rect x="582" y="600" width="14" height="18" rx="1"/>
                    <rect x="658" y="600" width="14" height="18" rx="1"/>
                    <rect x="620" y="628" width="14" height="18" rx="1"/>
                    <rect x="676" y="628" width="14" height="18" rx="1"/>
                </g>

                {{-- Narrow apartment tower (far right) --}}
                <rect x="730" y="490" width="70" height="310" fill="url(#bldB)"/>
                <rect x="740" y="470" width="52" height="22"  fill="#1d3557"/>
                <rect x="752" y="450" width="28" height="22"  fill="#1a3050"/>
                <rect x="762" y="432" width="8"  height="20"  fill="#b8960a"/>
                <g fill="#fbbf24" opacity="0.38">
                    <rect x="738" y="500" width="10" height="13" rx="1"/>
                    <rect x="754" y="500" width="10" height="13" rx="1"/>
                    <rect x="770" y="500" width="10" height="13" rx="1"/>
                    <rect x="738" y="522" width="10" height="13" rx="1"/>
                    <rect x="770" y="522" width="10" height="13" rx="1"/>
                    <rect x="754" y="544" width="10" height="13" rx="1"/>
                    <rect x="738" y="566" width="10" height="13" rx="1"/>
                    <rect x="770" y="566" width="10" height="13" rx="1"/>
                    <rect x="754" y="588" width="10" height="13" rx="1"/>
                    <rect x="738" y="610" width="10" height="13" rx="1"/>
                    <rect x="770" y="610" width="10" height="13" rx="1"/>
                </g>

                {{-- Low-rise retail strip (ground level) --}}
                <rect x="0"   y="700" width="800" height="200" fill="url(#ground)"/>
                <rect x="75"  y="680" width="100" height="120" fill="#0d1b2e"/>
                <rect x="185" y="672" width="80"  height="128" fill="#0e1c30"/>
                <rect x="375" y="675" width="60"  height="125" fill="#0d1b2e"/>
                <rect x="550" y="678" width="90"  height="122" fill="#0e1c30"/>
                <rect x="650" y="685" width="70"  height="115" fill="#0d1b2e"/>

                {{-- Street-level shop lights --}}
                <g fill="#fbbf24" opacity="0.55">
                    <rect x="82"  y="688" width="16" height="10" rx="2"/>
                    <rect x="106" y="688" width="16" height="10" rx="2"/>
                    <rect x="130" y="688" width="16" height="10" rx="2"/>
                    <rect x="192" y="680" width="16" height="10" rx="2"/>
                    <rect x="215" y="680" width="16" height="10" rx="2"/>
                    <rect x="380" y="683" width="14" height="10" rx="2"/>
                    <rect x="400" y="683" width="14" height="10" rx="2"/>
                    <rect x="558" y="686" width="14" height="10" rx="2"/>
                    <rect x="580" y="686" width="14" height="10" rx="2"/>
                    <rect x="610" y="686" width="14" height="10" rx="2"/>
                    <rect x="657" y="692" width="14" height="10" rx="2"/>
                    <rect x="678" y="692" width="14" height="10" rx="2"/>
                </g>

                {{-- Street lamp poles --}}
                <g fill="#DAA520" opacity="0.7">
                    <rect x="138" y="668" width="3" height="36"/>
                    <rect x="133" y="668" width="13" height="3" rx="1"/>
                    <rect x="310" y="668" width="3" height="36"/>
                    <rect x="305" y="668" width="13" height="3" rx="1"/>
                    <rect x="490" y="668" width="3" height="36"/>
                    <rect x="485" y="668" width="13" height="3" rx="1"/>
                    <rect x="700" y="668" width="3" height="36"/>
                    <rect x="695" y="668" width="13" height="3" rx="1"/>
                </g>
                <g fill="#fef3c7" opacity="0.8">
                    <circle cx="140" cy="668" r="4" filter="url(#blur2)"/>
                    <circle cx="312" cy="668" r="4" filter="url(#blur2)"/>
                    <circle cx="492" cy="668" r="4" filter="url(#blur2)"/>
                    <circle cx="702" cy="668" r="4" filter="url(#blur2)"/>
                </g>

                {{-- Road reflections --}}
                <rect x="0" y="750" width="800" height="150" fill="#060e1a" opacity="0.9"/>
                <g fill="#fbbf24" opacity="0.12">
                    <rect x="0" y="760" width="800" height="2"/>
                </g>
                {{-- Road markings --}}
                <g fill="#fef3c7" opacity="0.15">
                    <rect x="120" y="780" width="40" height="4" rx="2"/>
                    <rect x="220" y="780" width="40" height="4" rx="2"/>
                    <rect x="320" y="780" width="40" height="4" rx="2"/>
                    <rect x="420" y="780" width="40" height="4" rx="2"/>
                    <rect x="520" y="780" width="40" height="4" rx="2"/>
                    <rect x="620" y="780" width="40" height="4" rx="2"/>
                </g>

                {{-- Sunset horizon glow bar --}}
                <rect x="0" y="640" width="800" height="30" fill="#f59e0b" opacity="0.18" filter="url(#blur4)"/>
                <rect x="0" y="645" width="800" height="12" fill="#fbbf24" opacity="0.22" filter="url(#blur2)"/>

                </g>
            </svg>

            {{-- Colour overlay for readability --}}
            <div class="cb-skyline-overlay"></div>
        </div>

        {{-- Floating content over skyline --}}
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
                    Nairobi's Properties.<br>
                    <span class="cb-heading-gold">Managed Brilliantly.</span>
                </h1>
                <p class="cb-subheading">
                    End-to-end lease management for Chabrin Agencies — from Westlands to Upperhill, every unit, every tenant, every workflow in one platform.
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

    {{-- RIGHT PANEL --}}
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
.cb-login-wrap { display: flex; min-height: 100vh; background: #fff; }

/* ── Left panel ── */
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

/* Skyline fills 100% of left panel */
.cb-skyline-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.cb-sky-svg {
    width: 100%;
    height: 100%;
    display: block;
}
/* Dark gradient overlay — stronger at top/bottom, lighter in middle for readability */
.cb-skyline-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(10,21,37,0.55) 0%,
        rgba(10,21,37,0.15) 40%,
        rgba(10,21,37,0.10) 60%,
        rgba(10,21,37,0.72) 100%
    );
}

/* Content floats above the skyline */
.cb-panel-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.cb-logo-area      { position: relative; z-index: 10; padding: 2.5rem 3rem; flex-shrink: 0; }
.cb-logo-img       { height: 52px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5)); }
.cb-logo-badge     { margin-top: .9rem; display: inline-flex; align-items: center; gap: 6px;
                     background: rgba(218,165,32,.2); border: 1px solid rgba(218,165,32,.45);
                     border-radius: 99px; padding: 3px 10px; backdrop-filter: blur(4px); }
.cb-badge-dot      { width: 6px; height: 6px; border-radius: 50%; background: #DAA520;
                     display: inline-block; flex-shrink: 0; }
.cb-badge-text     { font-size: .6rem; font-weight: 700; letter-spacing: .14em;
                     text-transform: uppercase; color: #fde68a; }

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
                     text-shadow: 0 2px 12px rgba(0,0,0,0.6); }
.cb-heading-gold   { background: linear-gradient(135deg,#f0c040,#DAA520,#fde68a);
                     -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                     background-clip: text; }
.cb-subheading     { color: rgba(255,255,255,0.82); font-size: .94rem; line-height: 1.75;
                     max-width: 340px; margin: 0 0 2.2rem;
                     text-shadow: 0 1px 4px rgba(0,0,0,0.5); }

.cb-features       { display: flex; flex-direction: column; gap: .65rem; }
.cb-feature-row    { display: flex; align-items: center; gap: .75rem; }
.cb-feature-icon   { flex-shrink: 0; width: 34px; height: 34px; border-radius: 9px;
                     display: flex; align-items: center; justify-content: center; font-size: .9rem;
                     background: rgba(255,255,255,0.12); border: 1px solid rgba(218,165,32,.4);
                     box-shadow: 0 2px 8px rgba(0,0,0,0.2); backdrop-filter: blur(4px); }
.cb-feature-text   { color: rgba(255,255,255,0.88); font-size: .87rem; font-weight: 500;
                     text-shadow: 0 1px 3px rgba(0,0,0,0.4); }

.cb-left-footer    { position: relative; z-index: 10; flex-shrink: 0; padding: 1.1rem 3rem;
                     border-top: 1px solid rgba(218,165,32,.25);
                     display: flex; align-items: center; justify-content: space-between;
                     background: rgba(10,21,37,0.45); backdrop-filter: blur(6px); }
.cb-footer-copy    { color: rgba(255,255,255,0.5); font-size: .72rem; margin: 0; }
.cb-online         { display: flex; align-items: center; gap: .4rem; }
.cb-online-dot     { display: inline-block; width: 7px; height: 7px;
                     border-radius: 50%; background: #22c55e; }
.cb-online-text    { color: rgba(255,255,255,0.6); font-size: .72rem; }

/* ── Right panel ── */
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
