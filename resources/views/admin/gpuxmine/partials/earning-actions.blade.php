{{-- ปุ่มตัดสินรายได้หนึ่งงาน: อนุมัติ (เฉพาะรอตรวจ) และยกเลิก (เฉพาะที่ยังไม่เข้ากระเป๋า)
     ทั้งสองอย่างเปลี่ยนเงินของคนอื่น จึงถามยืนยันก่อนเสมอ และปิดปุ่มหลังกดกันกดซ้ำ
     (ฝั่งเซิร์ฟเวอร์กันซ้ำอยู่แล้ว — ตรงนี้กันแค่ความงง) --}}
@php
    $holdEndsAt = $earning->holdEndsAt($holdHours);
    $clearsNow = $holdEndsAt === null || $holdEndsAt->lte(now());
    $amount = '฿' . number_format($earning->amount_satang / 100, 2);
    $small = 'px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white';
    $approvePrompt = "อนุมัติรายได้ {$earning->job_id} ({$amount})?\n\n" . ($clearsNow
        ? 'พ้นระยะพักแล้ว — จะเข้ากระเป๋าเจ้าของเครื่องในรอบโอนถัดไป (ภายในหนึ่งชั่วโมง)'
        : "ยังอยู่ในระยะพัก — จะกลับไปพักจนครบ {$holdHours} ชม. แล้วค่อยเข้ากระเป๋า");
    $voidPrompt = "ยกเลิกรายได้ {$earning->job_id} ({$amount})?\n\nเจ้าของเครื่องจะไม่ได้เงินรายการนี้ และจะเห็นเหตุผลที่กรอก — ย้อนกลับไม่ได้";
@endphp
<div class="w-56 space-y-1.5">
    @if ($earning->canBeApproved())
        <form method="POST" action="{{ route('admin.gpuxmine.earnings.approve', $earning->id) }}"
              onsubmit="if (! window.confirm(@js($approvePrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <button type="submit" class="w-full px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                {{ $clearsNow ? 'อนุมัติ → รอโอน' : 'อนุมัติ → กลับไปพัก' }}
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

    @if (! $earning->canBeApproved() && ! $earning->canBeVoided())
        <span class="text-xs text-gray-400">—</span>
    @endif
</div>
