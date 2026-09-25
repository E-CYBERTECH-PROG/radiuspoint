<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an admin credit/debit a registered (non-voucher) customer's prepaid balance directly —
 * separate from a real M-Pesa transaction. `notes` records why, surfaced in the same
 * transaction-history list the customer detail page already shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('status');
        });

        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->decimal('balance', 10, 2)->default(0)->after('status');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('notes')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
