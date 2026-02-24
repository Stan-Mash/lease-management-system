<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit Filament admin login attempts to mitigate brute force.
 * Applies only to POST requests to the admin login path.
 */
class ThrottleFilamentLogin
{
    public const LIMITER_NAME = 'filament-login';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->is('admin/login')) {
            return $next($request);
        }

        $key = self::LIMITER_NAME . ':' . $request->ip();
        $maxAttempts = 5;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Too many login attempts. Please try again in a minute.')
                ->withInput($request->only('email'));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
