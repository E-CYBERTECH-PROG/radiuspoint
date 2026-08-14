<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a tenant configure two independent M-Pesa gateways (e.g. two different Till/Paybill
 * numbers) — slot 1 is tried first for every STK push, slot 2 is only used automatically if
 * slot 1 fails to initiate. Existing single-gateway rows default to slot 1 (primary), so nothing
 * changes for a tenant who never sets up a second one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('slot')->default(1)->after('tenant_id');
            $table->unique(['tenant_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'slot']);
            $table->dropColumn('slot');
        });
    }
};
