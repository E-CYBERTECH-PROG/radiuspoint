<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which of the 3 dashboard.blade.php arrangements this user sees (see
            // DashboardController::index()) — a real layout swap, not just a CSS recolor like
            // the Appearance color pickers, so it has to be known at server-render time rather
            // than applied client-side after the fact (which would mean rendering all 3 layouts'
            // worth of queries/charts on every page load just to hide two of them).
            $table->string('dashboard_layout')->default('standard')->after('is_platform_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_layout');
        });
    }
};
