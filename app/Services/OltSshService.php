<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Throwable;

/**
 * Generic remote-terminal driver for GPON/EPON OLTs (VSOL, Hioso, and similar), which
 * are managed over a Cisco IOS-style text CLI via Telnet/SSH rather than a structured
 * API. Opens a session, sends whatever the operator types, and returns whatever the
 * device prints back, proxied through the app so an admin doesn't need direct network
 * access to the OLT.
 *
 * Reads by timeout-based quiescence rather than matching a prompt regex, since the
 * exact prompt string varies by brand/model/firmware.
 */
class OltSshService
{
    private ?SSH2 $ssh = null;

    public function connect(string $host, int $port, string $username, string $password): bool
    {
        try {
            $this->ssh = new SSH2($host, $port, 8);

            if (! $this->ssh->login($username, $password)) {
                return false;
            }

            // Drain the post-login banner/MOTD so it doesn't leak into the first command's output
            $this->ssh->setTimeout(2);
            $this->ssh->read();

            return true;
        } catch (Throwable $e) {
            $this->ssh = null;

            return false;
        }
    }

    /**
     * @throws \RuntimeException if connect() wasn't called (or failed) first
     */
    public function exec(string $command, int $timeoutSeconds = 5): string
    {
        if (! $this->ssh) {
            throw new \RuntimeException('Not connected — call connect() first.');
        }

        $this->ssh->write(rtrim($command, "\r\n")."\n");
        $this->ssh->setTimeout($timeoutSeconds);

        return (string) $this->ssh->read();
    }

    public function disconnect(): void
    {
        $this->ssh?->disconnect();
        $this->ssh = null;
    }
}
