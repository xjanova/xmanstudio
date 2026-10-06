<?php

namespace Tests\Feature;

use App\Models\AppAiUsage;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\AiChatService;
use App\Services\AppAiBilling;
use App\Services\InputSanitizerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The AI proxy the GigGok app calls instead of holding our OpenAI key.
 *
 * Every message is paid from the user's wallet (THB) - no free quota. What is
 * defended here is MONEY in both directions: we must never answer without
 * being paid (a free message is our bill), and the user must never pay for an
 * answer they did not get. Most tests exist to prove the charge and the refund
 * really happen rather than just looking wired up.
 *
 * That distinction is not paranoia: a sibling project shipped a wallet guard
 * that showed up correctly in route:list and had never once run, because the
 * field it looked for was named differently by the caller.
 */
class AppAiProxyTest extends TestCase
{
    use RefreshDatabase;

    protected Product $appProduct;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'packs.app_product_slug' => 'giggok',
            'appai.enabled' => true,
        ]);

        // A migration registers the app as a product (free, requires_license) — the same row production has
        $this->appProduct = Product::where('slug', 'giggok')->sole();

        $this->offer([
            ['id' => 'gpt-6-luna', 'label' => 'Luna', 'price' => 0.5, 'enabled' => true],
            ['id' => 'gpt-6.1-sol', 'label' => 'Sol', 'price' => 2, 'enabled' => true],
            ['id' => 'gpt-6-astra', 'label' => 'Astra', 'price' => 9, 'enabled' => false],
        ]);
    }

    protected function offer(array $models, float $cap = 0): void
    {
        Setting::setValue(AppAiBilling::KEY_MODELS, $models, 'json', 'ai');
        Setting::setValue(AppAiBilling::KEY_DAILY_CAP, (string) $cap, 'string', 'ai');
        Setting::setValue(AppAiBilling::KEY_ENABLED, true, 'boolean', 'ai');
    }

    protected function makeLicense(?Product $product = null, float $balance = 100): LicenseKey
    {
        $user = User::factory()->create();
        $this->fund($user->id, $balance);

        return LicenseKey::create([
            'product_id' => ($product ?? $this->appProduct)->id,
            'user_id' => $user->id,
            'license_key' => 'KEY-' . uniqid(),
            'status' => 'active',
            'expires_at' => null,
        ]);
    }

    protected function fund(int $userId, float $balance, bool $active = true): Wallet
    {
        return Wallet::create([
            'user_id' => $userId,
            'balance' => $balance,
            'total_deposited' => $balance,
            'total_spent' => 0,
            'total_refunded' => 0,
            'is_active' => $active,
        ]);
    }

    protected function balanceOf(LicenseKey $license): float
    {
        return (float) Wallet::where('user_id', $license->user_id)->value('balance');
    }

    /** Stand in for the real provider call so no test ever leaves the machine. */
    protected function fakeAi(string $answer = 'สวัสดีค่ะ', bool $ok = true): object
    {
        $fake = new class($answer, $ok) extends AiChatService
        {
            public array $seenMessages = [];

            public ?string $seenSystem = null;

            public ?string $seenModel = null;

            public int $calls = 0;

            public function __construct(public string $answer, public bool $ok)
            {
                // Deliberately not calling parent::__construct - it reads
                // settings and builds an HTTP client we do not want here.
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function chat(array $messages, ?string $systemPrompt = null, ?string $modelOverride = null): array
            {
                $this->calls++;
                $this->seenMessages = $messages;
                $this->seenSystem = $systemPrompt;
                $this->seenModel = $modelOverride;

                return [
                    'success' => $this->ok,
                    'message' => $this->ok ? $this->answer : null,
                    'provider' => 'openai',
                    'model' => $modelOverride ?: 'gpt-4o-mini',
                ];
            }
        };

        $this->app->instance(AiChatService::class, $fake);

        return $fake;
    }

    protected function askWithModel(string $key, string $model): TestResponse
    {
        return $this->withToken($key)->postJson('/api/ai/v1/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => 'hi']],
        ]);
    }

    protected function ask(?string $key, array $messages = []): TestResponse
    {
        $messages = $messages ?: [
            ['role' => 'system', 'content' => 'เธอชื่อมายด์'],
            ['role' => 'user', 'content' => 'สวัสดี'],
        ];

        $req = $key === null ? $this : $this->withToken($key);

        return $req->postJson('/api/ai/v1/chat/completions', ['messages' => $messages]);
    }

    // ── who may call at all ─────────────────────────────────────

    public function test_no_license_is_rejected(): void
    {
        $this->fakeAi();

        // 401 on purpose: the app turns that into "your key is not right".
        $this->ask(null)->assertStatus(401);

        $this->assertDatabaseCount('app_ai_usages', 0);
    }

    public function test_a_license_for_a_different_product_is_rejected(): void
    {
        $this->fakeAi();

        $other = Product::create([
            'category_id' => $this->appProduct->category_id,
            'name' => 'Something else',
            'slug' => 'something-else',
            'description' => 'x',
            'price' => 100,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        // Otherwise a licence bought for any product spends our AI budget.
        $this->ask($this->makeLicense($other)->license_key)->assertStatus(401);
    }

    protected function makeFreeLicense(bool $linked, float $balance = 100): LicenseKey
    {
        $userId = null;
        if ($linked) {
            $userId = User::factory()->create()->id;
            $this->fund($userId, $balance);
        }

        return LicenseKey::create([
            'product_id' => $this->appProduct->id,
            'user_id' => $userId,
            'license_key' => 'FREE-' . strtoupper(uniqid()),
            'license_type' => LicenseKey::TYPE_FREE,
            'status' => 'active',
            'expires_at' => null,
        ]);
    }

    public function test_an_unlinked_key_cannot_use_the_assistant(): void
    {
        $fake = $this->fakeAi();

        // No account = no wallet to charge. Any device can mint a free key.
        $this->ask($this->makeFreeLicense(false)->license_key)
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'not_linked')
            ->assertJsonPath('error.message', 'Link this device to your account first: xman4289.com/giggok/link');

        $this->assertSame(0, $fake->calls);
        $this->assertDatabaseCount('app_ai_usages', 0);
    }

    public function test_an_unlinked_paid_key_cannot_either(): void
    {
        // Paid keys used to pass without an owner. Now every message is charged
        // to an account, so a key nobody claimed has nobody to charge.
        $this->fakeAi();
        $license = LicenseKey::create([
            'product_id' => $this->appProduct->id,
            'user_id' => null,
            'license_key' => 'PAID-' . uniqid(),
            'status' => 'active',
        ]);

        $this->ask($license->license_key)->assertStatus(403);
    }

    // ── money ───────────────────────────────────────────────────

    public function test_each_message_is_charged_the_admins_price(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeAi('สวัสดีค่ะ วันนี้เป็นยังไงบ้าง');
        $license = $this->makeLicense(balance: 10);

        $res = $this->askWithModel($license->license_key, 'gpt-6.1-sol')->assertOk();

        $res->assertJsonPath('choices.0.message.content', 'สวัสดีค่ะ วันนี้เป็นยังไงบ้าง');
        $res->assertJsonPath('choices.0.message.role', 'assistant');
        $res->assertJsonPath('object', 'chat.completion');
        $res->assertJsonPath('giggok_billing.charged', 2);
        $res->assertJsonPath('giggok_billing.balance', 8);
        $res->assertJsonPath('giggok_billing.currency', 'THB');
        $this->assertSame(8.0, $this->balanceOf($license));

        // The ledger shows what it was for, and the usage row points at it
        $txn = WalletTransaction::where('user_id', $license->user_id)->sole();
        $this->assertSame(WalletTransaction::TYPE_PAYMENT, $txn->type);
        $this->assertSame(AppAiBilling::REFERENCE, $txn->reference_type);
        $usage = AppAiUsage::sole();
        $this->assertSame($txn->id, $usage->wallet_transaction_id);
        $this->assertSame($usage->id, $txn->reference_id);
        $this->assertTrue($usage->ok);
        $this->assertSame('2.00', (string) $usage->price);
    }

    public function test_not_enough_credit_is_refused_before_anything_is_spent(): void
    {
        $fake = $this->fakeAi();
        $license = $this->makeLicense(balance: 1);

        // 402 is what the app turns into "top up"; the link comes with it
        $this->askWithModel($license->license_key, 'gpt-6.1-sol')
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'insufficient_credit')
            ->assertJsonPath('error.topup_url', url('/wallet/topup'));

        $this->assertSame(0, $fake->calls, 'must not call the model for free');
        $this->assertSame(1.0, $this->balanceOf($license));
        $this->assertDatabaseCount('app_ai_usages', 0);
    }

    public function test_a_user_with_no_wallet_at_all_gets_top_up_not_a_crash(): void
    {
        $this->fakeAi();
        $user = User::factory()->create();
        $license = LicenseKey::create([
            'product_id' => $this->appProduct->id,
            'user_id' => $user->id,
            'license_key' => 'KEY-' . uniqid(),
            'status' => 'active',
        ]);

        $this->ask($license->license_key)->assertStatus(402);
    }

    public function test_a_failed_answer_is_refunded(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeAi(ok: false);
        $license = $this->makeLicense(balance: 10);

        $this->askWithModel($license->license_key, 'gpt-6.1-sol')
            ->assertStatus(502)
            ->assertJsonPath('error.message', 'The assistant could not answer just now. You were not charged.');

        $this->assertSame(10.0, $this->balanceOf($license));
        $usage = AppAiUsage::sole();
        $this->assertFalse($usage->ok);
        $this->assertTrue($usage->refunded);
        $this->assertSame(1, WalletTransaction::where('type', WalletTransaction::TYPE_REFUND)->count());
    }

    public function test_a_suspended_wallet_is_refused(): void
    {
        $fake = $this->fakeAi();
        $user = User::factory()->create();
        $this->fund($user->id, 100, active: false);
        $license = LicenseKey::create([
            'product_id' => $this->appProduct->id,
            'user_id' => $user->id,
            'license_key' => 'KEY-' . uniqid(),
            'status' => 'active',
        ]);

        $this->ask($license->license_key)->assertStatus(403)->assertJsonPath('error.code', 'wallet_inactive');
        $this->assertSame(0, $fake->calls);
    }

    public function test_the_daily_cap_stops_spending_and_failures_do_not_count(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->offer([['id' => 'gpt-6-luna', 'label' => 'Luna', 'price' => 1, 'enabled' => true]], cap: 2);
        $fake = $this->fakeAi();
        $license = $this->makeLicense(balance: 100);

        $this->ask($license->license_key)->assertOk();
        $this->ask($license->license_key)->assertOk();
        $this->ask($license->license_key)->assertStatus(429)->assertJsonPath('error.code', 'daily_cap');
        $this->assertSame(98.0, $this->balanceOf($license));

        // A refunded failure did not cost anything, so it must not eat the cap
        $other = $this->makeLicense(balance: 100);
        $fake->ok = false;
        $this->ask($other->license_key)->assertStatus(502);
        $fake->ok = true;
        $this->ask($other->license_key)->assertOk();
        $this->ask($other->license_key)->assertOk();
    }

    public function test_one_users_cap_does_not_limit_another(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->offer([['id' => 'gpt-6-luna', 'label' => 'Luna', 'price' => 1, 'enabled' => true]], cap: 1);
        $this->fakeAi();

        $spent = $this->makeLicense();
        $this->ask($spent->license_key)->assertOk();
        $this->ask($spent->license_key)->assertStatus(429);

        $this->ask($this->makeLicense()->license_key)->assertOk();
    }

    // ── models: only what we offer ─────────────────────────────

    public function test_an_offered_model_is_used_and_reported(): void
    {
        $fake = $this->fakeAi();

        $this->askWithModel($this->makeLicense()->license_key, 'gpt-6.1-sol')
            ->assertOk()
            ->assertJsonPath('model', 'gpt-6.1-sol');

        $this->assertSame('gpt-6.1-sol', $fake->seenModel);
    }

    public function test_a_switched_off_model_is_never_used(): void
    {
        $fake = $this->fakeAi();
        $license = $this->makeLicense(balance: 100);

        // Astra is configured but switched off: fall back to the first offer, at ITS price
        $this->askWithModel($license->license_key, 'gpt-6-astra')
            ->assertOk()
            ->assertJsonPath('model', 'gpt-6-luna')
            ->assertJsonPath('giggok_billing.charged', 0.5);

        $this->assertSame('gpt-6-luna', $fake->seenModel);
        $this->assertSame(99.5, $this->balanceOf($license));
    }

    public function test_nothing_offered_means_the_service_is_closed(): void
    {
        $this->offer([]);
        $fake = $this->fakeAi();

        $this->ask($this->makeLicense()->license_key)->assertStatus(503);
        $this->assertSame(0, $fake->calls);
    }

    public function test_the_admin_switch_turns_the_proxy_off(): void
    {
        $this->fakeAi();
        Setting::setValue(AppAiBilling::KEY_ENABLED, false, 'boolean', 'ai');

        $this->ask($this->makeLicense()->license_key)->assertStatus(503);
    }

    public function test_the_master_switch_turns_the_proxy_off(): void
    {
        $this->fakeAi();
        config(['appai.enabled' => false]);

        $this->ask($this->makeLicense()->license_key)->assertStatus(503);
    }

    // ── the account screen ─────────────────────────────────────

    public function test_account_shows_balance_today_and_only_offered_models(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->offer([
            ['id' => 'gpt-6-luna', 'label' => 'Luna', 'price' => 0.5, 'enabled' => true],
            ['id' => 'gpt-6-astra', 'label' => 'Astra', 'price' => 9, 'enabled' => false],
        ], cap: 20);
        $this->fakeAi();
        $license = $this->makeLicense(balance: 10);
        $this->ask($license->license_key)->assertOk();

        $res = $this->withToken($license->license_key)->getJson('/api/ai/v1/account')->assertOk();

        $res->assertJsonPath('linked', true)
            ->assertJsonPath('balance', 9.5)
            ->assertJsonPath('currency', 'THB')
            ->assertJsonPath('daily_cap', 20)
            ->assertJsonPath('today.spent', 0.5)
            ->assertJsonPath('today.messages', 1)
            ->assertJsonPath('topup_url', url('/wallet/topup'))
            ->assertJsonPath('default_model', 'gpt-6-luna')
            ->assertJsonCount(1, 'models')
            ->assertJsonPath('models.0.price', 0.5);
        $this->assertStringNotContainsString('astra', $res->getContent());
    }

    public function test_an_unlinked_device_still_sees_prices_and_where_to_link(): void
    {
        $res = $this->withToken($this->makeFreeLicense(false)->license_key)
            ->getJson('/api/ai/v1/account')->assertOk();

        $res->assertJsonPath('linked', false)
            ->assertJsonPath('balance', 0)
            ->assertJsonPath('link_url', url('/giggok/link'));
        $this->assertNotEmpty($res->json('models'));
    }

    public function test_account_needs_a_valid_license(): void
    {
        $this->getJson('/api/ai/v1/account')->assertStatus(401);
        $this->withToken('nope')->getJson('/api/ai/v1/account')->assertStatus(401);
    }

    // ── what reaches the model ─────────────────────────────────

    public function test_the_persona_is_passed_through_as_the_system_prompt(): void
    {
        // If the system message were dropped, AiChatService would fall back to
        // the WEBSITE assistant's prompt and Mind would start answering as
        // "XMAN Studio support" in her own app.
        $fake = $this->fakeAi();

        $this->ask($this->makeLicense()->license_key, [
            ['role' => 'system', 'content' => 'เธอชื่อมายด์ เป็นแฟนของเจ้าของ'],
            ['role' => 'user', 'content' => 'หิวข้าว'],
        ])->assertOk();

        $this->assertSame('เธอชื่อมายด์ เป็นแฟนของเจ้าของ', $fake->seenSystem);
        // The system turn must not also be left in the message list.
        $this->assertSame([['role' => 'user', 'content' => 'หิวข้าว']], $fake->seenMessages);
    }

    public function test_an_oversized_conversation_is_refused_before_it_is_charged(): void
    {
        $this->fakeAi();
        config(['appai.max_chars' => 100]);
        $license = $this->makeLicense(balance: 10);

        $this->ask($license->license_key, [
            ['role' => 'user', 'content' => str_repeat('ก', 500)],
        ])->assertStatus(422);

        $this->assertDatabaseCount('app_ai_usages', 0);
        $this->assertSame(10.0, $this->balanceOf($license));
    }

    public function test_an_override_does_not_leak_into_later_calls(): void
    {
        // AiChatService can be resolved as a shared instance. If an override
        // stuck, the website's own chat widget - which never asked for one -
        // would quietly start using the app user's model.
        $service = new AiChatService(new InputSanitizerService);

        $before = (fn () => $this->model)->call($service);
        try {
            $service->chat([['role' => 'user', 'content' => 'hi']], 'sys', 'some-other-model');
        } catch (\Throwable) {
            // Reaching the network is not the point; restoring state is.
        }
        $after = (fn () => $this->model)->call($service);

        $this->assertSame($before, $after);
    }

    public function test_upstream_failure_details_are_not_handed_to_the_app(): void
    {
        // The app prints error.message straight onto the settings screen, so
        // anything we put there is shown to the user.
        $throwing = new class extends AiChatService
        {
            public function __construct() {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function chat(array $messages, ?string $systemPrompt = null, ?string $modelOverride = null): array
            {
                throw new \RuntimeException('sk-proj-LEAKED-KEY upstream said no');
            }
        };
        $this->app->instance(AiChatService::class, $throwing);
        $license = $this->makeLicense(balance: 10);

        $res = $this->ask($license->license_key)->assertStatus(502);

        $body = $res->getContent();
        $this->assertStringNotContainsString('sk-proj-LEAKED-KEY', $body);
        $this->assertStringNotContainsString('upstream said no', $body);
        // ...and the throw is refunded like any other failure
        $this->assertSame(10.0, $this->balanceOf($license));
    }

    // ── admin ──────────────────────────────────────────────────

    public function test_admin_saves_models_prices_and_cap(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.ai-settings.app'), [
            'appai_enabled' => '1',
            'appai_daily_cap' => '50',
            'models' => [
                ['id' => 'gpt-6-luna', 'label' => 'ประหยัด', 'price' => '0.35', 'enabled' => '1'],
                ['id' => '', 'label' => 'แถวว่าง', 'price' => '1'],
                ['id' => 'gpt-6-astra', 'label' => 'แรงสุด', 'price' => '5'],
                ['id' => 'gpt-6-luna', 'label' => 'ซ้ำ', 'price' => '9', 'enabled' => '1'],
            ],
        ])->assertRedirect(route('admin.ai-settings.index'));

        $billing = app(AppAiBilling::class);
        $this->assertSame(50.0, $billing->dailyCap());
        $this->assertSame([
            ['id' => 'gpt-6-luna', 'label' => 'ประหยัด', 'price' => 0.35, 'enabled' => true],
            ['id' => 'gpt-6-astra', 'label' => 'แรงสุด', 'price' => 5.0, 'enabled' => false],
        ], $billing->allModels());
        // Only the switched-on one reaches the app
        $this->assertSame([['id' => 'gpt-6-luna', 'label' => 'ประหยัด', 'price' => 0.35]], $billing->models());
    }

    public function test_the_settings_page_renders_the_app_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.ai-settings.index'))
            ->assertOk()
            ->assertSee('GigGok · AI ในแอป', false)
            ->assertSee('gpt-6-luna', false);
    }
}
