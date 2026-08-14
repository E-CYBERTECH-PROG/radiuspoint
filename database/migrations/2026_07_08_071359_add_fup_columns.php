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
        Schema::table('plans', function (Blueprint $table) {
            // Throttled speed once a customer exceeds data_cap_mb; null means no FUP for this plan.
            $table->string('fup_speed_limit')->nullable()->after('data_cap_mb');
        });

        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->timestamp('fup_throttled_at')->nullable()->after('expires_at');
        });

        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->timestamp('fup_throttled_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('fup_speed_limit');
        });

        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->dropColumn('fup_throttled_at');
        });

        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->dropColumn('fup_throttled_at');
        });
    }
};
