@extends($publicLayout ?? 'layouts.app')

@section('title', 'ราคาจดโดเมน | XMAN Studio')
@section('meta_description', 'ราคาจดทะเบียนและต่ออายุโดเมนทุกนามสกุลที่เราให้บริการ ราคารวมทุกอย่าง ไม่มีค่าธรรมเนียมแอบแฝง')

@section('content')
<section class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
    <x-page-art art="hero-domains" :opacity="35" :scrim="false" fade="bottom" />
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16 text-center">
        <span class="inline-block px-4 py-1.5 bg-indigo-600/30 text-indigo-200 text-xs font-semibold rounded-full mb-5 backdrop-blur-sm border border-indigo-400/30 tracking-[0.2em] uppercase">
            Pricing
        </span>
        <h1 class="text-3xl sm:text-4xl font-bold mb-3">
            <x-bi th="ราคาจดโดเมน" en="Domain pricing" layout="stack" />
        </h1>
        <p class="text-slate-300 max-w-2xl mx-auto">
            <x-bi th="ราคาต่อปี รวมทุกอย่างแล้ว ไม่มีค่าธรรมเนียมแอบแฝง และไม่มีค่าใช้จ่ายตอนย้ายออก"
                  en="Per year, all in. No hidden fees, and nothing to pay if you transfer away." />
        </p>
    </div>
</section>

<section class="bg-gray-50 dark:bg-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        @if($tlds->isEmpty())
            <div class="text-center py-16">
                <p class="text-slate-600 dark:text-slate-300 font-semibold mb-2">
                    <x-bi th="ยังไม่เปิดให้บริการ" en="Not open yet" />
                </p>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <x-bi th="กำลังเตรียมรายการนามสกุลโดเมน กลับมาดูใหม่เร็ว ๆ นี้"
                          en="We're preparing the extension list — check back soon." />
                </p>
            </div>
        @else
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="text-left px-5 py-3.5 font-semibold text-slate-700 dark:text-slate-200">
                                    <x-bi th="นามสกุล" en="Extension" />
                                </th>
                                <th class="text-right px-5 py-3.5 font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                    <x-bi th="จดใหม่ / ปี" en="Register / yr" />
                                </th>
                                <th class="text-right px-5 py-3.5 font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                    <x-bi th="ต่ออายุ / ปี" en="Renew / yr" />
                                </th>
                                <th class="px-5 py-3.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($tlds as $t)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 dark:text-white">.{{ $t['tld'] }}</span>
                                            @if($t['featured'])
                                                <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-[11px] font-semibold">
                                                    <x-bi th="ยอดนิยม" en="Popular" />
                                                </span>
                                            @endif
                                        </div>
                                        @if($t['description_th'] || $t['description_en'])
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                <x-bi :th="$t['description_th'] ?? ''" :en="$t['description_en'] ?? ''" />
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                        {{ $t['register_display'] }}
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <span class="{{ $t['dearer'] ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-slate-600 dark:text-slate-300' }}">
                                            {{ $t['renew_display'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('domains.index') }}?q={{ urlencode('.'.$t['tld']) }}"
                                           class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium whitespace-nowrap">
                                            <x-bi th="ค้นหา" en="Search" /> →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ราคาต่ออายุที่ต่างจากปีแรกเป็นเรื่องปกติของวงการ แต่ลูกค้าควรรู้ตั้งแต่ก่อนซื้อ --}}
            @if($tlds->contains('dearer', true))
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 8h.01M11 12h1v4h1"/>
                    </svg>
                    <span>
                        <x-bi th="ตัวเลขสีเหลืองคือนามสกุลที่ค่าต่ออายุสูงกว่าปีแรก เราแสดงไว้ตั้งแต่ก่อนซื้อเพื่อให้คุณวางแผนได้"
                              en="Amber figures renew for more than the first year. We show it before you buy so there are no surprises." />
                    </span>
                </p>
            @endif
        @endif

        <div class="mt-10 text-center">
            <a href="{{ route('domains.index') }}"
               class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] transition">
                <x-bi th="ค้นหาโดเมนของคุณ" en="Search for your domain" />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>
@endsection
