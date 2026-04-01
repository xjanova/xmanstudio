<?php

namespace App\Console\Commands;

use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Services\WireguardService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WireguardHealthCheckCommand extends Command
{
    protected $signature = 'wireguard:health-check';

    protected $description = 'Health-check WireGuard servers and update peer status';

    /**
     * Stale threshold: mark clients as disconnected if no handshake in this many minutes.
     */
    private const STALE_MINUTES = 5;

    public function handle(WireguardService $wireguardService): int
    {
        $servers = WireguardServer::where('is_active', true)->get();

        if ($servers->isEmpty()) {
            $this->info('No active WireGuard servers to check.');

            return self::SUCCESS;
        }

        $totalServers = $servers->count();
        $healthyCount = 0;
        $totalPeers = 0;
        $stalePeers = 0;

        foreach ($servers as $server) {
            $this->info("Checking server: {$server->name} ({$server->endpoint})...");

            $status = $wireguardService->getServerStatus($server);
            $isUp = $status['is_up'] ?? false;

            // Update server health
            $server->update([
                'is_healthy' => $isUp,
                'last_health_check_at' => now(),
            ]);

            if (! $isUp) {
                $error = $status['error'] ?? 'Unknown error';
                $this->warn("  Server {$server->name} is DOWN: {$error}");
                Log::warning("[WireGuard Health Check] Server {$server->name} is DOWN: {$error}");

                continue;
            }

            $healthyCount++;
            $livePeers = $status['peers'] ?? [];
            $totalPeers += count($livePeers);

            $peerCount = count($livePeers);
            $this->info("  Interface is UP, {$peerCount} live peers");

            // Build a lookup of live peers by public key
            $livePeerMap = [];
            foreach ($livePeers as $peer) {
                $livePeerMap[$peer['public_key']] = $peer;
            }

            // Update client records from live data
            $clients = WireguardClient::where('server_id', $server->id)->get();
            foreach ($clients as $client) {
                $livePeer = $livePeerMap[$client->public_key] ?? null;

                if ($livePeer) {
                    // Parse latest handshake - if it's a relative time string like "1 minute, 23 seconds ago"
                    $handshakeAt = $this->parseHandshakeTime($livePeer['latest_handshake'] ?? null);
                    $isStale = $handshakeAt && $handshakeAt->diffInMinutes(now()) > self::STALE_MINUTES;

                    $client->update([
                        'bytes_rx' => $livePeer['transfer_rx'] ?? $client->bytes_rx,
                        'bytes_tx' => $livePeer['transfer_tx'] ?? $client->bytes_tx,
                        'last_handshake_at' => $handshakeAt ?? $client->last_handshake_at,
                        'is_connected' => ! $isStale,
                    ]);

                    if ($isStale) {
                        $stalePeers++;
                        // Remove stale peer from WireGuard interface
                        $wireguardService->removePeerFromServer($server, $client->public_key);
                        $this->line("  Removed stale peer: {$client->assigned_ip} (last handshake: {$handshakeAt})");
                    }
                } else {
                    // Peer not in live wg show output — might have been removed or never connected
                    if ($client->is_connected) {
                        $client->update(['is_connected' => false]);
                        $stalePeers++;
                        $this->line("  Marked disconnected (not in wg show): {$client->assigned_ip}");
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Health check complete: {$healthyCount}/{$totalServers} servers healthy, {$totalPeers} live peers, {$stalePeers} stale/disconnected");

        Log::info("[WireGuard Health Check] {$healthyCount}/{$totalServers} servers healthy, {$totalPeers} live peers, {$stalePeers} marked stale");

        return self::SUCCESS;
    }

    /**
     * Parse WireGuard handshake time string to a Carbon instance.
     * WireGuard outputs times like "1 minute, 23 seconds ago" or a Unix timestamp.
     */
    private function parseHandshakeTime(?string $handshakeStr): ?Carbon
    {
        if (empty($handshakeStr) || $handshakeStr === '(none)') {
            return null;
        }

        // If it looks like a relative time "X minutes, Y seconds ago"
        if (str_contains($handshakeStr, 'ago')) {
            $totalSeconds = 0;

            if (preg_match('/(\d+)\s*hour/', $handshakeStr, $m)) {
                $totalSeconds += (int) $m[1] * 3600;
            }
            if (preg_match('/(\d+)\s*minute/', $handshakeStr, $m)) {
                $totalSeconds += (int) $m[1] * 60;
            }
            if (preg_match('/(\d+)\s*second/', $handshakeStr, $m)) {
                $totalSeconds += (int) $m[1];
            }

            return now()->subSeconds($totalSeconds);
        }

        // If it's a Unix timestamp
        if (is_numeric($handshakeStr)) {
            return Carbon::createFromTimestamp((int) $handshakeStr);
        }

        return null;
    }
}
