<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\RentalPackage;
use App\Models\RentalPayment;
use App\Models\User;
use App\Models\UserRental;
use App\Support\Alerts\BusinessAlerts;
use App\Support\Auth\Totp;
use App\Support\Auth\TwoFactor;
use App\Support\PaymentSlips;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Payment slips are a customer's bank details. They used to be written to the public disk, which
 * the web server hands out at /storage/payment-slips/… to anyone holding the address; now they are
 * kept on the private disk and opened only by the customer who sent the slip or by an admin.
 */
class PaymentSlipPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(PaymentSlips::DISK);
        Storage::fake(PaymentSlips::LEGACY_DISK);
        Http::preventStrayRequests();
        Mail::fake();
        Cache::flush();
        $this->withoutVite();
    }

    // ── new slips go to the private disk ─────────────────────────────

    public function test_a_cart_slip_is_kept_off_the_web_root(): void
    {
        $buyer = $this->customer();
        $order = $this->order($buyer);

        $this->actingAs($buyer)
            ->post(route('orders.confirm-payment', $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('verifying', $order->payment_status);
        $this->assertStringStartsWith('payment-slips/', $order->payment_slip);
        Storage::disk('local')->assertExists($order->payment_slip);
        $this->assertSame([], Storage::disk('public')->allFiles(), 'nothing may land under the web root');
        $this->assertPublicAddressServesNothing($order->payment_slip);

        $this->get(route('orders.show', $order))->assertOk()
            ->assertSee(route('payment-slips.order', $order), false)
            ->assertDontSee('storage/payment-slips', false);
    }

    public function test_a_slip_goes_only_on_your_own_order(): void
    {
        $order = $this->order($this->customer());

        $this->actingAs($this->customer())
            ->post(route('orders.confirm-payment', $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_rental_slip_is_kept_off_the_web_root(): void
    {
        [$customer, $payment] = $this->rentalPayment();

        $this->actingAs($customer)
            ->post(route('rental.upload-slip', $payment->uuid), ['slip' => UploadedFile::fake()->image('slip.png')])
            ->assertRedirect(route('rental.payment.status', $payment->uuid));

        $payment->refresh();
        $this->assertSame(RentalPayment::STATUS_PROCESSING, $payment->status);
        $this->assertStringStartsWith('payment-slips/', $payment->transfer_slip_url);
        Storage::disk('local')->assertExists($payment->transfer_slip_url);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertPublicAddressServesNothing($payment->transfer_slip_url);

        $this->actingAs($this->admin())->get(route('admin.rentals.payments'))->assertOk()
            ->assertSee(route('payment-slips.rental', $payment), false)
            ->assertDontSee('storage/payment-slips', false);
    }

    /** @return array<string, array{0: string, 1: string, 2: bool}> route, folder, metadata saved as JSON text */
    public static function productCheckouts(): array
    {
        return [
            'AI credits' => ['xdreamer.checkout.confirm', 'ai-credits', true],
            'AutoTradeX' => ['autotradex.confirm-payment', 'autotradex', true],
            'LocalVPN' => ['localvpn.confirm-payment', 'localvpn', false],
            'SmsChecker' => ['smschecker.confirm-payment', 'smschecker', true],
            'Tping' => ['tping.confirm-payment', 'tping', true],
        ];
    }

    /** @dataProvider productCheckouts */
    public function test_a_product_checkout_slip_is_kept_off_the_web_root(string $route, string $folder, bool $jsonText): void
    {
        $buyer = $this->customer();
        // Saved the way each checkout saves it: most json_encode() into the array-cast column
        $metadata = ['plan' => 'monthly', 'source' => 'xdreamer'];
        $order = $this->order($buyer, ['metadata' => $jsonText ? json_encode($metadata) : $metadata]);

        $this->actingAs($buyer)
            ->post(route($route, $order), ['payment_slip' => UploadedFile::fake()->image('slip.jpg')])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $slip = PaymentSlips::forOrder($order->fresh());
        $this->assertNotNull($slip, 'the order carries its slip');
        $this->assertMatchesRegularExpression('~^payment-slips/' . $folder . '/[A-Za-z0-9]{40}\.webp$~', $slip);
        Storage::disk('local')->assertExists($slip);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($this->admin())->get(route('payment-slips.order', $order))
            ->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    // ── who may open one ─────────────────────────────────────────────

    public function test_only_the_buyer_and_an_admin_open_an_order_slip(): void
    {
        $buyer = $this->customer();
        $order = $this->order($buyer, ['payment_status' => 'verifying', 'payment_slip' => $this->privateSlip()]);
        $url = route('payment-slips.order', $order);

        $this->get($url)->assertNotFound();
        $this->actingAs($this->customer())->get($url)->assertNotFound();

        $mine = $this->actingAs($buyer)->get($url);
        $this->assertServedPrivately($mine, 'image/png');
        $this->assertSame(Storage::disk('local')->get('payment-slips/slip.png'), $mine->streamedContent());

        $this->assertServedPrivately($this->actingAs($this->admin())->get($url), 'image/png');
    }

    public function test_only_the_customer_and_an_admin_open_a_rental_slip(): void
    {
        [$customer, $payment] = $this->rentalPayment(['transfer_slip_url' => $this->privateSlip()]);
        $url = route('payment-slips.rental', $payment);

        $this->get($url)->assertNotFound();
        $this->actingAs($this->customer())->get($url)->assertNotFound();
        $this->assertServedPrivately($this->actingAs($customer)->get($url), 'image/png');
        $this->assertServedPrivately($this->actingAs($this->admin())->get($url), 'image/png');
    }

    public function test_an_admin_opens_slips_only_after_the_second_sign_in_step(): void
    {
        config(['security.two_factor.required_for_admins' => true]);
        $order = $this->order($this->customer(), ['payment_slip' => $this->privateSlip()]);
        $url = route('payment-slips.order', $order);

        // Not enrolled while the panel requires it: the panel itself would send this admin to set it up
        $this->actingAs($this->admin())->get($url)->assertNotFound();

        $admin = $this->admin();
        $secret = Totp::generateSecret();
        [, $hashes] = TwoFactor::newRecoveryCodes();
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $hashes, 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($admin)->get($url)->assertNotFound();
        $this->post(route('two-factor.verify'), ['code' => Totp::at($secret, Totp::stepAt(time()))]);
        $this->get($url)->assertOk();
    }

    public function test_an_order_without_a_slip_answers_like_a_stranger(): void
    {
        $buyer = $this->customer();
        $order = $this->order($buyer);

        $this->actingAs($buyer)->get(route('payment-slips.order', $order))->assertNotFound();
        $this->actingAs($buyer)->get('/payment-slips/orders/' . ($order->id + 100))->assertNotFound();
    }

    // ── what a row may point at ──────────────────────────────────────

    /** @return array<string, array{0: ?string, 1: ?string}> */
    public static function storedValues(): array
    {
        return [
            'the path' => ['payment-slips/a.jpg', 'payment-slips/a.jpg'],
            'a product folder' => ['payment-slips/autotradex/b.webp', 'payment-slips/autotradex/b.webp'],
            'the public address' => ['/storage/payment-slips/a.jpg', 'payment-slips/a.jpg'],
            'without the slash' => ['storage/payment-slips/a.jpg', 'payment-slips/a.jpg'],
            'a full URL' => ['https://xman4289.com/storage/payment-slips/a.jpg', 'payment-slips/a.jpg'],
            'padded' => ['  payment-slips/a.jpg ', 'payment-slips/a.jpg'],
            'nothing' => [null, null],
            'empty' => ['', null],
            'the folder itself' => ['payment-slips/', null],
            'climbing out' => ['payment-slips/../kyc/front.png', null],
            'a dot segment' => ['payment-slips/./a.jpg', null],
            'a double slash' => ['payment-slips//a.jpg', null],
            'a hidden file' => ['payment-slips/.htaccess', null],
            'another private folder' => ['kyc/front.png', null],
            'the game slips' => ['game-slips/a.png', null],
            'a backslash' => ['payment-slips\\a.jpg', null],
            'a query string' => ['payment-slips/a.jpg?v=1', null],
            'a server path' => ['/home/admin/storage/app/public/payment-slips/a.jpg', null],
        ];
    }

    /** @dataProvider storedValues */
    public function test_a_stored_value_reads_as_a_path_inside_the_slips_folder_or_not_at_all(?string $stored, ?string $expected): void
    {
        $this->assertSame($expected, PaymentSlips::normalize($stored));
    }

    public function test_a_row_cannot_point_the_slip_route_at_another_private_file(): void
    {
        $buyer = $this->customer();
        Storage::disk('local')->put('kyc/front.png', $this->png());
        $this->privateSlip();

        foreach (['kyc/front.png', 'payment-slips/../kyc/front.png', '/storage/../kyc/front.png'] as $stored) {
            $order = $this->order($buyer, ['payment_slip' => $stored]);
            $this->actingAs($buyer)->get(route('payment-slips.order', $order))->assertNotFound();
        }
    }

    public function test_a_slip_the_move_has_not_reached_yet_still_opens(): void
    {
        $buyer = $this->customer();
        Storage::disk('public')->put('payment-slips/legacy.png', $this->png());
        $order = $this->order($buyer, ['payment_slip' => 'https://xman4289.com/storage/payment-slips/legacy.png']);

        $this->assertServedPrivately($this->actingAs($buyer)->get(route('payment-slips.order', $order)), 'image/png');
    }

    public function test_a_slip_that_is_not_a_raster_image_is_only_offered_as_a_download(): void
    {
        // An SVG accepted before uploads became raster-only
        $buyer = $this->customer();
        Storage::disk('public')->put('payment-slips/old.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
        $order = $this->order($buyer, ['payment_slip' => 'payment-slips/old.svg']);

        $response = $this->actingAs($buyer)->get(route('payment-slips.order', $order))->assertOk();

        $this->assertServedPrivately($response, 'application/octet-stream');
        $this->assertStringStartsWith('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    // ── the admin's Telegram card ────────────────────────────────────

    public function test_the_telegram_card_attaches_the_slip_from_the_private_disk(): void
    {
        $path = $this->privateSlip();
        $order = $this->order($this->customer(), ['payment_status' => 'verifying', 'payment_slip' => $path]);
        [, $payment] = $this->rentalPayment(['status' => RentalPayment::STATUS_PROCESSING, 'transfer_slip_url' => $path]);
        $real = realpath(Storage::disk('local')->path($path));

        $this->assertSame($real, BusinessAlerts::orderCard($order, null, $path)->photo);
        $this->assertSame($real, BusinessAlerts::rentalCard($payment)->photo);

        Storage::disk('local')->put('payment-slips/old.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->assertNull(PaymentSlips::localPath('payment-slips/old.svg'), 'only a raster image goes to Telegram');
    }

    // ── moving the slips already on the public disk ──────────────────

    public function test_the_move_takes_every_public_slip_and_nothing_else(): void
    {
        Storage::disk('public')->put('payment-slips/a.jpg', 'slip a');
        Storage::disk('public')->put('payment-slips/autotradex/b.png', 'slip b');
        Storage::disk('public')->put('avatars/someone.png', 'an avatar');
        $order = $this->order($this->customer(), ['payment_slip' => 'payment-slips/a.jpg']);
        Log::spy();

        $this->artisan('payment-slips:privatize')
            ->expectsOutputToContain('moved payment-slips/autotradex/b.png')
            ->expectsOutputToContain('Moved 2 slip(s) to the private disk and rewrote 0 row(s); 0 problem(s).')
            ->assertSuccessful();

        $this->assertSame('slip a', Storage::disk('local')->get('payment-slips/a.jpg'));
        $this->assertSame('slip b', Storage::disk('local')->get('payment-slips/autotradex/b.png'));
        $this->assertSame([], Storage::disk('public')->allFiles('payment-slips'));
        $this->assertSame(['avatars/someone.png'], Storage::disk('public')->allFiles(), 'other public files stay');
        $this->assertSame(['payment-slips/a.jpg', 'payment-slips/autotradex/b.png'], Storage::disk('local')->allFiles(), 'no temporary copies left');
        $this->assertSame('payment-slips/a.jpg', $order->fresh()->payment_slip, 'the row already holds the right path');

        $this->artisan('payment-slips:privatize')
            ->expectsOutputToContain('Moved 0 slip(s) to the private disk and rewrote 0 row(s); 0 problem(s).')
            ->assertSuccessful();

        // Logged once, with the old addresses a CDN may still hold; a run with nothing to do stays quiet
        Log::shouldHaveReceived('log')->once()->withArgs(fn (string $level, string $message, array $context) => $level === 'info'
            && $context['moved'] === 2 && $context['paths'] === ['payment-slips/a.jpg', 'payment-slips/autotradex/b.png']);
    }

    public function test_the_move_finishes_an_interrupted_copy_and_never_settles_a_clash(): void
    {
        // An earlier run copied this one and stopped before deleting the public file
        Storage::disk('public')->put('payment-slips/same.jpg', 'same bytes');
        Storage::disk('local')->put('payment-slips/same.jpg', 'same bytes');
        // A different file under the same name: nobody can tell which is right
        Storage::disk('public')->put('payment-slips/clash.jpg', 'public bytes');
        Storage::disk('local')->put('payment-slips/clash.jpg', 'private bytes');

        $this->artisan('payment-slips:privatize')
            ->expectsOutputToContain('payment-slips/clash.jpg: the private disk holds a different file under this name; both left alone.')
            ->assertFailed();

        Storage::disk('public')->assertMissing('payment-slips/same.jpg');
        $this->assertSame('same bytes', Storage::disk('local')->get('payment-slips/same.jpg'));
        $this->assertSame('public bytes', Storage::disk('public')->get('payment-slips/clash.jpg'));
        $this->assertSame('private bytes', Storage::disk('local')->get('payment-slips/clash.jpg'));
    }

    public function test_the_move_rewrites_rows_that_saved_the_public_address(): void
    {
        $buyer = $this->customer();
        $cart = $this->order($buyer, ['payment_slip' => 'https://xman4289.com/storage/payment-slips/a.jpg']);
        $product = $this->order($buyer, ['metadata' => json_encode(['plan' => 'pro', 'payment_slip' => '/storage/payment-slips/autotradex/b.webp'])]);
        $vpn = $this->order($buyer, ['metadata' => ['plan' => 'monthly', 'payment_slip' => 'storage/payment-slips/localvpn/c.webp']]);
        [, $rental] = $this->rentalPayment(['transfer_slip_url' => '/storage/payment-slips/d.png']);

        $this->artisan('payment-slips:privatize')
            ->expectsOutputToContain('rewrote 4 row(s); 0 problem(s).')
            ->assertSuccessful();

        $this->assertSame('payment-slips/a.jpg', $cart->fresh()->payment_slip);
        $this->assertSame('payment-slips/autotradex/b.webp', PaymentSlips::forOrder($product->fresh()));
        $this->assertSame('payment-slips/localvpn/c.webp', PaymentSlips::forOrder($vpn->fresh()));
        $this->assertSame('payment-slips/d.png', $rental->fresh()->transfer_slip_url);

        // Written back the way each checkout reads it (MySQL may reorder the keys of a JSON object)
        $this->assertEquals(['plan' => 'pro', 'payment_slip' => 'payment-slips/autotradex/b.webp'], json_decode($product->fresh()->metadata, true));
        $this->assertEquals(['plan' => 'monthly', 'payment_slip' => 'payment-slips/localvpn/c.webp'], $vpn->fresh()->metadata);
    }

    public function test_a_row_that_holds_no_slip_path_is_reported_and_left_alone(): void
    {
        $order = $this->order($this->customer(), ['payment_slip' => 'C:\\Users\\someone\\slip.jpg']);

        $this->artisan('payment-slips:privatize')
            ->expectsOutputToContain("orders #{$order->id}: payment_slip is not a slip path; left as it is.")
            ->assertFailed();

        $this->assertSame('C:\\Users\\someone\\slip.jpg', $order->fresh()->payment_slip);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        Storage::disk('public')->put('payment-slips/a.jpg', 'slip a');
        $order = $this->order($this->customer(), ['payment_slip' => '/storage/payment-slips/a.jpg']);

        $this->artisan('payment-slips:privatize', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run: would move 1 slip(s) to the private disk and rewrite 1 row(s); 0 problem(s).')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('payment-slips/a.jpg');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame('/storage/payment-slips/a.jpg', $order->fresh()->payment_slip);
    }

    public function test_the_deploy_migration_moves_the_slips(): void
    {
        Storage::disk('public')->put('payment-slips/a.jpg', 'slip a');

        (require database_path('migrations/2026_10_10_120000_move_payment_slips_to_the_private_disk.php'))->up();

        $this->assertSame('slip a', Storage::disk('local')->get('payment-slips/a.jpg'));
        Storage::disk('public')->assertMissing('payment-slips/a.jpg');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function order(User $buyer, array $overrides = []): Order
    {
        return Order::create($overrides + [
            'user_id' => $buyer->id,
            'order_number' => 'XM20261010-' . random_int(1000, 9999),
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'somchai@example.com',
            'customer_phone' => '0812345678',
            'subtotal' => 990,
            'total' => 990,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /** @return array{0: User, 1: RentalPayment} */
    private function rentalPayment(array $overrides = []): array
    {
        $customer = $this->customer();
        $package = RentalPackage::create([
            'name' => 'Pro Monthly', 'name_th' => 'โปรรายเดือน', 'price' => 990,
            'duration_type' => 'monthly', 'duration_value' => 1, 'is_active' => true,
        ]);
        $rental = UserRental::create([
            'user_id' => $customer->id, 'rental_package_id' => $package->id,
            'status' => UserRental::STATUS_PENDING, 'amount_paid' => 990, 'payment_method' => 'bank_transfer',
        ]);
        $payment = RentalPayment::create($overrides + [
            'user_id' => $customer->id, 'user_rental_id' => $rental->id,
            'amount' => 990, 'payment_method' => RentalPayment::METHOD_BANK_TRANSFER,
            'status' => RentalPayment::STATUS_PENDING,
        ]);

        return [$customer, $payment];
    }

    private function privateSlip(string $path = 'payment-slips/slip.png'): string
    {
        Storage::disk('local')->put($path, $this->png());

        return $path;
    }

    private function png(): string
    {
        return UploadedFile::fake()->image('slip.png', 24, 24)->getContent();
    }

    private function assertServedPrivately(TestResponse $response, string $type): void
    {
        $response->assertOk()
            ->assertHeader('Content-Type', $type)
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cache = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cache);
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('sandbox', (string) $response->headers->get('Content-Security-Policy'));
    }

    /** The address the web server used to hand out; Laravel's own /storage route wants a signature. */
    private function assertPublicAddressServesNothing(string $path): void
    {
        $this->assertContains($this->get('/storage/' . $path)->getStatusCode(), [403, 404]);
    }
}
