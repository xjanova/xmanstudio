<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The BrainX desktop app's Velopack update feed, served from https://xman4289.com/brainx/download/.
 *
 * The app's update source points here, never at GitHub (the owner's rule, 2026-09-24 — see
 * BrainXInstaller). Its CI publishes, on every GitHub release, `releases.win.json` (the Velopack
 * feed) and `releases.win.json.sig` (an ECDSA signature over the feed's exact bytes, which the app
 * checks before it trusts the feed). This class finds the latest release, takes BOTH files from
 * that one release — so the pair always belongs together — and hands their bytes on untouched:
 * no re-encoding, no pretty-printing, nothing the signature does not cover.
 *
 * The package files the feed lists (BrainX-2.0.492-full.nupkg, deltas) are streamed by
 * ReleaseDownloadStreamer from the same release; only a name the feed lists is ever fetched.
 */
class BrainXUpdateFeed
{
    public const FEED = 'releases.win.json';

    public const SIGNATURE = 'releases.win.json.sig';

    /** The pair GitHub gave last, believed for FRESH_MINUTES. */
    public const CACHE_KEY = 'brainx:update-feed';

    /** The last pair that was good, kept until a newer one is: what is served while GitHub is down. */
    public const LAST_GOOD_KEY = 'brainx:update-feed:last-good';

    /** Set after GitHub failed: every app polls, so the next look waits instead of piling on. */
    public const BACKOFF_KEY = 'brainx:update-feed:backoff';

    public const FRESH_MINUTES = 5;

    public const BACKOFF_SECONDS = 60;

    /** A Velopack package name as vpk writes it: BrainX-2.0.492-full.nupkg, BrainX-2.0.492-delta.nupkg. */
    public const FILE_PATTERN = '[A-Za-z0-9_][A-Za-z0-9._-]{0,199}';

    /** The feed carries release notes, so it is not tiny, but it is never this big. */
    private const MAX_FEED_BYTES = 4 * 1024 * 1024;

    private const MAX_SIGNATURE_BYTES = 8 * 1024;

    private const USER_AGENT = 'XMAN-Studio-Download-Proxy';

    /**
     * The feed to serve — ['tag' => 'v2.0.492', 'json' => bytes, 'sig' => bytes, 'files' => [name => size]] —
     * or null when GitHub cannot say and no pair was ever fetched.
     */
    public function current(): ?array
    {
        $fresh = Cache::get(self::CACHE_KEY);

        if (self::isPair($fresh)) {
            return $fresh;
        }

        $lastGood = Cache::get(self::LAST_GOOD_KEY);
        $lastGood = self::isPair($lastGood) ? $lastGood : null;

        if (Cache::has(self::BACKOFF_KEY)) {
            return $lastGood;
        }

        $pair = $this->fetch();

        if ($pair !== null) {
            Cache::put(self::CACHE_KEY, $pair, now()->addMinutes(self::FRESH_MINUTES));
            Cache::forever(self::LAST_GOOD_KEY, $pair);

            return $pair;
        }

        Cache::put(self::BACKOFF_KEY, true, now()->addSeconds(self::BACKOFF_SECONDS));

        if ($lastGood !== null) {
            Log::info('BrainX update feed: serving the last good feed while GitHub cannot be read', ['tag' => $lastGood['tag']]);
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
     * Ask GitHub for the latest release's feed and signature — one HEAD to learn the tag (no API,
     * whose 60 calls an hour are shared with every product's release sync), then both files from that tag.
     */
    private function fetch(): ?array
    {
        $latest = 'https://github.com/' . BrainXInstaller::REPO . '/releases/latest/download/' . self::FEED;

        try {
            // 1) "latest" redirects to the latest release's copy of the feed: that names the tag.
            $hop = $this->github()->withoutRedirecting()->head($latest);
            $location = (string) $hop->header('Location');

            if (! $hop->redirect() || ! preg_match(self::releaseFilePattern(), $location, $match)) {
                Log::warning('BrainX update feed: GitHub did not point at a release', ['status' => $hop->status()]);

                return null;
            }

            $base = $match[1];
            $tag = rawurldecode($match[2]);

            // 2) Both files from that one release, so the signature is the feed's own.
            $json = $this->download($base . self::FEED, self::MAX_FEED_BYTES);
            $sig = $this->download($base . self::SIGNATURE, self::MAX_SIGNATURE_BYTES);
        } catch (\Throwable $e) {
            // Timeout or DNS: the HTTP client throws. Polling apps must not get a 500.
            Log::warning('BrainX update feed: GitHub could not be reached', ['error' => preg_replace('/\?\S*/', '?…', $e->getMessage())]);

            return null;
        }

        if ($json === null || $sig === null) {
            Log::warning('BrainX update feed: the release is missing its feed or signature', ['tag' => $tag, 'feed' => $json !== null, 'signature' => $sig !== null]);

            return null;
        }

        $files = self::listedFiles($json);

        if ($files === null) {
            Log::warning('BrainX update feed: the release feed is not a Velopack feed', ['tag' => $tag]);

            return null;
        }

        if (! preg_match('#^[A-Za-z0-9+/=_\-\s]+$#D', $sig)) {
            Log::warning('BrainX update feed: the signature is not base64 text', ['tag' => $tag]);

            return null;
        }

        // The bytes go out exactly as signed, so they cannot be cleaned up here. A feed that names
        // GitHub (release notes with a "Full Changelog" link, an absolute package URL) would hand the
        // repository to every app — it is not served; BrainX's CI has to leave those out.
        if (preg_match('#github(?:usercontent)?\.(?:com|io)#i', $json . $sig)) {
            Log::warning('BrainX update feed: the release feed names GitHub, so it is not served', ['tag' => $tag]);

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
    private static function listedFiles(string $json): ?array
    {
        // Parsed without a byte-order mark, if the CI ever writes one — served with it all the same.
        $feed = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true);

        if (! is_array($feed)) {
            return null;
        }

        $assets = self::field($feed, 'Assets');

        if (! is_array($assets) || ! array_is_list($assets)) {
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

    private static function field(array $object, string $name): mixed
    {
        foreach ($object as $key => $value) {
            if (is_string($key) && strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    private static function isPair(mixed $value): bool
    {
        return is_array($value) && isset($value['tag'], $value['base'], $value['json'], $value['sig'], $value['files']);
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
