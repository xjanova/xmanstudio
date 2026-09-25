<?php

namespace Tests\Feature;

use App\Exceptions\WithdrawalRefused;
use App\Models\GpuNode;
use App\Models\KycVerification;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use App\Models\WalletWithdrawal;
use App\Services\WalletLedger;
use App\Services\WalletWithdrawalService;
use App\Support\AdminAlerts;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * ถอนเงินสดออกจากกระเป๋า XMAN (R2-4): ขอ → พักยอดทันที → แอดมินกดเริ่มโอน → โอนแล้วบันทึก
 * หรือปฏิเสธ (คืนยอด)
 *
 *   — ต้องยืนยันตัวตนแล้ว และโอนเข้าบัญชีที่ยืนยันไว้กับ KYC เท่านั้น
 *   — กดส่งซ้ำได้คำขอเดิม, หนึ่งคำขอที่เปิดอยู่ต่อคน, ยอดขั้นต่ำ, ไม่เกินยอดคงเหลือ, จำกัดอัตรา
 *   — ยอดที่คืน (ปฏิเสธ/ยกเลิก) ตรงกับที่พักไว้ทุกสตางค์ และคืนได้ครั้งเดียว
 *   — ผู้ใช้ยกเลิกได้จนกว่าแอดมินกดเริ่มโอน — หลังจากนั้นไม่ได้ (เงินอาจออกไปแล้ว)
 *   — แอดมินสองคนกดพร้อมกัน / ผู้ใช้ยกเลิกพร้อมแอดมินกดเริ่มโอน มีผลครั้งเดียว
 *   — แจ้งแอดมินทาง Telegram ด้วยระบบแจ้งเตือนเดิม โดยไม่ส่งเลขบัญชีเต็ม
 */
class WalletWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private const ACCOUNT = '1234567890';

    private User $user;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
        Http::preventStrayRequests();
        Storage::fake('local');
        AdminAlerts::$immediate = false;
        BusinessAlerts::$actor = null;

        $this->user = $this->verifiedUser();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินการเงิน']);
        $this->fund($this->user, '500.00');
    }

    private function verifiedUser(bool $withBank = true): User
    {
        $user = User::factory()->create(['name' => 'สมหญิง ใจดี']);
        KycVerification::create([
            'user_id' => $user->id,
            'status' => KycVerification::STATUS_APPROVED,
            'full_name_th' => 'สมหญิง ใจดี',
            'bank_code' => $withBank ? 'KBANK' : null,
            'bank_account_number' => $withBank ? '123-4-56789-0' : null,
            'bank_account_name' => $withBank ? 'สมหญิง ใจดี' : null,
        ]);
        $user->forceFill(['kyc_status' => KycVerification::STATUS_APPROVED, 'kyc_verified_at' => now()])->save();

        return $user;
    }

    private function fund(User $user, string $baht): Wallet
    {
        $wallet = Wallet::getOrCreateForUser($user->id);
        $wallet->update(['balance' => $baht, 'total_deposited' => $baht]);

        return $wallet->fresh();
    }

    private function balance(?User $user = null): string
    {
        return (string) Wallet::where('user_id', ($user ?? $this->user)->id)->value('balance');
    }

    private function ask(string $amount, ?string $key = null, array $extra = [], ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->user)->post('/wallet/withdraw', array_merge([
            'amount' => $amount,
            'request_key' => $key ?? (string) Str::uuid(),
        ], $extra));
    }

    private function onlyRequest(): WalletWithdrawal
    {
        return WalletWithdrawal::sole();
    }

    /** แอดมินกด "เริ่มโอน" ที่หน้าคำขอ */
    private function start(WalletWithdrawal $w, ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->admin)->post("/admin/wallets/withdrawals/{$w->id}/start");
    }

    private function telegramOn(): void
    {
        Setting::setValue('telegram_bot_token', '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw', 'string', 'telegram');
        Setting::setValue('telegram_chat_id', '-1001234567890', 'string', 'telegram');
        Setting::setValue('telegram_alerts_enabled', '1', 'boolean', 'telegram');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 77]])]);
    }

    // ── ขอถอน ─────────────────────────────────────────────────────────────

    public function test_a_request_holds_the_amount_at_once_and_pays_only_the_kyc_account(): void
    {
        $this->ask('200.50')->assertRedirect(route('user.wallet.withdraw'))->assertSessionHas('success');

        $w = $this->onlyRequest();
        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->status);
        $this->assertSame('200.50', $w->amount);
        $this->assertSame($this->user->id, $w->open_user_id);
        // ปลายทางมาจาก KYC ที่อนุมัติแล้ว — ไม่ใช่สิ่งที่ฟอร์มส่งมา
        $this->assertSame(WalletWithdrawal::SOURCE_KYC, $w->account_source);
        $this->assertSame(self::ACCOUNT, $w->account_number);
        $this->assertSame('สมหญิง ใจดี', $w->account_name);
        $this->assertSame('กสิกรไทย', $w->bank_name);
        // เลขบัญชีเก็บแบบเข้ารหัส
        $this->assertNotSame(self::ACCOUNT, DB::table('wallet_withdrawals')->value('account_number'));

        // ยอดถูกพักด้วยการหักทันที — aixman และทุกที่ที่ตัดเงินจาก balance ใช้ยอดนี้ไม่ได้แล้ว
        $this->assertSame('299.50', $this->balance());
        $wallet = Wallet::where('user_id', $this->user->id)->first();
        $this->assertSame('0.00', $wallet->total_spent);
        $this->assertSame('500.00', $wallet->total_deposited);

        $hold = WalletTransaction::findOrFail($w->hold_transaction_id);
        $this->assertSame('WDH' . $w->id, $hold->transaction_id);
        $this->assertSame(WalletTransaction::TYPE_WITHDRAWAL, $hold->type);
        $this->assertSame(WalletTransaction::STATUS_PENDING, $hold->status);
        $this->assertSame('-200.50', $hold->amount);
        $this->assertSame('500.00', $hold->balance_before);
        $this->assertSame('299.50', $hold->balance_after);
        $this->assertSame('พักยอดไว้ · รอแอดมินอนุมัติ', $hold->stateLabel());

        // ผู้ใช้เห็นคำขอที่รอ ทั้งหน้าถอน หน้ากระเป๋า และประวัติ
        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()
            ->assertSee($w->withdrawal_id)->assertSee('คำขอที่รอแอดมินโอน')->assertSee('ยกเลิกคำขอนี้')->assertDontSee(self::ACCOUNT);
        $this->actingAs($this->user)->get('/wallet')->assertOk()
            ->assertSee('คำขอถอนรอแอดมินโอน')->assertSee('พักยอดไว้ · รอแอดมินอนุมัติ');
        $this->actingAs($this->user)->get('/wallet/transactions')->assertOk()->assertSee('พักยอดไว้ · รอแอดมินอนุมัติ');
    }

    public function test_the_form_shows_the_kyc_account_or_asks_for_one(): void
    {
        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()
            ->assertSee('name="request_key"', false)
            ->assertSee('กสิกรไทย · xxx-7890')
            ->assertSee('บัญชีที่ยืนยันตัวตนไว้')
            ->assertSee('฿100.00')          // ขั้นต่ำ
            ->assertSee('฿500.00')          // ถอนได้สูงสุด
            ->assertDontSee('name="account_number"', false)
            ->assertDontSee(self::ACCOUNT)
            ->assertSee('ยังไม่เคยขอถอนเงิน');

        $noBank = $this->verifiedUser(withBank: false);
        $this->fund($noBank, '50.00');
        $this->actingAs($noBank)->get('/wallet/withdraw')->assertOk()
            ->assertSee('name="account_number"', false)
            ->assertSee('name="promptpay_id"', false)
            ->assertSee('ยอดในกระเป๋ายังไม่ถึงขั้นต่ำ');

        // หน้ากระเป๋ามีทางไปหน้าถอน
        $this->actingAs($this->user)->get('/wallet')->assertOk()->assertSee(route('user.wallet.withdraw'), false);
    }

    public function test_the_same_submit_twice_makes_one_request_and_one_hold(): void
    {
        $key = (string) Str::uuid();

        $this->ask('150', $key)->assertSessionHas('success');
        $this->ask('150', $key)->assertRedirect(route('user.wallet.withdraw'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'ส่งไปแล้ว'));

        $this->assertSame(1, WalletWithdrawal::count());
        $this->assertSame(1, WalletTransaction::where('type', 'withdrawal')->count());
        $this->assertSame('350.00', $this->balance());

        // service ตรง ๆ ด้วยกุญแจเดิม (เช่นคำขอที่หลุดมาพร้อมกัน) ก็ได้คำขอเดิม
        [$again, $created] = app(WalletWithdrawalService::class)->request($this->user->fresh(), 15000, $key);
        $this->assertFalse($created);
        $this->assertSame($this->onlyRequest()->id, $again->id);
        $this->assertSame('350.00', $this->balance());
    }

    public function test_only_one_open_request_per_user(): void
    {
        $this->ask('100')->assertSessionHas('success');

        $this->ask('100')->assertSessionHas('error', fn ($m) => str_contains($m, 'รอแอดมินอยู่แล้ว'));

        $this->assertSame(1, WalletWithdrawal::count());
        $this->assertSame('400.00', $this->balance());
    }

    public function test_the_minimum_is_one_hundred_baht_unless_the_back_office_sets_another(): void
    {
        $this->ask('99.99')->assertSessionHas('error', fn ($m) => str_contains($m, 'ขั้นต่ำ') && str_contains($m, '฿100.00'));
        $this->assertSame(0, WalletWithdrawal::count());

        config(['services.wallet.withdraw_min_baht' => '150']);
        $this->ask('120')->assertSessionHas('error', fn ($m) => str_contains($m, '฿150.00'));

        // หลังบ้านตั้งทับค่า env
        Setting::setValue('wallet_withdraw_min_amount', '50');
        $this->ask('60')->assertSessionHas('success');
        $this->assertSame('440.00', $this->balance());
    }

    public function test_more_than_the_balance_or_a_malformed_amount_is_refused_in_thai(): void
    {
        $this->ask('500.01')->assertSessionHas('error', fn ($m) => str_contains($m, 'ไม่พอ') && str_contains($m, '฿500.00'));

        foreach (['abc', '-100', '1e3', '100.123', ''] as $bad) {
            $this->ask($bad)->assertSessionHasErrors('amount');
        }
        // ความพยายามที่ผิดก็นับโควตา — ล้างก่อนลองของจริง
        Cache::flush();

        $this->assertSame(0, WalletWithdrawal::count());
        $this->assertSame('500.00', $this->balance());

        // ทั้งหมดถอนได้ — ยอดไม่ติดลบ ไม่เหลือเศษ
        $this->ask('500')->assertSessionHas('success');
        $this->assertSame('0.00', $this->balance());
    }

    public function test_an_unverified_account_cannot_request_and_is_sent_to_kyc(): void
    {
        $stranger = User::factory()->create();
        $this->fund($stranger, '1000.00');

        $this->ask('200', null, [], $stranger)->assertRedirect(route('kyc.index'))->assertSessionHas('error');
        $this->actingAs($stranger)
            ->postJson('/wallet/withdraw', ['amount' => '200', 'request_key' => (string) Str::uuid()])
            ->assertForbidden()
            ->assertJsonPath('error', 'kyc_required');

        // KYC ยังรอตรวจ = ยังถอนไม่ได้
        $stranger->forceFill(['kyc_status' => KycVerification::STATUS_PENDING])->save();
        $this->ask('200', null, [], $stranger)->assertRedirect(route('kyc.index'));

        $this->assertSame(0, WalletWithdrawal::count());
        $this->assertSame('1000.00', $this->balance($stranger));

        // หน้าถอนเปิดได้ และบอกว่าต้องทำอะไรก่อน
        $this->actingAs($stranger)->get('/wallet/withdraw')->assertOk()->assertSee('ไปหน้ายืนยันตัวตน')->assertDontSee('name="request_key"', false);
    }

    public function test_a_frozen_wallet_or_paused_withdrawals_refuse_the_request(): void
    {
        Wallet::where('user_id', $this->user->id)->update(['is_active' => false]);
        $this->ask('100')->assertSessionHas('error', fn ($m) => str_contains($m, 'ถูกระงับ'));

        Wallet::where('user_id', $this->user->id)->update(['is_active' => true]);
        Setting::setValue('wallet_withdraw_enabled', '0');
        $this->ask('100')->assertSessionHas('error', fn ($m) => str_contains($m, 'ปิดปรับปรุง'));
        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()->assertSee('ปิดปรับปรุงชั่วคราว');

        $this->assertSame(0, WalletWithdrawal::count());
        $this->assertSame('500.00', $this->balance());
    }

    public function test_requests_are_throttled(): void
    {
        foreach (range(1, 6) as $i) {
            // ขอแล้วยกเลิกทันที — วนขอซ้ำเร็ว ๆ ได้ไม่เกินที่จำกัด
            $this->ask('100')->assertSessionHas('success');
            $w = WalletWithdrawal::latest('id')->first();
            $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")->assertSessionHas('success');
        }

        $this->ask('100')->assertStatus(429);
        $this->assertSame('500.00', $this->balance());
    }

    public function test_without_a_kyc_bank_account_the_form_asks_for_one_and_checks_it(): void
    {
        $user = $this->verifiedUser(withBank: false);
        $this->fund($user, '300.00');

        $this->ask('150', null, [], $user)->assertSessionHasErrors(['method', 'account_name']);
        $this->ask('150', null, ['method' => 'promptpay', 'promptpay_id' => '12345', 'account_name' => 'สมหญิง ใจดี'], $user)
            ->assertSessionHasErrors('promptpay_id');
        $this->ask('150', null, ['method' => 'bank', 'bank_code' => 'NOPE', 'account_number' => '1234567890', 'account_name' => 'สมหญิง ใจดี'], $user)
            ->assertSessionHasErrors('bank_code');
        $this->assertSame(0, WalletWithdrawal::count());

        $this->ask('150', null, ['method' => 'promptpay', 'promptpay_id' => '081-234-5678', 'account_name' => 'สมหญิง  ใจดี'], $user)
            ->assertSessionHas('success');

        $w = $this->onlyRequest();
        $this->assertSame(WalletWithdrawal::SOURCE_FORM, $w->account_source);
        $this->assertSame('0812345678', $w->promptpay_id);
        $this->assertSame('สมหญิง ใจดี', $w->account_name);
        $this->assertSame('พร้อมเพย์ xxx-5678', $w->maskedDestination());

        // แอดมินถูกเตือนให้เทียบชื่อกับ KYC — เลขเต็มขึ้นหลังกดเริ่มโอน
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()
            ->assertSee('ผู้ใช้กรอกบัญชีนี้เอง')->assertSee('พร้อมเพย์ xxx-5678')->assertDontSee('0812345678');
        $this->start($w)->assertSessionHas('success');
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()->assertSee('0812345678');
    }

    public function test_a_form_account_is_ignored_when_kyc_has_one(): void
    {
        $this->ask('100', null, ['method' => 'bank', 'bank_code' => 'SCB', 'account_number' => '9999999999', 'account_name' => 'คนอื่น'])
            ->assertSessionHas('success');

        $w = $this->onlyRequest();
        $this->assertSame(self::ACCOUNT, $w->account_number);
        $this->assertSame('KBANK', $w->bank_code);
        $this->assertSame(WalletWithdrawal::SOURCE_KYC, $w->account_source);
    }

    // ── ยกเลิก ───────────────────────────────────────────────────────────

    public function test_the_user_can_cancel_and_gets_the_hold_back_exactly_once(): void
    {
        $this->ask('123.45')->assertSessionHas('success');
        $w = $this->onlyRequest();
        $this->assertSame('376.55', $this->balance());

        $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")
            ->assertRedirect(route('user.wallet.withdraw'))->assertSessionHas('success');

        $w->refresh();
        $this->assertSame(WalletWithdrawal::STATUS_CANCELLED, $w->status);
        $this->assertNull($w->open_user_id);
        $this->assertNotNull($w->cancelled_at);
        $this->assertSame('500.00', $this->balance());

        $this->assertSame(WalletTransaction::STATUS_CANCELLED, WalletTransaction::find($w->hold_transaction_id)->status);
        $returned = WalletTransaction::findOrFail($w->return_transaction_id);
        $this->assertSame('WDR' . $w->id, $returned->transaction_id);
        $this->assertSame('123.45', $returned->amount);
        $this->assertSame('376.55', $returned->balance_before);
        $this->assertSame('500.00', $returned->balance_after);
        $this->assertSame('คืนยอดที่ขอถอน', $returned->type_label);
        // เงินที่คืนไม่ใช่เงินเติมหรือเงินคืนจากการซื้อ
        $this->assertSame('0.00', Wallet::where('user_id', $this->user->id)->value('total_refunded'));

        // ยกเลิกซ้ำ — ไม่คืนสองรอบ
        $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")->assertSessionHas('error');
        $this->assertSame('500.00', $this->balance());
        $this->assertSame(1, WalletTransaction::where('type', 'refund')->count());

        // ขอใหม่ได้หลังยกเลิก
        $this->ask('100')->assertSessionHas('success');
        $this->actingAs($this->user)->get('/wallet/transactions')->assertOk()->assertSee('ยกเลิกแล้ว · คืนเข้ากระเป๋าแล้ว');
    }

    public function test_nobody_else_can_cancel_or_see_a_request(): void
    {
        $this->ask('100')->assertSessionHas('success');
        $w = $this->onlyRequest();
        $other = $this->verifiedUser();

        $this->actingAs($other)->post("/wallet/withdraw/{$w->id}/cancel")->assertNotFound();
        $this->actingAs($other)->get("/wallet/withdraw/{$w->id}/slip")->assertNotFound();
        $this->actingAs($other)->get('/wallet/withdraw')->assertOk()->assertDontSee($w->withdrawal_id);

        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->fresh()->status);
        $this->assertSame('400.00', $this->balance());
    }

    // ── แอดมิน ────────────────────────────────────────────────────────────

    public function test_admin_rejects_with_a_reason_and_the_hold_returns_exactly(): void
    {
        $this->ask('199.99')->assertSessionHas('success');
        $w = $this->onlyRequest();

        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/reject", ['reason' => 'สั้นไป'])
            ->assertSessionHasErrors('reason');
        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->fresh()->status);
        $this->assertSame('300.01', $this->balance());

        $this->actingAs($this->admin)
            ->post("/admin/wallets/withdrawals/{$w->id}/reject", ['reason' => 'ชื่อบัญชีไม่ตรงกับเอกสารยืนยันตัวตน'])
            ->assertSessionHas('success');

        $w->refresh();
        $this->assertSame(WalletWithdrawal::STATUS_REJECTED, $w->status);
        $this->assertSame('ชื่อบัญชีไม่ตรงกับเอกสารยืนยันตัวตน', $w->reject_reason);
        $this->assertSame($this->admin->id, $w->reviewed_by);
        $this->assertSame('500.00', $this->balance());
        $this->assertSame(WalletTransaction::STATUS_FAILED, WalletTransaction::find($w->hold_transaction_id)->status);
        $this->assertSame('199.99', WalletTransaction::find($w->return_transaction_id)->amount);

        // ปฏิเสธซ้ำ / อนุมัติหลังปฏิเสธ — ไม่มีผล
        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/reject", ['reason' => 'กดซ้ำจากแท็บเก่าที่เปิดค้างไว้'])
            ->assertSessionHas('error');
        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/approve", ['payout_reference' => 'REF-1'])
            ->assertSessionHas('error');
        $this->assertSame('500.00', $this->balance());
        $this->assertSame(WalletWithdrawal::STATUS_REJECTED, $w->fresh()->status);

        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()
            ->assertSee('ไม่อนุมัติ · คืนเข้ากระเป๋าแล้ว')->assertSee('ชื่อบัญชีไม่ตรงกับเอกสารยืนยันตัวตน');
    }

    public function test_admin_approves_with_a_reference_and_slip_and_the_owner_sees_it(): void
    {
        $this->ask('250')->assertSessionHas('success');
        $w = $this->onlyRequest();

        // บันทึกว่าโอนแล้วโดยยังไม่กดเริ่มโอนไม่ได้ — ระหว่างนั้นผู้ใช้ยังยกเลิกได้
        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/approve", ['payout_reference' => 'EARLY'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'เริ่มโอน'));
        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->fresh()->status);

        $this->start($w)->assertSessionHas('success', fn ($m) => str_contains($m, 'ยกเลิกไม่ได้แล้ว'));
        $w->refresh();
        $this->assertSame(WalletWithdrawal::STATUS_PROCESSING, $w->status);
        $this->assertSame($this->admin->id, $w->processing_by);
        $this->assertNotNull($w->processing_at);
        $this->assertSame($this->user->id, $w->open_user_id, 'still the one open request');
        $this->assertSame('250.00', $this->balance(), 'still held, not moved again');

        // ต้องมีเลขอ้างอิงหรือสลิปอย่างน้อยหนึ่งอย่าง
        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/approve", [])
            ->assertSessionHasErrors(['payout_reference', 'slip']);
        $this->assertSame(WalletWithdrawal::STATUS_PROCESSING, $w->fresh()->status);

        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/approve", [
            'payout_reference' => 'KBANK-202609261234',
            'slip' => UploadedFile::fake()->image('slip.png', 600, 900),
        ])->assertSessionHas('success');

        $w->refresh();
        $this->assertSame(WalletWithdrawal::STATUS_APPROVED, $w->status);
        $this->assertNull($w->open_user_id);
        $this->assertSame('KBANK-202609261234', $w->payout_reference);
        $this->assertStringStartsWith('withdrawals/' . $this->user->id . '/', $w->slip_path);
        Storage::disk('local')->assertExists($w->slip_path);

        $hold = WalletTransaction::find($w->hold_transaction_id);
        $this->assertSame(WalletTransaction::STATUS_COMPLETED, $hold->status);
        $this->assertSame('KBANK-202609261234', $hold->payment_reference);
        $this->assertSame('โอนเข้าบัญชีแล้ว', $hold->stateLabel());
        // ยอดถูกหักไปตั้งแต่ตอนขอ — อนุมัติไม่หักซ้ำ
        $this->assertSame('250.00', $this->balance());
        $this->assertNull($w->return_transaction_id);

        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()
            ->assertSee('โอนเข้าบัญชีแล้ว')->assertSee('KBANK-202609261234')->assertSee('ดูสลิปการโอน');
        $this->actingAs($this->user)->get("/wallet/withdraw/{$w->id}/slip")->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}/slip")->assertOk();

        // ผู้ใช้ยกเลิกหลังโอนแล้วไม่ได้
        $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")->assertSessionHas('error');
        $this->assertSame('250.00', $this->balance());
    }

    public function test_two_admins_approving_at_once_and_a_late_cancel_have_one_effect(): void
    {
        $this->ask('300')->assertSessionHas('success');
        $service = app(WalletWithdrawalService::class);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินคนที่สอง']);

        // สองแท็บกดเริ่มโอนพร้อมกัน — คนที่สองรู้ว่ามีคนโอนอยู่แล้ว ไม่โอนซ้ำ
        $startA = WalletWithdrawal::sole();
        $startB = WalletWithdrawal::sole();
        $service->startTransfer($startA, $this->admin);
        try {
            $service->startTransfer($startB, $otherAdmin);
            $this->fail('the second start must be refused');
        } catch (WithdrawalRefused $e) {
            $this->assertStringContainsString('เริ่มโอนไปแล้ว โดย แอดมินการเงิน', $e->getMessage());
        }
        $this->assertSame($this->admin->id, WalletWithdrawal::sole()->processing_by);

        // สองแท็บโหลดคำขอเดียวกันไว้ก่อน
        $first = WalletWithdrawal::sole();
        $second = WalletWithdrawal::sole();
        $userCopy = WalletWithdrawal::sole();

        $service->approve($first, $this->admin, 'REF-A', null);

        try {
            $service->approve($second, $otherAdmin, 'REF-B', null);
            $this->fail('the second approval must be refused');
        } catch (WithdrawalRefused $e) {
            $this->assertStringContainsString('โอนเข้าบัญชีแล้ว', $e->getMessage());
        }

        try {
            $service->cancel($this->user, $userCopy);
            $this->fail('cancelling an approved request must be refused');
        } catch (WithdrawalRefused) {
        }

        $w = WalletWithdrawal::sole();
        $this->assertSame('REF-A', $w->payout_reference);
        $this->assertSame($this->admin->id, $w->reviewed_by);
        $this->assertSame('200.00', $this->balance());
        $this->assertSame(0, WalletTransaction::where('type', 'refund')->count());
    }

    public function test_a_cancel_before_the_transfer_starts_stops_the_admin(): void
    {
        $this->ask('300')->assertSessionHas('success');
        $service = app(WalletWithdrawalService::class);
        $adminCopy = WalletWithdrawal::sole();

        $service->cancel($this->user, WalletWithdrawal::sole());

        // แอดมินที่เปิดหน้าค้างไว้กดเริ่มโอนไม่ได้ — ไม่มีอะไรให้โอนแล้ว
        try {
            $service->startTransfer($adminCopy, $this->admin);
            $this->fail('starting a cancelled request must be refused');
        } catch (WithdrawalRefused $e) {
            $this->assertStringContainsString('ยกเลิกแล้ว', $e->getMessage());
        }

        $this->expectException(WithdrawalRefused::class);
        try {
            $service->approve($adminCopy, $this->admin, 'REF-LATE', UploadedFile::fake()->image('late.png', 400, 400));
        } finally {
            $this->assertSame(WalletWithdrawal::STATUS_CANCELLED, WalletWithdrawal::sole()->status);
            $this->assertSame('500.00', $this->balance());
            // สลิปของการอนุมัติที่แพ้ไม่ค้างบนดิสก์
            $this->assertSame([], Storage::disk('local')->allFiles('withdrawals'));
        }
    }

    /**
     * รีวิวรอบสอง: แอดมินโอนในแอปธนาคารก่อนแล้วค่อยกลับมาบันทึก ถ้าผู้ใช้ยังยกเลิกได้ในช่วงนั้น
     * ยอดที่พักจะคืนเข้ากระเป๋าทั้งที่เงินสดออกไปแล้ว — จ่ายสองทาง
     */
    public function test_once_the_admin_starts_the_transfer_the_user_can_no_longer_cancel(): void
    {
        $this->ask('300')->assertSessionHas('success');
        $w = $this->onlyRequest();
        $userCopy = WalletWithdrawal::sole();

        $this->start($w)->assertSessionHas('success');

        // ผู้ใช้เห็นเงินเข้าบัญชีแล้วกดยกเลิก (ทั้งทางหน้าเว็บ และจากหน้าเก่าที่ยังเป็น pending)
        $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ยกเลิกไม่ได้แล้ว'));
        try {
            app(WalletWithdrawalService::class)->cancel($this->user, $userCopy);
            $this->fail('a stale pending copy must not cancel a request being transferred');
        } catch (WithdrawalRefused) {
        }

        $this->assertSame(WalletWithdrawal::STATUS_PROCESSING, $w->fresh()->status);
        $this->assertSame('200.00', $this->balance(), 'the hold stays held');
        $this->assertSame(0, WalletTransaction::where('type', 'refund')->count());

        // หน้าผู้ใช้บอกว่ากำลังโอน และไม่มีปุ่มยกเลิก
        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()
            ->assertSee('แอดมินกำลังโอนเงินให้คุณ')->assertSee('ยกเลิกคำขอนี้ไม่ได้แล้ว')->assertDontSee('ยกเลิกคำขอนี้</span>', false)
            ->assertDontSee(route('user.wallet.withdraw.cancel', $w->id), false);
        $this->actingAs($this->user)->get('/wallet')->assertOk()->assertSee('แอดมินกำลังโอนเงินถอน');

        // ยังมีคำขอเปิดอยู่ — ขอใหม่ไม่ได้
        $this->ask('100')->assertSessionHas('error', fn ($m) => str_contains($m, 'กำลังโอนอยู่'));

        // แอดมินบันทึกผลได้ตามปกติ
        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/approve", ['payout_reference' => 'SCB-7788'])
            ->assertSessionHas('success');
        $this->assertSame(WalletWithdrawal::STATUS_APPROVED, $w->fresh()->status);
        $this->assertSame('200.00', $this->balance());
    }

    public function test_the_admin_can_still_reject_after_starting_and_the_hold_returns_once(): void
    {
        $this->ask('150')->assertSessionHas('success');
        $w = $this->onlyRequest();
        $this->start($w)->assertSessionHas('success');

        // หน้ารับเรื่องเตือนว่าห้ามปฏิเสธถ้าโอนไปแล้ว
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()
            ->assertSee('ใช้เมื่อยังไม่ได้โอนหรือโอนไม่ผ่านเท่านั้น')->assertSee('เริ่มโอนโดย');

        $this->actingAs($this->admin)->post("/admin/wallets/withdrawals/{$w->id}/reject", ['reason' => 'โอนไม่ผ่าน บัญชีปลายทางถูกปิด'])
            ->assertSessionHas('success');

        $w->refresh();
        $this->assertSame(WalletWithdrawal::STATUS_REJECTED, $w->status);
        $this->assertNull($w->open_user_id);
        $this->assertSame('500.00', $this->balance());
        $this->assertSame(1, WalletTransaction::where('type', 'refund')->count());

        $this->start($w)->assertSessionHas('error');
        $this->assertSame('500.00', $this->balance());
    }

    public function test_a_wallet_that_went_negative_cannot_be_started(): void
    {
        $this->ask('200')->assertSessionHas('success');
        $w = $this->onlyRequest();

        // ปรับยอดด้วยมือจนติดลบ (หรือรายการที่ตัดเงินซ้อนกันจากที่อื่น)
        Wallet::where('user_id', $this->user->id)->update(['balance' => '-50.00']);

        $this->start($w)->assertSessionHas('error', fn ($m) => str_contains($m, 'ติดลบ') && str_contains($m, '−฿50.00'));
        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->fresh()->status);
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()->assertSee('กระเป๋าติดลบ');
    }

    public function test_a_stale_form_does_not_report_a_request_that_is_not_there(): void
    {
        $key = (string) Str::uuid();
        $this->ask('150', $key)->assertSessionHas('success');
        $w = $this->onlyRequest();

        // กุญแจเดิม ยอดใหม่ ขณะคำขอเดิมยังเปิด — ไม่ใช่การกดซ้ำ
        $this->ask('180', $key)->assertSessionHas('error', fn ($m) => str_contains($m, 'ฟอร์มนี้ใช้ส่งคำขอ'));

        // ยกเลิกแล้วกด Back กลับไปที่ฟอร์มเดิม (เบราว์เซอร์จำกุญแจไว้)
        $this->actingAs($this->user)->post("/wallet/withdraw/{$w->id}/cancel")->assertSessionHas('success');
        $this->ask('150', $key)->assertSessionHas('error', fn ($m) => str_contains($m, 'ฟอร์มนี้ใช้ส่งคำขอ') && str_contains($m, 'ยกเลิกแล้ว'));
        $this->ask('800', $key)->assertSessionHas('error', fn ($m) => str_contains($m, 'โหลดหน้าใหม่'));

        // ฟอร์มที่กลับมาได้กุญแจใหม่ — ส่งอีกครั้งได้เลย ไม่วนเจอข้อความเดิม
        $this->actingAs($this->user)->get('/wallet/withdraw')->assertOk()->assertSee('name="request_key"', false)->assertDontSee($key);

        $this->assertSame(1, WalletWithdrawal::count());
        $this->assertSame('500.00', $this->balance());
        $this->assertNull(app(WalletWithdrawalService::class)->openFor($this->user->id));
    }

    public function test_the_request_page_shows_where_the_money_came_from(): void
    {
        $wallet = Wallet::where('user_id', $this->user->id)->first();
        // เงินเดิม ฿500 จาก setUp ไม่มีรายการ — เพิ่มที่มาให้เห็น: รายได้ ฿120, เติมเงินด้วยบัตร ฿300 (+โบนัส ฿30)
        DB::transaction(function () use ($wallet) {
            app(WalletLedger::class)->credit($wallet, 12000, [
                'transaction_id' => 'GXM' . $this->user->id . '-1',
                'type' => WalletTransaction::TYPE_EARNING,
                'status' => WalletTransaction::STATUS_COMPLETED,
            ]);
        });
        WalletTopup::create([
            'wallet_id' => $wallet->id,
            'user_id' => $this->user->id,
            'topup_id' => 'TOPTEST1',
            'amount' => '300.00',
            'bonus_amount' => '30.00',
            'total_amount' => '330.00',
            'payment_method' => 'stripe',
            'status' => 'pending',
        ])->approve($this->admin->id);
        $this->assertSame('950.00', $this->balance());

        $this->ask('400')->assertSessionHas('success');
        $w = $this->onlyRequest();

        $funds = app(WalletWithdrawalService::class)->fundsFor($w);
        $this->assertSame(55000, $funds['balance']);
        $this->assertSame(12000, $funds['earnings']);
        $this->assertSame(33000, $funds['deposits']);
        $this->assertSame(3000, $funds['topup_bonus']);
        $this->assertSame(40000, $funds['withdrawn']);
        $this->assertSame(28000, $funds['over_earned']);
        $this->assertTrue($funds['recent_topup']);

        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()
            ->assertSee('กระเป๋าและที่มาของเงิน')->assertSee('฿550.00')->assertSee('฿120.00')
            ->assertSee('เกินรายได้และค่าแนะนำ GPUxMINE สุทธิ')->assertSee('chargeback');
    }

    public function test_the_admin_queue_is_for_admins_only(): void
    {
        $this->ask('100')->assertSessionHas('success');
        $w = $this->onlyRequest();

        $this->actingAs($this->user)->get('/admin/wallets/withdrawals')->assertForbidden();
        $this->actingAs($this->user)->get("/admin/wallets/withdrawals/{$w->id}")->assertForbidden();
        $this->actingAs($this->user)->post("/admin/wallets/withdrawals/{$w->id}/start")->assertForbidden();
        $this->actingAs($this->user)->post("/admin/wallets/withdrawals/{$w->id}/approve", ['payout_reference' => 'SELF'])->assertForbidden();
        $this->actingAs($this->user)->post("/admin/wallets/withdrawals/{$w->id}/reject", ['reason' => 'ขอคืนเงินให้ตัวเองหน่อย'])->assertForbidden();
        $this->actingAs($this->user)->get('/admin/wallets/withdrawals/export')->assertForbidden();

        $this->assertSame(WalletWithdrawal::STATUS_PENDING, $w->fresh()->status);
        $this->assertSame('400.00', $this->balance());
    }

    public function test_the_admin_queue_lists_filters_and_shows_the_full_account_only_on_the_request_page(): void
    {
        $this->ask('100')->assertSessionHas('success');
        $w = $this->onlyRequest();

        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals')->assertOk()
            ->assertSee($w->withdrawal_id)->assertSee('กสิกรไทย xxx-7890')->assertDontSee(self::ACCOUNT)->assertSee('คำขอถอนเงิน');
        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals?status=approved')->assertOk()->assertDontSee($w->withdrawal_id);
        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals?status=all&q=' . urlencode($this->user->email))->assertOk()->assertSee($w->withdrawal_id);
        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals?q[]=x&status[]=y')->assertOk();

        // ก่อนเริ่มโอน: เลขปิดกลาง มีปุ่มเริ่มโอนและปุ่มไม่อนุมัติ ไม่มีฟอร์มบันทึกผล
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()
            ->assertDontSee(self::ACCOUNT)->assertSee('กสิกรไทย xxx-7890')->assertSee('เริ่มโอน')->assertDontSee('บันทึกว่าโอนแล้ว')
            ->assertSee('ไม่อนุมัติ')->assertSee('window.confirm', false);

        $this->start($w)->assertSessionHas('success');
        // ออกจากคิวรออนุมัติ ไปอยู่แท็บกำลังโอน (ข้อความสำเร็จของการกดยังขึ้นเลขคำขอบนหน้าถัดไป — ดูที่รายการว่าง)
        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals')->assertOk()->assertSee('ไม่มีคำขอที่รออนุมัติ');
        $this->actingAs($this->admin)->get('/admin/wallets/withdrawals?status=processing')->assertOk()
            ->assertSee($w->withdrawal_id)->assertSee('แอดมินกำลังโอน')->assertSee('บันทึกผลการโอน')->assertDontSee(self::ACCOUNT);
        $this->actingAs($this->admin)->get("/admin/wallets/withdrawals/{$w->id}")->assertOk()
            ->assertSee(self::ACCOUNT)->assertSee('บันทึกว่าโอนแล้ว')->assertSee('ไม่อนุมัติ')->assertSee('window.confirm', false);
    }

    public function test_the_export_masks_the_account_and_defuses_spreadsheet_formulas(): void
    {
        $user = $this->verifiedUser(withBank: false);
        $this->fund($user, '300.00');
        $this->ask('150', null, ['method' => 'bank', 'bank_code' => 'SCB', 'account_number' => '5555555555', 'account_name' => '=HYPERLINK("http://evil")'], $user)
            ->assertSessionHas('success');
        $this->ask('100')->assertSessionHas('success');

        $csv = $this->actingAs($this->admin)->get('/admin/wallets/withdrawals/export?status=all')->assertOk()->streamedContent();

        $this->assertStringContainsString('ไทยพาณิชย์ xxx-5555', $csv);
        $this->assertStringNotContainsString('5555555555', $csv);
        $this->assertStringNotContainsString(self::ACCOUNT, $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('100.00', $csv);
    }

    public function test_the_admin_hears_about_a_request_on_telegram_without_the_full_account_number(): void
    {
        $this->telegramOn();

        $this->ask('321')->assertSessionHas('success');
        $w = $this->onlyRequest();

        $sent = Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendPhoto') || str_ends_with($r->url(), '/sendMessage'))
            ->values();
        $this->assertCount(1, $sent);
        // caption ของรูปการ์ด หรือข้อความ (เมื่อวาดรูปไม่ได้) — ไม่ใช่ไบต์ของรูป
        $body = '';
        foreach ((array) $sent[0]->data() as $key => $part) {
            if (is_array($part) && in_array($part['name'] ?? null, ['caption', 'text', 'reply_markup'], true)) {
                $body .= (string) $part['contents'];
            } elseif (in_array($key, ['caption', 'text', 'reply_markup'], true)) {
                $body .= (string) $part;
            }
        }
        $this->assertStringContainsString($w->withdrawal_id, $body);
        $this->assertStringContainsString('xxx-7890', $body);
        $this->assertStringNotContainsString(self::ACCOUNT, $body);
        $this->assertSame('withdrawal:' . $w->id, DB::table('admin_alerts')->value('subject'));
    }

    public function test_the_machine_status_shows_the_withdraw_page_and_the_amount_held(): void
    {
        $node = GpuNode::factory()->paired()->create(['user_id' => $this->user->id]);
        $this->ask('120.25')->assertSessionHas('success');

        $this->postJson('/api/v1/product/gpuxmine/status', ['worker_id' => $node->worker_id, 'token' => $node->relay_token])
            ->assertOk()
            ->assertJsonPath('data.earnings.withdraw_url', route('user.wallet.withdraw'))
            ->assertJsonPath('data.earnings.withdrawal_pending_satang', 12025)
            ->assertJsonPath('data.earnings.wallet_balance_satang', 37975);

        // แอดมินเริ่มโอนแล้ว — ยังเป็นเงินที่รอถอนอยู่
        $this->start(WalletWithdrawal::sole())->assertSessionHas('success');
        $this->postJson('/api/v1/product/gpuxmine/status', ['worker_id' => $node->worker_id, 'token' => $node->relay_token])
            ->assertOk()
            ->assertJsonPath('data.earnings.withdrawal_pending_satang', 12025);

        Setting::setValue('wallet_withdraw_enabled', '0');
        $this->postJson('/api/v1/product/gpuxmine/status', ['worker_id' => $node->worker_id, 'token' => $node->relay_token])
            ->assertOk()
            ->assertJsonPath('data.earnings.withdraw_url', null);
    }

    public function test_the_back_office_sets_the_minimum_and_can_pause_withdrawals(): void
    {
        $this->actingAs($this->admin)->get('/admin/wallets/settings')->assertOk()->assertSee('ยอดถอนขั้นต่ำต่อคำขอ')->assertSee('100.00');

        $this->actingAs($this->admin)->put('/admin/wallets/settings', [
            'wallet_topup_min_amount' => 100,
            'wallet_topup_max_amount' => 100000,
            'wallet_topup_expiry_minutes' => 30,
            'wallet_quick_amounts' => '100,500',
            'wallet_enabled' => '1',
            'wallet_withdraw_min_amount' => '250.50',
        ])->assertSessionHas('success');

        $service = app(WalletWithdrawalService::class);
        $this->assertSame(25050, $service->minSatang());
        $this->assertFalse($service->enabled(), 'an unticked box pauses withdrawals');

        // หน้าตั้งค่าที่เปิดค้างจากก่อนอัปเดต (ไม่มีช่องถอนเงิน) ไม่ไปแตะค่าการถอน
        Setting::setValue('wallet_withdraw_enabled', '1');
        $this->actingAs($this->admin)->put('/admin/wallets/settings', [
            'wallet_topup_min_amount' => 100,
            'wallet_topup_max_amount' => 100000,
            'wallet_topup_expiry_minutes' => 30,
            'wallet_quick_amounts' => '100,500',
        ])->assertSessionHas('success');
        $this->assertTrue($service->enabled());
        $this->assertSame(25050, $service->minSatang());
    }
}
