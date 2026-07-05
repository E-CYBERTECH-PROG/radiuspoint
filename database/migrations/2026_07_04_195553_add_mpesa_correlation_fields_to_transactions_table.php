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
            $table->string('checkout_request_id')->nullable()->unique()->after('mpesa_receipt');
            $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('merchant_request_id');
            $table->foreignId('hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete()->after('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotspot_user_id');
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['checkout_request_id', 'merchant_request_id']);
        });
    }
};
