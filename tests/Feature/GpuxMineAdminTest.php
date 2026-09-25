<?php

namespace Tests\Feature;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GpuxMineDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * หน้าแอดมินของ GPUxMINE: ระงับ แบน ส่งซ้ำ และตัดสินรายได้
 *
 *   — ก่อนหน้านี้ไม่มีอะไรให้แอดมินกดเลย เครื่องที่ส่งผลงานปลอมอยู่ในคิวต่อไป
 *     และรายได้ที่ติด "รอตรวจสอบ" ไม่มีทางออก
 *   — ระงับต้องไปถึง aixman (suspended=true) และ relay และพักเงินของเครื่องนั้น
 *   — แบนต้องถอนเครื่องออกจริง และปิดทางจับคู่ใหม่ ทั้งเครื่องนั้นและบัญชีเจ้าของ
 *   — ยกเลิกรายได้ได้เฉพาะเงินที่ยังไม่เข้ากระเป๋า และรายการที่ยกเลิกไม่มีวันถูกจ่าย
 *   — ทุกเส้นทางเป็นของแอดมินเท่านั้น
 */
class GpuxMineAdminTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private GpuNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        $this->fakeGpuxMine();
        config(['services.gpuxmine.earning_hold_hours' => 24]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create(['email' => 'owner-of-the-card@example.test']);
        $this->node = GpuNode::factory()->paired()->online()->assessed()->withTunnelToken()->create([
            'user_id' => $this->owner->id,
            'label' => 'ริกหลังห้อง',
            'dispatch_status' => 'eligible',
            'dispatch_worker_status' => 'ready',
            'dispatch_synced_at' => now()->subMinute(),
        ]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    /** ยอดในกระเป๋าของเจ้าของเครื่อง — ยังไม่มีกระเป๋า = ศูนย์ */
    private function balance(): string
    {
        return (string) (Wallet::where('user_id', $this->owner->id)->value('balance') ?? '0.00');
    }

    private function settle(): int
    {
        return $this->artisan('gpuxmine:settle-earnings')->run();
    }

    private function earning(array $attributes = []): GpuJobEarning
    {
        return GpuJobEarning::factory()->forNode($this->node)->matured()->create($attributes);
    }

    private function claim(string $code, string $machineId): TestResponse
    {
        return $this->postJson('/api/v1/product/gpuxmine/claim', [
            'pairing_code' => $code,
            'machine_id' => $machineId,
            'machine_name' => 'เครื่องใหม่',
            'app_version' => '0.2.0',
        ]);
    }

    private function enrolmentFor(string $workerId): void
    {
        $this->enrolment = [
            'workerId' => $workerId,
            'token' => 'agent-token-' . $workerId,
            'tunnelToken' => 'tunnel-token-' . $workerId,
            'agentRelayUrl' => 'wss://relay.example.test:8443/agent',
            'aixmanEndpoint' => $this->relayBase . '/w/' . $workerId,
        ];
    }

    // ── สิทธิ์ ─────────────────────────────────────────────────────────

    public function test_every_admin_route_is_closed_to_members_and_guests(): void
    {
        $member = User::factory()->create();
        $earning = $this->earning(['status' => GpuJobEarning::STATUS_REVIEW]);
        $id = $this->node->id;

        $gets = ['/admin/gpuxmine', '/admin/gpuxmine/earnings', "/admin/gpuxmine/nodes/{$id}"];
        $posts = [
            "/admin/gpuxmine/nodes/{$id}/suspend" => ['reason' => 'ทดสอบสิทธิ์'],
            "/admin/gpuxmine/nodes/{$id}/resume" => [],
            "/admin/gpuxmine/nodes/{$id}/ban" => ['reason' => 'ทดสอบสิทธิ์'],
            "/admin/gpuxmine/nodes/{$id}/unban" => [],
            "/admin/gpuxmine/nodes/{$id}/resync" => [],
            "/admin/gpuxmine/earnings/{$earning->id}/approve" => [],
            "/admin/gpuxmine/earnings/{$earning->id}/void" => ['reason' => 'ทดสอบสิทธิ์'],
        ];

        foreach ($gets as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach ($posts as $url => $data) {
            $this->post($url, $data)->assertRedirect(route('login'));
        }

        $this->actingAs($member);
        foreach ($gets as $url) {
            $this->get($url)->assertForbidden();
        }
        foreach ($posts as $url => $data) {
            $this->post($url, $data)->assertForbidden();
        }

        $node = $this->node->fresh();
        $this->assertNull($node->suspended_at);
        $this->assertNull($node->banned_at);
        $this->assertFalse($node->trashed());
        $this->assertSame(GpuJobEarning::STATUS_REVIEW, $earning->fresh()->status);
        $this->assertSame([], $this->aixmanPushes(), 'a refused request must not reach aixman');
        $this->assertSame([], $this->aixmanRetires());
        $this->assertSame([], $this->relayAdminActions(), 'a refused request must not reach the relay');
        $this->assertSame([], $this->relayDeletes());
    }

    // ── รายการเครื่อง ──────────────────────────────────────────────────

    public function test_the_list_shows_every_node_with_its_owner_dispatch_state_and_money(): void
    {
        $this->node->forceFill([
            'dispatch_worker_status' => 'terminated',
            'dispatch_last_error' => 'probe failed: ComfyUI answered 503',
        ])->save();
        $this->earning(['amount_satang' => 150]);
        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 275, 'status' => GpuJobEarning::STATUS_PAID]);

        $removed = GpuNode::factory()->paired()->create(['label' => 'เครื่องที่ถอนไปแล้ว', 'retire_status' => GpuNode::RETIRE_PENDING]);
        $removed->delete();
        // aixman เขียนแถวโดยไม่มี gpu_node_id — ต้องนับให้เครื่องที่ worker ตรงกัน
        GpuJobEarning::factory()->create([
            'user_id' => $removed->user_id,
            'worker_id' => $removed->worker_id,
            'amount_satang' => 4200,
            'status' => GpuJobEarning::STATUS_PAID,
        ]);

        $this->asAdmin()->get('/admin/gpuxmine')
            ->assertOk()
            ->assertSee('ริกหลังห้อง')
            ->assertSee('owner-of-the-card@example.test')
            ->assertSee('probe failed: ComfyUI answered 503')
            ->assertSee('worker terminated')
            ->assertSee('฿2.75')          // เข้ากระเป๋าแล้วของเครื่องแรก
            ->assertSee('ยังไม่จ่าย ฿1.50')
            ->assertSee('เครื่องที่ถอนไปแล้ว')
            ->assertSee('ถอนยังไม่เสร็จ')
            ->assertSee('฿42.00');

        $this->asAdmin()->get('/admin/gpuxmine?state=removed')
            ->assertOk()
            ->assertSee('เครื่องที่ถอนไปแล้ว')
            ->assertDontSee('ริกหลังห้อง');

        $this->asAdmin()->get('/admin/gpuxmine?q=owner-of-the-card')
            ->assertOk()
            ->assertSee('ริกหลังห้อง')
            ->assertDontSee('เครื่องที่ถอนไปแล้ว');

        // ค่าแปลก ๆ ใน query string ไม่ใช่ 500
        $this->asAdmin()->get('/admin/gpuxmine?state[]=x&q[]=y')->assertOk();
    }

    public function test_the_detail_page_opens_for_live_and_removed_nodes(): void
    {
        $this->earning(['status' => GpuJobEarning::STATUS_REVIEW, 'review_reason' => 'ใช้เวลาน้อยผิดปกติ']);

        $this->asAdmin()->get("/admin/gpuxmine/nodes/{$this->node->id}")
            ->assertOk()
            ->assertSee('ริกหลังห้อง')
            ->assertSee('ระงับเครื่องนี้')
            ->assertSee('แบนเครื่องนี้')
            ->assertSee('ใช้เวลาน้อยผิดปกติ');

        $this->node->delete();
        $this->asAdmin()->get("/admin/gpuxmine/nodes/{$this->node->id}")
            ->assertOk()
            ->assertSee('ถอนออกจากระบบเมื่อ');
    }

    // ── ระงับ / ยกเลิกระงับ ────────────────────────────────────────────

    public function test_suspending_stops_dispatch_tells_the_relay_and_holds_the_money(): void
    {
        $matured = $this->earning(['amount_satang' => 500]);

        $this->asAdmin()->from("/admin/gpuxmine/nodes/{$this->node->id}")
            ->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ผลงานว่างเปล่าซ้ำ ๆ'])
            ->assertRedirect("/admin/gpuxmine/nodes/{$this->node->id}")
            ->assertSessionHas('success');

        $node = $this->node->fresh();
        $this->assertNotNull($node->suspended_at);
        $this->assertSame('ผลงานว่างเปล่าซ้ำ ๆ', $node->suspended_reason);
        $this->assertSame($this->admin->id, (int) $node->suspended_by);

        $pushes = $this->aixmanPushes();
        $this->assertCount(1, $pushes);
        $this->assertTrue($pushes[0]['suspended']);
        $this->assertSame([$node->worker_id . '/disable'], $this->relayAdminActions());

        // เงินของเครื่องที่ถูกระงับไม่ถูกปล่อย แม้จะพ้นระยะพักแล้ว
        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $matured->fresh()->status);
        $this->assertSame('0.00', $this->balance());

        // เจ้าของเห็นว่าถูกระงับและเพราะอะไร
        $this->actingAs($this->owner)->get('/gpuxmine')
            ->assertOk()
            ->assertSee('ผู้ดูแลระงับเครื่องนี้ไว้')
            ->assertSee('ผลงานว่างเปล่าซ้ำ ๆ');
    }

    public function test_suspending_needs_a_reason_and_happens_once(): void
    {
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertNull($this->node->fresh()->suspended_at);
        $this->assertSame([], $this->aixmanPushes());
        $this->assertSame([], $this->relayAdminActions());

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ครั้งแรก']);
        $first = $this->node->fresh()->suspended_at;

        $this->travel(5)->minutes();
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'กดซ้ำ'])
            ->assertSessionHas('error');

        $node = $this->node->fresh();
        $this->assertTrue($first->equalTo($node->suspended_at));
        $this->assertSame('ครั้งแรก', $node->suspended_reason);
        $this->assertCount(1, $this->aixmanPushes());
        $this->assertCount(1, $this->relayAdminActions());
    }

    public function test_a_failed_relay_or_aixman_call_still_suspends_and_the_cron_catches_up(): void
    {
        $this->aixmanStatus = null;          // ติดต่อ aixman ไม่ได้
        $this->relayAdminActionStatus = 404; // relay รุ่นเก่ายังไม่มีคำสั่งนี้

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ตรวจสอบ'])
            ->assertSessionHas('success', fn (string $m) => str_contains($m, 'แจ้ง aixman ไม่สำเร็จ') && str_contains($m, 'relay ไม่รับคำสั่ง'));

        $node = $this->node->fresh();
        $this->assertTrue($node->isSuspended());
        $this->assertSame('error', $node->dispatch_status);

        // aixman กลับมา: ตัวจับเวลาส่ง suspended=true ให้เอง
        $this->aixmanStatus = 200;
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->gpuxCalls = [];
        $this->artisan('gpuxmine:sync-nodes')->run();

        $pushes = $this->aixmanPushes();
        $this->assertCount(1, $pushes);
        $this->assertTrue($pushes[0]['suspended']);
    }

    public function test_resuming_lifts_the_suspension_everywhere_and_releases_the_money(): void
    {
        $matured = $this->earning(['amount_satang' => 500]);
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ตรวจสอบ']);
        $this->gpuxCalls = [];

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/resume")
            ->assertSessionHas('success');

        $node = $this->node->fresh();
        $this->assertNull($node->suspended_at);
        $this->assertNull($node->suspended_reason);
        $this->assertNull($node->suspended_by);
        $this->assertSame([$node->worker_id . '/enable'], $this->relayAdminActions());
        $this->assertFalse($this->aixmanPushes()[0]['suspended']);

        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_PAID, $matured->fresh()->status);

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/resume")
            ->assertSessionHas('error');
    }

    public function test_a_push_from_a_stale_copy_never_undoes_a_suspension_or_revives_a_removed_worker(): void
    {
        // ตัวจับเวลาโหลดแถวนี้ไว้แล้ว...
        $stale = GpuNode::find($this->node->id);

        // ...ระหว่างนั้นแอดมินระงับ
        GpuNode::whereKey($this->node->id)->update(['suspended_at' => now(), 'suspended_reason' => 'ตรวจสอบ']);
        app(GpuxMineDispatchService::class)->push($stale);
        $this->assertTrue($this->aixmanPushes()[0]['suspended']);

        // ...หรือเจ้าของถอนออก / แอดมินแบน — ไม่ส่งเลย
        $stale = GpuNode::find($this->node->id);
        GpuNode::whereKey($this->node->id)->update(['deleted_at' => now()]);
        $this->assertSame(GpuxMineDispatchService::OUTCOME_SKIPPED, app(GpuxMineDispatchService::class)->push($stale));
        $this->assertCount(1, $this->aixmanPushes());
    }

    // ── แบน ───────────────────────────────────────────────────────────

    public function test_a_ban_retires_the_worker_and_closes_every_way_back_in(): void
    {
        // รหัสที่เจ้าของขอไว้ก่อนโดนแบน — ต้องใช้ไม่ได้หลังแบน
        $earlierCode = GpuNode::factory()->create(['user_id' => $this->owner->id]);
        $machineId = $this->node->machine_id;
        $workerId = $this->node->worker_id;

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/ban", ['reason' => 'ส่งภาพปลอมเพื่อเก็บเงิน'])
            ->assertSessionHas('success');

        $node = GpuNode::withTrashed()->find($this->node->id);
        $this->assertTrue($node->trashed());
        $this->assertTrue($node->isBanned());
        $this->assertTrue($node->isSuspended(), 'a ban also holds the money');
        $this->assertSame(GpuNode::RETIRE_DONE, $node->retire_status);
        $this->assertSame([$workerId], $this->aixmanRetires());
        $this->assertSame([$workerId], $this->relayDeletes());

        // บัญชีเจ้าของขอรหัสใหม่ไม่ได้ และเห็นเหตุผล
        $this->actingAs($this->owner)->post('/gpuxmine/pair')->assertSessionHas('error');
        $this->assertSame(1, GpuNode::where('user_id', $this->owner->id)->whereNull('paired_at')->count());
        $this->actingAs($this->owner)->get('/gpuxmine')
            ->assertOk()
            ->assertSee('บัญชีนี้ถูกผู้ดูแลระงับการแชร์เครื่อง')
            ->assertSee('ส่งภาพปลอมเพื่อเก็บเงิน');

        // รหัสที่ออกไว้ก่อนแบน ใช้กับเครื่องใหม่ก็ไม่ได้
        $this->enrolmentFor('gxm-afterban0001');
        $this->claim($earlierCode->pairing_code, Str::lower(Str::random(40)))
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'OWNER_BANNED');

        // เครื่องเดิมไปจับคู่ในบัญชีอื่นก็ไม่ได้
        $other = User::factory()->create();
        $otherCode = GpuNode::factory()->create(['user_id' => $other->id]);
        $this->claim($otherCode->pairing_code, $machineId)
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'MACHINE_BLOCKED');

        $this->assertSame(0, $this->enrolments(), 'no worker may be issued to a banned machine or owner');
    }

    public function test_banning_twice_is_one_ban(): void
    {
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/ban", ['reason' => 'ครั้งแรก']);
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/ban", ['reason' => 'กดซ้ำ'])
            ->assertSessionHas('error');

        $this->assertSame('ครั้งแรก', GpuNode::withTrashed()->find($this->node->id)->banned_reason);
        $this->assertCount(1, $this->aixmanRetires());
    }

    public function test_a_ban_that_cannot_reach_the_relay_is_retried_by_the_cron(): void
    {
        $this->relayDeleteStatus = 500;

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/ban", ['reason' => 'ทดสอบ'])
            ->assertSessionHas('success', fn (string $m) => str_contains($m, 'ยังไม่ครบ'));
        $this->assertSame(GpuNode::RETIRE_PENDING, GpuNode::withTrashed()->find($this->node->id)->retire_status);

        $this->relayDeleteStatus = 200;
        $this->artisan('gpuxmine:sync-nodes')->run();

        $this->assertSame(GpuNode::RETIRE_DONE, GpuNode::withTrashed()->find($this->node->id)->retire_status);
    }

    public function test_unbanning_lets_the_owner_pair_that_machine_again(): void
    {
        $machineId = $this->node->machine_id;
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/ban", ['reason' => 'เข้าใจผิด']);

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/unban")
            ->assertSessionHas('success');

        $row = GpuNode::withTrashed()->find($this->node->id);
        $this->assertNull($row->banned_at);
        $this->assertNull($row->suspended_at);
        $this->assertTrue($row->trashed(), 'the retired worker does not come back');

        $code = GpuNode::factory()->create(['user_id' => $this->owner->id]);
        $this->enrolmentFor('gxm-unbanned0001');
        $this->claim($code->pairing_code, $machineId)->assertOk();
        $this->assertSame(1, $this->enrolments());
    }

    public function test_a_suspended_machine_cannot_escape_by_being_removed_and_paired_again(): void
    {
        $machineId = $this->node->machine_id;
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ตรวจสอบ']);

        $this->actingAs($this->owner)->delete("/gpuxmine/{$this->node->id}")
            ->assertSessionHas('success', fn (string $m) => str_contains($m, 'ถูกผู้ดูแลระงับไว้'));

        $code = GpuNode::factory()->create(['user_id' => $this->owner->id]);
        $this->enrolmentFor('gxm-escape000001');
        $this->claim($code->pairing_code, $machineId)
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'MACHINE_BLOCKED');
        $this->assertSame(0, $this->enrolments());
        // รหัสยังไม่ถูกใช้ — คืนให้จนหมดอายุเดิม
        $this->assertTrue($code->fresh()->pairingIsUsable());
    }

    public function test_a_suspended_machine_that_reinstalls_the_client_gets_its_own_worker_back_still_suspended(): void
    {
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/suspend", ['reason' => 'ตรวจสอบ']);
        $this->relayWorkers = [$this->liveWorker($this->node->worker_id)];

        $code = GpuNode::factory()->create(['user_id' => $this->owner->id]);
        $this->claim($code->pairing_code, $this->node->machine_id)
            ->assertOk()
            ->assertJsonPath('data.worker_id', $this->node->worker_id);

        $this->assertTrue($this->node->fresh()->isSuspended());
        $this->assertTrue(collect($this->aixmanPushes())->last()['suspended']);
    }

    // ── ส่งซ้ำ ─────────────────────────────────────────────────────────

    public function test_force_resync_reads_the_relay_and_pushes_even_when_nothing_changed(): void
    {
        $this->relayWorkers = [$this->liveWorker($this->node->worker_id, ['score' => 1500])];
        // aixman ตอบรับชุดนี้ไปแล้ว — ตัวจับเวลาจะไม่ส่งซ้ำ แต่แอดมินสั่งได้
        $this->node->forceFill([
            'dispatch_fingerprint' => app(GpuxMineDispatchService::class)->fingerprint($this->node),
        ])->save();

        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/resync")
            ->assertSessionHas('success');

        $this->assertSame(1, $this->relayListings());
        $pushes = $this->aixmanPushes();
        $this->assertCount(1, $pushes);
        $this->assertSame(1500, $pushes[0]['score']);
    }

    public function test_force_resync_on_a_removed_node_retries_its_stuck_retirement(): void
    {
        $this->relayDeleteStatus = 500;
        $this->actingAs($this->owner)->delete("/gpuxmine/{$this->node->id}");
        $this->assertSame(GpuNode::RETIRE_PENDING, GpuNode::withTrashed()->find($this->node->id)->retire_status);

        $this->relayDeleteStatus = 200;
        $this->asAdmin()->post("/admin/gpuxmine/nodes/{$this->node->id}/resync")
            ->assertSessionHas('success');

        $this->assertSame(GpuNode::RETIRE_DONE, GpuNode::withTrashed()->find($this->node->id)->retire_status);
        $this->assertSame([], $this->aixmanPushes(), 'a removed node is never registered again');
    }

    // ── ตัดสินรายได้ ───────────────────────────────────────────────────

    public function test_approving_a_review_past_the_hold_sends_it_to_the_next_transfer(): void
    {
        $earning = $this->earning(['amount_satang' => 800, 'status' => GpuJobEarning::STATUS_REVIEW, 'review_reason' => 'เร็วผิดปกติ']);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$earning->id}/approve")
            ->assertSessionHas('success');

        $earning->refresh();
        $this->assertSame(GpuJobEarning::STATUS_CLEARED, $earning->status);
        $this->assertNotNull($earning->cleared_at);
        $this->assertSame($this->admin->id, $earning->reviewed_by);
        $this->assertNotNull($earning->reviewed_at);
        $this->assertSame('เร็วผิดปกติ', $earning->review_reason, 'why it was flagged stays on record');

        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_PAID, $earning->fresh()->status);
        $this->assertSame('8.00', $this->balance());
    }

    public function test_approving_a_review_still_inside_the_hold_puts_it_back_on_hold(): void
    {
        $earning = GpuJobEarning::factory()->forNode($this->node)->create([
            'amount_satang' => 800,
            'status' => GpuJobEarning::STATUS_REVIEW,
            'completed_at' => now()->subHours(2),
        ]);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$earning->id}/approve")
            ->assertSessionHas('success');
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $earning->fresh()->status);

        $this->settle();
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $earning->fresh()->status);

        $this->travel(23)->hours();
        $this->settle();
        $this->assertSame(GpuJobEarning::STATUS_PAID, $earning->fresh()->status);
    }

    public function test_only_a_non_negative_review_can_be_approved(): void
    {
        $pending = $this->earning();
        $negative = $this->earning(['amount_satang' => -100, 'status' => GpuJobEarning::STATUS_REVIEW]);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$pending->id}/approve")->assertSessionHas('error');
        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$negative->id}/approve")->assertSessionHas('error');

        $this->assertSame(GpuJobEarning::STATUS_PENDING, $pending->fresh()->status);
        $this->assertNull($pending->fresh()->reviewed_by);
        $this->assertSame(GpuJobEarning::STATUS_REVIEW, $negative->fresh()->status);
    }

    public function test_a_voided_earning_records_why_and_is_never_paid(): void
    {
        $cleared = $this->earning(['amount_satang' => 900, 'status' => GpuJobEarning::STATUS_CLEARED, 'cleared_at' => now()]);
        $review = $this->earning(['amount_satang' => -100, 'status' => GpuJobEarning::STATUS_REVIEW]);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$cleared->id}/void", ['reason' => 'ภาพว่างเปล่า'])
            ->assertSessionHas('success');
        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$review->id}/void", ['reason' => 'ยอดติดลบจาก aixman'])
            ->assertSessionHas('success');

        $cleared->refresh();
        $this->assertSame(GpuJobEarning::STATUS_VOID, $cleared->status);
        $this->assertSame('ภาพว่างเปล่า', $cleared->void_reason);
        $this->assertSame($this->admin->id, $cleared->reviewed_by);
        $this->assertSame(GpuJobEarning::STATUS_VOID, $review->fresh()->status);

        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_VOID, $cleared->fresh()->status);
        $this->assertNull($cleared->fresh()->wallet_transaction_id);
        $this->assertSame('0.00', $this->balance());

        // เจ้าของเห็นว่ายกเลิกเพราะอะไร
        $this->actingAs($this->owner)->get('/gpuxmine')->assertOk()->assertSee('ภาพว่างเปล่า');
    }

    public function test_money_already_in_the_wallet_cannot_be_voided(): void
    {
        $paid = $this->earning(['status' => GpuJobEarning::STATUS_PAID, 'paid_at' => now()]);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$paid->id}/void", ['reason' => 'สายไปแล้ว'])
            ->assertSessionHas('error', fn (string $m) => str_contains($m, 'เข้ากระเป๋าไปแล้ว'));

        $this->assertSame(GpuJobEarning::STATUS_PAID, $paid->fresh()->status);
        $this->assertNull($paid->fresh()->void_reason);
    }

    public function test_voiding_needs_a_reason_and_keeps_the_first_one(): void
    {
        $earning = $this->earning();

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$earning->id}/void", ['reason' => ' '])
            ->assertSessionHasErrors('reason');
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $earning->fresh()->status);

        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$earning->id}/void", ['reason' => 'เหตุผลแรก']);
        $this->asAdmin()->post("/admin/gpuxmine/earnings/{$earning->id}/void", ['reason' => 'เหตุผลที่สอง'])
            ->assertSessionHas('error');

        $this->assertSame('เหตุผลแรก', $earning->fresh()->void_reason);
    }

    public function test_the_review_queue_lists_the_longest_waiting_first(): void
    {
        $newer = $this->earning(['status' => GpuJobEarning::STATUS_REVIEW, 'completed_at' => now()->subHours(26)]);
        $older = $this->earning(['status' => GpuJobEarning::STATUS_REVIEW, 'completed_at' => now()->subHours(50)]);
        $pending = $this->earning();

        $this->asAdmin()->get('/admin/gpuxmine/earnings')
            ->assertOk()
            ->assertSeeInOrder([$older->job_id, $newer->job_id])
            ->assertDontSee($pending->job_id)
            ->assertSee('อนุมัติ → รอโอน');

        $this->asAdmin()->get('/admin/gpuxmine/earnings?status=all&q=' . urlencode($pending->job_id))
            ->assertOk()
            ->assertSee($pending->job_id)
            ->assertDontSee($older->job_id);
    }
}
