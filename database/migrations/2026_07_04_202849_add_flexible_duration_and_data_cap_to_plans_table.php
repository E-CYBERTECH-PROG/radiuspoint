<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_value')->nullable()->after('duration_days');
            $table->enum('duration_unit', ['minutes', 'hours', 'days', 'weeks', 'months'])->default('days')->after('duration_value');
            $table->unsignedBigInteger('data_cap_mb')->nullable()->after('duration_unit');
        });

        DB::table('plans')->update(['duration_value' => DB::raw('duration_days'), 'duration_unit' => 'days']);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable()->after('price');
        });

        DB::table('plans')->update(['duration_days' => DB::raw('duration_value')]);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['duration_value', 'duration_unit', 'data_cap_mb']);
        });
    }
};
