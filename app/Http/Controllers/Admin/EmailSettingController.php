<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AffiliateCommissionMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\PaymentConfirmedMail;
use App\Mail\TestMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'resend_api_key' => PaymentSetting::get('resend_api_key'),
            'mail_from_address' => PaymentSetting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => PaymentSetting::get('mail_from_name', config('app.name')),
            'mail_enabled' => PaymentSetting::get('mail_enabled', true),
            'email_site_url' => PaymentSetting::get('email_site_url', config('app.url')),
            'email_logo_url' => PaymentSetting::get('email_logo_url'),
        ];

        return view('admin.email-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_enabled' => 'boolean',
            'resend_api_key' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'email_site_url' => 'nullable|url|max:255',
            'email_logo_url' => 'nullable|url|max:500',
        ]);

        PaymentSetting::set('mail_enabled', $request->boolean('mail_enabled'), [
            'group' => 'email',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานระบบอีเมล',
        ]);

        if (! empty($validated['resend_api_key'])) {
            PaymentSetting::set('resend_api_key', $validated['resend_api_key'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'Resend API Key',
                'is_encrypted' => true,
            ]);

            // Update runtime config
            config(['services.resend.key' => $validated['resend_api_key']]);
        }

        if (! empty($validated['mail_from_address'])) {
            PaymentSetting::set('mail_from_address', $validated['mail_from_address'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'อีเมลผู้ส่ง',
            ]);
        }

        if (! empty($validated['mail_from_name'])) {
            PaymentSetting::set('mail_from_name', $validated['mail_from_name'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'ชื่อผู้ส่ง',
            ]);
        }

        if (! empty($validated['email_site_url'])) {
            PaymentSetting::set('email_site_url', rtrim($validated['email_site_url'], '/'), [
                'group' => 'email',
                'type' => 'string',
                'label' => 'URL เว็บสำหรับอีเมล',
            ]);
        }

        if (! empty($validated['email_logo_url'])) {
            PaymentSetting::set('email_logo_url', $validated['email_logo_url'], [
                'group' => 'email',
                'type' => 'string',
                'label' => 'URL โลโก้สำหรับอีเมล',
            ]);
        }

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าอีเมลเรียบร้อยแล้ว');
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        // Apply dynamic config
        $this->applyDynamicMailConfig();

        try {
            Mail::to($validated['test_email'])->send(new TestMail);

            return redirect()->back()->with('success', "ส่งอีเมลทดสอบไปที่ {$validated['test_email']} เรียบร้อยแล้ว");
        } catch (\Exception $e) {
            Log::error('Test email failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'ส่งอีเมลไม่สำเร็จ กรุณาตรวจสอบ API Key และการตั้งค่า');
        }
    }

    /**
     * Preview email templates with mock data.
     */
    public function preview(string $template)
    {
        $mockOrder = $this->buildMockOrder();

        return match ($template) {
            'test' => (new TestMail)->render(),
            'order-confirmation' => (new OrderConfirmationMail($mockOrder))->render(),
            'payment-confirmed' => (new PaymentConfirmedMail($mockOrder))->render(),
            'affiliate-paid' => (new AffiliateCommissionMail($this->buildMockCommission(), 'paid'))->render(),
            'affiliate-rejected' => (new AffiliateCommissionMail($this->buildMockCommission('rejected'), 'rejected'))->render(),
            default => abort(404, 'ไม่พบเทมเพลตอีเมล'),
        };
    }

    /**
     * Show email preview selector page.
     */
    public function previewIndex()
    {
        $templates = [
            ['slug' => 'test', 'name' => 'ทดสอบระบบอีเมล', 'description' => 'เมลทดสอบการทำงานของระบบ'],
            ['slug' => 'order-confirmation', 'name' => 'ยืนยันคำสั่งซื้อ', 'description' => 'ส่งเมื่อลูกค้าสั่งซื้อสินค้า (รอชำระเงิน)'],
            ['slug' => 'payment-confirmed', 'name' => 'ชำระเงินสำเร็จ', 'description' => 'ส่งเมื่อยืนยันการชำระเงินแล้ว พร้อม License Key'],
            ['slug' => 'affiliate-paid', 'name' => 'คอมมิชชัน Affiliate (อนุมัติ)', 'description' => 'แจ้ง Affiliate เมื่อคอมมิชชันได้รับการอนุมัติ'],
            ['slug' => 'affiliate-rejected', 'name' => 'คอมมิชชัน Affiliate (ปฏิเสธ)', 'description' => 'แจ้ง Affiliate เมื่อคอมมิชชันถูกปฏิเสธ'],
        ];

        return view('admin.email-settings.preview-index', compact('templates'));
    }

    /**
     * Build a mock Order object for email previews.
     */
    private function buildMockOrder(): Order
    {
        $order = new Order;
        $order->id = 999;
        $order->order_number = 'ORD-20260322-0001';
        $order->payment_status = 'paid';
        $order->payment_method = 'promptpay';
        $order->subtotal = '1990.00';
        $order->tax = '139.30';
        $order->total = '2129.30';
        $order->total_amount = '2129.30';
        $order->status = 'completed';
        $order->created_at = now();
        $order->updated_at = now();
        $order->paid_at = now();

        // Mock user
        $mockUser = new User;
        $mockUser->name = 'ลูกค้าทดสอบ';
        $mockUser->email = 'test@example.com';
        $order->setRelation('user', $mockUser);

        // Mock items
        $items = collect([
            $this->buildMockOrderItem('XMAN Studio Pro License', 1, 1490),
            $this->buildMockOrderItem('Metal-X Template Pack', 1, 500),
        ]);
        $order->setRelation('items', $items);

        return $order;
    }

    private function buildMockOrderItem(string $name, int $qty, float $price): OrderItem
    {
        $item = new OrderItem;
        $item->quantity = $qty;
        $item->price = $price;
        $item->total = $price * $qty;
        $item->product_name = $name;

        $product = new Product;
        $product->name = $name;
        $item->setRelation('product', $product);

        return $item;
    }

    private function buildMockCommission(string $status = 'approved'): AffiliateCommission
    {
        $commission = new AffiliateCommission;
        $commission->order_amount = '2129.30';
        $commission->commission_rate = '10';
        $commission->commission_amount = '212.93';
        $commission->status = $status;
        $commission->source_type = 'order';
        $commission->source_description = 'คำสั่งซื้อ #ORD-20260322-0001';
        $commission->admin_note = $status === 'rejected' ? 'ตรวจพบการสั่งซื้อที่ไม่ถูกต้อง' : null;
        $commission->created_at = now();

        // Mock affiliate with user and wallet
        $affiliateUser = new User;
        $affiliateUser->name = 'พันธมิตรทดสอบ';
        $affiliateUser->email = 'affiliate@example.com';

        $wallet = new Wallet;
        $wallet->balance = '5432.10';
        $affiliateUser->setRelation('wallet', $wallet);

        $affiliate = new Affiliate;
        $affiliate->setRelation('user', $affiliateUser);

        $commission->setRelation('affiliate', $affiliate);

        return $commission;
    }

    private function applyDynamicMailConfig(): void
    {
        $apiKey = PaymentSetting::get('resend_api_key');
        if ($apiKey) {
            config(['services.resend.key' => $apiKey]);
        }

        $fromAddress = PaymentSetting::get('mail_from_address');
        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }

        $fromName = PaymentSetting::get('mail_from_name');
        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }
    }
}
