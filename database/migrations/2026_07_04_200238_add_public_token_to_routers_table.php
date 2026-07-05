<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->string('public_token')->nullable()->unique()->after('secret_key');
        });

        foreach (DB::table('routers')->whereNull('public_token')->select('id')->get() as $router) {
            DB::table('routers')->where('id', $router->id)->update(['public_token' => Str::random(24)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
