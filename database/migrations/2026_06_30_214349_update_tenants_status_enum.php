<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE tenants SET status = 'active' WHERE status NOT IN ('active', 'suspended')");

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        } else {
            DB::statement("ALTER TABLE tenants MODIFY status ENUM('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE tenants SET status = 'active' WHERE status NOT IN ('active', 'suspended')");

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        } else {
            DB::statement("ALTER TABLE tenants MODIFY status ENUM('active', 'suspended') NOT NULL DEFAULT 'active'");
        }
    }
};
