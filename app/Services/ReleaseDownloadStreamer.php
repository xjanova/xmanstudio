<?php

namespace App\Services;

use App\Exceptions\DownloadUnavailableException;
use App\Models\GithubSetting;
use App\Models\ProductVersion;
use GuzzleHttp\Handler\StreamHandler;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ส่งไฟล์ของเวอร์ชันให้ลูกค้าจาก xman4289.com เอง — ลูกค้าไม่เห็นเลยว่าไฟล์อยู่บน GitHub
 *
 * กฎเจ้าของ (2026-09-24): แอปโหลดได้จาก xman4289.com เท่านั้น และห้ามให้ลูกค้ารู้ repo
 * redirect ไป GitHub ก็คือยื่นลิงก์ repo ให้ในแถบที่อยู่ ไฟล์จึงถูกดึงผ่านเซิร์ฟเวอร์แล้วส่งต่อทีละก้อน
 * redirect ของ GitHub (API → CDN) ตามกันบนเซิร์ฟเวอร์ และไม่มี header ของต้นทางติดออกไปสักตัว
 *
 * ⚠️ production เป็น Apache + PHP-FPM ไม่มีอะไรพักไฟล์ไว้ให้ — worker หนึ่งตัวถูกจองตลอดเวลาที่ลูกค้าโหลด
 *    (CluadeX ~480 MB เน็ตช้าเป็นสิบนาที) จึงจำกัดจำนวนที่ส่งพร้อมกัน (config/downloads.php) เต็มแล้วตอบ 503
 * ⚠️ memory_limit 128M — อ่านทีละก้อนแล้ว flush ออกทันที ห้ามเก็บทั้งไฟล์ไว้ในหน่วยความจำ
 * ⚠️ HEAD (curl -I, link preview) ไม่เคยรัน callback ของ StreamedResponse — Symfony ตั้งว่า "ส่งแล้ว" ไว้ก่อน
 *    ถ้าจองช่องหรือเปิดต้นทางไว้ตอน HEAD จะไม่มีใครปล่อย HEAD จึงตอบจากข้อมูลใน DB อย่างเดียว
 */
class ReleaseDownloadStreamer
{
    /** อ่านจากต้นทางทีละไม่เกินเท่านี้ (stream ของ PHP คืนให้ทีละ ~8 KB อยู่แล้ว) */
    private const CHUNK_BYTES = 65536;

    /** รอต้นทางตอบ header ได้นานสุดต่อหนึ่งทอด */
    private const CONNECT_SECONDS = 20;

    /** ระหว่างส่งเนื้อไฟล์ ต้นทางเงียบนานเท่านี้ = ตาย (ต่อการอ่านหนึ่งครั้ง ไม่ใช่ทั้งไฟล์) */
    private const READ_TIMEOUT_SECONDS = 60;

    /** ช่องเต็ม บอกลูกค้า/แอปให้ลองใหม่หลังจากนี้ */
    private const RETRY_AFTER_SECONDS = 60;

    public function __construct(private GithubReleaseService $github) {}

