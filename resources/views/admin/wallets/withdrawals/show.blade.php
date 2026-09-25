@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คำขอถอน ' . $withdrawal->withdrawal_id)
@section('page-title', 'คำขอถอนเงิน')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $bkk = static fn ($date) => $date ? $date->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '—';
    $fmt = static fn (int $satang) => \App\Support\Satang::format($satang);
    $amount = $fmt($withdrawal->amountSatang());
    $input = 'w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white';
    $fromKyc = $withdrawal->account_source === \App\Models\WalletWithdrawal::SOURCE_KYC;
    $destination = $withdrawal->method === \App\Models\WalletWithdrawal::METHOD_PROMPTPAY
        ? 'พร้อมเพย์ ' . $withdrawal->promptpay_id
        : ($withdrawal->bank_name ?: $withdrawal->bank_code) . ' ' . $withdrawal->account_number;
    $approvePrompt = "บันทึกว่าโอน {$amount} ให้ {$withdrawal->withdrawal_id} แล้ว?\n\nปลายทาง: {$destination}\nชื่อบัญชี: {$withdrawal->account_name}\n\nโอนเงินจริงในแอปธนาคารก่อนกด — กดแล้วผู้ใช้จะเห็นว่า \"โอนเข้าบัญชีแล้ว\" ย้อนกลับไม่ได้";
    $rejectPrompt = "ไม่อนุมัติคำขอ {$withdrawal->withdrawal_id} ({$amount})?\n\nยอดที่พักไว้จะคืนเข้ากระเป๋าผู้ใช้ทันที และผู้ใช้จะเห็นเหตุผลที่กรอก — ย้อนกลับไม่ได้";
@endphp

