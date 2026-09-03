<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers two related changes made together:
 *
 *  1. Vouchers (HotspotUser::is_voucher=true) are excluded from "hotspot customer"
 *     counts/lists app-wide — they aren't real walk-up customers until redeemed, and even
 *     then belong on their own Vouchers page.
 *  2. An auto-purchased hotspot account's RADIUS credential is its M-Pesa receipt
 *     (username=password=receipt), not phone_number+random password — mirroring how
 *     vouchers already work. HotspotUser::radiusUsername() resolves this, and every place
 *     that used to assume phone_number IS the RADIUS username had to be updated to use it,
 *     since that assumption silently breaks re-login, expiry enforcement, FUP throttling,
 *     and disable/delete for every auto-purchased account otherwise.
 */
class HotspotVoucherCredentialSchemeTest extends TestCase
{
    use RefreshDatabase;

    private function activatePurchase(Transaction $transaction, Plan $plan): HotspotUser
    {
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $transaction->checkout_request_id,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGH7ABCDE1'],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('api.mpesa.callback'), $payload)->assertOk();

        return HotspotUser::where('phone_number', $transaction->phone_number)->firstOrFail();
    }

    public function test_auto_purchase_syncs_mpesa_receipt_as_radius_credential_and_captures_mac(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'duration_value' => 1, 'duration_unit' => 'days']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);

        $transaction = Transaction::factory()->create(['tenant_id' => $tenant->id] + [
            'status' => 'pending',
            'mpesa_receipt' => null,
            'checkout_request_id' => 'ws_CO_TEST001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'phone_number' => '254712345678',
            'mac_address' => 'AA:BB:CC:DD:EE:01',
        ]);

        $hotspotUser = $this->activatePurchase($transaction, $plan);

        // MAC captured straight from the transaction, not left for the async backfill job.
        $this->assertSame('AA:BB:CC:DD:EE:01', $hotspotUser->mac_address);

        // RADIUS credential is the receipt — not the phone number.
        $this->assertSame('QGH7ABCDE1', DB::table('radcheck')
            ->where('username', 'QGH7ABCDE1')->where('attribute', 'Cleartext-Password')->value('value'));
        $this->assertSame(0, DB::table('radcheck')->where('username', '254712345678')->count());

        // The app-level identity is still the real phone number.
        $this->assertSame('254712345678', $hotspotUser->phone_number);
        $this->assertSame('QGH7ABCDE1', $hotspotUser->radiusUsername());
    }

    public function test_radius_username_falls_back_to_phone_number_when_no_purchase_is_linked(): void
    {
        // Voucher and manually-created accounts both have no Transaction row.
        $voucher = HotspotUser::factory()->create(['phone_number' => 'UI2CR5D0LN', 'is_voucher' => true]);
        $manual = HotspotUser::factory()->create(['phone_number' => 'staff-typed-username']);

        $this->assertSame('UI2CR5D0LN', $voucher->radiusUsername());
        $this->assertSame('staff-typed-username', $manual->radiusUsername());
    }

    public function test_captive_portal_receipt_lookup_returns_receipt_as_credential(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'duration_value' => 1, 'duration_unit' => 'days']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'public_token' => 'router-token-1']);

        $transaction = Transaction::factory()->create(['tenant_id' => $tenant->id] + [
            'status' => 'pending',
            'mpesa_receipt' => null,
            'checkout_request_id' => 'ws_CO_TEST002',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'phone_number' => '254711112222',
        ]);
        $this->activatePurchase($transaction, $plan);

        $response = $this->postJson("/captive/{$router->public_token}/lookup-receipt", [
            'message' => "Confirmed. QGH7ABCDE1 sent to Acme Wifi. New M-PESA balance is Ksh100.00.",
        ]);

        $response->assertOk()->assertJson([
            'found' => true,
            'username' => 'QGH7ABCDE1',
            'password' => 'QGH7ABCDE1',
        ]);
    }

    public function test_captive_portal_phone_lookup_returns_receipt_as_credential(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'duration_value' => 1, 'duration_unit' => 'days']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'public_token' => 'router-token-2']);

        $transaction = Transaction::factory()->create(['tenant_id' => $tenant->id] + [
            'status' => 'pending',
            'mpesa_receipt' => null,
            'checkout_request_id' => 'ws_CO_TEST003',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'phone_number' => '0722334455',
        ]);
        // BillingController normalizes phone via M-Pesa's own 2547... format; the STK request
        // itself is what carries the raw 07... shape, and PaymentPortalController normalizes
        // before persisting — so mimic that here for a realistic row.
        $transaction->update(['phone_number' => '254722334455']);
        $this->activatePurchase($transaction, $plan);

        $response = $this->postJson("/captive/{$router->public_token}/lookup", [
            'phone' => '0722334455',
        ]);

        $response->assertOk()->assertJson([
            'found' => true,
            'username' => 'QGH7ABCDE1',
            'password' => 'QGH7ABCDE1',
        ]);
    }

    public function test_deleting_an_auto_purchased_customer_actually_revokes_their_real_radius_credential(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'duration_value' => 1, 'duration_unit' => 'days']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);

        $transaction = Transaction::factory()->create(['tenant_id' => $tenant->id] + [
            'status' => 'pending',
            'mpesa_receipt' => null,
            'checkout_request_id' => 'ws_CO_TEST004',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'phone_number' => '254733445566',
        ]);
        $hotspotUser = $this->activatePurchase($transaction, $plan);

        $this->assertSame(1, DB::table('radcheck')->where('username', 'QGH7ABCDE1')->where('attribute', 'Cleartext-Password')->count());

        $this->actingAs($admin)->delete(route('hotspot-users.destroy', $hotspotUser))->assertRedirect();

        // The credential a customer would actually use is gone — destroy() didn't just
        // no-op against a phone-number-keyed row that was never there, leaving a deleted
        // customer's real login still valid.
        $this->assertSame(0, DB::table('radcheck')->where('username', 'QGH7ABCDE1')->count());
    }

    public function test_expiring_an_auto_purchased_customer_revokes_their_real_radius_credential(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'type' => 'hotspot', 'duration_value' => 1, 'duration_unit' => 'days']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);

        $transaction = Transaction::factory()->create(['tenant_id' => $tenant->id] + [
            'status' => 'pending',
            'mpesa_receipt' => null,
            'checkout_request_id' => 'ws_CO_TEST005',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'phone_number' => '254744556677',
        ]);
        $hotspotUser = $this->activatePurchase($transaction, $plan);
        $hotspotUser->update(['expires_at' => now()->subMinute()]);

        $this->artisan('users:expire-overdue')->assertSuccessful();

        $this->assertSame('expired', $hotspotUser->fresh()->status);
        $this->assertSame(0, DB::table('radcheck')->where('username', 'QGH7ABCDE1')->count());
    }

    public function test_vouchers_are_excluded_from_customers_hub_and_its_stats(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => '254700111222']);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => 'VOUCHER99', 'is_voucher' => true]);

        $response = $this->actingAs($admin)->get('/customers/hotspot');

        $response->assertOk();
        $response->assertSee('254700111222');
        $response->assertDontSee('VOUCHER99');
    }

    public function test_vouchers_are_excluded_from_hotspot_users_index(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => '254700333444']);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'phone_number' => 'VOUCHERAB', 'is_voucher' => true]);

        $response = $this->actingAs($admin)->get('/hotspot-users');

        $response->assertOk();
        $response->assertSee('254700333444');
        $response->assertDontSee('VOUCHERAB');
    }

    public function test_vouchers_are_excluded_from_dashboard_customer_total(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id]);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'is_voucher' => true]);
        HotspotUser::factory()->active()->create(['tenant_id' => $tenant->id, 'is_voucher' => true]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        // Only the one non-voucher row should count — if the two vouchers were still folded
        // in, this would be 3.
        $this->assertSame(1, $response->original->getData()['stats']['customers_total']);
    }
}
