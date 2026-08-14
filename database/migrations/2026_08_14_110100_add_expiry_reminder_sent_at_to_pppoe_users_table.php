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
        Schema::table('pppoe_users', function (Blueprint $table) {
            // Same "already acted on this cycle" pattern as fup_throttled_at — prevents
            // App\Console\Commands\SendExpiryReminders from re-sending on every daily run, and
            // gets cleared on renewal so the next cycle re-arms it.
            $table->timestamp('expiry_reminder_sent_at')->nullable()->after('fup_throttled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->dropColumn('expiry_reminder_sent_at');
        });
    }
};
