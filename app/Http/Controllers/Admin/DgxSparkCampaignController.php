<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\DgxSparkOrderService;
use App\Support\DgxSparkCampaign;
use Illuminate\Http\Request;

/**
 * /admin/campaigns/dgx-spark — the three numbers that set the bundle price (JIB price, the date
 * it was checked, the markup), the on/off switch, the live count of the 20 sets, and the hardware
 * status of each order. Payments are approved where every order's are: the order page or the
 * Telegram card — approving issues both lifetime licenses.
 */
class DgxSparkCampaignController extends Controller
{
    public function __construct(private DgxSparkOrderService $orders) {}

    public function index()
    {
        DgxSparkCampaign::expireStaleHolds();

        $product = DgxSparkCampaign::product();

        $orders = Order::query()
            ->whereIn('id', OrderItem::select('order_id')->where('product_id', $product?->id ?? 0))
            ->with(['items.product', 'user'])
            ->latest('id')
            ->paginate(50);

        return view('admin.campaigns.dgx-spark', [
            'availability' => DgxSparkCampaign::availability(),
            'problems' => DgxSparkCampaign::problems(),
            'enabled' => DgxSparkCampaign::enabled(),
            'referencePrice' => DgxSparkCampaign::referencePrice(),
            'referenceDate' => DgxSparkCampaign::referenceCheckedAt(),
            'markup' => DgxSparkCampaign::markup(),
            'price' => DgxSparkCampaign::price(),
            'orders' => $orders,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'reference_price' => ['required', 'integer', 'min:1000', 'max:9000000'],
            'reference_checked_at' => ['required', 'date', 'before_or_equal:today'],
            'markup' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [
            'reference_price.required' => 'กรุณากรอกราคา JIB',
            'reference_price.min' => 'ราคา JIB ต่ำผิดปกติ — ตรวจสอบอีกครั้ง',
            'reference_checked_at.required' => 'กรุณาระบุวันที่ตรวจราคา',
            'reference_checked_at.before_or_equal' => 'วันที่ตรวจราคาต้องไม่เกินวันนี้',
            'markup.required' => 'กรุณากรอกส่วนต่าง',
            'markup.min' => 'ส่วนต่างติดลบไม่ได้ — ขายต่ำกว่าราคาซื้อจะขาดทุน',
        ]);

        Setting::setValue(DgxSparkCampaign::SETTING_ENABLED, $request->boolean('enabled') ? '1' : '0', 'boolean', 'campaign', 'แคมเปญ DGX Spark เปิดรับคำสั่งซื้อ');
        Setting::setValue(DgxSparkCampaign::SETTING_REFERENCE_PRICE, (int) $validated['reference_price'], 'integer', 'campaign', 'ราคาอ้างอิง JIB (รวม VAT)');
        Setting::setValue(DgxSparkCampaign::SETTING_REFERENCE_DATE, date('Y-m-d', strtotime($validated['reference_checked_at'])), 'string', 'campaign', 'วันที่ตรวจราคา JIB');
        Setting::setValue(DgxSparkCampaign::SETTING_MARKUP, (int) $validated['markup'], 'integer', 'campaign', 'ส่วนต่างที่บวกจากราคา JIB');

        return redirect()->route('admin.campaigns.dgx-spark')
            ->with('success', 'บันทึกแล้ว — ราคาชุดตอนนี้ ' . DgxSparkCampaign::baht(DgxSparkCampaign::price())
                . ' (คำสั่งซื้อที่สร้างไปแล้วคงราคาเดิม)');
    }

    public function fulfillment(Request $request, Order $order)
    {
        abort_unless(DgxSparkCampaign::isOrder($order), 404);

        $validated = $request->validate([
            'status' => ['required', 'in:paid,ordered,shipped,delivered'],
            'tracking' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // The owner's own rule: the unit is bought only after the customer's money has cleared.
        if ($order->payment_status !== 'paid' || $order->status === 'cancelled') {
            return back()->with('error', "คำสั่งซื้อ #{$order->order_number} ยังไม่ได้ยืนยันการชำระเงิน — อนุมัติการชำระเงินก่อน แล้วค่อยสั่งเครื่อง");
        }

        $this->orders->updateFulfillment($order, $validated['status'], $validated['tracking'] ?? null, $validated['note'] ?? null, $request->user());

        return back()->with('success', "อัปเดตสถานะจัดส่ง #{$order->order_number}: " . DgxSparkCampaign::FULFILLMENT[$validated['status']]);
    }
}
