<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Captured from the captive portal's checkout request ($mac query param RouterOS
            // supplies) so the resulting HotspotUser can be MAC-bound immediately at purchase,
            // instead of waiting on the async radacct backfill (SyncHotspotConnectionInfo).
            $table->string('mac_address')->nullable()->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('mac_address');
        });
    }
};
