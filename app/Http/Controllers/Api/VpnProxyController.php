<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VpnProxyController extends Controller
{
    private const VPNGATE_APIS = [
        'https://www.vpngate.net/api/iphone/',
        'http://www.vpngate.net/api/iphone/',
    ];

    private const CACHE_KEY = 'vpngate_servers';

    private const CACHE_TTL = 1800; // 30 minutes

    private const STALE_TTL = 604800; // 7 days

    private const FREE_COUNTRIES = ['JP', 'US', 'KR'];

    /**
     * GET /api/v1/localvpn/proxy-servers
     *
     * Returns VPN Gate servers grouped by country.
     * Free users: limited to JP, US, KR.
     * Premium users: all countries.
     */
    public function servers(Request $request)
    {
        $machineId = $request->input('machine_id');
        if (! $machineId) {
            return response()->json(['success' => false, 'error' => 'machine_id required'], 400);
        }

        // Determine license tier — find the best active non-expired license
        $isPremium = false;
        $product = Product::where('slug', 'localvpn')->first();
        if ($product) {
            $license = LicenseKey::where('product_id', $product->id)
                ->where('machine_id', $machineId)
                ->where('status', 'active')
                ->whereNotIn('license_type', ['free', 'demo'])
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();

            $isPremium = $license !== null;
        }

        // Fetch and cache server list (VPN Gate + premium custom servers)
        // Use stale-while-revalidate pattern: keep old cache if fresh fetch fails
        $allServers = Cache::get(self::CACHE_KEY);
        if ($allServers === null) {
            $allServers = $this->fetchVpnGateServers();
            if (! empty($allServers)) {
                Cache::put(self::CACHE_KEY, $allServers, self::CACHE_TTL);
                Cache::put(self::CACHE_KEY . '_stale', $allServers, self::STALE_TTL);
            } else {
                // Fresh fetch failed — try stale cache
                $allServers = Cache::get(self::CACHE_KEY . '_stale', []);
            }
        }

        // Merge premium proxy servers from admin settings
        $premiumServers = [];
        if ($isPremium && Setting::getValue('localvpn_premium_proxy_enabled', '0') === '1') {
            $customJson = Setting::getValue('localvpn_premium_proxy_servers', '[]');
            $custom = json_decode($customJson, true);
            if (is_array($custom)) {
                $premiumServers = $custom;
                $allServers = array_merge($premiumServers, $allServers);
            }
        }

        if (empty($allServers)) {
            return response()->json([
                'success' => false,
                'error' => 'ไม่สามารถดึงรายการ VPN servers ได้',
            ], 503);
        }

        // Group by country
        $grouped = [];
        foreach ($allServers as $server) {
            $code = $server['country_code'];

            if (! $isPremium && ! in_array($code, self::FREE_COUNTRIES)) {
                continue;
            }

            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'country_code' => $code,
                    'country_name' => $server['country_name'],
                    'servers' => [],
                ];
            }

            $grouped[$code]['servers'][] = $server;
        }

        // Sort servers within each country by score (descending)
        foreach ($grouped as &$country) {
            usort($country['servers'], fn ($a, $b) => $b['score'] <=> $a['score']);
            $country['server_count'] = count($country['servers']);
            $country['best_speed'] = max(array_column($country['servers'], 'speed'));
        }
        unset($country);

        // Sort countries by server count
        $countries = array_values($grouped);
        usort($countries, fn ($a, $b) => $b['server_count'] <=> $a['server_count']);

        // Build list of all available country codes (for showing locked countries to free users)
        $allCountryCodes = [];
        if (! $isPremium) {
            $allGrouped = [];
            foreach ($allServers as $server) {
                $allGrouped[$server['country_code']] = $server['country_name'];
            }
            foreach ($allGrouped as $code => $name) {
                if (! in_array($code, self::FREE_COUNTRIES)) {
                    $allCountryCodes[] = ['country_code' => $code, 'country_name' => $name, 'locked' => true];
                }
            }
            usort($allCountryCodes, fn ($a, $b) => strcmp($a['country_name'], $b['country_name']));
        }

        return response()->json([
            'success' => true,
            'is_premium' => $isPremium,
            'free_countries' => self::FREE_COUNTRIES,
            'has_premium_servers' => ! empty($premiumServers),
            'countries' => $countries,
            'locked_countries' => $allCountryCodes,
        ]);
    }

    /**
     * Fetch and parse VPN Gate server list.
     */
    private function fetchVpnGateServers(): array
    {
        try {
            $csv = null;
            foreach (self::VPNGATE_APIS as $apiUrl) {
                try {
                    $response = Http::timeout(5)->connectTimeout(3)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                        ->get($apiUrl);

                    if ($response->successful() && strlen($response->body()) > 100) {
                        $csv = $response->body();
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (! $csv) {
                return [];
            }
            $lines = explode("\n", $csv);
            $servers = [];

            // Skip first line (*vpn_servers) and header line
            $headerLine = null;
            $dataStarted = false;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '*')) {
                    continue;
                }

                if (! $dataStarted) {
                    // First non-* line is the header
                    $headerLine = $line;
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
