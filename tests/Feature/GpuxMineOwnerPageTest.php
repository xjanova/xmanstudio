<?php

namespace Tests\Feature;

use App\Models\GpuJobEarning;
use App\Models\GpuNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\FakesGpuxMineNetwork;
use Tests\TestCase;

/**
 * หน้า "เครื่องของฉัน" บอกเจ้าของเครื่องตามที่ระบบทำจริง (D3)
 *
 *   — เคยสัญญาว่า "โอนค่าตอบแทนเข้ากระเป๋าเงินอัตโนมัติ" ทั้งที่ไม่มีอะไรโอนเลย
 *     ตอนนี้เงินพักตามระยะที่ตั้งไว้ แล้วเข้ากระเป๋า XMAN — และยังไม่มีการถอนเป็นเงินสด
 *   — เครื่องที่ถูกระงับต้องบอกว่าถูกระงับและเพราะอะไร ไม่ใช่ขึ้นว่าพร้อมรับงาน
 *   — สิ่งที่ระบบส่งงานเห็น (สถานะ worker, ข้อผิดพลาดล่าสุด) เป็นภาษาไทย
 *   — ประวัติเงินแสดงมูลค่างานที่แชร์ฟรี และส่วนแบ่งผู้แนะนำที่หักไป
 */
class GpuxMineOwnerPageTest extends TestCase
{
    use FakesGpuxMineNetwork;
    use RefreshDatabase;

    private User $owner;

    private GpuNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        $this->fakeGpuxMine();
        // relay ตอบไม่ได้ = หน้าแสดงสถานะล่าสุดที่รู้ ไม่เขียนทับ — เทสต์ควบคุมแถวได้ตรง ๆ
        $this->relayWorkers = null;
        config(['services.gpuxmine.earning_hold_hours' => 36]);

        $this->owner = User::factory()->create();
        $this->node = GpuNode::factory()->paired()->online()->assessed()->create([
            'user_id' => $this->owner->id,
            'label' => 'เครื่องเล่นเกม',
            'dispatch_status' => 'eligible',
            'dispatch_worker_status' => 'ready',
            'dispatch_synced_at' => now(),
        ]);
    }

    private function page()
    {
        return $this->actingAs($this->owner)->get('/gpuxmine')->assertOk();
    }

    public function test_the_page_promises_the_hold_and_the_xman_wallet_not_an_automatic_cash_payout(): void
    {
        $this->page()
            ->assertDontSee('โอนค่าตอบแทนเข้ากระเป๋าเงินอัตโนมัติ')
            ->assertDontSee('โอนเป็นรอบ')
            ->assertSee('พักไว้ 36 ชั่วโมง')
            ->assertSee('กระเป๋าเงิน XMAN')
            ->assertSee('ยังไม่มีการถอนเป็นเงินสด');
    }

    public function test_money_not_yet_in_the_wallet_is_split_by_what_it_is_waiting_for(): void
    {
        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 100]);
        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 250, 'status' => GpuJobEarning::STATUS_REVIEW]);
        GpuJobEarning::factory()->forNode($this->node)->create(['amount_satang' => 400, 'status' => GpuJobEarning::STATUS_CLEARED]);

        $this->page()
            ->assertSee('฿7.50')     // รวมที่ยังไม่เข้ากระเป๋า
            ->assertSeeInOrder(['อยู่ในระยะพัก', '฿1.00'])
            ->assertSeeInOrder(['รอตรวจสอบ', '฿2.50'])
            ->assertSeeInOrder(['รอโอนรอบถัดไป', '฿4.00']);
    }

    public function test_a_suspended_machine_says_so_and_why_instead_of_ready(): void
    {
        $this->node->forceFill([
            'suspended_at' => now(),
            'suspended_reason' => 'ผลงานว่างเปล่าหลายครั้ง',
        ])->save();

        $this->page()
            ->assertSee('ถูกระงับ')
            ->assertSee('ผู้ดูแลระงับเครื่องนี้ไว้')
            ->assertSee('ผลงานว่างเปล่าหลายครั้ง')
            ->assertSee('0 <span class="bi bi-inline"><span class="bi-th">พร้อมรับงาน', false);
    }

    public function test_what_the_dispatcher_sees_is_shown_in_thai(): void
    {
        $this->node->forceFill([
            'dispatch_worker_status' => 'terminated',
            'dispatch_last_error' => 'probe failed: 503 from /aixman/ready',
        ])->save();

        $this->page()
            ->assertSee('มีสิทธิ์รับงาน')
            ->assertSee('ถูกนำออกจากคิวงาน')
            ->assertSee('ปัญหาล่าสุดที่ระบบส่งงานพบ')
            ->assertSee('probe failed: 503 from /aixman/ready');
    }

    public function test_the_history_shows_donated_value_and_the_referral_share(): void
    {
        GpuJobEarning::factory()->forNode($this->node)->create([
            'amount_satang' => 0,
            'free_share' => true,
            'donated_value_satang' => 300,
        ]);
        GpuJobEarning::factory()->forNode($this->node)->create([
            'amount_satang' => 135,
            'referral_satang' => 15,
        ]);

        $this->page()
            ->assertSee('แชร์ฟรี (มูลค่า)')
            ->assertSee('หักผู้แนะนำ')
            ->assertSee('฿3.00')
            ->assertSee('−฿0.15')
            ->assertSee('พ้นระยะพัก');
    }

    public function test_an_audio_capable_machine_names_the_job_type(): void
    {
        $this->node->forceFill(['can_run' => ['image', 'audio']])->save();

        $this->page()->assertSee('สร้างเสียง/เพลง');
    }

    public function test_a_banned_owner_cannot_ask_for_a_pairing_code(): void
    {
        $banned = GpuNode::factory()->paired()->create([
            'user_id' => $this->owner->id,
            'banned_at' => now(),
            'banned_reason' => 'ใช้หลายบัญชีหลบการระงับ',
            'suspended_at' => now(),
        ]);
        $banned->delete();

        $this->page()
            ->assertSee('ใช้หลายบัญชีหลบการระงับ')
            ->assertSee('disabled', false);

        $this->actingAs($this->owner)->post('/gpuxmine/pair')->assertSessionHas('error');
        $this->assertSame(0, GpuNode::where('user_id', $this->owner->id)->whereNull('paired_at')->count());
    }
}
