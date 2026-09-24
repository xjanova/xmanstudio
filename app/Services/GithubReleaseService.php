<?php

namespace App\Services;

use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Support\ReleaseNotes;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubReleaseService
{
    /** Maximum number of versions to keep per product */
    public const MAX_VERSIONS_KEEP = 5;

    /** ถาม GitHub ซ้ำได้เร็วสุดทุกกี่นาที (freshness check ใน latestVersionFresh) */
    public const FRESH_TTL_MINUTES = 5;

    /**
     * cron ถามแอปที่ไม่มี token ห่างสุดทุกกี่นาที (แอปที่มี token ยังถามทุกรอบของ cron)
     *
     * ไม่มี token = ใช้โควตา 60 ครั้ง/ชม. ของ IP เซิร์ฟเวอร์ร่วมกันทุกแอป (และทุกโปรแกรมบนเครื่องเดียวกัน)
     * cron ทุก 10 นาที × 7 แอปกินไปแล้ว 42 ครั้ง — 2026-09-25 โควตาหมดทั้งชั่วโมง Chanthra 0.11.0 จึงไม่ถูก sync
     * release ใหม่ยังขึ้นเว็บภายใน FRESH_TTL_MINUTES ผ่าน read-through เมื่อมีคนเปิดหน้า/แอปเช็คอัปเดต
     * cron เป็นแค่ตัวกันพลาดตอนไม่มีใครเข้า
     */
    public const TOKENLESS_SYNC_MINUTES = 30;

    /** เหลือโควตาเท่านี้แล้ว read-through หยุดถาม — ที่เหลือเก็บไว้ให้ cron กับปุ่ม Sync ในหน้า admin */
    public const READ_THROUGH_RESERVE = 20;

    /**
     * Sync the latest release from GitHub for a product
     *
     * @param  ?array  $release  release ที่ผู้เรียกเพิ่งถามมาแล้ว (read-through) — ไม่ต้องถาม GitHub ซ้ำ
     */
    public function syncLatestRelease(Product $product, ?array $release = null): ?ProductVersion
    {
        $githubSetting = $product->githubSetting;

        if (! $githubSetting || ! $githubSetting->is_active) {
            throw new \Exception('GitHub settings not configured for this product');
        }

        $release ??= $this->fetchLatestRelease($githubSetting);

        if (! $release) {
            // ข้อความนี้ขึ้นในหน้า admin (ปุ่ม Sync) — บอกให้รู้ว่ารอถึงเมื่อไหร่ ไม่ใช่แค่ "ดึงไม่ได้"
            if ($until = $this->quotaPausedUntil($githubSetting)) {
                throw new \Exception('โควตา GitHub API ของเซิร์ฟเวอร์หมดชั่วคราว ลองใหม่หลัง '
                    . $until->copy()->timezone('Asia/Bangkok')->format('H:i') . ' น.');
            }

            throw new \Exception('Could not fetch release from GitHub');
        }

        $version = $this->createOrUpdateVersion($product, $githubSetting, $release);

        // Cleanup: keep only the latest N versions, delete the rest
        $this->cleanupOldVersions($product);

        // เพิ่ง sync สด ๆ = DB ตรงกับ GitHub ณ ตอนนี้ → นับเป็นการถามรอบล่าสุดของ read-through ด้วย
        // (เดิมล้าง cache ทิ้ง คำขอถัดไปจึงถาม GitHub ซ้ำทันทีทั้งที่เพิ่งได้คำตอบมา เปลืองโควตาเปล่า ๆ)
        Cache::put($this->freshCacheKey($product), $release['tag_name'] ?? $version->version, now()->addMinutes(self::FRESH_TTL_MINUTES));

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
     * รอบถัดไปจึงลองใหม่ทันที — ยกเว้นเรื่องโควตา: GitHub บอกว่าหมด = พักทุกทางจนถึงเวลารีเซ็ต
     * และเมื่อเหลือไม่เกิน READ_THROUGH_RESERVE ตัวนี้หยุดถามเอง (ดู mayReadThrough())
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

        // โควตาหมดหรือใกล้หมด — ใช้ของใน DB ไปก่อน cron ตามให้เองเมื่อโควตากลับมา
        if (! $this->mayReadThrough($githubSetting)) {
            return $current;
        }

        try {
            $release = $this->fetchLatestRelease($githubSetting);
        } catch (\Throwable $e) {
            // ต่อ GitHub ไม่ติดเลย (timeout/DNS) HTTP client โยน ConnectionException ออกมา — เดิมหลุดขึ้นไป
            // ทำให้ /update/check ของทุกผลิตภัณฑ์ตอบ 500 · สัญญาของ method นี้คือคืนค่าจาก DB แทน
            Log::warning('GitHub unreachable during read-through release check', [
                'product' => $product->slug,
                'error' => $e->getMessage(),
            ]);

            return $current;
        }
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
            // ส่ง release ที่เพิ่งได้มาไปด้วย — เดิม sync ถาม GitHub ซ้ำอีกรอบทุกครั้งที่เจอเวอร์ชันใหม่
            $synced = $this->syncLatestRelease($product, $release);

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
        $response = $this->githubRequest($githubSetting, fn (bool $withToken) => Http::withHeaders($this->getHeaders($githubSetting, $withToken))
            ->get($githubSetting->releases_api_url, [
                'per_page' => $perPage,
            ]));

        // พักการถาม GitHub อยู่ (โควตาหมด) — log ไว้แล้วครั้งเดียวตอนเริ่มพัก
        if ($response === null || $this->rateLimitedUntil($response)) {
            return [];
        }

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
        $response = $this->githubRequest($githubSetting, fn (bool $withToken) => Http::withHeaders($this->getHeaders($githubSetting, $withToken))
            ->get($githubSetting->latest_release_api_url));

        // พักการถาม GitHub อยู่ (โควตาหมด) — log ไว้แล้วครั้งเดียวตอนเริ่มพัก ไม่ใช่ทุกคำขอ
        if ($response === null || $this->rateLimitedUntil($response)) {
            return null;
        }

        if (! $response->successful()) {
            Log::error('GitHub API Error - Latest Release', [
                'status' => $response->status(),
                'body' => $response->body(),
                'repo' => $githubSetting->full_repo_name,
            ]);

            return null;
        }

        // ให้ cron รู้ว่าเพิ่งมีคนถามแอปนี้ไป (read-through หรือ cron เอง) — ดู checkedRecently()
        Cache::put($this->checkedKey($githubSetting), now()->getTimestamp(), now()->addMinutes(self::TOKENLESS_SYNC_MINUTES));

        return $response->json();
    }

    /**
     * Fetch a specific release by tag
     */
    public function fetchReleaseByTag(GithubSetting $githubSetting, string $tag): ?array
    {
        $url = "https://api.github.com/repos/{$githubSetting->full_repo_name}/releases/tags/{$tag}";

        $response = $this->githubRequest($githubSetting, fn (bool $withToken) => Http::withHeaders($this->getHeaders($githubSetting, $withToken))
            ->get($url));

        if ($response === null || ! $response->successful()) {
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
        // token ตายแล้วต้องไม่ทำให้ลูกค้าโหลดไฟล์ไม่ได้ — ใช้ทางถอยเดียวกับตอนอ่าน release
        $buildHeaders = function (bool $withToken) use ($githubSetting): array {
            $headers = [
                'Accept' => 'application/octet-stream',
                'User-Agent' => 'XMAN-Studio-Download-Service',
            ];
            $token = $withToken ? $githubSetting->github_token_decrypted : null;
            if (! empty($token)) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            return $headers;
        };

        $response = $this->githubRequest($githubSetting, fn (bool $withToken) => Http::withHeaders($buildHeaders($withToken))
            ->withOptions([
                'stream' => true,
            ])->get($assetUrl));

        if ($response === null || ! $response->successful()) {
            throw new \Exception('Could not download asset from GitHub');
        }

        return $response;
    }

    /**
     * เลข asset ของไฟล์เวอร์ชันนี้บน GitHub
     *
     * เวอร์ชันที่ sync มาเก็บ URL ของ asset API (…/releases/assets/<id>) ไว้ใน github_release_url
     * แต่ถ้า release นั้นไม่มี asset จะเป็นหน้า release (html_url) และเวอร์ชันที่สร้างมือในหน้า admin
     * เป็นลิงก์อะไรก็ได้ — สองแบบหลังไม่ใช่ไฟล์บน GitHub คืน null
     */
    public function releaseAssetId(ProductVersion $version): ?int
    {
        return preg_match('#^https://api\.github\.com/repos/[^/]+/[^/]+/releases/assets/(\d+)$#', (string) $version->github_release_url, $m)
            ? (int) $m[1]
            : null;
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
            // ลิงก์ตรงของไฟล์ — repo public ส่งลูกค้าไปโหลดได้เลยโดยไม่ต้องถาม API
            'download_url' => $asset['browser_download_url'] ?? null,
            'download_filename' => $asset ? $asset['name'] : null,
            'file_size' => $asset ? $asset['size'] : null,
            // body ของ GitHub พกลิงก์ repo มาเสมอ ("**Full Changelog**: …/compare/…", "by @user in …/pull/N")
            // ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24) — เก็บเฉพาะข้อความที่คนเขียน · cron sync ทุก 10 นาที
            // จึงล้างแถวของเวอร์ชันล่าสุดที่ sync ไว้ก่อนหน้านี้ให้เองด้วย
            'changelog' => ReleaseNotes::forCustomers(
                $release['body'] ?? null,
                [$githubSetting->github_owner, ...ReleaseNotes::studioAccounts()],
            ),
            'is_active' => true,
            'synced_at' => now(),
        ];

        // GitHub ใส่ digest "sha256:<hex>" ให้ asset ที่อัปโหลดตั้งแต่กลางปี 2025 — แอปใช้ตรวจไฟล์
        // ที่โหลดมาก่อนติดตั้ง asset เก่าไม่มีค่านี้ → ไม่ใส่คีย์เลย ค่าที่มีอยู่แล้วจึงไม่ถูกทับด้วย null
        $sha256 = $this->sha256FromDigest($asset['digest'] ?? null);

        if ($sha256 !== null) {
            $data['sha256'] = $sha256;
        }

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
     * "sha256:ABC…" → "abc…" (hex ตัวเล็ก 64 ตัว) · ไม่มีหรือเป็นอัลกอริทึมอื่น = null
     */
    protected function sha256FromDigest(mixed $digest): ?string
    {
        if (is_string($digest) && preg_match('/^sha256:([0-9a-f]{64})$/i', $digest, $m)) {
            return strtolower($m[1]);
        }

        return null;
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
    protected function getHeaders(GithubSetting $githubSetting, bool $withToken = true): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'XMAN-Studio-Release-Service',
        ];

        $token = $withToken ? $githubSetting->github_token_decrypted : null;
        if (! empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * ยิง GitHub API แล้วกู้สถานการณ์เองเมื่อ token ที่เก็บไว้ตายแล้ว
     *
     * 2026-08-31 — cluadex เงียบไป 26 วัน เพราะ PAT (`ghp_…`) ในฐานข้อมูลถูก revoke
     * GitHub ตอบ **401 Bad credentials** ทั้งที่ repo เป็น public ที่ใครก็อ่านได้
     * ⇒ token ที่ตายแล้ว "แย่กว่า" การไม่มี token เลย
     * ซ้ำร้ายหน้า admin ลบ token ทิ้งไม่ได้ (ช่องนี้ validate เป็น required) = ตันสนิท
     *
     * repo สาธารณะอ่าน release ได้โดยไม่ต้องล็อกอิน จึงถอยไปยิงซ้ำแบบไม่ใส่ token
     * repo ส่วนตัวจะได้ 404 อยู่ดี ไม่มีอะไรเสียหาย แค่ได้ข้อความ error ที่ตรงกว่าเดิม
     *
     * ⚠️ ยัง Log::error ทุกครั้งที่ token ถูกปฏิเสธ — fallback ต้องไม่กลายเป็นการซุกปัญหา
     *    ไว้เงียบ ๆ จนไม่มีใครรู้ว่าต้องไปเปลี่ยน token (นั่นคือวิธีที่ของพังยาว ๆ)
     *
     * ทุกคำขอผ่านโควตาของ bucket ตัวเอง (sendWithinQuota) — โควตาหมดอยู่ = ไม่ยิงเลย คืน null
     *
     * @param  callable  $send  รับ bool $withToken คืน Response — เรียกซ้ำได้ทั้งแบบใส่และไม่ใส่ token
     */
    protected function githubRequest(GithubSetting $githubSetting, callable $send): ?Response
    {
        $response = $this->sendWithinQuota($this->quotaBucket($githubSetting, true), fn () => $send(true));

        if ($response === null || empty($githubSetting->github_token_decrypted) || ! $this->tokenRejected($response)) {
            return $response;
        }

        Log::error('GitHub ปฏิเสธ token ที่เก็บไว้ — ยิงซ้ำแบบไม่ใช้ token', [
            'status' => $response->status(),
            'repo' => $githubSetting->full_repo_name,
            'ต้องทำ' => 'ลบหรือเปลี่ยน GitHub token ของผลิตภัณฑ์นี้ในหน้า admin',
        ]);

        return $this->sendWithinQuota('anonymous', fn () => $send(false));
    }

    /**
     * แยก "token ใช้ไม่ได้" ออกจาก "โดนจำกัดจำนวนครั้ง"
     *
     * 401 = Bad credentials ชัดเจน · 403 เป็นได้ทั้งสองอย่าง
     * ถ้าเป็น rate limit (หลักหรือ secondary) ห้ามยิงซ้ำแบบไม่ล็อกอินเด็ดขาด — โควตาไม่ล็อกอินคือ
     * 60 ครั้ง/ชม. เทียบกับ 5,000 ครั้ง/ชม. ตอนมี token ⇒ ยิ่งซ้ำยิ่งแย่
     */
    protected function tokenRejected(Response $response): bool
    {
        $status = $response->status();

        if ($status === 401) {
            return true;
        }

        if ($status !== 403) {
            return false;
        }

        return $this->rateLimitedUntil($response) === null;
    }

    /**
     * ถึงเวลาไหนที่ห้ามถาม GitHub ในนามของ GitHub setting นี้ (โควตาหมด) — null = ถามได้
     *
     * cron ใช้ตัวนี้ข้ามทั้งรอบแบบเงียบ ๆ แทนการ log "sync failed" ทุกแอปทุก 10 นาที
     */
    public function quotaPausedUntil(GithubSetting $githubSetting): ?Carbon
    {
        return $this->pausedUntil($this->quotaBucket($githubSetting, true));
    }

    /** ใช้โควตาร่วม 60 ครั้ง/ชม. ของ IP เซิร์ฟเวอร์ (ไม่มี token) — cron ถามแอปพวกนี้ห่างขึ้น */
    public function usesSharedQuota(GithubSetting $githubSetting): bool
    {
        return $this->quotaBucket($githubSetting, true) === 'anonymous';
    }

    /** มีคนถาม release ล่าสุดของแอปนี้สำเร็จไปแล้วภายใน TOKENLESS_SYNC_MINUTES (cron หรือ read-through) */
    public function checkedRecently(GithubSetting $githubSetting): bool
    {
        return Cache::has($this->checkedKey($githubSetting));
    }

    /**
     * read-through ถาม GitHub ได้ไหม — ไม่ได้ถ้าโควตาหมด (รอถึงเวลารีเซ็ต) หรือเหลือไม่เกิน READ_THROUGH_RESERVE
     *
     * หน้าเว็บกับ update/check ของแอปมีคนเรียกตลอด ถ้าปล่อยให้ใช้จนเกลี้ยง cron จะไม่เหลือโควตาไว้
     * ตามเวอร์ชันใหม่ · โควตาที่เหลือจำจาก header ของคำตอบล่าสุด (rememberQuota)
     */
    protected function mayReadThrough(GithubSetting $githubSetting): bool
    {
        $bucket = $this->quotaBucket($githubSetting, true);

        if ($this->pausedUntil($bucket)) {
            return false;
        }

        $quota = Cache::get($this->quotaKey($bucket));

        return ! (is_array($quota)
            && $quota['remaining'] <= self::READ_THROUGH_RESERVE
            && $quota['reset'] > now()->getTimestamp());
    }

    /**
     * โควตาของใคร: ไม่มี token = ของ IP เซิร์ฟเวอร์ ใช้ร่วมกันทุกแอป · มี token = ของ token นั้น (5,000 ครั้ง/ชม.)
     */
    protected function quotaBucket(GithubSetting $githubSetting, bool $withToken): string
    {
        $token = $withToken ? $githubSetting->github_token_decrypted : null;

        return empty($token) ? 'anonymous' : 'token:' . substr(hash('sha256', $token), 0, 16);
    }

    /**
     * ยิงหนึ่งครั้งภายใต้โควตาของ bucket — กำลังพักอยู่ = ไม่ยิง คืน null
     * จำโควตาที่เหลือจาก header ทุกครั้ง และเริ่มพักเมื่อ GitHub บอกว่าหมด
     */
    private function sendWithinQuota(string $bucket, callable $send): ?Response
    {
        if ($this->pausedUntil($bucket)) {
            return null;
        }

        $response = $send();

        $this->rememberQuota($bucket, $response);

        if ($until = $this->rateLimitedUntil($response)) {
            $this->pause($bucket, $until, $response);
        }

        return $response;
    }

    /**
     * GitHub บอกว่าโควตาหมดไหม และให้รอถึงเมื่อไหร่ — ตามเอกสาร rate limit ของ GitHub:
     * มี retry-after (วินาที) ให้รอตามนั้น · x-ratelimit-remaining = 0 ให้รอถึง x-ratelimit-reset · นอกนั้นรออย่างน้อย 1 นาที
     *
     * 403 ที่ไม่มีทั้ง header เหล่านี้และข้อความ "rate limit" = เรื่องสิทธิ์/token ไม่ใช่โควตา → null
     */
    protected function rateLimitedUntil(Response $response): ?Carbon
    {
        $status = $response->status();

        if ($status !== 403 && $status !== 429) {
            return null;
        }

        $retryAfter = $response->header('retry-after');
        $remaining = $response->header('x-ratelimit-remaining');
        $reset = $response->header('x-ratelimit-reset');
        $message = strtolower((string) $response->json('message', ''));

        if ($status === 403 && $remaining !== '0' && ! is_numeric($retryAfter) && ! str_contains($message, 'rate limit')) {
            return null;
        }

        $until = match (true) {
            is_numeric($retryAfter) => now()->addSeconds((int) $retryAfter),
            $remaining === '0' && is_numeric($reset) => Carbon::createFromTimestamp((int) $reset)->addSeconds(5),
            default => now()->addMinute(),
        };

        // นาฬิกาเซิร์ฟเวอร์กับ GitHub อาจเหลื่อมกัน — พักอย่างน้อย 1 นาที อย่างมากหนึ่งหน้าต่างโควตา (ชั่วโมงเศษ)
        return $until->max(now()->addMinute())->min(now()->addMinutes(65));
    }

    /**
     * เริ่มพักการถาม GitHub ของ bucket นี้ — Cache::add เขียนได้เฉพาะคนแรก จึง log ครั้งเดียวต่อช่วงที่พัก
     */
    private function pause(string $bucket, Carbon $until, Response $response): void
    {
        if (! Cache::add($this->pauseKey($bucket), $until->getTimestamp(), $until)) {
            return;
        }

        Log::warning('GitHub API quota exhausted — no GitHub calls until the reset, the DB versions are served meanwhile', [
            'bucket' => $bucket,
            'status' => $response->status(),
            'until' => $until->toIso8601String(),
            // ข้อความของ GitHub มี IP ต้นทางของเซิร์ฟเวอร์ ("… exceeded for <ip>") — อยู่หลัง Cloudflare ห้ามหลุดไปไหน
            'message' => mb_substr((string) preg_replace('/ for [0-9a-f.:]+/i', ' for [server]', (string) $response->json('message', '')), 0, 200),
        ]);
    }

    private function pausedUntil(string $bucket): ?Carbon
    {
        $until = Cache::get($this->pauseKey($bucket));

        return is_numeric($until) && (int) $until > now()->getTimestamp()
            ? Carbon::createFromTimestamp((int) $until)
            : null;
    }

    /** โควตาที่เหลือตาม header ของคำตอบล่าสุด — ทุกคำตอบของ GitHub API มี x-ratelimit-* มาด้วย */
    private function rememberQuota(string $bucket, Response $response): void
    {
        $remaining = $response->header('x-ratelimit-remaining');
        $reset = $response->header('x-ratelimit-reset');

        if (! is_numeric($remaining) || ! is_numeric($reset) || (int) $reset <= now()->getTimestamp()) {
            return;
        }

        Cache::put($this->quotaKey($bucket), [
            'remaining' => (int) $remaining,
            'reset' => (int) $reset,
        ], Carbon::createFromTimestamp((int) $reset));
    }

    private function pauseKey(string $bucket): string
    {
        return "github:api:paused:{$bucket}";
    }

    private function quotaKey(string $bucket): string
    {
        return "github:api:quota:{$bucket}";
    }

    private function checkedKey(GithubSetting $githubSetting): string
    {
        return "github:release:checked:{$githubSetting->id}";
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
