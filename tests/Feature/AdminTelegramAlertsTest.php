<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\UniquePaymentAmount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Support\AdminAlerts;
use App\Support\Alerts\Alert;
use App\Support\Alerts\AlertCard;
use App\Support\Alerts\BusinessAlerts;
use App\Support\Alerts\ErrorAlert;
use App\Support\Alerts\Reports;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Telegram\BotActions;
use App\Support\Telegram\TelegramAdmins;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * The admin Telegram pipeline: business events become cards, cards carry buttons, buttons change
 * real records — and every way that goes wrong in practice: it spams (throttle, caps), it goes
 * somewhere nobody asked, it leaks, it lets the wrong person press "approve", or it approves twice.
 */
class AdminTelegramAlertsTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';

    private const SECRET = 'webhook-secret-for-tests-0123456789abcdefghijkl';

    private const ADMIN_TG = 424242;

    protected function setUp(): void
    {
        parent::setUp();
        // Every ceiling counts per clock slot — the hour (category caps, ErrorAlert), the Thai day
        // (security tallies), the minute (button presses) — and a test that runs across an edge
        // starts a fresh count halfway: at 23:00 all 25 contact alerts got past a ceiling of 20.
        // Ten minutes into the current hour is clear of both edges, and still close enough to the
        // real clock for quietUntil(), which compares against time().
        $this->travelTo(now()->startOfHour()->addMinutes(10));
        Cache::flush();
        Http::preventStrayRequests();
        BusinessAlerts::$actor = null;
        AdminAlerts::$immediate = false;
    }

    /** Registered per test, not in setUp: the first matching stub wins. */
    private function fakeTelegram(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 77]])]);
    }

    private function telegramOn(): void
    {
        Setting::setValue('telegram_bot_token', self::TOKEN, 'string', 'telegram');
        Setting::setValue('telegram_chat_id', '-1001234567890', 'string', 'telegram');
        Setting::setValue('telegram_bot_username', 'XmanAlertBot', 'string', 'telegram');
        Setting::setValue('telegram_alerts_enabled', '1', 'boolean', 'telegram');
    }

    private function webhookOn(): void
    {
        Setting::setValue('telegram_webhook_secret', self::SECRET, 'string', 'telegram');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'name' => 'แอดมินทดสอบ']);
    }

    private function linkedAdmin(): User
    {
        $admin = $this->admin();
        TelegramAdmins::link(self::ADMIN_TG, $admin, 'Owner');

        return $admin;
    }

    private function order(array $overrides = []): Order
    {
        return Order::create($overrides + [
            'order_number' => 'XM20260913-' . random_int(1000, 9999),
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'somchai@example.com',
            'customer_phone' => '0812345678',
            'subtotal' => 4990,
            'total' => 4990,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /** A field of a Telegram request, whether it went as multipart (photo) or a form (text). */
    private static function field(Request $r, string $name): ?string
    {
        foreach ((array) $r->data() as $key => $part) {
            if (is_array($part) && ($part['name'] ?? null) === $name) {
                return (string) $part['contents'];
            }
            if ($key === $name) {
                return (string) $part;
            }
        }

        return null;
    }

    /** @return array<int,Request> Telegram calls to $method */
    private function calls(string $method): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_ends_with($r->url(), '/' . $method))->values()->all();
    }

    /** @return array<int,Request> every alert that went out (photo card or text fallback) */
    private function sends(): array
    {
        return array_merge($this->calls('sendPhoto'), $this->calls('sendMessage'));
    }

    private function press(string $data, int $fromId = self::ADMIN_TG, int $updateId = 1): TestResponse
    {
        return $this->postJson('/api/telegram/webhook', [
            'update_id' => $updateId,
            'callback_query' => [
                'id' => 'cb' . $updateId,
                'from' => ['id' => $fromId, 'first_name' => 'Owner'],
                'data' => $data,
                'message' => ['message_id' => 77, 'chat' => ['id' => -1001234567890, 'type' => 'supergroup']],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET]);
    }

    // ============================================================================ delivery

    public function test_a_new_order_arrives_as_a_card_with_approve_and_reject_buttons(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();

        $order = $this->order(['customer_name' => 'ลูกค้า <b>ทดสอบ</b> & co']);

        $sends = $this->sends();
        $this->assertCount(1, $sends);
        $r = $sends[0];
        $this->assertStringEndsWith(AlertCard::available() ? '/sendPhoto' : '/sendMessage', $r->url());
        $this->assertSame('-1001234567890', self::field($r, 'chat_id'));

        $caption = (string) (self::field($r, 'caption') ?? self::field($r, 'text'));
        $this->assertStringContainsString($order->order_number, $caption);
        $this->assertStringContainsString('ลูกค้า &lt;b&gt;ทดสอบ&lt;/b&gt; &amp; co', $caption, 'customer text is escaped');

        $keyboard = json_decode((string) self::field($r, 'reply_markup'), true)['inline_keyboard'];
        $this->assertSame(['oy', 'on'], array_map(fn ($b) => explode(':', $b['callback_data'])[0], $keyboard[0]));

        $row = DB::table('admin_alerts')->first();
        $this->assertSame('order:' . $order->id, $row->subject);
        $this->assertSame(77, (int) $row->message_id);
        $this->assertTrue((bool) $row->ok);
    }

    public function test_nothing_is_sent_when_telegram_is_off_or_the_category_is_switched_off(): void
    {
        $this->fakeTelegram();
        $this->order();                             // not configured at all
        $this->telegramOn();
        AdminAlerts::saveCategories(['security']);  // orders switched off
        $this->order();

        $this->assertCount(0, $this->sends());
    }

    public function test_the_same_alert_key_is_silent_during_its_cooling_off(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        $alert = new Alert(key: 'k', level: Alert::WARNING, title: 'x', category: 'system');

        $this->assertTrue(AdminAlerts::send($alert, 60));
        $this->assertFalse(AdminAlerts::send($alert, 60));
        $this->assertCount(1, $this->sends());
    }

    public function test_a_flood_of_contact_messages_folds_into_one_digest(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        for ($i = 0; $i < 25; $i++) {
            AdminAlerts::send(new Alert(key: 'contact:' . $i, level: Alert::MONEY, title: 'spam ' . $i, category: 'contact'));
        }
        $this->assertCount(20, $this->sends(), 'the hourly ceiling for contact is 20');

        $this->assertSame(5, AdminAlerts::flushOverflow());
        $this->assertCount(21, $this->sends());
    }

    public function test_quiet_hours_silence_everything_but_critical(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        AdminAlerts::setQuiet(60);

        AdminAlerts::send(new Alert(key: 'a', level: Alert::MONEY, title: 'order', category: 'orders'));
        AdminAlerts::send(new Alert(key: 'b', level: Alert::CRITICAL, title: 'down', category: 'system'));

        [$money, $critical] = $this->sends();
        $this->assertSame('true', self::field($money, 'disable_notification'));
        $this->assertSame('false', self::field($critical, 'disable_notification'));
    }

    public function test_a_payment_arriving_without_a_person_is_news_but_an_admin_approval_is_not(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        $a = $this->order();
        $b = $this->order();
        $before = count($this->sends());

        // Fresh instances: a payment lands in a later request, never on the object that created the order.
        $a->fresh()->update(['payment_status' => 'paid', 'paid_at' => now()]);   // e.g. the SMS matcher
        $this->assertCount($before + 1, $this->sends(), 'money in by the system gets its own card');

        $this->actingAs($this->admin());
        $b->fresh()->update(['payment_status' => 'paid', 'paid_at' => now()]);  // an admin pressed approve
        $this->assertCount($before + 1, $this->sends(), 'no new card — the existing one is edited');
        $this->assertNotEmpty($this->calls('editMessageMedia'));
    }

    public function test_a_slip_inside_double_encoded_metadata_is_still_found(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        // AutoTradeX/Tping/SmsChecker save json_encode()d text into the array-cast column.
        $order = $this->order(['metadata' => json_encode(['plan' => 'pro'])]);
        $before = count($this->sends());

        $meta = json_decode(Order::find($order->id)->metadata, true);
        $meta['payment_slip'] = 'payment-slips/autotradex/x.webp';
        Order::find($order->id)->update(['status' => 'processing', 'metadata' => json_encode($meta)]);

        $this->assertCount($before + 1, $this->sends());
        $this->assertSame(1, Reports::pendingCounts()['slips']);
    }

    // ============================================================================ webhook & buttons

    public function test_the_webhook_refuses_requests_without_the_secret(): void
    {
        $this->telegramOn();
        $this->postJson('/api/telegram/webhook', ['update_id' => 1])->assertForbidden();   // webhook off

        $this->webhookOn();
        $this->postJson('/api/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'nope'])->assertForbidden();
        $this->postJson('/api/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])->assertOk();
    }

    public function test_the_webhook_is_not_throttled_per_ip(): void
    {
        // Every update comes from Telegram's own few IPs; a per-IP limit would let a stranger who
        // spams the bot lock the admin's button presses out.
        $this->telegramOn();
        $this->webhookOn();
        for ($i = 1; $i <= 70; $i++) {
            $this->postJson('/api/telegram/webhook', ['update_id' => 1000 + $i], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])
                ->assertOk();
        }
    }

    public function test_someone_in_the_chat_who_is_not_a_linked_admin_cannot_approve(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $order = $this->order();

        $this->press(BotActions::sign('oY', (string) $order->id), fromId: 999)->assertOk();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $answer = $this->calls('answerCallbackQuery')[0];
        $this->assertStringContainsString('ยังไม่ได้ผูก', (string) self::field($answer, 'text'));
    }

    public function test_a_forged_button_is_refused(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $order = $this->order();

        $this->press('oY:' . $order->id . ':AAAAAAAAAA')->assertOk();

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_approving_takes_two_presses_and_happens_once(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $order = $this->order();

        // First press only asks.
        $this->press(BotActions::sign('oy', (string) $order->id), updateId: 1);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $confirm = json_decode((string) self::field($this->calls('editMessageReplyMarkup')[0], 'reply_markup'), true);
        $this->assertStringStartsWith('oY:', $confirm['inline_keyboard'][0][0]['callback_data']);

        // Second press approves — through the same service as the admin page.
        $this->press(BotActions::sign('oY', (string) $order->id), updateId: 2);
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertStringContainsString('ยืนยันการชำระผ่าน Telegram', (string) $order->notes);
        $this->assertStringNotContainsString('แอดมินทดสอบ', (string) $order->notes, 'the admin\'s name is not written where the customer can see it');
        $paidAt = $order->paid_at;

        // A double-tap (or a second admin) changes nothing.
        $this->travel(5)->minutes();
        $this->press(BotActions::sign('oY', (string) $order->id), updateId: 3);
        $this->assertEquals($paidAt, $order->fresh()->paid_at);

        // Telegram re-delivering the same update is ignored outright.
        $answers = count($this->calls('answerCallbackQuery'));
        $this->press(BotActions::sign('oY', (string) $order->id), updateId: 3);
        $this->assertCount($answers, $this->calls('answerCallbackQuery'));
    }

    public function test_a_top_up_approved_from_telegram_is_deposited_exactly_once(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $customer = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 0]);
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id, 'user_id' => $customer->id, 'topup_id' => WalletTopup::generateTopupId(),
            'amount' => 500, 'bonus_amount' => 0, 'total_amount' => 500,
            'payment_method' => WalletTopup::METHOD_TRUEMONEY, 'status' => WalletTopup::STATUS_PENDING,
        ]);

        $this->press(BotActions::sign('tY', (string) $topup->id), updateId: 1);
        $this->press(BotActions::sign('tY', (string) $topup->id), updateId: 2);

        $this->assertSame(WalletTopup::STATUS_APPROVED, $topup->fresh()->status);
        $this->assertEquals(500.0, (float) $wallet->fresh()->balance);
    }

    public function test_a_transfer_the_sms_matcher_parked_for_review_gets_a_card_and_is_confirmed_the_sms_way(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $amount = UniquePaymentAmount::create([
            'base_amount' => 4990, 'unique_amount' => 4990.37, 'decimal_suffix' => 37,
            'transaction_id' => 0, 'transaction_type' => 'order', 'status' => 'reserved', 'expires_at' => now()->addHour(),
        ]);
        $order = $this->order(['unique_payment_amount_id' => $amount->id]);
        $before = count($this->sends());

        // What SmsPaymentNotification::matchOrder does in manual/smart mode.
        Order::find($order->id)->update(['sms_verification_status' => 'matched', 'payment_status' => 'processing']);

        $sends = $this->sends();
        $this->assertCount($before + 1, $sends, 'a matched transfer waiting on a person is its own card');
        $keyboard = json_decode((string) self::field(end($sends), 'reply_markup'), true)['inline_keyboard'];
        $this->assertStringStartsWith('oy:', $keyboard[0][0]['callback_data']);

        $this->press(BotActions::sign('oY', (string) $order->id), updateId: 20);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->sms_verification_status, 'decided the way the SMS Payment page decides');
    }

    public function test_a_pending_card_payment_offers_no_approve_button(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        $this->order(['payment_method' => 'stripe']);

        $markup = json_decode((string) self::field($this->sends()[0], 'reply_markup'), true);
        $data = collect($markup['inline_keyboard'] ?? [])->flatten(1)->pluck('callback_data')->filter()->all();
        $this->assertSame([], $data, 'Stripe decides card payments; there is nothing for a person to confirm');
    }

    public function test_security_noise_cannot_bury_a_critical_alert(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        for ($i = 0; $i < 15; $i++) {
            AdminAlerts::send(new Alert(key: 'probe:' . $i, level: Alert::INFO, title: 'noise ' . $i, category: 'security'));
        }
        $this->assertCount(12, $this->sends());

        $this->assertTrue(AdminAlerts::send(new Alert(key: 'admin-brute:x', level: Alert::CRITICAL, title: 'real attack', category: 'security')));
        $this->assertCount(13, $this->sends(), 'CRITICAL has its own ceiling');
    }

    public function test_a_disabled_admin_account_loses_the_buttons(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $admin = $this->linkedAdmin();
        $order = $this->order();

        $admin->update(['is_active' => false]);
        $this->press(BotActions::sign('oY', (string) $order->id));

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_a_top_up_refused_from_telegram_shows_the_customer_a_neutral_reason(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $this->linkedAdmin();
        $customer = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 0]);
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id, 'user_id' => $customer->id, 'topup_id' => WalletTopup::generateTopupId(),
            'amount' => 300, 'bonus_amount' => 0, 'total_amount' => 300,
            'payment_method' => WalletTopup::METHOD_TRUEMONEY, 'status' => WalletTopup::STATUS_PENDING,
        ]);

        $this->press(BotActions::sign('tN', (string) $topup->id));

        $topup->refresh();
        $this->assertSame(WalletTopup::STATUS_REJECTED, $topup->status);
        $this->assertStringNotContainsString('แอดมินทดสอบ', (string) $topup->reject_reason);
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }

    public function test_an_admin_who_loses_the_role_loses_the_buttons(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $admin = $this->linkedAdmin();
        $order = $this->order();

        $admin->update(['role' => 'user']);
        $this->press(BotActions::sign('oY', (string) $order->id));

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_linking_an_account_uses_a_one_time_code(): void
    {
        $this->telegramOn();
        $this->webhookOn();
        $this->fakeTelegram();
        $admin = $this->admin();
        $code = TelegramAdmins::issueCode($admin);

        $start = fn (int $update) => $this->postJson('/api/telegram/webhook', [
            'update_id' => $update,
            'message' => ['message_id' => 5, 'text' => '/start link_' . $code, 'from' => ['id' => 555, 'first_name' => 'Boss'], 'chat' => ['id' => 555, 'type' => 'private']],
        ], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET]);

        $start(10)->assertOk();
        $this->assertSame($admin->id, TelegramAdmins::user(555)?->id);

        TelegramAdmins::unlink('555');
        $start(11)->assertOk();     // the same code again
        $this->assertNull(TelegramAdmins::user(555), 'a code works once');
    }

    // ============================================================================ customer contact

    public function test_a_contact_message_reaches_telegram_even_when_the_email_fails(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        Setting::setValue('contact_email', 'team@xman4289.com');
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

        $this->post('/contact', [
            'name' => 'วิภา', 'email' => 'wipa@example.com', 'phone' => '0899999999',
            'subject' => 'สอบถามราคา', 'message' => 'อยากทราบราคาแพ็กเกจรายปีค่ะ ขอบคุณค่ะ',
        ])->assertSessionHas('contact_error');

        $caption = (string) (self::field($this->sends()[0], 'caption') ?? self::field($this->sends()[0], 'text'));
        $this->assertStringContainsString('อยากทราบราคาแพ็กเกจรายปี', $caption);
        $this->assertStringContainsString('ส่งอีเมลเข้ากล่องไม่สำเร็จ', $caption);
    }

    public function test_the_ai_chat_hands_a_visitor_with_a_phone_number_to_the_team(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();

        BusinessAlerts::aiChatLead([['role' => 'user', 'content' => 'ราคาเท่าไหร่คะ']], '/services', '1.2.3.4');
        $this->assertCount(0, $this->sends(), 'a plain question is the AI\'s job');

        BusinessAlerts::aiChatLead([
            ['role' => 'user', 'content' => 'ราคาเท่าไหร่คะ'],
            ['role' => 'assistant', 'content' => 'เริ่มต้น 15,000 บาทค่ะ'],
            ['role' => 'user', 'content' => 'ให้ทีมงานโทรกลับได้ไหมคะ 081-234-5678'],
        ], '/services', '1.2.3.4');

        $caption = (string) (self::field($this->sends()[0], 'caption') ?? self::field($this->sends()[0], 'text'));
        $this->assertStringContainsString('0812345678', $caption);
        $this->assertStringContainsString('เริ่มต้น 15,000 บาทค่ะ', $caption, 'the conversation comes along');
    }

    public function test_contact_details_are_found_in_chat_text(): void
    {
        $this->assertSame(['0812345678'], BusinessAlerts::contactIn('โทร 081-234-5678 นะคะ'));
        $this->assertSame(['a.b@example.co.th'], BusinessAlerts::contactIn('เมล a.b@example.co.th'));
        $this->assertSame(['LINE somchai_99'], BusinessAlerts::contactIn('ไลน์ไอดี: somchai_99'));
        $this->assertSame([], BusinessAlerts::contactIn('สั่ง 2 ชิ้น ราคา 15000 บาท'));
    }

    // ============================================================================ security

    public function test_password_guessing_from_one_ip_is_reported_once_at_the_threshold(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        for ($i = 0; $i < 12; $i++) {
            SecurityAlerts::failedLogin('someone@example.com', '203.0.113.7', null);
        }

        $this->assertCount(1, $this->sends());
        $this->assertSame(12, SecurityAlerts::stats(CarbonImmutable::now())['counts']['failed_login']);
    }

    public function test_an_attack_on_an_admin_account_is_critical_after_three_misses(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        $admin = $this->admin();
        foreach (['198.51.100.1', '198.51.100.2', '198.51.100.3'] as $ip) {
            SecurityAlerts::failedLogin($admin->email, $ip, $admin);
        }

        $caption = (string) (self::field($this->sends()[0], 'caption') ?? self::field($this->sends()[0], 'text'));
        $this->assertStringContainsString('บัญชีแอดมิน', $caption);
        $this->assertStringNotContainsString($admin->email, $caption, 'the e-mail is masked');
    }

    public function test_error_alerts_never_carry_secrets(): void
    {
        $this->telegramOn();
        $this->fakeTelegram();
        ErrorAlert::report(new RuntimeException('call failed: https://api.telegram.org/bot' . self::TOKEN . '/x?key=abc123 user admin@xman4289.com'));

        $caption = (string) (self::field($this->sends()[0], 'caption') ?? self::field($this->sends()[0], 'text'));
        $this->assertStringNotContainsString(self::TOKEN, $caption);
        $this->assertStringNotContainsString('abc123', $caption);
        $this->assertStringNotContainsString('admin@xman4289.com', $caption);
    }

    // ============================================================================ reports & page

    public function test_the_daily_report_counts_only_money_that_arrived(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Bangkok')->subDay()->setTime(12, 0);
        $this->order(['total' => 1000, 'payment_status' => 'paid', 'paid_at' => $yesterday->utc()]);
        $this->order(['total' => 700, 'payment_status' => 'pending', 'created_at' => $yesterday->utc()]);

        $report = Reports::day($yesterday);

        $this->assertStringStartsWith('฿1,000', (string) $report->facts['ยอดขาย']);
        $this->assertSame('1', $report->facts['รายการ']);
        $this->assertCount(7, $report->columns);
    }

    public function test_the_settings_page_is_for_admins_and_never_shows_the_token(): void
    {
        $this->telegramOn();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['url' => '', 'pending_update_count' => 0]])]);

        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/alerts')->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/alerts')
            ->assertOk()
            ->assertSee('แจ้งเตือน Telegram')
            ->assertDontSee(self::TOKEN);
    }

    public function test_saving_a_token_checks_it_with_telegram_first(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

        $this->actingAs($this->admin())
            ->put('/admin/alerts/telegram', ['telegram_bot_token' => self::TOKEN, 'telegram_chat_id' => '12345'])
            ->assertSessionHasErrors('telegram_bot_token');

        $this->assertSame('', (string) Setting::getValue('telegram_bot_token', ''));
    }
}
