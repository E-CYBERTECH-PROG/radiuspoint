<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('timezone')->default('Africa/Nairobi')->after('location');
            $table->string('currency_symbol', 10)->default('KES')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'currency_symbol']);
        });
    }
};
