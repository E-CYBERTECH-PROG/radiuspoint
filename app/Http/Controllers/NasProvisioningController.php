<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikApiService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class NasProvisioningController extends Controller
{
    /**
     * Public, public_token-scoped endpoint that a router's bootstrap script fetches and imports
     * (see RouterController::provision()). Generated fresh on every request so it always
     * reflects current credentials.
     *
     * Always includes the tunnel/API-user/RADIUS bootstrap (Router::buildProvisioningScript()).
     * If this router has already had its interfaces assigned roles (port_configuration is set —
     * done via the Ports wizard, which pushes that config live over the binary API), this ALSO
     * appends a full hotspot/PPPoE/walled-garden/free-mode script covering the same ground, so
     * the exact same two-line fetch-and-import snippet re-provisions everything after a factory
     * reset or on a replacement unit — not just the tunnel. That extra section is best-effort:
     * it needs a live connection back to the router (over the tunnel this same script sets up),
     * so on a truly first-ever fetch — no tunnel yet — it's silently skipped and this behaves
     * exactly as before.
     */
    public function startup(Router $router): Response
    {
        $script = $router->buildProvisioningScript();

        if (! empty($router->port_configuration)) {
            try {
                $script .= $this->buildFullConfigScript($router);
            } catch (Throwable $e) {
                Log::warning("Full config script skipped for {$router->name} (router unreachable or not yet tunneled): " . $e->getMessage());
            }
        }

        return response($script, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Re-expresses what RouterController's live-API provisioning methods (provisionHotspot,
     * provisionPppoe, the walled-garden part of provisionCaptivePortal, provisionFreeMode) do,
     * as RouterOS script text instead of binary-API calls — using the exact same deterministic
     * naming scheme, so this can never collide with or duplicate what that live path already
     * created; either path is safe to (re-)run at any time.
     *
     * Deliberately does NOT include everything the live path does:
     *   - use-radius=yes on the router's default Hotspot/PPP profiles: proven (see
     *     Router::buildProvisioningScript()'s own comment) to fail RouterOS's :import parser in
     *     script form. Stays binary-API-only (RouterController::checkStatus()).
     *   - Rewriting hotspot/login.html's contents: embedding that HTML safely inside a RouterOS
     *     script string literal is fragile enough not to be worth the risk here. Stays
     *     binary-API-only (RouterController::provisionCaptivePortal()/pushCaptivePortalFiles()).
     *   - The retroactive drift-fix loops (renaming older-generation object names, fixing
     *     login-by/radius-interim-update/idle-timeout on pre-existing servers): those exist to
     *     repair routers provisioned by an older version of this app, which is meaningless for
     *     a script generated fresh from this router's current, already-correct configuration.
     */
    private function buildFullConfigScript(Router $router): string
    {
        $api = new MikrotikApiService();
        if (! $api->connect($router->ip_address, $router->api_username, $router->api_password, timeout: 5)) {
            throw new \RuntimeException('Router unreachable over the tunnel.');
        }

        $slug = $router->namingSlug();
        $lines = [];
        $lines[] = '';
        $lines[] = '# --- Full config (hotspot/PPPoE/walled-garden/free mode), generated '.now()->toDateTimeString().' ---';

        $anyHotspot = false;

        foreach ((array) $router->port_configuration as $interface => $config) {
            $role = $config['role'] ?? 'none';
            if ($role === 'none') {
                continue;
            }

            // Same bridge-port resolution provisionHotspot()/provisionPppoe() apply — a hotspot
            // or PPPoE server bound directly to a bridge-slave port is invalid on RouterOS.
            $bridgePort = $api->queryWhere('/interface/bridge/port/print', 'interface', $interface);
            $boundInterface = $bridgePort ? $bridgePort[0]['bridge'] : $interface;

            if (in_array($role, ['hotspot', 'both'], true)) {
                $anyHotspot = true;
                $hotspotLines = $this->buildHotspotLines($api, $slug, $interface, $boundInterface);
                if ($hotspotLines) {
                    $lines[] = "# {$interface} — hotspot";
                    array_push($lines, ...$hotspotLines);
                }
            }

            if (in_array($role, ['pppoe', 'both'], true)) {
                $pppoeLines = $this->buildPppoeLines($api, $slug, $interface, $boundInterface);
                if ($pppoeLines) {
                    $lines[] = "# {$interface} — PPPoE";
                    array_push($lines, ...$pppoeLines);
                }
            }
        }

        if ($anyHotspot) {
            $lines[] = '# Walled garden — lets an unauthenticated client reach the captive portal.';
            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $lines[] = ":if ([:len [/ip hotspot walled-garden find dst-host={$host}]] = 0) do={ /ip hotspot walled-garden add action=allow dst-host={$host} comment=\"{$slug} captive portal\" };";

            array_push($lines, ...$this->buildFreeModeLines($slug));
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Mirrors RouterController::provisionHotspot(), reading the pool range that already exists
     * (created by that same live method) rather than allocating a new one — this script only
     * ever re-creates what's already there, never invents fresh addressing.
     */
    private function buildHotspotLines(MikrotikApiService $api, string $slug, string $interface, string $boundInterface): array
    {
        $serverName = "{$slug}_hotspot_{$boundInterface}";
        $poolName = "{$slug}_hs_pool_{$boundInterface}";
        $profileName = "{$slug}_hs_prof_{$boundInterface}";

        $pool = $api->queryWhere('/ip/pool/print', 'name', $poolName);
        if (! $pool || ! preg_match('/^(\d+\.\d+\.\d+)\.\d+-/', $pool[0]['ranges'] ?? '', $m)) {
            // Not provisioned live yet (or pool was renamed/removed) — nothing to reproduce.
            return [];
        }
        $gateway = "{$m[1]}.1";
        $networkCidr = "{$m[1]}.0/24";
        $range = $pool[0]['ranges'];

        return [
            ":if ([:len [/ip hotspot find name={$serverName}]] = 0) do={",
            "  :if ([:len [/ip address find interface={$boundInterface} address={$gateway}/24]] = 0) do={ /ip address add address={$gateway}/24 interface={$boundInterface} };",
            "  :if ([:len [/ip pool find name={$poolName}]] = 0) do={ /ip pool add name={$poolName} ranges={$range} };",
            "  :if ([:len [/ip hotspot profile find name={$profileName}]] = 0) do={ /ip hotspot profile add name={$profileName} hotspot-address={$gateway} use-radius=yes login-by=http-chap,http-pap radius-interim-update=00:05:00 };",
            "  /ip hotspot add name={$serverName} interface={$boundInterface} address-pool={$poolName} profile={$profileName} disabled=no idle-timeout=2m;",
            "};",
            ":if ([:len [/ip dhcp-server find interface={$boundInterface}]] = 0) do={ /ip dhcp-server add name={$slug}_hs_dhcp_{$boundInterface} interface={$boundInterface} address-pool={$poolName} lease-time=1h disabled=no };",
            ":if ([:len [/ip dhcp-server network find address={$networkCidr}]] = 0) do={ /ip dhcp-server network add address={$networkCidr} gateway={$gateway} dns-server={$gateway} };",
        ];
    }

    /**
     * Mirrors RouterController::provisionPppoe() — same read-not-allocate approach as the
     * hotspot builder above.
     */
    private function buildPppoeLines(MikrotikApiService $api, string $slug, string $interface, string $boundInterface): array
    {
        $poolName = "{$slug}_pppoe_pool_{$boundInterface}";
        $profileName = "{$slug}_pppoe_prof_{$boundInterface}";

        $pool = $api->queryWhere('/ip/pool/print', 'name', $poolName);
        if (! $pool) {
            return [];
        }
        $range = $pool[0]['ranges'];

        return [
            ":if ([:len [/ip pool find name={$poolName}]] = 0) do={ /ip pool add name={$poolName} ranges={$range} };",
            ":if ([:len [/ppp profile find name={$profileName}]] = 0) do={ /ppp profile add name={$profileName} remote-address={$poolName} };",
            "/ppp aaa set use-radius=yes;",
            ":if ([:len [/interface pppoe-server server find interface={$boundInterface}]] = 0) do={ /interface pppoe-server server add interface={$boundInterface} service-name={$slug} default-profile={$profileName} disabled=no one-session-per-host=yes };",
        ];
    }

    /**
     * Mirrors RouterController::provisionFreeMode() exactly — same profile/DNS/firewall setup,
     * same domain allowlist.
     */
    private function buildFreeModeLines(string $slug): array
    {
        $profileName = "{$slug}_free";
        $freemodeList = "{$slug}-freemode";
        $allowedList = "{$slug}-freemode-allowed";

        $lines = [
            '# Free Mode — throttled, walled-off access for customers with no active plan.',
            ":if ([:len [/ip hotspot user profile find name={$profileName}]] = 0) do={ /ip hotspot user profile add name={$profileName} address-list={$freemodeList} rate-limit=96k/96k };",
        ];

        $allowedDomains = [
            '.*whatsapp\\.com',
            '.*whatsapp\\.net',
            '.*facebook\\.com',
            '.*fbcdn\\.net',
            '.*messenger\\.com',
        ];
        foreach ($allowedDomains as $regexp) {
            $lines[] = ":if ([:len [/ip dns static find regexp=\"{$regexp}\"]] = 0) do={ /ip dns static add regexp=\"{$regexp}\" type=FWD forward-to=8.8.8.8 address-list={$allowedList} ttl=1m comment=\"{$slug} free mode\" };";
        }

        $lines[] = ":if ([:len [/ip firewall filter find comment=\"{$slug}-freemode-allow\"]] = 0) do={ /ip firewall filter add chain=forward action=accept src-address-list={$freemodeList} dst-address-list={$allowedList} comment=\"{$slug}-freemode-allow\" };";
        $lines[] = ":if ([:len [/ip firewall filter find comment=\"{$slug}-freemode-block-rest\"]] = 0) do={ /ip firewall filter add chain=forward action=drop src-address-list={$freemodeList} comment=\"{$slug}-freemode-block-rest\" };";

        return $lines;
    }
}
