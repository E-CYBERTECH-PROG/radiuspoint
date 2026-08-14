<?php

use Illuminate\Database\Migrations\Migration;
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

        DB::statement("ALTER TABLE radacct MODIFY acctsessiontime int(12) unsigned default NULL");

        Schema::table('radacct', function ($table) {
            $table->string('framedipv6address', 45)->default('')->after('framedipaddress');
            $table->string('framedipv6prefix', 45)->default('')->after('framedipv6address');
            $table->string('framedinterfaceid', 44)->default('')->after('framedipv6prefix');
            $table->string('delegatedipv6prefix', 45)->default('')->after('framedinterfaceid');
            $table->string('class', 64)->nullable()->after('delegatedipv6prefix');
        });

        // De-duplicate existing rows before adding the unique key below, or it will fail.
        DB::statement('
            DELETE r1 FROM radacct r1
            INNER JOIN radacct r2
            WHERE r1.radacctid < r2.radacctid AND r1.acctuniqueid = r2.acctuniqueid
        ');

        DB::statement('ALTER TABLE radacct ADD UNIQUE KEY acctuniqueid (acctuniqueid)');
    }

    public function down(): void
    {
        Schema::table('radacct', function ($table) {
            $table->dropUnique('acctuniqueid');
            $table->dropColumn(['acctinterval', 'framedipv6address', 'framedipv6prefix', 'framedinterfaceid', 'delegatedipv6prefix', 'class']);
        });
    }
};
