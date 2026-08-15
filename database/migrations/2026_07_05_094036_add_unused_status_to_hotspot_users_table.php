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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('hotspot_users', function (Blueprint $table) {
                $table->string('status')->default('offline')->change();
            });
        } else {
            DB::statement("ALTER TABLE hotspot_users MODIFY status ENUM('unused', 'active', 'expired', 'offline') DEFAULT 'offline'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('hotspot_users', function (Blueprint $table) {
                $table->string('status')->default('offline')->change();
            });
        } else {
            DB::statement("ALTER TABLE hotspot_users MODIFY status ENUM('active', 'expired', 'offline') DEFAULT 'offline'");
        }
    }
};
