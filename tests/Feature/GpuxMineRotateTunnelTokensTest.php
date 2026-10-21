<?php

namespace Tests\Feature;

use App\Models\GpuNode;
use App\Services\GpuxMineDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * gpuxmine:rotate-tunnel-tokens — ย้าย worker รุ่นกุญแจใบเดียวให้มีกุญแจอุโมงค์ของ aixman เอง
 *
 *   — เคยไม่มีทางเก็บกุญแจที่ relay ออกใหม่เลย คู่มือ relay สั่งให้ยิง rotate?only=tunnel
 *     ด้วยมือ แล้ว aixman ได้ 401 จากทุกเครื่องที่ถูกย้าย เพราะไม่มีใครเก็บใบใหม่
 *   — ใบใหม่ต้องถูกเก็บและถึง aixman ในขั้นเดียวกัน ส่งไม่ถึงตอนนี้ ตัวจับเวลาส่งซ้ำ
 *   — เครื่องที่กำลังเรนเดอร์ให้ลูกค้าไม่ถูกแตะ เว้นแต่สั่ง
 */
class GpuxMineRotateTunnelTokensTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->fakeGpuxMine();
    }

    private function rotate(array $options = []): int
    {
        return $this->artisan('gpuxmine:rotate-tunnel-tokens', $options)->run();
    }

    /** @return array<int, string> worker ที่ถูกขอกุญแจใหม่ */
    private function rotations(): array
    {
        return array_values(array_filter($this->relayAdminActions(), fn (string $a) => str_contains($a, '/rotate')));
    }

    public function test_a_single_token_worker_gets_its_own_tunnel_token_stored_and_pushed_at_once(): void
    {
        $legacy = GpuNode::factory()->paired()->create();
        $agentToken = $legacy->relay_token;
        // relay รุ่นใหม่ที่ยังไม่เปิดการแยก ตอบ tunnelToken = token
        $unsplit = GpuNode::factory()->paired()->create();
        $unsplit->forceFill(['tunnel_token' => $unsplit->relay_token])->save();
        $split = GpuNode::factory()->paired()->withTunnelToken()->create();

        $this->assertSame(0, $this->rotate());

        $this->assertSame([
            $legacy->worker_id . '/rotate?only=tunnel',
            $unsplit->worker_id . '/rotate?only=tunnel',
        ], $this->rotations());

        $legacy->refresh();
        $this->assertSame('tunnel-rotated-' . $legacy->worker_id, $legacy->tunnel_token);
        $this->assertSame($agentToken, $legacy->relay_token, 'the machine keeps its own token and stays connected');
        $this->assertSame('tunnel-rotated-' . $unsplit->worker_id, $unsplit->fresh()->tunnel_token);

        // aixman ได้ใบใหม่ทันที — ไม่ใช่ใบของเครื่อง
        $pushed = collect($this->aixmanPushes())->keyBy('workerId');
        $this->assertSame('tunnel-rotated-' . $legacy->worker_id, $pushed[$legacy->worker_id]['token']);
        $this->assertSame('tunnel-rotated-' . $unsplit->worker_id, $pushed[$unsplit->worker_id]['token']);
        $this->assertFalse($pushed->has($split->worker_id));
        $this->assertSame('eligible', $legacy->dispatch_status);

        // รันซ้ำ: ไม่มีอะไรเหลือให้ทำ
        $this->gpuxCalls = [];
        $this->assertSame(0, $this->rotate());
        $this->assertSame([], $this->rotations());
    }

    public function test_a_token_aixman_could_not_take_now_is_pushed_by_the_cron(): void
    {
        $node = GpuNode::factory()->paired()->create([
            'dispatch_status' => 'eligible',
            'dispatch_fingerprint' => str_repeat('a', 40),
            'dispatch_synced_at' => now(),
        ]);
        $this->aixmanStatus = null;

        $this->rotate();

        $node->refresh();
        $this->assertSame('tunnel-rotated-' . $node->worker_id, $node->tunnel_token, 'stored even though aixman was down');
        $this->assertSame('error', $node->dispatch_status);

        $this->aixmanStatus = 200;
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->gpuxCalls = [];
        $this->artisan('gpuxmine:sync-nodes')->run();

        $this->assertSame('tunnel-rotated-' . $node->worker_id, $this->aixmanPushes()[0]['token']);
    }

    public function test_a_push_from_a_copy_loaded_before_the_rotation_still_sends_the_new_token(): void
    {
        // ตัวจับเวลาโหลดแถวไว้ก่อนหมุนกุญแจ แล้วส่งทีหลัง — ต้องไม่ส่งกุญแจที่ relay ไม่รับแล้ว
        $node = GpuNode::factory()->paired()->create();
        $stale = GpuNode::find($node->id);

        $this->rotate();
        $this->gpuxCalls = [];
        app(GpuxMineDispatchService::class)->push($stale);

        $this->assertSame('tunnel-rotated-' . $node->worker_id, $this->aixmanPushes()[0]['token']);
    }

    public function test_two_runs_at_once_cannot_both_rotate(): void
    {
        GpuNode::factory()->paired()->create();
        $held = Cache::lock('gpuxmine:rotate-tunnel-tokens', 900);
        $held->get();

        $this->assertSame(1, $this->rotate());
        $this->assertSame([], $this->rotations());

        $held->release();
        $this->assertSame(0, $this->rotate());
        $this->assertCount(1, $this->rotations());
    }

    public function test_a_machine_rendering_for_a_customer_is_left_alone_unless_asked(): void
    {
        $busy = GpuNode::factory()->paired()->create(['busy' => true]);

        $this->rotate();
        $this->assertSame([], $this->rotations());
        $this->assertNull($busy->fresh()->tunnel_token);

        $this->rotate(['--include-busy' => true]);
        $this->assertSame([$busy->worker_id . '/rotate?only=tunnel'], $this->rotations());
    }

    public function test_a_dry_run_touches_nothing(): void
    {
        $node = GpuNode::factory()->paired()->create();

        $this->artisan('gpuxmine:rotate-tunnel-tokens', ['--dry-run' => true])
            ->expectsOutputToContain($node->worker_id)
            ->assertExitCode(0);

        $this->assertSame([], $this->gpuxCalls);
        $this->assertNull($node->fresh()->tunnel_token);
    }

    public function test_a_relay_without_the_command_stops_the_run_and_changes_nothing(): void
    {
        $node = GpuNode::factory()->paired()->create();
        GpuNode::factory()->paired()->create();
        $this->relayAdminActionStatus = 404;   // relay รุ่นก่อนไม่มี /rotate

        $this->assertSame(1, $this->rotate());

        $this->assertCount(1, $this->rotations(), 'one refusal is enough to know');
        $this->assertNull($node->fresh()->tunnel_token);
        $this->assertSame([], $this->aixmanPushes());
    }

    public function test_only_the_workers_asked_for_are_rotated(): void
    {
        $asked = GpuNode::factory()->paired()->create();
        GpuNode::factory()->paired()->create();

        $this->rotate(['--worker' => [$asked->worker_id]]);

        $this->assertSame([$asked->worker_id . '/rotate?only=tunnel'], $this->rotations());
    }

    public function test_the_doctor_fails_when_the_relay_split_a_token_this_app_does_not_hold(): void
    {
        // มีคนหมุนกุญแจเองที่ relay (curl ตามคู่มือเก่า) — aixman ถือใบเดิมที่ relay ไม่รับแล้ว
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [['hasTunnelToken' => true, 'disabled' => false] + $this->liveWorker($node->worker_id)];

        $this->artisan('gpuxmine:doctor')
            ->expectsOutputToContain('gpuxmine:rotate-tunnel-tokens')
            ->assertExitCode(1);
    }
}
