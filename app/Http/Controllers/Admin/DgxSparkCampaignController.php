<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\LicenseRenewal;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\DgxSparkOrderService;
use App\Services\OrderPaymentService;
use App\Support\DgxSparkCampaign;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * /admin/campaigns/dgx-spark — the owner's desk for the campaign: every order sorted by what it
 * waits for (a transfer, a slip to check, a unit to order, a parcel to ship), the slip, the
 * approve / reject buttons, the licenses issued and the hardware status — all on one page.
 * Below the orders: the count of sets (including those sold outside the website) and the three
 * numbers that set the bundle price (JIB price, the date it was checked, the markup).
 *
 * Approving here is the same OrderPaymentService::approve the order page and the Telegram card
 * use, so it issues both lifetime licenses exactly as they do.
 */
class DgxSparkCampaignController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private DgxSparkOrderService $orders) {}

    public function index(Request $request)
    {
        DgxSparkCampaign::expireStaleHolds();

        $tab = array_key_exists((string) $request->query('tab'), DgxSparkCampaign::ADMIN_TABS)
            ? (string) $request->query('tab')
            : null;
        $search = trim((string) $request->query('q', ''));

        $all = DgxSparkCampaign::orders()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search) . '%';
                $query->where(fn ($q) => $q->where('order_number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('customer_email', 'like', $like));
            })
            ->with(['items.product', 'user'])
            ->latest('id')
            ->get();

        $stages = $all->mapWithKeys(fn (Order $order) => [$order->id => DgxSparkCampaign::adminStage($order)]);
        $counts = [];
        foreach (array_keys(DgxSparkCampaign::ADMIN_TABS) as $key) {
            $counts[$key] = match ($key) {
                'all' => $all->count(),
                'action' => $stages->filter(fn ($s) => in_array($s, DgxSparkCampaign::ACTION_STAGES, true))->count(),
                default => $stages->filter(fn ($s) => $s === $key)->count(),
            };
        }

        // No tab chosen: open on what waits for the owner, or on everything when nothing does.
        $tab ??= $counts['action'] > 0 ? 'action' : 'all';

        $listed = $all->filter(fn (Order $order) => match ($tab) {
            'all' => true,
            'action' => in_array($stages[$order->id], DgxSparkCampaign::ACTION_STAGES, true),
            default => $stages[$order->id] === $tab,
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $orders = new LengthAwarePaginator(
            $listed->forPage($page, self::PER_PAGE)->values(),
            $listed->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $ids = $orders->getCollection()->pluck('id');
        $licenses = LicenseKey::whereIn('order_id', $ids)->with('product:id,name')->get()->groupBy('order_id');
        $renewals = LicenseRenewal::whereIn('order_id', $ids)->with('licenseKey.product:id,name')->get()->groupBy('order_id');

        $availability = DgxSparkCampaign::availability();

        return view('admin.campaigns.dgx-spark', [
            'availability' => $availability,
            'offlineSold' => DgxSparkCampaign::offlineSold(),
            'offlineMax' => $this->offlineMax($availability),
            'problems' => DgxSparkCampaign::problems(),
            'enabled' => DgxSparkCampaign::enabled(),
            'referencePrice' => DgxSparkCampaign::referencePrice(),
            'referenceDate' => DgxSparkCampaign::referenceCheckedAt(),
            'markup' => DgxSparkCampaign::markup(),
            'price' => DgxSparkCampaign::price(),
            'orders' => $orders,
            'stages' => $stages,
            'counts' => $counts,
            'tab' => $tab,
            'search' => $search,
            'licenses' => $licenses,
            'renewals' => $renewals,
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

    /** Sets sold outside the website: they count as sold, so the page and the cap leave them out. */
    public function stock(Request $request)
    {
        $max = $this->offlineMax(DgxSparkCampaign::availability());

        $validated = $request->validate([
            'offline_sold' => ['required', 'integer', 'min:0', 'max:' . $max],
        ], [
            'offline_sold.required' => 'กรุณากรอกจำนวนชุดที่ขายนอกเว็บ (ไม่มีให้ใส่ 0)',
            'offline_sold.integer' => 'จำนวนชุดต้องเป็นจำนวนเต็ม',
            'offline_sold.min' => 'จำนวนชุดติดลบไม่ได้',
            'offline_sold.max' => "ขายนอกเว็บได้ไม่เกิน {$max} ชุด — ที่เหลือถูกขายหรือจองผ่านเว็บแล้ว",
        ]);

        Setting::setValue(DgxSparkCampaign::SETTING_OFFLINE_SOLD, (int) $validated['offline_sold'], 'integer', 'campaign', 'ชุด DGX Spark ที่ขายนอกเว็บ');

        $availability = DgxSparkCampaign::availability();

        return redirect()->route('admin.campaigns.dgx-spark')
            ->with('success', "บันทึกแล้ว — หน้าแคมเปญแสดง เหลือ {$availability['remaining']} จาก {$availability['cap']} ชุด");
    }

    /**
     * Approve or reject the payment from the campaign page. Approving issues both lifetime
     * licenses (OrderPaymentService::approve, the same as the order page and Telegram).
     */
    public function payment(Request $request, Order $order)
    {
        abort_unless(DgxSparkCampaign::isOrder($order), 404);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $note = $validated['note'] ?? null;
        $payments = app(OrderPaymentService::class);

        $refusal = DB::transaction(function () use ($order, $validated, $note, $payments) {
            // Same queue as a new reservation, so approving a late payer cannot take a set that a
            // customer is reserving at this moment.
            if ($product = DgxSparkCampaign::product()) {
                Product::whereKey($product->id)->lockForUpdate()->first();
            }
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $order->setRawAttributes($locked->getAttributes(), true);

            if ($order->payment_status === 'paid') {
                return "คำสั่งซื้อ #{$order->order_number} ชำระเงินแล้ว — คืนเงิน/ยกเลิกหลังชำระ ทำที่หน้าคำสั่งซื้อ";
            }
            if ($order->status === 'cancelled') {
                return "คำสั่งซื้อ #{$order->order_number} ถูกยกเลิกไปแล้ว";
            }

            if ($validated['action'] === 'reject') {
                $payments->reject($order, $note);

                return null;
            }

            // A reservation past its hold no longer holds a set: approve it only while one is free.
            if (DgxSparkCampaign::holdExpired($order) && DgxSparkCampaign::availability($order->id)['remaining'] <= 0) {
                return "อนุมัติ #{$order->order_number} ไม่ได้ — การจองหมดเวลาแล้วและชุดเต็ม หากลูกค้าโอนมาแล้วต้องคืนเงิน";
            }

            $payments->approve($order, $note);

            return null;
        });

        if ($refusal) {
            return back()->with('error', $refusal);
        }

        return back()->with('success', $validated['action'] === 'approve'
            ? "อนุมัติการชำระเงิน #{$order->order_number} แล้ว — ออก License ทั้งสองตัวให้ลูกค้าแล้ว ขั้นต่อไป: สั่งเครื่องจากผู้จัดจำหน่าย"
            : "ปฏิเสธ/ยกเลิก #{$order->order_number} แล้ว — ชุดนี้กลับมาว่างให้คนถัดไป");
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

    /** The most sets that can be marked sold outside the website: those not taken on the website. */
    private function offlineMax(array $availability): int
    {
        $online = $availability['sold'] - DgxSparkCampaign::offlineSold() + $availability['reserved'];

        return max(0, $availability['cap'] - $online);
    }
}
