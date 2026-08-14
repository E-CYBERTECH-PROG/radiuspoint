<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Captured at STK-push time (pay() always knows which router's captive portal the
            // customer paid through) so BillingController::activateHotspotUser() can carry it
            // onto the resulting HotspotUser — previously lost entirely by the time the async
            // M-Pesa callback fires, silently leaving every self-service customer's
            // current_router_id null and breaking any per-router revenue/usage reporting.
            $table->foreignId('router_id')->nullable()->after('plan_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('router_id');
        });
    }
};
