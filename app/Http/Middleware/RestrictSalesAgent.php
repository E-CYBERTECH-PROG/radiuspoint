<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the Sales Agent role from router, team, and tenant settings routes. Applied at the
 * route-group level (routes/web.php) so the gated routes stay in one place.
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
