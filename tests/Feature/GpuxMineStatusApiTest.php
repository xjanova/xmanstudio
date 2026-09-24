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
        ], $jobs[0]);
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

    public function test_the_status_route_has_its_own_budget(): void
    {
        $middleware = Route::getRoutes()->getByName('api.gpuxmine.status')->gatherMiddleware();

        $this->assertContains('throttle:30,1,api-gpuxmine-status', $middleware);
    }
}
