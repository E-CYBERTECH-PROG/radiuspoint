<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 4-template set (default/business/promo/premium) was replaced with 5 new themes
     * (light-lumen/crystal/grid/package/raw) that don't share names with the old ones — remap
     * existing rows so nobody's portal silently falls back to the default view.
     */
    public function up(): void
    {
        $map = [
            'default' => 'light-lumen',
            'business' => 'crystal',
            'promo' => 'package',
            'premium' => 'crystal',
        ];

        foreach ($map as $old => $new) {
            DB::table('captive_portals')->where('template', $old)->update(['template' => $new]);
        }

        Schema::table('captive_portals', function (Blueprint $table) {
            $table->string('template')->default('light-lumen')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('captive_portals', function (Blueprint $table) {
            $table->string('template')->default('default')->change();
        });
    }
};
