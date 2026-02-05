<?php

namespace App\Services;

use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubReleaseService
{
    /**
     * Sync the latest release from GitHub for a product
     */
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

        return $this->createOrUpdateVersion($product, $githubSetting, $release);
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
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $githubSetting->github_token_decrypted,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download-Service',
        ])->withOptions([
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $githubSetting->github_token_decrypted,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download-Service',
        ])->withOptions([
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
        return [
            'Authorization' => 'Bearer ' . $githubSetting->github_token_decrypted,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'XMAN-Studio-Release-Service',
        ];
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
