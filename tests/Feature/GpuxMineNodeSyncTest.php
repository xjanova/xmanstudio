<?php

namespace Tests\Feature;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use App\Services\GpuxMineNodeStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * เครื่องที่แชร์อยู่ต้องไปถึง aixman และอยู่ที่นั่นต่อไป
 *
 * เดิมการส่งต่อเกิดเฉพาะเมื่อตัวจับเวลา "เห็นอะไรเปลี่ยน" เอง:
 *   — เจ้าของที่กด START แล้วเปิดหน้าเครื่องของฉัน ทำให้หน้าเว็บเขียนแถวไปก่อน
 *     ตัวจับเวลาเลยไม่เห็นอะไรเปลี่ยน และไม่เคยบอก aixman
 *   — ส่งไม่สำเร็จครั้งเดียวก็ไม่มีการลองใหม่ เพราะความล้มเหลวก็ถูกประทับเวลา
 *   — aixman ปิด worker ไปเองระหว่างทาง ไม่มีใครส่งซ้ำให้ฟื้น
 *   — relay ตอบไม่ได้ครั้งเดียว ทุกเครื่องถูกเขียนเป็นออฟไลน์ คะแนนหาย และถูก
 *     บอก aixman ให้ถอดทั้งกอง
 * ตอนนี้การส่งตัดสินจากสิ่งที่ aixman ตอบรับไปล่าสุด (dispatch_fingerprint)
 */
class GpuxMineNodeSyncTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        $this->fakeGpuxMine();
    }

    private function sync(bool $force = false): int
    {
        return $this->artisan('gpuxmine:sync-nodes', $force ? ['--force' => true] : [])->run();
    }

    public function test_a_node_that_just_came_online_is_pushed_with_the_contract_fields(): void
    {
        $referrer = User::factory()->create();
        $node = GpuNode::factory()->paired()->withTunnelToken()->create([
            'referrer_user_id' => $referrer->id,
            'paired_at' => now()->subDays(3),
        ]);
        // เครื่องแรกของเจ้าของคนนี้ ซึ่งถอนไปแล้ว — วันเริ่มนับของผู้แนะนำต้องมาจากเครื่องนี้
        $first = GpuNode::factory()->paired()->create(['user_id' => $node->user_id, 'paired_at' => now()->subDays(30)]);
        $first->delete();

        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['freeSharePct' => 25, 'busy' => false])];

        $this->assertSame(0, $this->sync());

        $pushes = $this->aixmanPushes();
        $this->assertCount(1, $pushes);
        $sent = $pushes[0];

        $this->assertSame($node->worker_id, $sent['workerId']);
        $this->assertTrue($sent['online']);
        $this->assertTrue($sent['assessed']);
        // aixman ได้กุญแจอุโมงค์ ไม่ใช่กุญแจที่เครื่องใช้ต่อ /agent
        $this->assertSame($node->tunnel_token, $sent['token']);
        $this->assertNotSame($node->relay_token, $sent['token']);
        $this->assertSame(25, $sent['freeSharePct']);
        $this->assertFalse($sent['pro']);
        $this->assertTrue($sent['accepting']);
        $this->assertFalse($sent['busy']);
        $this->assertSame($referrer->id, $sent['referrerUserId']);
        $this->assertSame($first->paired_at->toIso8601String(), $sent['firstPairedAt']);
        $this->assertFalse($sent['suspended']);
        $this->assertSame($node->user_id, $sent['ownerUserId']);

        $node->refresh();
        $this->assertSame('eligible', $node->dispatch_status);
        $this->assertSame('warming', $node->dispatch_worker_status);
        $this->assertNotNull($node->dispatch_fingerprint);
    }

    public function test_the_legacy_single_token_is_pushed_when_the_relay_issued_no_tunnel_token(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        $this->sync();

        $this->assertSame($node->relay_token, $this->aixmanPushes()[0]['token']);
    }

    public function test_the_owner_page_writing_the_row_first_does_not_swallow_the_push(): void
    {
        // ตอนจับคู่: aixman รับเครื่องไปแล้วในสภาพยังไม่เปิด ยังไม่ประเมิน
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id, online: false)];
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());
        $this->assertFalse($this->aixmanPushes()[0]['online']);

        // เจ้าของกด START แล้วเปิดหน้าเว็บ: หน้าเว็บเขียนสถานะสดลงแถวไปก่อน
        // (และรอบนี้ไม่ได้ส่งต่อ)
        $node->refresh();
        app(GpuxMineNodeStateService::class)->apply($node, $this->liveWorker($node->worker_id));
        $this->assertTrue($node->fresh()->online);
        $this->assertCount(1, $this->aixmanPushes());

        // ตัวจับเวลาไม่เห็นอะไร "เปลี่ยน" แล้ว แต่ aixman ยังไม่เคยได้ยิน — ต้องส่ง
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();

        $this->assertCount(2, $this->aixmanPushes());
        $this->assertTrue($this->aixmanPushes()[1]['online']);
        $this->assertTrue($this->aixmanPushes()[1]['assessed']);
    }

    public function test_an_acknowledged_node_with_nothing_new_is_not_pushed_again(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        $this->sync();
        $this->travel(2)->minutes();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];   // lastSeenAt ขยับ ไม่ใช่เหตุให้ส่ง
        $this->sync();

        $this->assertCount(1, $this->aixmanPushes());
    }

    public function test_a_score_wiggle_or_a_busy_flip_alone_does_not_push_but_a_lane_change_does(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['busy' => false])];
        $this->sync();

        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['score' => 1215, 'busy' => true])];
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());

        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['lanes' => ['image' => 'slow']])];
        $this->sync();
        $this->assertCount(2, $this->aixmanPushes());
        $this->assertSame(['image' => 'slow'], $this->aixmanPushes()[1]['lanes']);
    }

    public function test_a_failed_push_is_retried_on_the_next_run(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        $this->aixmanStatus = 422;
        $this->sync();
        $this->assertSame('error', $node->fresh()->dispatch_status);
        $this->assertNull($node->fresh()->dispatch_fingerprint);

        $this->aixmanStatus = 200;
        $this->sync();

        $this->assertSame('eligible', $node->fresh()->dispatch_status);
        $this->assertNotNull($node->fresh()->dispatch_fingerprint);
    }

    public function test_an_acknowledged_node_is_pushed_again_after_the_resync_window(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();

        $this->travel(11)->minutes();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();

        $this->assertCount(2, $this->aixmanPushes());
    }

    /** คำตอบของ POST /api/gpux/nodes จาก aixman บน main (ก่อนสัญญา C1) — มี worker.status แต่ไม่มี lastError */
    private function answerLikeTheOldAixman(): void
    {
        $this->aixmanBody = [
            'status' => 'eligible',
            'note' => null,
            'modelKey' => 'sdxl-community',
            'worker' => ['id' => 1, 'externalId' => 'x', 'status' => 'warming', 'modelKey' => 'sdxl-community'],
        ];
    }

    public function test_an_older_aixman_is_not_resynced_on_a_timer_even_though_it_reports_a_worker_status(): void
    {
        // aixman รุ่นก่อนสัญญานี้เขียนแถวที่กำลังเรนเดอร์เป็น warming ทุกครั้งที่ได้ข้อมูล
        // แล้วตัวเก็บกวาดของมันฆ่า worker ที่ warming นานเกินชั่วโมง — ส่งซ้ำตามรอบเวลาคือ
        // ฆ่างานกลางทาง รุ่นนั้นคืน worker.status อยู่แล้ว ตัวแยกรุ่นจึงต้องเป็น lastError
        $this->answerLikeTheOldAixman();
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();
        $this->assertNull($node->fresh()->dispatch_worker_status);
        $this->assertSame('eligible', $node->fresh()->dispatch_status);

        $this->travel(30)->minutes();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());

        // เจ้าของขยับเมาส์ / เปิดเกม — accepting พลิก ไม่ใช่เหตุให้ส่งกับรุ่นนี้
        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['accepting' => false])];
        $this->sync();
        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['accepting' => true])];
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());

        // ข้อมูลที่ aixman รุ่นนั้นใช้จริงเปลี่ยน — ยังส่งเหมือนที่เคยทำ
        $this->relayWorkers = [$this->liveWorker($node->worker_id, online: false)];
        $this->sync();
        $this->assertCount(2, $this->aixmanPushes());
    }

    public function test_the_current_aixman_hears_an_accepting_flip_and_the_timed_resync(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();
        $this->assertSame('warming', $node->fresh()->dispatch_worker_status);

        $this->relayWorkers = [$this->liveWorker($node->worker_id, ['accepting' => false])];
        $this->sync();
        $this->assertCount(2, $this->aixmanPushes());
        $this->assertFalse($this->aixmanPushes()[1]['accepting']);
    }

    public function test_an_upgraded_aixman_is_recognised_on_its_first_answer(): void
    {
        $this->answerLikeTheOldAixman();
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();
        $this->assertNull($node->fresh()->dispatch_worker_status);

        // aixman อัปเกรดแล้ว: ส่งครั้งถัดไป (เพราะข้อมูลเปลี่ยน) ได้คำตอบแบบใหม่
        $this->aixmanBody['worker']['lastError'] = null;
        $this->relayWorkers = [$this->liveWorker($node->worker_id, online: false)];
        $this->sync();
        $this->assertSame('warming', $node->fresh()->dispatch_worker_status);

        // จากนี้ส่งซ้ำตามรอบเวลาได้
        $this->travel(11)->minutes();
        $this->relayWorkers = [$this->liveWorker($node->worker_id, online: false)];
        $this->sync();
        $this->assertCount(3, $this->aixmanPushes());
    }

    public function test_an_unconfigured_aixman_is_recorded_once_and_pushed_as_soon_as_it_is_configured(): void
    {
        config(['services.aixman.webhook_secret' => null]);
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        $this->sync();
        $this->assertSame('unconfigured', $node->fresh()->dispatch_status);
        $stamped = $node->fresh()->dispatch_synced_at;

        $this->travel(5)->minutes();
        $this->sync();
        $this->assertEquals($stamped, $node->fresh()->dispatch_synced_at);
        $this->assertSame([], $this->aixmanPushes());

        config(['services.aixman.webhook_secret' => 'shared-webhook-secret-for-tests']);
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());
        $this->assertSame('eligible', $node->fresh()->dispatch_status);
    }

    public function test_aixman_reporting_the_worker_terminated_brings_a_push_back_early(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->aixmanBody['worker']['status'] = 'terminated';
        $this->aixmanBody['worker']['lastError'] = 'reaped: idle';
        $this->sync();

        $node->refresh();
        $this->assertSame('terminated', $node->dispatch_worker_status);
        $this->assertSame('reaped: idle', $node->dispatch_last_error);

        // ยังไม่ถึงสองนาที — ไม่ยิงถี่ เผื่อแอดมินฝั่ง aixman ปิดไว้เอง
        $this->travel(1)->minutes();
        $this->sync();
        $this->assertCount(1, $this->aixmanPushes());

        $this->travel(2)->minutes();
        $this->aixmanBody['worker']['status'] = 'warming';
        $this->aixmanBody['worker']['lastError'] = null;
        $this->sync();

        $this->assertCount(2, $this->aixmanPushes());
        $this->assertSame('warming', $node->fresh()->dispatch_worker_status);
        $this->assertNull($node->fresh()->dispatch_last_error);
    }

    public function test_a_relay_outage_fails_the_run_without_touching_any_node(): void
    {
        $node = GpuNode::factory()->paired()->online()->assessed()->create([
            'dispatch_status' => 'eligible',
        ]);
        $before = $node->fresh()->getAttributes();

        $this->relayWorkers = null;

        $this->assertSame(1, $this->sync());

        $this->assertSame($before, $node->fresh()->getAttributes());
        $this->assertSame([], $this->aixmanPushes());
    }

    public function test_an_offline_node_keeps_its_last_known_assessment(): void
    {
        $node = GpuNode::factory()->paired()->online()->assessed()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id, online: false)];

        $this->sync();

        $node->refresh();
        $this->assertFalse($node->online);
        $this->assertTrue($node->assessed);
        $this->assertSame(1200, $node->score);
        $this->assertSame('b', $node->tier);
        $this->assertSame(['image'], $node->can_run);
        $this->assertNull($node->accepting);

        $sent = $this->aixmanPushes()[0];
        $this->assertFalse($sent['online']);
        $this->assertTrue($sent['assessed']);
    }

    public function test_a_worker_the_relay_no_longer_knows_reads_offline_and_keeps_its_row(): void
    {
        $node = GpuNode::factory()->paired()->online()->assessed()->create();
        $this->relayWorkers = [];

        $this->assertSame(0, $this->sync());

        $this->assertFalse($node->fresh()->online);
        $this->assertSame(1200, $node->fresh()->score);
        $this->assertNotNull(GpuNode::find($node->id));
    }

    public function test_a_down_aixman_stops_the_run_after_a_few_nodes(): void
    {
        $nodes = GpuNode::factory()->paired()->count(5)->create();
        $this->relayWorkers = $nodes->map(fn ($n) => $this->liveWorker($n->worker_id))->all();
        $this->aixmanStatus = null;

        $this->assertSame(1, $this->sync());

        $tried = collect($this->aixmanPushes())->pluck('workerId')->unique();
        $this->assertCount(3, $tried);
        // ที่ยังไม่ได้ลองยังค้างเป็น "ต้องส่ง" — รอบหน้าหยิบต่อเอง
        $this->assertSame(2, GpuNode::whereNull('dispatch_status')->count());
    }

    public function test_a_suspended_node_is_pushed_as_suspended(): void
    {
        $node = GpuNode::factory()->paired()->create();
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];
        $this->sync();

        $node->forceFill(['suspended_at' => now(), 'suspended_reason' => 'test'])->save();
        $this->sync();

        $this->assertCount(2, $this->aixmanPushes());
        $this->assertTrue($this->aixmanPushes()[1]['suspended']);
    }

    public function test_pending_retirements_are_retried_until_both_sides_confirm(): void
    {
        $node = GpuNode::factory()->paired()->create(['retire_status' => GpuNode::RETIRE_PENDING]);
        $node->delete();

        $this->relayDeleteStatus = 500;
        $this->sync();
        $this->assertSame(GpuNode::RETIRE_PENDING, GpuNode::withTrashed()->find($node->id)->retire_status);

        $this->relayDeleteStatus = 200;
        $this->sync();

        $this->assertSame(GpuNode::RETIRE_DONE, GpuNode::withTrashed()->find($node->id)->retire_status);
        $this->assertSame([$node->worker_id, $node->worker_id], $this->aixmanRetires());
        $this->assertSame([$node->worker_id, $node->worker_id], $this->relayDeletes());
    }

    public function test_the_owner_page_reads_the_relay_through_a_short_cache_and_pushes_after_responding(): void
    {
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->create(['user_id' => $owner->id]);
        $this->relayWorkers = [$this->liveWorker($node->worker_id)];

        $this->actingAs($owner)->get('/gpuxmine')->assertOk();

        $this->assertSame(1, $this->relayListings());
        $this->assertTrue($node->fresh()->online);
        // ส่งต่อหลังตอบหน้าเว็บ ไม่ต้องรอตัวจับเวลา
        $this->assertCount(1, $this->aixmanPushes());
        $this->assertSame('eligible', $node->fresh()->dispatch_status);

        $this->actingAs($owner)->get('/gpuxmine')->assertOk();
        $this->assertSame(1, $this->relayListings(), 'second view within 15 s must come from the cache');
        $this->assertCount(1, $this->aixmanPushes());
    }

    public function test_the_payout_history_pages_through_every_job_and_names_removed_machines(): void
    {
        $owner = User::factory()->create();
        $removed = GpuNode::factory()->paired()->create(['user_id' => $owner->id, 'label' => 'เครื่องเก่าในห้องเก็บของ']);
        foreach (range(1, 30) as $i) {
            GpuJobEarning::create([
                'gpu_node_id' => $removed->id,
                'user_id' => $owner->id,
                'worker_id' => $removed->worker_id,
                'job_id' => 'aix-gpu-job-' . $i,
                'kind' => 'image',
                'amount_satang' => 150,
                'status' => 'pending',
                'completed_at' => now()->subMinutes($i),
            ]);
        }
        $removed->delete();

        $this->actingAs($owner)->get('/gpuxmine')
            ->assertOk()
            ->assertSee('เครื่องเก่าในห้องเก็บของ')
            ->assertSee('page=2', false);

        $this->actingAs($owner)->get('/gpuxmine?page=2')->assertOk();
    }

    public function test_the_owner_page_survives_a_relay_outage_and_shows_the_last_known_state(): void
    {
        $owner = User::factory()->create();
        $node = GpuNode::factory()->paired()->online()->assessed()->create([
            'user_id' => $owner->id,
            'dispatch_status' => 'eligible',
            'dispatch_fingerprint' => str_repeat('a', 40),
            'dispatch_synced_at' => now(),
        ]);
        $this->relayWorkers = null;

        $this->actingAs($owner)->get('/gpuxmine')->assertOk()->assertSee($node->displayName());

        // relay ตอบไม่ได้ ≠ เครื่องหลุด — ไม่เขียนอะไรทับ
        $this->assertTrue($node->fresh()->online);
        $this->assertTrue($node->fresh()->assessed);
        $this->assertSame(1200, $node->fresh()->score);
    }
}
