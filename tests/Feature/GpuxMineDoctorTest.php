<?php

namespace Tests\Feature;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Services\GpuxMineHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * gpuxmine:doctor — ทุกอย่างที่ทำให้ GPUxMINE พังแบบเงียบ ต้องทำให้คำสั่งนี้จบด้วย 1
 *
 *   — env ขาด, relay ไม่รับ admin key, aixman ไม่รับ webhook secret
 *   — cron ไม่เคยรัน หรือรันห่างเกินไป (ตั้งเป็นทุกห้านาที)
 *   — เงินพ้นระยะพักแล้วยังค้าง pending (ตัวปล่อยเงินไม่ทำงาน)
 * และต้องไม่เขียนอะไรที่ relay/aixman ไม่พิมพ์ความลับออกมา
 */
class GpuxMineDoctorTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->fakeGpuxMine();
        config(['services.gpuxmine.earning_hold_hours' => 24]);

        // aixman จริงตอบ 400 กับ body ที่ไม่มี workerId — หลังตรวจความลับผ่านแล้ว
        $this->aixmanStatus = 400;
        $this->aixmanBody = ['error' => 'workerId, endpoint and token are all required'];
        $this->relayWorkers = [];

        $this->app->make(ConsoleKernel::class)->bootstrap();
        $this->beatBoth();
    }

    private function beatBoth(): void
    {
        $health = app(GpuxMineHealthService::class);
        $health->beat(GpuxMineHealthService::TASK_SYNC);
        $health->beat(GpuxMineHealthService::TASK_SETTLE);
    }

    /** @return array{0:int, 1:string} */
    private function doctor(array $options = []): array
    {
        $code = Artisan::call('gpuxmine:doctor', $options);

        return [$code, Artisan::output()];
    }

    public function test_a_healthy_setup_passes_without_writing_anything_or_printing_secrets(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        [$code, $out] = $this->doctor();

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('ไม่ผ่าน 0', $out);
        $this->assertStringContainsString('รู้จัก 1 เครื่อง ออนไลน์ 1', $out);
        $this->assertStringContainsString('gpuxmine:sync-nodes', $out);
        $this->assertStringContainsString('* * * * *', $out);
        $this->assertStringContainsString('0 * * * *', $out);
        $this->assertStringNotContainsString('relay-admin-key-for-tests', $out);
        $this->assertStringNotContainsString('shared-webhook-secret-for-tests', $out);

        // ถาม aixman ด้วย body ที่ไม่มี workerId เท่านั้น — ไม่มีการขึ้นทะเบียนเครื่องจริง
        $probes = $this->aixmanPushes();
        $this->assertCount(1, $probes);
        $this->assertArrayNotHasKey('workerId', $probes[0]);
        $this->assertSame([], $this->aixmanRetires());
        $this->assertSame([], $this->relayDeletes());
        $this->assertSame([], $this->relayAdminActions());
    }

    public function test_missing_relay_or_aixman_settings_fail(): void
    {
        config(['services.gpuxmine.admin_key' => null]);
        [$code, $out] = $this->doctor();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('GPUXMINE_RELAY_ADMIN_KEY', $out);

        config(['services.gpuxmine.admin_key' => 'relay-admin-key-for-tests', 'services.aixman.webhook_secret' => '']);
        [$code, $out] = $this->doctor();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('AIXMAN_WEBHOOK_SECRET', $out);
    }

    public function test_a_plain_http_relay_on_the_internet_fails(): void
    {
        config(['services.gpuxmine.relay_url' => 'http://relay.example.test:8443']);

        [$code, $out] = $this->doctor();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('ต้องเป็น https', $out);
    }

    public function test_a_relay_that_refuses_the_admin_key_fails(): void
    {
        $this->relayListStatus = 401;

        [$code, $out] = $this->doctor();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('relay ไม่รับ admin key', $out);
    }

    public function test_an_unreachable_relay_fails(): void
    {
        $this->relayWorkers = null;   // 502

        [$code] = $this->doctor();

        $this->assertSame(1, $code);
    }

    public function test_aixman_rejecting_the_secret_or_missing_the_route_fails(): void
    {
        $this->aixmanStatus = 401;
        [$code, $out] = $this->doctor();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('XMAN_WEBHOOK_SECRET', $out);

        $this->aixmanStatus = 404;
        [$code, $out] = $this->doctor();
        $this->assertSame(1, $code);
        $this->assertStringContainsString('ยังไม่มี /api/gpux/nodes', $out);

        $this->aixmanStatus = null;   // ต่อไม่ติด
        [$code] = $this->doctor();
        $this->assertSame(1, $code);
    }

    public function test_a_cron_that_never_ran_fails_once_machines_are_paired(): void
    {
        Cache::flush();
        GpuNode::factory()->paired()->create();

        [$code, $out] = $this->doctor();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('ไม่มีบันทึกว่าเคยรัน', $out);
    }

    public function test_a_sync_every_five_minutes_warns_and_a_dead_one_fails(): void
    {
        $this->travel(6)->minutes();
        $this->beatSettleOnly();
        [$code, $out] = $this->doctor();
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('*/5', $out);
        [$strict] = $this->doctor(['--strict' => true]);
        $this->assertSame(1, $strict);

        $this->travel(20)->minutes();
        $this->beatSettleOnly();
        [$code] = $this->doctor();
        $this->assertSame(1, $code);
    }

    public function test_the_commands_record_that_the_cron_ran(): void
    {
        Cache::flush();
        $health = app(GpuxMineHealthService::class);

        $this->artisan('gpuxmine:sync-nodes')->run();
        $this->artisan('gpuxmine:settle-earnings')->run();

        $this->assertNotNull($health->lastBeat(GpuxMineHealthService::TASK_SYNC));
        $this->assertNotNull($health->lastBeat(GpuxMineHealthService::TASK_SETTLE));
    }

    public function test_earnings_stuck_past_the_hold_fail(): void
    {
        $node = GpuNode::factory()->paired()->create();
        GpuJobEarning::factory()->forNode($node)->create(['completed_at' => now()->subHours(30), 'created_at' => now()->subHours(30)]);

        [$code, $out] = $this->doctor();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('เงินค้างระยะพัก', $out);
        $this->assertStringContainsString('1 งาน ฿1.50', $out);
    }

    public function test_a_row_aixman_wrote_late_is_not_stuck_until_its_own_hold_has_passed(): void
    {
        // งานเสร็จสามวันก่อน แต่ aixman เพิ่งเขียนแถว (catch-up sweep) — ยังพักไม่ครบ ไม่ใช่ "ค้าง"
        $node = GpuNode::factory()->paired()->create();
        GpuJobEarning::factory()->forNode($node)->backfilled(3)->create();

        [$code, $out] = $this->doctor();

        $this->assertSame(0, $code, $out);
        $this->assertStringNotContainsString('พ้นระยะพักเกิน', $out);
    }

    public function test_money_held_on_purpose_is_not_reported_as_stuck(): void
    {
        // เพิ่งพ้นระยะพัก — รอบปล่อยเงินชั่วโมงหน้าหยิบเอง
        $node = GpuNode::factory()->paired()->create();
        GpuJobEarning::factory()->forNode($node)->create(['completed_at' => now()->subHours(25)]);

        // เครื่องที่ถูกระงับ: พักไว้โดยตั้งใจ
        $suspended = GpuNode::factory()->paired()->create(['suspended_at' => now()]);
        GpuJobEarning::factory()->forNode($suspended)->create(['completed_at' => now()->subHours(90)]);

        [$code, $out] = $this->doctor();

        $this->assertSame(0, $code, $out);
    }

    public function test_earnings_waiting_for_an_admin_warn(): void
    {
        $node = GpuNode::factory()->paired()->create();
        GpuJobEarning::factory()->forNode($node)->create(['status' => GpuJobEarning::STATUS_REVIEW]);

        [$code, $out] = $this->doctor();
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('รอแอดมินตรวจ', $out);
        $this->assertStringContainsString('/admin/gpuxmine/earnings', $out);

        [$strict] = $this->doctor(['--strict' => true]);
        $this->assertSame(1, $strict);
    }

    public function test_a_stored_http_endpoint_fails_before_aixman_starts_refusing_it(): void
    {
        // doctor เคยตรวจแค่ URL ใน config — ค่าที่เก็บในแต่ละแถวคือสิ่งที่ถูกส่งให้ aixman จริง
        $node = GpuNode::factory()->paired()->create();
        $node->forceFill(['tunnel_endpoint' => 'http://relay.example.test/w/' . $node->worker_id])->save();

        [$code, $out] = $this->doctor();

        $this->assertSame(1, $code, $out);
        $this->assertStringContainsString('ปลายทางอุโมงค์', $out);
        $this->assertStringContainsString('1 เครื่องไม่ใช่ https', $out);
        $this->assertStringContainsString($node->worker_id . ' → http://relay.example.test/w/', $out);
    }

    public function test_an_https_endpoint_that_differs_from_the_relay_url_only_warns(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $node->forceFill(['tunnel_endpoint' => 'https://relay.example.test/w/' . $node->worker_id])->save();

        [$code, $out] = $this->doctor();
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('ไม่ตรงกับ GPUXMINE_RELAY_URL', $out);

        [$strict] = $this->doctor(['--strict' => true]);
        $this->assertSame(1, $strict);
    }

    public function test_the_doctor_says_where_a_referral_share_nobody_can_take_goes(): void
    {
        [, $out] = $this->doctor();
        $this->assertStringContainsString('ส่วนแบ่งผู้แนะนำที่ไม่มีผู้รับ: คืนเจ้าของเครื่อง', $out);
        $this->assertStringContainsString('ทุกเครื่องเก็บปลายทาง', $out);

        config(['services.gpuxmine.unpaid_referral' => 'platform']);
        [, $out] = $this->doctor();
        $this->assertStringContainsString('แพลตฟอร์มเก็บ (GPUXMINE_UNPAID_REFERRAL=platform)', $out);
    }

    public function test_both_gpuxmine_tasks_are_scheduled_as_the_doctor_expects(): void
    {
        $events = collect($this->app->make(Schedule::class)->events());

        $sync = $events->first(fn ($e) => str_contains((string) $e->command, 'gpuxmine:sync-nodes'));
        $this->assertNotNull($sync);
        $this->assertSame('* * * * *', $sync->expression);
        $this->assertTrue($sync->withoutOverlapping);
        $this->assertLessThanOrEqual(5, $sync->expiresAt);

        $settle = $events->first(fn ($e) => str_contains((string) $e->command, 'gpuxmine:settle-earnings'));
        $this->assertNotNull($settle);
        $this->assertSame('0 * * * *', $settle->expression);
    }

    private function beatSettleOnly(): void
    {
        app(GpuxMineHealthService::class)->beat(GpuxMineHealthService::TASK_SETTLE);
    }
}
