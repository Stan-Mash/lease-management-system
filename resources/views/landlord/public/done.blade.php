<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $action === 'approved' ? 'Lease Approved' : 'Lease Rejected' }} — Chabrin Agencies</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f0e8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; }
        .topbar { background: #1a365d; width: 100%; max-width: 420px; border-radius: 16px 16px 0 0; padding: 16px 20px; display: flex; align-items: center; gap: 12px; }
        .topbar-logo { width: 32px; height: 32px; background: #DAA520; border-radius: 7px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 900; color: #1a365d; }
        .topbar-name { color: #fff; font-size: 14px; font-weight: 700; }
        .card {
            background: #fff; width: 100%; max-width: 420px;
            border-radius: 0 0 16px 16px;
            padding: 40px 28px 44px;
            text-align: center;
            box-shadow: 0 4px 24px rgba(26,54,93,.12);
        }
        .icon { font-size: 64px; margin-bottom: 20px; animation: pop .4s cubic-bezier(.36,.07,.19,.97); }
        @keyframes pop { 0%{transform:scale(0)} 80%{transform:scale(1.15)} 100%{transform:scale(1)} }
        .title { font-size: 22px; font-weight: 800; color: #1a365d; margin-bottom: 10px; }
        .body  { font-size: 14px; color: #6b7280; line-height: 1.7; }
        .ref   { display: inline-block; margin-top: 20px; background: #faf8f4; border: 1.5px solid rgba(218,165,32,.35); border-left: 4px solid #DAA520; border-radius: 8px; padding: 12px 18px; font-size: 13px; font-weight: 700; color: #1a365d; }
        .note  { margin-top: 24px; font-size: 12px; color: #9ca3af; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-logo">C</div>
        <div class="topbar-name">Chabrin Agencies</div>
    </div>
    <div class="card">
        @if($action === 'approved')
            <div class="icon">🎉</div>
            <div class="title">Lease Approved!</div>
            <div class="body">
                Thank you. You have successfully approved the lease for <strong>{{ $tenant }}</strong>.
                The Chabrin team has been notified and will proceed with the next steps immediately.
            </div>
        @else
            <div class="icon">👍</div>
            <div class="title">Rejection Recorded</div>
            <div class="body">
                Thank you for your feedback. The lease for <strong>{{ $tenant }}</strong> has been rejected
                and the Chabrin team has been notified. They will contact you to resolve any issues.
            </div>
        @endif
        <div class="ref">Ref: {{ $reference }}</div>
        <div class="note">You may now close this page. This link has been invalidated for security.</div>
    </div>
</body>
</html>
