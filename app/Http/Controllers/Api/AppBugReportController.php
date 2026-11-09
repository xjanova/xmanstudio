<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\BugReportAttachment;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Support\BugReports\SecretScrubber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * "แจ้งปัญหา" from a desktop app — POST /api/v1/product/{productSlug}/bug-reports.
 *
 * The user writes what went wrong in their own words; the app attaches what it collected (recent
 * debug log, version, OS, a settings summary, recent errors, optionally a screenshot) after the
 * user saw it and unticked what they did not want sent. Stored in bug_reports with channel 'app',
 * reviewed on /admin/bug-reports. Same envelope as the product licence API:
 * {success, message, error_code, data}; the app branches on error_code, the message is Thai.
 *
 * - Idempotent on (product, client_report_id): the app's offline queue and a double click resend
 *   the same id and get the same ticket, never a second row.
 * - Linked to an account only through a licence key of THIS product (its user, or its order's
 *   user). A typed e-mail is kept as a contact address and links nothing.
 * - Size: the whole body is refused above MAX_BODY_BYTES before anything is parsed further; each
 *   part has its own cap below that.
 * - Rate: the app-bug-reports limiter (AppServiceProvider), per address and per machine.
 */
class AppBugReportController extends Controller
{
    /** The whole request. A screenshot (~1.5 MB as base64) plus a log excerpt fits well under it. */
    public const MAX_BODY_BYTES = 3 * 1024 * 1024;

    public const MAX_MESSAGE_CHARS = 5000;

    public const MAX_LOG_CHARS = 400_000;

    /** The diagnostics object, as JSON. */
    public const MAX_DIAGNOSTICS_BYTES = 256 * 1024;

    /** The decoded screenshot. */
    public const MAX_SCREENSHOT_BYTES = 1536 * 1024;

    /** What the app may file a report under; the admin list filters on these. */
    public const CATEGORIES = ['bug', 'crash', 'performance', 'connection', 'feature_request', 'other'];

    public function store(Request $request, string $productSlug): JsonResponse
    {
        $length = (int) $request->header('Content-Length', 0);
        if ($length > self::MAX_BODY_BYTES || strlen((string) $request->getContent()) > self::MAX_BODY_BYTES) {
            return $this->fail('PAYLOAD_TOO_LARGE', 'รายงานใหญ่เกินไป ลองเอาภาพหน้าจอหรือล็อกออกแล้วส่งใหม่', 413);
        }

        $product = Product::bySlugOrAlias($productSlug)->where('requires_license', true)->first();
        if (! $product) {
            return $this->fail('PRODUCT_NOT_FOUND', 'เซิร์ฟเวอร์ไม่รู้จักโปรแกรมนี้', 404);
        }

        $validator = Validator::make($request->all(), [
            'client_report_id' => ['required', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'machine_id' => 'required|string|min:16|max:64',
            'message' => 'required|string|max:' . self::MAX_MESSAGE_CHARS,
            'title' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:' . implode(',', self::CATEGORIES),
            'contact_email' => 'nullable|email|max:255',
            'license_key' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:150',
            'diagnostics' => 'nullable|array',
            'log_excerpt' => 'nullable|string|max:' . self::MAX_LOG_CHARS,
            'included' => 'nullable|array|max:20',
            'included.*' => 'string|max:40',
            'screenshot' => 'nullable|string|max:' . (int) ceil(self::MAX_SCREENSHOT_BYTES * 4 / 3 + 8),
        ]);
        if ($validator->fails()) {
            return $this->fail('VALIDATION_FAILED', 'ข้อมูลที่ส่งมาไม่ครบหรือไม่ถูกต้อง', 422, ['errors' => $validator->errors()->toArray()]);
        }

        $message = trim((string) $request->input('message'));
        if ($message === '') {
            return $this->fail('VALIDATION_FAILED', 'เขียนเล่าปัญหาสั้นๆ ก่อนส่ง', 422, ['errors' => ['message' => ['required']]]);
        }

        $diagnostics = $request->input('diagnostics');
        if (is_array($diagnostics) && strlen((string) json_encode($diagnostics)) > self::MAX_DIAGNOSTICS_BYTES) {
            return $this->fail('PAYLOAD_TOO_LARGE', 'ข้อมูลวินิจฉัยใหญ่เกินไป', 413);
        }

        $screenshot = null;
        if ($request->filled('screenshot')) {
            $screenshot = self::decodeScreenshot((string) $request->input('screenshot'));
            if ($screenshot === null) {
                return $this->fail('INVALID_SCREENSHOT', 'ภาพหน้าจอเปิดไม่ได้ ส่งได้เฉพาะ PNG หรือ JPEG ไม่เกิน 1.5 MB', 422);
            }
        }

        $clientId = (string) $request->input('client_report_id');
        if ($existing = $this->existing($product, $clientId)) {
            return $this->accepted($existing, duplicate: true);
        }

        $license = $this->license($product, $request->input('license_key'));

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            $title = Str::limit(preg_replace('/\s+/u', ' ', $message), 120, '…');
        }

        $attributes = [
            'channel' => BugReport::CHANNEL_APP,
            'client_report_id' => $clientId,
            'product_id' => $product->id,
            'product_name' => Str::limit($product->name, 50, ''),
            'product_version' => self::short($request->input('app_version'), 20),
            'app_version' => self::short($request->input('app_version'), 50),
            'os_version' => self::short($request->input('os_version'), 150),
            'report_type' => $request->input('category') ?: 'bug',
            'title' => Str::limit(SecretScrubber::text($title), 250, '…'),
            'description' => SecretScrubber::text($message),
            'log_excerpt' => SecretScrubber::text($request->input('log_excerpt')),
            'additional_info' => is_array($diagnostics) ? SecretScrubber::value($diagnostics) : null,
            'metadata' => [
                'source' => 'app',
                'included' => array_values((array) $request->input('included', [])),
                'has_screenshot' => $screenshot !== null,
                'license_masked' => $license ? self::maskKey($license->license_key) : null,
                'ip' => $request->ip(),
            ],
            'device_id' => (string) $request->input('machine_id'),
            'user_email' => $request->input('contact_email'),
            'user_id' => $license ? ($license->user_id ?? $license->order?->user_id) : null,
            'license_key_id' => $license?->id,
            'priority' => $request->input('category') === 'crash' ? 'high' : 'medium',
            'severity' => $request->input('category') === 'crash' ? 'major' : 'moderate',
        ];

        try {
            $report = $this->create($attributes);
        } catch (QueryException $e) {
            // the same client id raced in from a second request: hand back the row that won
            if ($existing = $this->existing($product, $clientId)) {
                return $this->accepted($existing, duplicate: true);
            }
            Log::error('App bug report could not be stored', ['product' => $product->id, 'error' => $e->getMessage()]);

            return $this->fail('SERVER_ERROR', 'เซิร์ฟเวอร์บันทึกรายงานไม่ได้ชั่วคราว แอปจะลองส่งใหม่ให้เอง', 500);
        }

        if ($screenshot !== null) {
            $this->storeScreenshot($report, $screenshot);
        }

        Log::info('App bug report received', [
            'ticket' => $report->ticket, 'product' => $product->id, 'type' => $report->report_type,
            'linked' => $report->user_id !== null,
        ]);

        return $this->accepted($report, duplicate: false);
    }

