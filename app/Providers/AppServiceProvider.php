<?php

namespace App\Providers;

use App\Services\MikrotikApiService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Explicit (matches default container resolution — a fresh instance per call, never
        // shared) so tests can swap in a fake via $this->instance(MikrotikApiService::class, ...)
        // without every router-facing call site needing its own DI seam.
        $this->app->bind(MikrotikApiService::class, MikrotikApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }
}
