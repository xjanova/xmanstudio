<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SMS Checker sales are closed until the owner reopens them (2026-09-23: the
 * prices on the pages and in the code disagreed). The switch is the product's
 * own is_active in แอดมิน → สินค้า.
 *
 * The rules:
 *   — while switched off, every page that shows or sells it answers 404
 *   — the APK download stays: the app's update check points customers there
 *   — switching the product back on reopens the pages, no deploy
 */
class SmsCheckerSalesClosedTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // A migration seeds the product; create it only if this schema has not.
        $this->product = Product::where('slug', 'smschecker')->first() ?? Product::create([
            'category_id' => Category::create(['name' => 'Apps', 'slug' => 'apps', 'description' => 'Apps'])->id,
            'name' => 'SmsChecker',
            'slug' => 'smschecker',
            'description' => 'x',
            'price' => 499,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        $this->product->forceFill(['is_active' => false])->save();
    }

    public function test_the_sales_pages_are_dark_while_the_product_is_off(): void
    {
        foreach (['/smschecker', '/smschecker/pricing', '/smschecker/buy', '/smschecker/download', '/products/smschecker'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_the_apk_download_is_not_closed(): void
    {
        $this->assertNotSame(404, $this->get('/smschecker/download/apk')->getStatusCode(),
            'the app update check sends existing customers here');
    }

    public function test_switching_the_product_back_on_reopens_the_page(): void
    {
        $this->product->forceFill(['is_active' => true])->save();

        $this->assertNotSame(404, $this->get('/smschecker/pricing')->getStatusCode());
    }
}
