@extends($customerLayout ?? 'layouts.customer')

@section('title', $domain->domain . ' · โดเมนของฉัน')
@section('page-title'){{ $domain->domain }}@endsection
@section('page-description')<x-bi th="ตั้งค่า DNS ต่ออายุ และจัดการโดเมนนี้" en="DNS, renewal and settings for this domain" />@endsection

@section('content')
@php
    $badge = $domain->statusBadge();
    $days = $domain->daysUntilExpiry();
    // กล่องพื้นอ่อนในพอร์ทัลต้องเขียนคู่ light/dark เสมอ — customer-premium
    // ทับพื้นด้วย !important แต่ไม่แตะสีตัวอักษร
    $card = 'rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700';
@endphp

<div class="space-y-6"
     x-data="dnsEditor(@js([
        'records' => collect($records)->where('editable', true)->values(),
        'locked'  => collect($records)->where('editable', false)->values(),
        'types'   => $editableTypes,
     ]))">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-900 dark:text-green-200 px-4 py-3 text-sm animate-fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm animate-fade-in">{{ session('error') }}</div>
    @endif

    <a href="{{ route('customer.domains.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <x-bi th="โดเมนทั้งหมด" en="All domains" />
    </a>

    {{-- ══════════ สรุปโดเมน ══════════ --}}
    <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900">
        <x-page-art art="hero-network" :opacity="35" :scrim="false" fade="bottom" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>
        <div class="relative px-6 sm:px-8 py-7">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div class="min-w-0">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">
                        <x-bi :th="$badge['label_th']" :en="$badge['label_en']" />
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mt-2 break-all">{{ $domain->domain }}</h1>
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-2 text-sm text-slate-300">
                        @if($domain->registered_at)
                            <span><x-bi th="จดเมื่อ" en="Registered" /> {{ $domain->registered_at->format('j M Y') }}</span>
                        @endif
                        @if($domain->expires_at)
                            <span class="{{ $days !== null && $days <= 30 ? 'text-amber-300 font-semibold' : '' }}">
                                <x-bi th="หมดอายุ" en="Expires" /> {{ $domain->expires_at->format('j M Y') }}
                                @if($days !== null && $days >= 0)
                                    ({{ $days }} <x-bi th="วัน" en="days" />)
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
                @if($renewPrice)
                    <div class="text-left lg:text-right shrink-0">
                        <p class="text-xs text-slate-400"><x-bi th="ค่าต่ออายุ" en="Renewal" /></p>
                        <p class="text-xl font-bold text-white">{{ $renewPrice }}<span class="text-sm font-normal text-slate-400">/<x-bi th="ปี" en="yr" /></span></p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($domain->status === \App\Models\DomainRegistration::STATUS_REGISTERING)
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-200">
            <p class="font-semibold mb-1"><x-bi th="กำลังจดทะเบียน" en="Registration in progress" /></p>
            <p><x-bi th="ปกติใช้เวลาไม่เกิน 15 นาที เราจะแจ้งคุณเมื่อเสร็จ และตั้งค่า DNS ได้ทันทีหลังจากนั้น"
                     en="Usually under 15 minutes. We'll let you know when it's done, and DNS opens up right after." /></p>
        </div>
    @endif

    @if($domain->isUsable())
        {{-- ══════════ ตั้งค่า DNS ══════════ --}}
        <div class="{{ $card }} overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    <x-bi th="ตั้งค่า DNS" en="DNS records" />
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    <x-bi th="ชี้โดเมนไปที่เซิร์ฟเวอร์ ตั้งค่าอีเมล หรือยืนยันความเป็นเจ้าของ · เปลี่ยนแล้วมีผลใน 5–30 นาที"
                          en="Point the domain at a server, set up mail, or verify ownership. Changes take 5–30 minutes to spread." />
                </p>
            </div>

            @if($dnsUnavailable)
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="อ่านการตั้งค่า DNS ไม่ได้ในขณะนี้ กรุณารีเฟรชอีกครั้ง หากยังไม่ได้กรุณาแจ้งทีมงาน"
                              en="Can't read the DNS settings right now. Try refreshing — if it persists, tell our team." />
                    </p>
                </div>
            @else
                <form method="POST" action="{{ route('customer.domains.dns', $domain->id) }}"
                      @submit="if (! confirmDeletions($event)) return; saving = true">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/50 text-left">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300 w-[22%]"><x-bi th="ชื่อ" en="Name" /></th>
                                    <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300 w-[14%]"><x-bi th="ชนิด" en="Type" /></th>
                                    <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300"><x-bi th="ค่า" en="Value" /></th>
                                    <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-300 w-[14%]">TTL</th>
                                    <th class="px-4 py-3 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                <template x-for="(row, i) in rows" :key="row._k">
                                    <tr>
                                        <td class="px-4 py-2.5">
                                            <input type="text" :name="`records[${i}][name]`" x-model="row.name"
                                                   placeholder="@" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-2.5">
                                            {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for: เมื่อ options
                                                 ถูกสร้างด้วย x-for ตัว x-model จะอ่านค่าตั้งแต่ก่อน
                                                 options มีอยู่จริง แล้ว select ก็ตกไปที่ตัวเลือกแรก
                                                 ทุกแถวกลายเป็น A หมด ทั้งที่ข้อมูลเป็น CNAME/MX/TXT --}}
                                            <select :name="`records[${i}][type]`" x-model="row.type"
                                                    class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                                @foreach($editableTypes as $t)
                                                    <option value="{{ $t }}">{{ $t }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <input type="text" :name="`records[${i}][content]`" x-model="row.content"
                                                   :placeholder="placeholderFor(row.type)"
                                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm py-1.5 font-mono focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <input type="number" :name="`records[${i}][ttl]`" x-model.number="row.ttl" min="60" max="604800" step="60"
                                                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button type="button" @click="remove(i)"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                                                    :aria-label="`ลบเรคคอร์ด ${row.name || '@'}`">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="rows.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                        <x-bi th="ยังไม่มีเรคคอร์ด กดเพิ่มด้านล่างเพื่อเริ่ม" en="No records yet — add one below to start" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- เตือนก่อนบันทึกเมื่อมีเรคคอร์ดหายไป — การกดบันทึกคือ
                         การลบของจริงบนอินเทอร์เน็ต ไม่ใช่แค่ลบแถวในตาราง
                         และเมลที่ตกหล่นเพราะ MX หายไปกู้คืนไม่ได้ --}}
                    <template x-if="deletions.length > 0">
                        <div class="mx-4 mt-4 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
                            <p class="font-semibold mb-1">
                                <x-bi th="การบันทึกครั้งนี้จะลบเรคคอร์ด" en="Saving will delete" />
                                <span x-text="deletions.length"></span>
                                <x-bi th="รายการ" en="record(s)" />
                            </p>
                            <ul class="font-mono text-xs space-y-0.5">
                                <template x-for="d in deletions" :key="d">
                                    <li x-text="d"></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <div class="px-4 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="add()"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <x-bi th="เพิ่มเรคคอร์ด" en="Add record" />
                            </button>
                            <button type="button" @click="addPreset('web')"
                                    class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                <x-bi th="+ ชี้ไปเว็บ" en="+ Point to a site" />
                            </button>
                            <button type="button" @click="reset()" x-show="dirty" x-cloak
                                    class="px-4 py-2 rounded-lg text-slate-500 dark:text-slate-400 text-sm hover:text-slate-700 dark:hover:text-slate-200 transition">
                                <x-bi th="ย้อนกลับ" en="Undo changes" />
                            </button>
                        </div>

                        <button type="submit" x-bind:disabled="saving || !dirty"
                                class="px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed">
                            <span x-show="!saving"><x-bi th="บันทึก DNS" en="Save DNS" /></span>
                            <span x-show="saving" x-cloak><x-bi th="กำลังบันทึก…" en="Saving…" /></span>
                        </button>
                    </div>
                </form>

                {{-- เรคคอร์ดระบบ: แสดงให้เห็น แต่แก้ไม่ได้ เพราะลบ SOA
                     ออกจากฟอร์มคือทำโซนพังแบบที่ลูกค้ากู้เองไม่ได้ --}}
                <template x-if="locked.length > 0">
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">
                            <x-bi th="เรคคอร์ดระบบ (แก้ไม่ได้)" en="System records (read-only)" />
                        </p>
                        <div class="space-y-1 font-mono text-xs text-slate-500 dark:text-slate-400">
                            <template x-for="r in locked" :key="r.name + r.type + r.content">
                                <p><span x-text="r.name"></span> <span x-text="r.type" class="font-semibold"></span> <span x-text="r.content"></span></p>
                            </template>
                        </div>
                    </div>
                </template>
            @endif
        </div>

        {{-- ══════════ Nameservers ══════════ --}}
        <div class="{{ $card }} p-6" x-data="{ open: false }">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                        <x-bi th="Nameservers" en="Nameservers" />
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="ตอนนี้ DNS ดูแลโดยเรา ถ้าอยากย้ายไปใช้ Cloudflare หรือที่อื่น เปลี่ยนได้เอง"
                              en="DNS is handled by us right now. Want Cloudflare or somewhere else? Change it yourself." />
                    </p>
                    @if($domain->nameservers)
                        <ul class="mt-3 space-y-1 font-mono text-sm text-slate-700 dark:text-slate-300">
                            @foreach($domain->nameservers as $ns)
                                <li>{{ $ns }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <button type="button" @click="open = !open"
                        class="shrink-0 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <x-bi th="เปลี่ยน" en="Change" />
                </button>
            </div>

            <form method="POST" action="{{ route('customer.domains.nameservers', $domain->id) }}" x-show="open" x-cloak class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700">
                @csrf
                <div class="rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 mb-4 text-sm text-amber-900 dark:text-amber-200">
                    <x-bi th="เมื่อเปลี่ยน nameserver ไปที่อื่น หน้าตั้งค่า DNS ด้านบนจะใช้ไม่ได้ เพราะ DNS จะไปอยู่ที่ผู้ให้บริการใหม่ · ใช้เวลาถึง 24 ชั่วโมง"
                          en="Once you point these elsewhere, the DNS panel above stops applying — your records will live with the new provider. Takes up to 24 hours." />
                </div>
                <div class="grid sm:grid-cols-2 gap-3 mb-4">
                    @for($i = 0; $i < 4; $i++)
                        <input type="text" name="nameservers[]" value="{{ $domain->nameservers[$i] ?? '' }}"
                               placeholder="ns{{ $i + 1 }}.example.com"
                               class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    @endfor
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-slate-800 dark:bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold transition">
                    <x-bi th="บันทึก nameservers" en="Save nameservers" />
                </button>
            </form>
        </div>
    @endif

    {{-- ══════════ ประวัติการต่ออายุ ══════════ --}}
    @if($renewals->isNotEmpty())
        <div class="{{ $card }} p-5">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">
                <x-bi th="ประวัติการต่ออายุ" en="Renewal history" />
            </h3>
            <div class="space-y-2">
                @foreach($renewals as $r)
                    @php
                        $tone = match ($r->status) {
                            \App\Models\DomainRegistration::STATUS_ACTIVE => 'border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20',
                            \App\Models\DomainRegistration::STATUS_REFUNDED => 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30',
                            default => 'border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20',
                        };
                    @endphp
                    <div class="rounded-xl border {{ $tone }} px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                {{ $r->created_at->format('j M Y') }}
                                <span class="font-normal text-slate-500 dark:text-slate-400">
                                    @if($r->status === \App\Models\DomainRegistration::STATUS_REFUNDED)
                                        · <x-bi th="ไม่สำเร็จ คืนเงินแล้ว" en="Failed — refunded" layout="inline" />
                                    @elseif($r->status === \App\Models\DomainRegistration::STATUS_ACTIVE)
                                        · <x-bi th="สำเร็จ" en="Renewed" layout="inline" />
                                    @else
                                        · <x-bi th="กำลังดำเนินการ" en="In progress" layout="inline" />
                                    @endif
                                </span>
                            </p>
                            @if($r->expires_at)
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <x-bi th="ต่อถึง" en="Paid through" layout="inline" /> {{ $r->expires_at->format('j M Y') }}
                                </p>
                            @endif
                        </div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white tabular-nums">
                            {{ number_format((float) $r->price_thb) }} ฿
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════ การตั้งค่า ══════════ --}}
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="{{ $card }} p-5">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /></h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                <x-bi th="ตัดจากกระเป๋าเงินก่อนหมดอายุ 30 วัน เราแจ้งล่วงหน้าทุกครั้ง"
                      en="Charged from your wallet 30 days before expiry. We always warn you first." />
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('customer.domains.auto-renew', $domain->id) }}">
                    @csrf
                    <input type="hidden" name="auto_renew" value="{{ $domain->auto_renew ? 0 : 1 }}">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $domain->auto_renew
                                ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200'
                                : 'bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700' }}">
                        @if($domain->auto_renew)
                            <x-bi th="เปิดอยู่ — กดเพื่อปิด" en="On — tap to turn off" />
                        @else
                            <x-bi th="ปิดอยู่ — กดเพื่อเปิด" en="Off — tap to turn on" />
                        @endif
                    </button>
                </form>

                {{-- ต่ออายุเองได้ตลอด ไม่ต้องรอรอบอัตโนมัติ — คนที่ปิดสวิตช์ไว้
                     ก็ยังต้องมีทางจ่ายเงินต่ออายุจากหน้านี้ --}}
                @if($canRenew && $renewPriceRaw > 0)
                    <form method="POST" action="{{ route('customer.domains.renew', $domain->id) }}"
                          onsubmit="return confirm('ต่ออายุ {{ $domain->domain }} อีก 1 ปี เป็นเงิน {{ number_format($renewPriceRaw) }} บาท จะตัดจากกระเป๋าเงินทันที ยืนยันไหม?')">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-blue-600 hover:bg-blue-500 text-white transition">
                            <x-bi th="ต่ออายุตอนนี้" en="Renew now" /> · {{ number_format($renewPriceRaw) }} ฿
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($domain->isUsable())
            <div class="{{ $card }} p-5">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ปกปิดข้อมูลใน WHOIS" en="WHOIS privacy" /></h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi th="ซ่อนชื่อ ที่อยู่ และเบอร์โทรจากฐานข้อมูลสาธารณะ"
                          en="Keeps your name, address and phone out of the public database." />
                </p>
                <form method="POST" action="{{ route('customer.domains.privacy', $domain->id) }}">
                    @csrf
                    <input type="hidden" name="privacy" value="{{ $domain->privacy_protection ? 0 : 1 }}">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $domain->privacy_protection
                                ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200'
                                : 'bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700' }}">
                        @if($domain->privacy_protection)
                            <x-bi th="เปิดอยู่ — กดเพื่อปิด" en="On — tap to turn off" />
                        @else
                            <x-bi th="ปิดอยู่ — กดเพื่อเปิด" en="Off — tap to turn on" />
                        @endif
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- ══════════ ย้ายโดเมนออก ══════════
         ให้รหัสย้ายโดยไม่ต้องอ้อนวอน โดเมนเป็นของลูกค้า ไม่ใช่ของเรา --}}
    @if($domain->isUsable())
        <div class="{{ $card }} p-5" x-data="transferCode(@js(route('customer.domains.auth-code', $domain->id)))">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ย้ายโดเมนไปผู้ให้บริการอื่น" en="Transfer to another provider" /></h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                <x-bi th="โดเมนนี้เป็นของคุณ ขอรหัสย้าย (EPP/Auth code) ได้ทุกเมื่อ ไม่มีค่าใช้จ่าย ไม่ต้องชี้แจงเหตุผล"
                      en="This domain is yours. Ask for the transfer code (EPP/Auth) any time — free, no questions asked." />
            </p>

            <template x-if="!code">
                <button type="button" @click="request()" x-bind:disabled="loading"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-50">
                    <span x-show="!loading"><x-bi th="ขอรหัสย้ายโดเมน" en="Get transfer code" /></span>
                    <span x-show="loading" x-cloak><x-bi th="กำลังขอ…" en="Requesting…" /></span>
                </button>
            </template>

            <template x-if="code">
                <div class="rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1.5"><x-bi th="รหัสย้ายโดเมน" en="Transfer code" /></p>
                    <div class="flex items-center gap-3">
                        <code class="flex-1 font-mono text-sm text-slate-900 dark:text-white break-all" x-text="code"></code>
                        <button type="button" @click="copy()" class="shrink-0 px-3 py-1.5 rounded-lg bg-slate-800 dark:bg-slate-600 text-white text-xs font-medium hover:bg-slate-700 transition">
                            <span x-show="!copied"><x-bi th="คัดลอก" en="Copy" /></span>
                            <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                        </button>
                    </div>
                </div>
            </template>

            <p x-show="error" x-cloak x-text="error" class="mt-3 text-sm text-red-600 dark:text-red-400"></p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// รหัสย้ายโดเมน — ไม่ฝังลงหน้า เพราะเป็นความลับที่ใครเปิด view-source ก็เห็น
