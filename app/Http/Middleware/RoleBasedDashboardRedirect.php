<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedDashboardRedirect
{
    /**
     * Redirect users to their role-appropriate dashboard when they hit the home URL.
     * Only applies to the exact admin home route (not sub-pages).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only intercept redirects to the home URL
        if (!$response instanceof \Illuminate\Http\RedirectResponse) {
            return $response;
        }

        $targetUrl = $response->getTargetUrl();
        if (!str_ends_with(parse_url($targetUrl, PHP_URL_PATH) ?? '', '/admin/company-dashboard')) {
            return $response;
        }

        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $dashboard = match (true) {
            $user->isFieldOfficer() => '/admin/field-officer-dashboard',
            $user->isZoneManager() => '/admin/zone-dashboard',
            default => '/admin/company-dashboard',
        };

        if ($dashboard !== '/admin/company-dashboard') {
            return redirect($dashboard);
        }

        return $response;
    }
}
