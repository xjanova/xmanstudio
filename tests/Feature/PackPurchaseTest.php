<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Buying a pack: the web link the app opens, and what a paid order leaves
 * behind for the app to find.
 */
class PackPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        config(['packs.app_product_slug' => 'giggok', 'packs.disk' => 'local']);

        $this->category = Category::create([
            'name' => 'GigGok',
            'slug' => 'giggok-packs',
            'description' => 'Avatar packs',
        ]);
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
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    protected function makePack(string $packId, float $price = 149): AvatarPack
    {
        $product = $this->makeProduct('pack-' . $packId, $packId, $price);

        ProductVersion::create([
            'product_id' => $product->id,
            'version' => '1.0.0',
            'download_filename' => $packId . '.zip',
            'storage_path' => 'packs/' . $packId . '.zip',
            'file_size' => 10,
            'sha256' => str_repeat('a', 64),
            'is_active' => true,
        ]);

        return AvatarPack::create([
            'product_id' => $product->id,
            'pack_id' => $packId,
            'kind' => AvatarPack::KIND_OUTFIT,
            'is_active' => true,
        ]);
    }

    protected function paidOrderFor(Product $product, User $user): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => $product->price,
            'total' => $product->price,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        return $order;
    }

    // ── the link the app opens ────────────────────────────────────────

    public function test_the_buy_link_lands_on_the_product_page(): void
    {
        $pack = $this->makePack('nana-office');

        $this->get('/shop/nana-office')
            ->assertRedirect(route('products.show', 'pack-nana-office'));

        $this->assertNotNull($pack);
    }

    public function test_the_buy_link_needs_no_login(): void
    {
        $this->makePack('nana-office');

        // Someone arriving from the app has not signed in yet; being bounced
        // to a login page before they can even see the price loses the sale.
        $this->get('/shop/nana-office')->assertRedirectContains('/products/');
    }

    public function test_an_unknown_pack_is_a_404_not_a_redirect_to_nowhere(): void
    {
        $this->get('/shop/no-such-pack')->assertNotFound();
    }

    public function test_a_pack_taken_off_sale_cannot_be_bought(): void
    {
        $pack = $this->makePack('retired');
        $pack->update(['is_active' => false]);

        $this->get('/shop/retired')->assertNotFound();
    }

    public function test_a_pack_whose_product_is_off_sale_cannot_be_bought(): void
    {
        $pack = $this->makePack('hidden');
        $pack->product->update(['is_active' => false]);

        $this->get('/shop/hidden')->assertNotFound();
    }

    // ── what a paid order leaves behind ──────────────────────────────

    /**
     * 🔴 A pack is bought outright, not rented.
     *
     * The shared default is a yearly license. On a pack that would take
     * something the customer paid for off their device a year later: it
     * drops out of the app's owned list the moment it expires, and nothing
     * anywhere explains why.
     */
    public function test_buying_a_pack_gives_a_license_that_never_expires(): void
    {
        $user = User::factory()->create();
        $pack = $this->makePack('nana-office');

        app(LicenseService::class)->generateLicensesForOrder(
            $this->paidOrderFor($pack->product, $user)
        );

        $license = LicenseKey::where('product_id', $pack->product_id)->first();

        $this->assertNotNull($license);
        $this->assertSame('lifetime', $license->license_type);
        $this->assertNull($license->expires_at);
    }

    /**
     * Everything that is not a pack keeps the old behaviour.
     */
    public function test_a_normal_product_still_gets_a_yearly_license(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct('some-tool', 'Some tool', 500);

        app(LicenseService::class)->generateLicensesForOrder(
            $this->paidOrderFor($product, $user)
        );

        $license = LicenseKey::where('product_id', $product->id)->first();

        $this->assertSame('yearly', $license->license_type);
        $this->assertNotNull($license->expires_at);
    }

    /**
     * The whole point of the purchase: after paying, the app sees it.
     */
    public function test_after_paying_the_app_reports_the_pack_as_owned(): void
    {
        $user = User::factory()->create();
        $appProduct = $this->makeProduct('giggok', 'GigGok', 0);
        $pack = $this->makePack('nana-office');

        $appLicense = LicenseKey::create([
            'product_id' => $appProduct->id,
            'user_id' => $user->id,
            'license_key' => 'APP-KEY',
            'status' => 'active',
        ]);

        $this->withToken($appLicense->license_key)
            ->getJson('/api/packs/mine')
            ->assertExactJson(['owned' => []]);

        app(LicenseService::class)->generateLicensesForOrder(
            $this->paidOrderFor($pack->product, $user)
        );

        $this->withToken($appLicense->license_key)
            ->getJson('/api/packs/mine')
            ->assertExactJson(['owned' => ['nana-office']]);

        $this->withToken($appLicense->license_key)
            ->postJson('/api/packs/nana-office/download')
            ->assertOk();
    }
}
