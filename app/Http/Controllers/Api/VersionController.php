<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    /**
     * Get the latest version for a product
     * Public endpoint - apps can check for updates without authentication
     */
    public function latest(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        $version = $product->latestVersion();

        if (! $version) {
            return response()->json([
                'success' => false,
                'error' => 'No version available',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'version' => [
                'version' => $version->version,
                'filename' => $version->download_filename,
                'file_size' => $version->file_size,
                'file_size_formatted' => $version->file_size_formatted,
                'changelog' => $version->changelog,
                'released_at' => $version->synced_at?->toIso8601String(),
            ],
            'download_url' => route('download.product', [
                'slug' => $product->slug,
                'version' => $version->version,
            ]),
        ]);
    }

    /**
     * Get all versions for a product
     */
    public function all(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        $versions = $product->versions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'versions' => $versions->map(function ($v) use ($product) {
                return [
                    'version' => $v->version,
                    'filename' => $v->download_filename,
                    'file_size' => $v->file_size,
                    'file_size_formatted' => $v->file_size_formatted,
                    'changelog' => $v->changelog,
                    'is_active' => $v->is_active,
                    'released_at' => $v->synced_at?->toIso8601String(),
                    'download_url' => route('download.product', [
                        'slug' => $product->slug,
                        'version' => $v->version,
                    ]),
                ];
            }),
        ]);
    }

    /**
     * Check version and return if update is available
     * Apps should call this with their current version
     */
    public function check(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'current_version' => 'required|string',
            'license_key' => 'nullable|string',
        ]);

        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        $latestVersion = $product->latestVersion();

        if (! $latestVersion) {
            return response()->json([
                'success' => false,
                'error' => 'No version available',
            ], 404);
        }

        $currentVersion = $request->input('current_version');
        $hasUpdate = version_compare($currentVersion, $latestVersion->version, '<');

        // Check license if provided
        $licenseValid = false;
        $licenseStatus = null;

        if ($request->filled('license_key')) {
            $license = LicenseKey::where('license_key', $request->input('license_key'))
                ->where('product_id', $product->id)
                ->first();

            if ($license) {
                $licenseValid = $license->status === 'active' &&
                    (! $license->expires_at || $license->expires_at->isFuture());
                $licenseStatus = $license->status;
            }
        }

        return response()->json([
            'success' => true,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion->version,
            'has_update' => $hasUpdate,
            'update' => $hasUpdate ? [
                'version' => $latestVersion->version,
                'filename' => $latestVersion->download_filename,
                'file_size' => $latestVersion->file_size,
                'file_size_formatted' => $latestVersion->file_size_formatted,
                'changelog' => $latestVersion->changelog,
                'released_at' => $latestVersion->synced_at?->toIso8601String(),
                'download_url' => route('download.product', [
                    'slug' => $product->slug,
                    'version' => $latestVersion->version,
                ]),
            ] : null,
            'license' => [
                'valid' => $licenseValid,
                'status' => $licenseStatus,
                'can_download' => $licenseValid,
            ],
        ]);
    }

    /**
     * Validate a license key
     */
    public function validateLicense(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'product_slug' => 'nullable|string',
            'machine_id' => 'nullable|string',
        ]);

        $query = LicenseKey::where('license_key', $request->input('license_key'));

        if ($request->filled('product_slug')) {
            $product = Product::where('slug', $request->input('product_slug'))->first();
            if ($product) {
                $query->where('product_id', $product->id);
            }
        }

        $license = $query->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'error' => 'License key not found',
            ], 404);
        }

        // Check if expired
        $isExpired = $license->expires_at && $license->expires_at->isPast();

        // Check machine binding
        $machineMatch = true;
        if ($request->filled('machine_id') && $license->machine_fingerprint) {
            $machineMatch = $license->machine_fingerprint === $request->input('machine_id');
        }

        // Bind machine if not yet bound
        if ($request->filled('machine_id') && ! $license->machine_fingerprint && $license->status === 'active') {
            $license->update([
                'machine_fingerprint' => $request->input('machine_id'),
                'activated_at' => now(),
            ]);
        }

        $isValid = $license->status === 'active' && ! $isExpired && $machineMatch;

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'license' => [
                'status' => $license->status,
                'type' => $license->license_type,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'is_expired' => $isExpired,
                'machine_bound' => ! empty($license->machine_fingerprint),
                'machine_match' => $machineMatch,
                'activated_at' => $license->activated_at?->toIso8601String(),
            ],
            'product' => [
                'name' => $license->product?->name,
                'slug' => $license->product?->slug,
            ],
        ]);
    }
}
