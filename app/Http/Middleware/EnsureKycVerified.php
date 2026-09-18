<?php

namespace App\Http\Middleware;

use App\Models\KycVerification;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ปิดเส้นทางที่ต้องรู้ว่าผู้ใช้เป็นใครจริง ๆ — การถอนเงิน และการเข้าถึง
 * หมวดเนื้อหาสำหรับผู้ใหญ่
 *
 * อ่านจาก `users.kyc_status` ที่ถูก denormalise ไว้ ไม่ join ตาราง kyc ทุก request
 */
class EnsureKycVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (($user->kyc_status ?? KycVerification::USER_STATUS_NOT_SUBMITTED) === KycVerification::STATUS_APPROVED) {
            return $next($request);
        }

        $message = match ($user->kyc_status ?? null) {
            KycVerification::STATUS_PENDING => 'คำขอยืนยันตัวตนของคุณอยู่ระหว่างการตรวจสอบ',
            KycVerification::STATUS_REJECTED => 'การยืนยันตัวตนไม่ผ่าน กรุณาแก้ไขและส่งใหม่',
            default => 'ต้องยืนยันตัวตนก่อนจึงจะใช้ส่วนนี้ได้',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'kyc_required',
                'kyc_status' => $user->kyc_status ?? KycVerification::USER_STATUS_NOT_SUBMITTED,
                'message' => $message,
            ], 403);
        }

        return redirect()->route('kyc.index')->with('error', $message);
    }
}
