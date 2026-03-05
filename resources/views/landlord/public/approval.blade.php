<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Lease Approval — {{ $approval->lease->reference_number }}</title>
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
        .doc-scroll {
            background: #fff;
            border: 1.5px solid #dde3ed;
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 520px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .doc-scroll::-webkit-scrollbar { width: 6px; }
        .doc-scroll::-webkit-scrollbar-thumb { background: #c4c9d4; border-radius: 3px; }
        .doc-inner {
            padding: 28px 24px;
            font-size: 13px;
            line-height: 1.75;
            color: #1a1a2e;
        }
        /* Styles for rendered lease content */
        .doc-inner h1 { font-size: 17px; font-weight: 800; margin: 0 0 16px; text-align: center; }
        .doc-inner h2 { font-size: 14px; font-weight: 700; margin: 18px 0 8px; }
        .doc-inner h3 { font-size: 13px; font-weight: 700; margin: 14px 0 6px; }
        .doc-inner p  { margin-bottom: 10px; }
        .doc-inner table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 12px; }
        .doc-inner table td, .doc-inner table th { border: 1px solid #dde3ed; padding: 7px 10px; vertical-align: top; }
        .doc-inner table th { background: #f0f4f8; font-weight: 700; }
        .doc-inner ul, .doc-inner ol { padding-left: 20px; margin-bottom: 10px; }
        .doc-inner li { margin-bottom: 4px; }
        .doc-no-template {
            text-align: center; padding: 40px 20px; color: #9ca3af;
        }
        .doc-no-template .icon { font-size: 40px; margin-bottom: 10px; }
        .doc-no-template p { font-size: 13px; }
        .doc-scroll-hint {
            text-align: center; padding: 8px 0;
            font-size: 11px; color: #9ca3af;
            background: #f8fafc; border-top: 1px solid #e8ecf2;
            border-radius: 0 0 10px 10px;
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

    {{-- Full lease document --}}
    <div class="doc-section">
        <div class="doc-header">
            <div class="doc-header-title">
                📄 Full Lease Agreement
            </div>
            <span class="doc-header-badge">READ CAREFULLY</span>
        </div>
        <div class="doc-scroll" id="leaseDoc">
            <div class="doc-inner">
                @if($leaseHtml)
                    {!! $leaseHtml !!}
                @else
                    <div class="doc-no-template">
                        <div class="icon">📋</div>
                        <p>The lease document template is not yet assigned for this lease.<br>The key terms are summarised in the card above.</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="doc-scroll-hint" id="scrollHint">↓ Scroll to read the full agreement</div>

        {{-- Read confirmation checkbox --}}
        <div class="read-confirmation" onclick="document.getElementById('readCheck').click()">
            <input type="checkbox" id="readCheck" onchange="toggleButtons()">
            <label for="readCheck">I have read and understood the full lease agreement above</label>
        </div>
    </div>

    {{-- Action buttons (disabled until checkbox ticked) --}}
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
        <form method="POST" action="{{ route('landlord.public.action', $approval->token) }}">
            @csrf
            <input type="hidden" name="action" value="approve">
            <label class="field-label">Comments (optional)</label>
            <textarea name="comments" rows="3" placeholder="e.g. Approved — please ensure deposit is paid before key handover."></textarea>
            <div class="field-hint">Any notes or conditions you'd like to add.</div>
            <div class="sheet-actions">
                <button type="button" class="btn btn-cancel" onclick="closeSheet('approve')">Cancel</button>
                <button type="submit" class="btn btn-confirm-approve">Confirm Approval</button>
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

<script>
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
    document.getElementById('btnApprove').disabled = !checked;
    document.getElementById('btnChanges').disabled = !checked;
    document.getElementById('btnReject').disabled  = !checked;
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

// Hide scroll hint once user has scrolled near the bottom of the document
const docEl = document.getElementById('leaseDoc');
const hint   = document.getElementById('scrollHint');
if (docEl) {
    docEl.addEventListener('scroll', function() {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 40) {
            hint.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
