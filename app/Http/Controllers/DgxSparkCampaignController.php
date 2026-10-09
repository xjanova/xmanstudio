<?php

namespace App\Http\Controllers;

use App\Services\DgxSparkOrderException;
use App\Services\DgxSparkOrderService;
use App\Support\DgxSparkCampaign;
use App\Support\LicensePlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * https://xman4289.com/dgx-spark — the DGX Spark + CluadeX + BrainX bundle. The CluadeX desktop
 * app links to this exact path, so it must not move. Ordering only from us: the JIB link on the
 * page is the price reference a customer can check, never a place to buy.
 */
class DgxSparkCampaignController extends Controller
{
    public function __construct(private DgxSparkOrderService $orders) {}

    public function show(Request $request)
    {
        // "เข้าสู่ระบบเพื่อสั่งจอง": back to the order form once signed in or registered.
        if ($request->filled('signin')) {
            $back = route('campaign.dgx-spark') . '#order';

            if (Auth::check()) {
                return redirect()->to($back);
            }

            $request->session()->put('url.intended', $back);

            return redirect()->route($request->query('signin') === 'register' ? 'register' : 'login');
        }

        // Reservations past their hold give their sets back before anything is counted.
        DgxSparkCampaign::expireStaleHolds();

        $availability = DgxSparkCampaign::availability();
        $user = $request->user();

        return view('campaign.dgx-spark', [
            'availability' => $availability,
            'closedReason' => DgxSparkCampaign::closedReason($availability),
            'price' => DgxSparkCampaign::price(),
            'totals' => DgxSparkCampaign::totals(DgxSparkCampaign::price()),
            'referencePrice' => DgxSparkCampaign::referencePrice(),
            'referenceDate' => DgxSparkCampaign::referenceCheckedAt(),
            'markup' => DgxSparkCampaign::markup(),
            'paymentMethods' => DgxSparkCampaign::paymentMethods(),
            'openOrder' => $user ? $this->orders->openOrderFor($user) : null,
            'user' => $user,
            'media' => [
                'hero' => DgxSparkCampaign::mediaUrl('hero'),
                'square' => DgxSparkCampaign::mediaUrl('square'),
                'story' => DgxSparkCampaign::mediaUrl('story'),
                'video' => DgxSparkCampaign::mediaUrl('video'),
                'poster' => DgxSparkCampaign::mediaUrl('poster'),
            ],
            'cluadexLifetimePrice' => LicensePlans::price('cluadex-ai-coding-assistant', 'lifetime'),
            'brainxMonthlyPrice' => LicensePlans::price('brainx', 'monthly'),
        ]);
    }

    public function order(Request $request)
    {
        $methods = array_column(DgxSparkCampaign::paymentMethods(), 'id');

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s\-]{7,16}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province' => ['required', 'string', 'max:100'],
            'shipping_postcode' => ['required', 'digits:5'],
            'payment_method' => ['required', 'in:' . implode(',', $methods ?: ['none'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_tax_id' => ['nullable', 'required_with:company_name', 'regex:/^[0-9\-\s]{13,17}$/'],
            'company_branch' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'expected_price' => ['required', 'integer'],
            'accept_terms' => ['accepted'],
        ], [
            'customer_name.required' => 'กรุณากรอกชื่อ-นามสกุลผู้สั่งซื้อ',
            'customer_email.required' => 'กรุณากรอกอีเมล',
            'customer_email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'customer_phone.required' => 'กรุณากรอกเบอร์โทรศัพท์สำหรับติดต่อและจัดส่ง',
            'customer_phone.regex' => 'เบอร์โทรศัพท์ไม่ถูกต้อง',
            'shipping_address.required' => 'กรุณากรอกที่อยู่จัดส่ง',
            'shipping_province.required' => 'กรุณากรอกจังหวัด',
            'shipping_postcode.required' => 'กรุณากรอกรหัสไปรษณีย์',
            'shipping_postcode.digits' => 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก',
            'payment_method.required' => 'กรุณาเลือกช่องทางชำระเงิน',
            'payment_method.in' => 'ช่องทางชำระเงินนี้ใช้กับชุดแคมเปญไม่ได้',
            'company_tax_id.required_with' => 'กรุณากรอกเลขประจำตัวผู้เสียภาษี 13 หลัก',
            'company_tax_id.regex' => 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก',
            'expected_price.required' => 'ราคาในหน้าไม่ครบ กรุณาโหลดหน้าใหม่',
            'accept_terms.accepted' => 'กรุณายอมรับเงื่อนไขการสั่งซื้อก่อนสั่งจอง',
            '*.max' => 'ข้อความยาวเกินไป',
        ]);

        if ($validated['company_tax_id'] ?? null) {
            if (strlen(preg_replace('/\D/', '', $validated['company_tax_id'])) !== 13) {
                return back()->withInput()->withErrors(['company_tax_id' => 'เลขประจำตัวผู้เสียภาษีต้องเป็นตัวเลข 13 หลัก']);
            }
        }

        // The customer agrees to the price they saw. If the owner changed it in the meantime
        // (JIB moved), show the new one instead of charging a number they never read.
        if ((int) $validated['expected_price'] !== DgxSparkCampaign::price()) {
            return redirect()->to(route('campaign.dgx-spark') . '#order')
                ->withInput()
                ->with('error', 'ราคาชุดแคมเปญเพิ่งมีการปรับเป็น ' . DgxSparkCampaign::baht(DgxSparkCampaign::price())
                    . ' กรุณาตรวจสอบราคาใหม่ แล้วกดสั่งจองอีกครั้ง');
        }

        try {
            [$order, $created] = $this->orders->place($request->user(), $validated);
        } catch (DgxSparkOrderException $e) {
            return redirect()->to(route('campaign.dgx-spark') . '#order')
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('DGX campaign: order failed', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);

            return redirect()->to(route('campaign.dgx-spark') . '#order')
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ กรุณาลองใหม่อีกครั้ง หรือติดต่อเรา');
        }

        $deadline = DgxSparkCampaign::thaiDateTime(DgxSparkCampaign::holdExpiresAt($order));

        if (! $created) {
            return redirect()->route('orders.show', $order)
                ->with('success', $order->payment_status === 'pending'
                    ? "คุณมีการจองชุด DGX Spark ที่รอชำระอยู่แล้ว (#{$order->order_number}) — กรุณาโอนเงินและแนบสลิปภายใน {$deadline}"
                    : "คุณมีการจองชุด DGX Spark ที่กำลังตรวจสอบการชำระเงินอยู่แล้ว (#{$order->order_number}) — สั่งชุดถัดไปได้หลังยืนยันยอดเงินแล้ว");
        }

        $this->orders->announce($order);

        return redirect()->route('orders.show', $order)
            ->with('success', "จองชุด DGX Spark สำเร็จ — กรุณาโอนเงินและแนบสลิปภายใน {$deadline} เพื่อยืนยันสิทธิ์");
    }
}
