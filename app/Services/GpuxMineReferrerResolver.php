<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\GpuNode;

/**
 * ใครคือผู้แนะนำของเจ้าของเครื่อง (D8)
 *
 * จับครั้งเดียวตอนจับคู่ แล้วเก็บไว้ที่ gpu_nodes.referrer_user_id ให้ aixman
 * อ่านตอนคิดส่วนแบ่ง — ต้องจับไว้ตอนนี้ เพราะ xmanstudio ไม่มีที่ไหนจำว่าใคร
 * ชวนใครมา นอกจาก cookie ที่หายไปเมื่อเปลี่ยนเบราว์เซอร์
 *
 * ลำดับ:
 *   1. เจ้าของคนนี้เคยจับคู่เครื่องที่มีผู้แนะนำแล้ว → คนเดิม (จับครั้งเดียว
 *      ต่อเจ้าของ ไม่ใช่ครั้งเดียวต่อเครื่อง ไม่งั้นลงเครื่องใหม่ก็เปลี่ยนคนได้)
 *   2. ลิงก์แนะนำที่เพิ่งพาเข้ามา (session/cookie affiliate_ref)
 *   3. ผู้แนะนำในผังของเจ้าของเอง — ยกเว้นรากของผังและบัญชีแอดมิน ซึ่ง
 *      Affiliate::getOrCreateForUser ใส่ให้ทุกคนที่มาเองโดยไม่มีใครชวน
 *      ถ้าไม่ตัดออก 5% ของค่าตอบแทนคนที่ไม่มีผู้แนะนำจะไหลไปที่แอดมิน
 *
 * ไม่มีทางคืนตัวเจ้าของเอง
 */
class GpuxMineReferrerResolver
{
    public function __construct(private readonly AffiliateCommissionService $affiliates) {}

    /**
     * @param  bool  $fromRequest  อ่านลิงก์แนะนำจาก session/cookie ของคำขอนี้ด้วยไหม
     *                             (หน้าเว็บ: ได้ — API ของโปรแกรม: ไม่มี session ให้อ่าน)
     */
    public function resolve(int $ownerUserId, bool $fromRequest = true): ?int
    {
        $known = GpuNode::withTrashed()
            ->where('user_id', $ownerUserId)
            ->whereNotNull('referrer_user_id')
            ->orderBy('id')
            ->value('referrer_user_id');
        if ($known !== null && (int) $known !== $ownerUserId) {
            return (int) $known;
        }

        if ($fromRequest) {
            $link = $this->affiliates->resolveAffiliate($ownerUserId);
            if ($link !== null && (int) $link->user_id !== $ownerUserId) {
                return (int) $link->user_id;
            }
        }

        $own = Affiliate::with('parent.user')->where('user_id', $ownerUserId)->first();
        $parent = $own?->parent;

        if ($parent === null
            || $parent->parent_id === null            // รากของผัง
            || (int) $parent->user_id === $ownerUserId
            || $parent->user === null
            || $parent->user->isAdmin()) {
            return null;
        }

        return (int) $parent->user_id;
    }
}
