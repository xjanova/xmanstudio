<?php

namespace Tests\Feature;

use App\Models\WinxDiskBenchmark;
use App\Services\WinxDiskBenchmarkStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * WinXTools uploads each disk speed test anonymously and asks what is normal for its drive model:
 *
 *   POST /api/v1/product/winx-tools/disk-benchmarks        — one result; nothing personal is kept
 *   GET  /api/v1/product/winx-tools/disk-benchmarks/stats  — quartiles, never fewer than three PCs
 *
 * The desktop client is written against this contract, so the shapes are pinned here too.
 */
class WinXToolsDiskBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/product/winx-tools/disk-benchmarks';

    private const STATS_URL = '/api/v1/product/winx-tools/disk-benchmarks/stats';

    private const INSTALL = '8b0c6a9e-3f1d-4c2b-9a7e-5d4f3c2b1a09';

    private const MODEL = 'WD_BLACK SN770 1TB';

    protected function setUp(): void
    {
        parent::setUp();

        // Answered as production answers: with debug off an API exception becomes a bare 500
        config(['app.debug' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Uploads ─────────────────────────────────────────────────────────

    public function test_an_upload_is_stored_with_its_model_key_and_nothing_personal(): void
    {
        $response = $this->postJson(self::URL, $this->upload([
            'drive.model' => '  ' . self::MODEL . ' ',
            // however the app cased its id, it is one installation
            'install_id' => strtoupper(self::INSTALL),
            // keys the contract does not name are dropped, whatever they hold
            'ip' => '198.51.100.20',
            'user_id' => 7,
            'drive.serial' => 'WD-WX12A3456789',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id']]);
        $this->assertIsInt($response->json('data.id'));

        $row = WinxDiskBenchmark::findOrFail($response->json('data.id'));

        $this->assertSame(self::MODEL, $row->model);
        $this->assertSame('WDBLACKSN7701TB', $row->model_key);
        $this->assertSame(self::INSTALL, $row->install_id);
        $this->assertSame('', $row->label);
        $this->assertSame(1000, $row->capacity_gb);
        $this->assertSame('731030WD', $row->firmware);
        $this->assertSame(26300, $row->os_build);
        $this->assertSame('0.31', $row->free_pct);
        $this->assertSame('3487.80', $row->seq1m_q8_read);
        $this->assertSame('113.00', $row->rnd4k_q1_write);
        $this->assertSame(240317, $row->rnd4k_q32_read_iops);
        $this->assertNull($row->access_ms);
        $this->assertSame('1800.00', $row->surface_avg);
        $this->assertEquals(self::points(1900), $row->surface_points);
        $this->assertSame([4256, 2358, 1898], [$row->score_total, $row->score_read, $row->score_write]);

        // there is no column an address, an account or a serial number could have gone to
        $columns = Schema::getColumnListing('winx_disk_benchmarks');

        foreach (['ip', 'ip_address', 'user_id', 'machine_id', 'serial'] as $personal) {
            $this->assertNotContains($personal, $columns);
        }
    }

    public function test_write_results_may_be_left_out_when_the_write_tests_were_skipped(): void
    {
        $upload = $this->upload([
            'results.seq1m_q8_write' => null,
            'results.seq1m_q1_write' => null,
            'score.write' => null,
            'surface' => null,
            'drive.firmware' => '',
            'os_build' => null,
        ]);
        // null, or not sent at all
        unset($upload['results']['rnd4k_q32_write'], $upload['results']['rnd4k_q1_write']);

        $row = WinxDiskBenchmark::findOrFail($this->postJson(self::URL, $upload)->assertOk()->json('data.id'));

        $empty = ['seq1m_q8_write', 'seq1m_q1_write', 'rnd4k_q32_write', 'rnd4k_q1_write', 'score_write',
            'surface_min', 'surface_avg', 'surface_max', 'surface_points', 'firmware', 'os_build'];

        foreach ($empty as $column) {
            $this->assertNull($row->{$column}, $column);
        }

        $this->assertSame('3487.80', $row->seq1m_q8_read);
        $this->assertSame(2358, $row->score_read);
    }

    public static function ordinaryModelNames(): array
    {
        return [
            'underscore and spaces' => ['WD_BLACK SN770 1TB', 'WDBLACKSN7701TB'],
            'dashes and slashes' => ['Generic- SD/MMC/MS PRO', 'GENERICSDMMCMSPRO'],
            'dots, brackets and a plus' => ['Samsung SSD 990 PRO (2.0) +', 'SAMSUNGSSD990PRO20'],
            'lower case' => ['wd black sn770 1tb', 'WDBLACKSN7701TB'],
            'eighty characters' => [str_repeat('A1', 40), str_repeat('A1', 40)],
        ];
    }

    /** @dataProvider ordinaryModelNames */
    public function test_ordinary_model_names_are_accepted_and_keyed_on_letters_and_digits(string $model, string $key): void
    {
        $id = $this->postJson(self::URL, $this->upload(['drive.model' => $model]))->assertOk()->json('data.id');

        $this->assertSame($key, WinxDiskBenchmark::findOrFail($id)->model_key);
    }

    public static function badModelNames(): array
    {
        return [
            'markup' => ['<script>alert(1)</script>'],
            'a semicolon' => ['Samsung SSD 980; DROP TABLE users'],
            'a quote' => ["Kingston 'A400' 480GB"],
            'a line break inside' => ["WD Blue\nSN570"],
            'a tab inside' => ["WD\tBlue SN570"],
            'letters outside ASCII' => ['ไดรฟ์ของฉัน 1TB'],
            'a lookalike letter' => ["S\u{0430}msung SSD 980"],
            'longer than 80' => [str_repeat('A', 81)],
            'no letter or digit' => ['-- / --'],
            'empty' => [''],
            'not text' => [12345],
        ];
    }

    /** @dataProvider badModelNames */
    public function test_a_model_name_outside_the_allowed_characters_is_refused(mixed $model): void
    {
        $this->postJson(self::URL, $this->upload(['drive.model' => $model]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'invalid')
            ->assertJsonStructure(['message', 'errors' => ['drive.model']]);

        $this->assertSame(0, WinxDiskBenchmark::count());
    }

    public static function badValues(): array
    {
        return [
            'MB/s above 100000' => ['results.seq1m_q8_read', 100000.01],
            'negative MB/s' => ['results.rnd4k_q1_write', -0.5],
            'a read result missing' => ['results.rnd4k_q1_read', null],
            'MB/s in words' => ['results.seq1m_q1_read', 'fast'],
            'IOPS above ten million' => ['results.rnd4k_q32_read_iops', 10000001],
            'access time above a second' => ['results.access_ms', 1000.5],
            'free space above 100%' => ['test.free_pct', 100.01],
            'capacity 0' => ['drive.capacity_gb', 0],
            'capacity above 200000' => ['drive.capacity_gb', 200001],
            'a fraction of a GB' => ['drive.capacity_gb', 999.5],
            'true for a number' => ['drive.capacity_gb', true],
            'test file under 64 MB' => ['test.file_mb', 63],
            'test file over 64 GB' => ['test.file_mb', 65537],
            'no time per test' => ['test.seconds_per_test', 0],
            'over a minute per test' => ['test.seconds_per_test', 61],
            'score above a million' => ['score.total', 1000001],
            'negative write score' => ['score.write', -1],
            'no read score' => ['score.read', null],
            'unknown kind' => ['drive.kind', 'ssd'],
            'bus in the wrong case' => ['drive.bus', 'nvme'],
            'unknown media' => ['drive.media', 'Flash'],
            'unknown label' => ['test.label', 'during'],
            'install id that is not a uuid' => ['install_id', 'DESKTOP-ABC123'],
            'no install id' => ['install_id', null],
            'app version with a space' => ['app_version', '1.1.0 beta'],
            'firmware with a control character' => ['drive.firmware', "73\x0130WD"],
            'results that are not an object' => ['results', 'fast'],
        ];
    }

    /** @dataProvider badValues */
    public function test_numbers_out_of_range_and_unknown_values_are_refused(string $path, mixed $value): void
    {
        $this->postJson(self::URL, $this->upload([$path => $value]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'invalid')
            ->assertJsonStructure(['message', 'errors' => [$path]]);

        $this->assertSame(0, WinxDiskBenchmark::count());
    }

    public function test_a_number_too_large_for_a_float_is_a_422_not_a_crash(): void
    {
        // json_decode makes 1e400 INF, and Laravel's between rule throws on INF
        $points = self::points(1900);
        $points[3] = '__HUGE__';

        $uploads = [
            'results.seq1m_q8_read' => '__HUGE__',
            'score.total' => '__HUGE__',
            'surface.points' => $points,
        ];

        foreach ($uploads as $path => $value) {
            $json = str_replace('"__HUGE__"', '1e400', json_encode($this->upload([$path => $value])));

            $this->call('POST', self::URL, [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], $json)
                ->assertStatus(422)
                ->assertJsonPath('error_code', 'invalid')
                ->assertJsonStructure(['errors' => [$path]]);
        }

        $this->assertSame(0, WinxDiskBenchmark::count());
    }

    public static function badSurfaces(): array
    {
        $points = self::points(1900);
        $surface = ['min' => 1500.0, 'avg' => 1800.0, 'max' => 1900.0];

        return [
            '19 points' => [$surface + ['points' => array_slice($points, 0, 19)]],
            '21 points' => [$surface + ['points' => [...$points, 1700.0]]],
            'a point above 100000' => [$surface + ['points' => array_replace($points, [7 => 100000.5])]],
            'a negative point' => [$surface + ['points' => array_replace($points, [0 => -1])]],
            'a point that is not a number' => [$surface + ['points' => array_replace($points, [7 => 'n/a'])]],
            'an empty point' => [$surface + ['points' => array_replace($points, [19 => null])]],
            'points as an object' => [$surface + ['points' => array_combine(range('a', 't'), $points)]],
            'no points' => [$surface],
            'no average' => [['min' => 1500.0, 'max' => 1900.0, 'points' => $points]],
            'not an object' => ['slow'],
        ];
    }

    /** @dataProvider badSurfaces */
    public function test_a_surface_is_exactly_twenty_points_or_null(mixed $surface): void
    {
        $this->postJson(self::URL, $this->upload(['surface' => $surface]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'invalid');

        $this->assertSame(0, WinxDiskBenchmark::count());
    }

    public function test_the_same_result_sent_twice_is_kept_once(): void
    {
        $first = $this->postJson(self::URL, $this->upload())->assertOk()->json('data.id');

        // the answer got lost on the way and the app sent it again
        $this->postJson(self::URL, $this->upload())->assertOk()->assertJsonPath('data.id', $first);
        $this->assertSame(1, WinxDiskBenchmark::count());

        // a new run of the same drive is a new sample
        $this->postJson(self::URL, $this->upload(['results.seq1m_q8_read' => 3490.1]))->assertOk();
        $this->assertSame(2, WinxDiskBenchmark::count());
    }

    public function test_one_address_may_upload_twenty_results_an_hour(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->postJson(self::URL, $this->upload(['score.total' => 4000 + $i]))->assertOk();
        }

        $this->postJson(self::URL, $this->upload(['score.total' => 5000]))
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'rate_limited')
            ->assertJsonStructure(['message'])
            ->assertHeader('Retry-After');
        $this->assertSame(20, WinxDiskBenchmark::count());

        // another address is not held back by it
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson(self::URL, $this->upload(['score.total' => 5000]))
            ->assertOk();

        // and an hour later the first one may upload again
        $this->travel(61)->minutes();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson(self::URL, $this->upload(['score.total' => 5001]))
            ->assertOk();
    }

    public function test_one_installation_may_upload_thirty_results_a_day(): void
    {
        // thirty uploads yesterday afternoon (stored directly: the per-address limit is another test)
        $this->travelTo(Carbon::parse('2026-10-08 13:00:00'));

        for ($i = 0; $i < 30; $i++) {
            $this->benchmark(['install_id' => self::INSTALL, 'score_total' => 3000 + $i]);
        }

        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));

        $this->postJson(self::URL, $this->upload())
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'rate_limited')
            ->assertJsonStructure(['message'])
            // the oldest of the thirty turns a day old in an hour
            ->assertHeader('Retry-After', 3600);
        $this->assertSame(30, WinxDiskBenchmark::count());

        // another installation is not held back by it
        $this->postJson(self::URL, $this->upload(['install_id' => (string) Str::uuid()]))->assertOk();

        // a day after the oldest there is room again
        $this->travelTo(Carbon::parse('2026-10-09 13:00:01'));
        $this->postJson(self::URL, $this->upload())->assertOk();
        $this->assertSame(32, WinxDiskBenchmark::count());
    }

    // ── Stats ───────────────────────────────────────────────────────────

    public function test_stats_stay_hidden_until_three_installations_measured_the_model(): void
    {
        $first = (string) Str::uuid();

        // many runs on two PCs are still only two people's results
        foreach ([4000, 4100, 4200, 4300] as $score) {
            $this->benchmark(['install_id' => $first, 'score_total' => $score]);
        }

        $this->benchmark(['score_total' => 3900]);

        $this->stats(self::MODEL)
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => ['model' => self::MODEL, 'samples' => 5, 'devices' => 2, 'stats' => null],
            ]);
    }

    public function test_three_installations_are_enough_and_numbers_are_rounded_to_one_decimal(): void
    {
        foreach ([[1000, 3000], [2000, 3400.5], [4000, 3500.25]] as [$score, $read]) {
            $this->benchmark(['score_total' => $score, 'seq1m_q8_read' => $read]);
        }

        $stats = $this->stats(self::MODEL)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.model', self::MODEL)
            ->assertJsonPath('data.samples', 3)
            ->assertJsonPath('data.devices', 3)
            ->json('data.stats');

        $this->assertEquals(['p25' => 1500, 'median' => 2000, 'p75' => 3000, 'max' => 4000], $stats['score_total']);
        // halfway between ranks: 3200.25 → 3200.3 and 3450.375 → 3450.4
        $this->assertEquals(['p25' => 3200.3, 'median' => 3400.5, 'p75' => 3450.4], $stats['seq1m_q8_read']);
        // every PC ran the reads; none sent a write, an access time or a surface
        $this->assertSame(
            ['score_total', 'seq1m_q8_read', 'seq1m_q1_read', 'rnd4k_q32_read', 'rnd4k_q1_read', 'surface_median'],
            array_keys($stats),
        );
        $this->assertNull($stats['surface_median']);
    }

    public function test_more_rows_interpolate_between_ranks_and_leave_out_what_too_few_pcs_measured(): void
    {
        [$a, $b, $c] = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];

        $this->benchmark(['install_id' => $a, 'score_total' => 1000, 'seq1m_q8_read' => 3000,
            'seq1m_q8_write' => 2000, 'surface_points' => self::points(1800)]);
        $this->benchmark(['install_id' => $b, 'score_total' => 2000, 'seq1m_q8_read' => 3400.5,
            'seq1m_q8_write' => 2100, 'surface_points' => self::points(1900)]);
        $this->benchmark(['install_id' => $b, 'score_total' => 2000, 'seq1m_q8_read' => 3400.5,
            'seq1m_q8_write' => 2150]);
        $this->benchmark(['install_id' => $c, 'score_total' => 4000, 'seq1m_q8_read' => 3500.25,
            'access_ms' => 0.125, 'surface_points' => self::points(2000)]);

        $stats = $this->stats('wd black sn770 1tb') // another spelling of the same drive
            ->assertOk()
            ->assertJsonPath('data.model', 'wd black sn770 1tb')
            ->assertJsonPath('data.samples', 4)
            ->assertJsonPath('data.devices', 3)
            ->json('data.stats');

        // ranks 0.75, 1.5 and 2.25 of four
        $this->assertEquals(['p25' => 1750, 'median' => 2000, 'p75' => 2500, 'max' => 4000], $stats['score_total']);
        $this->assertEquals(['p25' => 3300.4, 'median' => 3400.5, 'p75' => 3425.4], $stats['seq1m_q8_read']);
        // three write results, but from two PCs — the numbers would be mostly one person's
        $this->assertArrayNotHasKey('seq1m_q8_write', $stats);
        // one PC measured an access time
        $this->assertArrayNotHasKey('access_ms', $stats);
        // three PCs sent a surface: the median of each of its twenty points
        $this->assertEquals(self::points(1900), $stats['surface_median']);
    }

    public function test_capacity_narrows_the_answer_to_drives_within_ten_percent(): void
    {
        foreach ([900, 1000, 1100] as $gb) {
            $this->benchmark(['capacity_gb' => $gb, 'score_total' => 4000]);
        }

        foreach ([899, 1101, 2000] as $gb) {
            $this->benchmark(['capacity_gb' => $gb, 'score_total' => 8000]);
        }

        $this->stats(self::MODEL, 1000)
            ->assertOk()
            ->assertJsonPath('data.samples', 3)
            ->assertJsonPath('data.devices', 3)
            ->assertJsonPath('data.stats.score_total.max', 4000);

        $this->stats(self::MODEL)
            ->assertJsonPath('data.samples', 6)
            ->assertJsonPath('data.stats.score_total.max', 8000);

        // only one PC near 2 TB
        $this->stats(self::MODEL, 2000)
            ->assertJsonPath('data.samples', 1)
            ->assertJsonPath('data.devices', 1)
            ->assertJsonPath('data.stats', null);

        // whole GB at both ends, without floating-point drift
        $this->assertSame([900, 1100], WinxDiskBenchmarkStats::capacityRange(1000));
        $this->assertSame([838, 1024], WinxDiskBenchmarkStats::capacityRange(931));
        $this->assertSame([1, 1], WinxDiskBenchmarkStats::capacityRange(1));
    }

    public function test_the_outer_five_percent_by_score_are_dropped_from_twenty_rows_on(): void
    {
        // a dying drive, seventeen ordinary runs and one that cannot have been this drive
        $this->benchmark(['score_total' => 10]);

        for ($i = 0; $i < 17; $i++) {
            $this->benchmark(['score_total' => 1000 + $i]);
        }

        $this->benchmark(['score_total' => 99999]);

        // nineteen rows are too few to call anything an outlier
        $this->stats(self::MODEL)
            ->assertJsonPath('data.samples', 19)
            ->assertJsonPath('data.devices', 19)
            ->assertJsonPath('data.stats.score_total.max', 99999);

        $this->benchmark(['score_total' => 1017]);
        Cache::flush(); // answers are kept ten minutes

        // twenty: below the 5th percentile (950.5) and above the 95th (5966.1) go
        $this->stats(self::MODEL)
            ->assertJsonPath('data.samples', 18)
            ->assertJsonPath('data.devices', 18)
            ->assertJsonPath('data.stats.score_total.p25', 1004.3)
            ->assertJsonPath('data.stats.score_total.median', 1008.5)
            ->assertJsonPath('data.stats.score_total.p75', 1012.8)
            ->assertJsonPath('data.stats.score_total.max', 1017);
    }

    public function test_an_answer_is_reused_for_ten_minutes(): void
    {
        foreach (range(1, 3) as $i) {
            $this->benchmark();
        }

        $this->stats(self::MODEL)->assertJsonPath('data.samples', 3);

        $this->benchmark();

        $this->travel(9)->minutes();
        $this->stats(self::MODEL)->assertJsonPath('data.samples', 3);

        $this->travel(2)->minutes();
        $this->stats(self::MODEL)->assertJsonPath('data.samples', 4);
    }

    public function test_an_unknown_model_gets_zeros_not_a_404(): void
    {
        $this->benchmark();

        foreach (['Nothing Like It 9TB', '-- / --'] as $model) {
            $this->stats($model)
                ->assertOk()
                ->assertExactJson([
                    'success' => true,
                    'data' => ['model' => $model, 'samples' => 0, 'devices' => 0, 'stats' => null],
                ]);
        }
    }

    public function test_a_stats_question_without_a_usable_model_or_capacity_is_refused(): void
    {
        $questions = [
            self::STATS_URL,
            self::STATS_URL . '?model=',
            self::STATS_URL . '?' . http_build_query(['model' => '<script>']),
            self::STATS_URL . '?' . http_build_query(['model' => str_repeat('A', 81)]),
            self::STATS_URL . '?' . http_build_query(['model' => self::MODEL, 'capacity_gb' => 'lots']),
            self::STATS_URL . '?' . http_build_query(['model' => self::MODEL, 'capacity_gb' => 0]),
        ];

        foreach ($questions as $question) {
            $this->getJson($question)
                ->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error_code', 'invalid')
                ->assertJsonStructure(['message']);
        }
    }

    public function test_each_route_keeps_its_own_throttle(): void
    {
        $upload = Route::getRoutes()->getByName('api.winx-tools.disk-benchmarks.store');
        $stats = Route::getRoutes()->getByName('api.winx-tools.disk-benchmarks.stats');

        $this->assertSame('api/v1/product/winx-tools/disk-benchmarks', $upload->uri());
        $this->assertSame(['POST'], $upload->methods());
        $this->assertContains('throttle:winx-disk-benchmarks', $upload->gatherMiddleware());

        $this->assertSame('api/v1/product/winx-tools/disk-benchmarks/stats', $stats->uri());
        $this->assertContains('GET', $stats->methods());
        $this->assertContains('throttle:60,1,api-winx-disk-stats', $stats->gatherMiddleware());
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    /**
     * What the app sends — the contract's own example — with $set applied (dot paths).
     *
     * @param  array<string, mixed>  $set
     * @return array<string, mixed>
     */
    private function upload(array $set = []): array
    {
        $upload = [
            'install_id' => self::INSTALL,
            'app_version' => '1.1.0',
            'os_build' => 26300,
            'drive' => [
                'model' => self::MODEL,
                'kind' => 'nvme',
                'bus' => 'Nvme',
                'media' => 'Ssd',
                'capacity_gb' => 1000,
                'firmware' => '731030WD',
                'rpm' => null,
            ],
            'test' => ['file_mb' => 1024, 'seconds_per_test' => 5, 'label' => '', 'free_pct' => 0.31],
            'results' => [
                'seq1m_q8_read' => 3487.8,
                'seq1m_q8_write' => 2420.2,
                'seq1m_q1_read' => 2390.5,
                'seq1m_q1_write' => 150.6,
                'rnd4k_q32_read' => 984.3,
                'rnd4k_q32_write' => 714.7,
                'rnd4k_q1_read' => 53.5,
                'rnd4k_q1_write' => 113.0,
                'rnd4k_q32_read_iops' => 240317,
                'rnd4k_q1_read_iops' => 13073,
                'access_ms' => null,
            ],
            'surface' => ['min' => 1500.0, 'avg' => 1800.0, 'max' => 1900.0, 'points' => self::points(1900)],
            'score' => ['total' => 4256, 'read' => 2358, 'write' => 1898],
        ];

        foreach ($set as $path => $value) {
            data_set($upload, $path, $value);
        }

        return $upload;
    }

    /**
     * Twenty surface points, 10 MB/s apart, starting at $first.
     *
     * @return list<float>
     */
    private static function points(float $first): array
    {
        return array_map(fn (int $i) => $first - 10 * $i, range(0, 19));
    }

    /**
     * An upload as stored, written directly (so the route's per-address limit stays out of the way).
     * Each one is a different installation unless install_id says otherwise.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function benchmark(array $attributes = []): WinxDiskBenchmark
    {
        return WinxDiskBenchmark::create(array_merge([
            'install_id' => (string) Str::uuid(),
            'app_version' => '1.1.0',
            'model' => self::MODEL,
            'model_key' => 'WDBLACKSN7701TB',
            'kind' => 'nvme',
            'bus' => 'Nvme',
            'media' => 'Ssd',
            'capacity_gb' => 1000,
            'file_mb' => 1024,
            'seconds_per_test' => 5,
            'seq1m_q8_read' => 3400,
            'seq1m_q1_read' => 2300,
            'rnd4k_q32_read' => 900,
            'rnd4k_q1_read' => 50,
            'score_total' => 4000,
            'score_read' => 2300,
        ], $attributes));
    }

    private function stats(string $model, ?int $capacityGb = null): TestResponse
    {
        $query = ['model' => $model];

        if ($capacityGb !== null) {
            $query['capacity_gb'] = $capacityGb;
        }

        return $this->getJson(self::STATS_URL . '?' . http_build_query($query));
    }
}
