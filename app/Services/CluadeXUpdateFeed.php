<?php

namespace App\Services;

use App\Support\ReleaseNotes;
use Illuminate\Support\Facades\Log;

/**
 * The CluadeX desktop app's Velopack update feed, served from https://xman4289.com/cluadex/download/.
 *
 * Owner 2026-11-09: the update details live in the updater itself, pulled from here, and nothing in
 * the app may lead to GitHub ("อย่ามีอะไร โยงไปถึง github เด็ดขาด ไม่ต้องการให้รู้ repo"). Until now
 * CluadeX updated straight from its GitHub releases (Velopack GithubSource), which put the
 * repository's address in the app, in every update request and behind its "details" button.
 *
 * CluadeX's feed is not signed, so it is cleaned before it is served: each asset's release notes go
 * through ReleaseNotes::forCustomers (no GitHub links, no account names, no commit hashes) and the
 * HTML copy of the notes, which Velopack only shows when the markdown is missing, is dropped. What
 * Velopack checks — file names, sizes, SHA1/SHA256 — is never touched. See VelopackReleaseFeed.
 */
class CluadeXUpdateFeed extends VelopackReleaseFeed
{
    /** Server-side only; the app knows https://xman4289.com/cluadex/download/ and nothing else. */
    public const REPO = 'xjanova/cluadeX';

    public const CACHE_KEY = 'cluadex:update-feed';

    protected function repository(): string
    {
        return self::REPO;
    }

    protected function cacheKey(): string
    {
        return self::CACHE_KEY;
    }

    protected function label(): string
    {
        return 'CluadeX';
    }

    protected function signed(): bool
    {
        return false;
    }

    protected function prepare(string $json, string $tag): ?string
    {
        $feed = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true);
        $assets = is_array($feed) ? self::field($feed, 'Assets') : null;

        if (! is_array($assets) || ! array_is_list($assets)) {
            Log::warning('CluadeX update feed: the release feed is not a Velopack feed', ['tag' => $tag]);

            return null;
        }

        $accounts = ReleaseNotes::studioAccounts();

        foreach ($assets as $i => $asset) {
            if (! is_array($asset)) {
                continue;
            }

            foreach (array_keys($asset) as $key) {
                if (! is_string($key)) {
                    continue;
                }
                if (strcasecmp($key, 'NotesMarkdown') === 0) {
                    $asset[$key] = ReleaseNotes::forCustomers(is_string($asset[$key]) ? $asset[$key] : null, $accounts) ?? '';
                } elseif (strcasecmp($key, 'NotesHtml') === 0) {
                    $asset[$key] = '';
                }
            }

            $assets[$i] = $asset;
        }

        foreach (array_keys($feed) as $key) {
            if (is_string($key) && strcasecmp($key, 'Assets') === 0) {
                $feed[$key] = $assets;
            }
        }

        $out = json_encode($feed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($out) ? $out : null;
    }
}
