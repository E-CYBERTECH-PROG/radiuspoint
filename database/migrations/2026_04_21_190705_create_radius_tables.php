<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. RADCHECK: Stores Usernames (or MAC addresses) and Passwords
        Schema::create('radcheck', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('==');
            $table->string('value', 253);
            $table->timestamps();
        });

        // 2. RADREPLY: Stores the Plan Limits (Speed, IP Address, Session Time)
        Schema::create('radreply', function (Blueprint $table) {
            $table->id();
            $table->string('username', 64)->index();
            $table->string('attribute', 64);
            $table->string('op', 2)->default('=');
            $table->string('value', 253);
            $table->timestamps();
        });

        // 3. RADACCT: Tracks Live Data Usage and Online/Offline Status
        Schema::create('radacct', function (Blueprint $table) {
            $table->id('radacctid');
            $table->string('acctsessionid', 64)->index();
            $table->string('acctuniqueid', 32)->unique();
            $table->string('username', 64)->index();
            $table->string('realm', 64)->nullable();
            $table->string('nasipaddress', 15)->index();
            $table->string('nasportid', 32)->nullable();
            $table->string('nasporttype', 32)->nullable();
            $table->dateTime('acctstarttime')->nullable()->index();
            $table->dateTime('acctupdatetime')->nullable();
            $table->dateTime('acctstoptime')->nullable()->index();
            $table->integer('acctsessiontime')->unsigned()->nullable();
            $table->string('acctauthentic', 32)->nullable();
            $table->string('connectinfo_start', 120)->nullable();
            $table->string('connectinfo_stop', 120)->nullable();
            $table->bigInteger('acctinputoctets')->nullable(); // Download
            $table->bigInteger('acctoutputoctets')->nullable(); // Upload
            $table->string('calledstationid', 50)->nullable();
            $table->string('callingstationid', 50)->nullable();
            $table->string('acctterminatecause', 32)->nullable();
            $table->string('servicetype', 32)->nullable();
            $table->string('framedprotocol', 32)->nullable();
            $table->string('framedipaddress', 15)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
    }
};