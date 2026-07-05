<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE hotspot_users MODIFY status ENUM('unused', 'active', 'expired', 'offline') DEFAULT 'offline'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE hotspot_users MODIFY status ENUM('active', 'expired', 'offline') DEFAULT 'offline'");
    }
};
