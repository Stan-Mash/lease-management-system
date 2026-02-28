<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password – Chabrin Lease System</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f3f4f6; padding: 1.5rem; }
        .card { max-width: 420px; width: 100%; background: #fff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,.07), 0 20px 40px rgba(0,0,0,.1); border-top: 3px solid #DAA520; }
        h1 { font-size: 1.35rem; font-weight: 700; color: #1f2937; margin: 0 0 1rem; }
        p { font-size: .9375rem; font-weight: 500; color: #4b5563; line-height: 1.6; margin: 0 0 1.5rem; }
        a.btn { display: inline-block; padding: .6rem 1.25rem; background: linear-gradient(180deg, #e8b923 0%, #b8860b 100%); color: #fff; font-weight: 700; text-decoration: none; border-radius: 8px; font-size: .9375rem; }
        a.btn:hover { opacity: .95; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Reset your password</h1>
        <p>To reset your password, please contact your system administrator or IT support. They can reset your account and ensure secure access.</p>
        <a href="{{ route('filament.admin.auth.login') }}" class="btn">Back to Sign in</a>
    </div>
</body>
</html>
