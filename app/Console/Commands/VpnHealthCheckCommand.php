<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpnHealthCheckCommand extends Command
{
    protected $signature = 'vpn:health-check';

    protected $description = 'Health-check VPN Gate servers and cache only reachable ones';

    private const VPNGATE_API = 'https://www.vpngate.net/api/iphone/';

    private const RAW_CACHE_KEY = 'vpngate_servers';

    private const HEALTHY_CACHE_KEY = 'vpngate_servers_healthy';

    private const HEALTHY_TTL = 900; // 15 minutes

    private const STALE_HEALTHY_TTL = 3600; // 1 hour fallback

    private const TCP_TIMEOUT = 3; // seconds

    private const MAX_CONCURRENT = 20; // parallel checks per batch

    public function handle(): int
    {
        $this->info('Fetching VPN Gate server list...');

        // Use existing cached raw servers, or fetch fresh
        $servers = Cache::get(self::RAW_CACHE_KEY);
        if (empty($servers)) {
            $servers = $this->fetchServers();
            if (! empty($servers)) {
                Cache::put(self::RAW_CACHE_KEY, $servers, 1800);
                Cache::put(self::RAW_CACHE_KEY . '_stale', $servers, 604800);
            }
        }

        if (empty($servers)) {
            $this->error('No servers to check.');

            return self::FAILURE;
        }

        $this->info(sprintf('Checking %d servers...', count($servers)));

        $healthy = [];
        $failed = 0;

        // Process in batches for parallel TCP checks
        $batches = array_chunk($servers, self::MAX_CONCURRENT);

        foreach ($batches as $batch) {
            $results = $this->checkBatch($batch);
            foreach ($results as $i => $reachable) {
                if ($reachable) {
                    $healthy[] = $batch[$i];
                } else {
                    $failed++;
                }
            }
        }

        $this->info(sprintf('Healthy: %d, Unreachable: %d', count($healthy), $failed));

        if (! empty($healthy)) {
            Cache::put(self::HEALTHY_CACHE_KEY, $healthy, self::HEALTHY_TTL);
            Cache::put(self::HEALTHY_CACHE_KEY . '_stale', $healthy, self::STALE_HEALTHY_TTL);
        }

        Log::info('[VPN Health Check] Completed', [
            'total' => count($servers),
            'healthy' => count($healthy),
            'failed' => $failed,
        ]);

        return self::SUCCESS;
    }

    /**
     * TCP-connect test a batch of servers in parallel using stream_socket_client.
     *
     * @return array<int, bool>
     */
    private function checkBatch(array $batch): array
    {
        $results = [];
        $sockets = [];

        foreach ($batch as $i => $server) {
            $target = $this->extractRemote($server['openvpn_config'] ?? '');
            if (! $target) {
                $results[$i] = false;

                continue;
            }

            [$host, $port] = $target;

            $errno = 0;
            $errstr = '';
            $sock = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                self::TCP_TIMEOUT,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($sock === false) {
                $results[$i] = false;
            } else {
                stream_set_blocking($sock, false);
                $sockets[$i] = $sock;
            }
        }

        // Poll all async sockets
        if (! empty($sockets)) {
            $deadline = microtime(true) + self::TCP_TIMEOUT;

            while (! empty($sockets) && microtime(true) < $deadline) {
                $read = null;
                $write = array_values($sockets);
                $except = null;

                $changed = @stream_select($read, $write, $except, 0, 200000); // 200ms poll

                if ($changed > 0) {
                    foreach ($write as $sock) {
                        $idx = array_search($sock, $sockets, true);
                        if ($idx !== false) {
                            $results[$idx] = true;
                            fclose($sock);
                            unset($sockets[$idx]);
                        }
                    }
                }
            }

            // Remaining sockets timed out
            foreach ($sockets as $idx => $sock) {
                $results[$idx] = false;
                fclose($sock);
            }
        }

        return $results;
    }

    /**
     * Extract first remote host:port from base64-encoded OpenVPN config.
     *
     * @return array{0: string, 1: int}|null
     */
    private function extractRemote(string $base64Config): ?array
    {
        if (empty($base64Config)) {
            return null;
        }

        $config = @base64_decode($base64Config, true);
        if (! $config) {
            return null;
        }

        // Match: remote <host> <port>
        if (preg_match('/^remote\s+(\S+)\s+(\d+)/m', $config, $m)) {
            return [$m[1], (int) $m[2]];
        }

        return null;
    }

    /**
     * Fetch VPN Gate server list (same logic as VpnProxyController).
     */
    private function fetchServers(): array
    {
        try {
            $response = Http::timeout(10)->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get(self::VPNGATE_API);

            if (! $response->successful() || strlen($response->body()) < 100) {
                return [];
            }

            $lines = explode("\n", $response->body());
            $servers = [];
            $dataStarted = false;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '*')) {
                    continue;
                }

                if (! $dataStarted) {
                    $dataStarted = true;

                    continue;
                }

                $fields = str_getcsv($line);
                if (count($fields) < 15) {
                    continue;
                }

                $openvpnConfig = $fields[14] ?? '';
                if (empty($openvpnConfig)) {
                    continue;
                }

                $speed = (int) ($fields[4] ?? 0);
                if ($speed <= 0) {
                    continue;
                }

                $servers[] = [
                    'hostname' => $fields[0] ?? '',
                    'ip' => $fields[1] ?? '',
                    'score' => (int) ($fields[2] ?? 0),
                    'ping' => (int) ($fields[3] ?? 0),
                    'speed' => $speed,
                    'country_name' => $fields[5] ?? '',
                    'country_code' => $fields[6] ?? '',
                    'sessions' => (int) ($fields[7] ?? 0),
                    'uptime' => (int) ($fields[8] ?? 0),
                    'openvpn_config' => $openvpnConfig,
                ];
            }

            return $servers;
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }
}