    /**
     * คำตอบที่ส่งไฟล์ของเวอร์ชันนี้ให้ลูกค้า
     *
     * ไฟล์บน GitHub → stream ผ่านเซิร์ฟเวอร์ · ลิงก์ที่ admin ใส่เองซึ่งไม่ใช่ GitHub (mirror ฯลฯ) → redirect ตามเดิม
     *
     * @param  ?string  $fallbackName  ชื่อไฟล์เมื่อไม่รู้ชื่อจริง (เวอร์ชันสร้างมือที่มีแค่ลิงก์ API) — นามสกุลกำหนด Content-Type
     *
     * @throws DownloadUnavailableException ไม่มีไฟล์ / ดึงไม่ได้ / ช่องส่งเต็ม / สินค้ายังเป็น Coming Soon
     */
    public function respond(Request $request, ProductVersion $version, ?GithubSetting $setting, ?string $fallbackName = null): Response
    {
        // Coming Soon = ไม่มีไฟล์ให้ใคร ไม่ว่าจะมาจากปุ่ม ลิงก์ API หรือตัวอัปเดตในแอป (กฎเจ้าของ 2026-10-12)
        // ด่านสุดท้ายที่ทุกทางผ่าน — controller แต่ละตัวกันไว้ก่อนแล้ว ที่นี่กันทางที่ลืม
        if ($version->product?->isComingSoon()) {
            throw DownloadUnavailableException::comingSoon();
        }

        $sources = $this->sources($version, $setting);

        if ($sources === []) {
            $external = $this->externalUrl($version);

            if ($external === null) {
                throw DownloadUnavailableException::missing();
            }

            return redirect()->away($external);
        }

        $filename = $this->filename($version, $sources, $fallbackName);
        $size = (int) $version->file_size;
        $etag = $this->etag($version);

        if ($request->isMethod('HEAD')) {
            return response('', 200, $this->headers($filename, $version->file_size, $size > 0, $etag));
        }

        $range = $this->requestedRange($request, $size, $etag);

        if ($range === false) {
            // Nothing of the file starts there — the client's partial copy is not of this file, or is complete
            return response('', 416, [
                'Content-Range' => "bytes */{$size}",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        $context = ['product_id' => $version->product_id, 'version' => $version->version];
        $slot = $this->takeSlot();

        try {
            $upstream = $this->open($sources, $context, $range);

            $partial = $upstream->status() === 206;

            if ($range !== null && ! ($partial ? $this->rangeMatches($upstream, $range, $size) : $this->contentLength($upstream) === $size)) {
                // GitHub's file is not the size the database recorded: a resumed piece would be glued onto a
                // different file. The whole file goes out instead — the client starts over and the sha256 decides.
                Log::warning('download: the upstream file does not fit the recorded size, sending the whole file', $context + [
                    'content_range' => $upstream->header('Content-Range'),
                    'content_length' => $upstream->header('Content-Length'),
                    'recorded' => $size,
                ]);
                $range = null;

                if ($upstream->status() !== 200) {
                    $upstream->toPsrResponse()->getBody()->close();
                    $upstream = $this->open($sources, $context);
                }
            }
        } catch (\Throwable $e) {
            $slot?->release();

            throw $e;
        }

        if ($range !== null) {
            [$start, $end] = $range;
            $length = $end - $start + 1;
            // GitHub answered 200 to the Range: the bytes before the range are read and dropped here, so the
            // client still gets only what it is missing over its own (slow, breaking) line
            $skip = $upstream->status() === 206 ? 0 : $start;
            $body = $upstream->toPsrResponse()->getBody();

            return new StreamedResponse(
                fn () => $this->pipe($body, $slot, $length, $context, $skip),
                206,
                $this->headers($filename, $length, true, $etag) + ['Content-Range' => "bytes {$start}-{$end}/{$size}"],
            );
        }

        $length = $this->contentLength($upstream);

        if ($length !== null && $version->file_size && $length !== (int) $version->file_size) {
            // แอปเทียบ Content-Length กับ file_size ที่ update/check ประกาศไว้ ไม่ตรงแอปจะไม่ติดตั้ง
            // ส่งขนาดจริงไปดีกว่าโกหก ไฟล์เสียต้องไม่ผ่าน · รอบ sync ถัดไปแก้ file_size ใน DB ให้ตรงเอง
            Log::warning('download: ขนาดไฟล์ต้นทางไม่ตรงกับที่บันทึกไว้', [
                'product_id' => $version->product_id,
                'version' => $version->version,
                'upstream' => $length,
                'recorded' => $version->file_size,
            ]);
        }

        $body = $upstream->toPsrResponse()->getBody();
        // A Range that was ignored (several ranges, a stale If-Range, nonsense) keeps "none": Apache's own
        // byterange filter would otherwise cut this 200 itself
        $ranges = $size > 0 && ! $request->headers->has('Range');

        return new StreamedResponse(
            fn () => $this->pipe($body, $slot, $length, $context),
            200,
            $this->headers($filename, $length, $ranges, $etag),
        );
    }

    /**
     * The one byte range the client asked for: null = send the whole file, false = 416
     *
     * Only "bytes=N-", "bytes=N-M" and "bytes=-S" — anything else, several ranges, or an If-Range that is not
     * this file's ETag is ignored and the whole file goes out (RFC 9110 §14.2 lets a server ignore Range).
     * A file whose size is unknown cannot say where a range ends, so it never resumes.
     *
     * @return array{0: int, 1: int}|false|null
     */
    private function requestedRange(Request $request, int $size, ?string $etag): array|false|null
    {
        $header = $request->headers->get('Range');

        if ($header === null || $size <= 0
            || preg_match('/^\s*bytes\s*=\s*(\d*)\s*-\s*(\d*)\s*$/i', $header, $m) !== 1
            || ($m[1] === '' && $m[2] === '')) {
            return null;
        }

        $ifRange = $request->headers->get('If-Range');

        if ($ifRange !== null && ($etag === null || trim($ifRange) !== $etag)) {
            return null;
        }

        if ($m[1] === '') {
            // the last S bytes
            $suffix = (int) $m[2];

            return $suffix > 0 ? [max(0, $size - $suffix), $size - 1] : false;
        }

        $start = (int) $m[1];

        if ($m[2] !== '' && (int) $m[2] < $start) {
            return null;
        }

        return $start < $size ? [$start, $m[2] === '' ? $size - 1 : min((int) $m[2], $size - 1)] : false;
    }

    /** GitHub's 206 covers exactly the asked bytes of a file of the recorded size */
    private function rangeMatches(ClientResponse $upstream, array $range, int $size): bool
    {
        return trim((string) $upstream->header('Content-Range')) === "bytes {$range[0]}-{$range[1]}/{$size}";
    }

    /** The release's sha256 is its identity: the same bytes always, another file never */
    private function etag(ProductVersion $version): ?string
    {
        $sha = strtolower((string) $version->sha256);

        return preg_match('/^[0-9a-f]{64}$/', $sha) === 1 ? '"' . $sha . '"' : null;
    }

    /**
     * Stream one file of a GitHub release that is not a ProductVersion — the BrainX app's Velopack
     * update packages, which the app asks for by the names in its own feed (BrainXUpdateFeed).
     *
     * Same rules as respond(): the bytes pass through this server, never a redirect, nothing of
     * GitHub's reaches the response, and the download takes a place in the same site-wide pool.
     *
     * @param  string  $url  https://github.com/{owner}/{repo}/releases/download/{tag}/{file} — nothing else is fetched
     * @param  ?int  $expectedSize  what the caller was told the file weighs; the HEAD answer (GitHub is not asked)
     * @param  array<string, mixed>  $context  what the logs say the file is
     *
     * @throws DownloadUnavailableException not a release file / cannot be fetched / every place taken
     */
    public function respondWithReleaseFile(Request $request, string $url, string $filename, ?int $expectedSize, array $context = []): Response
    {
        if (! $this->isReleaseFileLink($url) || str_contains($url, '/releases/latest/')) {
            throw DownloadUnavailableException::missing();
        }

        if ($request->isMethod('HEAD')) {
            return response('', 200, $this->headers($filename, $expectedSize));
        }

        $slot = $this->takeSlot();

        try {
            $upstream = $this->open([['url' => $url, 'token' => null, 'via' => 'release-link']], $context);
        } catch (\Throwable $e) {
            $slot?->release();

            throw $e;
        }

        $length = $this->contentLength($upstream);

        if ($length !== null && $expectedSize && $length !== $expectedSize) {
            // The real size goes out: a client that checks the file (Velopack checks SHA1/SHA256) refuses
            // a wrong one either way, and a Content-Length that lies would cut it short or run over.
            Log::warning('download: the release file is not the size it was announced at', $context + [
                'upstream' => $length,
                'announced' => $expectedSize,
            ]);
        }

        // GitHub always says how big a release file is; if it ever did not, the size the caller was
        // told is promised instead, and pipe() holds the stream to it.
        $length ??= $expectedSize;
        $body = $upstream->toPsrResponse()->getBody();

        return new StreamedResponse(
            fn () => $this->pipe($body, $slot, $length, $context),
            200,
            $this->headers($filename, $length),
        );
    }

    /**
     * ที่ที่ดึงไฟล์ได้ เรียงตามลำดับที่ควรลอง
     *
     * 1. API ของ asset + token — ทางเดียวที่ repo private ใช้ได้
     * 2. ลิงก์ไฟล์ของ release (browser_download_url) — repo public ไม่กินโควตา API (ไม่มี token ได้ 60 ครั้ง/ชม.)
     *    และเป็นทางถอยเมื่อ token ตายแล้ว (GitHub ตอบ 401 แม้ repo เป็น public — cluadex เจอมาแล้ว)
     * 3. API ของ asset แบบไม่มี token — เวอร์ชันที่ sync ไว้ก่อนมีคอลัมน์ download_url
     *
     * @return list<array{url: string, token: ?string, via: string}>
     */
    private function sources(ProductVersion $version, ?GithubSetting $setting): array
    {
        $assetApi = $this->github->releaseAssetId($version) !== null ? $version->github_release_url : null;
        $releaseLink = collect([$version->download_url, $version->github_release_url])
            ->first(fn ($url) => $this->isReleaseFileLink($url));
        $token = $setting?->github_token_decrypted ?: null;

        $sources = [];

        if ($assetApi && $token) {
            $sources[] = ['url' => $assetApi, 'token' => $token, 'via' => 'asset-api-token'];
        }

        if ($releaseLink) {
            $sources[] = ['url' => $releaseLink, 'token' => null, 'via' => 'release-link'];
        } elseif ($assetApi) {
            $sources[] = ['url' => $assetApi, 'token' => null, 'via' => 'asset-api'];
        }

        return $sources;
    }

    /** https://github.com/{owner}/{repo}/releases/download/{tag}/{file} (หรือ …/releases/latest/download/{file}) */
    private function isReleaseFileLink(mixed $url): bool
    {
        return is_string($url)
            && preg_match('#^https://github\.com/[^/]+/[^/]+/releases/(?:download/[^/]+|latest/download)/[^/?\#]+$#i', $url) === 1;
    }

    /**
     * ลิงก์ที่ admin ใส่เองสำหรับเวอร์ชันที่ไม่ได้มาจาก GitHub — คืนเฉพาะลิงก์ที่ไม่ใช่ GitHub
     * (หน้า release บน GitHub ที่ไม่มีไฟล์ = ไม่มีอะไรให้โหลด ไม่ใช่ลิงก์ให้พาลูกค้าไป)
     */
    private function externalUrl(ProductVersion $version): ?string
    {
        foreach ([$version->download_url, $version->github_release_url] as $url) {
            if (is_string($url)
                && filter_var($url, FILTER_VALIDATE_URL)
                && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                && ! $this->isGithubHost($url)) {
                return $url;
            }
        }

        return null;
    }

    private function isGithubHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'github.com'
            || $host === 'githubusercontent.com'
            || str_ends_with($host, '.github.com')
            || str_ends_with($host, '.githubusercontent.com')
            || str_ends_with($host, '.github.io');
    }

    /**
     * จองที่ส่งไฟล์หนึ่งที่ — null เมื่อปิดการจำกัดไว้ หรือ cache ทำ lock ไม่ได้ (ให้ลูกค้าโหลดได้ดีกว่าปิดประตู)
     *
     * @throws DownloadUnavailableException ทุกที่เต็ม
     */
    private function takeSlot(): ?Lock
    {
        $slots = (int) config('downloads.max_concurrent_streams', 10);

        if ($slots <= 0) {
            return null;
        }

        $seconds = max(60, (int) config('downloads.stream_slot_seconds', 1200));

        try {
            for ($i = 0; $i < $slots; $i++) {
                $lock = Cache::lock("downloads:stream-slot:{$i}", $seconds);

                if ($lock->get()) {
                    return $lock;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('download: จองช่องส่งไฟล์ไม่ได้ ส่งไฟล์โดยไม่จำกัดจำนวน', ['error' => $e->getMessage()]);

            return null;
        }

        throw DownloadUnavailableException::busy(self::RETRY_AFTER_SECONDS);
    }

    /**
     * เปิดไฟล์จากที่แรกที่ใช้ได้ — ได้สถานะและ header แล้ว แต่ยังไม่ได้อ่านเนื้อไฟล์
     *
     * @param  list<array{url: string, token: ?string, via: string}>  $sources
     * @param  array<string, mixed>  $about  what the logs say the file is
     * @param  ?array{0: int, 1: int}  $range  asked of GitHub too
     *
     * @throws DownloadUnavailableException ทุกทางล้มเหลว
     */
    private function open(array $sources, array $about, ?array $range = null): ClientResponse
    {
        foreach ($sources as $source) {
            $context = $about + ['via' => $source['via']];

            try {
                $response = $this->request($source['url'], $source['token'], $range);
            } catch (\Throwable $e) {
                // ข้อความของ HTTP client อาจมีลิงก์ที่ GitHub เซ็นไว้ — ตัด query string ออกก่อนลง log
                Log::warning('download: ต่อไฟล์ต้นทางไม่ได้', $context + [
                    'error' => preg_replace('/\?\S*/', '?…', $e->getMessage()),
                ]);

                continue;
            }

            // หน้าเว็บที่ตอบ 200 (หน้า error/login) ไม่ใช่ไฟล์ — ห้ามส่งต่อให้ลูกค้าเหมือนเป็นตัวติดตั้ง
            $isPage = str_starts_with(strtolower((string) $response->header('Content-Type')), 'text/html');

            // with a range: 206, 200 (it ignored the range) or 416 (its file is shorter than recorded) — respond() decides
            if (($response->status() === 200 || ($range !== null && in_array($response->status(), [206, 416], true))) && ! $isPage) {
                return $response;
            }

            $response->toPsrResponse()->getBody()->close();

            Log::warning('download: ต้นทางไม่ส่งไฟล์ให้', $context + [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
            ]);
        }

        throw DownloadUnavailableException::unreachable();
    }

    private function request(string $url, ?string $token, ?array $range = null): ClientResponse
    {
        $headers = [
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download',
        ];

        if ($range !== null) {
            // Guzzle keeps it across the API → CDN redirect (it drops only Authorization and cookies)
            $headers['Range'] = "bytes={$range[0]}-{$range[1]}";
        }

        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return Http::withHeaders($headers)
            // stream ของ PHP ให้อ่านเนื้อไฟล์ทีละก้อนได้จริง — ถ้าปล่อยให้ Guzzle เลือก handler เอง บนเครื่องที่ปิด
            // allow_url_fopen มันจะใช้ cURL ซึ่งโหลดทั้งไฟล์ลง php://temp ก่อนคืนค่า ลูกค้ารอเป็นนาทีกว่าจะได้ byte แรก
            ->setHandler(new StreamHandler)
            // StreamHandler: timeout = รอต่อการอ่านหนึ่งครั้ง ไม่ใช่เวลาทั้งไฟล์ — ไฟล์ใหญ่ส่งนานได้ไม่โดนตัด
            ->timeout(self::CONNECT_SECONDS)
            ->withOptions([
                'stream' => true,
                'read_timeout' => self::READ_TIMEOUT_SECONDS,
                'allow_redirects' => [
                    'max' => 5,
                    'protocols' => ['https'],
                    // Guzzle ตัด Authorization ทิ้งเองเมื่อข้ามโดเมน (API → CDN) token จึงไปไม่ถึง CDN
                    // และไม่ยอมตามออกนอก GitHub — ไฟล์ของเราไม่เคยอยู่ที่อื่น
                    'on_redirect' => function ($request, $response, UriInterface $uri): void {
                        if (! $this->isGithubHost((string) $uri)) {
                            throw new \RuntimeException('Refused a redirect to ' . $uri->getHost());
                        }
                    },
                ],
            ])
            ->get($url);
    }

    private function contentLength(ClientResponse $response): ?int
    {
        $value = (string) $response->header('Content-Length');

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * ส่งเนื้อไฟล์ต่อให้ลูกค้าทีละก้อน — รันใน callback ของ StreamedResponse หลังส่ง header ไปแล้ว
     *
     * @param  int  $skip  bytes read from upstream and dropped first (GitHub sent the whole file for a range)
     */
    private function pipe(StreamInterface $body, ?Lock $slot, ?int $length, array $context, int $skip = 0): void
    {
        // ลูกค้าปิดหน้าต่างกลางทาง: ให้ลูปรู้ตัวแล้วเลิกเอง จะได้ปิดต้นทางและคืนช่อง (PHP ตัดจบเองจะข้าม finally)
        ignore_user_abort(true);

        // ไฟล์ใหญ่ + เน็ตลูกค้าช้า = หลายนาที — max_execution_time ต้องไม่ตัดกลางไฟล์
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $sent = 0;
        $emptyReads = 0;

        try {
            while ($skip > 0 && ! $body->eof()) {
                $chunk = $body->read(min(self::CHUNK_BYTES, $skip));

                if ($chunk === '') {
                    if ($body->getMetadata('timed_out') || ++$emptyReads > 3) {
                        break;
                    }

                    continue;
                }

                $emptyReads = 0;
                $skip -= strlen($chunk);
            }

            if ($skip > 0) {
                // ต้นทางขาดก่อนถึงจุดเริ่มของช่วง — ส่งต่อจากตรงนี้คือส่ง byte ผิดตำแหน่ง ไม่ส่งอะไรเลยดีกว่า
                Log::warning('download: upstream ended before the requested range started', $context + ['missing' => $skip]);

                return;
            }

            while (! $body->eof()) {
                $chunk = $body->read(self::CHUNK_BYTES);

                if ($chunk === '') {
                    // stream ของ PHP คืนค่าว่างเมื่อรอเกิน read_timeout = ต้นทางเงียบ เลิกรอ
                    if ($body->getMetadata('timed_out') || ++$emptyReads > 3) {
                        break;
                    }

                    continue;
                }

                $emptyReads = 0;

                if ($length !== null && $sent + strlen($chunk) > $length) {
                    // Apache เชื่อ Content-Length ของเรา (public_html/.htaccess) — byte ที่เกินจะถูกอ่านเป็นต้น
                    // response ถัดไปบน connection เดียวกัน จึงส่งแค่ที่ประกาศไว้แล้วเลิก
                    Log::warning('download: ต้นทางส่งเกินขนาดที่ประกาศ ตัดที่ Content-Length', $context + [
                        'expected' => $length,
                    ]);
                    $chunk = substr($chunk, 0, $length - $sent);
                }

                $sent += strlen($chunk);

                echo $chunk;

                // มี output buffer ค้างอยู่ (php.ini output_buffering) ต้องดันออกด้วย ไม่งั้นไฟล์ไปกองในหน่วยความจำ
                if (ob_get_level() > 0) {
                    @ob_flush();
                }

                flush();

                if (connection_aborted() || ($length !== null && $sent >= $length)) {
                    break;
                }
            }
        } finally {
            $body->close();
            $slot?->release();
        }

        if ($length !== null && $sent < $length && ! connection_aborted()) {
            // Content-Length บอกขนาดไว้แล้ว เบราว์เซอร์/แอปจึงรู้ว่าไฟล์ขาด ไม่เอาไฟล์เสียไปติดตั้ง
            Log::warning('download: ต้นทางหยุดส่งก่อนครบไฟล์', $context + [
                'sent' => $sent,
                'expected' => $length,
            ]);
        }
    }

    /**
     * ชื่อที่รู้จริงก่อน (ที่ sync มา → ชื่อไฟล์ในลิงก์ release) แล้วค่อยเป็นชื่อสำรองที่ controller ให้มา
     *
     * @param  list<array{url: string, token: ?string, via: string}>  $sources
     */
    private function filename(ProductVersion $version, array $sources, ?string $fallbackName = null): string
    {
        $name = $version->download_filename;

        if (! $name) {
            foreach ($sources as $source) {
                if ($source['via'] === 'release-link') {
                    $name = rawurldecode(basename((string) parse_url($source['url'], PHP_URL_PATH)));
                }
            }
        }

        $name = trim((string) preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/', '_', (string) ($name ?: $fallbackName)));

        return $name !== '' ? $name : 'download-' . $version->version;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $filename, int|string|null $length, bool $ranges = false, ?string $etag = null): array
    {
        $fallback = (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', Str::ascii($filename));

        $headers = [
            // .apk ต้องบอกชนิดให้ถูก เบราว์เซอร์บนมือถือถึงจะเสนอติดตั้ง · ที่เหลือเป็นไฟล์ให้บันทึกเฉย ๆ
            // (ตัวอัปเดตในแอป WinXTools ไม่รับ text/* หรือ json — octet-stream ผ่าน)
            'Content-Type' => str_ends_with(strtolower($filename), '.apk')
                ? 'application/vnd.android.package-archive'
                : 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
                $fallback !== '' ? $fallback : 'download',
            ),
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            // bytes: a broken download resumes with Range (respond() — a ProductVersion whose size is known)
            // none: start over — better than sending Range and getting the whole file appended
            'Accept-Ranges' => $ranges ? 'bytes' : 'none',
        ];

        if ($etag !== null) {
            $headers['ETag'] = $etag;
        }

        if ((int) $length > 0) {
            $headers['Content-Length'] = (string) (int) $length;
        }

        return $headers;
    }
}
