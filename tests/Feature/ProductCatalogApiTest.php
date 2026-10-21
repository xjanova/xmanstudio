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

    public function test_it_carries_the_shops_one_price_table(): void
    {
        config(['licenses.plans.autotradex' => ['monthly' => 349, 'yearly' => 1990]]);
        $this->product('autotradex', ['price' => 19900]);
        $this->product('postx-agent', ['price' => 7990, 'is_coming_soon' => true]);

        $json = $this->getJson('/api/v1/catalog')->assertOk();
        $catalog = collect($json->json('products'))->keyBy('slug');

        $this->assertSame(['monthly' => 349, 'yearly' => 1990], $catalog['autotradex']['plans']);
        $this->assertEquals(19900, $catalog['autotradex']['price']);
        // No terms: an object, not a list, so the hub can read it the same way
        $this->assertStringContainsString('"plans":{}', $json->getContent());
        $this->assertEquals(7990, $catalog['postx-agent']['price']);
        // The whole table, BrainX Cloud's included (it has no product row of its own)
        $this->assertSame(['monthly' => 399], $json->json('plans.brainx'));
        $this->assertSame(['monthly' => 349, 'yearly' => 1990], $json->json('plans.autotradex'));
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
