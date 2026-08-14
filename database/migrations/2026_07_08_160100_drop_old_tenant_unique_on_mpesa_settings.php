<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The previous migration added a (tenant_id, slot) composite unique but left the original
 * single-column tenant_id unique constraint in place — since that older constraint alone
 * already forbids more than one row per tenant regardless of slot, it made a second (backup)
 * gateway row impossible to insert at all. Caught immediately by an actual save attempt, not
 * left for a real tenant to hit in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->dropUnique('mpesa_settings_tenant_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->unique('tenant_id');
        });
    }
};
