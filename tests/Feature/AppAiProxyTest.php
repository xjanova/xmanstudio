<?php

namespace Tests\Feature;

use App\Models\AppAiUsage;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The AI proxy the GigGok app calls instead of holding our OpenAI key.
 *
 * What is actually being defended here is the BILL. The key is safe by
 * construction - it never leaves the server - so the remaining risk is that
 * someone pulls a license key out of the app and spends our AI budget with it.
 * The daily cap is the thing standing in the way, so most of these tests exist
 * to prove that cap really fires rather than just looking wired up.
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
            'appai.daily_limit' => 3,
        ]);

        $category = Category::create([
            'name' => 'Apps',
            'slug' => 'apps',
            'description' => 'Apps',
        ]);

        $this->appProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'GigGok',
            'slug' => 'giggok',
            'description' => 'GigGok',
            'price' => 0,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    protected function makeLicense(?Product $product = null): LicenseKey
    {
        $user = User::factory()->create();

        return LicenseKey::create([
            'product_id' => ($product ?? $this->appProduct)->id,
            'user_id' => $user->id,
            'license_key' => 'KEY-' . uniqid(),
            'status' => 'active',
            'expires_at' => null,
        ]);
    }

    /** Stand in for the real provider call so no test ever leaves the machine. */
    protected function fakeAi(string $answer = 'สวัสดีค่ะ', bool $ok = true): void
    {
        $fake = new class($answer, $ok) extends AiChatService
        {
            public array $seenMessages = [];

            public ?string $seenSystem = null;

            public function __construct(public string $answer, public bool $ok)
            {
                // Deliberately not calling parent::__construct - it reads
                // settings and builds an HTTP client we do not want here.
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function chat(array $messages, ?string $systemPrompt = null): array
            {
                $this->seenMessages = $messages;
                $this->seenSystem = $systemPrompt;

                return [
                    'success' => $this->ok,
                    'message' => $this->ok ? $this->answer : null,
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                ];
            }
        };

        $this->app->instance(AiChatService::class, $fake);
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

    public function test_a_valid_license_gets_an_openai_shaped_answer(): void
    {
        $this->fakeAi('สวัสดีค่ะ วันนี้เป็นยังไงบ้าง');

        $res = $this->ask($this->makeLicense()->license_key)->assertOk();

        // The app parses choices[0].message.content and nothing else.
        $res->assertJsonPath('choices.0.message.content', 'สวัสดีค่ะ วันนี้เป็นยังไงบ้าง');
        $res->assertJsonPath('choices.0.message.role', 'assistant');
        $res->assertJsonPath('object', 'chat.completion');
    }

    public function test_the_persona_is_passed_through_as_the_system_prompt(): void
    {
        // If the system message were dropped, AiChatService would fall back to
        // the WEBSITE assistant's prompt and Mind would start answering as
        // "XMAN Studio support" in her own app.
        $this->fakeAi();

        $this->ask($this->makeLicense()->license_key, [
            ['role' => 'system', 'content' => 'เธอชื่อมายด์ เป็นแฟนของเจ้าของ'],
            ['role' => 'user', 'content' => 'หิวข้าว'],
        ])->assertOk();

        $fake = $this->app->make(AiChatService::class);

        $this->assertSame('เธอชื่อมายด์ เป็นแฟนของเจ้าของ', $fake->seenSystem);
        // The system turn must not also be left in the message list.
        $this->assertSame([['role' => 'user', 'content' => 'หิวข้าว']], $fake->seenMessages);
    }

    public function test_the_daily_cap_actually_stops_the_next_call(): void
    {
        // Throttle would answer 429 for its own reasons and hide whether the
        // cap works at all - the two are indistinguishable from the status code.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeAi();

        $key = $this->makeLicense()->license_key;

        // daily_limit is 3 in setUp.
        for ($i = 0; $i < 3; $i++) {
            $this->ask($key)->assertOk();
        }

        $this->ask($key)->assertStatus(429);
        $this->assertDatabaseCount('app_ai_usages', 3);
    }

    public function test_one_licenses_spending_does_not_limit_another(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeAi();

        $spent = $this->makeLicense()->license_key;
        for ($i = 0; $i < 3; $i++) {
            $this->ask($spent)->assertOk();
        }
        $this->ask($spent)->assertStatus(429);

        // The cap is per license, not global - one heavy user must not take
        // the assistant away from everyone else.
        $this->ask($this->makeLicense()->license_key)->assertOk();
    }

    public function test_failed_calls_are_counted_too(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeAi(ok: false);

        $key = $this->makeLicense()->license_key;

        // A caller stuck retrying a broken request still costs us upstream
        // attempts. Counting only successes turns that into a free loop.
        $this->ask($key)->assertStatus(502);
        $this->ask($key)->assertStatus(502);
        $this->ask($key)->assertStatus(502);
        $this->ask($key)->assertStatus(429);

        $this->assertSame(3, AppAiUsage::where('license_key', $key)->count());
    }

    public function test_an_oversized_conversation_is_refused_before_it_is_sent(): void
    {
        $this->fakeAi();
        config(['appai.max_chars' => 100]);

        $this->ask($this->makeLicense()->license_key, [
            ['role' => 'user', 'content' => str_repeat('ก', 500)],
        ])->assertStatus(422);

        // Refused before spending anything upstream.
        $this->assertDatabaseCount('app_ai_usages', 0);
    }

    public function test_the_master_switch_turns_the_proxy_off(): void
    {
        $this->fakeAi();
        config(['appai.enabled' => false]);

        $this->ask($this->makeLicense()->license_key)->assertStatus(503);
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

            public function chat(array $messages, ?string $systemPrompt = null): array
            {
                throw new \RuntimeException('sk-proj-LEAKED-KEY upstream said no');
            }
        };
        $this->app->instance(AiChatService::class, $throwing);

        $res = $this->ask($this->makeLicense()->license_key)->assertStatus(502);

        $body = $res->getContent();
        $this->assertStringNotContainsString('sk-proj-LEAKED-KEY', $body);
        $this->assertStringNotContainsString('upstream said no', $body);
    }
}
