@extends($publicLayout ?? 'layouts.app')

@section('title', 'ติดตามใบสั่งงาน - XMAN Studio')
@section('meta_description', 'ติดตามสถานะใบเสนอราคาและโครงการกับ XMAN Studio ด้วยเลขที่ใบเสนอราคา เลขโครงการ หรืออีเมลที่ใช้สั่งงาน')

@section('content')
@php
    $searched = isset($query);
    $found = ($quotations ?? collect())->isNotEmpty() || ($projects ?? collect())->isNotEmpty();
    // สีสถานะบนพื้นมืด: พื้นจาง ขอบบาง ตัวอักษรสว่าง — ป้ายเดิมทำมาสำหรับพื้นขาว
    $projectTone = [
        'pending' => 'bg-white/5 border-white/15 text-gray-200',
        'in_progress' => 'bg-sky-500/15 border-sky-400/40 text-sky-200',
        'on_hold' => 'bg-yellow-500/15 border-yellow-400/40 text-yellow-200',
        'review' => 'bg-purple-500/15 border-purple-400/40 text-purple-200',
        'revision' => 'bg-orange-500/15 border-orange-400/40 text-orange-200',
        'completed' => 'bg-emerald-500/15 border-emerald-400/40 text-emerald-200',
        'cancelled' => 'bg-red-500/15 border-red-400/40 text-red-200',
    ];
    $quoteTone = [
        'draft' => 'bg-white/5 border-white/15 text-gray-200',
        'sent' => 'bg-sky-500/15 border-sky-400/40 text-sky-200',
        'viewed' => 'bg-purple-500/15 border-purple-400/40 text-purple-200',
        'accepted' => 'bg-emerald-500/15 border-emerald-400/40 text-emerald-200',
        'paid' => 'bg-emerald-500/15 border-emerald-400/40 text-emerald-200',
        'expired' => 'bg-red-500/15 border-red-400/40 text-red-200',
        'rejected' => 'bg-red-500/15 border-red-400/40 text-red-200',
    ];
    $quoteLabels = [
        'draft' => 'ร่าง',
        'sent' => 'ส่งแล้ว',
        'viewed' => 'เปิดดูแล้ว',
        'accepted' => 'ยอมรับ',
        'paid' => 'ชำระแล้ว',
        'expired' => 'หมดอายุ',
        'rejected' => 'ปฏิเสธ',
    ];
    // ค้นได้สามแบบ ตามที่ trackingSearch ค้นจริง: เลขเต็มของใบหรือโครงการ ไม่ต้องล็อกอิน
    // ส่วนอีเมลแสดงเฉพาะใบของเจ้าของบัญชีที่ล็อกอินอยู่
    $ways = [
        ['th' => 'เลขที่ใบเสนอราคา', 'en' => 'Quotation number', 'eg' => 'เช่น QT-20260213-ABCD', 'd' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
        ['th' => 'เลขโครงการ', 'en' => 'Project number', 'eg' => 'เช่น PRJ-20260213-ABCD', 'd' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['th' => 'อีเมล', 'en' => 'Email', 'eg' => 'อีเมลที่ใช้สั่งงาน — ต้องเข้าสู่ระบบด้วยอีเมลนั้น', 'd' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    ];
@endphp
<x-xp.page class="dark" a="#67e8f9" b="#a78bfa" c="#7dd3fc" glow="103 232 249" g1="#a78bfa" g2="#67e8f9" g3="#f0abfc">

    {{-- ============================ HERO ============================ --}}
    <x-svc.hero :art="asset('artwork/hero-tracking.webp')" compact
                art-class="top-0 right-[-40%] w-[160%] opacity-35 sm:right-[-25%] sm:w-[120%] lg:right-[-14%] lg:w-[72%] lg:opacity-75"
                :crumbs="[
                    ['หน้าแรก', route('home')],
                    ['สั่งงาน & ใบเสนอราคา', route('quote.index')],
                    ['ติดตามสถานะงาน', null],
                ]"
                lead="ค้นหาด้วยเลขที่ใบเสนอราคา เลขโครงการ หรืออีเมลที่ใช้สั่งงาน"
                lead-en="Search by quotation number, project number or email.">
        <x-slot:chips><span class="xp-chip"><i></i>ค้นด้วยเลขเต็ม ไม่ต้องเข้าสู่ระบบ</span></x-slot:chips>
        <x-slot:title><span class="inline-block">ติดตาม</span><span class="xp-text-glow inline-block">ใบสั่งงาน</span></x-slot:title>

        <form method="GET" action="{{ route('quote.track.search') }}" class="svc-surface rounded-2xl p-2 flex flex-col sm:flex-row gap-2" role="search">
            <label for="track-query" class="sr-only">เลขที่ใบเสนอราคา เลขโครงการ หรืออีเมล</label>
            <div class="flex-1 relative min-w-0">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="track-query" type="text" name="query" value="{{ old('query', $query ?? '') }}" required minlength="3" maxlength="100"
                       placeholder="QT-20260213-ABCD / PRJ-20260213-ABCD / email@example.com"
                       @error('query') aria-invalid="true" aria-describedby="track-query-error" @enderror
                       class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-black/30 border border-white/10 text-white placeholder-gray-500 text-sm focus:border-cyan-300/60 focus:ring-2 focus:ring-cyan-300/30 transition">
            </div>
            <button type="submit" class="xp-btn-main inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl font-extrabold whitespace-nowrap transition">
                ค้นหา
            </button>
        </form>
        @error('query')
            <p id="track-query-error" class="mt-2 text-sm text-red-300">{{ $message }}</p>
        @enderror
    </x-svc.hero>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 -mt-6 scroll-mt-20" id="results">

        @if($searched)
            @unless($found)
                {{-- ไม่พบ --}}
                <div class="svc-surface rounded-2xl px-6 py-14 text-center">
                    <svg class="w-14 h-14 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-white text-lg font-bold">ไม่พบข้อมูลของ “{{ $query }}”</p>
                    <p class="text-gray-400 text-sm mt-1">ลองค้นหาด้วยเลขที่ใบเสนอราคา, เลขโครงการ หรืออีเมลที่ใช้</p>
                </div>
            @endunless

            {{-- ========================= PROJECTS ========================= --}}
            @if(isset($projects) && $projects->isNotEmpty())
                <section class="mb-12">
                    <h2 class="text-2xl font-black text-white mb-5 flex items-center gap-3">
                        <span class="svc-icon !w-10 !h-10 !text-base !rounded-xl">
                            <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </span>
                        โครงการ
                        <span class="text-sm font-semibold text-gray-500">{{ $projects->count() }}</span>
                    </h2>
                    <div class="space-y-5">
                        @foreach($projects as $project)
                            @php $pct = max(0, min(100, (int) ($project->progress_percent ?? 0))); @endphp
                            <article class="svc-surface rounded-2xl p-6 sm:p-7">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
                                    <div class="min-w-0">
                                        <p class="font-mono text-sm font-semibold xp-link">{{ $project->project_number }}</p>
                                        <h3 class="text-xl font-bold text-white break-words">{{ $project->project_name }}</h3>
                                    </div>
                                    <span class="self-start shrink-0 px-3 py-1 text-sm font-semibold rounded-full border {{ $projectTone[$project->status] ?? $projectTone['pending'] }}">
                                        {{ \App\Models\ProjectOrder::STATUS_LABELS[$project->status] ?? $project->status }}
                                    </span>
                                </div>

                                {{-- ความคืบหน้า --}}
                                <div class="mb-6">
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-gray-400">ความคืบหน้า</span>
                                        <span class="font-black text-white tabular-nums">{{ $pct }}%</span>
                                    </div>
                                    <div class="w-full h-3 rounded-full bg-white/10 overflow-hidden" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="ความคืบหน้า">
                                        <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: linear-gradient(90deg, var(--xp-a), var(--xp-b)); box-shadow: 0 0 18px -2px color-mix(in srgb, var(--xp-a) 70%, transparent)"></div>
                                    </div>
                                </div>

                                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <dt class="text-gray-500">ประเภท</dt>
                                        <dd class="font-semibold text-gray-100">{{ \App\Models\ProjectOrder::TYPE_LABELS[$project->project_type] ?? $project->project_type }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">เริ่มงาน</dt>
                                        <dd class="font-semibold text-gray-100">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">กำหนดส่ง</dt>
                                        <dd class="font-semibold text-gray-100">{{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-500">มูลค่า</dt>
                                        <dd class="font-black text-white tabular-nums">฿{{ number_format($project->total_price, 0) }}</dd>
                                    </div>
                                </dl>

                                {{-- ฟีเจอร์ --}}
                                @if($project->features->isNotEmpty())
                                    <div class="mt-6 pt-5 border-t border-white/10">
                                        <p class="text-sm font-bold text-gray-200 mb-3">ฟีเจอร์</p>
                                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($project->features as $feature)
                                                <li class="flex items-center gap-2.5 text-sm">
                                                    @if($feature->status === 'completed')
                                                        <span class="xp-tick !mt-0 !w-5 !h-5 !text-[.65rem]" aria-label="เสร็จแล้ว">✓</span>
                                                    @elseif($feature->status === 'in_progress')
                                                        <svg class="w-5 h-5 text-cyan-300 shrink-0 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-label="กำลังทำ">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                    @else
                                                        <span class="w-5 h-5 rounded-full border-2 border-white/20 shrink-0" aria-label="ยังไม่เริ่ม"></span>
                                                    @endif
                                                    <span class="{{ $feature->status === 'completed' ? 'line-through text-gray-500' : 'text-gray-200' }}">{{ $feature->name }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- อัปเดตล่าสุด --}}
                                @if($project->progress->isNotEmpty())
                                    <div class="mt-6 pt-5 border-t border-white/10">
                                        <p class="text-sm font-bold text-gray-200 mb-3">อัปเดตล่าสุด</p>
                                        <ol class="space-y-3">
                                            @foreach($project->progress as $update)
                                                <li class="flex items-start gap-3 text-sm">
                                                    <span class="xp-dot mt-1.5"></span>
                                                    <div>
                                                        <p class="text-gray-200">{{ $update->description }}</p>
                                                        <p class="text-xs text-gray-500">{{ $update->created_at->diffForHumans() }}</p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ======================== QUOTATIONS ======================== --}}
            @if(isset($quotations) && $quotations->isNotEmpty())
                <section>
                    <h2 class="text-2xl font-black text-white mb-5 flex items-center gap-3">
                        <span class="svc-icon !w-10 !h-10 !text-base !rounded-xl">
                            <svg class="w-5 h-5 text-violet-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        ใบเสนอราคา
                        <span class="text-sm font-semibold text-gray-500">{{ $quotations->count() }}</span>
                    </h2>
                    <div class="space-y-3">
                        @foreach($quotations as $quote)
                            <article class="svc-surface rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-sm font-semibold text-violet-200">{{ $quote->quote_number }}</p>
                                    <p class="text-white font-semibold break-words">{{ $quote->service_name }}</p>
                                    <p class="text-sm text-gray-400">{{ $quote->customer_name }} &middot; {{ $quote->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full border {{ $quoteTone[$quote->status] ?? $quoteTone['draft'] }}">
                                        {{ $quoteLabels[$quote->status] ?? $quote->status }}
                                    </span>
                                    <p class="font-black text-lg text-white whitespace-nowrap tabular-nums">฿{{ number_format($quote->grand_total, 0) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            {{-- ======================= HOW TO SEARCH ======================= --}}
            <ul class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($ways as $way)
                    <li class="xp-card p-6 flex items-start gap-4 transition">
                        <span class="svc-icon !w-11 !h-11 !rounded-xl shrink-0">
                            <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $way['d'] }}"/></svg>
                        </span>
                        <div>
                            <h3 class="font-bold text-white">{{ $way['th'] }} <span class="text-xs font-normal text-gray-500">{{ $way['en'] }}</span></h3>
                            <p class="text-sm text-gray-400 mt-1">{{ $way['eg'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="text-center mt-12 flex flex-wrap justify-center gap-3">
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                กลับหน้าสั่งซื้อ/ใบเสนอราคา
            </a>
        </div>
    </div>
</x-xp.page>
@endsection
