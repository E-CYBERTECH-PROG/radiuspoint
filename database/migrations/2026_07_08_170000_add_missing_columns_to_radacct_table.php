<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * radacct was missing 6 columns FreeRADIUS 3.0's accounting queries expect on every insert,
 * plus the unique key needed to upsert interim updates instead of duplicating them. Without
 * them every accounting insert failed silently — RADIUS auth kept working, but online-count,
 * voucher activation, and FUP/data-cap enforcement (all of which read from radacct) did not.
 * Columns copied from FreeRADIUS's own shipped schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radacct', function ($table) {
            $table->integer('acctinterval')->nullable()->after('acctstoptime');
        });

        // Already an unsigned nullable integer from create_radius_tables — MySQL's own width
        // display attribute (int(12)) has no SQLite equivalent and no functional effect.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE radacct MODIFY acctsessiontime int(12) unsigned default NULL');
        }

        Schema::table('radacct', function ($table) {
            $table->string('framedipv6address', 45)->default('')->after('framedipaddress');
            $table->string('framedipv6prefix', 45)->default('')->after('framedipv6address');
            $table->string('framedinterfaceid', 44)->default('')->after('framedipv6prefix');
            $table->string('delegatedipv6prefix', 45)->default('')->after('framedinterfaceid');
            $table->string('class', 64)->nullable()->after('delegatedipv6prefix');
        });

        // De-duplicate existing rows before adding the unique key below, or it will fail.
        // MySQL's multi-table DELETE...JOIN has no SQLite equivalent; this subquery form keeps
        // the newest row per acctuniqueid on both drivers.
        DB::statement('
            DELETE FROM radacct
            WHERE radacctid NOT IN (SELECT MAX(radacctid) FROM radacct GROUP BY acctuniqueid)
        ');

        Schema::table('radacct', function (Blueprint $table) {
            // Named explicitly to match the key name the original raw SQL created on
            // already-migrated (production) databases — Laravel's default convention-based
            // name (radacct_acctuniqueid_unique) would only match on a fresh install.
            $table->unique('acctuniqueid', 'acctuniqueid');
        });
    }

    public function down(): void
    {
        Schema::table('radacct', function (Blueprint $table) {
            $table->dropUnique('acctuniqueid');
            $table->dropColumn(['acctinterval', 'framedipv6address', 'framedipv6prefix', 'framedinterfaceid', 'delegatedipv6prefix', 'class']);
        });
    }
};
