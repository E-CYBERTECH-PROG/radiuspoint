<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * A separate token from public_token, which is permanent and baked into every router's
     * live walled-garden/login.html config for the captive portal and payment portal —
     * rotating that one would break customer-facing service on every already-deployed router
     * until an admin re-pushed config. setup_token is only ever used for the one-time bootstrap
     * script fetch, so it's safe to expire and regenerate without touching anything live.
     */
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('setup_token')->nullable()->unique()->after('public_token');
            $table->timestamp('setup_token_expires_at')->nullable()->after('setup_token');
        });

        foreach (DB::table('routers')->whereNull('setup_token')->select('id')->get() as $router) {
            DB::table('routers')->where('id', $router->id)->update([
                'setup_token' => Str::random(40),
                'setup_token_expires_at' => now()->addDays(7),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['setup_token', 'setup_token_expires_at']);
        });
    }
};
