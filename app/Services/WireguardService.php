<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class WireguardService
{
    /**
     * Generates a fresh WireGuard keypair for a router. Safe to run inline in a web
     * request; applying the public key as a live peer is handled by ReconcileNetworking.
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
