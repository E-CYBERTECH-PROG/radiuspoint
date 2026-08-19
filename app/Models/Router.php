<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\BelongsToTenant;

class Router extends Model {
    use BelongsToTenant, HasFactory;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function captivePortal(): HasOne
    {
        return $this->hasOne(CaptivePortal::class);
    }

    /**
     * Tenant-branded prefix for every RouterOS object this app creates (bridges, pools,
     * profiles, the API user, firewall comments) — so a tenant's own technician sees their
     * own ISP's name in Winbox, not a third-party vendor name.
     */
    public function namingSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->tenant->company_name) ?: 'tenant';
    }

    protected $fillable = [
        'tenant_id', 'name', 'ip_address', 'api_username', 'api_password',
        'status', 'port_configuration', 'last_seen', 'last_log_alert_at', 'public_token', 'routeros_version', 'board_model',
        'secret_key', 'wg_public_key', 'wg_private_key', 'wg_synced_at',
        'web_proxy_port', 'winbox_proxy_port',
    ];

    protected $casts = [
        'port_configuration' => 'array',
        'last_seen' => 'datetime',
        'last_log_alert_at' => 'datetime',
    ];

    /**
     * Builds the RouterOS provisioning script for this router: tunnel setup, API user,
     * and RADIUS wiring. Fetched and imported directly by the router via
     * NasProvisioningController::startup().
     *
     * Structured as two passes — clean up anything a previous run of this same script left
     * behind, THEN set up fresh — rather than the old interleaved add/hope-it's-new approach.
     * RouterOS's :import stops at the first line that errors, and both `/user add` and
     * `/interface list member add` error on an exact duplicate (unlike `/radius add`, which
     * already had its own remove-first guard). That meant re-pasting this script after a
     * failed run, or re-provisioning a router without a factory reset, could die on a
     * duplicate-name error partway through — silently skipping everything after it, including
     * the parts that actually establish the tunnel.
     */
    public function buildProvisioningScript(): string
    {
        $publicIp = config('vpn.public_ip');
        $publicKey = config('vpn.public_key');
        $serverVpnIp = config('vpn.server_vpn_ip');
        $tunnelInterface = $this->routeros_version === 'v6' ? 'l2tp-isp' : 'wg-isp';

        $lines = [];

        // Fixes the actual root cause of a factory-fresh router's clock being unset — the
        // check-certificate=no on the bootstrap fetch (RouterController::provision()) is a
        // workaround for the same problem, kept as defense-in-depth since NTP needs a moment
        // to sync. Kenya-only system, so the timezone is hardcoded rather than configurable.
        // A plain `set`, so it's already safe to run on every pass without any cleanup step.
        $lines[] = "/system clock set time-zone-name=Africa/Nairobi time-zone-autodetect=no;";

        // --- Clean up: remove anything this exact script would otherwise collide with.
        // Peers before the interface itself — while it still exists to find them by.
        if ($this->routeros_version === 'v6') {
            $lines[] = ":if ([:len [/interface l2tp-client find name={$tunnelInterface}]] > 0) do={ /interface l2tp-client remove [find name={$tunnelInterface}] };";
        } else {
            $lines[] = ":if ([:len [/interface wireguard peers find interface={$tunnelInterface}]] > 0) do={ /interface wireguard peers remove [find interface={$tunnelInterface}] };";
            $lines[] = ":if ([:len [/interface wireguard find name={$tunnelInterface}]] > 0) do={ /interface wireguard remove [find name={$tunnelInterface}] };";
        }
        $lines[] = ":if ([:len [/ip address find interface={$tunnelInterface}]] > 0) do={ /ip address remove [find interface={$tunnelInterface}] };";
        $lines[] = ":if ([:len [/user find name={$this->api_username}]] > 0) do={ /user remove [find name={$this->api_username}] };";
        // `/radius add` has no "update if exists" form — a stale entry would be tried first.
        // Filtered by address, not service=hotspot,ppp: RouterOS silently re-stores that field
        // as "ppp,hotspot" the moment it's added, so an exact-match find on the order this
        // script writes it in never matches anything it just created — confirmed live, this
        // let 5 identical entries pile up silently across repeated runs before being caught.
        // address is a single scalar value, immune to that reordering.
        $lines[] = ":if ([:len [/radius find address={$serverVpnIp}]] > 0) do={ /radius remove [find address={$serverVpnIp}] };";

        // --- Set up fresh.
        if ($this->routeros_version === 'v6') {
            $lines[] = "/interface l2tp-client add name={$tunnelInterface} connect-to={$publicIp} user={$this->api_username} password={$this->api_password} ipsec-secret={$this->secret_key} use-ipsec=yes disabled=no;";
        } else {
            // mtu=1280 avoids PMTUD black holes on WAN links that block ICMP
            // fragmentation-needed messages. Server side (wg0) matches this.
            $lines[] = "/interface wireguard add name={$tunnelInterface} listen-port=13231 mtu=1280 private-key=\"{$this->wg_private_key}\";";
            $lines[] = "/interface wireguard peers add interface={$tunnelInterface} public-key=\"{$publicKey}\" endpoint-address=\"{$publicIp}\" endpoint-port=13231 allowed-address=10.0.0.0/24 persistent-keepalive=25s;";
        }
        $lines[] = "/ip address add address={$this->ip_address}/24 interface={$tunnelInterface};";

        $lines[] = "/user add name={$this->api_username} password={$this->api_password} group=full;";
        // Not setting `address=` here — it replaces rather than appends, and would cut off
        // access on hardware already restricted to another management subnet. On migrated
        // hardware, manually widen `address=` in `/ip service print` to include 10.0.0.0/24.
        $lines[] = "/ip service set api disabled=no port=8728;";
        // Must be the server's tunnel-internal address, not $publicIp: FreeRADIUS binds only
        // to the VPN interface, so pointing at $publicIp routes outside the tunnel and fails.
        $lines[] = "/radius add address={$serverVpnIp} secret={$this->secret_key} service=hotspot,ppp;";
        $lines[] = "/radius incoming set accept=yes port=3799;";

        // RouterOS's default firewall drops input not on the "LAN" interface-list, so the
        // new VPN interface must be added there or the API/RADIUS traffic gets silently
        // dropped. Add-only-if-missing rather than remove-then-add: the interface was just
        // freshly recreated above, so any stale membership already went with the old one.
        $lines[] = ":if ([:len [/interface list member find list=LAN interface={$tunnelInterface}]] = 0) do={ /interface list member add list=LAN interface={$tunnelInterface} };";

        // v7 moved NTP servers to their own submenu — primary-ntp/secondary-ntp on
        // `ntp client set` only exists in v6 and errors as a bad parameter on v7. Also a
        // plain `set`/add-if-missing, no cleanup step needed.
        if ($this->routeros_version === 'v6') {
            $lines[] = "/system ntp client set enabled=yes primary-ntp=216.239.35.8;";
        } else {
            $lines[] = "/system ntp client set enabled=yes;";
            $lines[] = ":if ([:len [/system ntp client servers find address=216.239.35.8]] = 0) do={ /system ntp client servers add address=216.239.35.8 };";
        }

        // use-radius=yes on the default Hotspot/PPP profiles is set separately via the binary
        // API (RouterController::checkStatus()) — the text-script form fails :import parsing.

        // Trailing newline required or RouterOS's :import fails on the final statement.
        return implode("\r\n", $lines) . "\r\n";
    }
}