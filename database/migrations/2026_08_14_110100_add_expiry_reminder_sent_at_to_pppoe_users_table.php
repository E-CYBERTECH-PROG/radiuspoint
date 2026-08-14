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
            // Prevents SendExpiryReminders from re-sending daily; cleared on renewal.
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
