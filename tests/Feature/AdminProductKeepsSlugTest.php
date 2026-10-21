<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-10-21: saving a product in the admin rebuilt its slug from the name, so 10 products on
 * production would have moved the moment anyone changed a price (winx-tools → winxtools,
 * aipray → aipray-buddhist-chanting-companion, pack-mind01 → ""). Download routes, the apps'
 * update checks and licence APIs find products by slug, and they would all have answered 404.
 */
class AdminProductKeepsSlugTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, string $slug): Product
    {
        $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps', 'is_active' => true]);

        // Migrations already register some of these apps (aipray): take the row they made
        return Product::updateOrCreate(['slug' => $slug], [
            'category_id' => $category->id,
            'name' => $name,
            'description' => 'x',
            'price' => 990,
            'stock' => 999,
            'is_active' => true,
        ]);
    }

    private function save(Product $product, array $fields)
    {
        $this->withoutVite();

        return $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.products.update', $product), $fields + [
                'name' => $product->name,
                'description' => 'x',
                'price' => 1290,
                'category_id' => $product->category_id,
                'is_active' => '1',
            ]);
    }

    public function test_changing_the_price_keeps_a_slug_that_differs_from_the_name(): void
    {
        $product = $this->product('WinXTools', 'winx-tools');

        $this->save($product, [])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('winx-tools', $product->slug);
        $this->assertEquals(1290, $product->price);
    }

    public function test_renaming_a_product_keeps_its_slug(): void
    {
        $product = $this->product('Aipray', 'aipray');

        $this->save($product, ['name' => 'Aipray - Buddhist Chanting Companion'])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Aipray - Buddhist Chanting Companion', $product->name);
        $this->assertSame('aipray', $product->slug);
    }

    public function test_a_name_with_no_latin_letters_never_empties_the_slug(): void
    {
        $product = $this->product('แพ็กมายด์ 01', 'pack-mind01');

        $this->save($product, [])->assertSessionHasNoErrors();

        $this->assertSame('pack-mind01', $product->refresh()->slug);
    }
}
