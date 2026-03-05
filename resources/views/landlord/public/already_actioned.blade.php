<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Already Actioned — Chabrin Agencies</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f0e8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; }
        .topbar { background: #1a365d; width: 100%; max-width: 420px; border-radius: 16px 16px 0 0; padding: 16px 20px; display: flex; align-items: center; gap: 12px; }
        .topbar-logo { width: 32px; height: 32px; background: #DAA520; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 900; color: #1a365d; }
        .topbar-name { color: #fff; font-size: 14px; font-weight: 700; }
        .card { background: #fff; width: 100%; max-width: 420px; border-radius: 0 0 16px 16px; padding: 36px 28px 40px; text-align: center; box-shadow: 0 4px 24px rgba(26,54,93,.12); }
        .icon { font-size: 52px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: 800; color: #1a365d; margin-bottom: 8px; }
        .body  { font-size: 14px; color: #6b7280; line-height: 1.6; }
        .badge { display: inline-block; margin-top: 20px; padding: 8px 20px; border-radius: 9999px; font-size: 13px; font-weight: 700; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .meta { margin-top: 16px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-logo">C</div>
        <div class="topbar-name">Chabrin Agencies</div>
    </div>
    <div class="card">
        @if($approval->isApproved())
            <div class="icon">✅</div>
            <div class="title">Lease Already Approved</div>
            <div class="body">You have already approved lease <strong>{{ $approval->lease->reference_number }}</strong>. The Chabrin team has been notified and will proceed with next steps.</div>
            <div class="badge badge-approved">Approved</div>
            @if($approval->reviewed_at)
                <div class="meta">Actioned on {{ $approval->reviewed_at->format('d M Y, g:i A') }}</div>
            @endif
        @else
            <div class="icon">❌</div>
            <div class="title">Lease Already Rejected</div>
            <div class="body">You have already rejected lease <strong>{{ $approval->lease->reference_number }}</strong>. The Chabrin team will contact you to discuss next steps.</div>
            <div class="badge badge-rejected">Rejected</div>
            @if($approval->reviewed_at)
                <div class="meta">Actioned on {{ $approval->reviewed_at->format('d M Y, g:i A') }}</div>
            @endif
        @endif
    </div>
</body>
</html>
