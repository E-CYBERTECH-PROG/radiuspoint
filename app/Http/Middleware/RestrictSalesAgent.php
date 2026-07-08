<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the Sales Agent role from router control/settings, team management, and tenant-level
 * settings routes. Applied at the route-group level (routes/web.php) rather than scattered
 * abort_unless() calls per controller, so the full set of gated routes stays auditable in one
 * place. SuperAdmin/Admin/Technician are unaffected — their current (unrestricted) behavior is
 * left as-is since nothing asked for that boundary to change.
 */
class RestrictSalesAgent
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()->role === 'Sales Agent', 403);

        return $next($request);
    }
}
