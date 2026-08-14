<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FreeRADIUS's standard `sql` module queries (queries.conf) reference 8 tables as part of its
 * normal authorize/post-auth flow: radcheck, radreply, radgroupcheck, radgroupreply,
 * radusergroup, radpostauth, radacct, nas. Only 4 of those (radcheck, radreply, radacct, nas)
 * were ever created on this install — the other 4 were missing entirely, which meant every real
 * login attempt got past the shared-secret check but then failed with a raw SQL error
 * ("Table 'radiuspoint.radusergroup' doesn't exist") the moment FreeRADIUS's authorize section
 * ran its group-membership lookup, immediately followed by the same for radpostauth's logging
 * insert. Confirmed directly in FreeRADIUS's own log against a real login attempt. Exact column
 * definitions copied from FreeRADIUS's own shipped schema
 * (/etc/freeradius/3.0/mods-config/sql/main/mysql/schema.sql) rather than reconstructed, since
 * queries.conf expects these exact names/types.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TABLE IF NOT EXISTS radgroupcheck (
              id int(11) unsigned NOT NULL auto_increment,
              groupname varchar(64) NOT NULL default '',
              attribute varchar(64)  NOT NULL default '',
              op char(2) NOT NULL DEFAULT '==',
              value varchar(253)  NOT NULL default '',
              PRIMARY KEY  (id),
              KEY groupname (groupname(32))
            );

            CREATE TABLE IF NOT EXISTS radgroupreply (
              id int(11) unsigned NOT NULL auto_increment,
              groupname varchar(64) NOT NULL default '',
              attribute varchar(64)  NOT NULL default '',
              op char(2) NOT NULL DEFAULT '=',
              value varchar(253)  NOT NULL default '',
              PRIMARY KEY  (id),
              KEY groupname (groupname(32))
            );

            CREATE TABLE IF NOT EXISTS radusergroup (
              id int(11) unsigned NOT NULL auto_increment,
              username varchar(64) NOT NULL default '',
              groupname varchar(64) NOT NULL default '',
              priority int(11) NOT NULL default '1',
              PRIMARY KEY  (id),
              KEY username (username(32))
            );

            CREATE TABLE IF NOT EXISTS radpostauth (
              id int(11) NOT NULL auto_increment,
              username varchar(64) NOT NULL default '',
              pass varchar(64) NOT NULL default '',
              reply varchar(32) NOT NULL default '',
              authdate timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
              class varchar(64) default NULL,
              PRIMARY KEY  (id),
              KEY username (username),
              KEY class (class)
            ) ENGINE = INNODB;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS radgroupcheck, radgroupreply, radusergroup, radpostauth;');
    }
};
