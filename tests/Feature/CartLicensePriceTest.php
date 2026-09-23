<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Who sets the price of a licence term: the shop.
 *
 * The pages that sell a licence by term (monthly / yearly / lifetime) posted
 * the price with the term, and the cart charged whatever arrived — so the
 * price field decided what a lifetime licence cost, and leaving it out bought
 * any term at the product's base price, which is the monthly one.
 */
class CartLicensePriceTest extends TestCase
{
    use RefreshDatabase;

    private Product $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'description' => 'Software']);

        $this->checker = Product::create([
            'category_id' => $category->id,
            'name' => 'SMS Payment Checker',
            'slug' => 'sms-payment-checker',
            'description' => 'x',
            'price' => 990,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        $this->actingAs(User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => 'user',
        ]));
    }

    public function test_the_term_price_comes_from_the_shop_not_the_form(): void
    {
        $this->post(route('cart.add', $this->checker), ['license_type' => 'lifetime', 'price' => 1]);

        $item = CartItem::firstOrFail();
        $this->assertEquals(29900, (float) $item->price);
        $this->assertSame('lifetime', json_decode($item->custom_requirements, true)['license_type']);
    }

    public function test_leaving_the_price_out_does_not_buy_a_term_at_the_base_price(): void
    {
        $this->post(route('cart.add', $this->checker), ['license_type' => 'lifetime']);

        $this->assertEquals(29900, (float) CartItem::firstOrFail()->price);
    }

    public function test_each_term_is_charged_what_the_page_shows(): void
    {
        foreach (['monthly' => 990, 'yearly' => 9900] as $term => $expected) {
            CartItem::query()->delete();

            $this->post(route('cart.add', $this->checker), ['license_type' => $term, 'price' => $expected]);

            $this->assertEquals($expected, (float) CartItem::firstOrFail()->price, $term);
        }
    }

    public function test_a_term_the_product_is_not_sold_by_is_refused(): void
    {
        $other = Product::create([
            'category_id' => $this->checker->category_id,
            'name' => 'Other',
            'slug' => 'other-tool',
            'description' => 'x',
            'price' => 19900,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        $this->post(route('cart.add', $other), ['license_type' => 'lifetime', 'price' => 1])
            ->assertSessionHas('error');

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_plain_add_still_charges_the_product_price(): void
    {
        $this->post(route('cart.add', $this->checker), ['price' => 1]);

        $item = CartItem::firstOrFail();
        $this->assertEquals(990, (float) $item->price);
        $this->assertNull($item->custom_requirements);
    }
}
