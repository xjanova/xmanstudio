<?php

namespace App\Http\Controllers;

use App\Services\BrainXInstaller;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GET /brainx/download — the free BrainX app for Windows.
 *
 * Public on purpose: the free tier needs no account and no key (BrainX Cloud, ฿399 a month, is the
 * part that is sold). It serves this one file and nothing else — never a /{slug}/download — so it
 * cannot become a free way into a product that must be bought first; DownloadController keeps its
 * login and purchase checks for those.
 *
 * The bytes pass through this server (BrainXInstaller says why). On production that holds a PHP-FPM
 * worker for as long as the customer takes to download ~260 MB — Apache hands PHP's output straight
 * to the client, and the pool has 50 workers — so each download takes a place in the site-wide pool
 * of streamed downloads, and the route's throttle limits how often one address may start one.
 */
class BrainXDownloadController extends Controller
{
    public function download(Request $request, BrainXInstaller $installer)
    {
        $file = $installer->latest();

        if ($file === null) {
            return $this->unavailable(
                $request,
                'The BrainX installer cannot be fetched right now, please try again shortly',
                'ดาวน์โหลดไม่สำเร็จชั่วคราว',
                'Download unavailable',
                'ตอนนี้ยังดึงไฟล์ติดตั้ง BrainX ไม่ได้ กรุณาลองใหม่อีกครั้งในอีกสักครู่',
                'The BrainX installer cannot be fetched right now. Please try again in a moment.',
            );
        }

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . BrainXInstaller::ASSET . '"',
            'Content-Length' => (string) $file['size'],
            // One whole file per request: a resumed or split (download-manager) request would hold
            // a worker of its own for every piece.
            'Accept-Ranges' => 'none',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // curl -I, link previews: the headers are the whole answer. A HEAD never runs a streamed
        // response's callback, so a slot taken here would never be given back.
        if ($request->isMethod('HEAD')) {
            return response('', 200, $headers);
        }

        $slot = $this->takeSlot();

        if ($slot === false) {
            return $this->unavailable(
                $request,
                'Too many downloads are running, please try again in a minute or two',
                'มีคนดาวน์โหลดพร้อมกันเต็มแล้ว',
                'Too many downloads at once',
                'ขณะนี้มีผู้ดาวน์โหลดพร้อมกันเต็มจำนวน กรุณาลองใหม่อีกครั้งในอีก 1–2 นาที',
                'Many downloads are running right now. Please try again in a minute or two.',
            );
        }

        return response()->stream(function () use ($installer, $file, $slot) {
            try {
                $installer->stream($file);
            } finally {
                $slot?->release();
            }
        }, 200, $headers);
    }

    /**
     * A place in the site-wide pool of streamed downloads, shared with the other apps' downloads
     * (ReleaseDownloadStreamer): the same lock keys and the same config/downloads.php, so all of
     * them together never hold more than `max_concurrent_streams` of the PHP-FPM workers.
     *
     * false: every place is taken. null: there is nothing to hold — the limit is switched off (0),
     * or the cache cannot lock, where a customer who can download beats a closed door.
     */
    private function takeSlot(): Lock|false|null
    {
        $slots = (int) config('downloads.max_concurrent_streams', 10);

        if ($slots <= 0) {
            return null;
        }

        // A worker that dies mid-download (PHP-FPM restarted by a deploy) never gives its place
        // back, so each place expires on its own; a longer download keeps going, just uncounted.
        $seconds = max(60, (int) config('downloads.stream_slot_seconds', 1200));

        try {
            for ($i = 0; $i < $slots; $i++) {
                $lock = Cache::lock("downloads:stream-slot:{$i}", $seconds);

                if ($lock->get()) {
                    return $lock;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('BrainX download: no download slot could be taken, streaming without one', ['error' => $e->getMessage()]);

            return null;
        }

        return false;
    }

    /**
     * 503 with Retry-After. A browser gets a page in the site's error style — the stock 503 page
     * says the whole site is down for maintenance, which this is not.
     */
    private function unavailable(Request $request, string $error, string $titleTh, string $titleEn, string $bodyTh, string $bodyEn)
    {
        $headers = ['Retry-After' => '60', 'Cache-Control' => 'no-store'];

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'error' => $error], 503, $headers);
        }

        return response()->view('errors.layout', [
            'code' => 503,
            'titleTh' => $titleTh,
            'titleEn' => $titleEn,
            'bodyTh' => $bodyTh,
            'bodyEn' => $bodyEn,
        ], 503, $headers);
    }
}
