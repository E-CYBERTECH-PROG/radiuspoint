<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every timestamp in the app (transaction times, "expires in X", notification "created X ago")
 * runs through Carbon::now()/now(), which follows PHP's default timezone — previously hardcoded
 * to UTC in config/app.php regardless of where the tenant actually operates. This applies the
 * authenticated tenant's own timezone (set on Company Settings) for the lifetime of the request,
 * so every one of those calls is correct without touching each call site individually.
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
