<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * One picture per product, everywhere (owner, 2026-10-12): the real one set in the admin, else
 * the product's picture from product.xman4289.com, else the older artwork. See
 * Product::getArtworkUrlAttribute().
 */
class ProductPictureTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_10_12_120000_set_real_product_images.php';

    private function product(string $slug, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x', 'is_active' => true]);

        return Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $category->id,
            'name' => $slug,
            'description' => $slug,
            'price' => 199,
            'stock' => 0,
            'is_active' => true,
            'image' => null,
            'images' => null,
            ...$attributes,
        ]);
    }

    public function test_the_real_picture_set_in_the_admin_wins(): void
    {
        $uploaded = new Product(['slug' => 'tping', 'image' => 'products/real/tping.webp']);
        $linked = new Product(['slug' => 'tping', 'image' => 'https://cdn.example/tping.png']);

        $this->assertSame(Storage::url('products/real/tping.webp'), $uploaded->artwork_url);
        $this->assertTrue($uploaded->hasRealImage());
        $this->assertSame('https://cdn.example/tping.png', $linked->artwork_url);
    }

    public function test_without_a_real_picture_the_product_hubs_is_shown(): void
    {
        $product = new Product(['slug' => 'tping']);

        $this->assertSame(asset('artwork/hub/tping.webp'), $product->artwork_url);
        $this->assertFalse($product->hasRealImage());
    }

    public function test_without_either_the_older_artwork_is_shown_else_nothing(): void
    {
        // artwork/product/brainx.webp exists; the hub's BrainX picture is the brainx-cloud row's.
        $this->assertFileDoesNotExist(public_path('artwork/hub/brainx.webp'));
        $this->assertSame(asset('artwork/product/brainx.webp'), (new Product(['slug' => 'brainx']))->artwork_url);
        $this->assertNull((new Product(['slug' => 'zz-nothing']))->artwork_url);
    }

    public function test_every_product_hub_picture_has_its_copy_on_this_site(): void
    {
        // The hub names products by its own id; two differ from the shop's slug.
        $slugOf = ['cluadex' => 'cluadex-ai-coding-assistant', 'brainx' => 'brainx-cloud'];
        $files = glob(base_path('sites/product.xman4289.com/assets/art/*.webp'));
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $id = pathinfo($file, PATHINFO_FILENAME);
            $copy = public_path('artwork/hub/' . ($slugOf[$id] ?? $id) . '.webp');

            $this->assertFileExists($copy, $id);
            $this->assertSame(md5_file($file), md5_file($copy), "artwork/hub copy of {$id} is out of date");
        }
    }

    public function test_the_migration_sets_the_real_pictures_and_keeps_the_admins_own(): void
    {
        Storage::fake('public');
        $this->product('anti-x');
        $this->product('cluadex-ai-coding-assistant', ['image' => 'products/lLtdJBSO4HAVoCLikuRv70LIQbUnqcSXtEV6zjKh.webp']);
        $this->product('localvpn', ['image' => 'products/chosen-by-the-admin.webp']);
        $this->product('spiderx', [
            'image' => 'https://raw.githubusercontent.com/xjanova/SpiderX/main/docs/logo.png',
            'images' => ['https://raw.githubusercontent.com/xjanova/SpiderX/main/docs/logo.png', 'products/spiderx-2.png'],
        ]);
        $this->product('tping');

        $migration = require database_path('migrations/' . self::MIGRATION);
        $migration->seed(Storage::disk('public'));

        foreach (['anti-x', 'cluadex-ai-coding-assistant'] as $slug) {
            $this->assertSame("products/real/{$slug}.webp", Product::where('slug', $slug)->value('image'), $slug);
            Storage::disk('public')->assertExists("products/real/{$slug}.webp");
        }
        $this->assertSame('products/chosen-by-the-admin.webp', Product::where('slug', 'localvpn')->value('image'));

        $spiderx = Product::where('slug', 'spiderx')->first();
        $this->assertNull($spiderx->image);
        $this->assertSame(['products/spiderx-2.png'], $spiderx->images);
        $this->assertSame(asset('artwork/hub/spiderx.webp'), $spiderx->artwork_url);

        // No real picture of Tping exists yet: it keeps the hub's.
        $this->assertNull(Product::where('slug', 'tping')->value('image'));

        // Run again on production by a later deploy: nothing changes.
        $this->assertSame([], $migration->seed(Storage::disk('public')));
    }

    public function test_skidrow_killer_shows_the_hub_picture_instead_of_its_logo(): void
    {
        $this->product('skidrow-killer', [
            'image' => 'products/skidrow-killer/logo.png',
            'images' => ['products/skidrow-killer/screenshot-1.png'],
        ]);

        (require database_path('migrations/2026_10_12_150000_skidrow_killer_shows_the_hub_picture.php'))->up();

        $product = Product::where('slug', 'skidrow-killer')->first();
        $this->assertNull($product->image);
        $this->assertSame(asset('artwork/hub/skidrow-killer.webp'), $product->artwork_url);
        $this->assertSame(['products/skidrow-killer/screenshot-1.png'], $product->images);
    }

    public function test_every_real_picture_is_a_960x540_cut(): void
    {
        foreach (['anti-x', 'cluadex-ai-coding-assistant', 'localvpn', 'winx-tools', 'aipray'] as $slug) {
            $path = resource_path("product-images/{$slug}.webp");
            $this->assertFileExists($path);
            $this->assertSame([960, 540], array_slice(getimagesize($path), 0, 2), $slug);
        }
    }

    public function test_the_admin_says_which_products_still_show_the_default_picture(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->product('tping');
        $real = $this->product('winx-tools', ['image' => 'products/real/winx-tools.webp']);

        $this->get(route('admin.products.edit', $real))
            ->assertOk()
            ->assertSee(Storage::url('products/real/winx-tools.webp'), false)
            ->assertSee('รูปภาพปัจจุบัน (รูปจริง)', false);

        $this->get(route('admin.products.edit', Product::where('slug', 'tping')->first()))
            ->assertOk()
            ->assertSee(asset('artwork/hub/tping.webp'), false)
            ->assertSee('ยังไม่มีรูปจริง', false);
    }
}
