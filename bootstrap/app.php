<?php

use App\Http\Middleware\ApplyTenantTimezone;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantIsApproved;
use App\Http\Middleware\EnsureTenantSubscribed;
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
            'tenant.subscribed' => EnsureTenantSubscribed::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'restrict.sales-agent' => RestrictSalesAgent::class,
            'tenant.timezone' => ApplyTenantTimezone::class,
        ]);

        // Trust the X-Forwarded-Proto header from Cloudflare, which can talk plain HTTP to
        // the origin even when the visitor's browser sees HTTPS — without this, route()/url()
        // generate http:// links on an https:// page, which browsers block as mixed content.
        // '*' is safe here since nginx is the only thing reaching PHP-FPM.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
