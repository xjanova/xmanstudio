<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GlobalTorrentController extends Controller
{
    /**
     * Get all active categories with file counts.
     * GET /api/v1/localvpn/torrent/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $machineId = $request->query('machine_id');
        $isKycApproved = $this->isKycApproved($machineId);

        $query = DB::table('bt_categories')
            ->select('bt_categories.*')
            ->selectRaw('(SELECT COUNT(*) FROM bt_files WHERE bt_files.category_id = bt_categories.id AND bt_files.is_active = 1) as file_count')
            ->where('is_active', true)
            ->orderBy('sort_order');

        if (! $isKycApproved) {
            $query->where('is_adult', false);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * List files in a category, paginated.
     * GET /api/v1/localvpn/torrent/files/{categorySlug}
     */
    public function listFiles(Request $request, string $categorySlug): JsonResponse
    {
        $category = DB::table('bt_categories')
            ->where('slug', $categorySlug)
            ->where('is_active', true)
            ->first();

        if (! $category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        // Adult category requires KYC
        if ($category->is_adult) {
            $machineId = $request->query('machine_id');
            if (! $this->isKycApproved($machineId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'KYC verification required for adult content.',
                ], 403);
            }
        }

        $sort = $request->query('sort', 'newest');
        $search = $request->query('search');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $query = DB::table('bt_files')
            ->select('bt_files.*')
            ->selectRaw('(SELECT COUNT(*) FROM bt_file_seeders WHERE bt_file_seeders.bt_file_id = bt_files.id AND bt_file_seeders.is_online = 1) as online_seeders_count')
            ->where('category_id', $category->id)
            ->where('is_active', true);

        if ($search) {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('file_name', 'like', "%{$escapedSearch}%")
                    ->orWhere('description', 'like', "%{$escapedSearch}%");
            });
        }

        switch ($sort) {
            case 'popular':
                $query->orderByDesc('download_count');
                break;
            case 'size':
                $query->orderByDesc('file_size');
                break;
            case 'newest':
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $total = $query->count();
        $files = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $filesFormatted = $files->map(function ($file) use ($category) {
            return [
                'id' => $file->id,
                'file_hash' => $file->file_hash,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'description' => $file->description,
                'thumbnail_url' => $file->thumbnail_url,
                'category' => [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'icon' => $category->icon,
                ],
                'uploader_display_name' => $file->uploader_display_name,
                'download_count' => $file->download_count,
                'online_seeders_count' => $file->online_seeders_count,
                'created_at' => $file->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'files' => $filesFormatted,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Full file details + seeder list + category info.
     * GET /api/v1/localvpn/torrent/file/{fileId}
     */
    public function fileDetail(int $fileId): JsonResponse
    {
        $file = DB::table('bt_files')
            ->where('id', $fileId)
            ->where('is_active', true)
            ->first();

        if (! $file) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        $category = DB::table('bt_categories')->where('id', $file->category_id)->first();

        // Check if file is in adult category
        if ($category && $category->is_adult) {
            $machineId = request()->query('machine_id');
            if (! $this->isKycApproved($machineId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'KYC verification required for adult content.',
                ], 403);
            }
        }

        $seeders = DB::table('bt_file_seeders')
            ->where('bt_file_id', $file->id)
            ->orderByDesc('is_online')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function ($s) {
                return [
                    'machine_id' => $s->machine_id,
                    'display_name' => $s->display_name,
                    'is_online' => (bool) $s->is_online,
                    'public_ip' => $s->public_ip,
                    'public_port' => $s->public_port,
                    'last_seen_at' => $s->last_seen_at,
                    'chunks_bitmap' => $s->chunks_bitmap,
                ];
            });

        $onlineSeeders = $seeders->where('is_online', true)->count();

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'file_hash' => $file->file_hash,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'description' => $file->description,
                'thumbnail_url' => $file->thumbnail_url,
                'chunk_size' => $file->chunk_size,
                'total_chunks' => $file->total_chunks,
                'download_count' => $file->download_count,
                'uploader_machine_id' => substr($file->uploader_machine_id, 0, 8) . '...',
                'uploader_display_name' => $file->uploader_display_name,
                'online_seeders_count' => $onlineSeeders,
                'created_at' => $file->created_at,
                'category' => $category ? [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'is_adult' => (bool) $category->is_adult,
                ] : null,
            ],
            'seeders' => $seeders,
        ]);
    }

    /**
     * Register a file in the global torrent system.
     * POST /api/v1/localvpn/torrent/upload
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'category_slug' => 'required|string|max:100',
            'file_hash' => 'required|string|size:64',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',
            'thumbnail_data' => 'nullable|string',
            'display_name' => 'nullable|string|max:100',
            'chunk_size' => 'nullable|integer|min:1024|max:65536',
            'public_ip' => 'nullable|string|max:45',
            'public_port' => 'nullable|integer',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $category = DB::table('bt_categories')
            ->where('slug', $request->input('category_slug'))
            ->where('is_active', true)
            ->first();

        if (! $category) {
            return response()->json(['success' => false, 'error' => 'Category not found.'], 404);
        }

        // Adult category requires KYC
        if ($category->is_adult && ! $this->isKycApproved($request->input('machine_id'))) {
            return response()->json([
                'success' => false,
                'error' => 'KYC verification required for adult content.',
            ], 403);
        }

        // Check duplicate
        $existing = DB::table('bt_files')->where('file_hash', $request->input('file_hash'))->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'File with this hash already exists.',
                'file_id' => $existing->id,
            ], 409);
        }

        $machineId = $request->input('machine_id');
        $displayName = $request->input('display_name', substr($machineId, 0, 8));
        $chunkSize = $request->input('chunk_size', 32768);
        $fileSize = $request->input('file_size');
        $totalChunks = (int) ceil($fileSize / $chunkSize);

        // Handle thumbnail
        $thumbnailUrl = null;
        if ($request->input('thumbnail_data')) {
            $thumbnailUrl = 'data:image/jpeg;base64,' . $request->input('thumbnail_data');
        }

        $now = now();

        $publicIp = $request->input('public_ip');
        $publicPort = $request->input('public_port');

        $fileId = DB::transaction(function () use ($request, $category, $machineId, $displayName, $fileSize, $chunkSize, $totalChunks, $thumbnailUrl, $publicIp, $publicPort, $now) {
            // Create file record
            $fileId = DB::table('bt_files')->insertGetId([
                'category_id' => $category->id,
                'uploader_machine_id' => $machineId,
                'uploader_display_name' => $displayName,
                'file_hash' => $request->input('file_hash'),
                'file_name' => $request->input('file_name'),
                'file_size' => $fileSize,
                'description' => $request->input('description'),
                'thumbnail_url' => $thumbnailUrl,
                'chunk_size' => $chunkSize,
                'total_chunks' => $totalChunks,
                'download_count' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Register uploader as first seeder
            DB::table('bt_file_seeders')->insert([
                'bt_file_id' => $fileId,
                'machine_id' => $machineId,
                'display_name' => $displayName,
                'is_online' => true,
                'last_seen_at' => $now,
                'public_ip' => $publicIp,
                'public_port' => $publicPort,
                'chunks_bitmap' => 'all',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Update user stats (don't set updated_at — let heartbeat own it for seed time tracking)
            DB::table('bt_user_stats')->updateOrInsert(
                ['machine_id' => $machineId],
                [
                    'display_name' => $displayName,
                    'created_at' => $now,
                ]
            );
            DB::table('bt_user_stats')
                ->where('machine_id', $machineId)
                ->increment('total_files_shared');
            DB::table('bt_user_stats')
                ->where('machine_id', $machineId)
                ->increment('total_uploaded_bytes', $fileSize);

            return $fileId;
        });

        // Recalculate score
        $this->recalculateUserScore($machineId);

        // Check trophies
        $this->checkAndAwardTrophies($machineId);

        $file = DB::table('bt_files')->where('id', $fileId)->first();

        return response()->json([
            'success' => true,
            'file' => $this->formatFileForApi($file, $category),
        ]);
    }

    /**
     * Register as seeder after downloading a file.
     * POST /api/v1/localvpn/torrent/seed
     */
    public function registerSeeder(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'chunks_bitmap' => 'nullable|string',
            'display_name' => 'nullable|string|max:100',
            'public_ip' => 'nullable|string|max:45',
            'public_port' => 'nullable|integer',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $file = DB::table('bt_files')
            ->where('file_hash', $request->input('file_hash'))
            ->where('is_active', true)
            ->first();

        if (! $file) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        $machineId = $request->input('machine_id');
        $displayName = $request->input('display_name', substr($machineId, 0, 8));
        $chunksBitmap = $request->input('chunks_bitmap', 'all');
        $publicIp = $request->input('public_ip');
        $publicPort = $request->input('public_port');
        $now = now();

        $isNewSeeder = DB::transaction(function () use ($file, $machineId, $displayName, $chunksBitmap, $publicIp, $publicPort, $now) {
            // Check inside transaction to prevent race condition
            $existing = DB::table('bt_file_seeders')
                ->where('bt_file_id', $file->id)
                ->where('machine_id', $machineId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Update existing seeder
                DB::table('bt_file_seeders')
                    ->where('bt_file_id', $file->id)
                    ->where('machine_id', $machineId)
                    ->update([
                        'display_name' => $displayName,
                        'is_online' => true,
                        'last_seen_at' => $now,
                        'chunks_bitmap' => $chunksBitmap,
                        'public_ip' => $publicIp,
                        'public_port' => $publicPort,
                        'updated_at' => $now,
                    ]);

                return false;
            }

            // Insert new seeder
            DB::table('bt_file_seeders')->insert([
                'bt_file_id' => $file->id,
                'machine_id' => $machineId,
                'display_name' => $displayName,
                'is_online' => true,
                'last_seen_at' => $now,
                'chunks_bitmap' => $chunksBitmap,
                'public_ip' => $publicIp,
                'public_port' => $publicPort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Increment download count
            DB::table('bt_files')->where('id', $file->id)->increment('download_count');

            // Update user stats
            DB::table('bt_user_stats')->updateOrInsert(
                ['machine_id' => $machineId],
                [
                    'display_name' => $displayName,
                    'created_at' => $now,
                ]
            );
            DB::table('bt_user_stats')
                ->where('machine_id', $machineId)
                ->increment('total_files_downloaded');
            DB::table('bt_user_stats')
                ->where('machine_id', $machineId)
                ->increment('total_downloaded_bytes', $file->file_size);

            return true;
        });

        // Recalculate score & trophies outside transaction (non-critical)
        if ($isNewSeeder) {
            $this->recalculateUserScore($machineId);
            $this->checkAndAwardTrophies($machineId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registered as seeder.',
        ]);
    }

    /**
     * Update seeder status (heartbeat).
     * POST /api/v1/localvpn/torrent/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hashes' => 'required|array',
            'file_hashes.*' => 'string|size:64',
            'public_ip' => 'nullable|string|max:45',
            'public_port' => 'nullable|integer',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $machineId = $request->input('machine_id');
        $fileHashes = $request->input('file_hashes');
        $publicIp = $request->input('public_ip');
        $publicPort = $request->input('public_port');
        $now = now();

        // Get file IDs for the provided hashes
        $fileIds = DB::table('bt_files')
            ->whereIn('file_hash', $fileHashes)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Update seeders for listed files as online
        if (! empty($fileIds)) {
            DB::table('bt_file_seeders')
                ->where('machine_id', $machineId)
                ->whereIn('bt_file_id', $fileIds)
                ->update([
                    'is_online' => true,
                    'last_seen_at' => $now,
                    'public_ip' => $publicIp,
                    'public_port' => $publicPort,
                    'updated_at' => $now,
                ]);
        }

        // Mark seeders NOT in heartbeat as offline
        DB::table('bt_file_seeders')
            ->where('machine_id', $machineId)
            ->where('is_online', true)
            ->when(! empty($fileIds), function ($q) use ($fileIds) {
                $q->whereNotIn('bt_file_id', $fileIds);
            })
            ->update([
                'is_online' => false,
                'updated_at' => $now,
            ]);

        // Update seed time: calculate seconds since last heartbeat
        // Use max last_seen_at from this user's seeders (set BEFORE this heartbeat updated them)
        // to avoid interference from upload/registerSeeder modifying updated_at on user_stats.
        if (! empty($fileIds)) {
            $stats = DB::table('bt_user_stats')->where('machine_id', $machineId)->first();
            if ($stats && $stats->updated_at) {
                $lastHeartbeat = Carbon::parse($stats->updated_at);
                $secondsSinceLast = min($now->diffInSeconds($lastHeartbeat), 300); // Cap at 5 minutes
                if ($secondsSinceLast > 0) {
                    DB::table('bt_user_stats')
                        ->where('machine_id', $machineId)
                        ->increment('seed_time_seconds', $secondsSinceLast);
                }
            }
        }

        // Update updated_at AFTER seed time calculation so next heartbeat can diff against it
        DB::table('bt_user_stats')->updateOrInsert(
            ['machine_id' => $machineId],
            [
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        return response()->json([
            'success' => true,
            'online_files' => count($fileIds),
        ]);
    }

    /**
     * Get online seeders for a file (for P2P download).
     * GET /api/v1/localvpn/torrent/file/{fileId}/seeders
     */
    public function getSeeders(int $fileId): JsonResponse
    {
        $file = DB::table('bt_files')
            ->where('id', $fileId)
            ->where('is_active', true)
            ->first();

        if (! $file) {
            return response()->json(['success' => false, 'error' => 'File not found.'], 404);
        }

        $category = DB::table('bt_categories')->where('id', $file->category_id)->first();
        if ($category && $category->is_adult) {
            $machineId = request()->query('machine_id');
            if (! $this->isKycApproved($machineId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'KYC verification required.',
                ], 403);
            }
        }

        $seeders = DB::table('bt_file_seeders')
            ->where('bt_file_id', $fileId)
            ->where('is_online', true)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function ($s) {
                return [
                    'machine_id' => $s->machine_id,
                    'display_name' => $s->display_name,
                    'public_ip' => $s->public_ip,
                    'public_port' => $s->public_port,
                    'last_seen_at' => $s->last_seen_at,
                    'chunks_bitmap' => $s->chunks_bitmap,
                ];
            });

        return response()->json([
            'success' => true,
            'file_hash' => $file->file_hash,
            'seeders' => $seeders,
        ]);
    }

    /**
     * Top 50 users by score.
     * GET /api/v1/localvpn/torrent/leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $users = DB::table('bt_user_stats')
            ->select('bt_user_stats.*')
            ->selectRaw("(SELECT GROUP_CONCAT(bt_trophies.badge_text SEPARATOR '||') FROM bt_user_trophies JOIN bt_trophies ON bt_user_trophies.trophy_id = bt_trophies.id WHERE bt_user_trophies.machine_id = bt_user_stats.machine_id) as trophy_badges")
            ->orderByDesc('score')
            ->limit(50)
            ->get();

        $leaderboard = $users->map(function ($user, $index) {
            $trophies = $user->trophy_badges
                ? explode('||', $user->trophy_badges)
                : [];

            return [
                'rank' => $user->rank_position ?: ($index + 1),
                'display_name' => $user->display_name,
                'machine_id' => substr($user->machine_id, 0, 8) . '...',
                'score' => $user->score,
                'total_uploaded_bytes' => $user->total_uploaded_bytes,
                'total_files_shared' => $user->total_files_shared,
                'seed_time_seconds' => $user->seed_time_seconds,
                'trophies' => $trophies,
            ];
        });

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Get stats for a specific machine_id.
     * GET /api/v1/localvpn/torrent/profile
     */
    public function userProfile(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $machineId = $request->input('machine_id');
        $stats = DB::table('bt_user_stats')->where('machine_id', $machineId)->first();

        if (! $stats) {
            return response()->json([
                'success' => true,
                'stats' => null,
                'trophies' => [],
                'rank' => 0,
            ]);
        }

        $trophies = DB::table('bt_user_trophies')
            ->join('bt_trophies', 'bt_user_trophies.trophy_id', '=', 'bt_trophies.id')
            ->where('bt_user_trophies.machine_id', $machineId)
            ->select('bt_trophies.*', 'bt_user_trophies.awarded_at')
            ->orderBy('bt_trophies.sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'display_name' => $stats->display_name,
                'total_uploaded_bytes' => $stats->total_uploaded_bytes,
                'total_downloaded_bytes' => $stats->total_downloaded_bytes,
                'total_files_shared' => $stats->total_files_shared,
                'total_files_downloaded' => $stats->total_files_downloaded,
                'seed_time_seconds' => $stats->seed_time_seconds,
                'score' => $stats->score,
                'rank_position' => $stats->rank_position,
            ],
            'trophies' => $trophies,
            'rank' => $stats->rank_position,
        ]);
    }

    /**
     * List all trophies grouped by difficulty.
     * GET /api/v1/localvpn/torrent/trophies
     */
    public function trophies(): JsonResponse
    {
        $trophies = DB::table('bt_trophies')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('difficulty');

        return response()->json([
            'success' => true,
            'trophies' => $trophies,
        ]);
    }

    /**
     * Get trophies for specific machine_id.
     * GET /api/v1/localvpn/torrent/user-trophies
     */
    public function userTrophies(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
        ]);

        $machineId = $request->query('machine_id');

        $trophies = DB::table('bt_user_trophies')
            ->join('bt_trophies', 'bt_user_trophies.trophy_id', '=', 'bt_trophies.id')
            ->where('bt_user_trophies.machine_id', $machineId)
            ->select('bt_trophies.*', 'bt_user_trophies.awarded_at')
            ->orderBy('bt_trophies.sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'trophies' => $trophies,
        ]);
    }

    /**
     * Submit KYC verification.
     * POST /api/v1/localvpn/torrent/kyc/submit
     */
    public function submitKyc(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'display_name' => 'required|string|max:100',
            'id_card_front' => 'required|string',
            'birth_date' => 'required|date',
            'id_card_back' => 'nullable|string',
            'selfie' => 'nullable|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        // Validate base64 size (max 2MB per image after encoding)
        $maxBase64Size = 2 * 1024 * 1024 * 1.34; // ~2.68MB base64 for 2MB binary
        foreach (['id_card_front', 'id_card_back', 'selfie'] as $field) {
            if ($request->input($field) && strlen($request->input($field)) > $maxBase64Size) {
                return response()->json([
                    'success' => false,
                    'error' => "Image $field exceeds 2MB limit.",
                ], 413);
            }
        }

        // Validate age >= 18
        $birthDate = Carbon::parse($request->input('birth_date'));
        if ($birthDate->age < 18) {
            return response()->json([
                'success' => false,
                'error' => 'You must be at least 18 years old.',
            ], 403);
        }

        $machineId = $request->input('machine_id');

        // Check if already has pending/approved request
        $existing = DB::table('bt_kyc_requests')
            ->where('machine_id', $machineId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'KYC request already ' . $existing->status . '.',
                'status' => $existing->status,
            ], 409);
        }

        // Save images to storage
        $storagePath = 'kyc/' . $machineId;

        // Validate and save images with MIME type checking
        $validMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        $frontData = base64_decode($request->input('id_card_front'), true);
        if ($frontData === false) {
            return response()->json(['success' => false, 'error' => 'Invalid base64 data for id_card_front.'], 422);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $frontMime = $finfo->buffer($frontData);
        if (! in_array($frontMime, $validMimeTypes)) {
            return response()->json(['success' => false, 'error' => 'id_card_front must be a JPEG, PNG, or WebP image.'], 422);
        }
        $frontExt = $mimeToExt[$frontMime] ?? 'jpg';
        $frontPath = $storagePath . '/id_front_' . time() . '.' . $frontExt;
        Storage::disk('local')->put($frontPath, $frontData);

        $backPath = null;
        if ($request->input('id_card_back')) {
            $backData = base64_decode($request->input('id_card_back'), true);
            if ($backData === false) {
                return response()->json(['success' => false, 'error' => 'Invalid base64 data for id_card_back.'], 422);
            }
            $backMime = $finfo->buffer($backData);
            if (! in_array($backMime, $validMimeTypes)) {
                return response()->json(['success' => false, 'error' => 'id_card_back must be a JPEG, PNG, or WebP image.'], 422);
            }
            $backExt = $mimeToExt[$backMime] ?? 'jpg';
            $backPath = $storagePath . '/id_back_' . time() . '.' . $backExt;
            Storage::disk('local')->put($backPath, $backData);
        }

        $selfiePath = null;
        if ($request->input('selfie')) {
            $selfieData = base64_decode($request->input('selfie'), true);
            if ($selfieData === false) {
                return response()->json(['success' => false, 'error' => 'Invalid base64 data for selfie.'], 422);
            }
            $selfieMime = $finfo->buffer($selfieData);
            if (! in_array($selfieMime, $validMimeTypes)) {
                return response()->json(['success' => false, 'error' => 'selfie must be a JPEG, PNG, or WebP image.'], 422);
            }
            $selfieExt = $mimeToExt[$selfieMime] ?? 'jpg';
            $selfiePath = $storagePath . '/selfie_' . time() . '.' . $selfieExt;
            Storage::disk('local')->put($selfiePath, $selfieData);
        }

        $now = now();
        DB::table('bt_kyc_requests')->insert([
            'machine_id' => $machineId,
            'display_name' => $request->input('display_name'),
            'id_card_front_path' => $frontPath,
            'id_card_back_path' => $backPath,
            'selfie_path' => $selfiePath,
            'birth_date' => $birthDate->toDateString(),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'status' => 'pending',
            'message' => 'KYC request submitted. Please wait for review.',
        ]);
    }

    /**
     * Check KYC status for a machine_id.
     * GET /api/v1/localvpn/torrent/kyc/status
     */
    public function kycStatus(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
        ]);

        $machineId = $request->query('machine_id');

        $kycRequest = DB::table('bt_kyc_requests')
            ->where('machine_id', $machineId)
            ->orderByDesc('created_at')
            ->first();

        if (! $kycRequest) {
            return response()->json([
                'success' => true,
                'status' => 'none',
                'message' => 'No KYC request found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $kycRequest->status,
            'display_name' => $kycRequest->display_name,
            'submitted_at' => $kycRequest->created_at,
            'reviewed_at' => $kycRequest->reviewed_at,
            'admin_note' => $kycRequest->status === 'rejected' ? $kycRequest->admin_note : null,
        ]);
    }

    // ==================== Torrent Relay (Server-mediated download) ====================

    /**
     * Downloader requests chunks from a seeder via server relay.
     * POST /api/v1/localvpn/torrent/relay/request
     */
    public function relayRequest(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'chunk_indices' => 'required|array|max:10',
            'chunk_indices.*' => 'integer|min:0',
            'target_machine_id' => 'required|string|max:255',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $cacheKey = 'torrent_relay:' . $request->input('target_machine_id');
        $pending = cache()->get($cacheKey, []);

        foreach ($request->input('chunk_indices') as $idx) {
            $pending[] = [
                'file_hash' => $request->input('file_hash'),
                'chunk_index' => $idx,
                'requester' => $request->input('machine_id'),
                'requested_at' => now()->toISOString(),
            ];
        }

        cache()->put($cacheKey, $pending, 300); // 5 min TTL

        return response()->json(['success' => true, 'queued' => count($request->input('chunk_indices'))]);
    }

    /**
     * Seeder polls for pending chunk requests.
     * POST /api/v1/localvpn/torrent/relay/poll
     */
    public function relayPoll(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $cacheKey = 'torrent_relay:' . $request->input('machine_id');
        $pending = cache()->get($cacheKey, []);

        // Take first 5 requests and leave rest
        $batch = array_splice($pending, 0, 5);
        cache()->put($cacheKey, $pending, 300);

        return response()->json(['success' => true, 'requests' => $batch]);
    }

    /**
     * Seeder uploads a chunk to relay to the requester.
     * POST /api/v1/localvpn/torrent/relay/chunk
     */
    public function relayChunk(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'chunk_index' => 'required|integer|min:0',
            'data' => 'required|string', // base64 chunk data
            'target_machine_id' => 'required|string|max:255',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        // Store chunk in cache for requester to fetch
        $chunkKey = sprintf(
            'torrent_chunk:%s:%s:%d',
            $request->input('target_machine_id'),
            $request->input('file_hash'),
            $request->input('chunk_index')
        );

        cache()->put($chunkKey, $request->input('data'), 120); // 2 min TTL

        return response()->json(['success' => true]);
    }

    /**
     * Downloader fetches relayed chunks.
     * POST /api/v1/localvpn/torrent/relay/fetch
     */
    public function relayFetch(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'license_key' => 'required|string',
            'file_hash' => 'required|string|size:64',
            'chunk_indices' => 'required|array|max:10',
            'chunk_indices.*' => 'integer|min:0',
        ]);

        $license = $this->validateDeviceAuth($request);
        if (! $license) {
            return response()->json(['success' => false, 'error' => 'Invalid license or device.'], 403);
        }

        $chunks = [];
        foreach ($request->input('chunk_indices') as $idx) {
            $chunkKey = sprintf(
                'torrent_chunk:%s:%s:%d',
                $request->input('machine_id'),
                $request->input('file_hash'),
                $idx
            );

            $data = cache()->pull($chunkKey); // get + delete
            if ($data) {
                $chunks[] = ['chunk_index' => $idx, 'data' => $data];
            }
        }

        return response()->json(['success' => true, 'chunks' => $chunks]);
    }

    // ==================== Private Helpers ====================

    /**
     * Validate device authentication via license key + machine_id.
     */
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

    /**
     * Check if a machine_id has KYC approved status.
     */
    private function isKycApproved(?string $machineId): bool
    {
        if (! $machineId) {
            return false;
        }

        return DB::table('bt_kyc_requests')
            ->where('machine_id', $machineId)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Check and award trophies for a user.
     */
    private function checkAndAwardTrophies(string $machineId): void
    {
        $stats = DB::table('bt_user_stats')->where('machine_id', $machineId)->first();
        if (! $stats) {
            return;
        }

        // Get all active trophies not yet awarded to this user
        $awardedTrophyIds = DB::table('bt_user_trophies')
            ->where('machine_id', $machineId)
            ->pluck('trophy_id')
            ->toArray();

        $availableTrophies = DB::table('bt_trophies')
            ->where('is_active', true)
            ->when(! empty($awardedTrophyIds), function ($q) use ($awardedTrophyIds) {
                $q->whereNotIn('id', $awardedTrophyIds);
            })
            ->get();

        $now = now();

        foreach ($availableTrophies as $trophy) {
            $met = false;

            switch ($trophy->requirement_type) {
                case 'files_shared':
                    $met = $stats->total_files_shared >= $trophy->requirement_value;
                    break;

                case 'files_downloaded':
                    $met = $stats->total_files_downloaded >= $trophy->requirement_value;
                    break;

                case 'uploaded_gb':
                    $met = ($stats->total_uploaded_bytes / (1024 * 1024 * 1024)) >= $trophy->requirement_value;
                    break;

                case 'seed_hours':
                    $met = ($stats->seed_time_seconds / 3600) >= $trophy->requirement_value;
                    break;

                case 'total_downloads_received':
                    $totalDownloads = DB::table('bt_files')
                        ->where('uploader_machine_id', $machineId)
                        ->sum('download_count');
                    $met = $totalDownloads >= $trophy->requirement_value;
                    break;

                case 'rank_position':
                    $met = $stats->rank_position > 0 && $stats->rank_position <= $trophy->requirement_value;
                    break;

                case 'account_created':
                    $met = true;
                    break;

                case 'seed_count':
                    $seedCount = DB::table('bt_file_seeders')
                        ->where('machine_id', $machineId)
                        ->count();
                    $met = $seedCount >= $trophy->requirement_value;
                    break;

                case 'categories_shared':
                    $catCount = DB::table('bt_files')
                        ->where('uploader_machine_id', $machineId)
                        ->distinct()
                        ->count('category_id');
                    $met = $catCount >= $trophy->requirement_value;
                    break;

                case 'categories_downloaded':
                    // Count distinct categories from files the user is seeding (but didn't upload)
                    $catDownloaded = DB::table('bt_file_seeders')
                        ->join('bt_files', 'bt_file_seeders.bt_file_id', '=', 'bt_files.id')
                        ->where('bt_file_seeders.machine_id', $machineId)
                        ->where('bt_files.uploader_machine_id', '!=', $machineId)
                        ->distinct()
                        ->count('bt_files.category_id');
                    $met = $catDownloaded >= $trophy->requirement_value;
                    break;

                case 'networks_created':
                    $license = LicenseKey::where('machine_id', $machineId)
                        ->where('status', 'active')
                        ->first();
                    if ($license) {
                        $netCount = DB::table('vpn_networks')
                            ->where('owner_user_id', $license->user_id)
                            ->count();
                        $met = $netCount >= $trophy->requirement_value;
                    }
                    break;

                case 'networks_joined':
                    $memberCount = DB::table('vpn_network_members')
                        ->where('machine_id', $machineId)
                        ->count();
                    $met = $memberCount >= $trophy->requirement_value;
                    break;

                case 'unique_peers_seeded':
                    // Estimate from total download_count of user's files
                    $peerEstimate = DB::table('bt_files')
                        ->where('uploader_machine_id', $machineId)
                        ->sum('download_count');
                    $met = $peerEstimate >= $trophy->requirement_value;
                    break;

                case 'seed_ratio_days':
                case 'days_at_rank_1':
                    // Skip for now (complex tracking needed)
                    $met = false;
                    break;
            }

            if ($met) {
                DB::table('bt_user_trophies')->insertOrIgnore([
                    'machine_id' => $machineId,
                    'trophy_id' => $trophy->id,
                    'awarded_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Recalculate score for a single user.
     * Score formula: uploaded_gb * 100 + files_shared * 10 + seed_hours * 5 + downloads_received * 2
     */
    private function recalculateUserScore(string $machineId): void
    {
        $stats = DB::table('bt_user_stats')->where('machine_id', $machineId)->first();
        if (! $stats) {
            return;
        }

        $uploadedGb = $stats->total_uploaded_bytes / (1024 * 1024 * 1024);
        $seedHours = $stats->seed_time_seconds / 3600;
        $downloadsReceived = DB::table('bt_files')
            ->where('uploader_machine_id', $machineId)
            ->sum('download_count');

        $score = (int) (
            ($uploadedGb * 100) +
            ($stats->total_files_shared * 10) +
            ($seedHours * 5) +
            ($downloadsReceived * 2)
        );

        DB::table('bt_user_stats')
            ->where('machine_id', $machineId)
            ->update(['score' => $score]);

        // Recalculate ranks
        $this->recalculateRanks();
    }

    /**
     * Update rank_position for all users based on score descending.
     */
    private function recalculateRanks(): void
    {
        DB::statement('
            UPDATE bt_user_stats AS s
            JOIN (
                SELECT machine_id, ROW_NUMBER() OVER (ORDER BY score DESC) AS new_rank
                FROM bt_user_stats
            ) AS r ON s.machine_id = r.machine_id
            SET s.rank_position = r.new_rank
        ');
    }

    /**
     * Format a file record for API response.
     */
    private function formatFileForApi(object $file, ?object $category = null): array
    {
        if (! $category) {
            $category = DB::table('bt_categories')->where('id', $file->category_id)->first();
        }

        // Use precomputed count if available (from subquery), otherwise query
        $onlineSeeders = property_exists($file, 'online_seeders_count')
            ? $file->online_seeders_count
            : DB::table('bt_file_seeders')
                ->where('bt_file_id', $file->id)
                ->where('is_online', true)
                ->count();

        return [
            'id' => $file->id,
            'file_hash' => $file->file_hash,
            'file_name' => $file->file_name,
            'file_size' => $file->file_size,
            'description' => $file->description,
            'thumbnail_url' => $file->thumbnail_url,
            'chunk_size' => $file->chunk_size,
            'total_chunks' => $file->total_chunks,
            'download_count' => $file->download_count,
            'uploader_display_name' => $file->uploader_display_name,
            'online_seeders_count' => $onlineSeeders,
            'created_at' => $file->created_at,
            'category' => $category ? [
                'slug' => $category->slug,
                'name' => $category->name,
                'icon' => $category->icon,
            ] : null,
        ];
    }
}
