@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ถอนเงิน')
@section('page-title')<x-bi th="ถอนเงิน" en="Withdraw" />@endsection
@section('page-description')<x-bi th="ถอนเงินจากกระเป๋า XMAN เข้าบัญชีที่ยืนยันตัวตนไว้ แอดมินตรวจแล้วโอนให้" en="Withdraw from your XMAN wallet to your verified account, transferred by our team" />@endsection

@section('content')
@php
    $fmt = static fn (int $satang) => \App\Support\Satang::format($satang);
    $bkk = static fn ($date) => $date ? $date->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '—';
    $kycApproved = $kycStatus === \App\Models\KycVerification::STATUS_APPROVED;
    $canRequest = $enabled && $kycApproved && ! $open && $availableSatang >= $minSatang;
    $input = 'w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
    $tone = static fn (string $status) => match ($status) {
        'approved' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30',
        'pending' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30',
        'processing' => 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-500/30',
        'rejected' => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border-red-200 dark:border-red-500/30',
        default => 'bg-gray-100 dark:bg-gray-700/40 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600',
    };
@endphp

<div class="space-y-6">
    {{-- ══════════ HERO ══════════ --}}
    <div class="relative overflow-hidden rounded-2xl shadow-lg bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700">
        <x-page-art art="hero-payment" :opacity="14" :scrim="false" fade="bottom" />
        <div class="relative px-6 sm:px-8 py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="min-w-0">
                <nav class="flex items-center text-emerald-100 text-sm mb-2">
                    <a href="{{ route('user.wallet.index') }}" class="hover:text-white transition-colors"><x-bi th="กระเป๋าเงิน" en="Wallet" /></a>
                    <span class="mx-2" aria-hidden="true">›</span>
                    <span class="text-white"><x-bi th="ถอนเงิน" en="Withdraw" /></span>
                </nav>
                <h1 class="text-3xl font-bold text-white"><x-bi th="ถอนเงินเข้าบัญชี" en="Withdraw to your bank" /></h1>
                <p class="mt-1 text-emerald-50/90 text-sm max-w-xl">
                    <x-bi th="ขอถอนแล้วยอดถูกพักไว้ทันที แอดมินตรวจและโอนเข้าบัญชีที่ยืนยันตัวตนไว้ ยกเลิกได้จนกว่าแอดมินจะเริ่มโอน"
                          en="The amount is held as soon as you ask. Our team checks it and transfers to your verified account. You can cancel until we start the transfer." />
                </p>
            </div>
            <div class="shrink-0 md:text-right">
                <p class="text-emerald-100 text-xs mb-1"><x-bi th="ยอดที่ถอนได้" en="Available to withdraw" /></p>
                <p class="text-3xl sm:text-4xl font-bold text-white tabular-nums">{{ $fmt($availableSatang) }}</p>
                @if ($open)
                    <p class="mt-1 text-xs text-amber-100"><x-bi th="พักไว้กับคำขอถอน" en="Held for withdrawal" /> {{ $fmt($open->amountSatang()) }}</p>
                @endif
            </div>
        </div>
    </div>

    @if (! $enabled)
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 text-sm">
            <x-bi th="ระบบถอนเงินปิดปรับปรุงชั่วคราว — คำขอที่ส่งไว้แล้วยังดำเนินการตามปกติ และยกเลิกได้"
                  en="Withdrawals are paused for maintenance. Requests already sent are still processed and can be cancelled." />
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($open)
                {{-- ══════════ คำขอที่รออยู่ ══════════ --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-amber-200 dark:border-amber-500/30 overflow-hidden">
                    <div class="px-6 py-4 bg-amber-50 dark:bg-amber-500/10 border-b border-amber-200 dark:border-amber-500/30 flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-amber-900 dark:text-amber-100">
                            @if ($open->isProcessing())
                                <x-bi th="แอดมินกำลังโอนเงินให้คุณ" en="We are transferring it now" />
                            @else
                                <x-bi th="คำขอที่รอแอดมินโอน" en="Waiting for our transfer" />
                            @endif
                        </h2>
                        <span class="text-xs font-mono text-amber-800 dark:text-amber-200">{{ $open->withdrawal_id }}</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 text-xs"><x-bi th="ยอดถอน" en="Amount" /></dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $fmt($open->amountSatang()) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 text-xs"><x-bi th="โอนเข้า" en="To" /></dt>
                                <dd class="text-gray-900 dark:text-white">{{ $open->maskedDestination() }}</dd>
                                <dd class="text-xs text-gray-500 dark:text-gray-400">{{ $open->account_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400 text-xs"><x-bi th="ขอเมื่อ" en="Requested" /></dt>
                                <dd class="text-gray-900 dark:text-white tabular-nums">{{ $bkk($open->created_at) }}</dd>
                            </div>
                        </dl>
                        @if ($open->isProcessing())
                            <p class="rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 px-4 py-3 text-sm text-sky-900 dark:text-sky-100 leading-relaxed">
                                <x-bi th="แอดมินเริ่มโอนเงินเข้าบัญชีของคุณแล้ว — ยกเลิกคำขอนี้ไม่ได้แล้ว สถานะจะเปลี่ยนเป็น “โอนเข้าบัญชีแล้ว” พร้อมเลขอ้างอิงเมื่อบันทึกผลเสร็จ หากมีปัญหาติดต่อทีมงาน"
                                      en="We have started the transfer to your account, so this request can no longer be cancelled. It turns to “Transferred” with a reference once recorded. Contact us if anything looks wrong." />
                            </p>
                        @else
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            <x-bi th="ยอดนี้ถูกพักไว้ ใช้จ่ายที่อื่นไม่ได้จนกว่าแอดมินจะโอนหรือคุณยกเลิก — ยกเลิกได้จนกว่าแอดมินจะเริ่มโอน และยอดคืนเข้ากระเป๋าทันที"
                                  en="This amount is held and cannot be spent until we transfer it or you cancel. You can cancel until we start the transfer; the amount returns to your wallet at once." />
                        </p>
                        <form method="POST" action="{{ route('user.wallet.withdraw.cancel', $open->id) }}"
                              x-data="{ sending: false }"
                              @submit="if (! window.confirm(@js('ยกเลิกคำขอถอน ' . $open->withdrawal_id . ' (' . $fmt($open->amountSatang()) . ')?' . "\n\n" . 'ยอดที่พักไว้จะคืนเข้ากระเป๋าทันที'))) { $event.preventDefault(); return; } sending = true">
                            @csrf
                            <button type="submit" x-bind:disabled="sending"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-red-300 dark:border-red-500/40 text-red-700 dark:text-red-300 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-500/10 transition disabled:opacity-50 disabled:cursor-wait">
                                <x-bi th="ยกเลิกคำขอนี้" en="Cancel this request" />
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            @elseif (! $kycApproved)
                {{-- ══════════ ต้องยืนยันตัวตนก่อน ══════════ --}}
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="font-semibold text-gray-900 dark:text-white"><x-bi th="ยืนยันตัวตนก่อนถอนเงิน" en="Verify your identity first" /></h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        @if ($kycStatus === \App\Models\KycVerification::STATUS_PENDING)
                            <x-bi th="คำขอยืนยันตัวตนของคุณอยู่ระหว่างการตรวจสอบ — ถอนเงินได้เมื่อผ่านแล้ว"
                                  en="Your verification is being reviewed. You can withdraw once it is approved." />
                        @elseif ($kycStatus === \App\Models\KycVerification::STATUS_REJECTED)
                            <x-bi th="การยืนยันตัวตนไม่ผ่าน กรุณาแก้ไขและส่งใหม่ — เงินโอนออกได้เฉพาะบัญชีที่ชื่อตรงกับผู้ที่ยืนยันตัวตนไว้"
                                  en="Your verification was not approved. Please fix and resubmit. Money only goes to an account in your verified name." />
                        @else
                            <x-bi th="เงินโอนออกได้เฉพาะบัญชีที่ชื่อตรงกับผู้ที่ยืนยันตัวตนไว้ ยืนยันครั้งเดียวใช้ได้ตลอด"
                                  en="Money only goes to an account in your verified name. Verify once and it stays." />
                        @endif
                    </p>
                    <a href="{{ route('kyc.index') }}" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-sm font-semibold shadow hover:shadow-lg transition">
                        <x-bi th="ไปหน้ายืนยันตัวตน" en="Go to verification" />
                    </a>
                </div>
            @elseif ($enabled)
                {{-- ══════════ ฟอร์มขอถอน ══════════ --}}
                <form method="POST" action="{{ route('user.wallet.withdraw.store') }}"
                      class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 space-y-5"
                      x-data="{ sending: false, amount: @js(old('amount', '')), method: @js(old('method', 'bank')) }"
                      @submit="if (! window.confirm('ยืนยันขอถอน ฿' + amount + '?\n\nยอดนี้จะถูกพักไว้ทันที และแอดมินจะโอนเข้าบัญชีที่แสดงไว้ — ยกเลิกได้จนกว่าแอดมินจะเริ่มโอน')) { $event.preventDefault(); return; } sending = true">
                    @csrf
                    <input type="hidden" name="request_key" value="{{ $requestKey }}">

                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white"><x-bi th="บัญชีรับเงิน" en="Payout account" /></h2>
                        @if ($kycAccount)
                            <div class="mt-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 p-4">
                                <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">{{ $kycAccount['bank_name'] }} · xxx-{{ substr($kycAccount['account_number'], -4) }}</p>
                                <p class="text-sm text-emerald-800 dark:text-emerald-200">{{ $kycAccount['account_name'] }}</p>
                                <p class="mt-2 text-xs text-emerald-700/80 dark:text-emerald-300/80">
                                    <x-bi th="บัญชีที่ยืนยันตัวตนไว้ — โอนเข้าบัญชีนี้เท่านั้น ถ้าต้องการเปลี่ยนบัญชี ติดต่อทีมงาน"
                                          en="Your verified account. Payouts only go here; contact us to change it." />
                                </p>
                            </div>
                        @else
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <x-bi th="ข้อมูลยืนยันตัวตนของคุณยังไม่มีบัญชีรับเงิน กรอกบัญชีที่ชื่อตรงกับผู้ที่ยืนยันตัวตนไว้ — ชื่อไม่ตรง แอดมินจะไม่อนุมัติ"
                                      en="Your verification has no payout account. Enter one in your verified name; a mismatch will not be approved." />
                            </p>
                            <div class="mt-3 flex gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="method" value="bank" x-model="method"> <x-bi th="บัญชีธนาคาร" en="Bank account" /></label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="method" value="promptpay" x-model="method"> <x-bi th="พร้อมเพย์" en="PromptPay" /></label>
                            </div>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="method === 'bank'">
                                <div>
                                    <select name="bank_code" class="{{ $input }}">
                                        <option value="">— เลือกธนาคาร —</option>
                                        @foreach ($banks as $code => $label)
                                            <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('bank_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <input type="text" name="account_number" value="{{ old('account_number') }}" inputmode="numeric" autocomplete="off" maxlength="30" placeholder="เลขบัญชี" class="{{ $input }}">
                                    @error('account_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="mt-3" x-show="method === 'promptpay'" x-cloak>
                                <input type="text" name="promptpay_id" value="{{ old('promptpay_id') }}" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="เบอร์มือถือหรือเลขบัตรที่ผูกพร้อมเพย์" class="{{ $input }}">
                                @error('promptpay_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="mt-3">
                                <input type="text" name="account_name" value="{{ old('account_name') }}" autocomplete="off" maxlength="150" placeholder="ชื่อบัญชี (ต้องตรงกับชื่อที่ยืนยันตัวตน)" class="{{ $input }}">
                                @error('account_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                @error('method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="amount" class="font-semibold text-gray-900 dark:text-white"><x-bi th="ยอดที่ต้องการถอน (บาท)" en="Amount (THB)" /></label>
                        <div class="mt-2 flex gap-2">
                            <input id="amount" type="text" name="amount" x-model="amount" inputmode="decimal" autocomplete="off" required maxlength="20"
                                   placeholder="{{ \App\Support\Satang::toBaht($minSatang) }}" class="{{ $input }} text-lg tabular-nums">
                            <button type="button" @click="amount = @js(\App\Support\Satang::toBaht($availableSatang))"
                                    class="shrink-0 px-4 rounded-xl border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <x-bi th="ถอนทั้งหมด" en="All" />
                            </button>
                        </div>
                        @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <x-bi th="ขั้นต่ำ" en="Minimum" /> {{ $fmt($minSatang) }} · <x-bi th="ถอนได้สูงสุด" en="Up to" /> {{ $fmt($availableSatang) }} · <x-bi th="ครั้งละหนึ่งคำขอ" en="one request at a time" />
                        </p>
                    </div>

                    @if ($availableSatang < $minSatang)
                        <p class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            <x-bi :th="'ยอดในกระเป๋ายังไม่ถึงขั้นต่ำ ' . $fmt($minSatang)" :en="'Your balance is below the ' . $fmt($minSatang) . ' minimum.'" />
                        </p>
                    @endif

                    <button type="submit" x-bind:disabled="sending || @js(! $canRequest)" @disabled(! $canRequest)
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold shadow-lg hover:shadow-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="! sending"><x-bi th="ขอถอนเงิน" en="Request withdrawal" /></span>
                        <span x-show="sending" x-cloak><x-bi th="กำลังส่งคำขอ…" en="Sending…" /></span>
                    </button>
                </form>
            @endif

            {{-- ══════════ ประวัติคำขอ ══════════ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white"><x-bi th="ประวัติการถอน" en="Withdrawal history" /></h2>
                </div>
                @if ($history->isEmpty())
                    <div class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        <x-bi th="ยังไม่เคยขอถอนเงิน" en="No withdrawals yet" />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-2.5 font-medium"><x-bi th="เลขที่ / ขอเมื่อ" en="Request" /></th>
                                    <th class="px-3 py-2.5 font-medium text-right"><x-bi th="ยอด" en="Amount" /></th>
                                    <th class="px-3 py-2.5 font-medium"><x-bi th="โอนเข้า" en="To" /></th>
                                    <th class="px-6 py-2.5 font-medium"><x-bi th="สถานะ" en="Status" /></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($history as $row)
                                    <tr class="align-top">
                                        <td class="px-6 py-3">
                                            <span class="block font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row->withdrawal_id }}</span>
                                            <span class="block text-xs text-gray-400 tabular-nums">{{ $bkk($row->created_at) }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-right font-semibold tabular-nums whitespace-nowrap {{ in_array($row->status, ['rejected', 'cancelled'], true) ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white' }}">
                                            {{ $fmt($row->amountSatang()) }}
                                        </td>
                                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ $row->maskedDestination() }}</td>
                                        <td class="px-6 py-3">
                                            <span class="inline-block text-xs px-2 py-0.5 rounded-full border whitespace-nowrap {{ $tone($row->status) }}">{{ $row->statusLabel() }}</span>
                                            @if ($row->status === 'approved')
                                                @if ($row->payout_reference)
                                                    <span class="block mt-1 text-[11px] text-gray-500 dark:text-gray-400"><x-bi th="อ้างอิง" en="Ref" /> {{ $row->payout_reference }}</span>
                                                @endif
                                                @if ($row->slip_path)
                                                    <a href="{{ route('user.wallet.withdraw.slip', $row->id) }}" target="_blank" rel="noopener" class="block mt-0.5 text-[11px] text-emerald-600 hover:text-emerald-700"><x-bi th="ดูสลิปการโอน" en="View slip" /></a>
                                                @endif
                                            @elseif ($row->status === 'rejected' && $row->reject_reason)
                                                <span class="block mt-1 text-[11px] text-gray-500 dark:text-gray-400 max-w-[16rem] break-words">{{ $row->reject_reason }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($history->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $history->links() }}</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ══════════ เงินเดินทางอย่างไร ══════════ --}}
        <aside class="space-y-4">
            <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="font-semibold text-gray-900 dark:text-white"><x-bi th="ถอนเงินทำงานอย่างไร" en="How withdrawals work" /></h2>
                <ol class="mt-3 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <li><span class="font-semibold text-gray-900 dark:text-white">1.</span> <x-bi th="ขอถอน — ยอดถูกพักไว้ในกระเป๋าทันที" en="Request: the amount is held in your wallet at once" /></li>
                    <li><span class="font-semibold text-gray-900 dark:text-white">2.</span> <x-bi th="แอดมินตรวจ แล้วเริ่มโอนเข้าบัญชีที่ยืนยันตัวตนไว้ — ตั้งแต่ตอนนี้ยกเลิกไม่ได้แล้ว" en="We check it and start the transfer to your verified account; from then on it cannot be cancelled" /></li>
                    <li><span class="font-semibold text-gray-900 dark:text-white">3.</span> <x-bi th="สถานะเปลี่ยนเป็น “โอนเข้าบัญชีแล้ว” พร้อมเลขอ้างอิงหรือสลิป" en="The status turns to “Transferred” with a reference or slip" /></li>
                </ol>
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    <x-bi th="ไม่อนุมัติหรือยกเลิกเอง ยอดที่พักไว้คืนเข้ากระเป๋าทันที · รายได้จากการแชร์การ์ดจอ GPUxMINE และค่าแนะนำเข้ากระเป๋านี้หลังพ้นระยะพัก แล้วถอนได้จากหน้านี้"
                          en="If it is declined or you cancel, the held amount returns at once. GPUxMINE earnings and referral commissions reach this wallet after their hold and can be withdrawn here." />
                </p>
            </div>
        </aside>
    </div>
</div>
@endsection
