<?php

namespace App\Services;

use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubReleaseService
{
    /**
     * Sync the latest release from GitHub for a product
     */
    /** Maximum number of versions to keep per product */
    public const MAX_VERSIONS_KEEP = 5;

    /** ถาม GitHub ซ้ำได้เร็วสุดทุกกี่นาที (freshness check ใน latestVersionFresh) */
    public const FRESH_TTL_MINUTES = 5;

    public function syncLatestRelease(Product $product): ?ProductVersion
    {
        $githubSetting = $product->githubSetting;

        if (! $githubSetting || ! $githubSetting->is_active) {
            throw new \Exception('GitHub settings not configured for this product');
        }

        $release = $this->fetchLatestRelease($githubSetting);

        if (! $release) {
            throw new \Exception('Could not fetch release from GitHub');
        }

        $version = $this->createOrUpdateVersion($product, $githubSetting, $release);

        // Cleanup: keep only the latest N versions, delete the rest
        $this->cleanupOldVersions($product);

        // เพิ่ง sync สด ๆ → ล้าง cache freshness ทิ้ง ไม่งั้นรอบหน้าจะเชื่อค่าเก่า
        Cache::forget($this->freshCacheKey($product));

        return $version;
    }

    /**
     * cache key เดียวสำหรับ freshness check — ทุกที่ที่อ่าน/ล้าง ต้องผ่าน method นี้เท่านั้น
     *
     * เคส tpix.online (2026-06-19) เจ็บมาแล้ว: หน้าเว็บอ่าน `chain_releases_<md5>`
     * แต่ webhook ไปล้าง `chain_releases` เฉย ๆ → webhook ไม่เคย bust cache ได้เลย
     */
    public function freshCacheKey(Product $product): string
    {
        return "product:release:fresh:{$product->id}";
    }

    /**
     * เวอร์ชันล่าสุดที่ "การันตีว่าตรงกับ GitHub" — แบบเดียวกับ NetWix AppRelease::latest()
     *
     * ต่างจาก Product::latestVersion() ตรงที่ตัวนี้จะแอบถาม GitHub ให้ด้วย (cache 5 นาที)
     * ถ้าพบว่า GitHub มี tag ใหม่กว่าที่ DB รู้ จะ sync เข้ามาทันทีในคำขอนั้นเลย
     * → ต่อให้ cron ตาย เว็บก็ยังตรงกับ GitHub ภายใน 5 นาที ไม่ต้องมีใครกดปุ่ม
     *
     * ⚠️ ห้าม cache ความล้มเหลว — เคส tpix.online cache ค่า null ไว้ 30 นาที
     * ทำให้ GitHub สะดุดแวบเดียวแล้วหน้าดาวน์โหลดว่างยาว 30 นาที
     * ที่นี่ถ้าถาม GitHub ไม่สำเร็จ จะคืนค่าจาก DB และ "ไม่" เขียน cache
     * รอบถัดไปจึงลองใหม่ทันที
     */
    public function latestVersionFresh(Product $product): ?ProductVersion
    {
        $current = $product->latestVersion();
        $githubSetting = $product->githubSetting;

        if (! $githubSetting || ! $githubSetting->is_active) {
            return $current;
        }

        $key = $this->freshCacheKey($product);

        // ยังอยู่ในช่วง cache = เพิ่งถาม GitHub ไป ไม่ต้องถามซ้ำทุก request
        if (Cache::has($key)) {
            return $current;
        }

        $release = $this->fetchLatestRelease($githubSetting);
        $tag = $release['tag_name'] ?? null;

        if (! $tag) {
            // ล้มเหลว (fetchLatestRelease log ไว้แล้ว) — ไม่เขียน cache, คืนของเดิมไปก่อน
            return $current;
        }

        // สำเร็จเท่านั้นถึงเขียน cache
        Cache::put($key, $tag, now()->addMinutes(self::FRESH_TTL_MINUTES));

        $remote = ltrim($tag, 'vV');

        if ($current && $current->version === $remote) {
            return $current;
        }

        try {
            $synced = $this->syncLatestRelease($product);

            Log::info('product release auto-synced on read', [
                'product' => $product->slug,
                'from' => $current?->version,
                'to' => $synced?->version,
            ]);

            return $synced ?? $product->refresh()->latestVersion();
        } catch (\Exception $e) {
            Log::warning('read-through release sync failed', [
                'product' => $product->slug,
                'error' => $e->getMessage(),
            ]);

            return $current;
        }
    }

    /**
     * Remove old versions beyond MAX_VERSIONS_KEEP.
     * Deletes associated download logs and any local files.
     */
    public function cleanupOldVersions(Product $product): int
    {
        // Get IDs of versions to keep (latest N by synced_at/created_at)
        $keepIds = ProductVersion::where('product_id', $product->id)
            ->orderByDesc('synced_at')
            ->orderByDesc('created_at')
            ->limit(self::MAX_VERSIONS_KEEP)
            ->pluck('id');

        // Find versions to delete
        $toDelete = ProductVersion::where('product_id', $product->id)
            ->whereNotIn('id', $keepIds)
            ->get();

        if ($toDelete->isEmpty()) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($toDelete as $version) {
            // Delete associated download logs
            $version->downloadLogs()->delete();

            // Delete version record
            $version->delete();
            $deletedCount++;

            Log::info("Cleaned up old version: {$product->name} v{$version->version}");
        }

        Log::info("Version cleanup for {$product->name}: deleted {$deletedCount} old versions, kept " . self::MAX_VERSIONS_KEEP);

        return $deletedCount;
    }

    /**
     * Fetch all releases from GitHub
     */
    public function fetchAllReleases(GithubSetting $githubSetting, int $perPage = 10): array
    {
        $response = Http::withHeaders($this->getHeaders($githubSetting))
            ->get($githubSetting->releases_api_url, [
                'per_page' => $perPage,
            ]);

        if (! $response->successful()) {
            Log::error('GitHub API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'repo' => $githubSetting->full_repo_name,
            ]);

            return [];
        }

        return $response->json();
    }

    /**
     * Fetch the latest release from GitHub
     */
    public function fetchLatestRelease(GithubSetting $githubSetting): ?array
    {
        $response = Http::withHeaders($this->getHeaders($githubSetting))
            ->get($githubSetting->latest_release_api_url);

        if (! $response->successful()) {
            Log::error('GitHub API Error - Latest Release', [
                'status' => $response->status(),
                'body' => $response->body(),
                'repo' => $githubSetting->full_repo_name,
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch a specific release by tag
     */
    public function fetchReleaseByTag(GithubSetting $githubSetting, string $tag): ?array
    {
        $url = "https://api.github.com/repos/{$githubSetting->full_repo_name}/releases/tags/{$tag}";

        $response = Http::withHeaders($this->getHeaders($githubSetting))
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Download a release asset
     * Returns the content stream for proxying
     */
    public function downloadAsset(GithubSetting $githubSetting, string $assetUrl)
    {
        $downloadHeaders = [
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download-Service',
        ];
        $token = $githubSetting->github_token_decrypted;
        if (! empty($token)) {
            $downloadHeaders['Authorization'] = 'Bearer ' . $token;
        }

        $response = Http::withHeaders($downloadHeaders)
            ->withOptions([
                'stream' => true,
            ])->get($assetUrl);

        if (! $response->successful()) {
            throw new \Exception('Could not download asset from GitHub');
        }

        return $response;
    }

    /**
     * Get download URL for an asset (requires authentication for private repos)
     */
    public function getAssetDownloadUrl(GithubSetting $githubSetting, int $assetId): ?string
    {
        $url = "https://api.github.com/repos/{$githubSetting->full_repo_name}/releases/assets/{$assetId}";

        $assetHeaders = [
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download-Service',
        ];
        $token = $githubSetting->github_token_decrypted;
        if (! empty($token)) {
            $assetHeaders['Authorization'] = 'Bearer ' . $token;
        }

        $response = Http::withHeaders($assetHeaders)
            ->withOptions([
                'allow_redirects' => false,
            ])->get($url);

        if ($response->status() === 302) {
            return $response->header('Location');
        }

        return null;
    }

    /**
     * Create or update a ProductVersion from a GitHub release
     */
    protected function createOrUpdateVersion(Product $product, GithubSetting $githubSetting, array $release): ProductVersion
    {
        // Extract version from tag name (remove 'v' prefix if present)
        $version = ltrim($release['tag_name'], 'v');

        // Find matching asset
        $asset = $this->findMatchingAsset($release['assets'] ?? [], $githubSetting->asset_pattern);

        $data = [
            'product_id' => $product->id,
            'version' => $version,
            'github_release_id' => $release['id'],
            'github_release_url' => $asset ? $asset['url'] : $release['html_url'],
            'download_filename' => $asset ? $asset['name'] : null,
            'file_size' => $asset ? $asset['size'] : null,
            'changelog' => $release['body'] ?? null,
            'is_active' => true,
            'synced_at' => now(),
        ];

        // Deactivate previous versions
        ProductVersion::where('product_id', $product->id)
            ->where('version', '!=', $version)
            ->update(['is_active' => false]);

        return ProductVersion::updateOrCreate(
            ['product_id' => $product->id, 'version' => $version],
            $data
        );
    }

    /**
     * Find an asset matching the pattern
     */
    protected function findMatchingAsset(array $assets, string $pattern): ?array
    {
        if (empty($assets)) {
            return null;
        }

        // Convert glob pattern to regex
        $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/i';

        foreach ($assets as $asset) {
            if (preg_match($regex, $asset['name'])) {
                return $asset;
            }
        }

        // If no match, return first asset
        return $assets[0] ?? null;
    }

    /**
     * Get HTTP headers for GitHub API requests
     */
    protected function getHeaders(GithubSetting $githubSetting): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'XMAN-Studio-Release-Service',
        ];

        $token = $githubSetting->github_token_decrypted;
        if (! empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Test GitHub connection
     */
    public function testConnection(GithubSetting $githubSetting): array
    {
        $response = Http::withHeaders($this->getHeaders($githubSetting))
            ->get("https://api.github.com/repos/{$githubSetting->full_repo_name}");

        if ($response->successful()) {
            $repo = $response->json();

            return [
                'success' => true,
                'message' => 'Connection successful',
                'repo_name' => $repo['full_name'],
                'is_private' => $repo['private'],
                'default_branch' => $repo['default_branch'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Connection failed: ' . $response->body(),
            'status' => $response->status(),
        ];
    }
}
