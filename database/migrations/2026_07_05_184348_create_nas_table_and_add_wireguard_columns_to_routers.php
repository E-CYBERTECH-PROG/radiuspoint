<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Matches FreeRADIUS's standard SQL schema (mods-config/sql/main/mysql/schema.sql)
        // exactly, since the `sql` module's read_clients feature queries this table directly.
        Schema::create('nas', function (Blueprint $table) {
            $table->id();
            $table->string('nasname', 128);
            $table->string('shortname', 32)->nullable();
            $table->string('type', 30)->default('other');
            $table->integer('ports')->nullable();
            $table->string('secret', 60)->default('secret');
            $table->string('server', 64)->nullable();
            $table->string('community', 50)->nullable();
            $table->string('description', 200)->default('RADIUS Client');

            $table->index('nasname');
        });

        Schema::table('routers', function (Blueprint $table) {
            $table->string('wg_public_key')->nullable()->after('secret_key');
            $table->string('wg_private_key')->nullable()->after('wg_public_key');
            $table->timestamp('wg_synced_at')->nullable()->after('wg_private_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas');

        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['wg_public_key', 'wg_private_key', 'wg_synced_at']);
        });
    }
};
