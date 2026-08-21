<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only migration — 'oneisp' is now the intended default layout (see User::$attributes
 * for how new users get it going forward). This backfills existing users who are still on
 * 'standard' only because that used to be the users.dashboard_layout column default, not
 * because they picked it via Profile > Appearance.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('dashboard_layout', 'standard')->update(['dashboard_layout' => 'oneisp']);
    }

    public function down(): void
    {
        DB::table('users')->where('dashboard_layout', 'oneisp')->update(['dashboard_layout' => 'standard']);
    }
};
