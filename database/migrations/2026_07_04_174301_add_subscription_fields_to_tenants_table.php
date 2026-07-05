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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_tier')->default('free')->after('status');
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'cancelled'])->default('trial')->after('subscription_tier');
            $table->dateTime('subscription_expires_at')->nullable()->after('subscription_status');
            $table->text('admin_notes')->nullable()->after('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_tier', 'subscription_status', 'subscription_expires_at', 'admin_notes']);
        });
    }
};
