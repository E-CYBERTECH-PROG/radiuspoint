<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\BelongsToTenant;

class Router extends Model {
    use BelongsToTenant;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function captivePortal(): HasOne
    {
        return $this->hasOne(CaptivePortal::class);
    }

    protected $fillable = [
        'tenant_id', 'name', 'ip_address', 'api_username', 'api_password',
        'status', 'port_configuration', 'last_seen', 'last_log_alert_at', 'public_token', 'routeros_version', 'board_model',
        'secret_key', 'wg_public_key', 'wg_private_key', 'wg_synced_at',
        'web_proxy_port', 'winbox_proxy_port',
    ];

    // Cast port_configuration as an array automatically
    protected $casts = [
        'port_configuration' => 'array',
        'last_seen' => 'datetime',
        'last_log_alert_at' => 'datetime',
    ];

    /**
     * The full real RouterOS provisioning script for this router — tunnel setup, API user,
     * and RADIUS wiring, using the server's real public IP/key (config/vpn.php) and this
     * router's own generated credentials. Used by NasProvisioningController::startup(), which
     * a router fetches and imports on its own rather than having this ever appear as
     * copy-pasted text (see routers/provision.blade.php for the short bootstrap script instead).
     */
    public function buildProvisioningScript(): string
    {
        $publicIp = config('vpn.public_ip');
        $publicKey = config('vpn.public_key');
        $serverVpnIp = config('vpn.server_vpn_ip');

        $lines = [];

        // RouterOS's interactive CLI happily runs a long ";"-separated chain typed at the
        // prompt, but `:import` parses a .rsc file far more strictly and expects one command
        // per line — cramming everything onto a single line (as this used to do) produces a
        // "Script Error: expected end of command" partway through the file.
        if ($this->routeros_version === 'v6') {
            $tunnelInterface = 'l2tp-isp';
            $lines[] = "/interface l2tp-client add name={$tunnelInterface} connect-to={$publicIp} user={$this->api_username} password={$this->api_password} ipsec-secret={$this->secret_key} use-ipsec=yes disabled=no;";
            $lines[] = "/ip address add address={$this->ip_address}/24 interface={$tunnelInterface};";
        } else {
            $tunnelInterface = 'wg-isp';
            // mtu=1280 avoids a PMTUD black hole: WireGuard's default (~1420) can exceed the
            // true path MTU on mobile/LTE/satellite WAN links with carrier encapsulation
            // overhead, and if ICMP "fragmentation needed" is blocked anywhere on the path (very
            // common), larger packets get silently dropped forever — the tunnel handshake and
            // small packets (e.g. ping) still work fine, but anything larger (like the API
            // login exchange) hangs with no error. 1280 is the IPv6-mandated minimum and is
            // safe regardless of the underlying path's quirks. The server side (wg0) is set the
            // same way — see /etc/wireguard/wg0.conf.
            $lines[] = "/interface wireguard add name={$tunnelInterface} listen-port=13231 mtu=1280 private-key=\"{$this->wg_private_key}\";";
            $lines[] = "/interface wireguard peers add interface={$tunnelInterface} public-key=\"{$publicKey}\" endpoint-address=\"{$publicIp}\" endpoint-port=13231 allowed-address=10.0.0.0/24 persistent-keepalive=25s;";
            $lines[] = "/ip address add address={$this->ip_address}/24 interface={$tunnelInterface};";
        }

        $lines[] = "/user add name={$this->api_username} password={$this->api_password} group=full;";
        // Deliberately NOT setting `address=` here. A fresh/new router has this field blank
        // (all sources allowed) so nothing extra is needed. But `address=` *replaces* the whole
        // field rather than appending — on hardware previously managed by another platform (e.g.
        // migrating off a different ISP billing system) that already restricted this to their
        // own management subnet, blindly overwriting it here would silently cut off that other
        // system's continued access. That exact scenario is why our own connection was silently
        // refused before it ever reached RouterOS's auth/logging layer the first time we hit
        // this (no error, no log entry — just a closed socket). On migrated hardware, check
        // `/ip service print` and manually widen `address=` to include 10.0.0.0/24 alongside
        // whatever's already there, rather than assuming this script alone will make it reachable.
        $lines[] = "/ip service set api disabled=no port=8728;";
        // Must be the server's *tunnel-internal* address, not $publicIp — that's only for
        // dialing the tunnel itself. FreeRADIUS binds solely to the VPN interface (10.0.0.1),
        // not the public one, so pointing RADIUS at $publicIp routes the request out over the
        // router's normal WAN instead of through the tunnel — RouterOS reports this as
        // "connect:Network unreachable" and every login (buy/reconnect/voucher/free-mode)
        // fails with "RADIUS server is not responding", with nothing in FreeRADIUS's own log
        // since the request never arrives. Confirmed against the real test router (id=11).
        //
        // Clear any existing hotspot/ppp radius entries first — this script can legitimately
        // run more than once for the same router (an interrupted ZTP flow restarted, a
        // deliberate re-provision), and `/radius add` has no "update if exists" form, so a
        // second run previously left a *second*, stale entry behind with whatever secret was
        // current at the time. RouterOS tried that stale entry first and got rejected, which
        // looks identical to "RADIUS server not responding" from the hotspot log alone —
        // confirmed as the actual live cause on a real re-provisioned router (id=17).
        $lines[] = "/radius remove [find service=hotspot,ppp];";
        $lines[] = "/radius add address={$serverVpnIp} secret={$this->secret_key} service=hotspot,ppp;";
        $lines[] = "/radius incoming set accept=yes port=3799;";

        // RouterOS's stock default firewall drops input traffic from any interface not on the
        // "LAN" interface-list ("defconf: drop all not coming from LAN") — since the VPN
        // interface is new and never added there, the API connection (and RADIUS traffic) get
        // silently dropped even though the tunnel itself is up (ICMP typically still gets
        // through, which is what makes this failure mode confusing: the tunnel looks healthy,
        // only the actual service ports are unreachable). Adding the VPN interface to the LAN
        // list is the idiomatic fix — it satisfies that rule's exception directly rather than
        // trying to splice a new accept rule into a specific position in a firewall chain that,
        // on a router with existing dynamic hotspot rules (e.g. from a prior BillNasi setup),
        // may not land where a naive `place-before` expects.
        $lines[] = "/interface list member add list=LAN interface={$tunnelInterface};";

        // Setting use-radius=yes on the default Hotspot/PPP profiles is handled separately via
        // the binary API (RouterController::checkStatus(), right after first successful connect)
        // instead of here. Every text-script variant tried — [find default=yes], [find
        // name=default], bare `default`, quoted "default" — failed :import parsing at the exact
        // same spot (right after the profile reference, before `use-radius=yes`), which pointed
        // to a RouterOS script-parser quirk with this construct rather than anything fixable by
        // re-wording it. The API protocol doesn't go through this text parser at all, so it
        // sidesteps the issue entirely.

        // A trailing newline is required — without it RouterOS's `:import` parser fails on the
        // final statement specifically ("expected end of command" pointing at the last line),
        // since it can't tell the last command is actually terminated.
        return implode("\r\n", $lines) . "\r\n";
    }
}