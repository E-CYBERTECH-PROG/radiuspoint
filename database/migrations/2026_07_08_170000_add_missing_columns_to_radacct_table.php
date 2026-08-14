<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `radacct` was created from an older/incomplete schema — missing 6 columns FreeRADIUS 3.0's
 * default accounting queries.conf expects on every INSERT, plus the unique key it relies on to
 * upsert (rather than duplicate) an in-progress session's interim updates. Every accounting
 * packet (session start, interim update, stop) was failing outright:
 * "ERROR 1054 (Unknown column 'framedipv6address' in 'INSERT INTO')" — confirmed live in
 * FreeRADIUS's own log. Net effect: authentication succeeded (radcheck/radreply/radpostauth all
 * fine), but zero accounting rows were *ever* written, for any customer, voucher, or plan,
 * system-wide. That's why "online now" always read 0, vouchers never left "unused"
 * (ActivateUsedVouchers depends on radacct to detect first connect), and FUP/data-cap
 * enforcement never triggered (it sums radacct's octet columns). Not specific to one voucher —
 * this was silently broken for every single session since whenever radacct was first created.
 * Columns copied verbatim from FreeRADIUS's own shipped schema
 * (/etc/freeradius/3.0/mods-config/sql/main/mysql/schema.sql).
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

        // Duplicate/blank acctuniqueid values can already exist from the broken period (every
        // failed insert that got partially retried) — de-duplicate before adding the unique key
        // or the migration itself would fail the same way the accounting inserts did.
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
