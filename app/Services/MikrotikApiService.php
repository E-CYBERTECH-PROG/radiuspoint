<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use Throwable;
use Illuminate\Support\Facades\Log;

class MikrotikApiService
{
    protected ?Client $client = null;

    public function connect(string $host, string $username, string $password, int $port = 8728): bool
    {
        try {
            $this->client = new Client([
                'host' => $host,
                'user' => $username,
                'pass' => $password,
                'port' => $port,
                'timeout' => 5,
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

        return $this->client->query($query)->read();
    }
}
