<?php

namespace Tests\Feature;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * POST /api/v1/product/gpuxmine/status — สิ่งที่โปรแกรมบนเครื่องถามทุกสามนาที (C6)
 *
 *   — โปรแกรมเคยขึ้นว่า "กำลังแชร์" ทั้งที่ aixman ปฏิเสธหรือแอดมินระงับเครื่อง
 *   — ทุกช่องเงินเป็นขีดกลาง เพราะไม่มีที่ไหนบอกว่างานไหนได้เท่าไร
 *   — ยืนยันตัวแบบเดียวกับ /referral: worker id + token ของเครื่อง เทียบเวลาคงที่
 */
class GpuxMineStatusApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/product/gpuxmine/status';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function askStatus(GpuNode $node, ?string $token = null)
    {
        return $this->postJson(self::URL, [
            'worker_id' => $node->worker_id,
            'token' => $token ?? $node->relay_token,
        ]);
    }

    public function test_the_machine_sees_what_aixman_sees_and_the_owners_money(): void
    {
        // 01:30 เวลาไทย = 18:30 UTC ของเมื่อวาน — "วันนี้" ต้องเริ่มที่เที่ยงคืนเวลาไทย
        Carbon::setTestNow(Carbon::parse('2026-09-25 18:30:00', 'UTC'));

        config(['services.gpuxmine.earning_hold_hours' => 12]);
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->online()->assessed()->withTunnelToken()->create([
            'user_id' => $owner->id,
            'dispatch_status' => 'eligible',
            'dispatch_note' => null,
            'dispatch_worker_status' => 'ready',
            'last_seen_at' => now()->subSeconds(20),
        ]);
        $other = GpuNode::factory()->paired()->create(['user_id' => $owner->id]);
        Wallet::getOrCreateForUser($owner->id)->update(['balance' => '123.45']);

        $at = fn (string $bangkok) => Carbon::parse($bangkok, 'Asia/Bangkok')->utc();
        $row = fn (GpuNode $n, array $a) => GpuJobEarning::factory()->forNode($n)->create($a);

        $todayJob = $row($node, ['amount_satang' => 150, 'prompt_id' => 'p-today', 'completed_at' => $at('2026-09-26 01:10')]);
        $row($node, ['amount_satang' => 200, 'status' => 'cleared', 'completed_at' => $at('2026-09-25 23:50')]);
        $row($node, ['amount_satang' => 300, 'status' => 'paid', 'completed_at' => $at('2026-09-20 10:00')]);
        $row($node, ['amount_satang' => 50, 'status' => 'review', 'completed_at' => $at('2026-09-26 00:30')]);
        $row($node, ['amount_satang' => 999, 'status' => 'void', 'completed_at' => $at('2026-09-26 00:40')]);
        $row($node, ['amount_satang' => 0, 'free_share' => true, 'donated_value_satang' => 80, 'status' => 'paid', 'completed_at' => $at('2026-09-10 10:00')]);
        $row($node, ['amount_satang' => 0, 'donated_value_satang' => 70, 'status' => 'paid', 'completed_at' => $at('2026-08-01 10:00')]);
        // เครื่องอื่นของเจ้าของคนเดียวกัน: นับในยอดบัญชี แต่ไม่อยู่ในรายการงานของเครื่องนี้
        $row($other, ['amount_satang' => 400, 'completed_at' => $at('2026-09-26 00:05')]);
        // งานของคนอื่นที่ worker_id ตรงกันโดยบังเอิญ ต้องไม่หลุดมา
        GpuJobEarning::factory()->create(['worker_id' => $node->worker_id, 'amount_satang' => 5000]);

        $response = $this->askStatus($node)->assertOk()->assertJsonPath('success', true);

        $response->assertJsonPath('data.node', [
            'dispatch_status' => 'eligible',
            'dispatch_note' => null,
            'dispatch_worker_status' => 'ready',
            'relay_online' => true,
            'suspended' => false,
            'suspended_reason' => null,
            'last_seen_at' => $node->last_seen_at->toIso8601String(),
        ]);

        $response->assertJsonPath('data.earnings', [
            'pending_satang' => 550,          // 150 + 400
            'review_satang' => 50,
            'cleared_satang' => 200,
            'paid_satang' => 300,
            'today_satang' => 600,            // 150 + 50 + 400 (void ไม่นับ, 23:50 เมื่อวานไม่นับ)
            'month_satang' => 1100,           // + 200 + 300 + 0
            'donated_satang_30d' => 80,       // 70 เกินสามสิบวันแล้ว
            'wallet_balance_satang' => 12345,
            'hold_hours' => 12,
        ]);

        $jobs = $response->json('data.jobs');
        $this->assertCount(7, $jobs);
        $this->assertSame([
            'job_id' => $todayJob->job_id,
            'prompt_id' => 'p-today',
            'kind' => 'image',
            'amount_satang' => 150,
            'donated_value_satang' => 0,
            'status' => 'pending',
            'completed_at' => $todayJob->completed_at->toIso8601String(),
            'updated_at' => $todayJob->updated_at->toIso8601String(),
        ], $jobs[0]);
        // ห้าสิบงานล่าสุดแบบเดิม ไม่มี jobs_more — โปรแกรมรุ่นเก่าเห็นคำตอบรูปเดิม
        $this->assertArrayNotHasKey('jobs_more', $response->json('data'));
        $this->assertNotContains(5000, array_column($jobs, 'amount_satang'));
        $this->assertNotContains(400, array_column($jobs, 'amount_satang'));
    }

    public function test_a_new_owner_gets_zeros_not_errors(): void
    {
        $node = GpuNode::factory()->paired()->create();

        $this->askStatus($node)
            ->assertOk()
            ->assertJsonPath('data.node.relay_online', false)
            ->assertJsonPath('data.node.dispatch_status', null)
            ->assertJsonPath('data.node.last_seen_at', null)
            ->assertJsonPath('data.earnings.pending_satang', 0)
            ->assertJsonPath('data.earnings.wallet_balance_satang', 0)
            ->assertJsonPath('data.earnings.hold_hours', 24)
            ->assertJsonPath('data.jobs', []);
    }

    public function test_a_suspended_machine_is_told_so_with_the_reason(): void
    {
        $node = GpuNode::factory()->paired()->create([
            'suspended_at' => now(),
            'suspended_reason' => 'ผลงานไม่ตรงกับที่สั่ง',
        ]);

        $this->askStatus($node)
            ->assertOk()
            ->assertJsonPath('data.node.suspended', true)
            ->assertJsonPath('data.node.suspended_reason', 'ผลงานไม่ตรงกับที่สั่ง');
    }

    public function test_the_job_list_stops_at_fifty_newest_first(): void
    {
        $node = GpuNode::factory()->paired()->create();
        foreach (range(1, 55) as $i) {
            GpuJobEarning::factory()->forNode($node)->create(['completed_at' => now()->subMinutes($i)]);
        }

        $jobs = $this->askStatus($node)->assertOk()->json('data.jobs');

        $this->assertCount(50, $jobs);
        $this->assertGreaterThan($jobs[1]['completed_at'], $jobs[0]['completed_at']);
    }

    public function test_a_wrong_identity_gets_401_without_leaking_anything(): void
    {
        $node = GpuNode::factory()->paired()->create();
        GpuJobEarning::factory()->forNode($node)->create();

        $this->askStatus($node, 'not-the-token')
            ->assertStatus(401)
            ->assertExactJson(['success' => false, 'message' => 'ตัวตนเครื่องไม่ถูกต้อง']);

        $this->postJson(self::URL, ['worker_id' => 'gxm-nobody', 'token' => 'x'])->assertStatus(401);

        // aixman ถือกุญแจอุโมงค์ ไม่ใช่กุญแจของเครื่อง — ใช้ถามแทนเครื่องไม่ได้
        $tunnelled = GpuNode::factory()->paired()->withTunnelToken()->create();
        $this->askStatus($tunnelled, $tunnelled->tunnel_token)->assertStatus(401);

        // เครื่องที่เจ้าของถอนแล้วต้องจับคู่ใหม่
        $node->delete();
        $this->askStatus($node)->assertStatus(401);
    }

    public function test_a_missing_field_is_a_validation_error_not_a_crash(): void
    {
        $this->postJson(self::URL, [])->assertStatus(422)->assertJsonValidationErrors(['worker_id', 'token']);
    }

    // ── งานที่เปลี่ยนตั้งแต่ครั้งก่อน (updated_after) ─────────────────────────

    private function askChanges(GpuNode $node, ?string $after)
    {
        return $this->postJson(self::URL, [
            'worker_id' => $node->worker_id,
            'token' => $node->relay_token,
            'updated_after' => $after,
        ]);
    }

    public function test_a_fast_machine_sees_its_old_jobs_clear_pay_and_void_beyond_the_newest_fifty(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
        $node = GpuNode::factory()->paired()->create();
        $old = GpuJobEarning::factory()->forNode($node)->create(['prompt_id' => 'p-oldest', 'completed_at' => now()->subHours(30)]);
        $voided = GpuJobEarning::factory()->forNode($node)->create(['prompt_id' => 'p-voided', 'completed_at' => now()->subHours(29)]);
        foreach (range(1, 60) as $i) {
            GpuJobEarning::factory()->forNode($node)->create(['completed_at' => now()->subMinutes($i)]);
        }

        $first = $this->askStatus($node)->assertOk()->json('data.jobs');
        $this->assertCount(50, $first);
        $this->assertNotContains('p-oldest', array_column($first, 'prompt_id'), 'the old way never showed it');
        $cursor = max(array_column($first, 'updated_at'));

        // ชั่วโมงถัดมา: งานเก่าพ้นระยะพักแล้วถูกโอน อีกงานถูกแอดมินยกเลิก
        Carbon::setTestNow(now()->addHour());
        GpuJobEarning::whereKey($old->id)->update(['status' => GpuJobEarning::STATUS_PAID, 'amount_satang' => 170]);
        Carbon::setTestNow(now()->addSeconds(5));
        GpuJobEarning::whereKey($voided->id)->update(['status' => GpuJobEarning::STATUS_VOID]);
        Carbon::setTestNow(now()->addMinutes(3));

        $response = $this->askChanges($node, $cursor)->assertOk();
        $jobs = $response->json('data.jobs');

        $this->assertSame(['p-oldest', 'p-voided'], array_column($jobs, 'prompt_id'), 'oldest change first');
        $this->assertSame(['paid', 'void'], array_column($jobs, 'status'));
        $this->assertSame(170, $jobs[0]['amount_satang']);
        $this->assertFalse($response->json('data.jobs_more'));
        // ยอดเงินของบัญชียังมาทุกครั้ง ไม่ขึ้นกับเคอร์เซอร์
        $this->assertSame(170, $response->json('data.earnings.paid_satang'));

        // ถามต่อจากค่ามากสุดที่เห็น: ไม่มีอะไรใหม่ = รายการว่าง ไม่ใช่ห้าสิบงานเดิม
        $this->askChanges($node, max(array_column($jobs, 'updated_at')))
            ->assertOk()
            ->assertJsonPath('data.jobs', [])
            ->assertJsonPath('data.jobs_more', false);
    }

    public function test_changes_from_the_last_two_minutes_wait_for_the_next_poll(): void
    {
        // แถวที่ทรานแซกชันอื่นคิดเวลาไว้ก่อนแต่ commit ทีหลัง หรือ aixman เขียนด้วยนาฬิกาที่ช้า
        // ต้องไม่มีเวลาน้อยกว่าเคอร์เซอร์ที่โปรแกรมเพิ่งจำไป
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
        $node = GpuNode::factory()->paired()->create();
        $job = GpuJobEarning::factory()->forNode($node)->create();
        $cursor = now()->subMinute()->toIso8601String();

        Carbon::setTestNow(now()->addSeconds(30));
        $this->askChanges($node, $cursor)->assertOk()->assertJsonPath('data.jobs', []);

        Carbon::setTestNow(now()->addMinutes(2));
        $this->askChanges($node, $cursor)->assertOk()->assertJsonPath('data.jobs.0.job_id', $job->job_id);
    }

    public function test_a_page_never_cuts_through_one_second_of_changes(): void
    {
        // ตัวปล่อยเงินเปลี่ยนสถานะทีละห้าร้อยแถวใน UPDATE เดียว — ทุกแถวได้ updated_at วินาทีเดียวกัน
        // หน้าที่ตัดกลางวินาทีทำให้โปรแกรมที่ถามต่อ "หลังค่ามากสุดที่เห็น" ข้ามส่วนที่เหลือไปตลอด
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
        $node = GpuNode::factory()->paired()->create();
        $before = GpuJobEarning::factory()->forNode($node)->create();
        $ids = GpuJobEarning::factory()->forNode($node)->count(230)->create()->modelKeys();
        $cursor = now()->subSecond()->toIso8601String();

        Carbon::setTestNow(now()->addHour());
        GpuJobEarning::whereKey($ids)->update(['status' => GpuJobEarning::STATUS_CLEARED]);
        Carbon::setTestNow(now()->addMinutes(3));

        $response = $this->askChanges($node, $cursor)->assertOk();
        $jobs = $response->json('data.jobs');

        // หน้าแรก: แถวที่ยังไม่เปลี่ยน (วินาทีก่อน) + วินาทีที่ปล่อยเงินทั้งกลุ่ม ไม่ใช่แค่สองร้อยแถวแรก
        $this->assertCount(231, $jobs);
        $this->assertSame($before->job_id, $jobs[0]['job_id']);
        $this->assertSame(230, count(array_filter($jobs, fn ($j) => $j['status'] === 'cleared')));
        $this->assertTrue($response->json('data.jobs_more'));

        $this->askChanges($node, max(array_column($jobs, 'updated_at')))
            ->assertOk()
            ->assertJsonPath('data.jobs', []);
    }

    public function test_changes_of_another_machine_or_owner_never_leak_through_the_cursor(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
        $node = GpuNode::factory()->paired()->create();
        $sibling = GpuNode::factory()->paired()->create(['user_id' => $node->user_id]);
        GpuJobEarning::factory()->forNode($sibling)->create();
        GpuJobEarning::factory()->create(['worker_id' => $node->worker_id]);   // เจ้าของอื่น worker_id ชนกัน
        $cursor = now()->subMinute()->toIso8601String();
        Carbon::setTestNow(now()->addMinutes(5));

        $this->askChanges($node, $cursor)->assertOk()->assertJsonPath('data.jobs', []);
    }

    public function test_an_unreadable_updated_after_is_a_readable_422_and_a_stranger_still_gets_401(): void
    {
        $node = GpuNode::factory()->paired()->create();

        $this->askChanges($node, 'yesterday-ish?')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['updated_after'])
            ->assertJsonPath('errors.updated_after.0', 'updated_after ต้องเป็นวันเวลาแบบ ISO 8601 ที่ได้จาก updated_at ของงาน');

        $this->postJson(self::URL, ['worker_id' => $node->worker_id, 'token' => 'wrong', 'updated_after' => 'nope'])
            ->assertStatus(401);

        // ค่าว่าง = ไม่ได้ส่ง ได้ห้าสิบงานล่าสุดแบบเดิม
        $this->askChanges($node, '')->assertOk()->assertJsonMissingPath('data.jobs_more');
    }

    // ── โควตา ─────────────────────────────────────────────────────────

    public function test_the_status_route_has_its_own_budget_per_machine_not_per_address(): void
    {
        $middleware = Route::getRoutes()->getByName('api.gpuxmine.status')->gatherMiddleware();

        $this->assertContains('throttle:gpuxmine-status', $middleware);
        // throttle:api ของทั้งกลุ่มนับต่อ IP — ถ้ายังอยู่ ร้านที่มีหลายเครื่องก็ชน 429 เหมือนเดิม
        $this->assertNotContains('throttle:api', $middleware);
    }

    public function test_many_machines_behind_one_router_each_keep_their_own_budget(): void
    {
        $nodes = GpuNode::factory()->paired()->count(16)->create();

        // สิบหกเครื่องหลัง IP เดียว ถามสี่รอบในนาทีเดียว (ทุกสิบห้าวินาที) = 64 ครั้ง — เกินทั้ง
        // throttle:30,1 ต่อ IP เดิม และ throttle:api 60 ต่อนาทีต่อ IP ของทั้งกลุ่ม เคยโดน 429 ทั้งร้าน
        foreach (range(1, 4) as $round) {
            foreach ($nodes as $node) {
                $this->askStatus($node)->assertOk();
            }
        }

        // แต่เครื่องเดียวที่ถามรัวเกินสิบครั้งต่อนาทีถูกเบรก ด้วยข้อความที่อ่านออก
        $noisy = $nodes->first();
        foreach (range(5, 10) as $i) {
            $this->askStatus($noisy)->assertOk();
        }
        $this->askStatus($noisy)
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED')
            ->assertHeader('Retry-After');

        // เครื่องอื่นในร้านยังถามได้
        $this->askStatus($nodes->last())->assertOk();
    }
}
