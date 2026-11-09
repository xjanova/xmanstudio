<?php

namespace App\Services;

/**
 * The BrainX desktop app's Velopack update feed, served from https://xman4289.com/brainx/download/.
 *
 * The app's update source points here, never at GitHub (the owner's rule, 2026-09-24 — see
 * BrainXInstaller). Its CI publishes, on every GitHub release, `releases.win.json` and
 * `releases.win.json.sig` (an ECDSA signature over the feed's exact bytes, which the app checks
 * before it trusts the feed). Both come from the same release and are handed on untouched: no
 * re-encoding, no pretty-printing, nothing the signature does not cover. See VelopackReleaseFeed.
 */
class BrainXUpdateFeed extends VelopackReleaseFeed
{
    /** The pair GitHub gave last, believed for FRESH_MINUTES. */
    public const CACHE_KEY = 'brainx:update-feed';

    /** The last pair that was good, kept until a newer one is: what is served while GitHub is down. */
    public const LAST_GOOD_KEY = 'brainx:update-feed:last-good';

    /** Set after GitHub failed: every app polls, so the next look waits instead of piling on. */
    public const BACKOFF_KEY = 'brainx:update-feed:backoff';

    protected function repository(): string
    {
        return BrainXInstaller::REPO;
    }

    protected function cacheKey(): string
    {
        return self::CACHE_KEY;
    }

    protected function label(): string
    {
        return 'BrainX';
    }

    protected function signed(): bool
    {
        return true;
    }
}
