<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class WireguardService
{
    /**
     * Generate a fresh WireGuard keypair for a router. Pure cryptographic operations
     * (no root / CAP_NET_ADMIN needed) — safe to run inline in a web request. Applying
     * the resulting public key as a live peer on the server's wg0 interface is a
     * separate, privileged step handled by the ReconcileNetworking command instead.
     */
    public function generateKeypair(): array
    {
        $private = $this->run(['wg', 'genkey']);

        $public = $this->run(['wg', 'pubkey'], $private);

        return ['private' => $private, 'public' => $public];
    }

    private function run(array $command, ?string $input = null): string
    {
        $process = new Process($command);

        if ($input !== null) {
            $process->setInput($input);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }
}
