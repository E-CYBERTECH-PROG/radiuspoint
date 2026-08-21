<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vouchers are hotspot_users rows under the hood (VoucherController::generate() creates
 * them with status 'unused'), but once redeemed they flip to 'active' just like any other
 * hotspot customer — indistinguishable from one at that point by status alone. This flag is
 * what lets the redesigned Vouchers table scope to voucher-issued rows specifically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->boolean('is_voucher')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->dropColumn('is_voucher');
        });
    }
};
