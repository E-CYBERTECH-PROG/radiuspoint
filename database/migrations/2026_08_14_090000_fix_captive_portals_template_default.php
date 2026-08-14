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
        // The 'minimal' template was removed; nothing ever saves this value, but the column
        // default was never updated, so a raw insert bypassing the app would still land on a
        // dead template name.
        DB::table('captive_portals')->where('template', 'minimal')->update(['template' => 'default']);

        Schema::table('captive_portals', function (Blueprint $table) {
            $table->string('template')->default('default')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('captive_portals', function (Blueprint $table) {
            $table->string('template')->default('minimal')->change();
        });
    }
};
