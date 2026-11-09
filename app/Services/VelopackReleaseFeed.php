<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A desktop app's Velopack update feed, served from this site so the app never names GitHub
 * (the owner's rule, 2026-09-24: "ห้ามโหลดหรือมีลิ้งค์ไป github", "ห้ามให้ลูกค้ารู้ repo").
 *
 * The app's CI publishes `releases.win.json` (the Velopack feed) on every GitHub release, and, for
 * apps that sign it, `releases.win.json.sig`. This class finds the latest release with one HEAD on
 * "latest/download" (no GitHub API, whose 60 calls an hour are shared with every product's release
 * sync), takes the files from that one release, and remembers them: FRESH_MINUTES while fresh, the
 * last good copy for as long as GitHub cannot be read.
 *
 * The package files the feed lists (App-1.2.3-full.nupkg, deltas) are streamed by
 * ReleaseDownloadStreamer from the same release; only a name the feed lists is ever fetched.
 *
 * Subclasses say which repository, which cache keys, whether a signature is required, and may
 * rewrite an unsigned feed (prepare()) — a signed one is served byte for byte.
 */
abstract class VelopackReleaseFeed
{
    public const FEED = 'releases.win.json';

    public const SIGNATURE = 'releases.win.json.sig';

    public const FRESH_MINUTES = 5;

    public const BACKOFF_SECONDS = 60;

    /** A Velopack package name as vpk writes it: App-2.0.492-full.nupkg, App-2.0.492-delta.nupkg. */
    public const FILE_PATTERN = '[A-Za-z0-9_][A-Za-z0-9._-]{0,199}';

    /** The feed carries release notes, so it is not tiny, but it is never this big. */
    protected const MAX_FEED_BYTES = 4 * 1024 * 1024;

    protected const MAX_SIGNATURE_BYTES = 8 * 1024;

    private const USER_AGENT = 'XMAN-Studio-Download-Proxy';

    /** "owner/repo" — server-side only, never sent to an app. */
    abstract protected function repository(): string;

    /** Prefix of this feed's cache keys, e.g. "brainx:update-feed". */
    abstract protected function cacheKey(): string;

    /** The app's name in the logs. */
    abstract protected function label(): string;

    /** Must every release carry releases.win.json.sig? A signed feed is never rewritten. */
    abstract protected function signed(): bool;

    /** An unsigned feed's chance to clean itself before it is served; null = do not serve this release. */
    protected function prepare(string $json, string $tag): ?string
    {
        return $json;
    }

    public function lastGoodKey(): string
    {
        return $this->cacheKey() . ':last-good';
    }

    public function backoffKey(): string
    {
        return $this->cacheKey() . ':backoff';
    }

    /**
     * The feed to serve — ['tag' => 'v2.0.492', 'base' => …, 'json' => bytes, 'sig' => bytes|null,
     * 'files' => [name => size]] — or null when GitHub cannot say and no feed was ever fetched.
     */
    public function current(): ?array
    {
        $fresh = Cache::get($this->cacheKey());

        if ($this->isPair($fresh)) {
            return $fresh;
        }

        $lastGood = Cache::get($this->lastGoodKey());
        $lastGood = $this->isPair($lastGood) ? $lastGood : null;

        if (Cache::has($this->backoffKey())) {
            return $lastGood;
        }

        $pair = $this->fetch();

        if ($pair !== null) {
            Cache::put($this->cacheKey(), $pair, now()->addMinutes(self::FRESH_MINUTES));
            Cache::forever($this->lastGoodKey(), $pair);

            return $pair;
        }

        Cache::put($this->backoffKey(), true, now()->addSeconds(self::BACKOFF_SECONDS));

        if ($lastGood !== null) {
            Log::info($this->label() . ' update feed: serving the last good feed while GitHub cannot be read', ['tag' => $lastGood['tag']]);
        }

        return $lastGood;
    }

    /**
     * Where a package the feed lists is fetched from — the feed's own release — and how big the feed
     * says it is; null for any name the feed does not list.
     *
     * @return array{url: string, size: int, tag: string}|null
     */
    public function package(array $pair, string $file): ?array
    {
        $size = $pair['files'][$file] ?? null;

        if (! is_int($size) || ! preg_match('#^' . self::FILE_PATTERN . '$#D', $file) || str_contains($file, '..')) {
            return null;
        }

        return ['url' => $pair['base'] . rawurlencode($file), 'size' => $size, 'tag' => $pair['tag']];
    }

    /**
     * Ask GitHub for the latest release's feed (and signature) — one HEAD to learn the tag, then the
     * files from that tag.
     */
    private function fetch(): ?array
    {
        $latest = 'https://github.com/' . $this->repository() . '/releases/latest/download/' . self::FEED;
        $label = $this->label();
        $tag = null;

        try {
            // 1) "latest" redirects to the latest release's copy of the feed: that names the tag.
            $hop = $this->github()->withoutRedirecting()->head($latest);
            $location = (string) $hop->header('Location');

            if (! $hop->redirect() || ! preg_match(self::releaseFilePattern(), $location, $match)) {
                Log::warning("{$label} update feed: GitHub did not point at a release", ['status' => $hop->status()]);

                return null;
            }

            $base = $match[1];
            $tag = rawurldecode($match[2]);

            // 2) The files from that one release, so a signature is the feed's own.
            $json = $this->download($base . self::FEED, static::MAX_FEED_BYTES);
            $sig = $this->signed() ? $this->download($base . self::SIGNATURE, static::MAX_SIGNATURE_BYTES) : null;
        } catch (\Throwable $e) {
            // Timeout or DNS: the HTTP client throws. Polling apps must not get a 500.
            Log::warning("{$label} update feed: GitHub could not be reached", ['error' => preg_replace('/\?\S*/', '?…', $e->getMessage())]);

            return null;
        }

        if ($json === null || ($this->signed() && $sig === null)) {
            Log::warning("{$label} update feed: the release is missing its feed or signature", ['tag' => $tag, 'feed' => $json !== null, 'signature' => $sig !== null]);

            return null;
        }

        if (! $this->signed()) {
            $json = $this->prepare($json, $tag);

            if ($json === null) {
                return null;
            }
        }

        $files = self::listedFiles($json);

        if ($files === null) {
            Log::warning("{$label} update feed: the release feed is not a Velopack feed", ['tag' => $tag]);

            return null;
        }

        if ($sig !== null && ! preg_match('#^[A-Za-z0-9+/=_\-\s]+$#D', $sig)) {
            Log::warning("{$label} update feed: the signature is not base64 text", ['tag' => $tag]);

            return null;
        }

        // A feed that still names GitHub (release notes with a "Full Changelog" link, an absolute
        // package URL) would hand the repository to every app — it is not served. A signed feed cannot
        // be cleaned here, so its CI has to leave those out; an unsigned one was cleaned by prepare().
        if (preg_match('#github(?:usercontent)?\.(?:com|io)#i', $json . ($sig ?? ''))) {
            Log::warning("{$label} update feed: the release feed names GitHub, so it is not served", ['tag' => $tag]);

            return null;
        }

        return ['tag' => $tag, 'base' => $base, 'json' => $json, 'sig' => $sig, 'files' => $files];
    }

    /** One small release file's bytes, exactly as GitHub holds them; null when it is not there. */
    private function download(string $url, int $maxBytes): ?string
    {
        $response = $this->github()->get($url);
        $body = $response->body();

        if ($response->status() !== 200
            || $body === ''
            || strlen($body) > $maxBytes
            || str_starts_with(strtolower((string) $response->header('Content-Type')), 'text/html')) {
            return null;
        }

        return $body;
    }

    /**
     * The package names a Velopack 1.x feed lists, with the size it gives each:
     * {"Assets":[{"PackageId":"BrainX","Version":"2.0.492","Type":"Full","FileName":"BrainX-2.0.492-full.nupkg","Size":123,…}]}.
     * Keys are matched without regard to case. null when this is not such a feed at all.
     *
     * @return array<string, int>|null
     */
    protected static function listedFiles(string $json): ?array
    {
        $assets = self::assets($json);

        if ($assets === null) {
            return null;
        }

        $files = [];

        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $name = self::field($asset, 'FileName');
            $size = self::field($asset, 'Size');

            // Only a plain file name of this release: an absolute URL or a path is never fetched.
            if (is_string($name) && preg_match('#^' . self::FILE_PATTERN . '$#D', $name) && ! str_contains($name, '..')
                && is_int($size) && $size > 0) {
                $files[$name] = $size;
            }
        }

        return $files;
    }

    /** The feed's Assets list, parsed without a byte-order mark; null when it is not a feed. */
    protected static function assets(string $json): ?array
    {
        $feed = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true);

        if (! is_array($feed)) {
            return null;
        }

        $assets = self::field($feed, 'Assets');

        return is_array($assets) && array_is_list($assets) ? $assets : null;
    }

    protected static function field(array $object, string $name): mixed
    {
        foreach ($object as $key => $value) {
            if (is_string($key) && strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function isPair(mixed $value): bool
    {
        return is_array($value) && isset($value['tag'], $value['base'], $value['json'], $value['files'])
            && (! $this->signed() || isset($value['sig']));
    }

    private function github(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(15)
            ->withOptions(['allow_redirects' => ['max' => 5, 'protocols' => ['https']]]);
    }

    /**
     * https://github.com/<owner>/<repo>/releases/download/<tag>/releases.win.json → [1] the release's
     * download base (up to and including the tag's slash), [2] the tag. Any owner and repo, so a
     * renamed or transferred repository (which GitHub redirects) keeps working.
     */
    private static function releaseFilePattern(): string
    {
        return '#^(https://github\.com/[^/]+/[^/]+/releases/download/([^/?\#]+)/)' . preg_quote(self::FEED, '#') . '$#';
    }
}
