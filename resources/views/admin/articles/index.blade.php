@extends($adminLayout ?? 'layouts.admin')

@section('title', 'บทความ (Blog)')
@section('page-title', 'บทความ — คลังความรู้ /blog')

@php
    $card = 'bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm';
    $btnPrimary = 'px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 shadow-lg transition';
    $btnGhost = 'px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 transition';
    $statusBadge = [
        'published' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'draft' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'archived' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    ];
    $statusLabel = ['published' => 'เผยแพร่', 'draft' => 'ฉบับร่าง', 'archived' => 'เก็บแล้ว'];
@endphp

@section('content')
<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 p-8 shadow-2xl">
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">บทความ (Blog)</h1>
                <p class="text-indigo-100">AI เขียนบทความไอทีจาก Google Trends + ข่าวเทคโนโลยี + สินค้าของเรา วันละ 2 รอบ (08:00 / 17:00) · ให้ความรู้ก่อน แล้วพาไปเจอสินค้าแบบเนียนๆ</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('articles.index') }}" target="_blank" class="{{ $btnGhost }}">ดูหน้า /blog ↗</a>
                <a href="{{ route('admin.articles.create') }}" class="{{ $btnPrimary }}">+ เขียนเอง</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-4">
        @foreach ([['เผยแพร่แล้ว', $stats['published']], ['ฉบับร่าง', $stats['draft']], ['ยอดอ่านรวม', number_format($stats['views'])], ['คะแนนดาวรวม', number_format($stats['ratings'])]] as [$label, $value])
            <div class="{{ $card }} !p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.articles.settings') }}" class="{{ $card }} space-y-4">
            @csrf @method('PUT')
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">เขียนอัตโนมัติ</h3>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="auto" value="1" @checked($settings['auto']) class="mt-1 rounded">
                <span class="text-sm text-gray-700 dark:text-gray-200"><b>เปิดเขียนบทความอัตโนมัติ</b><br><span class="text-gray-500">วันละ 2 เรื่อง ขึ้นเว็บและเพจตอน 12:00 และ 20:00 (เวลาไทย) ช่วงที่คนอ่านเรื่องไอทีมากที่สุด — เขียนล่วงหน้าราว 1 ชม. ครึ่ง</span></span>
            </label>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="publish" value="1" @checked($settings['publish']) class="mt-1 rounded">
                <span class="text-sm text-gray-700 dark:text-gray-200"><b>เผยแพร่อัตโนมัติ</b><br><span class="text-gray-500">ถ้าปิด บทความจะเป็นฉบับร่างให้ตรวจก่อน แล้วกดเผยแพร่เอง</span></span>
            </label>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="images" value="1" @checked($settings['images']) class="mt-1 rounded">
                <span class="text-sm text-gray-700 dark:text-gray-200"><b>วาดภาพปกและภาพประกอบ</b><br><span class="text-gray-500">ใช้คีย์ OpenAI ตัวเดียวกับที่เขียนบทความ รุ่นถูกสุด (gpt-image-1-mini คุณภาพ low) ทุกเรื่องได้ภาพปก + ภาพประกอบอย่างน้อย 2 ภาพ (ไม่เกิน 3) ภาพละไม่ถึง 1 บาท@unless($settings['has_key']) — <b class="text-red-600">ยังไม่มีคีย์ OpenAI จึงยังวาดไม่ได้</b>@endunless</span></span>
            </label>
            <label class="block text-sm text-gray-700 dark:text-gray-200">
                จำนวนบทความต่อรอบ (1–5)
                <input type="number" name="per_run" min="1" max="5" value="{{ $settings['per_run'] }}" class="{{ $input }} mt-1 max-w-[8rem]">
                <span class="block text-xs text-gray-500 mt-1">แนะนำ 1 — บทความน้อยแต่ดี Google ให้คะแนนมากกว่าบทความเยอะแต่บาง</span>
            </label>
            <button class="{{ $btnPrimary }}">บันทึก</button>
        </form>

        <form method="POST" action="{{ route('admin.articles.generate') }}" class="{{ $card }} space-y-4">
            @csrf
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">สั่งเขียนตอนนี้</h3>
            <label class="block text-sm text-gray-700 dark:text-gray-200">
                หัวข้อ (เว้นว่าง = ให้ AI เลือกจาก Google Trends / ข่าวไอที / สินค้าของเรา)
                <input type="text" name="topic" maxlength="190" class="{{ $input }} mt-1" placeholder="เช่น วิธีเลือก VPS สำหรับร้านค้าออนไลน์">
            </label>
            <label class="block text-sm text-gray-700 dark:text-gray-200">
                จำนวน
                <select name="count" class="{{ $input }} mt-1 max-w-[8rem]"><option>1</option><option>2</option><option>3</option></select>
            </label>
            <button class="{{ $btnPrimary }}">เขียนบทความ</button>

            @if ($lastRun)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-sm">
                    <div class="font-semibold text-gray-800 dark:text-gray-100 mb-1">
                        รอบล่าสุด ({{ \Illuminate\Support\Carbon::parse($lastRun['at'])->timezone('Asia/Bangkok')->format('d/m H:i') }}):
                        @if ($lastRun['state'] === 'running') <span class="text-indigo-500">กำลังเขียน… รีเฟรชหน้านี้เพื่อดูผล</span>
                        @elseif ($lastRun['state'] === 'failed') <span class="text-red-500">ไม่สำเร็จ</span>
                        @else <span class="text-green-600">เสร็จแล้ว</span> @endif
                    </div>
                    @if (! empty($lastRun['error']))<p class="text-red-600 dark:text-red-400">{{ $lastRun['error'] }}</p>@endif
                    <ul class="space-y-1">
                        @foreach ($lastRun['results'] ?? [] as $r)
                            <li>
                                @if ($r['ok'])
                                    ✓ <a class="text-indigo-600 dark:text-indigo-300 underline" href="{{ route('admin.articles.edit', $r['id']) }}">{{ $r['title'] }}</a> ({{ $statusLabel[$r['status']] ?? $r['status'] }})
                                @else
                                    <span class="text-red-600 dark:text-red-400">✗ {{ $r['topic'] }} — {{ $r['error'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>
    </div>

    <div class="{{ $card }} !p-0 overflow-hidden">
        <div class="flex flex-wrap gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
            @foreach (['' => 'ทั้งหมด', 'published' => 'เผยแพร่', 'draft' => 'ฉบับร่าง', 'archived' => 'เก็บแล้ว'] as $key => $label)
                <a href="{{ route('admin.articles.index', $key ? ['status' => $key] : []) }}"
                   class="px-3 py-1.5 rounded-full text-sm {{ ($status ?? '') === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 dark:text-gray-400 text-left">
                    <tr>
                        <th class="px-4 py-3">บทความ</th>
                        <th class="px-4 py-3">ที่มา</th>
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3 text-right">อ่าน</th>
                        <th class="px-4 py-3 text-right">ดาว</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($articles as $a)
                        <tr class="text-gray-800 dark:text-gray-100">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.articles.edit', $a->id) }}" class="font-semibold hover:text-indigo-600">{{ $a->title }}</a>
                                <div class="text-xs text-gray-500">{{ $a->categoryLabel() }} · {{ $a->word_count }} คำ · {{ optional($a->published_at ?? $a->created_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 max-w-[14rem]">
                                {{ ['trend' => 'เทรนด์/ข่าว', 'catalogue' => 'สินค้าเรา', 'manual' => 'เขียนเอง'][$a->source] ?? $a->source }}
                                @if ($a->source_topic && $a->source !== 'manual')<div class="truncate" title="{{ $a->source_topic }}">{{ $a->source_topic }}</div>@endif
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge[$a->status] ?? '' }}">{{ $statusLabel[$a->status] ?? $a->status }}</span></td>
                            <td class="px-4 py-3 text-right">{{ number_format($a->views) }}</td>
                            <td class="px-4 py-3 text-right">{{ $a->rating_count ? number_format($a->ratingAverage(), 1) . ' (' . $a->rating_count . ')' : '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ $a->url() }}" target="_blank" class="text-indigo-600 dark:text-indigo-300 hover:underline">ดู ↗</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">ยังไม่มีบทความ — กด "เขียนบทความ" ด้านบนเพื่อเริ่ม</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $articles->links() }}</div>
    </div>
</div>
@endsection
