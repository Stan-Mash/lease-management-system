<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lease for review – {{ $lease->reference_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        .signature-canvas { border: 2px solid #e5e7eb; border-radius: 0.5rem; touch-action: none; background: #fff; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-10">

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-indigo-700 px-6 py-4">
                <h1 class="text-xl font-bold text-white">Chabrin Agencies – Advocate portal</h1>
                <p class="text-indigo-200 text-sm mt-1">Lease reference: {{ $lease->reference_number }}</p>
            </div>

            <div class="p-6 space-y-6">
                @if(session('success'))
                    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                @if($alreadyProcessed ?? $tracking->status === 'returned')
                    <div class="rounded-lg bg-green-50 border border-green-200 px-5 py-4 text-green-800">
                        <p class="font-semibold text-base mb-1">✅ Document already processed</p>
                        <p class="text-sm">Your signature and/or stamped PDF have been received by Chabrin Agencies. This document is no longer accessible via this link. No further action is needed. Thank you.</p>
                    </div>
                @else
                <p class="text-gray-600">
                    This lease has been sent to you for legal review and advocate stamping. Read the document below, then either <strong>sign and stamp digitally</strong> or <strong>upload a pre-stamped PDF</strong>.
                </p>

                {{-- Section 1: Document viewer (hidden once processed) --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">1. Review lease document</h2>
                        <a href="{{ $downloadUrl }}"
                           class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download PDF
                        </a>
                    </div>
                    <iframe
                        src="{{ route('lawyer.portal.view', ['token' => $token]) }}"
                        class="w-full"
                        style="height: 680px; min-height: 400px;"
                        title="Lease document – {{ $lease->reference_number }}"
                        loading="lazy">
                        <div class="p-6 text-center text-gray-600">
                            <p class="mb-2">Your browser does not support inline PDF viewing.</p>
                            <a href="{{ $downloadUrl }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                                Click here to download and view the lease
                            </a>
                        </div>
                    </iframe>
                </div>

                {{-- Option A: Sign & stamp digitally --}}
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 mb-2">2a. Sign and stamp digitally</h2>
                    <p class="text-sm text-gray-600 mb-4">Draw your signature below or upload an image. Optionally upload your Commissioner for Oaths (or similar) stamp. We will overlay them onto the lease PDF.</p>

                    <form action="{{ route('lawyer.portal.upload', ['token' => $token]) }}" method="post" enctype="multipart/form-data" class="space-y-4" id="form-sign-stamp">
                        @csrf
                        <input type="hidden" name="signature_data" id="signature-data" value="">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your signature</label>
                            <canvas id="signature-pad" class="signature-canvas w-full" width="600" height="220"></canvas>
                            <div class="flex items-center gap-3 mt-2">
                                <button type="button" id="clear-signature-btn" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                    Clear signature
                                </button>
                                <span class="text-xs text-gray-500">Or upload an image below</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload signature image (optional if you drew above)</label>
                            <input type="file" name="signature_upload" accept=".jpg,.jpeg,.png"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('signature_upload')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload stamp image (optional, e.g. Commissioner for Oaths)</label>
                            <input type="file" name="stamp_upload" accept=".jpg,.jpeg,.png"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('stamp_upload')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" name="legal_consent" id="legal_consent" value="1" required
                                class="mt-1 h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                            <label for="legal_consent" class="ml-3 text-sm text-gray-700">
                                I confirm I am applying my legally binding signature and/or stamp to this document.
                            </label>
                        </div>
                        @error('legal_consent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <button type="submit" name="submit_mode" value="sign_stamp" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Apply signature &amp; submit
                        </button>
                    </form>
                </div>

                {{-- Option B: Upload stamped PDF --}}
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h2 class="font-semibold text-gray-900 mb-2">2b. Or upload your stamped PDF</h2>
                    <p class="text-sm text-gray-600 mb-4">If you stamped the lease offline, upload the PDF here.</p>
                    <form action="{{ route('lawyer.portal.upload', ['token' => $token]) }}" method="post" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <input type="file" name="stamped_pdf" accept=".pdf"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('stamped_pdf')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" name="submit_mode" value="upload_pdf" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Upload stamped lease
                        </button>
                    </form>
                </div>

                @endif {{-- end @else (tracking not yet returned) --}}

                @if($expiresAt)
                    <p class="text-xs text-gray-500">This link expires on {{ $expiresAt->format('d M Y') }}.</p>
                @endif
            </div>
        </div>

    </div>

    <script>
    (function () {
        var canvas = document.getElementById('signature-pad');
        var hiddenInput = document.getElementById('signature-data');
        var clearBtn = document.getElementById('clear-signature-btn');
        var form = document.getElementById('form-sign-stamp');

        if (!canvas) return;

        function initPad() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);

            window.advocateSignaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 0.5,
                maxWidth: 2.5
            });

            window.advocateSignaturePad.addEventListener('endStroke', function () {
                if (!window.advocateSignaturePad.isEmpty()) {
                    hiddenInput.value = window.advocateSignaturePad.toDataURL('image/png');
                } else {
                    hiddenInput.value = '';
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (window.advocateSignaturePad) {
                    window.advocateSignaturePad.clear();
                    hiddenInput.value = '';
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function () {
                if (window.advocateSignaturePad && !window.advocateSignaturePad.isEmpty()) {
                    hiddenInput.value = window.advocateSignaturePad.toDataURL('image/png');
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPad);
        } else {
            initPad();
        }
    })();
    </script>
    <script>
        // Security: Force reload if loaded from browser cache (Back button)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>