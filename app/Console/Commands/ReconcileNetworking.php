<?php

namespace App\Console\Commands;

use App\Models\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class ReconcileNetworking extends Command
{
    protected $signature = 'router:reconcile-networking';

    protected $description = 'Apply pending WireGuard peers to the live wg0 interface and reload FreeRADIUS if its NAS clients changed — the only steps that need root, kept out of the web-facing app process entirely';

    private const TCP_PROXY_DIR = '/www/server/panel/vhost/nginx/tcp';

    public function handle(): int
    {
        $peersChanged = $this->reconcileWireguardPeers();
        $nasChanged = $this->nasTableChangedSinceLastRun();
        $this->reconcileProxyPorts();

        if ($peersChanged || $nasChanged) {
            // Must be `restart`, not `reload`: FreeRADIUS only loads NAS clients from the `nas`
            // table at process startup, so a SIGHUP reload won't pick up secret changes. The
            // brief interruption is fine since this only fires when the nas table changed.
            (new Process(['systemctl', 'restart', 'freeradius']))->run();
            $this->info('FreeRADIUS restarted (NAS clients and/or peers changed).');
        }

        return self::SUCCESS;
    }

    private function reconcileWireguardPeers(): bool
    {
        $desired = Router::withoutGlobalScope('tenant')
            ->whereNotNull('wg_public_key')
            ->get(['id', 'wg_public_key', 'ip_address']);

        $show = new Process(['wg', 'show', 'wg0', 'peers']);
        $show->run();

        if (! $show->isSuccessful()) {
            $this->error('wg0 interface not available — is WireGuard running?');

            return false;
        }

        $currentPeers = array_filter(explode("\n", trim($show->getOutput())));
        $desiredKeys = $desired->pluck('wg_public_key')->all();
        $changed = false;

        // Add missing peers.
        foreach ($desired as $router) {
            if (! in_array($router->wg_public_key, $currentPeers, true)) {
                $add = new Process([
                    'wg', 'set', 'wg0', 'peer', $router->wg_public_key,
                    'allowed-ips', "{$router->ip_address}/32",
                ]);
                $add->run();

                if ($add->isSuccessful()) {
                    Router::withoutGlobalScope('tenant')->where('id', $router->id)->update(['wg_synced_at' => now()]);
                    $this->info("Added WireGuard peer for router #{$router->id} ({$router->ip_address}).");
                    $changed = true;
                } else {
                    $this->error("Failed to add peer for router #{$router->id}: " . $add->getErrorOutput());
                }
            }
        }

        // Remove peers for routers that no longer exist.
        foreach ($currentPeers as $peerKey) {
            if (! in_array($peerKey, $desiredKeys, true)) {
                (new Process(['wg', 'set', 'wg0', 'peer', $peerKey, 'remove']))->run();
                $this->info("Removed stale WireGuard peer {$peerKey}.");
                $changed = true;
            }
        }

        if ($changed) {
            (new Process(['wg-quick', 'save', 'wg0']))->run();
        }

        return $changed;
    }

    /**
     * A restart is only needed if the `nas` table changed since the last run.
     */
    private function nasTableChangedSinceLastRun(): bool
    {
        $marker = storage_path('app/nas_last_synced_count.txt');
        $currentCount = DB::table('nas')->count();
        $lastCount = is_file($marker) ? (int) file_get_contents($marker) : null;

        file_put_contents($marker, $currentCount);

        return $lastCount === null || $lastCount !== $currentCount;
    }

    /**
     * Writes one nginx stream{} config per router, forwarding its public web/winbox proxy
     * ports through the tunnel to the router's real 80/8291. aaPanel includes every .conf in
     * this directory automatically. Diffs against desired state and reloads only on change.
     */
    private function reconcileProxyPorts(): void
    {
        if (! is_dir(self::TCP_PROXY_DIR)) {
            $this->error(self::TCP_PROXY_DIR . ' does not exist — is aaPanel\'s TCP/UDP forwarding feature available?');

            return;
        }

        $desired = Router::withoutGlobalScope('tenant')
            ->whereNotNull('web_proxy_port')
            ->whereNotNull('winbox_proxy_port')
            ->get(['id', 'ip_address', 'web_proxy_port', 'winbox_proxy_port']);

        $desiredFiles = [];
        $changed = false;

        foreach ($desired as $router) {
            $filename = self::TCP_PROXY_DIR . "/radiuspoint-router-{$router->id}.conf";
            $desiredFiles[] = $filename;

            $config = <<<CONF
            server {
                listen {$router->web_proxy_port};
                proxy_pass {$router->ip_address}:80;
                proxy_timeout 10s;
            }
            server {
                listen {$router->winbox_proxy_port};
                proxy_pass {$router->ip_address}:8291;
                proxy_timeout 10s;
            }

            CONF;

            if (! is_file($filename) || file_get_contents($filename) !== $config) {
                file_put_contents($filename, $config);
                $this->info("Wrote proxy config for router #{$router->id} ({$router->ip_address}).");
                $changed = true;
            }
        }

        // Remove config for routers that no longer exist or lost their port allocation.
        foreach (glob(self::TCP_PROXY_DIR . '/radiuspoint-router-*.conf') as $existingFile) {
            if (! in_array($existingFile, $desiredFiles, true)) {
                unlink($existingFile);
                $this->info('Removed stale proxy config: ' . basename($existingFile));
                $changed = true;
            }
        }

        if ($changed) {
            $test = new Process(['nginx', '-t']);
            $test->run();

            if ($test->isSuccessful()) {
                (new Process(['nginx', '-s', 'reload']))->run();
                $this->info('nginx reloaded (proxy ports changed).');
            } else {
                $this->error('Generated nginx proxy config failed validation, not reloading: ' . $test->getErrorOutput());
            }
        }
    }
}
