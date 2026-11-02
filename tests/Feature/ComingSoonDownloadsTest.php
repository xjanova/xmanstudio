<?php

namespace Tests\Feature;

use App\Exceptions\DownloadUnavailableException;
use App\Http\Controllers\BrainXDownloadController;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DownloadLog;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Services\ReleaseDownloadStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Owner's rule (2026-10-12): software or a product set to Coming Soon cannot be downloaded from
 * any point of the site, and every place that would offer a download shows the Coming Soon label.
 *
 * What these pin, for a product that HAS a release and a buyer who HAS bought it and holds a
 * valid license — so the only thing standing between them and the file is Coming Soon:
 *   — /download/{slug}, /download/{slug}/{version}, POST /api/download/{slug} refuse
 *   — each app's own download route (here WinXTools) refuses, as JSON for the app
 *   — the version / update APIs hand out no link and no update
 *   — the BrainX installer refuses NEW people once BrainX is set to Coming Soon, while someone who
 *     already uses BrainX (a key holder, an admin) still gets it, and the update feed of apps
 *     already installed never closes (owner, 2026-10-19)
 *   — ReleaseDownloadStreamer, which every file passes through, refuses on its own
 *   — the product page and the customer's Download Center show the label instead of a button
 *   — once coming_soon_until has passed, everything opens again without anyone touching it
 */
class ComingSoonDownloadsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::preventStrayRequests();
        $this->withoutVite();

        // Without an admin every page redirects to the setup wizard.
        User::factory()->create(['role' => 'admin']);

        $this->category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
    }

    public function test_the_download_routes_refuse_a_coming_soon_product_even_to_its_buyer(): void
    {
        [$product, $version, $buyer, $license] = $this->boughtComingSoon('xcluade-agent');

        $this->actingAs($buyer)->get('/download/xcluade-agent')
            ->assertRedirect(route('products.show', 'xcluade-agent'))
            ->assertSessionHas('error', Product::COMING_SOON_DOWNLOAD_MESSAGE);
        $this->actingAs($buyer)->get('/download/xcluade-agent/' . $version->version)
            ->assertRedirect(route('products.show', 'xcluade-agent'));
        $this->actingAs($buyer)->getJson('/download/xcluade-agent/' . $version->version)
            ->assertForbidden()
            ->assertJsonPath('coming_soon', true);
        $this->postJson('/api/download/xcluade-agent', ['license_key' => $license->license_key])
            ->assertForbidden()
            ->assertJsonPath('coming_soon', true);

        $this->assertSame(0, DownloadLog::count());
        $this->assertNull($product->downloadUrl());
    }

    public function test_the_version_and_update_apis_hand_out_no_link(): void
    {
        [, $version, , $license] = $this->boughtComingSoon('postx-agent');

        $this->getJson('/api/v1/products/postx-agent/version')
            ->assertOk()
            ->assertJsonPath('coming_soon', true)
            ->assertJsonPath('download_url', null);
        $this->assertSame([null], array_column($this->getJson('/api/v1/products/postx-agent/versions')->json('versions'), 'download_url'));
        $this->postJson('/api/v1/products/postx-agent/check-update', ['current_version' => '0.0.1', 'license_key' => $license->license_key])
            ->assertOk()
            ->assertJsonPath('has_update', false)
            ->assertJsonPath('update', null)
            ->assertJsonPath('license.can_download', false);
        $this->getJson('/api/v1/product/postx-agent/update/check?current_version=0.0.1')
            ->assertOk()
            ->assertJsonPath('has_update', false)
            ->assertJsonPath('download_url', '')
            ->assertJsonPath('coming_soon', true);

        $this->assertSame('1.0.0', $version->version);
    }

    public function test_an_apps_own_download_route_refuses_too(): void
    {
        $product = $this->product('winx-tools', comingSoon: true);
        $this->release($product);

        $this->get('/winx-tools/download', ['Accept' => '*/*'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
        $this->get('/winx-tools/download', ['Accept' => 'text/html'])
            ->assertRedirect(route('products.show', 'winx-tools'))
            ->assertSessionHas('error', Product::COMING_SOON_DOWNLOAD_MESSAGE);
        $this->assertSame(0, DownloadLog::count());
    }

    public function test_every_file_is_refused_where_they_all_pass(): void
    {
        $product = $this->product('livexshop-pro', comingSoon: true);
        $version = $this->release($product);

        try {
            app(ReleaseDownloadStreamer::class)->respond(Request::create('/x'), $version, null);
            $this->fail('the streamer handed out a coming-soon file');
        } catch (DownloadUnavailableException $e) {
            $this->assertSame(403, $e->status);
            $this->assertSame(Product::COMING_SOON_DOWNLOAD_MESSAGE, $e->customerMessage);
        }
    }

    public function test_brainx_set_to_coming_soon_turns_new_people_away_from_its_installer(): void
    {
        Product::where('slug', 'brainx')->firstOrFail()->update(['is_coming_soon' => true]);

        $this->get('/brainx/download')->assertRedirect(route('products.show', 'brainx'))
            ->assertSessionHas('error', BrainXDownloadController::COMING_SOON_EXISTING_USERS);
        $this->getJson('/brainx/download')->assertForbidden()->assertJsonPath('coming_soon', true);

        // Signed in, but never used BrainX (no key of it — a key of another app does not count)
        $stranger = User::factory()->create();
        LicenseKey::create([
            'product_id' => $this->product('winx-tools', comingSoon: false)->id, 'user_id' => $stranger->id,
            'license_key' => LicenseKey::generateKey(), 'license_type' => 'yearly', 'status' => 'active', 'expires_at' => now()->addYear(),
        ]);
        $this->actingAs($stranger)->getJson('/brainx/download')->assertForbidden()->assertJsonPath('coming_soon', true);
    }

    public function test_someone_who_already_uses_brainx_still_gets_the_installer_while_it_is_coming_soon(): void
    {
        $brainx = Product::where('slug', 'brainx')->firstOrFail();
        $brainx->update(['is_coming_soon' => true]);
        // The installer is fetched from the release only once the gate let the request through
        Http::fake(['*' => Http::response('', 500)]);

        $user = User::factory()->create();
        LicenseKey::create([
            'product_id' => $brainx->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            // a lapsed key is still someone coming back
            'license_type' => 'monthly', 'status' => 'expired', 'expires_at' => now()->subMonth(),
        ]);

        $this->actingAs($user)->getJson('/brainx/download')
            ->assertStatus(503)                       // past the gate: the (faked) release is what failed
            ->assertJsonMissingPath('coming_soon');

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->getJson('/brainx/download')->assertStatus(503)->assertJsonMissingPath('coming_soon');
    }

    public function test_a_key_holder_of_brainx_cloud_counts_as_an_existing_user_too(): void
    {
        Product::where('slug', 'brainx')->update(['is_coming_soon' => false]);
        $cloud = Product::updateOrCreate(['slug' => 'brainx-cloud'], [
            'category_id' => $this->category->id, 'name' => 'BrainX Cloud', 'description' => 'x',
            'price' => 399, 'stock' => 0, 'is_active' => true, 'is_coming_soon' => true,
        ]);
        Http::fake(['*' => Http::response('', 500)]);
        $user = User::factory()->create();
        LicenseKey::create([
            'product_id' => $cloud->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'license_type' => 'monthly', 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)->getJson('/brainx/download')->assertStatus(503)->assertJsonMissingPath('coming_soon');
    }

    public function test_the_update_feed_of_installed_apps_stays_open_while_brainx_is_coming_soon(): void
    {
        Product::where('slug', 'brainx')->firstOrFail()->update(['is_coming_soon' => true]);
        Http::fake(['*' => Http::response('', 500)]);

        // Not 403: the feed went on to the release (faked down here), exactly as when BrainX is open
        foreach (['releases.win.json', 'releases.win.json.sig', 'BrainX-1.0.0-full.nupkg'] as $file) {
            $this->get("/brainx/download/{$file}")->assertStatus(503);
        }
    }

    public function test_a_brainx_key_holder_sees_the_download_on_their_license_page(): void
    {
        $brainx = Product::where('slug', 'brainx')->firstOrFail();
        $brainx->update(['is_coming_soon' => true]);
        $user = User::factory()->create();
        $key = LicenseKey::create([
            'product_id' => $brainx->id, 'user_id' => $user->id, 'license_key' => LicenseKey::generateKey(),
            'license_type' => 'monthly', 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertSee(route('brainx.download'), false);
    }

    public function test_brainx_listed_as_brainx_cloud_and_coming_soon_hands_out_no_installer(): void
    {
        // Production lists BrainX as `brainx-cloud`; a check on `brainx` alone let the installer
        // through (owner, 2026-10-12: "ยังดาวโหลด brainx ได้ทั้งๆที่ coming soon").
        Product::where('slug', 'brainx')->update(['is_coming_soon' => false]);
        Product::updateOrCreate(['slug' => 'brainx-cloud'], [
            'category_id' => $this->category->id, 'name' => 'BrainX Cloud', 'description' => 'x',
            'price' => 399, 'stock' => 0, 'is_active' => true, 'is_coming_soon' => true,
        ]);

        $this->get('/brainx/download')->assertRedirect(route('products.show', 'brainx-cloud'))
            ->assertSessionHas('error', BrainXDownloadController::COMING_SOON_EXISTING_USERS);
        $this->getJson('/brainx/download')->assertForbidden()->assertJsonPath('coming_soon', true);
    }

    public function test_the_product_page_and_the_download_center_show_the_label_instead_of_a_button(): void
    {
        [$product, , $buyer] = $this->boughtComingSoon('xcluade-agent');

        $html = $this->actingAs($buyer)->get(route('products.show', $product->slug))->assertOk()->getContent();
        $this->assertStringContainsString('data-coming-soon', $html);
        $this->assertStringContainsString('Coming Soon — ยังไม่เปิดให้ดาวน์โหลด', $html);
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $this->section($html, 'id="download"'));
        $this->assertStringNotContainsString('action="' . route('cart.add', $product) . '"', $html);

        // Not on sale either: a hand-made POST to the cart is refused
        $this->actingAs($buyer)->post(route('cart.add', $product), ['quantity' => 1])
            ->assertRedirect(route('products.show', $product->slug))
            ->assertSessionHas('error');
        $this->assertSame(0, CartItem::count());

        $center = $this->actingAs($buyer)->get(route('customer.downloads'))->assertOk()->getContent();
        $this->assertStringContainsString('data-coming-soon', $center);
        $this->assertStringNotContainsString('/download/xcluade-agent', $center);
    }

    public function test_once_its_date_has_passed_the_product_opens_by_itself(): void
    {
        [$product, $version, $buyer] = $this->boughtComingSoon('xcluade-agent');
        $product->update(['coming_soon_until' => now()->subMinute()]);

        $this->assertFalse($product->fresh()->isComingSoon());
        $this->assertSame(route('download.page', 'xcluade-agent'), $product->fresh()->downloadUrl());
        $this->getJson('/api/v1/products/xcluade-agent/version')
            ->assertJsonPath('coming_soon', false)
            ->assertJsonPath('download_url', route('download.product', ['slug' => 'xcluade-agent', 'version' => $version->version]));
        $this->actingAs($buyer)->get('/download/xcluade-agent')->assertOk();

        // The listing badges follow the date too, not the raw flag
        $this->assertStringNotContainsString('data-coming-soon', $this->actingAs($buyer)->get(route('products.show', 'xcluade-agent'))->getContent());
    }

    /**
     * Every app's own pages: set to Coming Soon, none of them links to the app's file any more and
     * each shows the label where the download button was. Navigation to a download page ("back
     * to the download page", "read the guide") stays — that page itself shows the label.
     *
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>}> slug, pages, file URLs
     */
    public static function appPages(): array
    {
        return [
            'CluadeX' => ['cluadex-ai-coding-assistant', ['/products/cluadex-ai-coding-assistant', '/cluadex/pricing'], ['/cluadex/download', '/download/cluadex-ai-coding-assistant']],
            'Tping' => ['tping', ['/tping', '/tping/pricing', '/tping/download', '/tping/install-guide'], ['/tping/download/apk']],
            'SMS Checker' => ['smschecker', ['/smschecker', '/smschecker/pricing', '/smschecker/download'], ['/smschecker/download/apk']],
            'LocalVPN' => ['localvpn', ['/products/localvpn', '/localvpn/pricing', '/localvpn/download', '/localvpn/install-guide'], ['/localvpn/download/apk', '/localvpn/download/windows']],
            'Anti X' => ['anti-x', ['/products/anti-x'], ['/anti-x/download']],
            'Reverse X' => ['reverse-x', ['/products/reverse-x'], ['/reverse-x/download']],
            'WinXTools' => ['winx-tools', ['/products/winx-tools'], ['/winx-tools/download']],
            'GigGok' => ['giggok', ['/products/giggok'], ['/giggok/download']],
            'AutoTradeX' => ['autotradex', ['/products/autotradex'], ['/autotradex/download']],
            'Aipray' => ['aipray', ['/apps/aipray'], ['/apps/aipray/download']],
            'GPUxMINE' => ['gpuxmine', ['/gpuxmine'], ['/gpuxmine/download']],
        ];
    }

    #[DataProvider('appPages')]
    public function test_an_apps_own_pages_show_the_label_instead_of_its_download(string $slug, array $pages, array $files): void
    {
        $this->product($slug, comingSoon: true);
        $this->actingAs(User::factory()->create());

        foreach ($pages as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            foreach ($files as $file) {
                $this->assertStringNotContainsString('href="' . url($file), $html, "{$page} still links to {$file}");
            }
            $this->assertStringContainsString('data-coming-soon', $html, $page);
            $this->assertStringContainsString('Coming Soon', $html, $page);
        }

        // And when the app is open, the same pages offer the download again
        Product::where('slug', $slug)->update(['is_coming_soon' => false]);
        $this->assertStringNotContainsString('data-coming-soon', $this->get($pages[0])->assertOk()->getContent(), $pages[0]);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function product(string $slug, bool $comingSoon): Product
    {
        $product = Product::firstOrCreate(['slug' => $slug], [
            'category_id' => $this->category->id,
            'name' => str($slug)->headline()->toString(),
            'description' => 'x',
            'price' => 990,
            'requires_license' => true,
            'is_active' => true,
        ]);
        $product->update(['is_active' => true, 'is_coming_soon' => $comingSoon, 'coming_soon_until' => null]);

        return $product->fresh();
    }

    private function release(Product $product): ProductVersion
    {
        return ProductVersion::create([
            'product_id' => $product->id,
            'version' => '1.0.0',
            'download_url' => "https://downloads.example.test/{$product->slug}-1.0.0.zip",
            'download_filename' => "{$product->slug}-1.0.0.zip",
            'file_size' => 1024,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    /** @return array{0: Product, 1: ProductVersion, 2: User, 3: LicenseKey} a coming-soon product its buyer paid for */
    private function boughtComingSoon(string $slug): array
    {
        $product = $this->product($slug, comingSoon: true);
        $version = $this->release($product);
        $buyer = User::factory()->create();

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'ORD-CS-' . $slug,
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'subtotal' => 990,
            'tax' => 0,
            'total' => 990,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 990,
            'quantity' => 1,
            'subtotal' => 990,
        ]);
        $license = LicenseKey::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'license_key' => 'CS-' . strtoupper($slug) . '-0001',
            'status' => 'active',
            'license_type' => 'lifetime',
            'max_activations' => 1,
            'activations' => 0,
        ]);

        return [$product, $version, $buyer, $license];
    }

    private function section(string $html, string $marker): string
    {
        $start = strpos($html, $marker);
        if ($start === false) {
            return '';
        }
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }
}
