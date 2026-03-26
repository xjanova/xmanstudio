<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\VpnFileSeeder;
use App\Models\VpnNetwork;
use App\Models\VpnNetworkMember;
use App\Models\VpnSharedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalVpnFileController extends Controller
{
    /**
     * Share a file to the network (register in registry).
     * POST /api/v1/localvpn/files/share
     */
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'chunk_size' => 'nullable|integer|min:1024|max:65536',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['error' => 'Invalid license or device'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->active()->first();
        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $member = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'error' => 'Not a member.'], 403);
        }

        $chunkSize = $request->input('chunk_size', 32768);
        $fileSize = $request->input('file_size');
        $totalChunks = (int) ceil($fileSize / $chunkSize);

        // Create or get shared file
        $sharedFile = VpnSharedFile::firstOrCreate(
            [
                'network_id' => $network->id,
                'file_hash' => $request->input('file_hash'),
            ],
            [
                'owner_member_id' => $member->id,
                'file_name' => $request->input('file_name'),
                'file_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
            ]
        );

        // Register this member as a seeder (has all chunks)
        VpnFileSeeder::updateOrCreate(
            [
                'shared_file_id' => $sharedFile->id,
                'member_id' => $member->id,
            ],
            [
                'chunks_bitmap' => 'all',
            ]
        );

        return response()->json([
            'success' => true,
            'file' => $this->formatFile($sharedFile),
        ], 201);
    }

    /**
     * List all shared files in a network.
     * GET /api/v1/localvpn/files/{slug}
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        $network = VpnNetwork::where('slug', $slug)->active()->first();
        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $files = VpnSharedFile::where('network_id', $network->id)
            ->with(['owner', 'seeders.member'])
            ->withCount([
                'seeders',
                'onlineSeeders as online_seeders_count',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($f) => $this->formatFile($f));

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    /**
     * Remove a shared file (owner only).
     * DELETE /api/v1/localvpn/files/{fileId}
     */
    public function destroy(Request $request, int $fileId): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['error' => 'Invalid license or device'], 403);
        }

        $sharedFile = VpnSharedFile::find($fileId);
        if (! $sharedFile) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        // Verify ownership
        $member = VpnNetworkMember::where('network_id', $sharedFile->network_id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member || $sharedFile->owner_member_id !== $member->id) {
            return response()->json(['success' => false, 'error' => 'Not the file owner.'], 403);
        }

        $sharedFile->delete();

        return response()->json(['success' => true, 'message' => 'File removed.']);
    }

    /**
     * Register as a seeder for a file (after downloading).
     * POST /api/v1/localvpn/files/seed
     */
    public function registerSeeder(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'chunks_bitmap' => 'nullable|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['error' => 'Invalid license or device'], 403);
        }

        $network = VpnNetwork::where('slug', $request->input('slug'))->active()->first();
        if (! $network) {
            return response()->json(['success' => false, 'error' => 'Network not found.'], 404);
        }

        $member = VpnNetworkMember::where('network_id', $network->id)
            ->where('machine_id', $request->input('machine_id'))
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'error' => 'Not a member.'], 403);
        }

        $sharedFile = VpnSharedFile::where('network_id', $network->id)
            ->where('file_hash', $request->input('file_hash'))
            ->first();

        if (! $sharedFile) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        VpnFileSeeder::updateOrCreate(
            [
                'shared_file_id' => $sharedFile->id,
                'member_id' => $member->id,
            ],
            [
                'chunks_bitmap' => $request->input('chunks_bitmap', 'all'),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Registered as seeder.']);
    }

    /**
     * Get seeders for a specific file (who to download from).
     * GET /api/v1/localvpn/files/{fileId}/seeders
     */
    public function seeders(Request $request, int $fileId): JsonResponse
    {
        $sharedFile = VpnSharedFile::find($fileId);
        if (! $sharedFile) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        $seeders = VpnFileSeeder::where('shared_file_id', $fileId)
            ->with('member')
            ->get()
            ->filter(fn ($s) => $s->member && $s->member->is_online)
            ->map(fn ($s) => [
                'virtual_ip' => $s->member->virtual_ip,
                'public_ip' => $s->member->public_ip,
                'public_port' => $s->member->public_port,
                'display_name' => $s->member->display_name,
                'chunks_bitmap' => $s->chunks_bitmap,
                'has_all' => $s->hasAllChunks(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'seeders' => $seeders,
            'total_chunks' => $sharedFile->total_chunks,
            'chunk_size' => $sharedFile->chunk_size,
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

    private function formatFile(VpnSharedFile $file): array
    {
        return [
            'id' => $file->id,
            'file_hash' => $file->file_hash,
            'file_name' => $file->file_name,
            'file_size' => $file->file_size,
            'chunk_size' => $file->chunk_size,
            'total_chunks' => $file->total_chunks,
            'owner_display_name' => $file->owner?->display_name ?? 'Unknown',
            'owner_virtual_ip' => $file->owner?->virtual_ip,
            'seeders_count' => $file->seeders_count ?? $file->seeders()->count(),
            'online_seeders_count' => $file->online_seeders_count ?? 0,
            'created_at' => $file->created_at?->toISOString(),
        ];
    }
}
