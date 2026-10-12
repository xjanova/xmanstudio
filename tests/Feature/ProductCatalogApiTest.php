<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * GET /api/v1/catalog — what product.xman4289.com reads to show what this site shows.
 */
class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function product(string $slug, array $attributes = [], string $category = 'software'): Product
    {
        $cat = Category::firstOrCreate(['slug' => $category], ['name' => $category, 'description' => 'x', 'is_active' => true]);

        return Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $cat->id, 'name' => $slug, 'description' => $slug, 'price' => 199, 'stock' => 0,
            'is_active' => true, 'is_coming_soon' => false, 'image' => null, ...$attributes,
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function catalog(): array
    {
        $json = $this->withHeader('Origin', 'https://product.xman4289.com')
            ->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonPath('success', true)
            ->json('products');

        return collect($json)->keyBy('slug')->all();
    }

    public function test_it_says_what_is_on_sale_and_what_is_not(): void
    {
        $this->product('winx-tools', ['image' => 'products/real/winx-tools.webp']);
        $this->product('gpuxmine', ['is_coming_soon' => true]);
        $this->product('smschecker', ['is_active' => false]);
        $this->product('zz-pack', [], Category::APP_ONLY_SLUGS[0]);

        $catalog = $this->catalog();

        $this->assertSame('on_sale', $catalog['winx-tools']['status']);
        $this->assertTrue($catalog['winx-tools']['real_image']);
        $this->assertSame(url(Storage::url('products/real/winx-tools.webp')), $catalog['winx-tools']['image_url']);
        $this->assertSame(route('winx-tools.download'), $catalog['winx-tools']['download_url']);
        $this->assertSame(route('products.show', 'winx-tools'), $catalog['winx-tools']['url']);

        // Coming Soon: no download, even though the app has a download route.
        $this->assertSame('coming_soon', $catalog['gpuxmine']['status']);
        $this->assertNull($catalog['gpuxmine']['download_url']);
        $this->assertFalse($catalog['gpuxmine']['real_image']);
        $this->assertSame(asset('artwork/hub/gpuxmine.webp'), $catalog['gpuxmine']['image_url']);

        $this->assertSame('off_sale', $catalog['smschecker']['status']);
        $this->assertNull($catalog['smschecker']['download_url']);

        // Avatar packs are sold inside the GigGok app, not on the website.
        $this->assertArrayNotHasKey('zz-pack', $catalog);
    }

    public function test_every_url_is_absolute(): void
    {
        $this->product('tping');

        foreach ($this->catalog() as $slug => $entry) {
            foreach (['image_url', 'url', 'download_url'] as $key) {
                if ($entry[$key] !== null) {
                    $this->assertMatchesRegularExpression('#^https?://#', $entry[$key], "{$slug}.{$key}");
                }
            }
        }
    }
}
