<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the authenticated tenant's timezone (from Company Settings) for the request,
 * so Carbon::now()/now() calls throughout the app reflect local time.
 */
class ApplyTenantTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Auth::user()?->tenant;

        if ($tenant?->timezone) {
            date_default_timezone_set($tenant->timezone);
            config(['app.timezone' => $tenant->timezone]);
        }

        return $next($request);
    }
}
