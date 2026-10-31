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
 * GPUsharX's product page (/products/gpusharx)
 *
 *   — GPUsharX has no source code yet: the page speaks of the plan only, with no price, no revenue
 *     share figure, no GPU list and no requirements it cannot back
 *   — nothing is sold here: no cart form, no licence term, no price tag — the same as the old page
 *   — Coming Soon turns every download into the label; the buttons otherwise stay what the old page
 *     had (the Download Center for a buyer, the product list for everyone else)
 *   — GPUxMINE is mentioned only while it is actually on offer
 */
class GpusharXProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $category = Category::firstOrCreate(['slug' => 'cloud'], ['name' => 'Cloud', 'description' => 'x']);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'GPUsharX',
            'slug' => 'gpusharx',
            'description' => 'x',
            'price' => 0,
            'requires_license' => false,
            'is_active' => true,
            'is_coming_soon' => true,
        ]);
    }

    public function test_the_page_is_its_own_view(): void
    {
        $this->get('/products/gpusharx')
            ->assertOk()
            ->assertViewIs('products.gpusharx')
            ->assertSee('GPUsharX')
            ->assertSee('application/ld+json', false)
            ->assertSee('id="plan"', false);
    }

    public function test_coming_soon_shows_the_label_and_no_download_anywhere(): void
    {
        foreach ([null, $this->buyer()] as $viewer) {
            $html = ($viewer ? $this->actingAs($viewer) : $this)->get('/products/gpusharx')->assertOk()->getContent();
            $main = $this->content($html);

            // hero + closing call to action
            $this->assertSame(2, substr_count($main, 'data-coming-soon'));
            $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $main);
        }
    }

    public function test_nothing_is_for_sale_and_no_unbacked_figure_is_shown(): void
    {
        foreach ([true, false] as $comingSoon) {
            $this->product->update(['is_coming_soon' => $comingSoon]);

            foreach ([null, User::factory()->create()] as $viewer) {
                $html = ($viewer ? $this->actingAs($viewer) : $this)->get('/products/gpusharx')->assertOk()->getContent();
                $main = $this->content($html);

                $this->assertStringNotContainsString(route('cart.add', $this->product), $main);
                $this->assertStringNotContainsString('license_type', $main);
                $this->assertStringNotContainsString('data-tpix-price', $main);
                // what a reader sees (class names such as w-[150%] are not figures)
                $text = strip_tags($main);
                $this->assertDoesNotMatchRegularExpression('/฿\s?\d/u', $text, 'a baht amount on the page');
                $this->assertDoesNotMatchRegularExpression('/\$\s?\d/u', $text, 'a dollar amount on the page');
                // the revenue share is not set in any code yet
                $this->assertDoesNotMatchRegularExpression('/\d+\s?%/u', $text, 'a percentage on the page');
                // the old page's unbacked claims
                foreach (['api.gpusharx.com', 'RTX 4090', 'MI250X', 'USDT', 'Docker'] as $gone) {
                    $this->assertStringNotContainsString($gone, $text);
                }
            }
        }
    }

    public function test_once_open_a_buyer_gets_the_download_center_and_others_the_product_list(): void
    {
        $this->product->update(['is_coming_soon' => false]);

        $main = $this->content($this->actingAs($this->buyer())->get('/products/gpusharx')->assertOk()->getContent());
        $this->assertStringNotContainsString('data-coming-soon', $main);
        // hero + closing call to action
        $this->assertSame(2, substr_count($main, 'href="' . route('customer.downloads') . '"'));

        $main = $this->content($this->actingAs(User::factory()->create())->get('/products/gpusharx')->assertOk()->getContent());
        $this->assertStringNotContainsString('href="' . route('customer.downloads') . '"', $main);
        $this->assertStringContainsString('href="' . route('products.index') . '"', $main);
    }

    public function test_gpuxmine_is_mentioned_only_while_it_is_on_offer(): void
    {
        // a migration (register_gpuxmine_product) may have created the row already
        $gpuxmine = Product::updateOrCreate(['slug' => 'gpuxmine'], [
            'category_id' => $this->product->category_id,
            'name' => 'GPUxMINE Worker',
            'description' => 'x',
            'price' => 0,
            'is_active' => true,
            'is_coming_soon' => true,
            'coming_soon_until' => null,
        ]);

        $this->get('/products/gpusharx')->assertOk()->assertDontSee('data-gpuxmine-link', false);

        $gpuxmine->update(['is_coming_soon' => false]);
        $this->get('/products/gpusharx')->assertOk()
            ->assertSee('data-gpuxmine-link', false)
            ->assertSee('href="' . route('products.show', 'gpuxmine') . '"', false);
    }

    /** Someone with a completed order for GPUsharX (what the controller calls $hasPurchased). */
    private function buyer(): User
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(), 'user_id' => $user->id, 'customer_name' => $user->name, 'customer_email' => $user->email,
            'customer_phone' => '0800000000', 'subtotal' => 0, 'total' => 0, 'status' => 'completed', 'payment_status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $this->product->id, 'product_name' => 'GPUsharX',
            'price' => 0, 'quantity' => 1, 'subtotal' => 0,
        ]);

        return $user;
    }

    /** The page's own part, without the site's header and footer. */
    private function content(string $html): string
    {
        $start = strpos($html, 'class="xp relative overflow-hidden"');
        $this->assertNotFalse($start, 'the product page frame');
        $end = strpos($html, 'application/ld+json', $start);

        return substr($html, $start, $end === false ? null : $end - $start);
    }
}
