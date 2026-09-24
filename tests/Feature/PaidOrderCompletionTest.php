<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A paid order whose licences are out is "completed". Every page that asks "did this customer buy
 * it?" looks for exactly that: /download/{slug}, the CluadeX and Chanthra Studio pages, the product
 * page, the support-ticket form and the admin revenue figures.
 *
 * Found 2026-09-24. An admin approving the slip (the Orders page or the Telegram bot) wrote
 * "processing" over the "completed" that issuing the licences had just set, and the Tping,
 * SmsChecker and LocalVPN wallet checkouts never completed their orders at all. Production held
 * three paid orders at "processing" with their keys issued: 9 and 10 (Tping, wallet) and 12
 * (CluadeX, a wallet order placed just before the checkout started completing orders). A buyer of
 * such an order who opened /download/{slug} was sent to the shop with "คุณต้องซื้อผลิตภัณฑ์นี้ก่อน".
 */
class PaidOrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    private const COMPLETION_MIGRATION = '2026_09_24_200000_complete_paid_orders_left_processing.php';

    private Product $product;

    private ?User $admin = null;

    private int $orders = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::preventStrayRequests();
        $this->withoutVite();

        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);

        // A licensed app with no download route of its own: its buyers download from /download/{slug}
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Some Desktop App',
            'slug' => 'some-desktop-app',
            'description' => 'x',
            'price' => 990,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);

        ProductVersion::create([
            'product_id' => $this->product->id,
            'version' => '2.0.0',
            'github_release_url' => 'https://files.example.net/SomeApp-2.0.0.zip',
            'download_filename' => 'SomeApp-2.0.0.zip',
            'is_active' => true,
        ]);
    }

    // ── an admin approving the slip ──────────────────────────────────

    public function test_an_approved_slip_completes_the_order_and_its_buyer_can_open_the_download_page(): void
    {
        [$order, $buyer] = $this->order('pending', 'verifying');

        $this->setPaymentStatusAsAdmin($order, 'paid');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->where('user_id', $buyer->id)->count());

        $this->actingAs($buyer)->get(route('download.page', $this->product->slug))
            ->assertOk()
            ->assertViewHas('hasValidLicense', true);
    }

    public function test_approving_a_completed_order_again_keeps_it_completed(): void
    {
        [$order] = $this->order('pending', 'verifying');
        $this->setPaymentStatusAsAdmin($order, 'paid');

        // Put back to "verifying" by mistake, then approved again
        $this->setPaymentStatusAsAdmin($order, 'verifying');
        $this->setPaymentStatusAsAdmin($order, 'paid');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->count());
    }

    public function test_approving_an_order_rejected_by_mistake_completes_it_again(): void
    {
        [$order, $buyer] = $this->order('pending', 'verifying');
        $this->setPaymentStatusAsAdmin($order, 'paid');
        $this->setPaymentStatusAsAdmin($order, 'rejected');
        $this->assertSame('cancelled', $order->fresh()->status);

        // The key from the first approval is still out, so there is nothing new to issue
        $this->setPaymentStatusAsAdmin($order, 'paid');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(1, LicenseKey::where('order_id', $order->id)->count());
        $this->actingAs($buyer)->get(route('download.page', $this->product->slug))->assertOk();
    }

    public function test_an_approved_order_with_nothing_to_licence_stays_processing(): void
    {
        // Approval issues nothing, so the order waits at "processing" for whoever delivers it
        $this->product->update(['requires_license' => false]);
        [$order] = $this->order('pending', 'verifying');

        $this->setPaymentStatusAsAdmin($order, 'paid');

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_approving_a_payment_again_never_reopens_an_order_delivered_by_hand(): void
    {
        // Nothing to licence, marked completed by whoever delivered it, then its payment re-checked
        $this->product->update(['requires_license' => false]);
        [$order] = $this->order('completed', 'verifying');

        $this->setPaymentStatusAsAdmin($order, 'paid');

        $this->assertSame('completed', $order->fresh()->status);
    }

    // ── wallet checkouts of the apps sold from their own pages ──────

    public function test_a_tping_wallet_purchase_completes_the_order(): void
    {
        $this->assertWalletPurchaseCompletes('tping', route('tping.process', 'monthly'));
    }

    public function test_a_localvpn_wallet_purchase_completes_the_order(): void
    {
        $this->assertWalletPurchaseCompletes('localvpn', route('localvpn.process', 'monthly'));
    }

    public function test_a_smschecker_wallet_purchase_completes_the_order(): void
    {
        // Sales are switched off in production until the owner reopens them; the checkout is the same
        Product::where('slug', 'smschecker')->update(['is_active' => true]);

        $this->assertWalletPurchaseCompletes('smschecker', route('smschecker.process', 'monthly'));
    }

    // ── the orders production already holds ─────────────────────────

    public function test_the_migration_completes_paid_orders_left_processing_with_their_keys_out(): void
    {
        [$stuck, $stuckBuyer] = $this->order('processing', 'paid');
        $this->key($stuck);
        [$renewal] = $this->order('processing', 'paid');
        $this->renewal($renewal);

        $this->actingAs($stuckBuyer)->get(route('download.page', $this->product->slug))
            ->assertRedirect(route('products.index'));

        $this->completionMigration()->up();

        $this->assertSame('completed', $stuck->fresh()->status);
        $this->assertSame('completed', $renewal->fresh()->status);
        $this->actingAs($stuckBuyer)->get(route('download.page', $this->product->slug))->assertOk();
    }

    public function test_the_migration_leaves_orders_without_proof_of_delivery_alone(): void
    {
        [$nothingIssued] = $this->order('processing', 'paid');
        [$paymentPending] = $this->order('processing', 'pending');
        $this->key($paymentPending);
        [$keyDeleted] = $this->order('processing', 'paid');
        $this->key($keyDeleted)->delete();
        [$cancelled] = $this->order('cancelled', 'rejected');
        $this->key($cancelled);

        $this->completionMigration()->up();
        $this->completionMigration()->up();

        $this->assertSame('processing', $nothingIssued->fresh()->status);
        $this->assertSame('processing', $paymentPending->fresh()->status);
        $this->assertSame('processing', $keyDeleted->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    // ── helpers ─────────────────────────────────────────────────────

    private function assertWalletPurchaseCompletes(string $slug, string $checkoutUrl): void
    {
        $buyer = User::factory()->create();
        Wallet::getOrCreateForUser($buyer->id)->update(['balance' => 10000]);

        $this->actingAs($buyer)->post($checkoutUrl, [
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_phone' => '0812345678',
            'payment_method' => 'wallet',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');

        $order = Order::where('user_id', $buyer->id)->sole();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('completed', $order->status);
        $this->assertTrue(LicenseKey::where('order_id', $order->id)
            ->where('user_id', $buyer->id)
            ->where('product_id', Product::where('slug', $slug)->value('id'))
            ->exists());
    }

    private function setPaymentStatusAsAdmin(Order $order, string $paymentStatus): void
    {
        $this->admin ??= User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-payment-status', $order), ['payment_status' => $paymentStatus])
            ->assertSessionHas('success');
    }

    /** @return array{0: Order, 1: User} */
    private function order(string $status, string $paymentStatus, ?User $buyer = null): array
    {
        $buyer ??= User::factory()->create();

        $order = Order::create([
            'order_number' => 'TEST-' . (++$this->orders),
            'user_id' => $buyer->id,
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_phone' => '0800000000',
            'subtotal' => 990,
            'total' => 990,
            'payment_method' => 'bank_transfer',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'price' => 990,
            'quantity' => 1,
            'subtotal' => 990,
        ]);

        return [$order, $buyer];
    }

    private function key(Order $order): LicenseKey
    {
        return LicenseKey::create([
            'product_id' => $this->product->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => 'yearly',
            'status' => 'active',
            'max_activations' => 1,
            'activations' => 0,
            'expires_at' => now()->addYear(),
        ]);
    }

    /** The order added its time to a key the buyer bought on an earlier order (a renewable product). */
    private function renewal(Order $order): LicenseRenewal
    {
        [$earlier] = $this->order('completed', 'paid', $order->user);
        $held = $this->key($earlier);

        return LicenseRenewal::create([
            'license_key_id' => $held->id,
            'order_id' => $order->id,
            'order_item_id' => $order->items()->value('id'),
            'user_id' => $order->user_id,
            'license_type' => 'monthly',
            'units' => 1,
            'days_added' => 30,
            'previous_expires_at' => $held->expires_at,
            'expires_at' => $held->expires_at->copy()->addDays(30),
        ]);
    }

    private function completionMigration(): object
    {
        return require database_path('migrations/' . self::COMPLETION_MIGRATION);
    }
}
