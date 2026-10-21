<?php

namespace Tests\Feature;

use App\Models\TpixPayment;
use App\Models\TpixPriceSnapshot;
use App\Models\User;
use App\Services\TpixPay\TpixReportService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin "รายงาน TPIX" page: totals from SQL, the charts' data, the CSV, and admin-only access.
 */
class TpixReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();

        // 10:00 Bangkok = 03:00 UTC
        Carbon::setTestNow(Carbon::parse('2026-10-16 03:00:00', 'UTC'));
        CarbonImmutable::setTestNow(Carbon::parse('2026-10-16 03:00:00', 'UTC'));

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->alice = User::factory()->create(['name' => 'Alice Payer', 'email' => 'alice@example.test']);
        $this->bob = User::factory()->create(['name' => '=HYPERLINK("http://x")', 'email' => 'bob@example.test']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function payment(User $user, string $status, string $thb, string $tpix, ?string $paidAt, array $extra = []): TpixPayment
    {
        $payment = new TpixPayment;
        $payment->forceFill(array_merge([
            'reference' => 'TPX' . strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'purpose' => TpixPayment::PURPOSE_ORDER,
            'amount_thb' => $thb,
            'rate_thb' => '3.25',
            'price_usd' => '0.1',
            'usd_thb' => '32.5',
            'amount_wei' => (string) BigDecimal::of($tpix)->multipliedBy('1000000000000000000')->toScale(0, RoundingMode::Down),
            'amount_tpix' => $tpix,
            'receive_address' => '0x00000000000000000000000000000000000a11ce',
            'status' => $status,
            'paid_at' => $paidAt,
            'expires_at' => now()->addMinutes(20),
            'created_at' => $paidAt ?? now()->subHour(),
            'updated_at' => now(),
        ], $extra))->save();

        return $payment;
    }

    private function seedPayments(): void
    {
        // Today (Bangkok): 03:00 UTC on the 16th is 10:00 ICT; 18:00 UTC on the 15th is 01:00 ICT on the 16th
        $this->payment($this->alice, 'paid', '1000.00', '307.692307692307692307', '2026-10-16 02:00:00');
        $this->payment($this->bob, 'paid', '200.00', '61.538461538461538461', '2026-10-15 18:00:00', ['purpose' => TpixPayment::PURPOSE_WALLET_TOPUP]);
        // Yesterday in Bangkok (16:00 UTC on the 15th = 23:00 ICT on the 15th) — inside 7 days, not today
        $this->payment($this->alice, 'credited_wallet', '500.00', '153.846153846153846153', '2026-10-15 16:00:00', ['credited_thb' => '480.00']);
        // 20 days ago — inside 30 days only
        $this->payment($this->bob, 'paid', '300.00', '92.307692307692307692', '2026-09-26 05:00:00');
        // 40 days ago — outside every window
        $this->payment($this->alice, 'paid', '9999.00', '3076.615384615384615384', '2026-09-06 05:00:00');
        // Not received / waiting
        $this->payment($this->alice, 'pending', '100.00', '30.769230769230769230', null);
        $this->payment($this->alice, 'pending', '100.00', '30.769230769230769231', null, ['expires_at' => now()->subMinute()]);
        $this->payment($this->bob, 'confirming', '150.00', '46.153846153846153846', null);
        $this->payment($this->bob, 'held', '700.00', '215.384615384615384615', '2026-10-16 01:00:00');
        $this->payment($this->bob, 'late', '50.00', '15.384615384615384615', null);
        $this->payment($this->alice, 'expired', '80.00', '24.615384615384615384', null);
    }

    public function test_the_kpis_come_from_the_settled_payments_in_bangkok_days(): void
    {
        $this->seedPayments();

        $k = app(TpixReportService::class)->build()['kpis'];

        $this->assertSame(1200.0, $k['today']['thb']);
        $this->assertSame(2, $k['today']['count']);
        // credited_wallet counts what went into the wallet (480), not the quote's 500
        $this->assertSame(1680.0, $k['d7']['thb']);
        $this->assertSame(3, $k['d7']['count']);
        $this->assertSame(1980.0, $k['d30']['thb']);
        $this->assertSame(4, $k['d30']['count']);
        $this->assertEqualsWithDelta(615.38, $k['d30']['tpix'], 0.01);
        $this->assertSame(495.0, $k['average_thb']);
        // 10 quotes made in the last 30 days, 4 of them settled
        $this->assertSame(10, $k['quotes_30d']);
        $this->assertSame(4, $k['settled_of_quotes_30d']);
        $this->assertSame(40.0, $k['conversion_pct']);
        // one live pending + one confirming; the expired pending does not count
        $this->assertSame(2, $k['open_quotes']);
        $this->assertSame(1, $k['held']);
        $this->assertSame(700.0, $k['held_thb']);
        $this->assertSame(1, $k['late']);
        $this->assertSame(480.0, $k['credited_thb_30d']);
        $this->assertSame(1, $k['credited_count_30d']);
    }

    public function test_the_daily_chart_has_thirty_bangkok_days_split_by_purpose(): void
    {
        $this->seedPayments();

        $daily = app(TpixReportService::class)->build()['daily'];

        $this->assertCount(30, $daily['labels']);
        $this->assertSame('16/10', end($daily['labels']));
        $this->assertSame(1000.0, end($daily['series']['order']));
        $this->assertSame(200.0, end($daily['series']['wallet_topup']));
        // 23:00 ICT on the 15th lands on the 15th
        $this->assertSame(480.0, $daily['series']['order'][28]);
        $this->assertEqualsWithDelta(1980.0, array_sum($daily['series']['order']) + array_sum($daily['series']['wallet_topup']), 0.001);
    }

    public function test_the_price_chart_is_downsampled_and_carries_a_thirty_minute_average(): void
    {
        $rows = [];
        $start = now()->subDays(7)->addMinute();
        for ($i = 0; $i < 1000; $i++) {
            $thb = $i < 500 ? 3.0 : 4.0;
            $rows[] = [
                'price_usd' => (string) ($thb / 32.5),
                'usd_thb' => '32.5',
                'price_thb' => (string) $thb,
                'reserve_tpix' => '1000',
                'reserve_usdt' => '100',
                'source' => 'dex',
                'created_at' => $start->copy()->addMinutes($i * 10)->format('Y-m-d H:i:s'),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('tpix_price_snapshots')->insert($chunk);
        }
        // Older than 7 days: left out
        TpixPriceSnapshot::create(['price_usd' => '9', 'price_thb' => '999', 'source' => 'dex', 'created_at' => now()->subDays(8)]);

        $price = app(TpixReportService::class)->build()['price'];

        $this->assertLessThanOrEqual(TpixReportService::MAX_POINTS, count($price['labels']));
        $this->assertGreaterThan(200, count($price['labels']));
        $this->assertCount(count($price['labels']), $price['thb_ma30']);
        $this->assertSame(3.0, $price['thb'][0]);
        $this->assertSame(3.0, $price['thb_ma30'][0]);
        $this->assertSame(4.0, end($price['thb']));
        $this->assertSame(4.0, end($price['thb_ma30']));
        $this->assertNotContains(999.0, $price['thb']);
        $this->assertSame(100.0, $price['reserve_usdt'][0]);
    }

    public function test_the_moving_average_lags_a_jump(): void
    {
        // one row a minute: 3.0 for an hour, then 6.0 — ten minutes after the jump the
        // 30-minute window holds 20 old rows and 10 new ones (well, 11 incl. the jump minute)
        for ($i = 0; $i < 71; $i++) {
            TpixPriceSnapshot::create([
                'price_usd' => '0.1', 'usd_thb' => '32.5', 'price_thb' => $i < 60 ? '3' : '6',
                'source' => 'dex', 'created_at' => now()->subMinutes(70 - $i),
            ]);
        }

        $price = app(TpixReportService::class)->build()['price'];

        $this->assertSame(6.0, end($price['thb']));
        $this->assertEqualsWithDelta((19 * 3 + 11 * 6) / 30, end($price['thb_ma30']), 0.0001);
    }

    public function test_downsample_keeps_short_series_and_averages_buckets(): void
    {
        $short = [['t' => 1, 'v' => 1.0], ['t' => 2, 'v' => 2.0]];
        $this->assertSame($short, TpixReportService::downsample($short, 300));

        $points = array_map(fn ($i) => ['t' => $i, 'v' => (float) $i], range(0, 9));
        $out = TpixReportService::downsample($points, 5);
        $this->assertCount(5, $out);
        $this->assertSame(0.5, $out[0]['v']);
        $this->assertSame(8.5, $out[4]['v']);
    }

    public function test_the_admin_sees_the_report_page_with_the_seeded_numbers(): void
    {
        $this->seedPayments();

        $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports'))
            ->assertOk()
            ->assertSee('รายงาน TPIX')
            ->assertSee('฿1,200.00')   // today
            ->assertSee('฿1,980.00')   // 30 days
            ->assertSee('40%')         // conversion
            ->assertSee('Alice Payer')
            ->assertSee('alice@example.test')
            ->assertSee('chart.js@4.4.1', false)
            ->assertSee('tpixDailyChart', false)
            ->assertSee('tpixStatusChart', false)
            ->assertSee(route('admin.tpix-payments.reports.csv'), false);

        // and the TPIX payments page links to it
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.index'))
            ->assertOk()
            ->assertSee(route('admin.tpix-payments.reports'), false);
    }

    public function test_the_report_page_renders_with_no_data_at_all(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports'))
            ->assertOk()
            ->assertSee('ยังไม่มีการชำระด้วย TPIX ใน 30 วันที่ผ่านมา')
            ->assertSee('ยังไม่มีข้อมูลราคาใน 7 วันที่ผ่านมา');
    }

    public function test_the_report_is_cached_for_a_minute(): void
    {
        $this->payment($this->alice, 'paid', '100.00', '30.769230769230769230', '2026-10-16 02:00:00');
        $first = app(TpixReportService::class)->report();

        $this->payment($this->alice, 'paid', '200.00', '61.538461538461538461', '2026-10-16 02:30:00');
        $this->assertSame($first['kpis']['today']['thb'], app(TpixReportService::class)->report()['kpis']['today']['thb']);

        Carbon::setTestNow(now()->addSeconds(TpixReportService::CACHE_SECONDS + 1));
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(TpixReportService::CACHE_SECONDS + 1));
        $this->assertSame(300.0, app(TpixReportService::class)->report()['kpis']['today']['thb']);
    }

    public function test_the_csv_has_a_bom_thai_headers_the_range_and_no_formulas(): void
    {
        $this->seedPayments();

        $response = $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports.csv', ['from' => '2026-10-15', 'to' => '2026-10-16']));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('tpix-payments_20261015-20261016.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);

        $lines = array_values(array_filter(explode("\n", substr($body, 3))));
        $header = str_getcsv($lines[0]);
        $this->assertSame('เลขอ้างอิง', $header[0]);
        $this->assertContains('จำนวน TPIX', $header);
        $this->assertContains('เข้ากระเป๋าเงิน (บาท)', $header);

        // created 15th–16th Bangkok: everything except the 20- and 40-day-old payments
        $this->assertCount(1 + 9, $lines);
        $this->assertStringNotContainsString('9999.00', $body);
        $this->assertStringContainsString('1000.00', $body);
        $this->assertStringContainsString('ชำระแล้ว', $body);
        $this->assertStringContainsString('480.00', $body);

        // A name that is a spreadsheet formula is written as text
        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
        $this->assertStringNotContainsString(',"=HYPERLINK', $body);
    }

    public function test_the_csv_refuses_a_bad_range(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports.csv', ['from' => '2026-10-16', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports.csv', ['from' => 'yesterday', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('from');
        $this->actingAs($this->admin)->get(route('admin.tpix-payments.reports.csv', ['from' => '2024-01-01', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_only_admins_reach_the_report_and_the_csv(): void
    {
        $this->payment($this->alice, 'paid', '100.00', '30.769230769230769230', '2026-10-16 02:00:00');
        $range = ['from' => '2026-10-01', 'to' => '2026-10-16'];

        $this->get(route('admin.tpix-payments.reports'))->assertRedirect(route('login'));
        $this->get(route('admin.tpix-payments.reports.csv', $range))->assertRedirect(route('login'));

        $this->actingAs($this->alice)->get(route('admin.tpix-payments.reports'))->assertForbidden();
        $this->actingAs($this->alice)->get(route('admin.tpix-payments.reports.csv', $range))->assertForbidden();
    }
}
