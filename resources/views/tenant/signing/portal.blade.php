<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Lease - {{ $lease->reference_number }}</title>
    <script nonce="{{ $cspNonce }}" src="https://cdn.tailwindcss.com"></script>
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-canvas {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            touch-action: none;
        }
        .step-indicator { transition: all 0.3s ease; }
        .step-active    { background-color: #3b82f6; color: white; }
        .step-completed { background-color: #10b981; color: white; }
        .step-pending   { background-color: #e5e7eb; color: #6b7280; }

        /* ID upload drop zone */
        .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }
        .drop-zone.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .drop-zone.has-file {
            border-color: #10b981;
            background: #f0fdf4;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Digital Lease Signing</h1>
                    <p class="text-sm text-gray-600 mt-1">Reference: {{ $lease->reference_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-700">{{ $tenant->names }}</p>
                    <p class="text-xs text-gray-500">{{ $tenant->mobile_number }}</p>
                </div>
            </div>
        </div>

        <!-- Step Indicators (4 steps) -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 text-center">
                    <div id="step1-indicator" class="step-indicator step-active w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold text-sm">1</div>
                    <p class="text-xs mt-2 font-medium">Verify Identity</p>
                </div>
                <div class="flex-1 border-t-2 border-gray-300 mx-2" style="margin-top:-20px;"></div>
                <div class="flex-1 text-center">
                    <div id="step2-indicator" class="step-indicator step-pending w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold text-sm">2</div>
                    <p class="text-xs mt-2 font-medium">Review Lease</p>
                </div>
                <div class="flex-1 border-t-2 border-gray-300 mx-2" style="margin-top:-20px;"></div>
                <div class="flex-1 text-center">
                    <div id="step3-indicator" class="step-indicator step-pending w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold text-sm">3</div>
                    <p class="text-xs mt-2 font-medium">Sign Lease</p>
                </div>
                <div class="flex-1 border-t-2 border-gray-300 mx-2" style="margin-top:-20px;"></div>
                <div class="flex-1 text-center">
                    <div id="step4-indicator" class="step-indicator step-pending w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold text-sm">4</div>
                    <p class="text-xs mt-2 font-medium">Upload ID Copy</p>
                </div>
            </div>
        </div>

        <!-- ── STEP 1: OTP Verification ── -->
        <div id="step1-content" class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 1: Verify Your Identity</h2>
            <p class="text-gray-600 mb-6">We'll send a verification code to your registered phone number ending in <strong>{{ substr($tenant->mobile_number, -4) }}</strong></p>

            <div id="otp-request-section">
                <button id="request-otp-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition">
                    Send Verification Code
                </button>
            </div>

            <div id="otp-verify-section" class="hidden">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enter 6-Digit Code</label>
                    <input type="text" id="otp-code" maxlength="6" pattern="[0-9]{6}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-2xl tracking-widest"
                           placeholder="000000">
                </div>
                <button id="verify-otp-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition">
                    Verify Code
                </button>
                <button id="resend-otp-btn" class="w-full mt-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-6 rounded-lg transition">
                    Resend Code
                </button>
                <p class="text-sm text-gray-500 text-center mt-3">Code expires in <span id="otp-timer" class="font-semibold">10:00</span></p>
            </div>

            <div id="otp-message" class="hidden mt-4 p-4 rounded-lg"></div>
        </div>

        <!-- ── STEP 2: Review Lease ── -->
        <div id="step2-content" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Step 2: Review Your Lease Agreement</h2>
            <p class="text-gray-600 mb-4">Read the full lease document carefully before signing. If you disagree with any terms, use the <strong>Dispute</strong> option below.</p>

            {{-- Key terms summary --}}
            <div class="border-2 border-blue-100 rounded-lg p-4 mb-4 bg-blue-50">
                <h3 class="font-semibold text-blue-900 mb-3 text-sm uppercase tracking-wide">Key Lease Terms</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-500">Property</p>
                        <p class="font-semibold">{{ $lease->property?->property_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Unit</p>
                        <p class="font-semibold">{{ $lease->unit?->unit_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Monthly Rent</p>
                        <p class="font-semibold text-green-700">KES {{ number_format($lease->monthly_rent, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Security Deposit</p>
                        <p class="font-semibold">KES {{ number_format($lease->deposit_amount ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Start Date</p>
                        <p class="font-semibold">{{ $lease->start_date ? $lease->start_date->format('d M Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">End Date</p>
                        <p class="font-semibold">{{ $lease->end_date ? $lease->end_date->format('d M Y') : 'Periodic' }}</p>
                    </div>
                </div>
            </div>

            {{-- Full lease PDF embedded --}}
            <div class="mb-1">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium text-gray-700">Full Lease Document</p>
                    <a href="{{ route('tenant.view-lease', ['lease' => $lease->id]) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                       target="_blank"
                       class="text-xs text-blue-600 hover:underline">Open in new tab ↗</a>
                </div>
                <iframe id="lease-pdf-frame"
                        src="{{ route('tenant.view-lease', ['lease' => $lease->id]) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                        class="w-full border-2 border-gray-300 rounded-lg"
                        style="height: 600px;"
                        onload="onPdfLoaded()">
                </iframe>
                <p id="pdf-scroll-notice" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 mt-2">
                    ⚠️ Please scroll through the entire lease document above before confirming.
                </p>
            </div>

            {{-- Read confirmation checkbox --}}
            <div class="flex items-start mb-4 mt-4">
                <input type="checkbox" id="agree-terms" class="mt-1 mr-3 h-5 w-5 text-blue-600 cursor-pointer">
                <label for="agree-terms" class="text-sm text-gray-700 cursor-pointer">
                    I <strong>{{ $lease->tenant->names }}</strong> confirm that I have read and fully understood all terms
                    and conditions of this lease agreement (Reference: <strong>{{ $lease->reference_number }}</strong>)
                    and agree to be legally bound by its terms.
                </label>
            </div>

            <button id="proceed-to-sign-btn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition disabled:bg-gray-400 disabled:cursor-not-allowed mb-3"
                    disabled>
                ✅ I Have Read the Lease — Proceed to Sign
            </button>

            {{-- Dispute section --}}
            <div class="border border-red-200 rounded-lg mt-2">
                <button type="button" id="toggle-dispute-btn"
                        class="w-full text-left px-4 py-3 text-sm text-red-700 font-medium flex items-center justify-between hover:bg-red-50 rounded-lg transition">
                    <span>⚠️ I have concerns or disagree with these terms</span>
                    <span id="dispute-chevron">▼</span>
                </button>
                <div id="dispute-form" class="hidden px-4 pb-4">
                    <p class="text-sm text-gray-600 mb-3">Select your reason and provide details. Our team will contact you within 24 hours.</p>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Dispute</label>
                        <select id="dispute-reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
                            <option value="">— Select a reason —</option>
                            <option value="rent_too_high">Rent amount is too high</option>
                            <option value="wrong_dates">Incorrect lease dates</option>
                            <option value="incorrect_details">Incorrect personal or property details</option>
                            <option value="terms_disagreement">I disagree with the lease terms/clauses</option>
                            <option value="not_my_lease">This is not my lease</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Details <span class="text-gray-400">(required)</span></label>
                        <textarea id="dispute-comment" rows="3"
                                  placeholder="Please explain your concern in detail..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400"></textarea>
                    </div>
                    <div id="dispute-message" class="hidden mb-3 p-3 rounded-lg text-sm"></div>
                    <button id="submit-dispute-btn"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                        Submit Dispute
                    </button>
                </div>
            </div>
        </div>

        <!-- ── STEP 3: Digital Signature ── -->
        <div id="step3-content" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 3: Sign the Lease</h2>
            <p class="text-gray-600 mb-4">Please sign in the box below using your mouse or touch screen.</p>

            <div class="mb-4">
                <canvas id="signature-pad" class="signature-canvas w-full" width="700" height="300"></canvas>
            </div>

            <input type="hidden" id="geolocation_data" name="geolocation_data" value="">

            <div class="flex gap-3 mb-6">
                <button id="clear-signature-btn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                    Clear
                </button>
                <button id="undo-signature-btn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                    Undo
                </button>
            </div>

            <!-- In-Person Witness section -->
            <div class="border-t border-gray-200 pt-6 mt-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">In-Person Witness (Lessee)</h3>
                <p class="text-sm text-gray-600 mb-4">
                    The person physically present with you while you sign must enter their details and sign below.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="lessee-witness-name" class="block text-sm font-medium text-gray-700 mb-1">Witness Full Name</label>
                        <input id="lessee-witness-name" type="text"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="Full name of witness">
                    </div>
                    <div>
                        <label for="lessee-witness-id" class="block text-sm font-medium text-gray-700 mb-1">Witness ID / Passport No.</label>
                        <input id="lessee-witness-id" type="text"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="National ID or Passport number">
                    </div>
                </div>

                <div class="mb-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">Witness Signature</p>
                    <canvas id="witness-signature-pad" class="signature-canvas w-full bg-white" width="700" height="200"></canvas>
                    <input type="hidden" id="witness-signature-data" name="witness_signature_data" value="">
                </div>
                <div class="flex gap-3 mb-6">
                    <button id="clear-witness-signature-btn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                        Clear Witness Signature
                    </button>
                </div>

                <!-- Advocate routing -->
                <div class="border-t border-gray-100 pt-4 mt-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">Legal Representation</p>
                    <p class="text-xs text-gray-500 mb-3">
                        Are you using Chabrin's advocate or your own advocate to review this lease?
                    </p>
                    <div class="flex flex-col md:flex-row gap-3 mb-3">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="advocate_selection" value="chabrin_advocate" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Use Chabrin's Advocate</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="advocate_selection" value="own_advocate" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">I have my own Advocate</span>
                        </label>
                    </div>
                    <div id="own-advocate-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="tenant-advocate-name" class="block text-sm font-medium text-gray-700 mb-1">Advocate Full Name</label>
                            <input id="tenant-advocate-name" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                   placeholder="e.g. John Doe, Advocate">
                        </div>
                        <div>
                            <label for="tenant-advocate-email" class="block text-sm font-medium text-gray-700 mb-1">Advocate Email Address</label>
                            <input id="tenant-advocate-email" type="email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                   placeholder="advocate@example.com">
                        </div>
                    </div>
                </div>
            </div>

            <button id="submit-signature-btn"
                    class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    disabled>
                Submit Signature
            </button>

            <div id="signature-message" class="hidden mt-4 p-4 rounded-lg"></div>
        </div>

        <!-- ── STEP 4: Upload ID Copy ── -->
        <div id="step4-content" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Step 4: Upload ID Copy</h2>
            <p class="text-gray-600 mb-1">As required by the lease agreement, please attach a copy of your identification document.</p>
            <p class="text-sm text-gray-500 mb-6">Accepted: National ID (front &amp; back), Passport biodata page. Formats: JPG, PNG, PDF. Max 5MB per file.</p>

            {{-- Upload zone --}}
            <div id="drop-zone" class="drop-zone mb-4" onclick="document.getElementById('id-file-input').click()">
                <input type="file" id="id-file-input" accept=".jpg,.jpeg,.png,.pdf" multiple class="hidden">
                <div id="drop-zone-idle">
                    <svg class="mx-auto mb-3 text-gray-400" style="width:48px;height:48px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <p class="text-gray-600 font-medium">Click to select files or drag &amp; drop here</p>
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG or PDF &bull; Max 5MB each</p>
                </div>
                <div id="drop-zone-files" class="hidden text-left"></div>
            </div>

            <div id="upload-message" class="hidden mb-4 p-4 rounded-lg text-sm"></div>

            <div class="flex gap-3">
                <button id="upload-id-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                        disabled>
                    Upload ID Copy
                </button>
                <button id="skip-id-btn"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-3 px-6 rounded-lg transition">
                    Skip for Now
                </button>
            </div>

            <p class="text-xs text-gray-400 mt-3 text-center">
                You may also provide a physical copy of your ID to the Chabrin Agencies office later.
            </p>
        </div>

        <!-- ── STEP 4: Completion ── -->
        <div id="step4-complete" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <div class="text-center py-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-green-700 mb-2">Lease Signed Successfully!</h2>
                <p class="text-gray-600 mb-1">Reference: <strong>{{ $lease->reference_number }}</strong></p>
                <p class="text-gray-600 mb-6">Your signature has been recorded. Chabrin Agencies will contact you once all parties have signed.</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-600 text-left max-w-sm mx-auto">
                    <p class="font-semibold text-gray-800 mb-2">What happens next:</p>
                    <ul class="space-y-1 list-disc list-inside">
                        <li>Your property manager will countersign the lease</li>
                        <li>You will receive a copy of the fully signed lease by email</li>
                        <li>Chabrin Agencies will contact you regarding deposit and key collection</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 mt-8">
            <p>This is a secure signing session. Your signature is encrypted and time-stamped.</p>
            <p class="mt-2">© {{ date('Y') }} Chabrin Lease Management System</p>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        const leaseId    = {{ $lease->id }};
        const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
        let signaturePad;
        let witnessSignaturePad;
        let otpTimer;
        let otpExpiresAt;
        let userLocation  = null;
        let selectedFiles = [];

        // ── Geolocation (with consent) ──
        // Location data is optional and only collected after explicit tenant consent.
        // Kenya Data Protection Act 2019 requires informed, freely given consent
        // before collecting any personal location data.
        function requestGeolocationWithConsent() {
            if (! navigator.geolocation) return;

            const modal = document.getElementById('geolocation-consent-modal');
            if (modal) modal.classList.remove('hidden');

            document.getElementById('geo-consent-accept').addEventListener('click', function () {
                modal.classList.add('hidden');
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        userLocation = { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
                        const geoField = document.getElementById('geolocation_data');
                        if (geoField) {
                            geoField.value = `${pos.coords.latitude},${pos.coords.longitude}`;
                        }
                    },
                    () => { /* User denied browser permission — proceed without location */ },
                    { timeout: 10000, maximumAge: 300000 }
                );
            }, { once: true });

            document.getElementById('geo-consent-decline').addEventListener('click', function () {
                modal.classList.add('hidden');
                // Proceed without location — signing is never blocked by this
            }, { once: true });
        }

        // ── Boot ──
        document.addEventListener('DOMContentLoaded', function() {
            setupOTPListeners();
            setupStep2Listeners();
            setupDisputeListeners();
            setupIDUploadListeners();
            // Show geolocation consent modal 1.5s after page is interactive
            // (gives the tenant time to read the page before the modal appears)
            setTimeout(requestGeolocationWithConsent, 1500);
        });

        // ═══════════ STEP 1 — OTP ═══════════

        function setupOTPListeners() {
            document.getElementById('request-otp-btn').addEventListener('click', requestOTP);
            document.getElementById('verify-otp-btn').addEventListener('click', verifyOTP);
            document.getElementById('resend-otp-btn').addEventListener('click', requestOTP);

            const otpInput = document.getElementById('otp-code');
            if (otpInput) {
                otpInput.addEventListener('input', () => {
                    // Strip non-digits to guard against pasted content
                    otpInput.value = otpInput.value.replace(/\D/g, '');
                    if (otpInput.value.length === 6) {
                        verifyOTP();
                    }
                });
            }
        }

        async function requestOTP(event) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';
            try {
                const url = new URL(window.location.href);
                const response = await fetch(`/tenant/sign/${leaseId}/request-otp${url.search}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('otp-request-section').classList.add('hidden');
                    document.getElementById('otp-verify-section').classList.remove('hidden');
                    startOTPTimer(data.expires_in_minutes * 60);
                    showMessage('otp-message', 'success', data.message);
                } else {
                    showMessage('otp-message', 'error', data.message, true);
                    btn.disabled = false;
                    btn.textContent = 'Send Verification Code';
                }
            } catch (error) {
                showMessage('otp-message', 'error', 'Network error. Please try again.', true);
                btn.disabled = false;
                btn.textContent = 'Send Verification Code';
            }
        }

        async function verifyOTP() {
            const code = document.getElementById('otp-code').value;
            if (code.length !== 6) {
                showMessage('otp-message', 'error', 'Please enter the 6-digit code sent to your phone.', true);
                return;
            }
            const btn = document.getElementById('verify-otp-btn');
            btn.disabled = true;
            btn.textContent = 'Verifying...';
            try {
                const url = new URL(window.location.href);
                const response = await fetch(`/tenant/sign/${leaseId}/verify-otp${url.search}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ code })
                });
                const data = await response.json();
                if (data.success) {
                    clearInterval(otpTimer);
                    goToStep(2);
                } else {
                    showMessage('otp-message', 'error', data.message, true);
                    btn.disabled = false;
                    btn.textContent = 'Verify Code';
                }
            } catch (error) {
                showMessage('otp-message', 'error', 'Network error. Please try again.', true);
                btn.disabled = false;
                btn.textContent = 'Verify Code';
            }
        }

        // ═══════════ STEP 2 — REVIEW ═══════════

        function setupStep2Listeners() {
            const agreeBox  = document.getElementById('agree-terms');
            const proceedBtn = document.getElementById('proceed-to-sign-btn');
            if (agreeBox && proceedBtn) {
                function updateProceedButton() { proceedBtn.disabled = !agreeBox.checked; }
                agreeBox.addEventListener('change', updateProceedButton);
                agreeBox.addEventListener('input', updateProceedButton);
                agreeBox.addEventListener('click', updateProceedButton);
                proceedBtn.addEventListener('click', () => goToStep(3));
            }
        }

        function onPdfLoaded() {
            document.getElementById('pdf-scroll-notice').textContent =
                '📄 Lease document loaded. Please scroll through and read all terms before confirming.';
        }

        // ═══════════ STEP 3 — SIGNATURE ═══════════

        function initializeSignaturePad() {
            try {
                const mainCanvas = document.getElementById('signature-pad');
                const witnessCanvas = document.getElementById('witness-signature-pad');
                const ratio = Math.max(window.devicePixelRatio || 1, 1);

                if (mainCanvas && !signaturePad) {
                    mainCanvas.width  = mainCanvas.offsetWidth  * ratio;
                    mainCanvas.height = mainCanvas.offsetHeight * ratio;
                    mainCanvas.getContext('2d').scale(ratio, ratio);

                    signaturePad = new SignaturePad(mainCanvas, {
                        backgroundColor: 'rgb(255, 255, 255)',
                        penColor: 'rgb(0, 0, 0)',
                        minWidth: 0.5,
                        maxWidth: 2.5
                    });
                    signaturePad.addEventListener('endStroke', () => {
                        updateSubmitButtonState();
                    });
                    document.getElementById('clear-signature-btn').addEventListener('click', () => {
                        signaturePad.clear();
                        updateSubmitButtonState();
                    });
                    document.getElementById('undo-signature-btn').addEventListener('click', () => {
                        const d = signaturePad.toData();
                        if (d) { d.pop(); signaturePad.fromData(d); }
                        updateSubmitButtonState();
                    });
                }

                if (witnessCanvas && !witnessSignaturePad) {
                    witnessCanvas.width  = witnessCanvas.offsetWidth  * ratio;
                    witnessCanvas.height = witnessCanvas.offsetHeight * ratio;
                    witnessCanvas.getContext('2d').scale(ratio, ratio);

                    witnessSignaturePad = new SignaturePad(witnessCanvas, {
                        backgroundColor: 'rgb(255, 255, 255)',
                        penColor: 'rgb(0, 0, 0)',
                        minWidth: 0.5,
                        maxWidth: 2.5
                    });
                    witnessSignaturePad.addEventListener('endStroke', () => {
                        updateSubmitButtonState();
                    });
                    document.getElementById('clear-witness-signature-btn').addEventListener('click', () => {
                        witnessSignaturePad.clear();
                        document.getElementById('witness-signature-data').value = '';
                        updateSubmitButtonState();
                    });
                }

                const advocateRadios = document.querySelectorAll('input[name="advocate_selection"]');
                const ownAdvocateFields = document.getElementById('own-advocate-fields');
                advocateRadios.forEach(radio => {
                    radio.addEventListener('change', () => {
                        if (radio.value === 'own_advocate' && radio.checked) {
                            ownAdvocateFields.classList.remove('hidden');
                        } else if (radio.checked) {
                            ownAdvocateFields.classList.add('hidden');
                        }
                        updateSubmitButtonState();
                    });
                });
                ['lessee-witness-name', 'lessee-witness-id', 'tenant-advocate-name', 'tenant-advocate-email'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('input', updateSubmitButtonState);
                    }
                });

                const submitBtn = document.getElementById('submit-signature-btn');
                if (submitBtn && !submitBtn._bound) {
                    submitBtn.addEventListener('click', submitSignature);
                    submitBtn._bound = true;
                }
            } catch (e) {
                console.error('SignaturePad init failed:', e);
            }
        }

        function updateSubmitButtonState() {
            const btn = document.getElementById('submit-signature-btn');
            if (!btn) return;
            const witnessName = document.getElementById('lessee-witness-name')?.value.trim() || '';
            const witnessId = document.getElementById('lessee-witness-id')?.value.trim() || '';
            const advocateSelection = document.querySelector('input[name="advocate_selection"]:checked')?.value || '';
            const ownAdvocateName = document.getElementById('tenant-advocate-name')?.value.trim() || '';
            const ownAdvocateEmail = document.getElementById('tenant-advocate-email')?.value.trim() || '';

            const mainOk = signaturePad && !signaturePad.isEmpty();
            const witnessOk = witnessSignaturePad && !witnessSignaturePad.isEmpty() && witnessName !== '' && witnessId !== '';
            let advocateOk = advocateSelection !== '';
            if (advocateSelection === 'own_advocate') {
                advocateOk = ownAdvocateName !== '' && ownAdvocateEmail !== '';
            }

            btn.disabled = !(mainOk && witnessOk && advocateOk);
        }

        async function submitSignature() {
            if (!signaturePad || signaturePad.isEmpty()) {
                showMessage('signature-message', 'error', 'Please provide your signature before submitting.');
                return;
            }
            if (!witnessSignaturePad || witnessSignaturePad.isEmpty()) {
                showMessage('signature-message', 'error', 'Please ask your in-person witness to sign as well.', true);
                return;
            }
            const witnessName = document.getElementById('lessee-witness-name').value.trim();
            const witnessId = document.getElementById('lessee-witness-id').value.trim();
            if (!witnessName || !witnessId) {
                showMessage('signature-message', 'error', 'Witness name and ID are required.', true);
                return;
            }
            const advocateSelection = document.querySelector('input[name="advocate_selection"]:checked')?.value;
            if (!advocateSelection) {
                showMessage('signature-message', 'error', 'Please select your legal representation option.', true);
                return;
            }
            const ownAdvocateName = document.getElementById('tenant-advocate-name').value.trim();
            const ownAdvocateEmail = document.getElementById('tenant-advocate-email').value.trim();
            if (advocateSelection === 'own_advocate') {
                if (!ownAdvocateName || !ownAdvocateEmail) {
                    showMessage('signature-message', 'error', 'Please provide your advocate’s name and email.', true);
                    return;
                }
            }
            const btn = document.getElementById('submit-signature-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';
            try {
                const signatureData = signaturePad.toDataURL();
                const witnessSignatureData = witnessSignaturePad.toDataURL();
                if (!signatureData || signatureData.length < 1000) {
                    showMessage('signature-message', 'error', 'Signature capture failed. Please clear and draw again.', true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                    return;
                }
                if (!witnessSignatureData || witnessSignatureData.length < 1000) {
                    showMessage('signature-message', 'error', 'Witness signature capture failed. Please clear and draw again.', true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                    return;
                }
                document.getElementById('witness-signature-data').value = witnessSignatureData;
                const url = new URL(window.location.href);
                const response = await fetch(`/tenant/sign/${leaseId}/submit-signature${url.search}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        signature_data: signatureData,
                        witness_signature_data: witnessSignatureData,
                        lessee_witness_name: witnessName,
                        lessee_witness_id: witnessId,
                        advocate_selection: advocateSelection,
                        tenant_advocate_name: advocateSelection === 'own_advocate' ? ownAdvocateName : null,
                        tenant_advocate_email: advocateSelection === 'own_advocate' ? ownAdvocateEmail : null,
                        latitude:  userLocation?.latitude,
                        longitude: userLocation?.longitude
                    })
                });
                const data = await response.json();
                // 409 already_signed means a previous attempt succeeded — treat as success
                if (response.status === 409 && data.error === 'already_signed') {
                    updateStepIndicator(3, 'completed');
                    showMessage('signature-message', 'success', '✅ Lease already signed. Proceeding...');
                    btn.disabled = true;
                    setTimeout(() => goToStep(4), 1800);
                    return;
                }
                if (!response.ok && response.status !== 400) {
                    showMessage('signature-message', 'error', 'Server error (' + response.status + '). Please try again.', true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                    return;
                }
                if (data.success) {
                    updateStepIndicator(3, 'completed');
                    showMessage('signature-message', 'success', '✅ Lease signed! Now please upload your ID copy.');
                    btn.disabled = true;
                    // Proceed to step 4 after a short delay
                    setTimeout(() => goToStep(4), 1800);
                } else {
                    showMessage('signature-message', 'error', data.message, true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                }
            } catch (error) {
                showMessage('signature-message', 'error', 'Network error: ' + error.message, true);
                btn.disabled = false;
                btn.textContent = 'Submit Signature';
            }
        }

        // ═══════════ STEP 4 — ID UPLOAD ═══════════

        function setupIDUploadListeners() {
            const input    = document.getElementById('id-file-input');
            const dropZone = document.getElementById('drop-zone');
            const uploadBtn = document.getElementById('upload-id-btn');
            const skipBtn  = document.getElementById('skip-id-btn');

            // File input change
            input.addEventListener('change', (e) => handleFileSelection(e.target.files));

            // Drag and drop
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                handleFileSelection(e.dataTransfer.files);
            });

            uploadBtn.addEventListener('click', uploadIDFiles);
            skipBtn.addEventListener('click', completeProcess);
        }

        function handleFileSelection(files) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
            selectedFiles = [];
            const errors  = [];

            Array.from(files).forEach(file => {
                if (!allowed.includes(file.type)) {
                    errors.push(`${file.name}: unsupported format (use JPG, PNG or PDF)`);
                } else if (file.size > maxSize) {
                    errors.push(`${file.name}: file too large (max 5MB)`);
                } else {
                    selectedFiles.push(file);
                }
            });

            const dropZone  = document.getElementById('drop-zone');
            const idleEl    = document.getElementById('drop-zone-idle');
            const filesEl   = document.getElementById('drop-zone-files');
            const uploadBtn = document.getElementById('upload-id-btn');
            const msgEl     = document.getElementById('upload-message');

            if (errors.length) {
                msgEl.className = 'mb-4 p-4 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = errors.join(' | ');
                msgEl.classList.remove('hidden');
            } else {
                msgEl.classList.add('hidden');
            }

            if (selectedFiles.length > 0) {
                dropZone.classList.add('has-file');
                idleEl.classList.add('hidden');
                filesEl.classList.remove('hidden');
                filesEl.innerHTML = selectedFiles.map(f =>
                    `<div class="flex items-center gap-2 py-1 text-sm text-gray-700">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="truncate">${f.name}</span>
                        <span class="text-gray-400 text-xs flex-shrink-0">(${(f.size/1024).toFixed(0)} KB)</span>
                    </div>`
                ).join('') + `<p class="mt-2 text-xs text-blue-600 cursor-pointer hover:underline" onclick="document.getElementById('id-file-input').click()">Change files</p>`;
                uploadBtn.disabled = false;
            } else {
                dropZone.classList.remove('has-file');
                idleEl.classList.remove('hidden');
                filesEl.classList.add('hidden');
                filesEl.innerHTML = '';
                uploadBtn.disabled = true;
            }
        }

        async function uploadIDFiles() {
            if (selectedFiles.length === 0) return;

            const btn   = document.getElementById('upload-id-btn');
            const msgEl = document.getElementById('upload-message');
            btn.disabled = true;
            btn.textContent = 'Uploading...';

            try {
                const formData = new FormData();
                selectedFiles.forEach((file, i) => formData.append(`id_documents[${i}]`, file));

                const url = new URL(window.location.href);
                const response = await fetch(`/tenant/sign/${leaseId}/upload-id${url.search}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    msgEl.className = 'mb-4 p-4 rounded-lg text-sm bg-green-100 text-green-800';
                    msgEl.textContent = '✅ ' + data.message;
                    msgEl.classList.remove('hidden');
                    btn.textContent = 'Uploaded ✓';
                    setTimeout(completeProcess, 1500);
                } else {
                    msgEl.className = 'mb-4 p-4 rounded-lg text-sm bg-red-100 text-red-800';
                    msgEl.textContent = data.message || 'Upload failed. Please try again.';
                    msgEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Upload ID Copy';
                }
            } catch (error) {
                const msgEl = document.getElementById('upload-message');
                msgEl.className = 'mb-4 p-4 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Network error. Please try again.';
                msgEl.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Upload ID Copy';
            }
        }

        function completeProcess() {
            updateStepIndicator(4, 'completed');
            document.getElementById('step4-content').classList.add('hidden');
            document.getElementById('step4-complete').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // ═══════════ STEP NAVIGATION ═══════════

        function goToStep(step) {
            // Hide all step contents
            [1, 2, 3, 4].forEach(s => {
                document.getElementById(`step${s}-content`)?.classList.add('hidden');
            });

            // Mark previous steps completed, new step active
            for (let s = 1; s < step; s++) updateStepIndicator(s, 'completed');
            updateStepIndicator(step, 'active');
            for (let s = step + 1; s <= 4; s++) updateStepIndicator(s, 'pending');

            document.getElementById(`step${step}-content`).classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Step-specific init
            if (step === 3) {
                requestAnimationFrame(() => requestAnimationFrame(initializeSignaturePad));
            }
        }

        // ═══════════ DISPUTE ═══════════

        function setupDisputeListeners() {
            const toggleBtn = document.getElementById('toggle-dispute-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const form    = document.getElementById('dispute-form');
                    const chevron = document.getElementById('dispute-chevron');
                    const hidden  = form.classList.contains('hidden');
                    form.classList.toggle('hidden', !hidden);
                    chevron.textContent = hidden ? '▲' : '▼';
                });
            }
            const submitBtn = document.getElementById('submit-dispute-btn');
            if (submitBtn) submitBtn.addEventListener('click', submitDispute);
        }

        async function submitDispute() {
            const reason  = document.getElementById('dispute-reason').value;
            const comment = document.getElementById('dispute-comment').value.trim();
            const msgEl   = document.getElementById('dispute-message');

            if (!reason) {
                msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Please select a reason for your dispute.';
                msgEl.classList.remove('hidden');
                return;
            }
            if (!comment || comment.length < 10) {
                msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Please provide details (at least 10 characters).';
                msgEl.classList.remove('hidden');
                return;
            }
            const btn = document.getElementById('submit-dispute-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';
            try {
                const url = new URL(window.location.href);
                const response = await fetch(`/tenant/sign/${leaseId}/reject${url.search}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ reason, comment }),
                });
                const data = await response.json();
                if (data.success) {
                    msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-green-100 text-green-800';
                    msgEl.textContent = '✅ ' + data.message;
                    msgEl.classList.remove('hidden');
                    btn.textContent = 'Dispute Submitted';
                    document.getElementById('proceed-to-sign-btn').style.display = 'none';
                    document.getElementById('agree-terms').closest('div').style.display = 'none';
                } else {
                    msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                    msgEl.textContent = data.message || 'Failed to submit. Please try again.';
                    msgEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Submit Dispute';
                }
            } catch (error) {
                msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Network error. Please try again.';
                msgEl.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Submit Dispute';
            }
        }

        // ═══════════ UTILITIES ═══════════

        function startOTPTimer(seconds) {
            otpExpiresAt = Date.now() + (seconds * 1000);
            otpTimer = setInterval(() => {
                const remaining = Math.max(0, Math.floor((otpExpiresAt - Date.now()) / 1000));
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                document.getElementById('otp-timer').textContent = `${m}:${s.toString().padStart(2, '0')}`;
                if (remaining === 0) {
                    clearInterval(otpTimer);
                    showMessage('otp-message', 'error', 'OTP has expired. Please request a new code.', true);
                }
            }, 1000);
        }

        function updateStepIndicator(step, status) {
            const el = document.getElementById(`step${step}-indicator`);
            if (!el) return;
            el.classList.remove('step-active', 'step-completed', 'step-pending');
            el.classList.add(`step-${status}`);
        }

        function showMessage(elementId, type, message, persistent = false) {
            const el = document.getElementById(elementId);
            if (!el) return;
            el.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            el.textContent = message;
            el.classList.remove('hidden');
            if (!persistent) setTimeout(() => el.classList.add('hidden'), 5000);
        }
    </script>

    {{-- ── Geolocation Consent Modal ── --}}
    {{-- Shown before requesting navigator.geolocation to comply with KDPA 2019. --}}
    <div id="geolocation-consent-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-labelledby="geo-consent-title">
        <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="geo-consent-title" class="font-semibold text-gray-900">Location Permission</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Optional — for audit trail only</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-5 leading-relaxed">
                We request your approximate location as part of the digital signing audit trail.
                This helps verify the signing event in case of a future dispute.
                <strong>You can decline and still complete the signing.</strong>
            </p>
            <div class="flex gap-3">
                <button id="geo-consent-accept"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2.5 px-4 rounded-xl transition-colors">
                    Allow Location
                </button>
                <button id="geo-consent-decline"
                        class="flex-1 border border-gray-300 hover:border-gray-400 text-gray-700 text-sm font-medium py-2.5 px-4 rounded-xl transition-colors">
                    No Thanks
                </button>
            </div>
        </div>
    </div>
</body>
</html>
