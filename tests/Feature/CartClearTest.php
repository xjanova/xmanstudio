<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The cart's "clear" button posted DELETE to /cart, which only answers GET,
 * so every customer who pressed it got a 405 page and a cart still full.
 */
class CartClearTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'description' => 'Software']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Plain Product',
            'slug' => 'plain-product',
            'description' => 'x',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs(User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => 'user',
        ]));

        $this->post(route('cart.add', $product));
    }

    public function test_the_clear_button_posts_to_the_clear_route(): void
    {
        $this->assertSame(1, CartItem::count());

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('action="' . route('cart.clear') . '"', false);
    }

    public function test_clearing_empties_the_cart(): void
    {
        $this->delete(route('cart.clear'))->assertRedirect(route('cart.index'));

        $this->assertSame(0, CartItem::count());
    }
}
