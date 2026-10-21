{{-- ไม่อนุมัติ + เหตุผล (ผู้ใช้เห็น) — ยอดที่พักคืนเข้ากระเป๋า ใช้ได้ทั้งก่อนและหลังกดเริ่มโอน --}}
<form method="POST" action="{{ route('admin.wallets.withdrawals.reject', $withdrawal->id) }}"
      class="{{ $card }} p-5 space-y-3"
      onsubmit="if (! window.confirm(@js($rejectPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
    @csrf
    <h2 class="font-semibold text-gray-900 dark:text-white">ไม่อนุมัติ — คืนยอดเข้ากระเป๋า</h2>
    @if ($withdrawal->isProcessing())
        <p class="text-xs rounded-lg bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-200 px-3 py-2">ใช้เมื่อยังไม่ได้โอนหรือโอนไม่ผ่านเท่านั้น — ถ้าโอนไปแล้ว ห้ามกด ผู้ใช้จะได้ทั้งเงินที่โอนและยอดที่คืน</p>
    @endif
    <div>
        <label class="block text-xs text-gray-500 mb-1" for="reason">เหตุผล (ผู้ใช้จะเห็น อย่างน้อย {{ \App\Services\WalletWithdrawalService::MIN_REJECT_REASON }} ตัวอักษร)</label>
        <textarea id="reason" name="reason" rows="3" required minlength="{{ \App\Services\WalletWithdrawalService::MIN_REJECT_REASON }}" maxlength="255" class="{{ $input }}">{{ old('reason') }}</textarea>
    </div>
    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">ไม่อนุมัติ</button>
</form>
