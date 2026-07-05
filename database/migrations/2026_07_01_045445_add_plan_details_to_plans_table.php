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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('name')->after('tenant_id');
            $table->enum('type', ['hotspot', 'pppoe'])->after('name');
            $table->decimal('price', 10, 2)->after('type');
            $table->unsignedInteger('duration_days')->after('price');
            $table->string('speed_limit')->after('duration_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['name', 'type', 'price', 'duration_days', 'speed_limit']);
        });
    }
};
