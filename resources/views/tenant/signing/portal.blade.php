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
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 2: Review Your Lease Agreement</h2>
            <p class="text-gray-600 mb-4">Please carefully review the lease agreement before signing.</p>

            <div class="border-2 border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Property Type</p>
                        <p class="font-semibold">{{ ucfirst($lease->lease_type) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Monthly Rent</p>
                        <p class="font-semibold">{{ number_format($lease->monthly_rent, 2) }} {{ $lease->currency }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Start Date</p>
                        <p class="font-semibold">{{ $lease->start_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">End Date</p>
                        <p class="font-semibold">{{ $lease->end_date->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Security Deposit</p>
                        <p class="font-semibold">{{ number_format($lease->security_deposit, 2) }} {{ $lease->currency }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Duration</p>
                        <p class="font-semibold">{{ $lease->start_date->diffInMonths($lease->end_date) }} months</p>
                    </div>
                </div>
            </div>

            @if($lease->document_path)
            <div class="mb-4">
                <iframe src="{{ route('tenant.view-lease', ['lease' => $lease->id]) }}"
                        class="w-full border-2 border-gray-300 rounded-lg"
                        style="height: 500px;">
                </iframe>
            </div>
            @endif

            <div class="flex items-start mb-4">
                <input type="checkbox" id="agree-terms" class="mt-1 mr-3 h-5 w-5 text-blue-600">
                <label for="agree-terms" class="text-sm text-gray-700">
                    I have read and understood all terms and conditions of this lease agreement. I agree to be bound by the terms stated herein.
                </label>
            </div>

            <button id="proceed-to-sign-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                Proceed to Signing
            </button>
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
            initializeSignaturePad();
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

                const data = await response.json();

                if (data.success) {
                    updateStepIndicator(3, 'completed');
                    showMessage('signature-message', 'success',
                        'Lease signed successfully! You will receive a confirmation via email and SMS.');

                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    showMessage('signature-message', 'error', data.message);
                    btn.disabled = false;
                    btn.textContent = 'Submit Signature';
                }
            } catch (error) {
                showMessage('signature-message', 'error', 'Failed to submit signature. Please try again.');
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
    </script>
</body>
</html>
