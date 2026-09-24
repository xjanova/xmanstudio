<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The free BrainX installer (BrainX-win-Setup.exe), handed out by xman4289.com.
 *
 * The file is an asset of the BrainX app's latest GitHub release — the same releases its Velopack
 * updater reads. The owner's rule (2026-09-24) is that customers download only from xman4289.com
 * and never learn the repository, so the browser is never sent to GitHub: not by a link, and not
 * by a redirect either — a 302 puts the repository in the address bar just as a link would. This
 * server finds the file and passes its bytes through itself.
 *
 * Deliberately not a ProductVersion of the `brainx` product: a synced version keeps the release
 * body as its changelog, BrainX release bodies end in "Full Changelog: https://github.com/…", and
 * products/show.blade.php prints the changelog to customers.
 */
class BrainXInstaller
{
    /** Server-side only. Never in a response, a page, or anything else a customer can read. */
    public const REPO = 'xjanova/BrainX';

    /** What the app's CI (`vpk pack --packId BrainX`) produces, and the name the customer saves. */
    public const ASSET = 'BrainX-win-Setup.exe';

    public const CACHE_KEY = 'brainx:installer:latest';

    /** How long a release that was found is believed before GitHub is asked again. */
    public const CACHE_MINUTES = 10;

    /** The longest one download may take. Also how long a download slot outlives a worker that died holding it. */
    public const MAX_SECONDS = 7200;

    /** A GitHub transfer slower than this for LOW_SPEED_SECONDS is dropped instead of holding a worker. */
    private const LOW_SPEED_BYTES = 1024;

    private const LOW_SPEED_SECONDS = 60;

    private const USER_AGENT = 'XMAN-Studio-Download-Proxy';

    /**
     * The latest installer — ['tag' => 'v2.0.408', 'url' => …, 'size' => 260677138] — or null
     * while GitHub cannot say. A failure is never cached, so the next request simply asks again.
     */
    public function latest(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $installer = $this->find();

        if ($installer !== null) {
            Cache::put(self::CACHE_KEY, $installer, now()->addMinutes(self::CACHE_MINUTES));
        }

        return $installer;
    }

    /**
     * Pass the installer's bytes to the response as they arrive from GitHub; returns how many were sent.
     *
     * Runs inside the streamed response, after the 200 and its Content-Length have gone out, so it
     * guards what it can still guard: nothing but the exact file is sent, never more than the promised
     * length, and GitHub stops being read the moment the customer goes away.
     */
    public function stream(array $installer): int
    {
        $expected = (int) $installer['size'];
        $checked = false;
        $refused = null;
        $sent = 0;

        // Keep running when the customer leaves, so the caller's `finally` still frees its slot;
        // the write callback notices the departure and stops the transfer itself.
        ignore_user_abort(true);
        set_time_limit(0);

        $curl = curl_init($installer['url']);

        curl_setopt_array($curl, [
            // Over HTTP/2, libcurl kept a transfer going after the write callback refused a chunk,
            // until the low-speed timeout ended it — measured: a cancelled download held its worker
            // and its slot 66 s longer. HTTP/1.1 closes the connection on the spot.
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => self::MAX_SECONDS,
            CURLOPT_LOW_SPEED_LIMIT => self::LOW_SPEED_BYTES,
            CURLOPT_LOW_SPEED_TIME => self::LOW_SPEED_SECONDS,
            CURLOPT_BUFFERSIZE => 128 * 1024,
            CURLOPT_HTTPHEADER => ['User-Agent: ' . self::USER_AGENT, 'Accept: application/octet-stream'],
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use ($expected, &$checked, &$refused, &$sent): int {
                if (! $checked) {
                    $checked = true;

                    // The first bytes of the final answer: curl does not pass on the bodies of the
                    // redirects it follows. Anything but this exact file is refused before one byte of it
                    // reaches the customer.
                    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                    $length = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD_T);

                    if ($status !== 200 || ($length >= 0 && $length !== $expected)) {
                        $refused = "HTTP {$status}, {$length} bytes";

                        return 0;
                    }
                }

                // Never more than the Content-Length the customer was promised.
                $part = substr($chunk, 0, $expected - $sent);

                echo $part;

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();

                $sent += strlen($part);

                // The customer cancelled or closed the tab: stop pulling the rest from GitHub.
                if (connection_aborted()) {
                    return 0;
                }

                return strlen($part) === strlen($chunk) ? strlen($chunk) : 0;
            },
        ]);

        curl_exec($curl);

        $context = ['tag' => $installer['tag'], 'bytes' => $sent, 'of' => $expected];

        if ($sent === $expected) {
            Log::info('BrainX installer downloaded', $context);
        } elseif (connection_aborted()) {
            Log::info('BrainX installer download stopped by the customer', $context);
        } else {
            if ($sent === 0) {
                // Not one byte of the file came: what was cached may no longer match GitHub, so the
                // next download looks the file up again instead of failing the same way for ten minutes.
                Cache::forget(self::CACHE_KEY);
            }

            Log::warning('BrainX installer: the file did not come through from GitHub', $context + [
                'answer' => $refused ?? 'HTTP ' . curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
                'error' => curl_errno($curl) ? curl_error($curl) : null,
            ]);
        }

        return $sent;
    }

    /**
     * Ask GitHub which file is the latest and how big it is — two HEAD requests, no API.
     *
     * The API would do it too, but unauthenticated it allows 60 calls an hour for the whole server,
     * shared with every product's release sync.
     */
    private function find(): ?array
    {
        $latest = 'https://github.com/' . self::REPO . '/releases/latest/download/' . self::ASSET;

        try {
            // 1) GitHub answers with a redirect to the latest release's copy of the file.
            $hop = $this->github()->withoutRedirecting()->head($latest);
            $url = (string) $hop->header('Location');

            if (! $hop->redirect() || ! preg_match(self::releaseFilePattern(), $url, $match)) {
                Log::warning('BrainX installer: GitHub did not point at a release file', ['status' => $hop->status()]);

                return null;
            }

            // 2) The file itself (behind one more redirect, to GitHub's file host) says how big it is.
            $file = $this->github()->head($url);
            $size = (int) $file->header('Content-Length');

            if ($file->status() !== 200 || $size <= 0) {
                Log::warning('BrainX installer: the release file did not answer', ['tag' => $match[1], 'status' => $file->status()]);

                return null;
            }
        } catch (\Throwable $e) {
            // Timeout or DNS: the HTTP client throws. The download page must not become a 500.
            Log::warning('BrainX installer: GitHub could not be reached', ['error' => $e->getMessage()]);

            return null;
        }

        return ['tag' => rawurldecode($match[1]), 'url' => $url, 'size' => $size];
    }

    private function github(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => self::USER_AGENT])
            ->timeout(15)
            ->withOptions(['allow_redirects' => ['max' => 5, 'protocols' => ['https']]]);
    }

    /**
     * github.com/<owner>/<repo>/releases/download/<tag>/BrainX-win-Setup.exe — any owner and repo,
     * so a renamed or transferred repository (which GitHub redirects) keeps working.
     */
    private static function releaseFilePattern(): string
    {
        return '#^https://github\.com/[^/]+/[^/]+/releases/download/([^/?\#]+)/' . preg_quote(self::ASSET, '#') . '$#';
    }
}
