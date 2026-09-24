<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\GpuNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * จับคู่ ลงทะเบียนใหม่ และถอนเครื่อง GPUxMINE
 *
 *   — relay ตอบไม่ได้ ≠ relay ไม่รู้จัก: เคยออก worker ใหม่ทิ้งตัวเดิมเป็นกำพร้า
 *   — worker เก่าที่ถูกแทนและเครื่องที่เจ้าของถอน ต้องออกจาก aixman และ relay
 *     จริง ๆ ไม่ใช่ยิงแล้วไม่ดูผล
 *   — รหัสจับคู่ใช้ได้ครั้งเดียวแม้กดพร้อมกันสองที่
 *   — relay รุ่นใหม่ออกกุญแจสองใบ: เครื่องได้ใบ /agent, aixman ได้ใบ /w/
 *   — เพดานเครื่องต่อบัญชี และผู้แนะนำที่จับไว้ตอนจับคู่
 */
class GpuxMinePairingTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    private string $machineId;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        $this->fakeGpuxMine();

        $this->machineId = Str::lower(Str::random(40));
        $this->enrolment = [
            'workerId' => 'gxm-new000000001',
            'token' => 'agent-token-new',
            'tunnelToken' => 'tunnel-token-new',
            'agentRelayUrl' => 'wss://relay.example.test:8443/agent',
            'aixmanEndpoint' => $this->relayBase . '/w/gxm-new000000001',
        ];
    }

    private function pendingCode(User $owner, array $attributes = []): GpuNode
    {
        return GpuNode::factory()->create(['user_id' => $owner->id] + $attributes);
    }

    private function claim(string $code, ?string $machineId = null): TestResponse
    {
        return $this->postJson('/api/v1/product/gpuxmine/claim', [
            'pairing_code' => $code,
            'machine_id' => $machineId ?? $this->machineId,
            'machine_name' => 'ห้องนอน',
            'app_version' => '0.2.0',
        ]);
    }

    // ── การจับคู่ครั้งแรก ─────────────────────────────────────────────

    public function test_a_new_machine_gets_the_agent_token_and_aixman_gets_the_tunnel_token(): void
    {
        $owner = User::factory()->create();
        $pending = $this->pendingCode($owner);

        $this->claim($pending->pairing_code)
            ->assertOk()
            ->assertJsonPath('data.worker_id', 'gxm-new000000001')
            ->assertJsonPath('data.token', 'agent-token-new');

        $node = $pending->fresh();
        $this->assertNotNull($node->paired_at);
        $this->assertNull($node->pairing_code);
        $this->assertSame('agent-token-new', $node->relay_token);
        $this->assertSame('tunnel-token-new', $node->tunnel_token);

        $pushes = $this->aixmanPushes();
        $this->assertCount(1, $pushes);
        $this->assertSame('tunnel-token-new', $pushes[0]['token']);
        $this->assertSame($this->relayBase . '/w/gxm-new000000001', $pushes[0]['endpoint']);
    }

    public function test_an_old_relay_without_a_tunnel_token_keeps_the_single_token_for_both(): void
    {
        unset($this->enrolment['tunnelToken']);
        $owner = User::factory()->create();
        $pending = $this->pendingCode($owner);

        $this->claim($pending->pairing_code)->assertOk();

        $this->assertNull($pending->fresh()->tunnel_token);
        $this->assertSame('agent-token-new', $this->aixmanPushes()[0]['token']);
    }

    public function test_a_code_cannot_be_redeemed_twice(): void
    {
        $owner = User::factory()->create();
        $pending = $this->pendingCode($owner);

        $this->claim($pending->pairing_code)->assertOk();
        $this->claim($pending->pairing_code, Str::lower(Str::random(40)))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAIRING_INVALID');

        $this->assertSame(1, $this->enrolments());
    }

    public function test_a_code_another_request_is_redeeming_right_now_is_refused_without_enrolling(): void
    {
        $owner = User::factory()->create();
        $pending = $this->pendingCode($owner);

        // คำขอแรกจองรหัสไว้แล้ว (UPDATE แบบมีเงื่อนไขชนะไปแล้ว) และยังคุยกับ relay อยู่
        GpuNode::whereKey($pending->id)->update(['pairing_expires_at' => null]);

        $this->claim($pending->pairing_code)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PAIRING_INVALID');

        $this->assertSame(0, $this->enrolments());
    }

    public function test_a_relay_that_refuses_enrolment_gives_the_code_back_for_a_retry(): void
    {
        $owner = User::factory()->create();
        $pending = $this->pendingCode($owner);
        $enrolment = $this->enrolment;

        $this->enrolment = null;
        $this->claim($pending->pairing_code)
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'RELAY_UNAVAILABLE');
        $this->assertTrue($pending->fresh()->pairingIsUsable());

        $this->enrolment = $enrolment;
        $this->claim($pending->pairing_code)->assertOk();
        $this->assertNotNull($pending->fresh()->paired_at);
    }

    // ── เครื่องเดิมกลับมาจับคู่ ───────────────────────────────────────

    public function test_a_returning_machine_the_relay_knows_is_reused_and_pushed_with_the_current_address(): void
    {
        $owner = User::factory()->create();
        $existing = GpuNode::factory()->paired()->create([
            'user_id' => $owner->id,
            'machine_id' => $this->machineId,
            'tunnel_endpoint' => 'https://old-relay.example.test/w/x',
            'dispatch_status' => 'eligible',
        ]);
        $pending = $this->pendingCode($owner);
        $this->relayWorkers = [$this->liveWorker($existing->worker_id)];

        $this->claim($pending->pairing_code)
            ->assertOk()
            ->assertJsonPath('data.worker_id', $existing->worker_id)
            ->assertJsonPath('data.token', $existing->relay_token);

        $this->assertSame(0, $this->enrolments());
        $this->assertSoftDeleted($pending);
        $this->assertSame($this->relayBase . '/w/' . $existing->worker_id, $existing->fresh()->tunnel_endpoint);

        // ที่อยู่เปลี่ยน aixman ต้องได้ยินทันที ไม่ใช่รอให้ตัวจับเวลาสังเกตเอง
        $this->assertCount(1, $this->aixmanPushes());
        $this->assertSame($this->relayBase . '/w/' . $existing->worker_id, $this->aixmanPushes()[0]['endpoint']);
    }

    public function test_a_relay_that_cannot_be_read_never_triggers_a_reenrolment(): void
    {
        $owner = User::factory()->create();
        $existing = GpuNode::factory()->paired()->create(['user_id' => $owner->id, 'machine_id' => $this->machineId]);
        $pending = $this->pendingCode($owner);
        $this->relayWorkers = null;

        $this->claim($pending->pairing_code)
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'RELAY_UNAVAILABLE');

        $this->assertSame(0, $this->enrolments());
        $this->assertSame($existing->worker_id, $existing->fresh()->worker_id);
        $this->assertSame([], $this->aixmanRetires());
        // ลองใหม่ได้ด้วยรหัสเดิม
        $this->assertTrue($pending->fresh()->pairingIsUsable());
    }

    public function test_reenrolment_retires_the_replaced_worker_at_aixman_and_the_relay(): void
    {
        $owner = User::factory()->create();
        $existing = GpuNode::factory()->paired()->online()->assessed()->withTunnelToken()->create([
            'user_id' => $owner->id,
            'machine_id' => $this->machineId,
            'dispatch_status' => 'eligible',
            'dispatch_fingerprint' => str_repeat('f', 40),
        ]);
        $oldWorker = $existing->worker_id;
        $pending = $this->pendingCode($owner);
        $this->relayWorkers = [];   // relay ตัวใหม่ไม่รู้จัก worker เดิม

        $this->claim($pending->pairing_code)
            ->assertOk()
            ->assertJsonPath('data.worker_id', 'gxm-new000000001')
            ->assertJsonPath('data.token', 'agent-token-new');

        $existing->refresh();
        $this->assertSame('gxm-new000000001', $existing->worker_id);
        $this->assertSame('tunnel-token-new', $existing->tunnel_token);
        $this->assertSoftDeleted($pending);

        $this->assertSame([$oldWorker], $this->aixmanRetires());
        $this->assertSame([$oldWorker], $this->relayDeletes());

        // worker เก่าอยู่ในแถวสำรองที่ soft delete — aixman ยังหาเจ้าของงานที่ค้างได้
        $tomb = GpuNode::onlyTrashed()->where('worker_id', $oldWorker)->first();
        $this->assertNotNull($tomb);
        $this->assertSame($owner->id, $tomb->user_id);
        $this->assertSame(GpuNode::RETIRE_DONE, $tomb->retire_status);
        $this->assertNull($tomb->relay_token);
        $this->assertNull($tomb->tunnel_token);

        $this->assertSame('tunnel-token-new', $this->aixmanPushes()[0]['token']);
    }

    public function test_a_replaced_worker_that_cannot_be_retired_now_is_retried_by_the_cron(): void
    {
        $owner = User::factory()->create();
        $existing = GpuNode::factory()->paired()->create(['user_id' => $owner->id, 'machine_id' => $this->machineId]);
        $oldWorker = $existing->worker_id;
        $pending = $this->pendingCode($owner);
        $this->relayWorkers = [];
        $this->aixmanRetireStatus = 500;

        $this->claim($pending->pairing_code)->assertOk();

        $tomb = GpuNode::onlyTrashed()->where('worker_id', $oldWorker)->first();
        $this->assertSame(GpuNode::RETIRE_PENDING, $tomb->retire_status);

        $this->aixmanRetireStatus = 200;
        $this->artisan('gpuxmine:sync-nodes')->run();

        $this->assertSame(GpuNode::RETIRE_DONE, $tomb->fresh()->retire_status);
    }

    // ── ถอนเครื่อง ────────────────────────────────────────────────────

    public function test_forgetting_a_machine_retires_it_at_aixman_and_revokes_it_at_the_relay(): void
    {
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->delete('/gpuxmine/' . $node->id)->assertRedirect();

        $this->assertSoftDeleted($node);
        $this->assertSame(GpuNode::RETIRE_DONE, GpuNode::withTrashed()->find($node->id)->retire_status);
        $this->assertSame([$node->worker_id], $this->aixmanRetires());
        $this->assertSame([$node->worker_id], $this->relayDeletes());
    }

    public function test_forgetting_while_aixman_refuses_leaves_the_retirement_pending(): void
    {
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->create(['user_id' => $owner->id]);
        $this->aixmanRetireStatus = 500;

        $this->actingAs($owner)->delete('/gpuxmine/' . $node->id)->assertRedirect();

        $this->assertSoftDeleted($node);
        $this->assertSame(GpuNode::RETIRE_PENDING, GpuNode::withTrashed()->find($node->id)->retire_status);
    }

    public function test_an_owner_cannot_forget_someone_elses_machine(): void
    {
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->create();

        $this->actingAs($owner)->delete('/gpuxmine/' . $node->id)->assertNotFound();

        $this->assertNotSoftDeleted($node);
        $this->assertSame([], $this->aixmanRetires());
    }

    // ── เพดานเครื่องต่อบัญชี (D9) ─────────────────────────────────────

    public function test_the_node_cap_refuses_a_new_pairing_code_on_the_web(): void
    {
        config(['services.gpuxmine.max_nodes_per_user' => 2]);
        $owner = User::factory()->create();
        GpuNode::factory()->paired()->count(2)->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->post('/gpuxmine/pair')
            ->assertRedirect()
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ครบ 2 เครื่อง'));

        $this->assertSame(0, GpuNode::where('user_id', $owner->id)->whereNull('paired_at')->count());
    }

    public function test_the_node_cap_refuses_a_new_machine_at_claim_but_not_a_returning_one(): void
    {
        config(['services.gpuxmine.max_nodes_per_user' => 1]);
        $owner = User::factory()->create();
        $existing = GpuNode::factory()->paired()->create(['user_id' => $owner->id, 'machine_id' => $this->machineId]);
        $this->relayWorkers = [$this->liveWorker($existing->worker_id)];

        // รหัสที่ออกไว้ก่อนถึงเพดาน แล้วเอาไปใช้กับเครื่องใหม่
        $pending = $this->pendingCode($owner);
        $this->claim($pending->pairing_code, Str::lower(Str::random(40)))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'NODE_LIMIT')
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'ครบ 1 เครื่อง'));
        $this->assertSame(0, $this->enrolments());

        // เครื่องเดิมที่ลงโปรแกรมใหม่ ไม่ได้เพิ่มจำนวน
        $this->claim($pending->pairing_code)->assertOk()->assertJsonPath('data.worker_id', $existing->worker_id);
    }

    public function test_a_cap_of_zero_means_no_cap(): void
    {
        config(['services.gpuxmine.max_nodes_per_user' => 0]);
        $owner = User::factory()->create();
        GpuNode::factory()->paired()->count(3)->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->post('/gpuxmine/pair')->assertRedirect()->assertSessionHas('pairing_code');
    }

    // ── ผู้แนะนำ (D8) ─────────────────────────────────────────────────

    private function affiliateFor(User $user, ?Affiliate $parent = null, string $status = 'active'): Affiliate
    {
        return Affiliate::create([
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'referral_code' => strtoupper(Str::random(8)),
            'commission_rate' => 10,
            'status' => $status,
        ]);
    }

    public function test_the_referral_link_the_owner_arrived_with_is_captured_at_pairing(): void
    {
        $referrer = User::factory()->create();
        $link = $this->affiliateFor($referrer);
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->withSession(['affiliate_ref' => $link->referral_code])
            ->post('/gpuxmine/pair')
            ->assertRedirect();

        $pending = GpuNode::where('user_id', $owner->id)->first();
        $this->assertSame($referrer->id, $pending->referrer_user_id);

        $this->claim($pending->pairing_code)->assertOk();

        $this->assertSame($referrer->id, $pending->fresh()->referrer_user_id);
        $this->assertSame($referrer->id, $this->aixmanPushes()[0]['referrerUserId']);
    }

    public function test_an_owner_never_refers_themselves(): void
    {
        $owner = User::factory()->create();
        $own = $this->affiliateFor($owner);

        $this->actingAs($owner)
            ->withSession(['affiliate_ref' => $own->referral_code])
            ->post('/gpuxmine/pair');

        $this->assertNull(GpuNode::where('user_id', $owner->id)->first()->referrer_user_id);
    }

    public function test_without_a_link_the_owners_own_upline_is_the_referrer(): void
    {
        $admin = User::factory()->admin()->create();
        $root = $this->affiliateFor($admin);
        $upline = User::factory()->create();
        $uplineAffiliate = $this->affiliateFor($upline, $root);
        $owner = User::factory()->create();
        $this->affiliateFor($owner, $uplineAffiliate);

        $pending = $this->pendingCode($owner);   // ออกรหัสมาก่อนมีระบบนี้ — ไม่มีผู้แนะนำติดมา
        $this->claim($pending->pairing_code)->assertOk();

        $this->assertSame($upline->id, $pending->fresh()->referrer_user_id);
    }

    public function test_the_admin_root_of_the_affiliate_tree_is_never_the_referrer(): void
    {
        $admin = User::factory()->admin()->create();
        $root = $this->affiliateFor($admin);
        $owner = User::factory()->create();
        $this->affiliateFor($owner, $root);

        $pending = $this->pendingCode($owner);
        $this->claim($pending->pairing_code)->assertOk();

        $this->assertNull($pending->fresh()->referrer_user_id);
    }

    public function test_the_referrer_is_captured_once_per_owner(): void
    {
        $first = User::factory()->create();
        $later = User::factory()->create();
        $laterLink = $this->affiliateFor($later);
        $owner = User::factory()->create();
        GpuNode::factory()->paired()->create(['user_id' => $owner->id, 'referrer_user_id' => $first->id]);

        $this->actingAs($owner)
            ->withSession(['affiliate_ref' => $laterLink->referral_code])
            ->post('/gpuxmine/pair');

        $this->assertSame($first->id, GpuNode::where('user_id', $owner->id)->whereNull('paired_at')->first()->referrer_user_id);
    }
}
