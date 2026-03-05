<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid Link — Chabrin Agencies</title>
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
        .contact { margin-top: 24px; background: #faf8f4; border-left: 4px solid #DAA520; border-radius: 8px; padding: 14px 18px; text-align: left; font-size: 13px; color: #374151; }
        .contact strong { display: block; margin-bottom: 4px; color: #1a365d; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-logo">C</div>
        <div class="topbar-name">Chabrin Agencies</div>
    </div>
    <div class="card">
        @if($reason === 'expired')
            <div class="icon">⏰</div>
            <div class="title">This Link Has Expired</div>
            <div class="body">The approval link you followed has expired (links are valid for 7 days). Please contact the Chabrin team to request a new one.</div>
        @else
            <div class="icon">🔗</div>
            <div class="title">Invalid Approval Link</div>
            <div class="body">This link is not recognised or may have already been used. Please contact the Chabrin team for assistance.</div>
        @endif
        <div class="contact">
            <strong>Need help? Contact us:</strong>
            Chabrin Agencies — lease management team.<br>
            Reply to the SMS or email you received.
        </div>
    </div>
</body>
</html>
