<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Same "upload/download" (rx/tx) format as speed_limit, e.g. "10M/10M". Both null
            // means no burst — the plan's rate limit is then exactly speed_limit, unchanged.
            $table->string('burst_limit')->nullable()->after('speed_limit');
            $table->unsignedSmallInteger('burst_time')->nullable()->after('burst_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['burst_limit', 'burst_time']);
        });
    }
};