// ต้องกดขอ แล้วเซิร์ฟเวอร์ถึงไปเอามา (และบันทึกว่าใครขอเมื่อไร)
function transferCode(endpoint) {
    return {
        code: null,
        loading: false,
        error: null,
        copied: false,

        async request() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.error = body.error || 'ขอรหัสไม่สำเร็จ กรุณาแจ้งทีมงาน';
                    return;
                }
                this.code = body.auth_code;
            } catch (e) {
                this.error = 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่';
            } finally {
                this.loading = false;
            }
        },

        copy() {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(this.code).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },
    };
}

function dnsEditor(config) {
    return {
        types: config.types,
        locked: config.locked,
        rows: [],
        original: '',
        saving: false,

        init() {
            this.rows = (config.records || []).map((r, i) => ({ ...r, _k: 'r' + i }));
            this.original = this.snapshot();
        },

        get dirty() {
            return this.snapshot() !== this.original;
        },

        // name/type ที่เคยมีแต่หายไปจากตาราง = จะถูกลบจริงเมื่อกดบันทึก
        get deletions() {
            const wanted = new Set(this.rows.map(r => `${(r.name || '@').toLowerCase()}|${r.type}`));

            return [...new Set(
                (config.records || [])
                    .map(r => `${(r.name || '@').toLowerCase()}|${r.type}`)
                    .filter(k => !wanted.has(k))
            )].map(k => {
                const [name, type] = k.split('|');
                return `${type}  ${name}`;
            });
        },

        // ลบของจริงบนอินเทอร์เน็ตต้องผ่านการยืนยันด้วยมือหนึ่งครั้ง
        confirmDeletions(event) {
            const list = this.deletions;
            if (list.length === 0) return true;

            const ok = window.confirm(
                'ยืนยันการลบ ' + list.length + ' เรคคอร์ด?\n\n' + list.join('\n') +
                '\n\nการลบมีผลทันทีกับโดเมนจริง และกู้คืนเองไม่ได้'
            );

            if (!ok) event.preventDefault();

            return ok;
        },

        snapshot() {
            return JSON.stringify(this.rows.map(r => [r.name, r.type, r.content, r.ttl]));
        },

        add(row) {
            this.rows.push({
                name: '@',
                type: 'A',
                content: '',
                ttl: 14400,
                _k: 'n' + Date.now() + Math.random(),
                ...(row || {}),
            });
        },

        // ชี้เว็บให้ครบในคลิกเดียว: โดเมนเปล่าและ www ควรไปที่เดียวกัน
        // การให้ลูกค้าเพิ่มทีละแถวคือที่มาของ "ทำไม www ใช้ไม่ได้"
        addPreset(kind) {
            if (kind === 'web') {
                this.add({ name: '@', type: 'A', content: '' });
                this.add({ name: 'www', type: 'CNAME', content: '@' });
            }
        },

        remove(i) {
            this.rows.splice(i, 1);
        },

        reset() {
            this.rows = (config.records || []).map((r, i) => ({ ...r, _k: 'r' + i }));
        },

        placeholderFor(type) {
            return {
                A: '203.0.113.10',
                AAAA: '2001:db8::1',
                CNAME: 'target.example.com',
                MX: '10 mail.example.com',
                TXT: 'v=spf1 include:_spf.example.com ~all',
                SRV: '10 5 443 sip.example.com',
                CAA: '0 issue "letsencrypt.org"',
                NS: 'ns1.example.com',
            }[type] || '';
        },
    };
}
</script>
@endpush
