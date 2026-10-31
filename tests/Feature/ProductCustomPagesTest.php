<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProductController::show picks a custom page by the product's slug. Four keys
 * matched no product ('xcluadeagent', 'live-x-shop-pro', 'winxtools',
 * 'postxagent') and were removed rather than renamed: renaming them would have
 * put XcluadeAgent, still coming soon at ฿3,490, on the CluadeX page with its
 * ฿199-a-year buy buttons.
 */
class ProductCustomPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_products_whose_custom_page_key_never_matched_keep_the_generic_page(): void
    {
        // postx-agent has had its own reviewed page since 2026-10 (products.postxagent — PostXAgentProductPageTest)
        // livexshop-pro too (products.livexshoppro — LiveXShopProProductPageTest)
        // and xcluade-agent (products.xcluade-agent — XcluadeAgentProductPageTest), so a made-up slug stands in
        foreach (['some-other-tool'] as $slug) {
            $this->product($slug, ['is_coming_soon' => true, 'price' => 3490]);

            $this->get("/products/{$slug}")->assertOk()->assertViewIs('products.show');
        }
    }

    public function test_the_products_that_have_a_custom_page_still_get_it(): void
    {
        // XcluadeAgent has its own page — never the CluadeX one (products.xcluadeagent) with its ฿199-a-year buttons
        foreach (['winx-tools' => 'products.winxtools', 'cluadex-ai-coding-assistant' => 'products.xcluadeagent', 'xcluade-agent' => 'products.xcluade-agent'] as $slug => $view) {
            $this->product($slug);

            $this->get("/products/{$slug}")->assertOk()->assertViewIs($view);
        }
    }

    private function product(string $slug, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'software'],
            ['name' => 'Software', 'description' => 'x']
        );

        return Product::create($attributes + [
            'category_id' => $category->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'description' => 'x',
            'price' => 0,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }
}
