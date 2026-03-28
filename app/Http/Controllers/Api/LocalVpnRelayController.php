<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\Setting;
use App\Models\VpnNetwork;
use App\Models\VpnNetworkMember;
use App\Models\VpnRelaySession;
use App\Models\VpnTrafficLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LocalVpnRelayController extends Controller
{
    /**
     * Create a new VPN network.
     * POST /api/v1/localvpn/networks
     */
    public function createNetwork(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:4|max:64',
            'max_members' => 'nullable|integer|min:2|max:254',
            'is_public' => 'nullable|boolean',
            'virtual_subnet' => ['nullable', 'string', 'max:18', 'regex:/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/'],
            'license_key' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);

        // Validate license + device
        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid license or device.',
            ], 403);
        }

        // Check network limit per user (paid) or per machine (free)
        $maxNetworks = (int) Setting::getValue('localvpn_max_networks_per_user', '5');
        if ($license->user_id) {
            $userNetworkCount = VpnNetwork::where('owner_user_id', $license->user_id)->count();
        } else {
            // Free users: count by machine_id stored in owner_device_id
            $userNetworkCount = VpnNetwork::where('owner_device_id', $request->input('machine_id'))->count();
        }

        if ($userNetworkCount >= $maxNetworks) {
            return response()->json([
                'success' => false,
                'error' => "Maximum number of networks ({$maxNetworks}) reached.",
            ], 429);
        }

        // Generate unique slug (with random suffix to prevent race conditions)
        $baseSlug = Str::slug($request->input('name'));
        if (empty($baseSlug)) {
            $baseSlug = 'network';
        }
        $slug = $baseSlug;
        $counter = 1;
        while (VpnNetwork::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            if ($counter > 100) {
                // Append random suffix to guarantee uniqueness
                $slug = $baseSlug . '-' . Str::random(6);
                break;
            }
        }

        // Validate subnet IP if provided
        $subnet = $request->input('virtual_subnet', '10.10.0.0/24');
        $subnetParts = explode('/', $subnet);
        if (ip2long($subnetParts[0]) === false || (int) ($subnetParts[1] ?? 0) < 16 || (int) ($subnetParts[1] ?? 0) > 30) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid virtual subnet. Use CIDR format like 10.10.0.0/24 (prefix 16-30).',
            ], 422);
        }

        $maxMembersLimit = (int) Setting::getValue('localvpn_max_members_per_network', '10');
        $requestedMax = $request->input('max_members', $maxMembersLimit);

        $network = VpnNetwork::create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'description' => $request->input('description'),
            'password_hash' => $request->input('password') ? Hash::make($request->input('password')) : null,
            'owner_user_id' => $license->user_id,
            'owner_device_id' => $request->input('machine_id'),
            'max_members' => min($requestedMax, $maxMembersLimit),
            'is_public' => $request->boolean('is_public', true),
            'virtual_subnet' => $request->input('virtual_subnet', '10.10.0.0/24'),
        ]);

        // Auto-join creator as first member
        $virtualIp = $network->assignNextVirtualIp();
        $member = VpnNetworkMember::create([
            'network_id' => $network->id,
            'device_id' => $request->input('device_id'),
            'machine_id' => $request->input('machine_id'),
            'display_name' => $request->input('display_name', 'Owner'),
            'virtual_ip' => $virtualIp,
            'public_ip' => $request->ip(),
            'public_port' => $request->input('public_port'),
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'joined_at' => now(),
        ]);

        // Log creation
        VpnTrafficLog::create([
            'network_id' => $network->id,
            'member_id' => $member->id,
            'action' => 'network_create',
            'ip_address' => $request->ip(),
            'metadata' => ['license_key' => Str::mask($request->input('license_key'), '*', 4, -4)],
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'network' => [
                'id' => $network->id,
                'name' => $network->name,
                'slug' => $network->slug,
                'description' => $network->description,
                'virtual_subnet' => $network->virtual_subnet,
                'max_members' => $network->max_members,
                'is_public' => $network->is_public,
                'has_password' => $network->password_hash !== null,
                'member_count' => 1,
                'online_count' => 1,
                'created_at' => $network->created_at->toISOString(),
            ],
            'member' => $this->formatMember($member),
        ], 201);
    }

    /**
     * List public networks (paginated).
     * GET /api/v1/localvpn/networks
     */
    public function listNetworks(Request $request): JsonResponse
    {
        $query = VpnNetwork::active()->public()
            ->withCount(['members', 'members as online_count' => function ($q) {
                $q->where('is_online', true);
            }]);

        if ($search = $request->get('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $networks = $query->orderByDesc('created_at')->paginate(20);

        $data = $networks->getCollection()->map(function ($n) {
            return [
                'id' => $n->id,
                'name' => $n->name,
                'slug' => $n->slug,
                'description' => $n->description,
                'member_count' => $n->members_count,
                'online_count' => $n->online_count,
                'max_members' => $n->max_members,
                'has_password' => $n->password_hash !== null,
                'virtual_subnet' => $n->virtual_subnet,
                'created_at' => $n->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'networks' => $data,
            'pagination' => [
                'current_page' => $networks->currentPage(),
                'last_page' => $networks->lastPage(),
                'per_page' => $networks->perPage(),
                'total' => $networks->total(),
            ],
        ]);
    }

    /**
     * Join a network.
     * POST /api/v1/localvpn/networks/join
     */
    public function joinNetwork(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'device_id' => 'nullable|string|max:255',
            'display_name' => 'required|string|max:100',
            'password' => 'nullable|string',
            'public_ip' => 'nullable|ip',
            'public_port' => 'nullable|integer|min:1|max:65535',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))
            ->active()
            ->first();

        if (! $network) {
            return response()->json([
                'success' => false,
                'error' => 'Network not found or inactive.',
            ], 404);
        }

        // Check password for private networks
        if ($network->password_hash) {
            if (! $request->input('password') || ! Hash::check($request->input('password'), $network->password_hash)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid network password.',
                ], 403);
            }
        }

        // Check if already a member
        $existingMember = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if ($existingMember) {
            // Re-join: update info and go online
            $existingMember->update([
                'display_name' => $request->input('display_name'),
                'device_id' => $request->input('device_id'),
                'public_ip' => $request->input('public_ip', $request->ip()),
                'public_port' => $request->input('public_port'),
                'is_online' => true,
                'last_heartbeat_at' => now(),
            ]);

            VpnTrafficLog::create([
                'network_id' => $network->id,
                'member_id' => $existingMember->id,
                'action' => 'join',
                'ip_address' => $request->ip(),
                'metadata' => ['rejoin' => true],
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'member' => $this->formatMember($existingMember),
                'network' => $this->formatNetworkInfo($network),
            ]);
        }

        // Check if network is full
        if ($network->isFull()) {
            return response()->json([
                'success' => false,
                'error' => 'Network is full.',
            ], 409);
        }

        // Freemium: check if network owner has a paid license
        // Free users are limited to 5 members per network
        $ownerHasPaidLicense = false;
        if ($network->owner_user_id) {
            $ownerHasPaidLicense = LicenseKey::where('user_id', $network->owner_user_id)
                ->where('status', 'active')
                ->where('license_type', '!=', 'free')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();
        } else {
            // Owner has no user_id — check by machine_id via owner_device_id
            $ownerHasPaidLicense = LicenseKey::where('machine_id', $network->owner_device_id)
                ->where('status', 'active')
                ->where('license_type', '!=', 'free')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->exists();
        }

        if (! $ownerHasPaidLicense) {
            $currentMemberCount = VpnNetworkMember::where('network_id', $network->id)->count();
            if ($currentMemberCount >= 5) {
                return response()->json([
                    'success' => false,
                    'error' => 'Network is limited to 5 members. Owner must upgrade to premium.',
                ], 403);
            }
        }

        // Assign virtual IP
        $virtualIp = $network->assignNextVirtualIp();

        $member = VpnNetworkMember::create([
            'network_id' => $network->id,
            'device_id' => $request->input('device_id'),
            'machine_id' => $request->input('machine_id'),
            'display_name' => $request->input('display_name'),
            'virtual_ip' => $virtualIp,
            'public_ip' => $request->input('public_ip', $request->ip()),
            'public_port' => $request->input('public_port'),
            'is_online' => true,
            'last_heartbeat_at' => now(),
            'joined_at' => now(),
        ]);

        // Log join
        VpnTrafficLog::create([
            'network_id' => $network->id,
            'member_id' => $member->id,
            'action' => 'join',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'member' => $this->formatMember($member),
            'network' => $this->formatNetworkInfo($network),
        ], 201);
    }

    /**
     * Leave a network.
     * POST /api/v1/localvpn/networks/leave
     */
    public function leaveNetwork(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->first();

        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $member = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'error' => 'Not a member of this network.'], 404);
        }

        // Prevent owner from leaving (must delete network instead)
        $isOwner = ($network->owner_user_id && $license->user_id === $network->owner_user_id)
            || ($network->owner_device_id === $request->input('machine_id'));

        if ($isOwner) {
            return response()->json([
                'success' => false,
                'error' => 'Network owner cannot leave. Delete the network instead.',
            ], 403);
        }

        // End active relay sessions
        VpnRelaySession::where(function ($q) use ($member) {
            $q->where('source_member_id', $member->id)
                ->orWhere('target_member_id', $member->id);
        })->where('is_active', true)->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        // Log leave
        VpnTrafficLog::create([
            'network_id' => $network->id,
            'member_id' => $member->id,
            'action' => 'leave',
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        $member->delete();

        return response()->json(['success' => true, 'message' => 'Left the network.']);
    }

    /**
     * Heartbeat: update online status, get peer list.
     * POST /api/v1/localvpn/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'public_ip' => 'nullable|ip',
            'public_port' => 'nullable|integer|min:1|max:65535',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->first();

        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        // Inform client if network has been deactivated
        if (! $network->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Network has been deactivated.',
                'network_active' => false,
            ], 403);
        }

        $member = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'error' => 'Not a member of this network.'], 404);
        }

        // Update heartbeat
        $member->updateHeartbeat(
            $request->input('public_ip', $request->ip()),
            $request->input('public_port')
        );

        // Mark stale members as offline
        $timeout = (int) Setting::getValue('localvpn_session_timeout', '120');
        VpnNetworkMember::where('network_id', $network->id)
            ->where('is_online', true)
            ->where('last_heartbeat_at', '<', now()->subSeconds($timeout))
            ->update(['is_online' => false]);

        // Get peer list (all online members including self)
        $peers = VpnNetworkMember::where('network_id', $network->id)
            ->where('is_online', true)
            ->get()
            ->map(fn ($p) => $this->formatMember($p));

        return response()->json([
            'success' => true,
            'peers' => $peers,
            'network_active' => $network->is_active,
        ]);
    }

    /**
     * Get members in a network.
     * GET /api/v1/localvpn/networks/{slug}/members
     */
    public function getMembers(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $slug)->first();

        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        // Verify requester is a member of this network
        $requester = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $requester) {
            return response()->json(['success' => false, 'error' => 'Not a member of this network.'], 403);
        }

        $members = VpnNetworkMember::where('network_id', $network->id)
            ->orderByDesc('is_online')
            ->orderByDesc('last_heartbeat_at')
            ->get()
            ->map(fn ($m) => $this->formatMember($m));

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Relay data between peers (when P2P fails).
     * POST /api/v1/localvpn/relay
     */
    public function relayData(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'source_machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'target_virtual_ip' => 'required|string',
            'data' => 'required|string|max:65536', // base64 encoded
        ]);

        // Validate auth using source_machine_id as machine_id
        $request->merge(['machine_id' => $request->input('source_machine_id')]);
        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->active()->first();

        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found or inactive.'], 404);
        }

        $sourceMember = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('source_machine_id'))
            ->first();

        if (! $sourceMember) {
            return response()->json(['success' => false, 'error' => 'Source is not a member.'], 403);
        }

        $targetMember = VpnNetworkMember::where('network_id', $network->id)
            ->where('virtual_ip', $request->input('target_virtual_ip'))
            ->where('is_online', true)
            ->first();

        if (! $targetMember) {
            return response()->json(['success' => false, 'error' => 'Target member not found or offline.'], 404);
        }

        // Check data relay limit
        $dataLimitMb = (int) Setting::getValue('localvpn_data_relay_limit_mb', '100');
        $dataBytes = strlen(base64_decode($request->input('data'), true) ?: '');

        // Check daily usage
        $todayBytes = VpnTrafficLog::where('network_id', $network->id)
            ->where('action', 'data_relay')
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('bytes');

        if (($todayBytes + $dataBytes) > ($dataLimitMb * 1024 * 1024)) {
            return response()->json([
                'success' => false,
                'error' => 'Daily relay data limit exceeded.',
            ], 429);
        }

        // Find or create relay session
        $session = VpnRelaySession::firstOrCreate(
            [
                'network_id' => $network->id,
                'source_member_id' => $sourceMember->id,
                'target_member_id' => $targetMember->id,
                'is_active' => true,
            ],
            [
                'started_at' => now(),
                'bytes_relayed' => 0,
            ]
        );

        $session->increment('bytes_relayed', $dataBytes);

        // Log relay
        VpnTrafficLog::create([
            'network_id' => $network->id,
            'member_id' => $sourceMember->id,
            'action' => 'data_relay',
            'bytes' => $dataBytes,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'relayed_bytes' => $dataBytes,
            'target' => [
                'display_name' => $targetMember->display_name,
                'virtual_ip' => $targetMember->virtual_ip,
                'public_ip' => $targetMember->public_ip,
                'public_port' => $targetMember->public_port,
            ],
        ]);
    }

    /**
     * Delete own network.
     * DELETE /api/v1/localvpn/networks/{slug}
     */
    public function deleteNetwork(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|max:255',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $query = VpnNetwork::where('slug', $slug);
        if ($license->user_id) {
            $query->where('owner_user_id', $license->user_id);
        } else {
            $query->where('owner_device_id', $request->input('machine_id'));
        }
        $network = $query->first();

        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found or not owned by you.'], 404);
        }

        // Log deletion BEFORE deleting (cascade would delete logs otherwise)
        // After migration fix, network_id is nullable so we set it to null
        // and store the original network info in metadata for audit trail
        $networkId = $network->id;
        $networkSlug = $network->slug;
        $networkName = $network->name;

        // Delete network (cascades: members, sessions, existing traffic logs)
        $network->delete();

        // Create audit log with null network_id (network no longer exists)
        VpnTrafficLog::create([
            'network_id' => null,
            'action' => 'network_delete',
            'ip_address' => $request->ip(),
            'metadata' => [
                'slug' => $networkSlug,
                'name' => $networkName,
                'deleted_network_id' => $networkId,
            ],
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Network deleted.']);
    }

    /**
     * STUN-like endpoint: return the caller's public IP and port.
     * GET /api/v1/localvpn/stun
     */
    public function stun(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'ip' => $request->ip(),
            'port' => $request->server('REMOTE_PORT'),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send a P2P signaling message to a specific peer.
     * POST /api/v1/localvpn/signal
     */
    public function signal(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'target_virtual_ip' => 'required|string',
            'type' => 'required|string|in:punch_request,punch_response,punch_ack',
            'payload' => 'nullable|array|max:20',
            'payload.*' => 'nullable|string|max:1000',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->active()->first();
        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $sourceMember = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $sourceMember) {
            return response()->json(['success' => false, 'error' => 'Not a member.'], 403);
        }

        $targetMember = VpnNetworkMember::where('network_id', $network->id)
            ->where('virtual_ip', $request->input('target_virtual_ip'))
            ->where('is_online', true)
            ->first();

        if (! $targetMember) {
            return response()->json(['success' => false, 'error' => 'Target not found or offline.'], 404);
        }

        // Store signal in cache (TTL 30 seconds)
        $signalKey = "vpn_signal:{$targetMember->id}";
        $signals = cache()->get($signalKey, []);
        $signals[] = [
            'from_virtual_ip' => $sourceMember->virtual_ip,
            'from_public_ip' => $sourceMember->public_ip,
            'from_public_port' => $sourceMember->public_port,
            'from_display_name' => $sourceMember->display_name,
            'type' => $request->input('type'),
            'payload' => $request->input('payload', []),
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 50 signals, TTL 30s
        $signals = array_slice($signals, -50);
        cache()->put($signalKey, $signals, 30);

        return response()->json(['success' => true, 'message' => 'Signal sent.']);
    }

    /**
     * Poll for pending signaling messages.
     * POST /api/v1/localvpn/signal/poll
     */
    public function pollSignals(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->active()->first();
        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $member = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'error' => 'Not a member.'], 404);
        }

        // Fetch and clear signals
        $signalKey = "vpn_signal:{$member->id}";
        $signals = cache()->get($signalKey, []);
        cache()->forget($signalKey);

        return response()->json([
            'success' => true,
            'signals' => $signals,
        ]);
    }

    // ==================== Private Helpers ====================

    private function validateDeviceAuth(Request $request): ?LicenseKey
    {
        $licenseKey = $request->input('license_key');
        $machineId = $request->input('machine_id');

        if (! $licenseKey || ! $machineId) {
            return null;
        }

        $product = Product::where('slug', 'localvpn')->where('requires_license', true)->first();
        if (! $product) {
            return null;
        }

        $license = LicenseKey::where('license_key', $licenseKey)
            ->where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->where('status', 'active')
            ->first();

        if (! $license || $license->isExpired()) {
            return null;
        }

        return $license;
    }

    private function formatMember(VpnNetworkMember $member): array
    {
        return [
            'id' => $member->id,
            'machine_id' => $member->machine_id,
            'display_name' => $member->display_name,
            'virtual_ip' => $member->virtual_ip,
            'public_ip' => $member->public_ip,
            'public_port' => $member->public_port,
            'is_online' => $member->is_online,
            'last_heartbeat_at' => $member->last_heartbeat_at?->toISOString(),
            'joined_at' => $member->joined_at?->toISOString(),
        ];
    }

    private function formatNetworkInfo(VpnNetwork $network): array
    {
        return [
            'id' => $network->id,
            'name' => $network->name,
            'slug' => $network->slug,
            'description' => $network->description,
            'virtual_subnet' => $network->virtual_subnet,
            'max_members' => $network->max_members,
            'is_public' => $network->is_public,
            'is_active' => $network->is_active,
            'member_count' => $network->members()->count(),
            'online_count' => $network->members()->where('is_online', true)->count(),
        ];
    }
}
