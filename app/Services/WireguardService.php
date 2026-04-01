<?php

namespace App\Services;

use App\Models\WireguardClient;
use App\Models\WireguardServer;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WireguardService
{
    /**
     * Run a command with sudo, using SUDO_PASS env var if available.
     */
    private function sudoRun(string $command, int $timeout = 60): ProcessResult
    {
        $rootPass = env('SUDO_PASS');
        if ($rootPass) {
            return Process::timeout($timeout)->input($rootPass . "\n")->run("su -c " . escapeshellarg($command) . " root");
        }

        return Process::timeout($timeout)->run("sudo {$command}");
    }

    /**
     * Generate a WireGuard key pair using libsodium (Curve25519).
     *
     * WireGuard uses Curve25519 for key exchange, which is the same as
     * X25519 used by sodium_crypto_box_keypair().
     *
     * @return array{private_key: string, public_key: string}
     */
    public function generateKeyPair(): array
    {
        // Try using the wg command first (most accurate)
        try {
            $result = Process::run('wg genkey');
            if ($result->successful()) {
                $privateKey = trim($result->output());
                $pubResult = Process::input($privateKey)->run('wg pubkey');
                if ($pubResult->successful()) {
                    return [
                        'private_key' => $privateKey,
                        'public_key' => trim($pubResult->output()),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fall through to sodium
        }

        // Fallback: use libsodium (available in PHP 7.2+)
        $keypair = sodium_crypto_box_keypair();
        $privateKey = sodium_crypto_box_secretkey($keypair);
        $publicKey = sodium_crypto_box_publickey($keypair);

        return [
            'private_key' => base64_encode($privateKey),
            'public_key' => base64_encode($publicKey),
        ];
    }

    /**
     * Register a client to a WireGuard server.
     *
     * @param  string  $machineId  The device's unique machine identifier
     * @param  string  $clientPublicKey  The client's WireGuard public key
     * @param  int|null  $serverId  Specific server ID, or null for auto-select
     * @return array{success: bool, client?: WireguardClient, config?: array, server?: array, error?: string}
     */
    public function registerClient(string $machineId, string $clientPublicKey, ?int $serverId = null): array
    {
        // Find the best server or use the specified one
        if ($serverId) {
            $server = WireguardServer::active()->healthy()->find($serverId);
            if (! $server) {
                return ['success' => false, 'error' => 'Specified server not found or unavailable'];
            }
        } else {
            $server = $this->findBestServer();
            if (! $server) {
                return ['success' => false, 'error' => 'No WireGuard servers available'];
            }
        }

        // Check if client already exists on this server
        $client = WireguardClient::where('server_id', $server->id)
            ->where('machine_id', $machineId)
            ->first();

        if ($client) {
            // Update the public key if changed
            if ($client->public_key !== $clientPublicKey) {
                // Remove old peer first
                $this->removePeerFromServer($server, $client->public_key);
                $client->update(['public_key' => $clientPublicKey]);
                // Add new peer
                if (! $this->addPeerToServer($server, $clientPublicKey, $client->assigned_ip)) {
                    return ['success' => false, 'error' => 'Failed to add peer to server'];
                }
            } else {
                // Re-add peer in case it was removed (e.g., after server restart)
                $this->addPeerToServer($server, $clientPublicKey, $client->assigned_ip);
            }

            $client->update([
                'is_connected' => true,
                'connected_at' => now(),
            ]);

            return $this->buildClientResponse($client, $server);
        }

        // Check capacity (only count connected clients)
        if ($server->isAtCapacity()) {
            // Try another server
            $server = $this->findBestServer();
            if (! $server || $server->isAtCapacity()) {
                return ['success' => false, 'error' => 'All servers are at capacity'];
            }
        }

        // Assign IP
        $assignedIp = $server->getNextAvailableIp();
        if (! $assignedIp) {
            return ['success' => false, 'error' => 'No available IP addresses on server'];
        }

        // Add peer to WireGuard interface first
        if (! $this->addPeerToServer($server, $clientPublicKey, $assignedIp)) {
            return ['success' => false, 'error' => 'Failed to configure peer on server'];
        }

        // Create client record
        $client = WireguardClient::create([
            'server_id' => $server->id,
            'machine_id' => $machineId,
            'public_key' => $clientPublicKey,
            'assigned_ip' => $assignedIp,
            'is_connected' => true,
            'connected_at' => now(),
        ]);

        return $this->buildClientResponse($client, $server);
    }

    /**
     * Remove a client from a WireGuard server.
     */
    public function removeClient(string $machineId, int $serverId): bool
    {
        $client = WireguardClient::where('server_id', $serverId)
            ->where('machine_id', $machineId)
            ->first();

        if (! $client) {
            return false;
        }

        $server = $client->server;

        // Remove peer from WireGuard interface
        $this->removePeerFromServer($server, $client->public_key);

        // Mark as disconnected (keep record for re-connection)
        $client->update([
            'is_connected' => false,
        ]);

        return true;
    }

    /**
     * Add a peer to a WireGuard server's interface.
     */
    public function addPeerToServer(WireguardServer $server, string $publicKey, string $assignedIp): bool
    {
        $interface = $server->getInterfaceName();
        $command = sprintf(
            'wg set %s peer %s allowed-ips %s/32',
            escapeshellarg($interface),
            escapeshellarg($publicKey),
            escapeshellarg($assignedIp)
        );

        try {
            $result = $this->sudoRun($command);

            if (! $result->successful()) {
                Log::error("[WireGuard] Failed to add peer to {$server->name}: {$result->errorOutput()}");

                return false;
            }

            Log::info("[WireGuard] Added peer {$assignedIp} to {$server->name}");

            return true;
        } catch (\Exception $e) {
            Log::error("[WireGuard] Exception adding peer to {$server->name}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Remove a peer from a WireGuard server's interface.
     */
    public function removePeerFromServer(WireguardServer $server, string $publicKey): bool
    {
        $interface = $server->getInterfaceName();
        $command = sprintf(
            'wg set %s peer %s remove',
            escapeshellarg($interface),
            escapeshellarg($publicKey)
        );

        try {
            $result = $this->sudoRun($command);

            if (! $result->successful()) {
                Log::error("[WireGuard] Failed to remove peer from {$server->name}: {$result->errorOutput()}");

                return false;
            }

            Log::info("[WireGuard] Removed peer from {$server->name}");

            return true;
        } catch (\Exception $e) {
            Log::error("[WireGuard] Exception removing peer from {$server->name}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get live status of a WireGuard server by running `wg show`.
     *
     * @return array{interface: string, public_key: string, listening_port: int, peers: array}
     */
    public function getServerStatus(WireguardServer $server): array
    {
        $interface = $server->getInterfaceName();

        try {
            $result = $this->sudoRun(sprintf('wg show %s', escapeshellarg($interface)));

            if (! $result->successful()) {
                return [
                    'interface' => $interface,
                    'is_up' => false,
                    'error' => trim($result->errorOutput()),
                    'peers' => [],
                ];
            }

            return $this->parseWgShow($result->output(), $interface);
        } catch (\Exception $e) {
            return [
                'interface' => $interface,
                'is_up' => false,
                'error' => $e->getMessage(),
                'peers' => [],
            ];
        }
    }

    /**
     * Find the best available WireGuard server (least loaded).
     */
    public function findBestServer(?string $countryCode = null, bool $isPremium = false): ?WireguardServer
    {
        $query = WireguardServer::active()->healthy();

        if ($countryCode) {
            $query->where('country_code', strtoupper($countryCode));
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            // If filtering by country returned nothing, try any server
            if ($countryCode) {
                $servers = WireguardServer::active()->healthy()->get();
            }

            if ($servers->isEmpty()) {
                return null;
            }
        }

        // Return the server with the least clients (lowest load)
        return $servers->sortBy(fn ($s) => $s->getCurrentLoad())->first();
    }

    /**
     * Generate the WireGuard client configuration string.
     */
    public function getClientConfig(WireguardClient $client): string
    {
        return $client->generateConfig();
    }

    /**
     * Parse the output of `wg show <interface>`.
     */
    private function parseWgShow(string $output, string $interface): array
    {
        $result = [
            'interface' => $interface,
            'is_up' => true,
            'public_key' => '',
            'listening_port' => 0,
            'peers' => [],
        ];

        $currentPeer = null;
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^peer:\s*(.+)$/', $line, $m)) {
                if ($currentPeer) {
                    $result['peers'][] = $currentPeer;
                }
                $currentPeer = [
                    'public_key' => trim($m[1]),
                    'endpoint' => '',
                    'allowed_ips' => '',
                    'latest_handshake' => null,
                    'transfer_rx' => 0,
                    'transfer_tx' => 0,
                ];

                continue;
            }

            if (preg_match('/^public key:\s*(.+)$/', $line, $m)) {
                $result['public_key'] = trim($m[1]);
            } elseif (preg_match('/^listening port:\s*(\d+)$/', $line, $m)) {
                $result['listening_port'] = (int) $m[1];
            } elseif ($currentPeer !== null) {
                if (preg_match('/^endpoint:\s*(.+)$/', $line, $m)) {
                    $currentPeer['endpoint'] = trim($m[1]);
                } elseif (preg_match('/^allowed ips:\s*(.+)$/', $line, $m)) {
                    $currentPeer['allowed_ips'] = trim($m[1]);
                } elseif (preg_match('/^latest handshake:\s*(.+)$/', $line, $m)) {
                    $currentPeer['latest_handshake'] = trim($m[1]);
                } elseif (preg_match('/^transfer:\s*(.+)\s+received,\s*(.+)\s+sent$/', $line, $m)) {
                    $currentPeer['transfer_rx'] = $this->parseTransferSize(trim($m[1]));
                    $currentPeer['transfer_tx'] = $this->parseTransferSize(trim($m[2]));
                }
            }
        }

        if ($currentPeer) {
            $result['peers'][] = $currentPeer;
        }

        return $result;
    }

    /**
     * Parse transfer size string (e.g. "1.23 MiB") to bytes.
     */
    private function parseTransferSize(string $size): int
    {
        if (preg_match('/^([\d.]+)\s*(B|KiB|MiB|GiB|TiB)$/i', $size, $m)) {
            $value = (float) $m[1];
            $unit = strtolower($m[2]);

            return (int) match ($unit) {
                'b' => $value,
                'kib' => $value * 1024,
                'mib' => $value * 1024 * 1024,
                'gib' => $value * 1024 * 1024 * 1024,
                'tib' => $value * 1024 * 1024 * 1024 * 1024,
                default => $value,
            };
        }

        return 0;
    }

    /**
     * Build the standard response array for a registered client.
     */
    private function buildClientResponse(WireguardClient $client, WireguardServer $server): array
    {
        return [
            'success' => true,
            'client' => $client,
            'config' => [
                'interface' => [
                    'address' => $client->assigned_ip . '/32',
                    'dns' => $server->dns,
                ],
                'peer' => [
                    'public_key' => $server->public_key,
                    'endpoint' => $server->endpoint,
                    'allowed_ips' => '0.0.0.0/0, ::/0',
                    'persistent_keepalive' => 25,
                ],
            ],
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'country_code' => $server->country_code,
                'country_name' => $server->country_name,
            ],
        ];
    }
}
