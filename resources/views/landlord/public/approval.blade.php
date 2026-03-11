<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lease Approval — {{ $approval->lease->reference_number }}</title>
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f0e8;
            min-height: 100vh;
            color: #1a365d;
        }

        /* ── Top bar ── */
        .topbar {
            background: #1a365d;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-logo {
            width: 36px; height: 36px;
            background: #DAA520;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 900; color: #1a365d;
            flex-shrink: 0;
        }
        .topbar-name { color: #fff; font-size: 15px; font-weight: 700; letter-spacing: .03em; }
        .topbar-sub  { color: rgba(255,255,255,.55); font-size: 12px; margin-top: 1px; }

        /* ── Layout ── */
        .page { max-width: 720px; margin: 0 auto; padding: 24px 16px 80px; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg,#1a365d 0%,#2a4a7f 100%);
            border-radius: 14px 14px 0 0;
            padding: 24px 24px 18px;
            color: #fff;
        }
        .hero-label {
            font-size: 10px; font-weight: 700; letter-spacing: .15em;
            text-transform: uppercase; color: #DAA520; margin-bottom: 6px;
        }
        .hero-ref { font-size: 20px; font-weight: 800; line-height: 1.2; }
        .hero-sub  { font-size: 13px; color: rgba(255,255,255,.65); margin-top: 5px; }

        /* ── Summary card ── */
        .card {
            background: #fff;
            border-radius: 0 0 14px 14px;
            box-shadow: 0 4px 24px rgba(26,54,93,.12);
            overflow: hidden;
        }

        /* ── Alert flash ── */
        .alert {
            padding: 14px 20px;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

        /* ── Sections ── */
        .section { padding: 18px 22px; border-bottom: 1px solid #f0ece0; }
        .section:last-child { border-bottom: none; }
        .section-title {
            font-size: 10px; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: #DAA520; margin-bottom: 12px;
        }

        .row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
        .row:last-child { margin-bottom: 0; }
        .row-label { font-size: 12px; color: #6b7280; }
        .row-value { font-size: 13px; font-weight: 600; color: #1a365d; text-align: right; max-width: 60%; }

        .rent-highlight {
            background: linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%);
            border: 1.5px solid rgba(218,165,32,.35);
            border-left: 4px solid #DAA520;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 4px;
        }
        .rent-highlight .label { font-size: 12px; color: #92700a; font-weight: 600; }
        .rent-highlight .amount { font-size: 20px; font-weight: 800; color: #1a365d; }
        .rent-highlight .currency { font-size: 12px; color: #92700a; font-weight: 700; margin-right: 3px; }

        .expires {
            background: #fef3c7;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 11px; color: #92400e;
            display: flex; align-items: center; gap: 8px;
        }

        /* ── Lease document panel ── */
        .doc-section {
            margin-top: 20px;
        }
        .doc-header {
            display: flex; align-items: center; justify-content: space-between;
            background: #1a365d;
            color: #fff;
            padding: 14px 18px;
            border-radius: 12px 12px 0 0;
        }
        .doc-header-title {
            font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;
        }
        .doc-header-badge {
            font-size: 10px; font-weight: 700; letter-spacing: .08em;
            background: #DAA520; color: #1a365d;
            padding: 3px 8px; border-radius: 20px;
        }
        .doc-iframe {
            display: block;
            width: 100%;
            height: 80vh;
            min-height: 600px;
            border: 1.5px solid #dde3ed;
            border-top: none;
            border-radius: 0 0 12px 12px;
            background: #f8fafc;
        }
        .read-confirmation {
            display: flex; align-items: flex-start; gap: 10px;
            background: #f0fdf4; border: 1.5px solid #bbf7d0;
            border-radius: 10px; padding: 14px 16px;
            margin-top: 14px; cursor: pointer;
        }
        .read-confirmation input[type=checkbox] { width: 18px; height: 18px; accent-color: #16a34a; flex-shrink: 0; margin-top: 1px; }
        .read-confirmation label { font-size: 13px; color: #166534; font-weight: 600; cursor: pointer; }

        /* ── Action buttons ── */
        .actions { margin-top: 24px; display: flex; flex-direction: column; gap: 10px; }
        .btn {
            display: block; width: 100%;
            padding: 15px;
            border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700;
            cursor: pointer; text-align: center;
            transition: opacity .15s, transform .1s;
            letter-spacing: .02em;
        }
        .btn:active { transform: scale(.98); }
        .btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }
        .btn-approve  { background: #DAA520; color: #1a365d; }
        .btn-changes  { background: #eff6ff; color: #1d4ed8; border: 1.5px solid #bfdbfe; }
        .btn-reject   { background: #f3f4f6; color: #374151; }

        /* ── Modals ── */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 100;
            align-items: flex-end; justify-content: center;
        }
        .overlay.active { display: flex; }
        .sheet {
            background: #fff; width: 100%; max-width: 720px;
            border-radius: 20px 20px 0 0;
            padding: 28px 24px 40px;
            animation: slideUp .25s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .sheet-title { font-size: 18px; font-weight: 800; color: #1a365d; margin-bottom: 6px; }
        .sheet-sub   { font-size: 13px; color: #6b7280; margin-bottom: 20px; line-height: 1.5; }
        textarea, input[type=text] {
            width: 100%; border: 1.5px solid #e5e7eb; border-radius: 10px;
            padding: 12px 14px; font-size: 14px; font-family: inherit;
            color: #1a365d; resize: vertical; outline: none;
        }
        textarea:focus, input[type=text]:focus { border-color: #DAA520; }
        .field-label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
        .field-hint  { font-size: 11px; color: #9ca3af; margin-top: 4px; }
        .sheet-actions { display: flex; gap: 10px; margin-top: 20px; }
        .sheet-actions .btn { flex: 1; margin-bottom: 0; }
        .btn-confirm-approve  { background: #DAA520; color: #1a365d; }
        .btn-confirm-changes  { background: #1d4ed8; color: #fff; }
        .btn-confirm-reject   { background: #ef4444; color: #fff; }
        .btn-cancel { background: #f3f4f6; color: #374151; }

        /* ── Footer ── */
        .footer {
            text-align: center; padding: 20px 0 0;
            font-size: 11px; color: #9ca3af; line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-logo">C</div>
    <div>
        <div class="topbar-name">Chabrin Agencies</div>
        <div class="topbar-sub">Lease Approval Portal</div>
    </div>
</div>

<div class="page">

    {{-- Flash messages --}}
    @if (session('error'))
        <div class="alert alert-error" style="border-radius:10px; margin-bottom:16px;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Hero --}}
    <div class="hero">
        <div class="hero-label">Lease Approval Request</div>
        <div class="hero-ref">{{ $approval->lease->reference_number }}</div>
        <div class="hero-sub">
            Hello {{ $approval->landlord->names }} — please read the full lease below, then approve, request changes, or reject.
        </div>
    </div>

    {{-- Summary card --}}
    <div class="card">

        <div class="section">
            <div class="section-title">Monthly Rent</div>
            <div class="rent-highlight">
                <div class="label">Agreed Rent</div>
                <div class="amount">
                    <span class="currency">KES</span>{{ number_format((float)$approval->lease->monthly_rent) }}
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Lease Details</div>
            <div class="row">
                <span class="row-label">Property</span>
                <span class="row-value">{{ $approval->lease->property?->property_name ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Unit</span>
                <span class="row-value">{{ $approval->lease->unit?->unit_number ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Lease Type</span>
                <span class="row-value">{{ ucwords(str_replace('_', ' ', $approval->lease->lease_type ?? '—')) }}</span>
            </div>
            <div class="row">
                <span class="row-label">Start Date</span>
                <span class="row-value">{{ $approval->lease->start_date?->format('d M Y') ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">End Date</span>
                <span class="row-value">{{ $approval->lease->end_date?->format('d M Y') ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Deposit</span>
                <span class="row-value">KES {{ number_format((float)($approval->lease->deposit_amount ?? 0)) }}</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Tenant</div>
            <div class="row">
                <span class="row-label">Name</span>
                <span class="row-value">{{ $approval->lease->tenant?->names ?? '—' }}</span>
            </div>
            <div class="row">
                <span class="row-label">Phone</span>
                <span class="row-value">{{ $approval->lease->tenant?->mobile_number ?? '—' }}</span>
            </div>
            @if($approval->lease->tenant?->email_address)
            <div class="row">
                <span class="row-label">Email</span>
                <span class="row-value">{{ $approval->lease->tenant->email_address }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="expires">
                ⏰ This approval link expires on <strong style="margin-left:4px;">{{ $approval->token_expires_at->format('d M Y, g:i A') }}</strong>
            </div>
        </div>

    </div>

    {{-- Full lease document (same PDF as admin sees) --}}
    <div class="doc-section">
        <div class="doc-header">
            <div class="doc-header-title">
                📄 Full Lease Agreement
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="doc-header-badge">READ CAREFULLY</span>
                <a href="{{ $documentUrl }}" target="_blank" rel="noopener"
                   style="font-size:11px;color:rgba(255,255,255,.75);text-decoration:none;white-space:nowrap;">
                    ↗ Open in new tab
                </a>
            </div>
        </div>
        <iframe
            class="doc-iframe"
            src="{{ $documentUrl }}#toolbar=0&navpanes=0&scrollbar=1"
            title="Lease Agreement {{ $approval->lease->reference_number }}"
            id="leaseDoc"
        ></iframe>

        {{-- Read confirmation checkbox --}}
        <div class="read-confirmation" onclick="document.getElementById('readCheck').click()">
            <input type="checkbox" id="readCheck" onchange="toggleButtons()">
            <label for="readCheck">I have read and understood the full lease agreement above</label>
        </div>
    </div>

    {{-- OTP verification for landlord approval --}}
    @php
        $landlordPhone = $approval->landlord?->mobile_number;
        $phoneSuffix = $landlordPhone ? substr($landlordPhone, -4) : null;
    @endphp
    @if (! $otpVerified)
        <div class="section" id="otp-section" style="margin-top:16px;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;">
            <div class="section-title">Phone Verification</div>
            <p style="font-size:13px;color:#374151;margin-bottom:10px;">
                For security, please verify your phone before approving this lease.
                @if($phoneSuffix)
                    A 6-digit code will be sent to your number ending in <strong>{{ $phoneSuffix }}</strong>.
                @endif
            </p>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <button type="button" id="otp-request-btn"
                        style="padding:10px 12px;border-radius:8px;border:none;background:#1d4ed8;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                    Send verification code
                </button>
                <div id="otp-input-row" style="display:none;align-items:center;gap:8px;">
                    <input type="text" id="otp-code-input" maxlength="6"
                           style="flex:1;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:14px;letter-spacing:0.3em;text-align:center;"
                           placeholder="000000">
                    <button type="button" id="otp-verify-btn"
                            style="padding:9px 14px;border-radius:8px;border:none;background:#16a34a;color:#fff;font-size:13px;font-weight:600;cursor:pointer;">
                        Verify
                    </button>
                </div>
                <p id="otp-message" style="display:none;font-size:12px;margin-top:4px;"></p>
            </div>
        </div>
    @endif

    {{-- Action buttons (Approve additionally requires OTP) --}}
    <div class="actions">
        <button class="btn btn-approve" id="btnApprove" onclick="openSheet('approve')" disabled>
            ✅ Approve This Lease
        </button>
        <button class="btn btn-changes" id="btnChanges" onclick="openSheet('changes')" disabled>
            ✍️ Request Changes
        </button>
        <button class="btn btn-reject" id="btnReject" onclick="openSheet('reject')" disabled>
            ❌ Reject This Lease
        </button>
    </div>

    <div class="footer">
        Sent by Chabrin Agencies · {{ config('app.name') }}<br>
        If you did not expect this message, please ignore it.
    </div>

</div>

{{-- Approve sheet --}}
<div class="overlay" id="approveSheet">
    <div class="sheet">
        <div class="sheet-title">✅ Approve Lease</div>
        <div class="sheet-sub">
            You are approving lease <strong>{{ $approval->lease->reference_number }}</strong> for <strong>{{ $approval->lease->tenant?->names }}</strong>. This action cannot be undone.
        </div>
        <form id="approve-form" method="POST" action="{{ route('landlord.public.action', $approval->token) }}">
            @csrf
            <input type="hidden" name="action" value="approve">
            <label class="field-label">Comments (optional)</label>
            <textarea name="comments" rows="3" placeholder="e.g. Approved — please ensure deposit is paid before key handover."></textarea>
            <div class="field-hint">Any notes or conditions you'd like to add.</div>

            {{-- Landlord signature --}}
            <div style="margin-top:18px;">
                <span class="field-label">Landlord Signature</span>
                <p class="field-hint" style="margin-bottom:6px;">Sign inside the box below using your mouse or finger.</p>
                <canvas id="signature-pad"
                        style="width:100%;height:160px;border:1.5px solid #e5e7eb;border-radius:10px;background:#ffffff;display:block;touch-action:none;cursor:crosshair;"
                        width="600" height="160"></canvas>
                <input type="hidden" name="signature_data" id="signature_data" value="">
            </div>

            {{-- In-person witness --}}
            <div style="margin-top:18px;background:#f9fafb;border:1px dashed #e5e7eb;border-radius:10px;padding:14px 16px;">
                <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:8px;">
                    In-Person Witness — Lessor Side
                </div>
                <p style="font-size:12px;color:#4b5563;margin-bottom:10px;">
                    The person physically present when you approve this lease should fill in their details and sign below.
                </p>
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;">
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <label for="lessor-witness-name" class="field-label">Witness Full Name</label>
                        <input id="lessor-witness-name" name="lessor_witness_name" type="text"
                               style="border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;color:#111827;"
                               placeholder="Full name of witness">
                    </div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <label for="lessor-witness-id" class="field-label">Witness ID / Passport No.</label>
                        <input id="lessor-witness-id" name="lessor_witness_id" type="text"
                               style="border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 12px;font-size:13px;color:#111827;"
                               placeholder="National ID or Passport number">
                    </div>
                </div>
                <div style="margin-top:6px;">
                    <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Witness Signature</div>
                    <canvas id="witness-signature-pad"
                            style="width:100%;height:160px;border:1.5px solid #e5e7eb;border-radius:10px;background:#ffffff;display:block;touch-action:none;cursor:crosshair;"
                            width="600" height="160"></canvas>
                    <input type="hidden" name="witness_signature_data" id="witness_signature_data" value="">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                        <span style="font-size:11px;color:#9ca3af;">Witness should sign inside the box above</span>
                        <button type="button" id="clear-witness-signature"
                                style="font-size:11px;color:#6b7280;text-decoration:underline;background:none;border:none;cursor:pointer;padding:0;">
                            Clear witness signature
                        </button>
                    </div>
                </div>
            </div>

            <div class="sheet-actions">
                <button type="button" class="btn btn-cancel" onclick="closeSheet('approve')">Cancel</button>
                <button type="submit" id="approve-submit-btn" class="btn btn-confirm-approve" disabled>Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

{{-- Request changes sheet --}}
<div class="overlay" id="changesSheet">
    <div class="sheet">
        <div class="sheet-title">✍️ Request Changes</div>
        <div class="sheet-sub">
            Describe what you would like changed. The Chabrin team will update the lease and send you a new approval link.
        </div>
        <form method="POST" action="{{ route('landlord.public.action', $approval->token) }}">
            @csrf
            <input type="hidden" name="action" value="request_changes">
            <label class="field-label">What would you like changed? <span style="color:#ef4444;">*</span></label>
            <textarea name="changes_comments" rows="5" placeholder="e.g. Clause 4: Please increase the notice period from 1 month to 2 months.&#10;Clause 7: The deposit amount should be KES 60,000 not 50,000." required></textarea>
            <div class="field-hint">Be as specific as possible — mention the clause or section where applicable.</div>
            <div class="sheet-actions">
                <button type="button" class="btn btn-cancel" onclick="closeSheet('changes')">Cancel</button>
                <button type="submit" class="btn btn-confirm-changes">Submit Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject sheet --}}
<div class="overlay" id="rejectSheet">
    <div class="sheet">
        <div class="sheet-title">❌ Reject Lease</div>
        <div class="sheet-sub">Please tell us why you are rejecting this lease. The Chabrin team will be notified.</div>
        <form method="POST" action="{{ route('landlord.public.action', $approval->token) }}">
            @csrf
            <input type="hidden" name="action" value="reject">
            <label class="field-label">Reason for Rejection <span style="color:#ef4444;">*</span></label>
            <textarea name="rejection_reason" rows="3" placeholder="e.g. Incorrect rent amount, wrong property details..." required></textarea>
            <div class="field-hint" style="margin-bottom:14px;">Required — the team needs to know what to fix.</div>
            <label class="field-label">Additional Comments (optional)</label>
            <textarea name="comments" rows="2" placeholder="Any extra notes..."></textarea>
            <div class="sheet-actions">
                <button type="button" class="btn btn-cancel" onclick="closeSheet('reject')">Cancel</button>
                <button type="submit" class="btn btn-confirm-reject">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
let landlordOtpVerified = "{{ $otpVerified ? 'true' : 'false' }}" === 'true';
function openSheet(type) {
    document.getElementById(type + 'Sheet').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSheet(type) {
    document.getElementById(type + 'Sheet').classList.remove('active');
    document.body.style.overflow = '';
}
function toggleButtons() {
    const checked = document.getElementById('readCheck').checked;
    const approveBtn = document.getElementById('btnApprove');
    const changesBtn = document.getElementById('btnChanges');
    const rejectBtn = document.getElementById('btnReject');
    if (approveBtn) {
        approveBtn.disabled = !checked || !landlordOtpVerified;
    }
    if (changesBtn) {
        changesBtn.disabled = !checked;
    }
    if (rejectBtn) {
        rejectBtn.disabled = !checked;
    }
}

// Close on overlay click
document.querySelectorAll('.overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Initialize landlord + witness signature pads when approve sheet opens
let landlordSigPad = null;
let witnessSigPad = null;

function initLandlordPads() {
    if (landlordSigPad && witnessSigPad) return;
    const landlordCanvas = document.getElementById('signature-pad');
    const witnessCanvas = document.getElementById('witness-signature-pad');
    const approveBtn = document.getElementById('approve-submit-btn');

    if (!landlordCanvas || !witnessCanvas || !approveBtn || typeof SignaturePad === 'undefined') {
        return;
    }

    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const landlordW = landlordCanvas.offsetWidth || 600;
    const witnessW  = witnessCanvas.offsetWidth  || 600;

    landlordCanvas.width = landlordW * ratio;
    landlordCanvas.height = 160 * ratio;
    landlordCanvas.getContext('2d').scale(ratio, ratio);

    witnessCanvas.width = witnessW * ratio;
    witnessCanvas.height = 160 * ratio;
    witnessCanvas.getContext('2d').scale(ratio, ratio);

    landlordSigPad = new SignaturePad(landlordCanvas, {
        backgroundColor: 'rgb(255,255,255)',
        penColor: 'rgb(0,0,0)',
        minWidth: 0.5,
        maxWidth: 2.5,
    });
    witnessSigPad = new SignaturePad(witnessCanvas, {
        backgroundColor: 'rgb(255,255,255)',
        penColor: 'rgb(0,0,0)',
        minWidth: 0.5,
        maxWidth: 2.5,
    });

    landlordSigPad.addEventListener('endStroke', updateApproveState);
    witnessSigPad.addEventListener('endStroke', updateApproveState);

    const clearWitnessBtn = document.getElementById('clear-witness-signature');
    if (clearWitnessBtn) {
        clearWitnessBtn.addEventListener('click', function () {
            witnessSigPad.clear();
            document.getElementById('witness_signature_data').value = '';
            updateApproveState();
        });
    }

    ['lessor-witness-name', 'lessor-witness-id'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateApproveState);
        }
    });
}

function updateApproveState() {
    const btn = document.getElementById('approve-submit-btn');
    if (!btn) return;
    const witnessName = document.getElementById('lessor-witness-name')?.value.trim() || '';
    const witnessId = document.getElementById('lessor-witness-id')?.value.trim() || '';
    const landlordOk = landlordSigPad && !landlordSigPad.isEmpty();
    const witnessOk = witnessSigPad && !witnessSigPad.isEmpty() && witnessName !== '' && witnessId !== '';
    btn.disabled = !(landlordOk && witnessOk);
}

document.getElementById('btnApprove')?.addEventListener('click', function () {
    setTimeout(initLandlordPads, 400);
});

document.getElementById('approve-form')?.addEventListener('submit', function (e) {
    // Populate hidden fields first so data is always sent
    if (landlordSigPad && !landlordSigPad.isEmpty()) {
        document.getElementById('signature_data').value = landlordSigPad.toDataURL('image/png');
    }
    if (witnessSigPad && !witnessSigPad.isEmpty()) {
        document.getElementById('witness_signature_data').value = witnessSigPad.toDataURL('image/png');
    }
    // Then validate
    const sigData = document.getElementById('signature_data').value;
    const witData = document.getElementById('witness_signature_data').value;
    if (!sigData || !witData) {
        e.preventDefault();
        alert('Please sign as landlord and ask your in-person witness to sign before confirming.');
    }
});

window.addEventListener('pageshow', function(event) {
    if (event.persisted) { window.location.reload(); }
});

// ── OTP JS ──
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const requestBtn = document.getElementById('otp-request-btn');
    const verifyBtn = document.getElementById('otp-verify-btn');
    const codeInput = document.getElementById('otp-code-input');
    const msgEl = document.getElementById('otp-message');
    const rowEl = document.getElementById('otp-input-row');

    if (!requestBtn || !csrf) {
        return;
    }

    function showOtpMessage(kind, text) {
        if (!msgEl) return;
        msgEl.style.display = 'block';
        msgEl.style.color = kind === 'success' ? '#166534' : '#b91c1c';
        msgEl.textContent = text;
    }

            requestBtn.addEventListener('click', async function () {
        requestBtn.disabled = true;
        requestBtn.textContent = 'Sending...';
        showOtpMessage('info', '');
            try {
                const resp = await fetch("{{ route('landlord.public.request-otp', $approval->token) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                if (rowEl) rowEl.style.display = 'flex';
                requestBtn.textContent = 'Resend code';
                showOtpMessage('success', data.message || 'Code sent.');
            } else {
                requestBtn.disabled = false;
                requestBtn.textContent = 'Send verification code';
                showOtpMessage('error', data.message || 'Could not send code.');
            }
        } catch (e) {
            requestBtn.disabled = false;
            requestBtn.textContent = 'Send verification code';
            showOtpMessage('error', 'Network error. Please try again.');
        }
    });

    if (verifyBtn && codeInput) {
        verifyBtn.addEventListener('click', async function () {
            const code = codeInput.value.trim();
            if (code.length !== 6) {
                showOtpMessage('error', 'Enter the 6-digit code sent to your phone.');
                return;
            }
            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Verifying...';
            try {
                const resp = await fetch("{{ route('landlord.public.verify-otp', $approval->token) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ code })
                });
                const data = await resp.json();
                if (data.success) {
                    landlordOtpVerified = true;
                    const otpSection = document.getElementById('otp-section');
                    if (otpSection) otpSection.style.display = 'none';
                    showOtpMessage('success', data.message || 'Phone verified.');
                    toggleButtons();
                } else {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';
                    showOtpMessage('error', data.message || 'Invalid code.');
                }
            } catch (e) {
                verifyBtn.disabled = false;
                verifyBtn.textContent = 'Verify';
                showOtpMessage('error', 'Network error. Please try again.');
            }
        });
    }
});

</script>
</body>
</html>
