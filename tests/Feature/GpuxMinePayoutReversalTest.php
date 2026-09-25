<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use App\Services\GpuxMinePayoutReversalService;
use App\Services\WalletWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * แอดมินเรียกคืนเงิน GPUxMINE ที่จ่ายเข้ากระเป๋าแล้ว (R2-3) — "จ่ายอัตโนมัติเลย แต่โรลแบ็คคืนได้
 * ถ้ายังไม่ได้ถอนเป็นเงินสด"
 *
 *   — รายได้ของเครื่องหนึ่งงาน และค่าแนะนำหนึ่งรายการ ด้วยกลไกเดียวกัน
 *   — หนึ่งรายการ adjustment ในกระเป๋าใบเดิม ผูกกลับรายการเดิม มีแอดมินและเหตุผล ≥ 10 ตัวอักษร
 *   — ได้ครั้งเดียว และเฉพาะเมื่อยอดที่ใช้ได้ยังพอ (ยอดที่พักกับคำขอถอนไม่นับ) — ไม่พอ = "ถอนออกไปแล้ว"
 *   — ที่ถูกเรียกคืนขึ้นเป็นเรียกคืนแล้วทุกที่: หน้าเจ้าของเครื่อง, /status (void + void_reason), หน้าค่าแนะนำ
 */
class GpuxMinePayoutReversalTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    private const REASON = 'ผลงานเป็นภาพว่างเปล่า ตรวจแล้วเป็นงานปลอม';

    private User $owner;

    private User $admin;

    private GpuNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gpuxmine.earning_hold_hours' => 24]);
        Cache::flush();
        $this->withoutVite();

        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->node = GpuNode::factory()->paired()->create(['user_id' => $this->owner->id]);
    }

    private function settle(): void
    {
        $this->assertSame(0, $this->artisan('gpuxmine:settle-earnings')->run());
    }

    private function paidEarning(array $attributes = []): GpuJobEarning
    {
        $row = GpuJobEarning::factory()->forNode($this->node)->matured()->create($attributes);
        $this->settle();

        return $row->fresh();
    }

    private function balance(User $user): string
    {
        return (string) Wallet::where('user_id', $user->id)->value('balance');
    }

    private function reverseEarning(GpuJobEarning $earning, string $reason = self::REASON, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)->post("/admin/gpuxmine/earnings/{$earning->id}/reverse", ['reason' => $reason]);
    }

    private function reverseCommission(AffiliateCommission $commission, string $reason = self::REASON, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)->post("/admin/affiliates/commission/{$commission->id}/reverse", ['reason' => $reason]);
    }

    private function verify(User $user): void
    {
        KycVerification::create([
            'user_id' => $user->id,
            'status' => KycVerification::STATUS_APPROVED,
            'bank_code' => 'SCB',
            'bank_account_number' => '1112223334',
            'bank_account_name' => 'เจ้าของ เครื่อง',
        ]);
        $user->forceFill(['kyc_status' => KycVerification::STATUS_APPROVED])->save();
    }

    // ── รายได้ของเครื่อง ─────────────────────────────────────────────────

    public function test_a_paid_earning_is_taken_back_once_as_one_linked_wallet_entry(): void
    {
        Wallet::getOrCreateForUser($this->owner->id)->update(['balance' => '10.00', 'total_deposited' => '10.00']);
        $bad = $this->paidEarning(['amount_satang' => 275]);
        $this->assertSame('12.75', $this->balance($this->owner));
        $original = $bad->wallet_transaction_id;

        $this->reverseEarning($bad)->assertSessionHas('success');

        $this->assertSame('10.00', $this->balance($this->owner));
        $bad->refresh();
        $this->assertSame(GpuJobEarning::STATUS_VOID, $bad->status);
        $this->assertTrue($bad->isReversed());
        $this->assertSame(self::REASON, $bad->void_reason);
        $this->assertSame($this->admin->id, $bad->reversed_by);
        $this->assertSame('เรียกคืนแล้ว', $bad->statusLabel());
        // ยังชี้ไปที่รายการเดิมที่จ่ายเข้ากระเป๋า
        $this->assertSame($original, $bad->wallet_transaction_id);

        $txn = WalletTransaction::findOrFail($bad->reversal_wallet_transaction_id);
        $this->assertSame('GXV' . $bad->id, $txn->transaction_id);
        $this->assertSame(WalletTransaction::TYPE_ADJUSTMENT, $txn->type);
        $this->assertSame('-2.75', $txn->amount);
        $this->assertSame('12.75', $txn->balance_before);
        $this->assertSame('10.00', $txn->balance_after);
        $this->assertSame($this->admin->id, (int) $txn->created_by);
        $this->assertSame(self::REASON, $txn->admin_note);
        $this->assertSame($original, $txn->metadata['reverses_wallet_transaction_id']);
        $this->assertSame('เรียกคืนโดยผู้ดูแล', $txn->type_label);
        // การเรียกคืนไม่ใช่การใช้จ่ายหรือการคืนเงิน
        $wallet = Wallet::where('user_id', $this->owner->id)->first();
        $this->assertSame('0.00', $wallet->total_spent);
        $this->assertSame('10.00', $wallet->total_deposited);

        // ครั้งที่สอง — ไม่หักซ้ำ
        $this->reverseEarning($bad)->assertSessionHas('error', fn ($m) => str_contains($m, 'ถูกเรียกคืนไปแล้ว'));
        $this->assertSame('10.00', $this->balance($this->owner));
        $this->assertSame(1, WalletTransaction::where('type', 'adjustment')->count());

        // settle รอบหน้าไม่แตะแถวที่ถูกเรียกคืน
        $this->settle();
        $this->assertSame('10.00', $this->balance($this->owner));
        $this->assertSame(GpuJobEarning::STATUS_VOID, $bad->fresh()->status);
    }

    public function test_the_owner_and_the_machine_see_the_reversal_with_its_reason(): void
    {
        $kept = $this->paidEarning(['amount_satang' => 300]);
        $bad = GpuJobEarning::factory()->forNode($this->node)->matured()->create(['amount_satang' => 120]);
        $this->settle();

        $this->reverseEarning($bad->fresh())->assertSessionHas('success');

        $this->fakeGpuxMine();
        $this->relayWorkers = null;
        $this->actingAs($this->owner)->get('/gpuxmine')->assertOk()
            ->assertSee('เรียกคืนแล้ว')->assertSee(self::REASON)->assertSee('฿3.00');

        $response = $this->postJson('/api/v1/product/gpuxmine/status', ['worker_id' => $this->node->worker_id, 'token' => $this->node->relay_token])
            ->assertOk()
            ->assertJsonPath('data.earnings.paid_satang', 300)
            ->assertJsonPath('data.earnings.wallet_balance_satang', 300);

        $job = collect($response->json('data.jobs'))->firstWhere('job_id', $bad->job_id);
        $this->assertSame('void', $job['status']);
        $this->assertSame(self::REASON, $job['void_reason']);
        $this->assertTrue($job['reversed']);
        $this->assertFalse(collect($response->json('data.jobs'))->firstWhere('job_id', $kept->job_id)['reversed']);

        // โปรแกรมที่ถามด้วยเคอร์เซอร์เห็นการเปลี่ยนแปลงนี้ด้วย (updated_at ขยับ)
        $this->travel(5)->minutes();
        $changed = $this->postJson('/api/v1/product/gpuxmine/status', [
            'worker_id' => $this->node->worker_id,
            'token' => $this->node->relay_token,
            'updated_after' => now()->subMinutes(10)->toIso8601String(),
        ])->assertOk()->json('data.jobs');
        $this->assertContains($bad->job_id, array_column($changed, 'job_id'));
    }

    public function test_money_already_withdrawn_or_held_for_a_withdrawal_cannot_be_taken_back(): void
    {
        $this->verify($this->owner);
        $bad = $this->paidEarning(['amount_satang' => 20000]);   // ฿200
        $this->assertSame('200.00', $this->balance($this->owner));

        // เจ้าของขอถอน ฿150 — ยอดถูกพัก เหลือที่ใช้ได้ ฿50 ไม่พอเรียกคืน ฿200
        [$withdrawal] = app(WalletWithdrawalService::class)->request($this->owner->fresh(), 15000, (string) Str::uuid());

        $this->reverseEarning($bad)->assertSessionHas('error', fn ($m) => str_contains($m, 'ถอนออกไปแล้ว') && str_contains($m, '฿50.00') && str_contains($m, '฿200.00'));
        $this->assertSame('50.00', $this->balance($this->owner));
        $this->assertSame(GpuJobEarning::STATUS_PAID, $bad->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('type', 'adjustment')->count());

        // เจ้าของยกเลิกคำขอถอน — เงินกลับมาอยู่ในกระเป๋า เรียกคืนได้
        app(WalletWithdrawalService::class)->cancel($this->owner, $withdrawal);
        $this->reverseEarning($bad)->assertSessionHas('success');
        $this->assertSame('0.00', $this->balance($this->owner));

        // เงินที่โอนออกไปแล้ว (อนุมัติแล้ว) ก็เรียกคืนไม่ได้เหมือนกัน
        $second = $this->paidEarning(['amount_satang' => 15000]);
        [$paidOut] = app(WalletWithdrawalService::class)->request($this->owner->fresh(), 15000, (string) Str::uuid());
        $paidOut = app(WalletWithdrawalService::class)->startTransfer($paidOut, $this->admin);
        app(WalletWithdrawalService::class)->approve($paidOut, $this->admin, 'SCB-REF-9', null);
        $this->reverseEarning($second)->assertSessionHas('error', fn ($m) => str_contains($m, 'ถอนออกไปแล้ว'));
        $this->assertSame(GpuJobEarning::STATUS_PAID, $second->fresh()->status);
        $this->assertSame(WalletWithdrawal::STATUS_APPROVED, $paidOut->fresh()->status);
    }

    public function test_a_reason_of_at_least_ten_characters_is_required(): void
    {
        $bad = $this->paidEarning(['amount_satang' => 100]);

        $this->reverseEarning($bad, 'ปลอม')->assertSessionHasErrors('reason');
        $this->reverseEarning($bad, '')->assertSessionHasErrors('reason');

        // service ตรง ๆ ก็ไม่ยอม
        $result = app(GpuxMinePayoutReversalService::class)->reverseEarning($bad->id, $this->admin, '   สั้น   ');
        $this->assertFalse($result['ok']);

        $this->assertSame(GpuJobEarning::STATUS_PAID, $bad->fresh()->status);
        $this->assertSame('1.00', $this->balance($this->owner));
    }

    public function test_money_not_yet_paid_is_voided_not_reversed(): void
    {
        $pending = GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 100]);

        $this->reverseEarning($pending)->assertSessionHas('error', fn ($m) => str_contains($m, 'ใช้ปุ่มยกเลิกแทน'));
        $this->assertSame(GpuJobEarning::STATUS_PENDING, $pending->fresh()->status);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_only_admins_can_reverse(): void
    {
        $bad = $this->paidEarning(['amount_satang' => 100]);

        $this->reverseEarning($bad, self::REASON, $this->owner)->assertForbidden();
        $this->post("/admin/gpuxmine/earnings/{$bad->id}/reverse", ['reason' => self::REASON])->assertForbidden();

        $this->assertSame(GpuJobEarning::STATUS_PAID, $bad->fresh()->status);
        $this->assertSame('1.00', $this->balance($this->owner));
    }

    public function test_the_admin_queue_offers_a_confirmed_reversal_on_paid_jobs_only(): void
    {
        $paid = $this->paidEarning(['amount_satang' => 100]);
        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 50, 'status' => GpuJobEarning::STATUS_REVIEW]);

        $this->fakeGpuxMine();
        $html = $this->actingAs($this->admin)->get('/admin/gpuxmine/earnings?status=all')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, route('admin.gpuxmine.earnings.reverse', $paid->id)));
        $this->assertStringContainsString('เหตุผลที่เรียกคืน', $html);
        // ถามยืนยันก่อนส่ง — ข้อความใน @js() ถูกเข้ารหัสเป็น escape ของ JSON จึงตรวจแค่ว่ามีการถาม
        $this->assertStringContainsString('window.confirm', $html);
    }

    // ── ค่าแนะนำ ────────────────────────────────────────────────────────

    private function paidCommission(User $referrer, int $share = 40): AffiliateCommission
    {
        Affiliate::create(['user_id' => $referrer->id, 'referral_code' => strtoupper(Str::random(8)), 'status' => 'active']);
        $this->paidEarning(['amount_satang' => 100, 'referral_satang' => $share, 'referral_user_id' => $referrer->id, 'revenue_satang' => $share * 10]);

        return AffiliateCommission::where('source_type', 'gpuxmine')->sole();
    }

    public function test_a_paid_referral_commission_is_taken_back_once(): void
    {
        $referrer = User::factory()->create();
        $commission = $this->paidCommission($referrer, 45);
        $this->assertSame('paid', $commission->status);
        $this->assertSame('0.45', $this->balance($referrer));

        $this->reverseCommission($commission, 'สั้นไป')->assertSessionHasErrors('reason');
        $this->reverseCommission($commission)->assertSessionHas('success');

        $commission->refresh();
        $this->assertSame(AffiliateCommission::STATUS_REVERSED, $commission->status);
        $this->assertSame(self::REASON, $commission->reversal_reason);
        $this->assertSame($this->admin->id, (int) $commission->reversed_by);
        $this->assertNotNull($commission->reversed_at);
        $this->assertSame('0.00', $this->balance($referrer));

        $txn = WalletTransaction::findOrFail($commission->reversal_wallet_transaction_id);
        $this->assertSame('GXAV' . $commission->id, $txn->transaction_id);
        $this->assertSame('-0.45', $txn->amount);
        $this->assertSame((int) $commission->wallet_transaction_id, $txn->metadata['reverses_wallet_transaction_id']);

        $affiliate = Affiliate::where('user_id', $referrer->id)->first();
        $this->assertSame('0.00', $affiliate->total_earned);
        $this->assertSame('0.00', $affiliate->total_paid);

        // ครั้งที่สอง — ไม่หักซ้ำ และรอบ settle ไม่จ่ายกลับมาใหม่
        $this->reverseCommission($commission)->assertSessionHas('error', fn ($m) => str_contains($m, 'ถูกเรียกคืนไปแล้ว'));
        $this->settle();
        $this->assertSame('0.00', $this->balance($referrer));
        $this->assertSame(AffiliateCommission::STATUS_REVERSED, $commission->fresh()->status);

        // ผู้แนะนำเห็นว่าถูกเรียกคืนและเพราะอะไร
        $this->actingAs($referrer)->get('/my-account/affiliate/commissions')->assertOk()
            ->assertSee('เรียกคืนแล้ว')->assertSee(self::REASON);
        $this->actingAs($this->admin)->get('/admin/affiliates/commissions?status=reversed')->assertOk()
            ->assertSee('เรียกคืนแล้ว')->assertSee(self::REASON);
    }

    public function test_a_commission_already_withdrawn_cannot_be_taken_back(): void
    {
        $referrer = User::factory()->create();
        $this->verify($referrer);
        Wallet::getOrCreateForUser($referrer->id)->update(['balance' => '100.00']);
        $commission = $this->paidCommission($referrer, 40);

        app(WalletWithdrawalService::class)->request($referrer->fresh(), 10000, (string) Str::uuid());   // พักไว้ ฿100 เหลือ ฿0.40

        // ยอดที่ใช้ได้ ฿0.40 พอดี — เรียกคืนได้ (เงินก้อนนี้ยังอยู่)
        $this->reverseCommission($commission)->assertSessionHas('success');
        $this->assertSame('0.00', $this->balance($referrer));

        $second = AffiliateCommission::create([
            'affiliate_id' => $commission->affiliate_id,
            'order_amount' => '1.00',
            'commission_rate' => '10.00',
            'commission_amount' => '0.10',
            'status' => 'paid',
            'source_type' => 'gpuxmine',
            'source_id' => 999,
        ]);
        $this->reverseCommission($second)->assertSessionHas('error', fn ($m) => str_contains($m, 'ถอนออกไปแล้ว'));
        $this->assertSame('paid', $second->fresh()->status);
    }

    public function test_only_gpuxmine_commissions_that_were_paid_can_be_reversed_and_only_by_admins(): void
    {
        $referrer = User::factory()->create();
        $commission = $this->paidCommission($referrer, 40);

        $this->reverseCommission($commission, self::REASON, $referrer)->assertForbidden();
        $this->assertSame('paid', $commission->fresh()->status);

        $tping = AffiliateCommission::create([
            'affiliate_id' => $commission->affiliate_id,
            'order_amount' => '100.00',
            'commission_rate' => '10.00',
            'commission_amount' => '10.00',
            'status' => 'paid',
            'source_type' => 'tping',
        ]);
        $this->reverseCommission($tping)->assertSessionHas('error', fn ($m) => str_contains($m, 'GPUxMINE'));
        $this->assertSame('paid', $tping->fresh()->status);

        $pending = AffiliateCommission::create([
            'affiliate_id' => $commission->affiliate_id,
            'order_amount' => '1.00',
            'commission_rate' => '10.00',
            'commission_amount' => '0.10',
            'status' => 'rejected',
            'source_type' => 'gpuxmine',
        ]);
        $this->reverseCommission($pending)->assertSessionHas('error', fn ($m) => str_contains($m, 'ยังไม่ได้จ่าย'));
    }

    public function test_reversing_an_earning_tells_the_admin_its_referral_share_is_separate(): void
    {
        $referrer = User::factory()->create();
        $commission = $this->paidCommission($referrer, 40);
        $earning = GpuJobEarning::where('affiliate_commission_id', $commission->id)->sole();

        $this->reverseEarning($earning)->assertSessionHas('success', fn ($m) => str_contains($m, 'ค่าแนะนำ #' . $commission->id));

        // ค่าแนะนำไม่ถูกเรียกคืนตามเอง — แอดมินตัดสินแยก
        $this->assertSame('paid', $commission->fresh()->status);
        $this->assertSame('0.40', $this->balance($referrer));
    }
}
