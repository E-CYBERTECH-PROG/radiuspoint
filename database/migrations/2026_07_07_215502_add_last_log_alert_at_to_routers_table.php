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
        Schema::table('routers', function (Blueprint $table) {
            // Tracks how far the critical-log scanner has already alerted on, so the same
            // RouterOS log line (e.g. a bridge-loop-detection message) isn't re-alerted every
            // minute for as long as it stays in the router's own log buffer.
            $table->timestamp('last_log_alert_at')->nullable()->after('last_seen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn('last_log_alert_at');
        });
    }
};
