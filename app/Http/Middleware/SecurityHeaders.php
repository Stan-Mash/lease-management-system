<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies OWASP-recommended HTTP security headers to every response.
 *
 * CSP uses unsafe-inline and unsafe-eval throughout because:
 * - Filament 4 injects bare <script> and <style> blocks from vendor views
 * - Alpine.js evaluates x-bind/x-on expressions via new Function()
 * - The nonce is still generated and shared so views can use it optionally.
 *
 * This matches the original working CSP before the nonce-hardening experiment.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a nonce and share with views (used by login page style/script tags).
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        $isProduction = app()->isProduction();

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net",
            "connect-src 'self'" . ($isProduction ? '' : ' ws://localhost:* http://localhost:*'),
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        // The landlord document endpoint is intentionally embedded in an iframe on the
        // approval page (same origin). Allow framing for that specific route only.
        $isLandlordDocument = $request->routeIs('landlord.public.document');

        if ($isLandlordDocument) {
            $csp = str_replace("frame-ancestors 'none'", "frame-ancestors 'self'", $csp);
        }

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $isLandlordDocument ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), bluetooth=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        if ($isProduction) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=63072000; includeSubDomains; preload',
            );
        }

        // Remove server fingerprinting headers.
        // $response->headers->remove() only removes headers set by Laravel/Symfony.
        // PHP's SAPI (built-in server, Apache mod_php, FPM) sets X-Powered-By at the
        // C level before Laravel runs. header_remove() operates on PHP's own header list
        // and is the only way to suppress the SAPI-injected X-Powered-By header.
        // ini_set('expose_php', '0') is the canonical fix, but header_remove() works at
        // runtime without needing php.ini access.
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
            header_remove('Server');
        }
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
