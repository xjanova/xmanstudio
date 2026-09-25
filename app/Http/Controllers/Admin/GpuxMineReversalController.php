<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpuJobEarning;
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

    public function earning(Request $request, int $id): RedirectResponse
    {
        $result = $this->reversals->reverseEarning($id, $request->user(), $this->reason($request));

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        // ค่าแนะนำของงานนี้อยู่ในรายการค่าแนะนำรวมของรอบโอน — ไม่ถูกเรียกคืนตามไปเอง บอกแอดมินตรง ๆ
        $commissionId = GpuJobEarning::whereKey($id)->value('affiliate_commission_id');

        return back()->with('success', $result['message']
            . ($commissionId ? ' · ส่วนแบ่งผู้แนะนำของงานนี้อยู่ในค่าแนะนำ #' . $commissionId . ' ซึ่งไม่ถูกเรียกคืนตาม — เรียกคืนแยกได้ที่หน้าค่าแนะนำ' : ''));
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