<div class="space-y-6 max-w-5xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $withdrawal->withdrawal_id }} · {{ $amount }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ขอเมื่อ {{ $bkk($withdrawal->created_at) }} · {{ $withdrawal->statusLabel() }}</p>
        </div>
        <a href="{{ route('admin.wallets.withdrawals') }}" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">← คำขอถอนทั้งหมด</a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ปลายทาง: เลขเต็มอยู่หน้านี้หน้าเดียว — คนที่โอนเงินจริงต้องเห็น --}}
        <div class="{{ $card }} p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white">โอนเข้า</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">ช่องทาง</dt><dd class="text-gray-900 dark:text-white">{{ $withdrawal->methodLabel() }}</dd></div>
                @if ($withdrawal->method === \App\Models\WalletWithdrawal::METHOD_PROMPTPAY)
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">พร้อมเพย์</dt><dd class="font-mono text-lg text-gray-900 dark:text-white select-all">{{ $withdrawal->promptpay_id }}</dd></div>
                @else
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">ธนาคาร</dt><dd class="text-gray-900 dark:text-white">{{ $withdrawal->bank_name ?: $withdrawal->bank_code }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">เลขบัญชี</dt><dd class="font-mono text-lg text-gray-900 dark:text-white select-all">{{ $withdrawal->account_number }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="text-gray-500">ชื่อบัญชี</dt><dd class="text-gray-900 dark:text-white">{{ $withdrawal->account_name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">ยอดที่ต้องโอน</dt><dd class="text-xl font-bold text-emerald-700 dark:text-emerald-300 tabular-nums">{{ $amount }}</dd></div>
            </dl>
            @if ($fromKyc)
                <p class="text-xs rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 px-3 py-2">บัญชีนี้มาจากข้อมูลยืนยันตัวตนที่อนุมัติแล้ว</p>
            @else
                <p class="text-xs rounded-lg bg-orange-50 dark:bg-orange-500/10 text-orange-800 dark:text-orange-200 px-3 py-2">
                    ผู้ใช้กรอกบัญชีนี้เอง (KYC ไม่มีบัญชีรับเงิน) — ตรวจว่าชื่อบัญชีตรงกับชื่อที่ยืนยันตัวตน:
                    <strong>{{ $kyc?->full_name_th ?? 'ไม่พบข้อมูล KYC' }}</strong>
                </p>
            @endif
        </div>

        <div class="{{ $card }} p-5 space-y-3">
            <h2 class="font-semibold text-gray-900 dark:text-white">ผู้ขอ</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">ชื่อ</dt><dd class="text-gray-900 dark:text-white">{{ $withdrawal->user?->name ?? 'บัญชีถูกลบ' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">อีเมล</dt><dd class="text-gray-900 dark:text-white">{{ $withdrawal->user?->email ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">ชื่อตาม KYC</dt><dd class="text-gray-900 dark:text-white">{{ $kyc?->full_name_th ?? '—' }} {{ $kyc ? '(' . $kyc->statusLabel() . ')' : '' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">IP ตอนขอ</dt><dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $withdrawal->request_ip ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">รายการพักยอด</dt><dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $withdrawal->holdTransaction?->transaction_id ?? '—' }} ({{ $withdrawal->holdTransaction?->stateLabel() ?? '—' }})</dd></div>
                @if ($withdrawal->returnTransaction)
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">รายการคืนยอด</dt><dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $withdrawal->returnTransaction->transaction_id }}</dd></div>
                @endif
            </dl>
            @if ($withdrawal->user_id)
                <a href="{{ route('admin.kyc.index', ['q' => $withdrawal->user?->email, 'status' => 'all']) }}" class="inline-block text-xs text-emerald-600 hover:text-emerald-700">ดูเอกสารยืนยันตัวตน →</a>
            @endif
        </div>
    </div>

    @if ($withdrawal->isPending())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <form method="POST" action="{{ route('admin.wallets.withdrawals.approve', $withdrawal->id) }}" enctype="multipart/form-data"
                  class="{{ $card }} p-5 space-y-3"
                  onsubmit="if (! window.confirm(@js($approvePrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <h2 class="font-semibold text-gray-900 dark:text-white">โอนแล้ว — บันทึกผล</h2>
                <div>
                    <label class="block text-xs text-gray-500 mb-1" for="payout_reference">เลขอ้างอิงการโอน</label>
                    <input id="payout_reference" type="text" name="payout_reference" value="{{ old('payout_reference') }}" maxlength="100" autocomplete="off" class="{{ $input }}" placeholder="เช่น เลขที่รายการจากแอปธนาคาร">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1" for="slip">สลิป (JPG/PNG/WEBP ไม่เกิน 5 MB)</label>
                    <input id="slip" type="file" name="slip" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-gray-700 dark:text-gray-300">
                </div>
                <p class="text-xs text-gray-500">ต้องมีเลขอ้างอิงหรือสลิปอย่างน้อยหนึ่งอย่าง — ผู้ใช้เห็นทั้งสองอย่างในหน้าถอนเงินของเขา</p>
                <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">บันทึกว่าโอนแล้ว</button>
            </form>

            <form method="POST" action="{{ route('admin.wallets.withdrawals.reject', $withdrawal->id) }}"
                  class="{{ $card }} p-5 space-y-3"
                  onsubmit="if (! window.confirm(@js($rejectPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <h2 class="font-semibold text-gray-900 dark:text-white">ไม่อนุมัติ — คืนยอดเข้ากระเป๋า</h2>
                <div>
                    <label class="block text-xs text-gray-500 mb-1" for="reason">เหตุผล (ผู้ใช้จะเห็น อย่างน้อย {{ \App\Services\WalletWithdrawalService::MIN_REJECT_REASON }} ตัวอักษร)</label>
                    <textarea id="reason" name="reason" rows="3" required minlength="{{ \App\Services\WalletWithdrawalService::MIN_REJECT_REASON }}" maxlength="255" class="{{ $input }}">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">ไม่อนุมัติ</button>
            </form>
        </div>
    @else
        <div class="{{ $card }} p-5 text-sm space-y-1.5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-2">ผลการดำเนินการ</h2>
            <p><span class="text-gray-500">สถานะ:</span> <span class="text-gray-900 dark:text-white">{{ $withdrawal->statusLabel() }}</span></p>
            @if ($withdrawal->reviewer)
                <p><span class="text-gray-500">โดย:</span> {{ $withdrawal->reviewer->name }} · {{ $bkk($withdrawal->reviewed_at) }}</p>
            @elseif ($withdrawal->cancelled_at)
                <p><span class="text-gray-500">ผู้ใช้ยกเลิกเมื่อ:</span> {{ $bkk($withdrawal->cancelled_at) }}</p>
            @endif
            @if ($withdrawal->payout_reference)
                <p><span class="text-gray-500">อ้างอิงการโอน:</span> <span class="font-mono">{{ $withdrawal->payout_reference }}</span></p>
            @endif
            @if ($withdrawal->slip_path)
                <p><a href="{{ route('admin.wallets.withdrawals.slip', $withdrawal->id) }}" target="_blank" rel="noopener" class="text-emerald-600 hover:text-emerald-700">ดูสลิปการโอน →</a></p>
            @endif
            @if ($withdrawal->reject_reason)
                <p><span class="text-gray-500">เหตุผลที่ไม่อนุมัติ:</span> {{ $withdrawal->reject_reason }}</p>
            @endif
        </div>
    @endif

    @if ($others->isNotEmpty())
        <div class="{{ $card }} overflow-hidden">
            <h2 class="px-5 py-3 font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700">คำขออื่นของผู้ใช้คนนี้</h2>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                @foreach ($others as $o)
                    <li class="px-5 py-2.5 flex flex-wrap justify-between gap-2">
                        <a href="{{ route('admin.wallets.withdrawals.show', $o->id) }}" class="font-mono text-xs text-gray-700 dark:text-gray-300 hover:text-emerald-600">{{ $o->withdrawal_id }}</a>
                        <span class="text-gray-500 tabular-nums">{{ $bkk($o->created_at) }}</span>
                        <span class="tabular-nums text-gray-900 dark:text-white">{{ $fmt($o->amountSatang()) }}</span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $o->statusLabel() }} · {{ $o->maskedDestination() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
