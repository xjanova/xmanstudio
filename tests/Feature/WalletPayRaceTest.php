<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\KycVerification;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletWithdrawalService;
use App\Support\AdminAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Wallet::pay() takes money with one conditional UPDATE, never from a balance read earlier.
 *
 * Found in round-2 review (2026-09-26): the shop checkouts read the wallet, built the order, then
 * paid from that stale copy with an unconditional decrement. A withdrawal request that held the same
 * money in between left the balance negative — the buyer kept the goods and the cash-out waited for
 * an admin. The cart checkout even marked the order paid and issued licences when pay() said no.
 */
class WalletPayRaceTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::preventStrayRequests();
        AdminAlerts::$immediate = false;
        $this->withoutVite();

        $this->buyer = User::factory()->create();
        Wallet::getOrCreateForUser($this->buyer->id)->update(['balance' => '1000.00', 'total_deposited' => '1000.00']);
    }

    private function balance(): string
    {
        return (string) Wallet::where('user_id', $this->buyer->id)->value('balance');
    }

    /** What a concurrent request does between the checkout's balance check and its pay() */
    private function drainWalletWhenTheOrderIsCreated(): void
    {
        Order::created(function () {
            Wallet::where('user_id', $this->buyer->id)->update(['balance' => '0.00']);
        });
    }

    public function test_a_stale_wallet_cannot_spend_money_a_withdrawal_request_already_holds(): void
    {
        KycVerification::create([
            'user_id' => $this->buyer->id,
            'status' => KycVerification::STATUS_APPROVED,
            'bank_code' => 'KBANK',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'ผู้ซื้อ ทดสอบ',
        ]);
        $this->buyer->forceFill(['kyc_status' => KycVerification::STATUS_APPROVED])->save();

        // the checkout loaded the wallet and saw ฿1,000 …
        $stale = Wallet::where('user_id', $this->buyer->id)->first();
        $this->assertTrue($stale->hasSufficientBalance(1000));

        // … then a withdrawal request held all of it
        app(WalletWithdrawalService::class)->request($this->buyer->fresh(), 100000, (string) Str::uuid());
        $this->assertSame('0.00', $this->balance());

        $this->assertNull($stale->pay(1000, 'ซื้อของ'));

        $this->assertSame('0.00', $this->balance());
        $this->assertSame('0.00', (string) Wallet::where('user_id', $this->buyer->id)->value('total_spent'));
        $this->assertSame(0, WalletTransaction::where('type', WalletTransaction::TYPE_PAYMENT)->count());
    }

    public function test_a_payment_records_the_balance_it_really_moved_and_updates_the_model(): void
    {
        $wallet = Wallet::where('user_id', $this->buyer->id)->first();

        $txn = $wallet->pay(120.5, 'ซื้อของ', Order::class, 42, ['k' => 'v']);

        $this->assertNotNull($txn);
        $this->assertSame('-120.50', $txn->amount);
        $this->assertSame('1000.00', $txn->balance_before);
        $this->assertSame('879.50', $txn->balance_after);
        $this->assertSame(WalletTransaction::TYPE_PAYMENT, $txn->type);
        $this->assertSame(42, (int) $txn->reference_id);
        $this->assertSame('879.50', $wallet->balance);
        $this->assertSame('120.50', $wallet->total_spent);
        $this->assertSame('879.50', $this->balance());

        // float noise from a computed total is not a satang off
        $wallet->pay(0.1 + 0.2, 'เศษ');
        $this->assertSame('879.20', $this->balance());
    }

    public function test_an_inactive_wallet_or_a_negative_amount_pays_nothing(): void
    {
        $wallet = Wallet::where('user_id', $this->buyer->id)->first();

        $this->assertNull($wallet->pay(-50, 'คืนเงินแบบแอบ ๆ'));
        $this->assertSame('1000.00', $this->balance());

        $wallet->update(['is_active' => false]);
        $this->assertNull($wallet->pay(10, 'ซื้อของ'));
        $this->assertNull($wallet->pay(0, 'ของฟรี'));
        $this->assertSame('1000.00', $this->balance());

        $wallet->update(['is_active' => true]);
        $free = $wallet->pay(0, 'ของฟรี');
        $this->assertNotNull($free);
        $this->assertSame('1000.00', $this->balance());
    }

    public function test_a_cart_checkout_whose_balance_went_away_is_neither_paid_nor_licensed(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Some Desktop App',
            'slug' => 'some-desktop-app',
            'description' => 'x',
            'price' => 500,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
        $cart = Cart::create(['user_id' => $this->buyer->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 500]);

        $this->drainWalletWhenTheOrderIsCreated();

        $this->actingAs($this->buyer)->post('/orders', [
            'payment_method' => 'wallet',
            'customer_name' => $this->buyer->name,
            'customer_email' => $this->buyer->email,
        ])->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'ไม่เพียงพอ') && str_contains($m, 'ยังไม่ได้ตัดเงิน'));

        $this->assertSame(0, Order::count());
        $this->assertSame(0, LicenseKey::where('user_id', $this->buyer->id)->count());
        $this->assertSame(1, $cart->items()->count(), 'the cart is kept for another try');
        $this->assertSame(0, WalletTransaction::where('type', WalletTransaction::TYPE_PAYMENT)->count());
        // The stand-in drain ran inside the checkout's own transaction, so the rollback undid it too.
        // In production it is another request's committed hold; either way nothing was taken here.
        $this->assertSame('1000.00', $this->balance());
        $this->assertSame('0.00', (string) Wallet::where('user_id', $this->buyer->id)->value('total_spent'));
    }

    public function test_a_tping_wallet_checkout_whose_balance_went_away_never_goes_negative(): void
    {
        $this->drainWalletWhenTheOrderIsCreated();

        $this->actingAs($this->buyer)->post(route('tping.process', 'monthly'), [
            'customer_name' => $this->buyer->name,
            'customer_email' => $this->buyer->email,
            'customer_phone' => '0812345678',
            'payment_method' => 'wallet',
        ])->assertRedirect()->assertSessionHas('error', 'ชำระเงินไม่สำเร็จ กรุณาลองใหม่');

        $this->assertSame('0.00', $this->balance());
        $order = Order::where('user_id', $this->buyer->id)->sole();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame(0, LicenseKey::where('order_id', $order->id)->count());
    }
}
