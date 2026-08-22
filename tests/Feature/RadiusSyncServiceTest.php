<?php

namespace Tests\Feature;

use App\Services\RadiusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RadiusSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_writes_password_and_rate_limit(): void
    {
        RadiusSyncService::sync('254700000001', 'secret123', '5M/5M');

        $this->assertSame('secret123', DB::table('radcheck')
            ->where('username', '254700000001')->where('attribute', 'Cleartext-Password')->value('value'));
        $this->assertSame('5M/5M', DB::table('radreply')
            ->where('username', '254700000001')->where('attribute', 'Mikrotik-Rate-Limit')->value('value'));
    }

    public function test_sync_is_idempotent_per_username(): void
    {
        RadiusSyncService::sync('254700000002', 'first', '2M/2M');
        RadiusSyncService::sync('254700000002', 'second', '10M/10M');

        $this->assertSame(1, DB::table('radcheck')->where('username', '254700000002')->count());
        $this->assertSame('second', DB::table('radcheck')->where('username', '254700000002')->value('value'));
        $this->assertSame('10M/10M', DB::table('radreply')
            ->where('username', '254700000002')->where('attribute', 'Mikrotik-Rate-Limit')->value('value'));
    }

    public function test_remove_deletes_both_tables(): void
    {
        RadiusSyncService::sync('254700000003', 'secret', '5M/5M');

        RadiusSyncService::remove('254700000003');

        $this->assertSame(0, DB::table('radcheck')->where('username', '254700000003')->count());
        $this->assertSame(0, DB::table('radreply')->where('username', '254700000003')->count());
    }

    public function test_has_credential(): void
    {
        $this->assertFalse(RadiusSyncService::hasCredential('254700000004'));

        RadiusSyncService::sync('254700000004', 'secret', null);

        $this->assertTrue(RadiusSyncService::hasCredential('254700000004'));
    }

    public function test_set_expiration_writes_freeradius_format(): void
    {
        $expiresAt = now()->setDate(2026, 12, 25)->setTime(14, 30, 0);

        RadiusSyncService::setExpiration('254700000005', $expiresAt);

        $this->assertSame('25 Dec 2026 14:30:00', DB::table('radcheck')
            ->where('username', '254700000005')->where('attribute', 'Expiration')->value('value'));
    }

    public function test_set_expiry_window_sets_both_expiration_and_session_timeout(): void
    {
        $expiresAt = now()->addHours(2);

        RadiusSyncService::setExpiryWindow('254700000007', $expiresAt);

        $this->assertSame($expiresAt->format('d M Y H:i:s'), DB::table('radcheck')
            ->where('username', '254700000007')->where('attribute', 'Expiration')->value('value'));

        $sessionTimeout = (int) DB::table('radreply')
            ->where('username', '254700000007')->where('attribute', 'Session-Timeout')->value('value');
        // ~2 hours in seconds, allowing a little slack for time elapsed during the test itself.
        $this->assertGreaterThan(7100, $sessionTimeout);
        $this->assertLessThanOrEqual(7200, $sessionTimeout);
    }

    public function test_set_expiry_window_floors_session_timeout_at_60_seconds(): void
    {
        RadiusSyncService::setExpiryWindow('254700000008', now()->subMinute());

        $this->assertSame('60', DB::table('radreply')
            ->where('username', '254700000008')->where('attribute', 'Session-Timeout')->value('value'));
    }

    public function test_clear_expiration_removes_only_that_attribute(): void
    {
        RadiusSyncService::sync('254700000006', 'secret', null);
        RadiusSyncService::setExpiration('254700000006', now()->addDay());

        RadiusSyncService::clearExpiration('254700000006');

        $this->assertSame(0, DB::table('radcheck')
            ->where('username', '254700000006')->where('attribute', 'Expiration')->count());
        $this->assertSame(1, DB::table('radcheck')
            ->where('username', '254700000006')->where('attribute', 'Cleartext-Password')->count());
    }
}
