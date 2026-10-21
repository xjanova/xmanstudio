<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WinxDiskBenchmark;
use App\Services\WinxDiskBenchmarkStats;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

/**
 * Anonymous disk speed-test results from WinXTools, and what is normal for a drive model.
 *
 * After a speed test the app uploads the result, and asks what other PCs measured on the same
 * model so its user can tell a slow drive from a slow model. Nothing personal comes in and
 * nothing personal is kept: install_id is a random id the app makes per installation (not its
 * license key, its machine id or a user), the address only feeds the hourly rate limiter, and
 * a field the rules below do not name is never read.
 *
 * The desktop client is written against this contract:
 *   POST .../disk-benchmarks         {"success": true, "data": {"id": 123}}
 *   GET  .../disk-benchmarks/stats   {"success": true, "data": {"model", "samples", "devices", "stats"}}
 *   errors                           {"success": false, "error_code": "invalid" | "rate_limited", "message"}
 */
class WinxDiskBenchmarkController extends Controller
{
    /** Uploads one installation may make in any 24 hours — counted from stored rows, not a cache. */
    private const PER_INSTALL_PER_DAY = 30;

    private const KINDS = ['hdd', 'sata-ssd', 'nvme', 'usb-hdd', 'usb-ssd', 'usb', 'unknown'];

    private const BUSES = [
        'Unknown', 'Sata', 'Nvme', 'Usb', 'Raid', 'Sas', 'Scsi', 'SdCard', 'Virtual', 'StorageSpaces', 'Other',
    ];

    private const MEDIA = ['Hdd', 'Ssd', 'Unknown'];

    private const LABELS = ['', 'before', 'after'];

    /** MB/s. The read tests always run. */
    private const READS = ['seq1m_q8_read', 'seq1m_q1_read', 'rnd4k_q32_read', 'rnd4k_q1_read'];

    /** MB/s. The write tests can be skipped, so these may be null. */
    private const WRITES = ['seq1m_q8_write', 'seq1m_q1_write', 'rnd4k_q32_write', 'rnd4k_q1_write'];

    private const IOPS = ['rnd4k_q32_read_iops', 'rnd4k_q1_read_iops'];

    /** Measured numbers two uploads must share to be one result sent twice. */
    private const MEASURED = [
        ...self::READS,
        ...self::WRITES,
        ...self::IOPS,
        'access_ms',
        'score_total',
        'score_read',
        'score_write',
    ];

    public function __construct(private readonly WinxDiskBenchmarkStats $modelStats) {}

