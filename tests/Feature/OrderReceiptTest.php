<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseService;
use App\Support\AmountInWords;
use App\Support\Letterhead;
use App\Support\OrderReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ใบเสร็จรับเงิน PDF ของคำสั่งซื้อในร้าน
 *
 *   — ลูกค้าโหลดได้จากหน้าคำสั่งซื้อและประวัติในบัญชี (เฉพาะของตัวเอง และเฉพาะที่ชำระแล้ว)
 *   — แอดมินโหลดสำเนาได้จากหน้าคำสั่งซื้อหลังบ้าน
 *   — แนบไปกับอีเมลยืนยันการชำระเงิน — ถ้าพิมพ์ไม่ได้ อีเมลต้องยังไปถึง (มี License Key อยู่ในนั้น)
 *   — คำสั่งซื้อที่ไม่มีสินค้าต้องออกคีย์ เดิมไม่ได้อีเมลเลย ตอนนี้ได้หนึ่งฉบับ (ไม่ซ้ำเมื่อยืนยันซ้ำ)
 *   — ปุ่มเดิมที่เขียนว่า "ดาวน์โหลดใบเสร็จ" แต่ได้ไฟล์ .txt ของคีย์ ต้องไม่หลอกอีก
 */
class OrderReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
    }

    public function test_the_owner_downloads_a_pdf_receipt_of_a_paid_order(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, ['payment_status' => 'paid']);

        $response = $this->actingAs($user)->get(route('orders.receipt', $order));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'RC-' . $order->order_number . '.pdf',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_somebody_elses_order_is_not_found(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'paid']);

        $this->actingAs(User::factory()->create())
            ->get(route('orders.receipt', $order))
            ->assertNotFound();
    }

    public function test_a_guest_is_sent_to_log_in(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'paid']);

        $this->get(route('orders.receipt', $order))->assertRedirect(route('login'));
    }

    public function test_an_unpaid_order_has_no_receipt(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, ['payment_status' => 'pending']);

        $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->get(route('orders.receipt', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');
    }

    public function test_an_admin_downloads_the_same_receipt(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'paid']);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.orders.receipt', $order));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_the_receipt_prints_the_figures_stored_on_the_order(): void
    {
        $order = $this->order(User::factory()->create(), [
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'tax' => 70,
            'discount' => 100,
            'coupon_code' => 'SAVE100',
            'total' => 970,
            'payment_display_amount' => 970.37,
            'payment_method' => 'promptpay',
            'paid_at' => '2026-09-28 17:30:00', // UTC → 29/09/2026 00:30 in Bangkok
        ]);

        $doc = OrderReceipt::data($order);

        $this->assertSame('RC-' . $order->order_number, $doc['number']);
        $this->assertSame('29/09/2026', $doc['issued']);
        $this->assertSame('29/09/2026 00:30', $doc['paid_at']);
        $this->assertSame(970.0, $doc['total']);
        $this->assertSame(970.37, $doc['paid_amount']);
        $this->assertSame('ภาษีมูลค่าเพิ่ม 7%', $doc['vat_label']);
        $this->assertSame('พร้อมเพย์', $doc['payment_method']);
        $this->assertSame('PromptPay', $doc['payment_method_en']);
        $this->assertSame('VAT 7%', $doc['vat_label_en']);
        $this->assertSame('เก้าร้อยเจ็ดสิบบาทถ้วน', $doc['total_words']);
        $this->assertSame('Nine Hundred Seventy Baht Only', $doc['total_words_en']);
        $this->assertSame('Receipt Test App', $doc['items'][0]['name']);

        $html = view('receipt.pdf', ['doc' => $doc, 'companyInfo' => Letterhead::info()])->render();
        $this->assertStringContainsString('ใบเสร็จรับเงิน', $html);
        $this->assertStringContainsString('RECEIPT', $html);
        $this->assertStringContainsString('Total received', $html);
        $this->assertStringContainsString('Nine Hundred Seventy Baht Only', $html);
        $this->assertStringContainsString('970.00', $html);
        $this->assertStringContainsString('SAVE100', $html);
    }

    public function test_the_payment_email_carries_the_receipt(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'paid']);

        $mail = new PaymentConfirmedMail($order);
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame('RC-' . $order->order_number . '.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);
        $bytes = $attachments[0]->attachWith(fn () => null, fn ($data) => $data());
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertStringContainsString('ใบเสร็จรับเงิน (PDF) แนบมา', $mail->render());
    }

    public function test_an_unpaid_or_unsaved_order_gets_no_attachment(): void
    {
        $this->assertSame([], (new PaymentConfirmedMail(
            $this->order(User::factory()->create(), ['payment_status' => 'pending'])
        ))->attachments());

        // The admin's e-mail preview renders a mock order that was never saved.
        $this->assertSame([], (new PaymentConfirmedMail(new Order(['payment_status' => 'paid'])))->attachments());
    }

    public function test_an_order_with_nothing_to_license_is_mailed_its_receipt_once(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'paid'], requiresLicense: false);

        $licenses = app(LicenseService::class);
        $licenses->generateLicensesForOrder($order);
        // The same payment confirmed a second time (webhook + poll, SMS + admin) stays quiet.
        $licenses->generateLicensesForOrder(Order::find($order->id));

        Mail::assertSent(PaymentConfirmedMail::class, 1);
        $this->assertNotEmpty(Order::find($order->id)->metadata['receipt_mailed_at'] ?? null);
    }

    public function test_an_unpaid_order_with_nothing_to_license_is_not_mailed(): void
    {
        $order = $this->order(User::factory()->create(), ['payment_status' => 'pending'], requiresLicense: false);

        app(LicenseService::class)->generateLicensesForOrder($order);

        Mail::assertNotSent(PaymentConfirmedMail::class);
    }

    public function test_the_order_page_offers_the_receipt_and_names_the_key_file_for_what_it_is(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, ['payment_status' => 'paid', 'status' => 'completed']);

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(route('orders.receipt', $order))
            ->assertSee('ดาวน์โหลดใบเสร็จรับเงิน (PDF)')
            ->assertSee('ดาวน์โหลด License Keys');

        $this->actingAs($user)->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee(route('orders.receipt', $order));

        $this->actingAs($user)->get(route('customer.orders'))
            ->assertOk()
            ->assertSee(route('orders.receipt', $order));
    }

    public function test_an_unpaid_order_page_has_no_receipt_link(): void
    {
        $user = User::factory()->create();
        $order = $this->order($user, ['payment_status' => 'pending']);

        $this->actingAs($user)->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertDontSee(route('orders.receipt', $order));
    }

    public function test_amounts_read_out_in_english(): void
    {
        $this->assertSame('Zero Baht Only', AmountInWords::baht(0));
        $this->assertSame('One Thousand Seventy Baht Only', AmountInWords::baht(1070));
        $this->assertSame('Twenty-One Baht and Fifty Satang', AmountInWords::baht(21.5));
        $this->assertSame('One Million Two Hundred Thousand Five Baht Only', AmountInWords::baht(1200005));
        $this->assertSame('One Hundred Baht Only', AmountInWords::baht(99.999));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(User $user, array $attributes = [], bool $requiresLicense = true): Order
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Receipt Test App',
            'slug' => 'receipt-test-app-' . Str::lower(Str::random(6)),
            'description' => 'x',
            'price' => 1000,
            'is_active' => true,
            'requires_license' => $requiresLicense,
        ]);

        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'XM20260928-' . Str::upper(Str::random(4)),
            'customer_name' => 'สมชาย ใจดี',
            'customer_email' => 'somchai@example.com',
            'customer_phone' => '0812345678',
            'subtotal' => 1000,
            'tax' => 70,
            'discount' => 0,
            'total' => 1070,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'status' => 'processing',
        ], $attributes));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 1000,
            'quantity' => 1,
            'subtotal' => 1000,
        ]);

        return $order->fresh();
    }
}
