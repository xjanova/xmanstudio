{{-- ปุ่มตัดสินรายได้หนึ่งงาน: อนุมัติ (เฉพาะรอตรวจ) ยกเลิก (เฉพาะที่ยังไม่เข้ากระเป๋า) และเรียกคืน
     (เข้ากระเป๋าแล้ว — หักกระเป๋าเจ้าของคืน ได้ต่อเมื่อยอดยังไม่ถูกถอนออก, R2-3)
     ทุกอย่างเปลี่ยนเงินของคนอื่น จึงถามยืนยันก่อนเสมอ และปิดปุ่มหลังกดกันกดซ้ำ
     (ฝั่งเซิร์ฟเวอร์กันซ้ำอยู่แล้ว — ตรงนี้กันแค่ความงง) --}}
@php
    $holdEndsAt = $earning->holdEndsAt($holdHours);
    $clearsNow = $holdEndsAt === null || $holdEndsAt->lte(now());
    // เครื่องที่ถูกระงับ: ตัวโอนข้ามเงินของมันจนกว่าจะยกเลิกระงับ — ห้ามสัญญาว่า "รอบถัดไป"
    $frozen = in_array((int) $earning->id, $frozenIds ?? [], true);
    $amount = '฿' . number_format($earning->amount_satang / 100, 2);
    $small = 'px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white';
    $approvePrompt = "อนุมัติรายได้ {$earning->job_id} ({$amount})?\n\n" . match (true) {
        $frozen => 'เครื่องนี้ถูกระงับอยู่ — อนุมัติได้ แต่เงินจะยังไม่เดินจนกว่าจะยกเลิกระงับ',
        $clearsNow => 'พ้นระยะพักแล้ว — จะเข้ากระเป๋าเจ้าของเครื่องในรอบโอนถัดไป (ภายในหนึ่งชั่วโมง)',
        default => 'ยังอยู่ในระยะพัก — จะกลับไปพักจนถึง ' . $holdEndsAt->copy()->timezone('Asia/Bangkok')->format('d/m H:i') . ' น. แล้วค่อยเข้ากระเป๋า',
    };
    $voidPrompt = "ยกเลิกรายได้ {$earning->job_id} ({$amount})?\n\nเจ้าของเครื่องจะไม่ได้เงินรายการนี้ และจะเห็นเหตุผลที่กรอก — ย้อนกลับไม่ได้";
    $reversePrompt = "เรียกคืนรายได้ {$earning->job_id} ({$amount}) จากกระเป๋าเจ้าของเครื่อง?\n\nหักกระเป๋าใบเดิมเท่ายอดนี้ (ได้ต่อเมื่อยอดยังไม่ถูกถอนออก) งานกลายเป็น \"เรียกคืนแล้ว\" และเจ้าของเห็นเหตุผลที่กรอก — ทำได้ครั้งเดียว ย้อนกลับไม่ได้";
    // ส่วนแบ่งผู้แนะนำของงานนี้ยังอยู่กับผู้แนะนำ (ค่าแนะนำจ่ายแล้วหรือยังรอจ่าย) — เลือกเรียกคืนพร้อมกันได้
    $referralShare = $earning->affiliate_commission_id
        && (int) $earning->referral_satang > 0
        && $earning->referral_unpaid_to === null
        && (int) $earning->referral_reversed_satang === 0
        && in_array($earning->affiliateCommission?->status, ['pending', 'paid'], true);
    $referralAmount = '฿' . number_format(((int) $earning->referral_satang) / 100, 2);
    $withReferralPrompt = $reversePrompt . "\n\nรวมส่วนแบ่งผู้แนะนำของงานนี้ {$referralAmount} (ค่าแนะนำ #{$earning->affiliate_commission_id}) — หักกระเป๋าผู้แนะนำถ้าจ่ายไปแล้ว หรือตัดออกจากค่าแนะนำที่ยังไม่จ่าย";
    $withoutReferralPrompt = $reversePrompt . "\n\nส่วนแบ่งผู้แนะนำของงานนี้ {$referralAmount} (ค่าแนะนำ #{$earning->affiliate_commission_id}) ไม่ถูกเรียกคืน";
@endphp
<div class="w-56 space-y-1.5">
    @if ($earning->canBeApproved())
        <form method="POST" action="{{ route('admin.gpuxmine.earnings.approve', $earning->id) }}"
              onsubmit="if (! window.confirm(@js($approvePrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <button type="submit" class="w-full px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                {{ $frozen ? 'อนุมัติ (เงินพักจนยกเลิกระงับ)' : ($clearsNow ? 'อนุมัติ → รอโอน' : 'อนุมัติ → กลับไปพัก') }}
            </button>
        </form>
    @endif

    @if ($earning->canBeVoided())
        <form method="POST" action="{{ route('admin.gpuxmine.earnings.void', $earning->id) }}"
              onsubmit="if (! window.confirm(@js($voidPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
              class="flex items-center gap-1.5">
            @csrf
            <input type="text" name="reason" required minlength="3" maxlength="255" autocomplete="off"
                   placeholder="เหตุผลที่ยกเลิก" title="เจ้าของเครื่องจะเห็นเหตุผลนี้"
                   class="flex-1 min-w-0 {{ $small }}">
            <button type="submit" class="px-2.5 py-1 rounded bg-red-600 hover:bg-red-500 text-white text-xs font-semibold transition whitespace-nowrap disabled:opacity-50 disabled:cursor-wait">
                ยกเลิก
            </button>
        </form>
    @endif

    @if ($earning->canBeReversed())
        <form method="POST" action="{{ route('admin.gpuxmine.earnings.reverse', $earning->id) }}"
              @if ($referralShare)
                  onsubmit="if (! window.confirm(this.querySelector('input[type=checkbox][name=with_referral]').checked ? @js($withReferralPrompt) : @js($withoutReferralPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
              @else
                  onsubmit="if (! window.confirm(@js($reversePrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
              @endif
              class="space-y-1">
            @csrf
            <div class="flex items-center gap-1.5">
                <input type="text" name="reason" required minlength="{{ \App\Services\GpuxMinePayoutReversalService::MIN_REASON }}" maxlength="255" autocomplete="off"
                       placeholder="เหตุผลที่เรียกคืน (≥10 ตัวอักษร)" title="เจ้าของเครื่องจะเห็นเหตุผลนี้"
                       class="flex-1 min-w-0 {{ $small }}">
                <button type="submit" class="px-2.5 py-1 rounded bg-rose-700 hover:bg-rose-600 text-white text-xs font-semibold transition whitespace-nowrap disabled:opacity-50 disabled:cursor-wait">
                    เรียกคืน
                </button>
            </div>
            @if ($referralShare)
                <input type="hidden" name="with_referral" value="0">
                <label class="flex items-start gap-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                    <input type="checkbox" name="with_referral" value="1" checked class="mt-0.5">
                    <span>เรียกคืนส่วนแบ่งผู้แนะนำ {{ $referralAmount }} ด้วย (ค่าแนะนำ #{{ $earning->affiliate_commission_id }})</span>
                </label>
            @endif
        </form>
    @endif

    @if (! $earning->canBeApproved() && ! $earning->canBeVoided() && ! $earning->canBeReversed())
        <span class="text-xs text-gray-400">—</span>
    @endif
</div>