    /**
     * POST /api/v1/product/winx-tools/disk-benchmarks — store one finished speed test.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->uploadRules());

        if ($validator->fails()) {
            return $this->invalid($validator->errors());
        }

        $input = $validator->validated();
        $modelKey = WinxDiskBenchmark::keyFor($input['drive']['model']);

        if ($modelKey === '') {
            return $this->invalid(new MessageBag([
                'drive.model' => ['The drive.model field must contain a letter or a digit.'],
            ]));
        }

        $row = $this->row($input, $modelKey);

        $lastDay = WinxDiskBenchmark::where('install_id', $row['install_id'])
            ->where('created_at', '>', now()->subDay())
            ->orderBy('id')
            ->get();

        // The same result again — a retry after the answer got lost, a second click — is the row
        // already stored, not one more sample of this drive.
        $stored = $lastDay->first(fn (WinxDiskBenchmark $earlier) => $this->isSameResult($earlier, $row));

        if ($stored !== null) {
            return $this->storedAs($stored);
        }

        if ($lastDay->count() >= self::PER_INSTALL_PER_DAY) {
            // A slot frees up when the oldest upload of the last 24 hours turns a day old
            $retryAfter = $lastDay->first()->created_at->getTimestamp() + 86400 - now()->getTimestamp();

            return response()->json([
                'success' => false,
                'error_code' => 'rate_limited',
                'message' => 'เครื่องนี้ส่งผลทดสอบครบ ' . self::PER_INSTALL_PER_DAY
                    . ' ครั้งใน 24 ชั่วโมงแล้ว — ส่งใหม่ได้ภายหลัง',
            ], 429, ['Retry-After' => max(1, $retryAfter)]);
        }

        return $this->storedAs(WinxDiskBenchmark::create($row));
    }

    /**
     * GET /api/v1/product/winx-tools/disk-benchmarks/stats?model=…&capacity_gb=… — what other PCs
     * measured on this model (optionally within ±10% of this capacity). See WinxDiskBenchmarkStats.
     */
    public function stats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'model' => self::modelRules(),
            'capacity_gb' => self::number('nullable', 1, 200000, integer: true),
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors());
        }

        $query = $validator->validated();
        $model = trim($query['model']);
        $capacity = isset($query['capacity_gb']) ? (int) $query['capacity_gb'] : null;
        $summary = $this->modelStats->forModel(WinxDiskBenchmark::keyFor($model), $capacity);

        return response()->json([
            'success' => true,
            'data' => ['model' => $model] + $summary,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadRules(): array
    {
        $rules = [
            'install_id' => ['required', 'string', 'uuid'],
            'app_version' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z.+-]+$/D'],
            'os_build' => self::number('nullable', 0, 1000000, integer: true),

            'drive' => ['required', 'array'],
            'drive.model' => self::modelRules(),
            'drive.kind' => ['required', 'string', Rule::in(self::KINDS)],
            'drive.bus' => ['required', 'string', Rule::in(self::BUSES)],
            'drive.media' => ['required', 'string', Rule::in(self::MEDIA)],
            'drive.capacity_gb' => self::number('required', 1, 200000, integer: true),
            'drive.firmware' => ['nullable', 'string', 'max:32', 'regex:/^[\x20-\x7E]+$/D'],
            'drive.rpm' => self::number('nullable', 0, 100000, integer: true),

            'test' => ['required', 'array'],
            'test.file_mb' => self::number('required', 64, 65536, integer: true),
            'test.seconds_per_test' => self::number('required', 1, 60, integer: true),
            // "" reaches the rules as null (ConvertEmptyStringsToNull) and is stored as ''
            'test.label' => ['nullable', 'string', Rule::in(self::LABELS)],
            'test.free_pct' => self::number('nullable', 0, 100),

            'results' => ['required', 'array'],
            'results.access_ms' => self::number('nullable', 0, 1000),

            // Either null, or all of it
            'surface' => ['nullable', 'array'],
            'surface.min' => self::number('required_with:surface', 0, 100000),
            'surface.avg' => self::number('required_with:surface', 0, 100000),
            'surface.max' => self::number('required_with:surface', 0, 100000),
            // Counted before a single point is looked at: no per-point wildcard rule for a
            // million-element array to expand into
            'surface.points' => [
                'bail',
                'required_with:surface',
                'array',
                'list',
                'size:' . WinxDiskBenchmarkStats::SURFACE_POINTS,
                self::eachPointInRange(...),
            ],

            'score' => ['required', 'array'],
            'score.total' => self::number('required', 0, 1000000, integer: true),
            'score.read' => self::number('required', 0, 1000000, integer: true),
            'score.write' => self::number('nullable', 0, 1000000, integer: true),
        ];

        foreach (self::READS as $column) {
            $rules['results.' . $column] = self::number('required', 0, 100000);
        }

        foreach (self::WRITES as $column) {
            $rules['results.' . $column] = self::number('nullable', 0, 100000);
        }

        foreach (self::IOPS as $column) {
            $rules['results.' . $column] = self::number('nullable', 0, 10000000);
        }

        return $rules;
    }

    /**
     * 1–80 characters: ASCII letters, digits, space and - _ . / ( ) +
     *
     * @return list<string>
     */
    private static function modelRules(): array
    {
        return ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9 ._\/()+-]+$/D'];
    }

    /**
     * A number from $min to $max. Checked in order, stopping at the first failure — which matters:
     * json_decode turns 1e400 into INF, and Laravel's between rule throws on INF (a 500 and an
     * alert to the owner instead of a 422). numeric also turns away true, which integer alone
     * accepts as 1.
     *
     * @return list<mixed>
     */
    private static function number(string $presence, int $min, int $max, bool $integer = false): array
    {
        return array_values(array_filter([
            'bail',
            $presence,
            'numeric',
            $integer ? 'integer' : null,
            static function (string $attribute, mixed $value, Closure $fail): void {
                if (is_float($value) && ! is_finite($value)) {
                    $fail('The :attribute field must be a finite number.');
                }
            },
            'between:' . $min . ',' . $max,
        ]));
    }

    private static function eachPointInRange(string $attribute, mixed $points, Closure $fail): void
    {
        foreach ($points as $point) {
            $value = is_numeric($point) ? (float) $point : NAN;

            if (! is_finite($value) || $value < 0 || $value > 100000) {
                $fail('Every value in :attribute must be a number from 0 to 100000.');

                return;
            }
        }
    }

    /**
     * The row to store, built field by field from validated input: nothing the rules do not
     * name — an address, an account, a serial number — can reach the table.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function row(array $input, string $modelKey): array
    {
        $drive = $input['drive'];
        $test = $input['test'];
        $results = $input['results'];
        $score = $input['score'];
        $surface = $input['surface'] ?? null;
        $hasSurface = is_array($surface) && isset($surface['points']);

        $row = [
            // One installation, however the client cased its id
            'install_id' => strtolower($input['install_id']),
            'app_version' => $input['app_version'],
            'os_build' => self::whole($input['os_build'] ?? null),
            'model' => trim($drive['model']),
            'model_key' => $modelKey,
            'kind' => $drive['kind'],
            'bus' => $drive['bus'],
            'media' => $drive['media'],
            'capacity_gb' => (int) $drive['capacity_gb'],
            'firmware' => isset($drive['firmware']) ? trim($drive['firmware']) : null,
            'rpm' => self::whole($drive['rpm'] ?? null),
            'file_mb' => (int) $test['file_mb'],
            'seconds_per_test' => (int) $test['seconds_per_test'],
            'label' => $test['label'] ?? '',
            'free_pct' => self::decimal($test['free_pct'] ?? null, 2),
            'access_ms' => self::decimal($results['access_ms'] ?? null, 3),
            'surface_min' => $hasSurface ? self::decimal($surface['min'], 2) : null,
            'surface_avg' => $hasSurface ? self::decimal($surface['avg'], 2) : null,
            'surface_max' => $hasSurface ? self::decimal($surface['max'], 2) : null,
            'surface_points' => $hasSurface
                ? array_map(fn ($point) => round((float) $point, 2), $surface['points'])
                : null,
            'score_total' => (int) $score['total'],
            'score_read' => (int) $score['read'],
            'score_write' => self::whole($score['write'] ?? null),
        ];

        foreach ([...self::READS, ...self::WRITES] as $column) {
            $row[$column] = self::decimal($results[$column] ?? null, 2);
        }

        foreach (self::IOPS as $column) {
            $row[$column] = self::whole($results[$column] ?? null);
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $upload
     */
    private function isSameResult(WinxDiskBenchmark $earlier, array $upload): bool
    {
        if ($earlier->model_key !== $upload['model_key'] || $earlier->label !== $upload['label']) {
            return false;
        }

        foreach (self::MEASURED as $column) {
            if (self::fixed($earlier->{$column}) !== self::fixed($upload[$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * A measured number as text with a fixed number of decimals: what the decimal columns hold
     * comes back as a string on MySQL and a float on SQLite, and floats are never compared with ==.
     */
    private static function fixed(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 3, '.', '');
    }

    private static function decimal(mixed $value, int $places): ?float
    {
        return $value === null ? null : round((float) $value, $places);
    }

    private static function whole(mixed $value): ?int
    {
        return $value === null ? null : (int) round((float) $value);
    }

    private function storedAs(WinxDiskBenchmark $benchmark): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['id' => $benchmark->id],
        ]);
    }

    private function invalid(MessageBag $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error_code' => 'invalid',
            'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง',
            'errors' => $errors->toArray(),
        ], 422);
    }
}
