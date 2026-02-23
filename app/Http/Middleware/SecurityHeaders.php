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
 * 'unsafe-inline', which would allow any injected inline script to execute.
 * The nonce is shared with Blade views via view()->share('cspNonce', ...) so
 * inline scripts can opt in with: <script nonce="{{ $cspNonce }}">...</script>
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a unique cryptographic nonce for every request.
        // Per CSP spec the value must be base64-encoded random bytes.
        $nonce = base64_encode(random_bytes(16));

        // Share with all Blade views so templates can attach it to inline scripts.
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        $isProduction = app()->isProduction();

        // script-src: nonce-based; eval only allowed in local dev for Vite
        $scriptSrc = "'self' 'nonce-{$nonce}'";
        $styleSrc  = "'self' 'nonce-{$nonce}'";
        $connectSrc = "'self'";

        if (! $isProduction) {
            // Local development needs: Vite HMR (eval + WS), Tailwind CDN
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
            "frame-ancestors 'none'",  // Stronger: blocks framing from ANY origin
            "form-action 'self'",      // Prevent form submissions to external domains
            "base-uri 'self'",         // Prevent <base href="..."> injection attacks
            "object-src 'none'",       // Block Flash, Java applets, ActiveX
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS: only in production — breaks local dev when served over plain HTTP
        if ($isProduction) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
