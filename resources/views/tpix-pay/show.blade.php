@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระด้วยเหรียญ TPIX - XMAN Studio')

@php
    $chainId = (int) config('tpix_pay.chain_id');
    $expired = ! $payment->isSettled() && in_array($payment->status, ['pending', 'expired'], true) && ! $payment->isPayable();
    $tpixConfig = [
        'status' => $payment->status,
        'label' => $payment->statusLabel(),
        'settled' => $payment->isSettled(),
        'payable' => $payment->isPayable(),
        'confirmations' => (int) $payment->confirmations,
        'required' => $requiredConfirmations,
        'txHash' => $payment->tx_hash,
        'explorerTxUrl' => $payment->explorerTxUrl(),
        'secondsLeft' => $payment->expires_at ? max(0, now()->diffInSeconds($payment->expires_at, false)) : 0,
        'statusUrl' => route('tpix-pay.status', $payment),
        'submitUrl' => route('tpix-pay.submit', $payment),
        'returnUrl' => $returnUrl,
        'address' => $payment->receive_address,
        'valueHex' => $valueHex,
        'chainIdHex' => '0x' . dechex($chainId),
        'chainName' => config('tpix_pay.chain_name'),
        'rpcUrl' => config('tpix_pay.rpc_url'),
        'explorerUrl' => rtrim(config('tpix_pay.explorer_url'), '/'),
        'csrf' => csrf_token(),
        'isOwner' => (int) $payment->user_id === (int) auth()->id(),
        'metamaskLink' => $metamaskLink,
    ];
@endphp

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950 text-white py-12">
    <x-page-art art="hero-payment" :opacity="40" />
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-amber-400 text-sm font-semibold tracking-wide">TPIX Chain · chain id {{ $chainId }}</p>
        <h1 class="mt-1 text-3xl md:text-4xl font-extrabold tracking-tight">ชำระด้วยเหรียญ TPIX</h1>
        <p class="mt-2 text-gray-400">{{ $payment->purposeLabel() }} · ฿{{ number_format((float) $payment->amount_thb, 2) }}</p>
    </div>
</section>

