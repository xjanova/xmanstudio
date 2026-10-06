<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/ai/v1/brainx - the GigGok app gets the BrainX Cloud key of the
 * account it is linked to, so its Mind shares one brain with the Mind in BrainX
 * on the owner's PC. Same xman account, same storage, no second payment.
 *
 * What is defended: a key goes only to that account's own linked device, only
 * when it is paid and active - never another account's key, never a demo key
 * the cloud would refuse anyway.
 */
class AppBrainXKeyTest extends TestCase
{
    use RefreshDatabase;

    protected Product $giggok;

    protected Product $brainx;

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
        return $this->withToken($device->license_key)->getJson('/api/ai/v1/brainx');
    }

    public function test_a_linked_device_gets_its_accounts_paid_key(): void
    {
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $res = $this->ask($this->device($user))->assertOk()
            ->assertJsonPath('linked', true)
            ->assertJsonPath('active', true)
            ->assertJsonPath('key', $key->license_key)
            ->assertJsonPath('license_type', 'monthly');

        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
    }

    public function test_never_another_accounts_key(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $this->brainxKey($theirs);

        $res = $this->ask($this->device($mine))->assertOk()
            ->assertJsonPath('active', false)
            ->assertJsonPath('buy_url', url('/products/brainx'));
        $this->assertNull($res->json('key'));
    }

    public function test_expired_suspended_demo_and_free_keys_do_not_count(): void
    {
        $user = User::factory()->create();
        $this->brainxKey($user, expires: '-1 day');
        $this->brainxKey($user, status: 'suspended');
        $this->brainxKey($user, type: LicenseKey::TYPE_DEMO);
        $this->brainxKey($user, type: LicenseKey::TYPE_FREE);

        $this->ask($this->device($user))->assertOk()->assertJsonPath('active', false);
    }

    public function test_the_longest_lasting_key_wins_and_lifetime_beats_all(): void
    {
        $user = User::factory()->create();
        $this->brainxKey($user, expires: '+3 days');
        $long = $this->brainxKey($user, type: LicenseKey::TYPE_YEARLY, expires: '+300 days');
        $this->ask($this->device($user))->assertJsonPath('key', $long->license_key);

        $life = $this->brainxKey($user, type: LicenseKey::TYPE_LIFETIME, expires: null);
        $this->ask($this->device($user))->assertJsonPath('key', $life->license_key);
    }

    public function test_an_unlinked_device_is_told_to_link_first(): void
    {
        $res = $this->ask($this->device(null))->assertOk()
            ->assertJsonPath('linked', false)
            ->assertJsonPath('link_url', url('/giggok/link'));
        $this->assertNull($res->json('key'));
    }

    public function test_a_brainx_key_cannot_be_used_as_the_device_credential(): void
    {
        // Only a GigGok license proves "this is the account's phone"
        $user = User::factory()->create();
        $key = $this->brainxKey($user);

        $this->withToken($key->license_key)->getJson('/api/ai/v1/brainx')->assertStatus(401);
        $this->getJson('/api/ai/v1/brainx')->assertStatus(401);
    }
}
