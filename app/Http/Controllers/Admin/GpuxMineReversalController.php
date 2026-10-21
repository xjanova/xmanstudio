<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GpuxMinePayoutReversalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * เรียกคืนเงิน GPUxMINE ที่จ่ายเข้ากระเป๋าไปแล้ว (R2-3) — รายได้ของเครื่องหนึ่งงาน หรือค่าแนะนำหนึ่งรายการ
 *
 * ปุ่มอยู่ในหน้าแอดมินเดิม (คิวรายได้ GPUxMINE, หน้าค่าแนะนำ) และถามยืนยันก่อนทุกครั้ง
 * กฎเงินทั้งหมด — ทำได้ครั้งเดียว ยอดที่ใช้ได้ต้องพอ — อยู่ใน GpuxMinePayoutReversalService
 */
class GpuxMineReversalController extends Controller
{
    public function __construct(private readonly GpuxMinePayoutReversalService $reversals) {}

    /**
     * with_referral: เรียกคืนส่วนแบ่งผู้แนะนำของงานเดียวกันด้วย (ฟอร์มติ๊กไว้ให้) — ไม่ส่งมา (หน้าที่เปิด
     * ค้างจากก่อนมีช่องนี้ ซึ่งบอกแอดมินว่าส่วนผู้แนะนำไม่ถูกเรียกคืนตาม) = เฉพาะส่วนเจ้าของเครื่อง
     * ข้อความผลบอกว่าส่วนผู้แนะนำไปทางไหน
     */
    public function earning(Request $request, int $id): RedirectResponse
    {
        $result = $this->reversals->reverseEarning($id, $request->user(), $this->reason($request), $request->boolean('with_referral'));

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function commission(Request $request, int $id): RedirectResponse
    {
        $result = $this->reversals->reverseCommission($id, $request->user(), $this->reason($request));

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** เหตุผลที่แอดมินกรอก — เจ้าของเงินได้อ่าน จึงบังคับให้มีความหมาย */
    private function reason(Request $request): string
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:' . GpuxMinePayoutReversalService::MIN_REASON, 'max:' . GpuxMinePayoutReversalService::MAX_REASON],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่เรียกคืน — เจ้าของเงินจะเห็นข้อความนี้',
            'reason.min' => 'เหตุผลสั้นเกินไป (อย่างน้อย ' . GpuxMinePayoutReversalService::MIN_REASON . ' ตัวอักษร)',
            'reason.max' => 'เหตุผลยาวเกิน ' . GpuxMinePayoutReversalService::MAX_REASON . ' ตัวอักษร',
        ]);

        return trim($validated['reason']);
    }
}
