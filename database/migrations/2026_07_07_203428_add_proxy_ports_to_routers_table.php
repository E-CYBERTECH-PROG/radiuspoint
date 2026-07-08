<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            // Unique TCP ports on this server's own public IP, forwarded via nginx's stream{}
            // module through the WireGuard tunnel to this router's real web/Winbox ports —
            // see App\Console\Commands\ReconcileNetworking. The router's raw VPN IP is only ever
            // reachable from this server itself, never from an admin's own computer.
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
