<?php

use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantIsApproved;
use App\Http\Middleware\RestrictSalesAgent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.approved' => EnsureTenantIsApproved::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'restrict.sales-agent' => RestrictSalesAgent::class,
        ]);

        // Nginx only sets $_SERVER['HTTPS'] when the connection reaching THIS server was itself
        // TLS — but Cloudflare (sitting in front) can talk plain HTTP to the origin even when the
        // visitor's own browser sees HTTPS (its default "Flexible" SSL mode). Without trusting
        // the proxy's X-Forwarded-Proto header, Laravel has no way to know the original request
        // was secure, so route()/url() generate http:// links on an https:// page — which
        // browsers silently block as mixed content. That's what caused "Failed to fetch" on the
        // router provisioning page's AJAX call with no server-side error at all (the request
        // never left the browser). '*' trusts whatever connects directly to nginx, which for
        // this server is only ever Cloudflare or local/direct testing — appropriate since nginx
        // itself is the sole thing reaching PHP-FPM.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
