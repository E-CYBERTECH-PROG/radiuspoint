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
        Schema::table('captive_portals', function (Blueprint $table) {
            // Null keeps today's responsive auto-fit grid; 1-4 pins a fixed column count.
            $table->unsignedTinyInteger('columns_per_row')->nullable()->after('primary_color');
            $table->boolean('show_speed')->default(true)->after('columns_per_row');
            $table->boolean('show_navbar')->default(false)->after('show_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('captive_portals', function (Blueprint $table) {
            $table->dropColumn(['columns_per_row', 'show_speed', 'show_navbar']);
        });
    }
};
