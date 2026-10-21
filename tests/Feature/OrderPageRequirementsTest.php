<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The order page shows what the customer asked for on each line.
 *
 * A licence bought by term (the WinXTools / SMS Checker / Tping buy buttons)
 * stores {"license_type": "..."} in custom_requirements. The page handed that
 * to the page-builder renderer, which walked the object as if it were a list
 * of blocks and died on the first string — so every such order answered 500
 * right after checkout, and the customer never saw how to pay.
 */
class OrderPageRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $category = Category::create(['name' => 'Software', 'slug' => 'software', 'description' => 'Software']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'WinXTools',
            'slug' => 'winx-tools',
            'description' => 'x',
            'price' => 199,
            'stock' => 999,
            'requires_license' => true,
            'is_active' => true,
        ]);

        $this->buyer = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => 'user',
        ]);
    }

    private function orderWith(?string $requirements): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $this->buyer->id,
            'customer_name' => $this->buyer->name,
            'customer_email' => $this->buyer->email,
            'customer_phone' => '0800000000',
            'subtotal' => 199,
            'total' => 199,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'WinXTools',
            'price' => 199,
            'quantity' => 1,
            'subtotal' => 199,
            'custom_requirements' => $requirements,
        ]);

        return $order;
    }

    public function test_a_licence_bought_by_term_shows_its_term_instead_of_failing(): void
    {
        $order = $this->orderWith(json_encode(['license_type' => 'lifetime']));

        $this->actingAs($this->buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('ตลอดชีพ');
    }

    public function test_the_term_objects_other_products_store_render_too(): void
    {
        // Tping / SMS Checker / LocalVPN add duration_days next to the term.
        $order = $this->orderWith(json_encode(['license_type' => 'monthly', 'duration_days' => 30]));

        $this->actingAs($this->buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('รายเดือน');
    }

    public function test_written_requirements_still_render(): void
    {
        $order = $this->orderWith(json_encode([
            ['type' => 'heading', 'content' => 'Landing page', 'level' => 'h2'],
            ['type' => 'text', 'content' => 'Three sections, Thai and English'],
        ]));

        $this->actingAs($this->buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Landing page')
            ->assertSee('Three sections, Thai and English');
    }

    public function test_plain_text_requirements_still_render(): void
    {
        $order = $this->orderWith('Please install on two PCs');

        $this->actingAs($this->buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Please install on two PCs');
    }

    public function test_the_block_renderer_ignores_json_that_is_not_blocks(): void
    {
        // Any other JSON object (or a list holding stray values) must not take the page down.
        $html = $this->blade('<x-page-builder-render :content="$content" />', [
            'content' => json_encode(['license_type' => 'yearly', 'note' => ['x' => 1], 7, 'stray']),
        ]);

        $html->assertDontSee('license_type');
    }
}
