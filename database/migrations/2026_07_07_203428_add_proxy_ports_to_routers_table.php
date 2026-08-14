<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            // Public ports on this server, forwarded via nginx over WireGuard to the router's web/Winbox ports.
            $table->unsignedInteger('web_proxy_port')->nullable()->unique()->after('wg_synced_at');
            $table->unsignedInteger('winbox_proxy_port')->nullable()->unique()->after('web_proxy_port');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['web_proxy_port', 'winbox_proxy_port']);
        });
    }
};
