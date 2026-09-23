{{-- ══════════ ต่ออายุและการชำระเงิน ══════════ --}}
<div class="{{ $card }} p-6">
    <h2 class="{{ $h2 }}"><x-bi th="ต่ออายุและการชำระเงิน" en="Renewal & billing" /></h2>

    <div class="mt-4 grid sm:grid-cols-2 gap-4">
        @if($status !== \App\Models\VpsInstance::STATUS_REFUNDED)
            <div class="{{ $tile }}">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /></h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi :th="'ตัดจากกระเป๋าเงินก่อนหมดอายุ ' . $chargeDays . ' วัน แจ้งล่วงหน้าทุกครั้ง'"
                          :en="'Charged from your wallet ' . $chargeDays . ' days before expiry — we always tell you first.'" />
                </p>
                <form method="POST" action="{{ route('customer.vps.auto-renew', $server->id) }}" data-once>
                    @csrf
                    <input type="hidden" name="auto_renew" value="{{ $server->auto_renew ? 0 : 1 }}">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $server->auto_renew
                                ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200'
                                : 'bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700' }}">
                        @if($server->auto_renew)
                            <x-bi th="เปิดอยู่ — กดเพื่อปิด" en="On — tap to turn off" />
                        @else
                            <x-bi th="ปิดอยู่ — กดเพื่อเปิด" en="Off — tap to turn on" />
                        @endif
                    </button>
                </form>
            </div>
        @endif

        <div class="{{ $tile }}">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ต่ออายุตอนนี้" en="Renew now" /></h3>
            @if($renewable)
                {{-- ต่ออายุเองได้ตลอด ไม่ต้องรอรอบอัตโนมัติ — คนที่ปิดสวิตช์ไว้ก็ต้องมีทางจ่าย --}}
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi :th="'อีก ' . $spanTh . ' เป็นเงิน ' . $renewDisplay . ' — ' . $renewBasisTh"
                          :en="'One more period for ' . $renewDisplay . ' — ' . $renewBasisEn . '.'" />
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('customer.vps.renew', $server->id) }}" data-once
                          onsubmit="return confirm(@js($confirm['renew']))">
                        @csrf
                        <button type="submit" class="{{ $btnRenew }}">
                            <x-bi th="ต่ออายุตอนนี้" en="Renew now" /> · {{ $renewDisplay }}
                        </button>
                    </form>
                    <a href="{{ route('user.wallet.topup') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="เติมเงินเข้ากระเป๋า" en="Top up wallet" /></a>
                </div>
            @elseif($pendingRenew)
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    <x-bi th="กำลังยืนยันการต่ออายุกับระบบ ไม่ต้องกดซ้ำ — วันหมดอายุจะอัปเดตภายในไม่กี่นาที"
                          en="Your renewal is being confirmed — no need to press again. The expiry date updates within minutes." />
                </p>
            @elseif(in_array($status, [\App\Models\VpsInstance::STATUS_ACTIVE, \App\Models\VpsInstance::STATUS_EXPIRED], true))
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <x-bi th="ตอนนี้ยังต่ออายุออนไลน์ไม่ได้ กรุณาติดต่อทีมงาน" en="Online renewal isn't available right now — please contact our team." />
                    <a href="{{ route('customer.support.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="แจ้งทีมงาน" en="Contact support" /></a>
                </p>
            @else
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <x-bi th="ต่ออายุได้เมื่อเซิร์ฟเวอร์พร้อมใช้งาน" en="Renewal opens once the server is up and running." />
                </p>
            @endif
        </div>
    </div>

    @if($payments->isNotEmpty())
        <h3 class="font-semibold text-slate-900 dark:text-white mt-6 mb-3"><x-bi th="ประวัติการชำระเงิน" en="Payment history" /></h3>
        <ul class="space-y-2">
            @foreach($payments as $p)
                @php
                    // สตริงคลาสเต็ม ไม่ต่อชื่อสีเอง — Tailwind เห็นเฉพาะคลาสทั้งคำ
                    $pill = match ($p->status) {
                        \App\Models\VpsPayment::STATUS_PAID => ['th' => 'ชำระแล้ว', 'en' => 'Paid', 'classes' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300'],
                        \App\Models\VpsPayment::STATUS_REFUNDED => ['th' => 'คืนเงินแล้ว', 'en' => 'Refunded', 'classes' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'],
                        default => ['th' => 'กำลังดำเนินการ', 'en' => 'Processing', 'classes' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300'],
                    };
                    $refunded = $p->status === \App\Models\VpsPayment::STATUS_REFUNDED;
                @endphp
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                            @if($p->kind === \App\Models\VpsPayment::KIND_RENEW)
                                <x-bi th="ต่ออายุ" en="Renewal" />
                            @else
                                <x-bi th="เช่าครั้งแรก" en="First rental" />
                            @endif
                            @if($p->months)
                                <span class="font-normal text-slate-500 dark:text-slate-400">· {{ $p->months }} <x-bi th="เดือน" en="mo" /></span>
                            @endif
                        </p>
                        @if($p->created_at)
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $p->created_at->copy()->timezone('Asia/Bangkok')->format('j M Y H:i') }} น.</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $pill['classes'] }}">
                            <x-bi :th="$pill['th']" :en="$pill['en']" />
                        </span>
                        <span class="text-sm font-bold tabular-nums {{ $refunded ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' }}">
                            {{ \App\Support\VpsPricing::format((float) $p->amount_thb) }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
