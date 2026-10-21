<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * คิวตรวจยืนยันตัวตนของผู้ดูแลระบบ
 *
 * การอนุมัติหนึ่งครั้งเปิดสองประตูพร้อมกัน: ถอนเงินได้ และสร้างเนื้อหาผู้ใหญ่
 * บน ai.xman4289.com ได้ จึงบันทึกไว้เสมอว่าใครเป็นคนอนุมัติและเมื่อไร
 */
class KycController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', KycVerification::STATUS_PENDING);
        $search = trim((string) $request->query('q', ''));

        $query = KycVerification::with('user')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('full_name_th', 'like', "%{$search}%")
                        ->orWhere('bank_account_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            // รอนานสุดขึ้นก่อน — คิวตรวจตัวตนที่เรียงตามใหม่สุด แปลว่าคนแรก
            // ที่ส่งเข้ามาจะรอตลอดไป
            ->orderBy('submitted_at');

        return view('admin.kyc.index', [
            'items' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'counts' => KycVerification::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(int $id)
    {
        return view('admin.kyc.show', [
            'kyc' => KycVerification::with(['user', 'reviewer'])->findOrFail($id),
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $kyc = KycVerification::findOrFail($id);

        if (! $kyc->isPending()) {
            return back()->with('error', 'คำขอนี้ถูกตรวจไปแล้ว');
        }

        DB::transaction(function () use ($kyc, $request) {
            $kyc->update([
                'status' => KycVerification::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $kyc->user?->forceFill([
                'kyc_status' => KycVerification::STATUS_APPROVED,
                'kyc_verified_at' => now(),
            ])->save();
        });

        Log::info('[kyc] approved', [
            'kyc_id' => $kyc->id,
            'user_id' => $kyc->user_id,
            'by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.kyc.index')
            ->with('success', 'อนุมัติการยืนยันตัวตนเรียบร้อย');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $kyc = KycVerification::findOrFail($id);

        if (! $kyc->isPending()) {
            return back()->with('error', 'คำขอนี้ถูกตรวจไปแล้ว');
        }

        $validated = $request->validate([
            // เหตุผลเป็นข้อความที่ผู้ใช้จะได้อ่าน — "ไม่ผ่าน" เฉย ๆ ทำให้เขา
            // ส่งใบเดิมกลับมาอีก และคิวก็วนอยู่อย่างนั้น
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.min' => 'กรุณาระบุเหตุผลให้ผู้ใช้เข้าใจว่าต้องแก้อะไร (อย่างน้อย 10 ตัวอักษร)',
        ]);

        DB::transaction(function () use ($kyc, $request, $validated) {
            $kyc->update([
                'status' => KycVerification::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $kyc->user?->forceFill([
                'kyc_status' => KycVerification::STATUS_REJECTED,
                'kyc_verified_at' => null,
            ])->save();
        });

        Log::info('[kyc] rejected', [
            'kyc_id' => $kyc->id,
            'user_id' => $kyc->user_id,
            'by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.kyc.index')
            ->with('success', 'ปฏิเสธคำขอและแจ้งเหตุผลให้ผู้ใช้แล้ว');
    }
}
