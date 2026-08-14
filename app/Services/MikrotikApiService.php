<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use Throwable;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected ?Client $client = null;

    /**
     * Default timeout is short since routine actions target an already-provisioned router
     * over the WireGuard tunnel. checkStatus() passes a longer timeout for first-contact
     * ZTP, since a freshly-booted router's API can be slower to come up.
     */
    public function connect(string $host, string $username, string $password, int $port = 8728, int $timeout = 3): bool
    {
        try {
            $this->client = new Client([
                'host' => $host,
                'user' => $username,
                'pass' => $password,
                'port' => $port,
                'timeout' => $timeout,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning("MikroTik connect failed for {$host}: " . $e->getMessage());
            $this->client = null;

            return false;
        }
    }

    public function query(string $endpoint, ?array $attributes = null): array
    {
        $query = new Query($endpoint);

        foreach ($attributes ?? [] as $key => $value) {
            $query->equal($key, $value);
        }

        return $this->readResult($this->client->query($query));
    }

    /**
     * The `.id` of the first item matching a filter on a /print endpoint, or null if none.
     * The API protocol has no inline "[find ...]" shorthand like RouterOS's CLI does.
     */
    public function findId(string $printEndpoint, string $key, $value): ?string
    {
        $query = new Query($printEndpoint);
        $query->where($key, $value);

        $result = $this->readResult($this->client->query($query));

        return $result[0]['.id'] ?? null;
    }

    /**
     * All matching rows from a /print endpoint filtered by a single key. Like findId()
     * but returns whole rows, not just .id.
     */
    public function queryWhere(string $printEndpoint, string $key, $value): array
    {
        $query = new Query($printEndpoint);
        $query->where($key, $value);

        return $this->readResult($this->client->query($query));
    }

    public function setById(string $setEndpoint, string $id, array $attributes): void
    {
        $query = new Query($setEndpoint);
        $query->equal('.id', $id);

        foreach ($attributes as $key => $value) {
            $query->equal($key, $value);
        }

        $this->readResult($this->client->query($query));
    }

    /**
     * Reads the raw RouterOS response and throws if it's a `!trap` (error) rather than
     * `!done` (success) — the vendor library's parser otherwise treats both the same,
     * making a failed /add or /set indistinguishable from a successful one.
     *
     * Returns a list of rows (each a flat key => value array).
     */
    private function readResult($queryResult): array
    {
        $raw = $queryResult->read(false);

        if (in_array('!trap', $raw, true)) {
            $message = 'RouterOS API error';
            foreach ($raw as $word) {
                if (is_string($word) && str_starts_with($word, '=message=')) {
                    $message = substr($word, strlen('=message='));
                    break;
                }
            }

            throw new \RuntimeException($message);
        }

        $rows = [];
        $current = [];

        foreach ($raw as $word) {
            if ($word === '!re') {
                if ($current) {
                    $rows[] = $current;
                }
                $current = [];
            } elseif (is_string($word) && str_starts_with($word, '=')) {
                [, $key, $value] = array_pad(explode('=', $word, 3), 3, '');
                $current[$key] = $value;
            }
        }

        if ($current) {
            $rows[] = $current;
        }

        return $rows;
    }

    /**
     * The first unused /24 under 172.20.0.0/16, reserved for Hotspot/PPPoE client pools.
     * Checks the router's actual current pools so it can't collide with anything already
     * configured on hardware with prior history.
     */
    public function allocateSubnet(): array
    {
        $existingRanges = array_column($this->query('/ip/pool/print'), 'ranges');

        for ($octet = 0; $octet <= 255; $octet++) {
            $cidr = "172.20.{$octet}.0/24";
            $gateway = "172.20.{$octet}.1";
            $range = "172.20.{$octet}.2-172.20.{$octet}.254";

            $collides = false;
            foreach ($existingRanges as $existing) {
                if (str_contains((string) $existing, "172.20.{$octet}.")) {
                    $collides = true;
                    break;
                }
            }

            if (! $collides) {
                return ['gateway' => $gateway, 'range' => $range, 'cidr' => $cidr];
            }
        }

        throw new \RuntimeException('No free /24 subnet available in the 172.20.0.0/16 allocation block.');
    }
}
