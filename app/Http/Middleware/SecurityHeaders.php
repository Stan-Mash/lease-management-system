<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies OWASP-recommended HTTP security headers to every response.
 *
 * Content-Security-Policy uses unsafe-inline for both script-src and style-src
 * because Filament 4 injects bare inline <script> blocks (localStorage theme,
 * window.filamentData) from vendor layout views that cannot receive nonces.
 * Without unsafe-inline those scripts are CSP-blocked, breaking Livewire/Alpine.
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

        // script-src: unsafe-inline + unsafe-eval required for Filament/Alpine to work.
        // Filament injects bare <script> blocks from vendor views (needs unsafe-inline).
        // Alpine.js evaluates x-bind/x-on expressions via Function() — needs unsafe-eval.
        $scriptSrc  = "'self' 'unsafe-inline' 'unsafe-eval' 'nonce-{$nonce}'";
        // style-src: unsafe-inline for style="" attributes (cannot carry nonces per CSP spec)
        $styleSrc   = "'self' 'unsafe-inline' 'nonce-{$nonce}'";
        $connectSrc = "'self'";

        if (! $isProduction) {
            $scriptSrc  .= " http://localhost:* ws://localhost:*";
            $styleSrc   .= ' https://cdn.tailwindcss.com https://cdn.jsdelivr.net';
            $connectSrc .= ' ws://localhost:* http://localhost:*';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            // Google Fonts CSS is loaded via @import in theme.css — needs googleapis.com
            "style-src {$styleSrc} https://fonts.googleapis.com",
            "img-src 'self' data: blob: https:",
            // Google Fonts actual font files served from gstatic.com
            "font-src 'self' data: https://fonts.gstatic.com",
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
