<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Avatar packs are sold inside the GigGok app, not on the website.
 *
 * They reuse the products table to inherit the cart, coupons, payment methods
 * and license issuing. The cost of that reuse is that they also inherited the
 * public catalogue, where an outfit for a 3D character sat next to enterprise
 * software and meant nothing to a visitor.
 *
 * The line drawn here is BROWSING vs REACHING. A pack must never appear in a
 * listing, a category menu or a related-products sidebar. Its own product page
 * must keep working, because that is the page the app opens when someone buys
 * a pack and it is where paying actually happens.
 */
class PackWebsiteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Category $packCategory;

    protected Category $normalCategory;

    protected Product $pack;

    protected Product $normal;

    protected function setUp(): void
    {
        parent::setUp();

        // Without an admin the homepage redirects to the setup wizard, so the
        // two homepage assertions below would pass on a 302 without ever
        // rendering the product list they are supposed to be checking.
        User::factory()->create(['role' => 'admin']);

        $this->packCategory = Category::create([
            'name' => 'ชุดตัวมายด์ (GigGok)',
            'slug' => Category::APP_ONLY_SLUGS[0],
            'description' => 'Avatar packs',
            'is_active' => true,
        ]);

        $this->normalCategory = Category::create([
            'name' => 'Software',
            'slug' => 'software',
            'description' => 'Normal products',
            'is_active' => true,
        ]);

        $this->pack = $this->makeProduct($this->packCategory, 'mind01', 'น้องมายด์เริ่มต้น');
        $this->normal = $this->makeProduct($this->normalCategory, 'skidrow-killer', 'Skidrow Killer');

        AvatarPack::create([
            'product_id' => $this->pack->id,
            'pack_id' => 'mind01',
            'kind' => 'character',
            'name_en' => 'Mind1',
            'is_active' => true,
        ]);
    }

    protected function makeProduct(Category $category, string $slug, string $name): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'description' => $name,
            'price' => 0,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }

    public function test_the_public_catalogue_does_not_list_a_pack(): void
    {
        $res = $this->get('/products')->assertOk();

        $res->assertDontSee('น้องมายด์เริ่มต้น', false);
        // The ordinary product must still be there - proving the filter removed
        // the packs rather than emptying the page.
        $res->assertSee('Skidrow Killer', false);
    }

    public function test_the_homepage_does_not_list_a_pack(): void
    {
        // Packs are the newest rows in the table, so "featured = newest" put
        // them on the front page first of all.
        $res = $this->get('/')->assertOk();

        $res->assertDontSee('น้องมายด์เริ่มต้น', false);
    }

    public function test_filtering_by_the_pack_category_still_shows_no_packs(): void
    {
        // The slug comes straight off the query string, so hiding the category
        // from the menu is not enough on its own - someone can still type it.
        $res = $this->get('/products?category=' . Category::APP_ONLY_SLUGS[0])->assertOk();

        $res->assertDontSee('น้องมายด์เริ่มต้น', false);
    }

    public function test_the_category_menu_does_not_offer_the_pack_category(): void
    {
        $this->get('/products')
            ->assertOk()
            ->assertDontSee('ชุดตัวมายด์ (GigGok)', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('ชุดตัวมายด์ (GigGok)', false);
    }

    public function test_a_normal_product_page_does_not_suggest_a_pack_as_related(): void
    {
        $sibling = $this->makeProduct($this->packCategory, 'mind02', 'มายด์ชุดที่สอง');

        $this->get('/products/' . $this->pack->slug)
            ->assertOk()
            ->assertDontSee($sibling->name, false);
    }

    public function test_the_pack_product_page_itself_still_works(): void
    {
        // This is the page the app opens to take a payment. Hiding packs from
        // browsing must not take the buy flow down with it.
        $this->get('/products/' . $this->pack->slug)
            ->assertOk()
            ->assertSee('น้องมายด์เริ่มต้น', false);
    }

    public function test_the_app_buy_link_still_reaches_that_page(): void
    {
        $this->get('/shop/mind01')
            ->assertRedirect(route('products.show', $this->pack->slug));
    }

    public function test_the_app_catalogue_api_still_lists_the_pack(): void
    {
        // Hiding packs from the website must not hide them from the app, which
        // reads avatar_packs directly and is the only place they are for sale.
        $this->getJson('/api/packs')
            ->assertOk()
            ->assertJsonPath('packs.0.id', 'mind01');
    }
}
