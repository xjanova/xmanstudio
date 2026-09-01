<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The GigGok pack store API.
 *
 * The rule worth the most tests here is that a download link cannot be had
 * without paying: everything else is a listing, but that one is the till.
 */
class PackStoreTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected Product $appProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['packs.app_product_slug' => 'giggok', 'packs.disk' => 'local']);

        $this->category = Category::create([
            'name' => 'GigGok',
            'slug' => 'giggok-packs',
            'description' => 'Avatar packs',
        ]);

        // The app itself is a product too; its license keys are what the app
        // sends as a bearer token.
        $this->appProduct = $this->makeProduct('giggok', 'GigGok', 0);
    }

    protected function makeProduct(string $slug, string $name, float $price): Product
    {
        return Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $name,
            'price' => $price,
            'stock' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * A pack on sale, with a real file behind it.
     */
    protected function makePack(string $packId, float $price = 149, string $body = 'zip-bytes'): AvatarPack
    {
        $product = $this->makeProduct('pack-' . $packId, $packId, $price);

        $path = 'packs/' . $packId . '.zip';
        Storage::disk('local')->put($path, $body);

        ProductVersion::create([
            'product_id' => $product->id,
            'version' => '1.0.0',
            'download_filename' => $packId . '.zip',
            'storage_path' => $path,
            'file_size' => strlen($body),
            'sha256' => hash('sha256', $body),
            'is_active' => true,
        ]);

        return AvatarPack::create([
            'product_id' => $product->id,
            'pack_id' => $packId,
            'kind' => AvatarPack::KIND_OUTFIT,
            'name_en' => ucfirst($packId),
            'is_active' => true,
        ]);
    }

    protected function makeLicense(Product $product, ?User $user = null, array $extra = []): LicenseKey
    {
        return LicenseKey::create(array_merge([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'license_key' => 'KEY-' . $product->id . '-' . ($user?->id ?? 'anon') . '-' . uniqid(),
            'status' => 'active',
        ], $extra));
    }

    public function test_catalogue_is_public_and_lists_active_packs(): void
    {
        $this->makePack('nana-office');

        $res = $this->getJson('/api/packs');

        $res->assertOk()
            ->assertJsonPath('packs.0.id', 'nana-office')
            ->assertJsonPath('packs.0.kind', 'outfit')
            ->assertJsonPath('packs.0.price', 149)
            ->assertJsonPath('packs.0.currency', 'THB')
            ->assertJsonPath('packs.0.sizeBytes', strlen('zip-bytes'));
    }

    public function test_catalogue_hides_packs_whose_product_is_off_sale(): void
    {
        $pack = $this->makePack('retired');
        $pack->product->update(['is_active' => false]);

        $this->getJson('/api/packs')->assertOk()->assertJsonCount(0, 'packs');
    }

    /**
     * The size and hash describe the file, not the listing - a pack whose zip
     * has not been uploaded yet must still appear, or admins cannot see what
     * they have half-finished.
     */
    public function test_catalogue_includes_a_pack_with_no_file_yet(): void
    {
        $product = $this->makeProduct('pack-draft', 'Draft', 99);
        AvatarPack::create([
            'product_id' => $product->id,
            'pack_id' => 'draft',
            'kind' => AvatarPack::KIND_CHARACTER,
            'is_active' => true,
        ]);

        $this->getJson('/api/packs')->assertOk()->assertJsonPath('packs.0.sizeBytes', 0);
    }

    public function test_mine_rejects_a_missing_or_unknown_license(): void
    {
        $this->getJson('/api/packs/mine')->assertStatus(401);

        $this->withToken('nonsense')->getJson('/api/packs/mine')->assertStatus(401);
    }

    public function test_mine_rejects_a_license_for_a_different_product(): void
    {
        $user = User::factory()->create();
        $other = $this->makeProduct('something-else', 'Other', 500);
        $license = $this->makeLicense($other, $user);

        $this->withToken($license->license_key)
            ->getJson('/api/packs/mine')
            ->assertStatus(401);
    }

    public function test_mine_rejects_an_expired_license(): void
    {
        $user = User::factory()->create();
        $license = $this->makeLicense($this->appProduct, $user, [
            'expires_at' => now()->subDay(),
        ]);

        $this->withToken($license->license_key)
            ->getJson('/api/packs/mine')
            ->assertStatus(401);
    }

    public function test_mine_lists_only_the_packs_this_user_licensed(): void
    {
        $user = User::factory()->create();
        $mine = $this->makePack('nana-office');
        $this->makePack('nana-beach');

        $this->makeLicense($mine->product, $user);
        $license = $this->makeLicense($this->appProduct, $user);

        $this->withToken($license->license_key)
            ->getJson('/api/packs/mine')
            ->assertOk()
            ->assertExactJson(['owned' => ['nana-office']]);
    }

    /**
     * A license with no user proves a machine, not a buyer.
     */
    public function test_a_device_only_license_owns_nothing_but_is_not_an_error(): void
    {
        $this->makePack('nana-office');
        $license = $this->makeLicense($this->appProduct, null);

        $this->withToken($license->license_key)
            ->getJson('/api/packs/mine')
            ->assertOk()
            ->assertExactJson(['owned' => []]);
    }

    public function test_download_is_refused_for_a_pack_that_was_not_bought(): void
    {
        $this->makePack('nana-office');
        $user = User::factory()->create();
        $license = $this->makeLicense($this->appProduct, $user);

        $this->withToken($license->license_key)
            ->postJson('/api/packs/nana-office/download')
            ->assertStatus(403);
    }

    /**
     * 404 and 403 must stay apart: one means the pack is gone, the other
     * means it is there and unpaid for, and the app says different things.
     */
    public function test_an_unknown_pack_is_404_not_403(): void
    {
        $user = User::factory()->create();
        $license = $this->makeLicense($this->appProduct, $user);

        $this->withToken($license->license_key)
            ->postJson('/api/packs/no-such-pack/download')
            ->assertStatus(404);
    }

    public function test_download_without_a_license_is_401(): void
    {
        $this->makePack('nana-office');

        $this->postJson('/api/packs/nana-office/download')->assertStatus(401);
    }

    public function test_a_free_pack_needs_no_purchase(): void
    {
        $this->makePack('mind-default', 0);
        $user = User::factory()->create();
        $license = $this->makeLicense($this->appProduct, $user);

        $this->withToken($license->license_key)
            ->postJson('/api/packs/mind-default/download')
            ->assertOk()
            ->assertJsonStructure(['url', 'sha256', 'expiresIn']);
    }

    /**
     * The case a fresh install actually hits.
     *
     * The app ships with an empty license key and has no login screen, so
     * the very first thing a new user does is ask for a free pack with no
     * credentials at all. The test above still sent a license, which meant
     * "free" was only ever proven for people who already had one.
     */
    public function test_a_free_pack_downloads_with_no_license_at_all(): void
    {
        $this->makePack('mind-default', 0);

        $res = $this->postJson('/api/packs/mind-default/download')
            ->assertOk()
            ->assertJsonStructure(['url', 'sha256', 'expiresIn']);

        // The link has to actually serve the file, not just look like a link.
        $this->get($res->json('url'))->assertOk();

        // Logged as a real download even though nobody can be named for it.
        $this->assertDatabaseCount('download_logs', 1);
        $this->assertDatabaseHas('download_logs', [
            'user_id' => null,
            'license_key_id' => null,
        ]);
    }

    public function test_a_paid_pack_still_needs_a_license_when_free_ones_do_not(): void
    {
        $this->makePack('nana-office', 149);

        // Same call shape as the free case above - only the price differs,
        // so this pins that opening up free packs did not open up paid ones.
        $this->postJson('/api/packs/nana-office/download')->assertStatus(401);
    }

    public function test_a_purchased_pack_returns_a_working_signed_link(): void
    {
        $pack = $this->makePack('nana-office');
        $user = User::factory()->create();
        $this->makeLicense($pack->product, $user);
        $license = $this->makeLicense($this->appProduct, $user);

        $res = $this->withToken($license->license_key)
            ->postJson('/api/packs/nana-office/download')
            ->assertOk()
            ->assertJsonPath('sha256', hash('sha256', 'zip-bytes'));

        $url = $res->json('url');
        $this->assertStringContainsString('signature=', $url);

        $this->get($url)->assertOk();
        $this->assertDatabaseCount('download_logs', 1);
    }

    public function test_the_file_route_refuses_an_unsigned_link(): void
    {
        $pack = $this->makePack('nana-office');
        $version = $pack->product->latestVersion();

        $this->get('/api/packs/file/' . $version->id)->assertStatus(403);
    }

    public function test_the_file_route_refuses_a_link_that_has_expired(): void
    {
        $pack = $this->makePack('nana-office');
        $version = $pack->product->latestVersion();

        $url = URL::temporarySignedRoute('packs.file', now()->addMinutes(10), [
            'version' => $version->id,
        ]);

        $this->travel(11)->minutes();

        $this->get($url)->assertStatus(403);
    }
}