<div class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8" x-data="tpixPay(@js($tpixConfig))" x-init="start()">

    @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl">{{ $errors->first() }}</div>
    @endif

    {{-- Paid --}}
    <div x-show="settled" x-cloak class="mb-6 rounded-2xl border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 p-6 text-center">
        <svg class="w-14 h-14 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="mt-3 text-xl font-bold text-green-700 dark:text-green-300" x-text="label"></p>
        <p class="mt-1 text-sm text-green-700/80 dark:text-green-300/80" x-show="status === 'credited_wallet'">รายการนี้ชำระหรือยกเลิกไปก่อนหน้าแล้ว ระบบจึงเติมยอดเป็นเงินเข้า Wallet ของคุณแทน</p>
        <a :href="returnUrl" class="mt-4 inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold shadow-lg hover:from-green-700 hover:to-emerald-700 transition">ไปต่อ →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6" x-show="!settled">
        <div class="lg:col-span-3 space-y-6">

            {{-- The exact amount --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยอดที่ต้องโอน — ต้องตรงทุกหลัก</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <span class="font-mono text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 break-all">{{ $payment->tpixDisplay() }}</span>
                    <span class="text-lg font-bold text-gray-700 dark:text-gray-200">TPIX</span>
                    <button type="button" @click="copy(@js($payment->tpixDisplay()), 'amount')"
                            class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 hover:bg-amber-200 dark:hover:bg-amber-900/60 transition">
                        <span x-text="copied === 'amount' ? 'คัดลอกแล้ว ✓' : 'คัดลอกยอด'"></span>
                    </button>
                </div>
                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div class="flex justify-between sm:block"><dt class="text-gray-500 dark:text-gray-400">ยอดเงิน</dt><dd class="font-semibold text-gray-900 dark:text-white">฿{{ number_format((float) $payment->amount_thb, 2) }}</dd></div>
                    <div class="flex justify-between sm:block"><dt class="text-gray-500 dark:text-gray-400">อัตราที่ใช้</dt><dd class="font-semibold text-gray-900 dark:text-white">1 TPIX = ฿{{ $rateFormatted }}</dd></div>
                    @if($payment->price_usd)
                        <div class="flex justify-between sm:block"><dt class="text-gray-500 dark:text-gray-400">ราคาตลาด</dt><dd class="text-gray-700 dark:text-gray-300">${{ \App\Services\TpixPay\TpixMath::format((string) $payment->price_usd, 4) }} · USD/THB {{ \App\Services\TpixPay\TpixMath::format((string) $payment->usd_thb, 4) }}</dd></div>
                    @endif
                    <div class="flex justify-between sm:block"><dt class="text-gray-500 dark:text-gray-400">ที่มาของราคา</dt><dd class="text-gray-700 dark:text-gray-300">{{ ($payment->meta['fixed_price'] ?? false) ? 'ราคาคงที่ที่ร้านตั้งเป็น TPIX' : 'TPIX DEX (WTPIX/USDT) · ' . $payment->created_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') . ' น.' }}</dd></div>
                </dl>
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    ฿{{ number_format((float) $payment->amount_thb, 2) }} ÷ ฿{{ $rateFormatted }} ปัดขึ้นเป็นทศนิยม 4 ตำแหน่ง แล้วบวกเศษท้ายเฉพาะรายการนี้ — เศษท้ายคือรหัสที่ระบบใช้จับคู่การโอนของคุณ กรุณาโอนให้ตรงทุกหลัก
                </p>

                <div class="mt-4 rounded-xl px-4 py-3 text-sm"
                     :class="secondsLeft > 0 ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'">
                    <template x-if="secondsLeft > 0">
                        <span>ยอดนี้ล็อกไว้ให้คุณอีก <strong class="font-mono" x-text="countdown"></strong> นาที (ถึง {{ $payment->expires_at?->copy()->timezone('Asia/Bangkok')->format('H:i') }} น.)</span>
                    </template>
                    <template x-if="secondsLeft <= 0">
                        <span>ยอดนี้หมดเวลาแล้ว — หากโอนไปแล้วภายในเวลา วางเลขธุรกรรมด้านล่างได้ ระบบยังตรวจให้ หรือขอยอดใหม่ตามราคาปัจจุบัน</span>
                    </template>
                </div>

                @if($requoteUrl && $expired)
                    <form method="POST" action="{{ $requoteUrl }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold shadow-lg hover:from-amber-600 hover:to-orange-600 transition">ขอยอดใหม่ตามราคาปัจจุบัน</button>
                    </form>
                @endif
            </div>

            {{-- Where to send it --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">โอนเข้าที่อยู่นี้ (เครือข่าย {{ config('tpix_pay.chain_name') }}, chain id {{ $chainId }})</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <span class="font-mono text-sm sm:text-base text-gray-900 dark:text-white break-all">{{ $payment->receive_address }}</span>
                    <button type="button" @click="copy(@js($payment->receive_address), 'address')"
                            class="ml-auto inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <span x-text="copied === 'address' ? 'คัดลอกแล้ว ✓' : 'คัดลอกที่อยู่'"></span>
                    </button>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">ค่าแก๊สบน TPIX Chain เป็น 0 — ส่งยอดตามด้านบนได้เต็มจำนวน อย่าโอนจากเครือข่ายอื่น</p>

                {{-- Only while the quote can still be paid: a transfer to an expired quote waits for the admin --}}
                <template x-if="isOwner && status === 'pending' && secondsLeft > 0">
                    <div class="mt-5">
                        <button type="button" x-show="hasWallet" @click="payWithWallet()" :disabled="busy"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold shadow-lg hover:from-orange-600 hover:to-amber-600 transition disabled:opacity-60 disabled:cursor-wait">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <span x-text="busy ? 'กำลังเปิดกระเป๋า…' : 'จ่ายด้วย MetaMask'"></span>
                        </button>
                        {{-- No wallet in this browser (e.g. Chrome/Safari on a phone): open this page inside the MetaMask app --}}
                        <div x-show="!hasWallet">
                            <a :href="metamaskLink" rel="noopener"
                               class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-semibold shadow-lg hover:from-orange-600 hover:to-amber-600 transition">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                เปิดในแอป MetaMask
                            </a>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">ไม่พบกระเป๋าในเบราว์เซอร์นี้ — กดปุ่มด้านบนเพื่อเปิดหน้านี้ในแอป MetaMask บนมือถือ (เข้าสู่ระบบอีกครั้งในแอป) หรือสแกน QR ด้วยแอปกระเป๋า หรือโอนเองแล้ววางเลขธุรกรรมด้านล่าง</p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- "I paid with this transaction" --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700" x-show="isOwner && !txHash">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">วางเลขธุรกรรม (tx hash)</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">โอนแล้วระบบจะเจอเองภายในประมาณหนึ่งนาที — วางเลขธุรกรรมเพื่อให้ตรวจทันที</p>
                <form method="POST" action="{{ route('tpix-pay.submit', $payment) }}" @submit.prevent="submitHash(pasted)" class="mt-3 flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="text" name="tx_hash" x-model="pasted" required maxlength="80" placeholder="0x…" autocomplete="off" spellcheck="false"
                           class="flex-1 px-4 py-2.5 font-mono text-sm border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white">
                    <button type="submit" :disabled="busy" class="px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-semibold hover:bg-gray-700 dark:hover:bg-gray-200 transition disabled:opacity-60">ตรวจสอบ</button>
                </form>
            </div>

            <p x-show="message" x-cloak class="rounded-xl px-4 py-3 text-sm"
               :class="messageOk ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'"
               x-text="message"></p>
        </div>

        <div class="lg:col-span-2 space-y-6">
            {{-- QR (EIP-681) --}}
            @if($qrSvg)
                <div x-show="status === 'pending' && secondsLeft > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 text-center">
                    <div class="inline-block rounded-xl bg-white p-3">{!! $qrSvg !!}</div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">สแกนด้วยแอปกระเป๋าที่รองรับลิงก์ชำระ EIP-681 (เช่น MetaMask) — ที่อยู่ เครือข่าย และยอดจะถูกกรอกให้ ตรวจยอดอีกครั้งก่อนกดส่ง</p>
                </div>
            @endif

            {{-- Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สถานะ</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white" x-text="label"></p>
                <template x-if="status === 'confirming'">
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400"><span>การยืนยันบนเชน</span><span x-text="Math.min(confirmations, required) + ' / ' + required"></span></div>
                        <div class="mt-1 h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                            <div class="h-2 bg-gradient-to-r from-amber-500 to-green-500 transition-all" :style="'width:' + Math.min(100, Math.round(confirmations / required * 100)) + '%'"></div>
                        </div>
                    </div>
                </template>
                <template x-if="status === 'late'">
                    <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">ได้รับเหรียญหลังหมดเวลาชำระ — ทีมงานจะตรวจสอบแล้วดำเนินการให้ (ส่งของหรือคืนเป็นเงินใน Wallet)</p>
                </template>
                <template x-if="status === 'held'">
                    <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">ได้รับเหรียญแล้ว แต่รายการนี้ปิดไปก่อน — ทีมงานจะอนุมัติการเติมมูลค่าเหรียญเข้า Wallet ให้</p>
                </template>
                <template x-if="status === 'pending' || status === 'expired'">
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        กำลังรอการโอนจากคุณ
                    </p>
                </template>
                <a x-show="explorerTxUrl" x-cloak :href="explorerTxUrl" target="_blank" rel="noopener" class="mt-3 inline-block text-sm text-amber-600 dark:text-amber-400 hover:underline break-all" x-text="'ดูธุรกรรมบน TPIX Explorer ↗'"></a>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-5 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <p>เลขที่รายการ: <span class="font-mono">{{ $payment->reference }}</span></p>
                <p>ระบบตรวจการโอนกับ TPIX Chain โดยตรง: ต้องโอนเข้าที่อยู่ของร้าน บนเครือข่าย {{ $chainId }} ยอดตรงทุกหลัก และได้รับการยืนยัน {{ $requiredConfirmations }} บล็อก</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tpixPay(cfg) {
    return {
        ...cfg,
        busy: false,
        pasted: '',
        copied: null,
        message: '',
        messageOk: true,
        hasWallet: false,
        start() {
            this.hasWallet = typeof window.ethereum !== 'undefined';
            setInterval(() => { if (this.secondsLeft > 0) this.secondsLeft--; }, 1000);
            if (!this.settled && this.isOwner) {
                this.poll();
                this.pollTimer = setInterval(() => this.poll(), 5000);
            }
        },
        get countdown() {
            const s = Math.max(0, this.secondsLeft);
            return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
        },
        apply(d) {
            if (!d || !d.status) return;
            this.status = d.status;
            this.label = d.label;
            this.confirmations = d.confirmations;
            this.required = d.required_confirmations || this.required;
            this.txHash = d.tx_hash;
            this.explorerTxUrl = d.explorer_url;
            this.payable = d.payable;
            this.secondsLeft = d.seconds_left;
            if (d.settled) {
                this.settled = true;
                this.returnUrl = d.redirect || this.returnUrl;
                clearInterval(this.pollTimer);
                setTimeout(() => { window.location.href = this.returnUrl; }, 3000);
            }
        },
        async poll() {
            if (this.settled || document.hidden) return;
            try {
                const r = await fetch(this.statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (r.ok) this.apply(await r.json());
            } catch (e) { /* the next poll tries again */ }
        },
        copy(text, key) {
            navigator.clipboard?.writeText(text).then(() => {
                this.copied = key;
                setTimeout(() => { this.copied = null; }, 1500);
            });
        },
        async submitHash(hash) {
            hash = (hash || '').trim();
            if (!hash) return;
            this.busy = true;
            try {
                const r = await fetch(this.submitUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ tx_hash: hash }),
                });
                const d = await r.json().catch(() => ({}));
                if (r.status === 429) {
                    this.message = 'ส่งถี่เกินไป กรุณารอสักครู่แล้วลองใหม่';
                    this.messageOk = false;
                } else {
                    this.message = d.message || (d.errors ? Object.values(d.errors)[0][0] : 'ตรวจสอบไม่สำเร็จ');
                    this.messageOk = ['paid', 'credited_wallet', 'held', 'settled', 'confirming', 'late', 'unknown'].includes(d.result);
                    this.apply(d);
                }
            } catch (e) {
                this.message = 'เชื่อมต่อไม่ได้ กรุณาลองใหม่';
                this.messageOk = false;
            } finally {
                this.busy = false;
            }
        },
        async payWithWallet() {
            if (!window.ethereum) {
                // No injected wallet (a phone browser): continue inside the MetaMask app
                window.location.href = this.metamaskLink;
                return;
            }
            this.busy = true;
            this.message = '';
            try {
                const [from] = await window.ethereum.request({ method: 'eth_requestAccounts' });
                try {
                    await window.ethereum.request({ method: 'wallet_switchEthereumChain', params: [{ chainId: this.chainIdHex }] });
                } catch (e) {
                    const code = e && (e.code ?? e?.data?.originalError?.code);
                    if (code !== 4902) throw e;
                    await window.ethereum.request({
                        method: 'wallet_addEthereumChain',
                        params: [{
                            chainId: this.chainIdHex,
                            chainName: this.chainName,
                            nativeCurrency: { name: 'TPIX', symbol: 'TPIX', decimals: 18 },
                            rpcUrls: [this.rpcUrl],
                            blockExplorerUrls: [this.explorerUrl],
                        }],
                    });
                }
                const hash = await window.ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [{ from, to: this.address, value: this.valueHex }],
                });
                this.busy = false;
                await this.submitHash(hash);
            } catch (e) {
                this.message = e && e.code === 4001 ? 'คุณยกเลิกรายการในกระเป๋า' : 'ทำรายการไม่สำเร็จ: ' + ((e && e.message) || e);
                this.messageOk = false;
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
