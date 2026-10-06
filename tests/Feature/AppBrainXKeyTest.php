<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * POST /api/ai/v1/brainx - signs the GigGok app in to the BrainX Cloud brain of
 * the account it is linked to, so its Mind shares one brain with the Mind in
 * BrainX on the owner's PC. Same xman account, same storage, no second payment.
 *
 * What is defended: the BrainX key itself NEVER reaches the phone - only a
 * device token the server got by signing in on its behalf. A leaked GigGok
 * license (shown in the app's settings) must not be a way to steal the account
 * key and with it the whole brain. Only that account's own linked device, only
 * a paid key active right now, never another account's.
 */
class AppBrainXKeyTest extends TestCase
{
    use RefreshDatabase;

    protected Product $giggok;

    protected Product $brainx;

    protected int $cloudStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        config(['packs.app_product_slug' => 'giggok']);
        $this->giggok = Product::where('slug', 'giggok')->sole();
        $this->brainx = Product::where('slug', 'brainx')->first() ?? Product::create([
            'category_id' => $this->giggok->category_id ?? Category::first()?->id,
            'name' => 'BrainX Cloud',
            'slug' => 'brainx',
            'description' => 'x',
            'price' => 399,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
        // One fake for the whole test (a second Http::fake would not replace the first);
        // cloudSignsIn() switches what the cloud answers
        Http::fake(['serverbrain.xman4289.com/api/cloud/login' => fn () => $this->cloudStatus === 200
            ? Http::response(['token' => 'bxc_device_token', 'account' => ['email' => 'a@b.c', 'licenseType' => 'monthly']])
            : Http::response(['error' => ['code' => 'X']], $this->cloudStatus)]);
    }

    /** What the cloud answers a login with: 200 = a device token and the account it belongs to. */
    protected function cloudSignsIn(int $status = 200): void
    {
        $this->cloudStatus = $status;
    }

    protected function device(?User $user): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => $this->giggok->id,
            'user_id' => $user?->id,
            'license_key' => 'FREE-' . strtoupper(uniqid()),
            'license_type' => LicenseKey::TYPE_FREE,
            'status' => 'active',
        ]);
    }

    protected function brainxKey(User $user, string $type = LicenseKey::TYPE_MONTHLY, $expires = '+20 days', string $status = 'active'): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => $this->brainx->id,
            'user_id' => $user->id,
            'license_key' => 'BRX-' . strtoupper(uniqid()),
            'license_type' => $type,
            'status' => $status,
            'expires_at' => $expires ? now()->modify($expires) : null,
        ]);
    }

    protected function ask(LicenseKey $device)
    {
        return $this->withToken($device->license_key)->postJson('/api/ai/v1/brainx', ['device_name' => 'GigGok (Pixel 8)']);
    }

    protected function assertSignedInWith(string $key): void
    {
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/api/cloud/login')
            && $r['licenseKey'] === $key && $r['deviceName'] === 'GigGok (Pixel 8)');
    }

    public function test_a_linked_device_gets_a_token_and_never_the_key(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $res = $this->ask($this->device($user))->assertOk()
            ->assertJsonPath('linked', true)
            ->assertJsonPath('active', true)
            ->assertJsonPath('token', 'bxc_device_token')
            ->assertJsonPath('account.email', 'a@b.c');

        $this->assertNull($res->json('key'));
        $this->assertStringNotContainsString($key->license_key, $res->getContent(), 'the key leaked to the phone');
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
        $this->assertSignedInWith($key->license_key);
    }

    public function test_the_old_get_that_handed_out_the_key_is_gone(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $res = $this->withToken($this->device($user)->license_key)->getJson('/api/ai/v1/brainx');
        $this->assertStringNotContainsString($key->license_key, $res->getContent());
        $this->assertContains($res->status(), [404, 405]);
    }

    public function test_never_another_accounts_brain(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $this->brainxKey($theirs);

        $res = $this->ask($this->device($mine))->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('buy_url', url('/products/brainx'));
        $this->assertNull($res->json('token'));
        Http::assertNothingSent();
    }

    public function test_expired_suspended_demo_and_free_keys_do_not_count(): void
    {
        $user = User::factory()->create();
        $this->brainxKey($user, expires: '-1 day');
        $this->brainxKey($user, status: 'suspended');
        $this->brainxKey($user, type: LicenseKey::TYPE_DEMO);
        $this->brainxKey($user, type: LicenseKey::TYPE_FREE);

        $this->ask($this->device($user))->assertOk()->assertJsonPath('active', false);
        Http::assertNothingSent();
    }

    public function test_the_longest_lasting_key_signs_in_and_lifetime_beats_all(): void
    {
        $user = User::factory()->create();
        $this->brainxKey($user, expires: '+3 days');
        $long = $this->brainxKey($user, type: LicenseKey::TYPE_YEARLY, expires: '+300 days');
        $this->ask($this->device($user))->assertOk();
        $this->assertSignedInWith($long->license_key);

        $life = $this->brainxKey($user, type: LicenseKey::TYPE_LIFETIME, expires: null);
        $this->cloudSignsIn();
        $this->ask($this->device($user))->assertOk();
        $this->assertSignedInWith($life->license_key);
    }

    public function test_cloud_refusals_become_plain_answers_and_never_echo_the_key(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);
        $device = $this->device($user);

        $this->cloudSignsIn(402);
        $this->ask($device)->assertOk()->assertJsonPath('active', false)->assertJsonPath('expired', true);

        $this->cloudSignsIn(401);
        $this->ask($device)->assertOk()->assertJsonPath('active', false);

        $this->cloudSignsIn(503);
        $res = $this->ask($device)->assertStatus(502);
        $this->assertStringNotContainsString($key->license_key, $res->getContent());
    }

    public function test_an_unlinked_device_is_told_to_link_first(): void
    {
        $res = $this->ask($this->device(null))->assertOk()
            ->assertJsonPath('linked', false)
            ->assertJsonPath('link_url', url('/giggok/link'));
        $this->assertNull($res->json('token'));
    }

    public function test_a_brainx_key_cannot_be_used_as_the_device_credential(): void
    {
        // Only a GigGok license proves "this is the account's phone"
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $this->withToken($key->license_key)->postJson('/api/ai/v1/brainx')->assertStatus(401);
        $this->postJson('/api/ai/v1/brainx')->assertStatus(401);
        Http::assertNothingSent();
    }
}
