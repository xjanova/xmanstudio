<?php

namespace App\Http\Controllers;

use App\Exceptions\DownloadUnavailableException;
use App\Services\CluadeXUpdateFeed;
use App\Services\ReleaseDownloadStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CluadeX desktop app updates itself from https://xman4289.com/cluadex/download/ (a Velopack
 * custom source — GitHub is never named to the app, owner 2026-11-09):
 *
 *   GET /cluadex/download/releases.win.json   the feed, release notes cleaned (Velopack adds ?arch=…&localVersion=…, ignored)
 *   GET /cluadex/download/{file}              a package the feed lists, streamed through this server (Range/resume)
 *
 * GET /cluadex/download itself (the installer, CluadeXWebController) is a different route with its own
 * throttle. Every answer here is for the app, not a person, so failures are JSON.
 */
class CluadeXUpdateController extends Controller
{
    public function __construct(private CluadeXUpdateFeed $feed) {}

    public function feed(): Response
    {
        $pair = $this->feed->current();

        if ($pair === null) {
            return $this->unavailable();
        }

        return response($pair['json'], 200, [
            'Content-Type' => 'application/json',
            'Content-Length' => (string) strlen($pair['json']),
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
                'product' => 'cluadex',
                'tag' => $package['tag'],
                'file' => $file,
            ]);
        } catch (DownloadUnavailableException $e) {
            return $this->fail($e->status, $e->getMessage(), $e->retryAfter ?? ($e->status >= 500 ? 60 : null));
        }
    }

    private function unavailable(): JsonResponse
    {
        return $this->fail(503, 'CluadeX updates cannot be checked right now, please try again shortly', 60);
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
