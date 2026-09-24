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
     * @throws DownloadUnavailableException ไม่มีไฟล์ / ดึงไม่ได้ / ช่องส่งเต็ม
     */
    public function respond(Request $request, ProductVersion $version, ?GithubSetting $setting): Response
    {
        $sources = $this->sources($version, $setting);

        if ($sources === []) {
            $external = $this->externalUrl($version);

            if ($external === null) {
                throw DownloadUnavailableException::missing();
            }

            return redirect()->away($external);
        }

        $filename = $this->filename($version, $sources);

        if ($request->isMethod('HEAD')) {
            return response('', 200, $this->headers($filename, $version->file_size));
        }

        $slot = $this->takeSlot();

        try {
            $upstream = $this->open($sources, $version);
        } catch (\Throwable $e) {
            $slot?->release();

            throw $e;
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

        return new StreamedResponse(
            fn () => $this->pipe($body, $slot, $length, $version),
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
     *
     * @throws DownloadUnavailableException ทุกทางล้มเหลว
     */
    private function open(array $sources, ProductVersion $version): ClientResponse
    {
        foreach ($sources as $source) {
            $context = ['product_id' => $version->product_id, 'version' => $version->version, 'via' => $source['via']];

            try {
                $response = $this->request($source['url'], $source['token']);
            } catch (\Throwable $e) {
                // ข้อความของ HTTP client อาจมีลิงก์ที่ GitHub เซ็นไว้ — ตัด query string ออกก่อนลง log
                Log::warning('download: ต่อไฟล์ต้นทางไม่ได้', $context + [
                    'error' => preg_replace('/\?\S*/', '?…', $e->getMessage()),
                ]);

                continue;
            }

            // หน้าเว็บที่ตอบ 200 (หน้า error/login) ไม่ใช่ไฟล์ — ห้ามส่งต่อให้ลูกค้าเหมือนเป็นตัวติดตั้ง
            $isPage = str_starts_with(strtolower((string) $response->header('Content-Type')), 'text/html');

            if ($response->status() === 200 && ! $isPage) {
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

    private function request(string $url, ?string $token): ClientResponse
    {
        $headers = [
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download',
        ];

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
     */
    private function pipe(StreamInterface $body, ?Lock $slot, ?int $length, ProductVersion $version): void
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
                    Log::warning('download: ต้นทางส่งเกินขนาดที่ประกาศ ตัดที่ Content-Length', [
                        'product_id' => $version->product_id,
                        'version' => $version->version,
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
            Log::warning('download: ต้นทางหยุดส่งก่อนครบไฟล์', [
                'product_id' => $version->product_id,
                'version' => $version->version,
                'sent' => $sent,
                'expected' => $length,
            ]);
        }
    }

    /**
     * @param  list<array{url: string, token: ?string, via: string}>  $sources
     */
    private function filename(ProductVersion $version, array $sources): string
    {
        $name = $version->download_filename;

        if (! $name) {
            foreach ($sources as $source) {
                if ($source['via'] === 'release-link') {
                    $name = rawurldecode(basename((string) parse_url($source['url'], PHP_URL_PATH)));
                }
            }
        }

        $name = trim((string) preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/', '_', (string) $name));

        return $name !== '' ? $name : 'download-' . $version->version;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $filename, int|string|null $length): array
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
            // ยังโหลดต่อจากที่ค้างไม่ได้ — บอกตรง ๆ ให้เริ่มใหม่ ดีกว่าส่ง Range มาแล้วได้ไฟล์ทั้งก้อนต่อท้าย
            'Accept-Ranges' => 'none',
        ];

        if ((int) $length > 0) {
            $headers['Content-Length'] = (string) (int) $length;
        }

        return $headers;
    }
}
