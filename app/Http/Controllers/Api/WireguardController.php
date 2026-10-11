<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Services\LocalVpnFleet\ExitAccess;
use App\Services\LocalVpnFleet\MachineLicenses;
use App\Services\WireguardService;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnLicenses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WireguardController extends Controller
{
    public function __construct(
        private readonly WireguardService $wireguard,
        private readonly ExitAccess $exits,
    ) {}

    /**
     * POST /api/v1/localvpn/wireguard/register
     *
     * Register device and get WireGuard tunnel config.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'public_key' => 'required|string|max:255',
            'license_key' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'server_id' => 'nullable|integer|exists:wireguard_servers,id',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid license or device.',
            ], 403);
        }

        $isPremium = $this->isPremiumLicense($license);
        $countryCode = $request->input('country_code') ? strtoupper($request->input('country_code')) : null;
        $serverId = $request->input('server_id');
        $machineId = $request->input('machine_id');

        // Enforce country restriction for free users
        $freeCountries = ['TH', 'JP', 'US', 'KR', 'SG', 'IN', 'GB', 'DE', 'AU', 'CA'];

        // Exits on fleet nodes follow the fleet policy (vpn mode = Pro only, game mode = tiers
        // with game: true, node budget, daily exit cap); the web server's own exit keeps the
        // free-country rule.
        $allowed = fn (WireguardServer $server) => $server->isNodeManaged()
            ? $this->exits->refusal($server, $machineId, $license) === null
            : ($isPremium || in_array($server->country_code, $freeCountries));

        // If a specific server ID is given, verify access
        if ($serverId) {
            $targetServer = WireguardServer::find($serverId);
            if ($targetServer && $targetServer->isNodeManaged()) {
                if ($refusal = $this->exits->refusal($targetServer, $machineId, $license)) {
                    return response()->json(['success' => false, 'error' => $refusal['error']], $refusal['status']);
                }
            } elseif ($targetServer && ! $isPremium && ! in_array($targetServer->country_code, $freeCountries)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Premium subscription required for this server.',
                ], 403);
            }
        }

        if ($countryCode && ! $serverId) {
            $inCountry = WireguardServer::active()->healthy()->where('country_code', $countryCode)->get();
            $fleetOnly = $inCountry->isNotEmpty() && $inCountry->every(fn (WireguardServer $s) => $s->isNodeManaged());

            // Only fleet nodes serve this country and none will take this device: say why,
            // instead of quietly connecting it somewhere else.
            if ($fleetOnly && ! $inCountry->contains($allowed)) {
                $refusal = $this->exits->refusal($inCountry->first(), $machineId, $license);

                return response()->json([
                    'success' => false,
                    'error' => $refusal['error'] ?? 'เซิร์ฟเวอร์นี้เต็มชั่วคราว กรุณาลองใหม่ภายหลัง',
                ], $refusal['status'] ?? 503);
            }

            // If a country code is specified, verify access
            if (! $fleetOnly && ! $isPremium && ! in_array($countryCode, $freeCountries)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Premium subscription required for this country.',
                ], 403);
            }

            // Find a server in that country
            $server = $this->wireguard->findBestServer($countryCode, $isPremium, $allowed);
            $serverId = $server?->id;
        }

        $result = $this->wireguard->registerClient(
            $machineId,
            $request->input('public_key'),
            $serverId,
            $allowed,
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 503);
        }

        return response()->json([
            'success' => true,
            'config' => $result['config'],
            'server' => $result['server'],
        ]);
    }

    /**
     * GET /api/v1/localvpn/wireguard/servers
     *
     * List available WireGuard servers.
     */
    public function servers(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'nullable|string|max:255',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid license or device.',
            ], 403);
        }

        $isPremium = $this->isPremiumLicense($license);

        // Free countries (same as VPN proxy)
        $freeCountries = ['TH', 'JP', 'US', 'KR', 'SG', 'IN', 'GB', 'DE', 'AU', 'CA'];

        $allServers = WireguardServer::active()->healthy()->with('node')->get()
            // Fleet exits whose node has gone quiet are not offered at all.
            ->filter(fn (WireguardServer $server) => ! $server->isNodeManaged() || $this->exits->usable($server));

        $policy = FleetPolicy::current();
        $tier = app(MachineLicenses::class)->tierFor($request->input('machine_id'), $license);

        $available = [];
        $locked = [];

        foreach ($allServers as $server) {
            $mode = $server->node?->exitMode();
            $serverData = [
                'id' => $server->id,
                'name' => $server->name,
                'country_code' => $server->country_code,
                'country_name' => $server->country_name,
                'endpoint' => $server->endpoint,
                'load' => $server->getCurrentLoad(),
                'is_healthy' => $server->is_healthy,
                'mode' => $mode,
            ];

            $open = $server->isNodeManaged()
                ? ExitAccess::tierAllowed($mode, $tier, $policy)
                : ($isPremium || in_array($server->country_code, $freeCountries));

            if ($open) {
                $available[] = $serverData;
            } else {
                $locked[] = $serverData;
            }
        }

        return response()->json([
            'success' => true,
            'is_premium' => $isPremium,
            'servers' => $available,
            'locked_servers' => $locked,
        ]);
    }

    /**
     * POST /api/v1/localvpn/wireguard/disconnect
     *
     * Mark client as disconnected.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'nullable|string|max:255',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid license or device.',
            ], 403);
        }

        $machineId = $request->input('machine_id');

        // Find all connected clients for this machine and disconnect them
        $clients = WireguardClient::where('machine_id', $machineId)
            ->where('is_connected', true)
            ->get();

        if ($clients->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No active connections found.',
            ]);
        }

        foreach ($clients as $client) {
            $this->wireguard->removePeerFromServer($client->server, $client->public_key);
            $client->update(['is_connected' => false]);
        }

        return response()->json([
            'success' => true,
            'disconnected' => $clients->count(),
        ]);
    }

    /**
     * GET /api/v1/localvpn/wireguard/status
     *
     * WireGuard service status (for admin dashboard / monitoring).
     */
    public function status(): JsonResponse
    {
        $servers = WireguardServer::all();
        $wireguardActive = $servers->where('is_active', true)->isNotEmpty();

        $serverStatuses = [];
        foreach ($servers as $server) {
            $liveStatus = $this->wireguard->getServerStatus($server);

            $serverStatuses[] = [
                'id' => $server->id,
                'name' => $server->name,
                'country_code' => $server->country_code,
                'endpoint' => $server->endpoint,
                'total_peers' => $server->clients()->count(),
                'active_peers' => $server->clients()->where('is_connected', true)->count(),
                'is_healthy' => $server->is_healthy,
                'is_active' => $server->is_active,
                'is_up' => $liveStatus['is_up'] ?? false,
                'load' => $server->getCurrentLoad(),
                'last_health_check' => $server->last_health_check_at?->toISOString(),
            ];
        }

        return response()->json([
            'wireguard_active' => $wireguardActive,
            'servers' => $serverStatuses,
        ]);
    }

    /**
     * The device's key: the one it sent if it holds it, else its best key, else its FREE key (created).
     */
    private function validateDeviceAuth(Request $request): ?LicenseKey
    {
        return LocalVpnLicenses::forDevice($request->input('license_key'), $request->input('machine_id'));
    }

    /**
     * Pro (every country): a paid key or the running 30-day trial — the trial is the full Pro app
     * (owner 2026-10-11). It used to leave 'demo' out, so trial users saw only the free countries.
     */
    private function isPremiumLicense(LicenseKey $license): bool
    {
        return LocalVpnLicenses::isPremium($license);
    }
}
