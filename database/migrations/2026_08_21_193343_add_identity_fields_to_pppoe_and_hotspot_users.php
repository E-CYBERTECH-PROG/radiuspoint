<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the unified "Add Customer" modal (Customers hub) — first/last name, email, and
 * address weren't captured before. pppoe_users keeps its existing 'name' column (now filled
 * as "first_name last_name" at write time); hotspot_users gets a matching 'name' column
 * since it had no identity field at all beyond phone_number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('username');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('email')->nullable()->after('phone_number');
            $table->string('address')->nullable()->after('email');
        });

        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('phone_number');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('name')->nullable()->after('last_name');
            $table->string('email')->nullable()->after('name');
            $table->string('address')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('pppoe_users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'email', 'address']);
        });

        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'name', 'email', 'address']);
        });
    }
};