    /** Insert with a fresh ticket; a ticket collision (5 letters a day) just draws again. */
    private function create(array $attributes): BugReport
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(fn () => BugReport::create($attributes + ['ticket' => BugReport::newTicket()]));
            } catch (QueryException $e) {
                $ticketClash = str_contains(strtolower($e->getMessage()), 'ticket');
                if (! $ticketClash || $attempt >= 5) {
                    throw $e;
                }
            }
        }
    }

    private function existing(Product $product, string $clientId): ?BugReport
    {
        return BugReport::where('product_id', $product->id)->where('client_report_id', $clientId)->first();
    }

    /** A key of this product, as typed by the app. Anything else links nothing. */
    private function license(Product $product, mixed $key): ?LicenseKey
    {
        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        return LicenseKey::with('order')
            ->where('product_id', $product->id)
            ->whereIn('license_key', array_unique([trim($key), strtoupper(trim($key))]))
            ->first();
    }

    /**
     * The base64 (optionally a data: URL) of a PNG or JPEG within the size cap, or null. The bytes
     * are checked for what they really are; the name the app gave is not trusted.
     *
     * @return array{bytes: string, ext: string}|null
     */
    public static function decodeScreenshot(string $data): ?array
    {
        if (preg_match('/^data:image\/[a-z]+;base64,/i', $data, $m)) {
            $data = substr($data, strlen($m[0]));
        }
        $bytes = base64_decode($data, true);
        if ($bytes === false || $bytes === '' || strlen($bytes) > self::MAX_SCREENSHOT_BYTES) {
            return null;
        }
        $info = @getimagesizefromstring($bytes);
        $ext = match ($info[2] ?? null) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_JPEG => 'jpg',
            default => null,
        };

        return $ext ? ['bytes' => $bytes, 'ext' => $ext] : null;
    }

    private function storeScreenshot(BugReport $report, array $shot): void
    {
        $path = "bug-reports/{$report->id}/screenshot.{$shot['ext']}";
        try {
            Storage::disk('local')->put($path, $shot['bytes']);
            BugReportAttachment::create([
                'bug_report_id' => $report->id,
                'filename' => "screenshot.{$shot['ext']}",
                'file_path' => $path,
                'file_type' => $shot['ext'],
                'file_size' => strlen($shot['bytes']),
                'description' => 'ภาพหน้าจอจากแอป',
            ]);
        } catch (\Throwable $e) {
            // the report itself is in; a lost screenshot is noted rather than failing the user's send
            Log::warning('App bug report screenshot not stored', ['ticket' => $report->ticket, 'error' => $e->getMessage()]);
        }
    }

    public static function maskKey(string $key): string
    {
        return strlen($key) <= 8 ? '••••' : substr($key, 0, 4) . '…' . substr($key, -4);
    }

    private static function short(mixed $value, int $max): ?string
    {
        return is_string($value) && trim($value) !== '' ? Str::limit(trim($value), $max, '') : null;
    }

    private function accepted(BugReport $report, bool $duplicate): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'ได้รับรายงานแล้ว ขอบคุณที่แจ้งเข้ามา',
            'error_code' => null,
            'data' => [
                'ticket' => $report->ticket,
                'status' => $report->status,
                'received_at' => $report->created_at?->utc()->format('Y-m-d\TH:i:s\Z'),
                'duplicate' => $duplicate,
            ],
        ], $duplicate ? 200 : 201);
    }

    private function fail(string $code, string $message, int $status, ?array $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'data' => $data,
        ], $status);
    }
}
