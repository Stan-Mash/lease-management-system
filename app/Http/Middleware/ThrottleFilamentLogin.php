<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit Filament admin login attempts to mitigate brute force.
 *
 * Two independent limiters run in parallel:
 *  1. Per-IP limiter   — 10 attempts per minute per IP address.
 *                        Stops distributed attacks from a single source.
 *  2. Per-email limiter — 10 attempts per 15 minutes per email address.
 *                        Stops credential-stuffing across multiple IPs
 *                        targeting the same account (account lockout).
 *
 * Both limiters must clear before a POST is passed to the auth handler.
 * All blocked attempts are logged at WARNING level for SIEM/alerting.
 *
 * Applies only to POST requests to the admin login path.
 */
class ThrottleFilamentLogin
{
    public const LIMITER_NAME = 'filament-login';

    /** Max attempts per IP per minute */
    private const IP_MAX_ATTEMPTS = 10;

    private const IP_DECAY_SECONDS = 60;

    /** Max attempts per email per 15-minute window (account lockout) */
    private const EMAIL_MAX_ATTEMPTS = 10;

    private const EMAIL_DECAY_SECONDS = 900; // 15 minutes

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->is('admin/login')) {
            return $next($request);
        }

        $ip = $request->ip();
        $email = (string) ($request->input('email') ?? '');
        $emailHash = hash('sha256', mb_strtolower(trim($email)));

        $ipKey = self::LIMITER_NAME . ':ip:' . $ip;
        $emailKey = self::LIMITER_NAME . ':email:' . $emailHash;

        // 1. Check per-IP rate limit
        if (RateLimiter::tooManyAttempts($ipKey, self::IP_MAX_ATTEMPTS)) {
            $retryAfter = RateLimiter::availableIn($ipKey);
            Log::warning('Admin login blocked: IP rate limit exceeded', [
                'ip' => $ip,
                'retry_after' => $retryAfter,
            ]);

            return redirect()->route('filament.admin.auth.login')
                ->with('error', "Too many login attempts from this IP. Try again in {$retryAfter} seconds.")
                ->withInput($request->only('email'));
        }

        // 2. Check per-email account lockout
        if ($email !== '' && RateLimiter::tooManyAttempts($emailKey, self::EMAIL_MAX_ATTEMPTS)) {
            $retryAfter = RateLimiter::availableIn($emailKey);
            Log::warning('Admin login blocked: account lockout (too many attempts for email)', [
                'ip' => $ip,
                'email_hash' => $emailHash,  // hash only — never log plaintext email in security logs
                'retry_after' => $retryAfter,
            ]);

            return redirect()->route('filament.admin.auth.login')
                ->with('error', "Account temporarily locked due to too many failed attempts. Try again in {$retryAfter} seconds.")
                ->withInput($request->only('email'));
        }

        // Increment both counters before passing the request through.
        // Filament will decrement / clear on success (via Authenticate event).
        // If auth fails, both counters increment naturally.
        RateLimiter::hit($ipKey, self::IP_DECAY_SECONDS);

        if ($email !== '') {
            RateLimiter::hit($emailKey, self::EMAIL_DECAY_SECONDS);
        }

        $response = $next($request);

        // On a successful authentication (redirect away from login page),
        // clear both limiters so a legitimate user is not locked out.
        if ($response->isRedirect() && ! str_contains((string) $response->headers->get('Location', ''), 'login')) {
            RateLimiter::clear($ipKey);

            if ($email !== '') {
                RateLimiter::clear($emailKey);
            }
        }

        return $response;
    }
}
