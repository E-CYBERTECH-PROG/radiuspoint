<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Throwable;

/**
 * Generic remote-terminal driver for GPON/EPON OLTs (VSOL, Hioso, and similar Chinese-vendor
 * hardware) — confirmed via each vendor's own documentation that both are managed through a
 * Cisco IOS-style text CLI over Telnet/SSH (`gpon-olt>` / `gpon-olt#` / `gpon-olt(config)#`
 * style prompts for VSOL; SSH/Telnet/CLI for Hioso), not a structured binary API the way
 * MikrotikApiService talks to RouterOS. There is no publicly-documented ONU-provisioning/status
 * command reference detailed enough to build parsed screens against without a real unit to
 * verify against (this project's established rule — see MikrotikApiService's own history of
 * RouterOS behavior that only revealed itself against real hardware). Until then, this service
 * deliberately stays at "open a session, send whatever the operator types, return whatever the
 * device prints back" — the same value a raw SSH client gives, just proxied through the app so
 * an admin doesn't need direct network access to the OLT (which may only be reachable from this
 * server's own network/VPN, mirroring the router terminal's reasoning).
 *
 * Reads by timeout-based quiescence (accumulate whatever arrives within a short window) rather
 * than matching a specific prompt regex, since the exact prompt string per brand/model/firmware
 * isn't confirmed. This is less precise than prompt-matching but works without vendor-specific
 * assumptions; revisit once a real device confirms the prompt format.
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

            // Drain the post-login banner/MOTD so it doesn't leak into the first real command's output.
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
