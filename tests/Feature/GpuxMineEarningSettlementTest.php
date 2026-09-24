<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\GpuxMineEarningSettlementService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * รายได้จากงาน GPUxMINE เดินจาก pending → cleared → paid แล้วเข้ากระเป๋า XMAN (D2)
 *
 *   — ก่อนหน้านี้ไม่มีอะไรพาเงินออกจาก pending เลย ทุกงานค้างเป็น "รอเข้ากระเป๋า"
 *   — การจ่ายรันซ้ำได้โดยไม่จ่ายซ้ำ แม้ตัวรันสองตัวจะเห็นแถวชุดเดียวกัน
 *   — เครื่องที่แอดมินระงับ และงานที่ติดรอตรวจ ไม่ถูกปล่อยเงินเอง
 *   — ส่วนแบ่งผู้แนะนำกลายเป็นค่าแนะนำหนึ่งรายการต่อหนึ่งงาน ครั้งเดียว
 *   — รายได้ไม่ใช่เงินเติม: total_deposited ต้องไม่ขยับ
 */
class GpuxMineEarningSettlementTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    private User $owner;

    private GpuNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gpuxmine.earning_hold_hours' => 24]);

        $this->owner = User::factory()->create();
        $this->node = GpuNode::factory()->paired()->create(['user_id' => $this->owner->id]);
    }

    private function settle(): int
    {
        return $this->artisan('gpuxmine:settle-earnings')->run();
    }

    private function earning(array $attributes = []): GpuJobEarning
    {
        return GpuJobEarning::factory()->forNode($this->node)->matured()->create($attributes);
    }

    private function wallet(?User $user = null): ?Wallet
    {
        return Wallet::where('user_id', ($user ?? $this->owner)->id)->first();
    }

    private function affiliateFor(User $user, string $status = 'active'): Affiliate
    {
        return Affiliate::create([
            'user_id' => $user->id,
            'referral_code' => strtoupper(Str::random(8)),
            'commission_rate' => 10,
            'status' => $status,
        ]);
    }

    // ── ระยะพักและการโอน ───────────────────────────────────────────────

    public function test_earnings_past_the_hold_reach_the_wallet_as_one_earning_entry(): void
    {
        Wallet::getOrCreateForUser($this->owner->id)->update(['balance' => '10.05', 'total_deposited' => '10.05']);
        $a = $this->earning(['amount_satang' => 150]);
        $b = $this->earning(['amount_satang' => 275]);

        $this->assertSame(0, $this->settle());

        $wallet = $this->wallet();
        $this->assertSame('14.30', $wallet->balance);
        // รายได้ไม่ใช่เงินที่เจ้าของเติม
        $this->assertSame('10.05', $wallet->total_deposited);

        $txn = WalletTransaction::where('wallet_id', $wallet->id)->sole();
        $this->assertSame(WalletTransaction::TYPE_EARNING, $txn->type);
        $this->assertSame('4.25', $txn->amount);
        $this->assertSame('10.05', $txn->balance_before);
        $this->assertSame('14.30', $txn->balance_after);
        $this->assertSame(WalletTransaction::STATUS_COMPLETED, $txn->status);
        $this->assertSame("GXM{$this->owner->id}-{$b->id}", $txn->transaction_id);
        $this->assertSame('gpu_job_earnings', $txn->reference_type);
        $this->assertSame([$a->id, $b->id], $txn->metadata['earning_ids']);
        $this->assertSame('รายได้แชร์การ์ดจอ', $txn->type_label);

        foreach ([$a, $b] as $row) {
            $row->refresh();
            $this->assertSame(GpuJobEarning::STATUS_PAID, $row->status);
            $this->assertSame($txn->id, $row->wallet_transaction_id);
            $this->assertNotNull($row->cleared_at);
            $this->assertNotNull($row->paid_at);
        }
    }

    public function test_an_owner_without_a_wallet_gets_one(): void
    {
        $this->earning(['amount_satang' => 99]);

        $this->assertSame(0, $this->settle());

        $this->assertSame('0.99', $this->wallet()->balance);
        $this->assertTrue($this->wallet()->is_active);
    }

    public function test_earnings_inside_the_hold_stay_pending(): void
    {
        $fresh = GpuJobEarning::factory()->forNode($this->node)->create(['completed_at' => now()->subHours(23)]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_PENDING, $fresh->fresh()->status);
        $this->assertNull($this->wallet());
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_the_hold_is_configurable_and_zero_releases_on_the_next_run(): void
    {
        config(['services.gpuxmine.earning_hold_hours' => 0]);
        $fresh = GpuJobEarning::factory()->forNode($this->node)->create(['completed_at' => now()->subMinute()]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_PAID, $fresh->fresh()->status);
    }

    public function test_a_row_aixman_wrote_without_completed_at_is_held_from_when_it_was_recorded(): void
    {
        $old = $this->earning(['completed_at' => null]);
        GpuJobEarning::whereKey($old->id)->update(['created_at' => now()->subDays(2)]);
        $new = $this->earning(['completed_at' => null]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_PAID, $old->fresh()->status);
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $new->fresh()->status);
    }

    // ── ไม่จ่ายซ้ำ ───────────────────────────────────────────────────

    public function test_running_twice_pays_once(): void
    {
        $this->earning(['amount_satang' => 500]);

        $this->assertSame(0, $this->settle());
        $this->assertSame(0, $this->settle());

        $this->assertSame('5.00', $this->wallet()->balance);
        $this->assertSame(1, WalletTransaction::count());

        // งานใหม่ที่พ้นระยะพักทีหลังเป็นรายการใหม่ ไม่ไปแก้รายการเดิม
        $this->earning(['amount_satang' => 125]);
        $this->assertSame(0, $this->settle());

        $this->assertSame('6.25', $this->wallet()->balance);
        $this->assertSame(2, WalletTransaction::count());
    }

    public function test_a_runner_holding_a_stale_view_of_a_paid_batch_cannot_credit_it_again(): void
    {
        $rows = collect([$this->earning(['amount_satang' => 300]), $this->earning(['amount_satang' => 200])]);
        $this->assertSame(0, $this->settle());
        $this->assertSame('5.00', $this->wallet()->balance);

        // ตัวรันตัวที่สองที่อ่านชุดเดียวกันไว้ก่อนตัวแรกบันทึก เห็นแถวเหล่านี้เป็น cleared
        // — จำลองด้วยการคืนสถานะแถวที่จ่ายแล้วกลับไป ชุดเดิมให้เลขรายการเดิม ซึ่งเป็น
        // unique ฐานข้อมูลจึงปฏิเสธ และทรานแซกชันทั้งก้อนถูกย้อน รวมเงินที่บวกเข้าไป
        GpuJobEarning::whereKey($rows->pluck('id'))->update([
            'status' => GpuJobEarning::STATUS_CLEARED,
            'wallet_transaction_id' => null,
            'paid_at' => null,
        ]);

        $this->assertSame(1, $this->settle());

        $this->assertSame('5.00', $this->wallet()->balance);
        $this->assertSame(1, WalletTransaction::count());
        $this->assertSame(2, GpuJobEarning::where('status', GpuJobEarning::STATUS_CLEARED)->count());
    }

    public function test_one_owner_failing_does_not_stop_the_others_being_paid(): void
    {
        $this->earning(['amount_satang' => 300]);
        $this->assertSame(0, $this->settle());
        GpuJobEarning::query()->update(['status' => GpuJobEarning::STATUS_CLEARED]);   // ชุดเดิม = ชนเลขรายการ

        $other = User::factory()->create();
        $otherNode = GpuNode::factory()->paired()->create(['user_id' => $other->id]);
        GpuJobEarning::factory()->forNode($otherNode)->matured()->create(['amount_satang' => 700]);

        $this->assertSame(1, $this->settle());

        $this->assertSame('7.00', $this->wallet($other)->balance);
    }

    public function test_record_absorbs_a_repeated_write_and_never_rewinds_a_paid_row(): void
    {
        $attributes = [
            'user_id' => $this->owner->id,
            'worker_id' => $this->node->worker_id,
            'job_id' => 'aix-gpu-job-777',
            'kind' => 'image',
            'amount_satang' => 150,
            'status' => GpuJobEarning::STATUS_PENDING,
            'completed_at' => now()->subDays(2),
        ];

        $first = GpuJobEarning::record($attributes);
        $first->update(['status' => GpuJobEarning::STATUS_PAID]);

        $again = GpuJobEarning::record(['amount_satang' => 999] + $attributes);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(GpuJobEarning::STATUS_PAID, $again->status);
        $this->assertSame(150, $again->amount_satang);
        $this->assertSame(1, GpuJobEarning::count());
    }

    // ── สิ่งที่ต้องไม่ถูกปล่อยเอง ──────────────────────────────────────

    public function test_a_suspended_node_holds_its_earnings_until_it_is_resumed(): void
    {
        $pending = $this->earning(['amount_satang' => 400]);
        $cleared = $this->earning(['amount_satang' => 100, 'status' => GpuJobEarning::STATUS_CLEARED]);
        $this->node->update(['suspended_at' => now(), 'suspended_reason' => 'ผลงานไม่ตรงกับที่สั่ง']);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_PENDING, $pending->fresh()->status);
        $this->assertSame(GpuJobEarning::STATUS_CLEARED, $cleared->fresh()->status);
        $this->assertSame(0, WalletTransaction::count());

        $this->node->update(['suspended_at' => null]);
        $this->assertSame(0, $this->settle());

        $this->assertSame('5.00', $this->wallet()->balance);
    }

    public function test_a_suspended_node_is_matched_by_worker_id_when_aixman_left_the_node_id_out(): void
    {
        $row = $this->earning(['gpu_node_id' => null]);
        $this->node->update(['suspended_at' => now()]);
        $this->node->delete();   // ถอนออกแล้วก็ยังถูกระงับอยู่

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_PENDING, $row->fresh()->status);
    }

    public function test_review_and_void_rows_are_never_released_automatically(): void
    {
        $review = $this->earning(['status' => GpuJobEarning::STATUS_REVIEW, 'review_reason' => 'ภาพว่างเปล่า']);
        $void = $this->earning(['status' => GpuJobEarning::STATUS_VOID, 'void_reason' => 'งานล้มหลังบันทึก']);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_REVIEW, $review->fresh()->status);
        $this->assertSame(GpuJobEarning::STATUS_VOID, $void->fresh()->status);
        $this->assertNull($this->wallet());
    }

    public function test_a_negative_amount_goes_to_review_instead_of_debiting_the_owner(): void
    {
        $row = $this->earning(['amount_satang' => -150]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(GpuJobEarning::STATUS_REVIEW, $row->fresh()->status);
        $this->assertNotNull($row->fresh()->review_reason);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_an_inactive_account_or_wallet_is_not_paid_until_it_is_reopened(): void
    {
        $row = $this->earning(['amount_satang' => 250]);
        $this->owner->forceFill(['is_active' => false])->save();

        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_CLEARED, $row->fresh()->status);

        $this->owner->forceFill(['is_active' => true])->save();
        Wallet::getOrCreateForUser($this->owner->id)->update(['is_active' => false]);

        $this->assertSame(0, $this->settle());
        $this->assertSame(GpuJobEarning::STATUS_CLEARED, $row->fresh()->status);
        $this->assertSame('0.00', $this->wallet()->balance);

        $this->wallet()->update(['is_active' => true]);
        $this->assertSame(0, $this->settle());
        $this->assertSame('2.50', $this->wallet()->balance);
    }

    public function test_a_free_share_job_closes_without_a_zero_baht_wallet_entry(): void
    {
        $row = $this->earning(['amount_satang' => 0, 'free_share' => true, 'donated_value_satang' => 150]);

        $this->assertSame(0, $this->settle());

        $row->refresh();
        $this->assertSame(GpuJobEarning::STATUS_PAID, $row->status);
        $this->assertNull($row->wallet_transaction_id);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_a_user_deleted_after_earning_keeps_the_rows_and_is_skipped(): void
    {
        $row = $this->earning();
        GpuJobEarning::whereKey($row->id)->update(['gpu_node_id' => null]);
        $this->node->forceDelete();
        $this->owner->delete();

        $this->assertSame(0, $this->settle());

        $row = GpuJobEarning::find($row->id);
        $this->assertNotNull($row, 'the payout audit trail must survive the account');
        $this->assertNull($row->user_id);
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $row->status);
    }

    // ── ส่วนแบ่งผู้แนะนำ (D8) ───────────────────────────────────────────

    public function test_the_referral_share_becomes_one_pending_commission_per_job(): void
    {
        $referrer = User::factory()->create();
        $affiliate = $this->affiliateFor($referrer);
        $a = $this->earning(['referral_satang' => 25, 'referral_user_id' => $referrer->id, 'revenue_satang' => 200]);
        $b = $this->earning(['referral_satang' => 30, 'referral_user_id' => $referrer->id, 'revenue_satang' => 300]);

        $this->assertSame(0, $this->settle());
        $this->assertSame(0, $this->settle());

        $commissions = AffiliateCommission::where('source_type', 'gpuxmine')->orderBy('source_id')->get();
        $this->assertCount(2, $commissions);
        $this->assertSame([$a->id, $b->id], $commissions->pluck('source_id')->map(fn ($id) => (int) $id)->all());

        $first = $commissions->first();
        $this->assertSame('pending', $first->status);
        $this->assertSame('0.25', $first->commission_amount);
        $this->assertSame('2.00', $first->order_amount);
        $this->assertSame('12.50', $first->commission_rate);
        $this->assertSame($this->owner->id, (int) $first->referred_user_id);
        $this->assertNull($first->order_id);
        $this->assertSame('GPUxMINE', $first->source_label);
        $this->assertSame($first->id, $a->fresh()->affiliate_commission_id);

        $affiliate->refresh();
        $this->assertSame('0.55', $affiliate->total_earned);
        $this->assertSame('0.55', $affiliate->total_pending);
        // เจ้าของเครื่องหนึ่งคนนับเป็นการแนะนำหนึ่งครั้ง ไม่ใช่ทุกงาน
        $this->assertSame(1, (int) $affiliate->total_referrals);
        $this->assertSame(1, (int) $affiliate->total_conversions);

        // แอดมินอนุมัติได้ตามทางเดิม
        $this->assertTrue($first->approveAndPay());
        $this->assertSame('0.25', Wallet::where('user_id', $referrer->id)->value('balance'));
    }

    public function test_a_suspended_or_missing_referrer_is_not_paid_and_the_owner_still_is(): void
    {
        $suspended = User::factory()->create();
        $this->affiliateFor($suspended, 'suspended');
        $nobody = User::factory()->create();
        $a = $this->earning(['amount_satang' => 100, 'referral_satang' => 20, 'referral_user_id' => $suspended->id]);
        $b = $this->earning(['amount_satang' => 100, 'referral_satang' => 20, 'referral_user_id' => $nobody->id]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(0, AffiliateCommission::count());
        $this->assertSame(GpuJobEarning::STATUS_PAID, $a->fresh()->status);
        $this->assertSame(GpuJobEarning::STATUS_PAID, $b->fresh()->status);
        $this->assertSame('2.00', $this->wallet()->balance);
    }

    public function test_the_owner_is_never_paid_a_referral_on_their_own_jobs(): void
    {
        $this->affiliateFor($this->owner);
        $this->earning(['referral_satang' => 20, 'referral_user_id' => $this->owner->id]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(0, AffiliateCommission::count());
    }

    public function test_an_existing_commission_for_the_job_is_linked_rather_than_duplicated(): void
    {
        $referrer = User::factory()->create();
        $affiliate = $this->affiliateFor($referrer);
        $row = $this->earning(['referral_satang' => 25, 'referral_user_id' => $referrer->id]);
        $existing = AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'order_amount' => 2,
            'commission_rate' => 12.5,
            'commission_amount' => 0.25,
            'status' => 'pending',
            'source_type' => 'gpuxmine',
            'source_id' => $row->id,
        ]);

        $this->assertSame(0, $this->settle());

        $this->assertSame(1, AffiliateCommission::count());
        $this->assertSame($existing->id, $row->fresh()->affiliate_commission_id);
    }

    // ── หน้าเครื่องของฉัน ─────────────────────────────────────────────

    public function test_the_owner_page_counts_held_reviewed_and_cleared_money_as_not_yet_in_the_wallet(): void
    {
        $this->withoutVite();
        $this->fakeGpuxMine();
        $this->relayWorkers = [];

        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 100]);
        $this->earning(['amount_satang' => 50, 'status' => GpuJobEarning::STATUS_REVIEW]);
        $this->earning(['amount_satang' => 25, 'status' => GpuJobEarning::STATUS_CLEARED]);
        $this->earning(['amount_satang' => 1000, 'status' => GpuJobEarning::STATUS_PAID]);
        $this->earning(['amount_satang' => 999, 'status' => GpuJobEarning::STATUS_VOID]);

        $this->actingAs($this->owner)->get('/gpuxmine')
            ->assertOk()
            ->assertSee('฿1.75')      // 100 + 50 + 25
            ->assertSee('฿10.00')
            ->assertSee('รอตรวจสอบ')
            ->assertSee('รอโอนเข้ากระเป๋า');
    }

    // ── ตัวตั้งเวลา ──────────────────────────────────────────────────

    public function test_settlement_is_scheduled_hourly_and_never_overlaps_itself(): void
    {
        // ตารางเวลาอยู่ใน routes/console.php ซึ่งโหลดตอน console kernel บูต
        $this->app->make(ConsoleKernel::class)->bootstrap();

        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'gpuxmine:settle-earnings'));

        $this->assertNotNull($event, 'gpuxmine:settle-earnings must be scheduled');
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertLessThan(60, $event->expiresAt);
    }

    public function test_money_helpers_never_go_through_float(): void
    {
        $this->assertSame('0.01', GpuxMineEarningSettlementService::baht(1));
        $this->assertSame('-1.50', GpuxMineEarningSettlementService::baht(-150));
        $this->assertSame('123456789.05', GpuxMineEarningSettlementService::baht(12345678905));
        $this->assertSame(30, GpuxMineEarningSettlementService::satang('0.3'));
        $this->assertSame(1005, GpuxMineEarningSettlementService::satang('10.05'));
        $this->assertSame(0, GpuxMineEarningSettlementService::satang(null));
    }
}
