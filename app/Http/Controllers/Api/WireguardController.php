<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\WireguardClient;
use App\Models\WireguardServer;
use App\Services\WireguardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WireguardController extends Controller
{
    public function __construct(
        private readonly WireguardService $wireguard,
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
        $countryCode = $request->input('country_code');
        $serverId = $request->input('server_id');

        // If a country code is specified but no server ID, find server in that country
        if ($countryCode && ! $serverId) {
            $server = $this->wireguard->findBestServer($countryCode, $isPremium);
            $serverId = $server?->id;
        }

        $result = $this->wireguard->registerClient(
            $request->input('machine_id'),
            $request->input('public_key'),
            $serverId,
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

        $allServers = WireguardServer::active()->healthy()->get();

        $available = [];
        $locked = [];

        foreach ($allServers as $server) {
            $serverData = [
                'id' => $server->id,
                'name' => $server->name,
                'country_code' => $server->country_code,
                'country_name' => $server->country_name,
                'endpoint' => $server->endpoint,
                'load' => $server->getCurrentLoad(),
                'is_healthy' => $server->is_healthy,
            ];

            if ($isPremium || in_array($server->country_code, $freeCountries)) {
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
        ]);

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
     * Validate device authentication (mirrors LocalVpnRelayController logic).
     */
    private function validateDeviceAuth(Request $request): ?LicenseKey
    {
        $licenseKey = $request->input('license_key');
        $machineId = $request->input('machine_id');

        if (! $machineId) {
            return null;
        }

        $product = Product::where('slug', 'localvpn')->where('requires_license', true)->first();
        if (! $product) {
            return null;
        }

        // Try license_key + machine_id first (paid users)
        if ($licenseKey) {
            $license = LicenseKey::where('license_key', $licenseKey)
                ->where('product_id', $product->id)
                ->where('machine_id', $machineId)
                ->where('status', 'active')
                ->first();

            if ($license && ! $license->isExpired()) {
                return $license;
            }
        }

        // Fallback: find any active non-expired license for this machine_id
        $license = LicenseKey::where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->where('status', 'active')
            ->orderByRaw("FIELD(license_type, 'lifetime','yearly','monthly','weekly','daily','free','demo')")
            ->get()
            ->first(fn ($l) => ! $l->isExpired());

        if ($license) {
            return $license;
        }

        // Self-healing: auto-create free license if none exists
        return LicenseKey::firstOrCreate(
            [
                'product_id' => $product->id,
                'machine_id' => $machineId,
                'license_type' => 'free',
                'status' => 'active',
            ],
            [
                'license_key' => 'FREE-' . strtoupper(Str::random(20)),
                'machine_fingerprint' => $machineId,
                'activated_at' => now(),
                'max_activations' => 1,
                'activations' => 1,
            ]
        );
    }

    /**
     * Check if a license is premium (non-free, non-demo, not expired).
     */
    private function isPremiumLicense(LicenseKey $license): bool
    {
        return ! in_array($license->license_type, ['free', 'demo'])
            && ! $license->isExpired();
    }
}
