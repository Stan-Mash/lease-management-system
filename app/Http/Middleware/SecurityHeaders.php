<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies OWASP-recommended HTTP security headers to every response.
 *
 * Content-Security-Policy uses a per-request cryptographic nonce instead of
 * unsafe-inline for scripts. Styles use nonce + unsafe-inline because the
 * login page relies heavily on inline style="" attributes which cannot carry
 * nonces per the CSP specification.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a unique cryptographic nonce for every request.
        $nonce = base64_encode(random_bytes(16));

        // Share with all Blade views so templates can attach it to inline scripts.
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        $isProduction = app()->isProduction();

        // script-src: nonce-based only (no unsafe-inline — strongest JS protection)
        $scriptSrc  = "'self' 'nonce-{$nonce}'";
        // style-src: nonce for <style> tags + unsafe-inline for style="" attributes
        // (inline style attributes cannot carry nonces per CSP spec)
        $styleSrc   = "'self' 'nonce-{$nonce}' 'unsafe-inline'";
        $connectSrc = "'self'";

        if (! $isProduction) {
            $scriptSrc  .= " 'unsafe-eval' http://localhost:* ws://localhost:*";
            $styleSrc   .= ' https://cdn.tailwindcss.com https://cdn.jsdelivr.net';
            $connectSrc .= ' ws://localhost:* http://localhost:*';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src {$connectSrc}",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        if ($isProduction) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
