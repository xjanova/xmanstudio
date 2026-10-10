<?php

namespace App\Http\Controllers;

use App\Exceptions\DownloadUnavailableException;
use App\Services\BrainXUpdateFeed;
use App\Services\ReleaseDownloadStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The BrainX desktop app updates itself from https://xman4289.com/brainx/download/ (a Velopack
 * custom source; GitHub is never named to it — see BrainXInstaller for the owner's rule):
 *
 *   GET /brainx/download/releases.win.json      the feed, byte for byte (Velopack adds ?arch=…&localVersion=…, ignored)
 *   GET /brainx/download/releases.win.json.sig  the CI's signature over those exact bytes, byte for byte
 *   GET /brainx/download/{file}                 a package the feed lists, streamed through this server
 *
 * Every answer here is for the app, not a person, so failures are JSON. GET /brainx/download itself
 * (the installer, BrainXDownloadController) is a different route and keeps its own throttle.
 */
class BrainXUpdateController extends Controller
{
    public function __construct(private BrainXUpdateFeed $feed) {}

    public function feed(): Response
    {
        $pair = $this->feed->current();

        if ($pair === null) {
            return $this->unavailable();
        }

        return $this->verbatim($pair['json'], 'application/json');
    }

    public function signature(): Response
    {
        $pair = $this->feed->current();

        if ($pair === null) {
            return $this->unavailable();
        }

        return $this->verbatim($pair['sig'], 'text/plain; charset=UTF-8');
    }

    public function package(Request $request, ReleaseDownloadStreamer $streamer, string $file): Response
    {
        $pair = $this->feed->current();

        if ($pair === null) {
            return $this->unavailable();
        }

        $package = $this->feed->package($pair, $file);

        if ($package === null) {
            return $this->fail(404, 'Not found');
        }

        try {
            return $streamer->respondWithReleaseFile($request, $package['url'], $file, $package['size'], [
                'product' => 'brainx',
                'tag' => $package['tag'],
                'file' => $file,
            ]);
        } catch (DownloadUnavailableException $e) {
            return $this->fail($e->status, $e->getMessage(), $e->retryAfter ?? ($e->status >= 500 ? 60 : null));
        }
    }

    /**
     * The bytes exactly as the release holds them — the signature covers every one — with their length,
     * which Apache passes on for this path (public_html/.htaccess). Never stored on the way: the feed
     * and its signature must always arrive as a pair from the same release.
     */
    private function verbatim(string $bytes, string $type): Response
    {
        return response($bytes, 200, [
            'Content-Type' => $type,
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function unavailable(): JsonResponse
    {
        return $this->fail(503, 'BrainX updates cannot be checked right now, please try again shortly', 60);
    }

    private function fail(int $status, string $error, ?int $retryAfter = null): JsonResponse
    {
        $headers = ['Cache-Control' => 'no-store'];

        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string) $retryAfter;
        }

        return response()->json(['success' => false, 'error' => $error], $status, $headers);
    }
}
