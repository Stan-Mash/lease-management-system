<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Lease - {{ $lease->reference_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-canvas {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            touch-action: none;
        }
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-active {
            background-color: #3b82f6;
            color: white;
        }
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
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

        <!-- Step Indicators -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 text-center">
                    <div id="step1-indicator" class="step-indicator step-active w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold">1</div>
                    <p class="text-xs mt-2 font-medium">Verify Identity</p>
                </div>
                <div class="flex-1 border-t-2 border-gray-300 mx-2 -mt-8"></div>
                <div class="flex-1 text-center">
                    <div id="step2-indicator" class="step-indicator step-pending w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold">2</div>
                    <p class="text-xs mt-2 font-medium">Review Lease</p>
                </div>
                <div class="flex-1 border-t-2 border-gray-300 mx-2 -mt-8"></div>
                <div class="flex-1 text-center">
                    <div id="step3-indicator" class="step-indicator step-pending w-10 h-10 rounded-full mx-auto flex items-center justify-center font-bold">3</div>
                    <p class="text-xs mt-2 font-medium">Sign Lease</p>
                </div>
            </div>
        </div>

        <!-- Step 1: OTP Verification -->
        <div id="step1-content" class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 1: Verify Your Identity</h2>
            <p class="text-gray-600 mb-6">We'll send a verification code to your registered phone number ending in {{ substr($tenant->mobile_number, -4) }}</p>

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

        <!-- Step 2: Review Lease -->
        <div id="step2-content" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Step 2: Review Your Lease Agreement</h2>
            <p class="text-gray-600 mb-4">Read the full lease document carefully before signing. You must scroll through the entire document and confirm you have read it. If you disagree with any terms, use the <strong>Dispute</strong> option below.</p>

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
                        <p class="font-semibold">KES {{ number_format($lease->security_deposit ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Start Date</p>
                        <p class="font-semibold">{{ $lease->start_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">End Date</p>
                        <p class="font-semibold">{{ $lease->end_date->format('d M Y') }}</p>
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
                    I <strong>{{ $lease->tenant->names }}</strong> confirm that I have read and fully understood all terms and conditions of this lease agreement (Reference: <strong>{{ $lease->reference_number }}</strong>) and agree to be legally bound by its terms.
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
                    <p class="text-sm text-gray-600 mb-3">Select your reason for disputing and provide details. Our team will contact you within 24 hours to resolve the matter.</p>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Dispute</label>
                        <select id="dispute-reason" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400">
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
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400"></textarea>
                    </div>
                    <div id="dispute-message" class="hidden mb-3 p-3 rounded-lg text-sm"></div>
                    <button id="submit-dispute-btn"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition text-sm">
                        Submit Dispute
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Digital Signature -->
        <div id="step3-content" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 3: Sign the Lease</h2>
            <p class="text-gray-600 mb-4">Please sign in the box below using your mouse or touch screen.</p>

            <div class="mb-4">
                <canvas id="signature-pad" class="signature-canvas w-full" width="700" height="300"></canvas>
            </div>

            <div class="flex gap-3 mb-6">
                <button id="clear-signature-btn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                    Clear
                </button>
                <button id="undo-signature-btn" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition">
                    Undo
                </button>
            </div>

            <button id="submit-signature-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                Submit Signature
            </button>

            <div id="signature-message" class="hidden mt-4 p-4 rounded-lg"></div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 mt-8">
            <p>This is a secure signing session. Your signature is encrypted and time-stamped.</p>
            <p class="mt-2">© {{ date('Y') }} Chabrin Lease Management System</p>
        </div>
    </div>

    <script>
        // Configuration
        const leaseId = {{ $lease->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let signaturePad;
        let otpTimer;
        let otpExpiresAt;
        let userLocation = null;

        // Get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    };
                },
                (error) => {
                    console.log('Location access denied:', error);
                }
            );
        }

        // Initialize — set up OTP buttons immediately; defer signature pad to step 3
        document.addEventListener('DOMContentLoaded', function() {
            setupOTPListeners();
        });

        function setupOTPListeners() {
            document.getElementById('request-otp-btn').addEventListener('click', requestOTP);
            document.getElementById('verify-otp-btn').addEventListener('click', verifyOTP);
            document.getElementById('resend-otp-btn').addEventListener('click', requestOTP);
        }

        function setupStep2Listeners() {
            document.getElementById('agree-terms').addEventListener('change', function() {
                document.getElementById('proceed-to-sign-btn').disabled = !this.checked;
            });
            document.getElementById('proceed-to-sign-btn').addEventListener('click', showStep3);
        }

        function initializeSignaturePad() {
            const canvas = document.getElementById('signature-pad');
            try {
                // Fix canvas internal resolution to match its CSS dimensions.
                // Without this, SignaturePad's isEmpty() check uses a mismatched
                // buffer and can incorrectly report empty after the first stroke,
                // causing the first submit click to be silently rejected.
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);

                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255, 255, 255)',
                    penColor: 'rgb(0, 0, 0)',
                    minWidth: 0.5,
                    maxWidth: 2.5
                });

                signaturePad.addEventListener('endStroke', () => {
                    document.getElementById('submit-signature-btn').disabled = signaturePad.isEmpty();
                });

                document.getElementById('clear-signature-btn').addEventListener('click', () => signaturePad.clear());
                document.getElementById('undo-signature-btn').addEventListener('click', () => {
                    const data = signaturePad.toData();
                    if (data) {
                        data.pop();
                        signaturePad.fromData(data);
                    }
                });
                document.getElementById('submit-signature-btn').addEventListener('click', submitSignature);
            } catch (e) {
                console.error('SignaturePad init failed:', e);
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
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
                console.error('OTP request error:', error);
                showMessage('otp-message', 'error', 'Network error: ' + error.message + '. Please try again.', true);
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
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code })
                });

                const data = await response.json();

                if (data.success) {
                    clearInterval(otpTimer);
                    updateStepIndicator(1, 'completed');
                    updateStepIndicator(2, 'active');
                    document.getElementById('step1-content').classList.add('hidden');
                    document.getElementById('step2-content').classList.remove('hidden');
                    setupStep2Listeners();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    showMessage('otp-message', 'error', data.message, true);
                    btn.disabled = false;
                    btn.textContent = 'Verify Code';
                }
            } catch (error) {
                showMessage('otp-message', 'error', 'Network error. Please check your connection and try again.', true);
                btn.disabled = false;
                btn.textContent = 'Verify Code';
            }
        }

        function showStep3() {
            updateStepIndicator(2, 'completed');
            updateStepIndicator(3, 'active');
            document.getElementById('step2-content').classList.add('hidden');
            document.getElementById('step3-content').classList.remove('hidden');
            // Defer SignaturePad init until after browser reflow so offsetWidth is correct
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    initializeSignaturePad();
                });
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function submitSignature() {
            if (signaturePad.isEmpty()) {
                showMessage('signature-message', 'error', 'Please provide your signature.');
                return;
            }

            const btn = document.getElementById('submit-signature-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            try {
                const signatureData = signaturePad.toDataURL();

                // Guard: if canvas was 0-width at init, toDataURL returns a tiny blank image
                if (!signatureData || signatureData.length < 1000) {
                    showMessage('signature-message', 'error', 'Signature capture failed. Please clear and draw again.', true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                    return;
                }

                const url = new URL(window.location.href);

                const payload = {
                    signature_data: signatureData,
                    latitude: userLocation?.latitude,
                    longitude: userLocation?.longitude
                };

                const response = await fetch(`/tenant/sign/${leaseId}/submit-signature${url.search}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                // If response is not JSON (413 too large, 419 CSRF, 500 error), show HTTP status
                if (!response.ok && response.status !== 400) {
                    const text = await response.text();
                    console.error('Signature submit error HTTP ' + response.status, text.substring(0, 300));
                    showMessage('signature-message', 'error', 'Server error (' + response.status + '). Please try again.', true);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    updateStepIndicator(3, 'completed');
                    showMessage('signature-message', 'success',
                        '✅ Lease signed successfully! A confirmation has been sent to your phone and email. Redirecting...');

                    // Disable submit button to prevent double-submit
                    btn.disabled = true;

                    // Reload shows the "already-signed" confirmation page
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    showMessage('signature-message', 'error', data.message);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                }
            } catch (error) {
                console.error('Signature submit exception:', error);
                showMessage('signature-message', 'error', 'Network error: ' + error.message, true);
                btn.disabled = false;
                btn.textContent = 'Submit Signature';
            }
        }

        function startOTPTimer(seconds) {
            otpExpiresAt = Date.now() + (seconds * 1000);

            otpTimer = setInterval(() => {
                const remaining = Math.max(0, Math.floor((otpExpiresAt - Date.now()) / 1000));
                const minutes = Math.floor(remaining / 60);
                const secs = remaining % 60;

                document.getElementById('otp-timer').textContent =
                    `${minutes}:${secs.toString().padStart(2, '0')}`;

                if (remaining === 0) {
                    clearInterval(otpTimer);
                    showMessage('otp-message', 'error', 'OTP has expired. Please request a new code.');
                }
            }, 1000);
        }

        function updateStepIndicator(step, status) {
            const indicator = document.getElementById(`step${step}-indicator`);
            indicator.classList.remove('step-active', 'step-completed', 'step-pending');
            indicator.classList.add(`step-${status}`);
        }

        function showMessage(elementId, type, message, persistent = false) {
            const element = document.getElementById(elementId);
            element.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
            element.textContent = message;
            element.classList.remove('hidden');

            if (!persistent) {
                setTimeout(() => {
                    element.classList.add('hidden');
                }, 5000);
            }
        }

        // ── PDF REVIEW (Step 2) ────────────────────────────────────────────────

        function onPdfLoaded() {
            // PDF iframe loaded — user can now scroll and confirm
            document.getElementById('pdf-scroll-notice').textContent =
                '📄 Lease document loaded. Please scroll through and read all terms before confirming.';
        }

        // Enable "Proceed to Sign" only when checkbox is ticked
        document.addEventListener('DOMContentLoaded', function() {
            const agreeCheckbox = document.getElementById('agree-terms');
            const proceedBtn = document.getElementById('proceed-to-sign-btn');

            if (agreeCheckbox && proceedBtn) {
                agreeCheckbox.addEventListener('change', function() {
                    proceedBtn.disabled = !this.checked;
                });
            }

            // Dispute form toggle
            const toggleBtn = document.getElementById('toggle-dispute-btn');
            const disputeForm = document.getElementById('dispute-form');
            const chevron = document.getElementById('dispute-chevron');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isHidden = disputeForm.classList.contains('hidden');
                    disputeForm.classList.toggle('hidden', !isHidden);
                    chevron.textContent = isHidden ? '▲' : '▼';
                });
            }

            // Dispute submission
            const submitDisputeBtn = document.getElementById('submit-dispute-btn');
            if (submitDisputeBtn) {
                submitDisputeBtn.addEventListener('click', submitDispute);
            }
        });

        async function submitDispute() {
            const reason = document.getElementById('dispute-reason').value;
            const comment = document.getElementById('dispute-comment').value.trim();
            const msgEl = document.getElementById('dispute-message');

            if (!reason) {
                msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Please select a reason for your dispute.';
                msgEl.classList.remove('hidden');
                return;
            }
            if (!comment || comment.length < 10) {
                msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                msgEl.textContent = 'Please provide details about your dispute (at least 10 characters).';
                msgEl.classList.remove('hidden');
                return;
            }

            const btn = document.getElementById('submit-dispute-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            const url = new URL(window.location.href);

            try {
                const response = await fetch(`/tenant/sign/${leaseId}/reject${url.search}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason, comment }),
                });

                const data = await response.json();

                if (data.success) {
                    msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-green-100 text-green-800';
                    msgEl.textContent = '✅ ' + data.message;
                    msgEl.classList.remove('hidden');
                    btn.textContent = 'Dispute Submitted';
                    // Hide the sign button since lease is now disputed
                    document.getElementById('proceed-to-sign-btn').style.display = 'none';
                    document.getElementById('agree-terms').closest('div').style.display = 'none';
                } else {
                    msgEl.className = 'mb-3 p-3 rounded-lg text-sm bg-red-100 text-red-800';
                    msgEl.textContent = data.message || 'Failed to submit dispute. Please try again.';
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
    </script>
</body>
</html>
