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
     */
    public function buildProvisioningScript(): string
    {
        $publicIp = config('vpn.public_ip');
        $publicKey = config('vpn.public_key');
        $serverVpnIp = config('vpn.server_vpn_ip');

        $lines = [];

        // Fixes the actual root cause of a factory-fresh router's clock being unset — the
        // check-certificate=no on the bootstrap fetch (RouterController::provision()) is a
        // workaround for the same problem, kept as defense-in-depth since NTP needs a moment
        // to sync. Kenya-only system, so the timezone is hardcoded rather than configurable.
        $lines[] = "/system clock set time-zone-name=Africa/Nairobi time-zone-autodetect=no;";
        $lines[] = "/system ntp client set enabled=yes primary-ntp=216.239.35.8;";

        // :import expects one command per line, unlike the interactive CLI
        if ($this->routeros_version === 'v6') {
            $tunnelInterface = 'l2tp-isp';
            $lines[] = "/interface l2tp-client add name={$tunnelInterface} connect-to={$publicIp} user={$this->api_username} password={$this->api_password} ipsec-secret={$this->secret_key} use-ipsec=yes disabled=no;";
            $lines[] = "/ip address add address={$this->ip_address}/24 interface={$tunnelInterface};";
        } else {
            $tunnelInterface = 'wg-isp';
            // mtu=1280 avoids PMTUD black holes on WAN links that block ICMP
            // fragmentation-needed messages. Server side (wg0) matches this.
            $lines[] = "/interface wireguard add name={$tunnelInterface} listen-port=13231 mtu=1280 private-key=\"{$this->wg_private_key}\";";
            $lines[] = "/interface wireguard peers add interface={$tunnelInterface} public-key=\"{$publicKey}\" endpoint-address=\"{$publicIp}\" endpoint-port=13231 allowed-address=10.0.0.0/24 persistent-keepalive=25s;";
            $lines[] = "/ip address add address={$this->ip_address}/24 interface={$tunnelInterface};";
        }

        $lines[] = "/user add name={$this->api_username} password={$this->api_password} group=full;";
        // Not setting `address=` here — it replaces rather than appends, and would cut off
        // access on hardware already restricted to another management subnet. On migrated
        // hardware, manually widen `address=` in `/ip service print` to include 10.0.0.0/24.
        $lines[] = "/ip service set api disabled=no port=8728;";
        // Must be the server's tunnel-internal address, not $publicIp: FreeRADIUS binds only
        // to the VPN interface, so pointing at $publicIp routes outside the tunnel and fails.
        //
        // Clear existing radius entries first since this script can run more than once and
        // `/radius add` has no "update if exists" form — a stale entry would be tried first.
        $lines[] = "/radius remove [find service=hotspot,ppp];";
        $lines[] = "/radius add address={$serverVpnIp} secret={$this->secret_key} service=hotspot,ppp;";
        $lines[] = "/radius incoming set accept=yes port=3799;";

        // RouterOS's default firewall drops input not on the "LAN" interface-list, so the
        // new VPN interface must be added there or the API/RADIUS traffic gets silently dropped.
        $lines[] = "/interface list member add list=LAN interface={$tunnelInterface};";

        // use-radius=yes on the default Hotspot/PPP profiles is set separately via the binary
        // API (RouterController::checkStatus()) — the text-script form fails :import parsing.

        // Trailing newline required or RouterOS's :import fails on the final statement.
        return implode("\r\n", $lines) . "\r\n";
    }
}