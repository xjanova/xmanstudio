@extends($publicLayout ?? 'layouts.app')

@php
    $published = $article->published_at ?? $article->created_at;
    $modified = $article->updated_at ?? $published;
    $cover = $article->coverUrl();
    $related_items = collect($article->related ?? [])->filter(fn ($r) => ! empty($r['url']) && ! empty($r['name']))->values();

    $ld = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'หน้าแรก', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'บทความ', 'item' => route('articles.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $article->categoryLabel(), 'item' => route('articles.index', ['category' => $article->category])],
                    ['@type' => 'ListItem', 'position' => 4, 'name' => $article->title, 'item' => $article->url()],
                ],
            ],
            array_filter([
                '@type' => 'BlogPosting',
                'headline' => \Illuminate\Support\Str::limit($article->title, 110, ''),
                'description' => $article->excerpt,
                'image' => $cover ? [$cover] : null,
                'datePublished' => $published->toIso8601String(),
                'dateModified' => $modified->toIso8601String(),
                'inLanguage' => 'th',
                'wordCount' => $article->word_count ?: null,
                'articleSection' => $article->categoryLabel(),
                'keywords' => $article->tags ? implode(', ', $article->tags) : null,
                'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $article->url()],
                'author' => ['@type' => 'Organization', 'name' => 'ทีมงาน XMAN Studio', 'url' => route('about')],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'XMAN Studio',
                    'url' => url('/'),
                    'logo' => ['@type' => 'ImageObject', 'url' => \App\Support\BrandLogo::markUrl() ?: url('/favicon.ico')],
                ],
            ]),
        ],
    ];
@endphp

@section('title', $article->title . ' | XMAN Studio')
@section('meta_description', $article->excerpt ?: \Illuminate\Support\Str::limit(\App\Support\ArticleHtml::text($article->body), 155))
@if($cover)
    @section('og_image', $cover)
@endif

@push('styles')
<style>
    .kb-body { color: #d1d5db; font-size: 1.0625rem; line-height: 1.9; }
    .kb-body > * + * { margin-top: 1.1em; }
    .kb-body h2 { color: #fff; font-size: 1.5rem; font-weight: 800; line-height: 1.4; margin-top: 2.2em; scroll-margin-top: 6rem; }
    .kb-body h3 { color: #f3f4f6; font-size: 1.2rem; font-weight: 700; line-height: 1.45; margin-top: 1.8em; scroll-margin-top: 6rem; }
    .kb-body a { color: #93c5fd; text-decoration: underline; text-underline-offset: 3px; }
    .kb-body a:hover { color: #bfdbfe; }
    .kb-body strong { color: #fff; }
    .kb-body ul { list-style: disc; padding-left: 1.4em; }
    .kb-body ol { list-style: decimal; padding-left: 1.4em; }
    .kb-body li + li { margin-top: .4em; }
    .kb-body blockquote { border-left: 4px solid #6366f1; background: rgba(99,102,241,.08); padding: .8em 1.1em; border-radius: 0 .75rem .75rem 0; color: #e5e7eb; }
    .kb-body code { background: rgba(255,255,255,.08); padding: .1em .4em; border-radius: .35rem; font-size: .9em; }
    .kb-body pre { background: #0b1020; border: 1px solid rgba(255,255,255,.08); padding: 1em; border-radius: .75rem; overflow-x: auto; }
    .kb-body pre code { background: none; padding: 0; }
    .kb-body table { width: 100%; border-collapse: collapse; font-size: .95em; display: block; overflow-x: auto; }
    .kb-body th, .kb-body td { border: 1px solid rgba(255,255,255,.12); padding: .55em .8em; text-align: left; }
    .kb-body th { background: rgba(255,255,255,.06); color: #fff; }
    .kb-body hr { border-color: rgba(255,255,255,.1); }
</style>
@endpush

@section('content')
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    @if(! $article->isLive())
        <div class="bg-amber-500/20 border-b border-amber-500/40 text-amber-200 text-sm text-center py-2 px-4">
            ตัวอย่างสำหรับแอดมิน — บทความนี้ยังไม่เผยแพร่ (สถานะ: {{ $article->status }})
        </div>
    @endif

    <article class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <nav aria-label="breadcrumb" class="text-xs sm:text-sm text-gray-400 mb-5 flex flex-wrap gap-x-1.5">
            <a href="{{ url('/') }}" class="hover:text-white">หน้าแรก</a><span>/</span>
            <a href="{{ route('articles.index') }}" class="hover:text-white">บทความ</a><span>/</span>
            <a href="{{ route('articles.index', ['category' => $article->category]) }}" class="hover:text-white">{{ $article->categoryLabel() }}</a>
        </nav>

        <header class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-4">{{ $article->title }}</h1>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-400">
                <span>โดย <a href="{{ route('about') }}" class="text-gray-200 hover:text-white">ทีมงาน XMAN Studio</a></span>
                <span>·</span>
                <time datetime="{{ $published->toIso8601String() }}">{{ $published->locale('th')->translatedFormat('j F Y') }}</time>
                <span>·</span>
                <span>อ่าน {{ $article->reading_minutes }} นาที</span>
                @if($article->rating_count > 0)
                    <span>·</span>
                    <span class="inline-flex items-center gap-1">
                        <x-star-rating :rating="(int) round($article->ratingAverage())" size="w-4 h-4" />
                        {{ number_format($article->ratingAverage(), 1) }} ({{ $article->rating_count }} คะแนน)
                    </span>
                @endif
            </div>
        </header>

        @if($cover)
            <img src="{{ $cover }}" alt="{{ $article->title }}" width="1200" height="630" decoding="async"
                 class="w-full rounded-2xl border border-gray-700/60 mb-8 aspect-[1200/630] object-cover">
        @endif

        @if(count($headings) >= 3)
            <nav class="mb-8 rounded-2xl border border-gray-700/60 bg-gray-800/40 p-5" aria-label="สารบัญ">
                <p class="text-sm font-bold text-white mb-2">ในบทความนี้</p>
                <ol class="space-y-1.5 text-sm">
                    @foreach($headings as $h)
                        <li class="{{ $h['level'] === 3 ? 'pl-4' : '' }}">
                            <a href="#{{ $h['id'] }}" class="text-gray-300 hover:text-primary-300">{{ $h['text'] }}</a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="kb-body">{!! $article->body !!}</div>

        @if($related_items->isNotEmpty())
            <aside class="mt-12 rounded-2xl border border-primary-500/40 bg-gradient-to-br from-primary-900/30 to-purple-900/20 p-6" aria-label="ตัวช่วยจาก XMAN Studio">
                <p class="text-sm text-primary-300 font-semibold mb-1">อยากลงมือทำเรื่องนี้จริง?</p>
                <p class="text-white font-bold text-lg mb-4">ตัวช่วยจาก XMAN Studio ที่เกี่ยวกับบทความนี้</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($related_items as $r)
                        <a href="{{ $r['url'] }}" class="block rounded-xl bg-gray-900/60 border border-gray-700 hover:border-primary-400 p-4 transition">
                            <span class="block text-white font-semibold">{{ $r['name'] }}</span>
                            @if(! empty($r['blurb']))
                                <span class="block text-sm text-gray-400 mt-1">{{ $r['blurb'] }}</span>
                            @endif
                            <span class="block text-sm text-primary-300 mt-2">ดูรายละเอียด →</span>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif

        {{-- Stars: one vote per reader, changing it replaces it (ArticleController::rate). --}}
        @if($article->isLive())
        <section class="mt-12 rounded-2xl border border-gray-700/60 bg-gray-800/40 p-6 text-center"
                 x-data="{
                    mine: {{ (int) ($myStars ?? 0) }}, hover: 0, avg: {{ $article->ratingAverage() }}, count: {{ (int) $article->rating_count }},
                    busy: false, note: '', failed: false,
                    async rate(n) {
                        if (this.busy) return;
                        this.busy = true; this.note = ''; this.failed = false;
                        try {
                            const r = await fetch(@js(route('articles.rate', $article->slug)), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                                body: JSON.stringify({ stars: n }),
                            });
                            const d = await r.json().catch(() => ({}));
                            if (r.ok && d.success) { this.mine = d.mine; this.avg = d.average; this.count = d.count; this.note = d.message; }
                            else if (r.status === 419) { this.failed = true; this.note = 'หน้านี้เปิดไว้นานจนหมดอายุ กรุณารีเฟรชหน้าแล้วให้ดาวอีกครั้ง'; }
                            else if (r.status === 429) { this.failed = true; this.note = 'ให้คะแนนถี่เกินไป กรุณารอสักครู่แล้วลองใหม่'; }
                            else { this.failed = true; this.note = d.message || ('ให้คะแนนไม่สำเร็จ (รหัส ' + r.status + ')'); }
                        } catch (e) { this.failed = true; this.note = 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจอินเทอร์เน็ตแล้วลองใหม่'; }
                        this.busy = false;
                    }
                 }">
            <p class="text-white font-bold mb-1">บทความนี้มีประโยชน์แค่ไหน?</p>
            <p class="text-sm text-gray-400 mb-3">Rate this article</p>
            <div class="inline-flex gap-1" role="radiogroup" aria-label="ให้คะแนน 1 ถึง 5 ดาว" @mouseleave="hover = 0">
                <template x-for="n in 5" :key="n">
                    <button type="button" role="radio" :aria-checked="mine === n" :aria-label="n + ' ดาว'" :disabled="busy"
                            @mouseenter="hover = n" @click="rate(n)" class="p-1 disabled:opacity-60">
                        <svg class="w-9 h-9 transition" :class="(hover || mine) >= n ? 'text-amber-400' : 'text-gray-600'" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </button>
                </template>
            </div>
            <p class="text-sm text-gray-400 mt-2" x-show="count > 0" x-cloak>
                เฉลี่ย <span x-text="Number(avg).toFixed(1)"></span> จาก <span x-text="count"></span> คะแนน
                <template x-if="mine > 0"><span> · คุณให้ <span x-text="mine"></span> ดาว</span></template>
            </p>
            <p class="text-sm mt-2" :class="failed ? 'text-red-400' : 'text-emerald-400'" x-show="note" x-text="note" x-cloak role="status"></p>
        </section>
        @endif

        @if($article->tags)
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach($article->tags as $tag)
                    <span class="px-3 py-1 rounded-full bg-gray-800 border border-gray-700 text-xs text-gray-300">#{{ $tag }}</span>
                @endforeach
            </div>
        @endif
    </article>

    @if($related->isNotEmpty())
        <section class="border-t border-gray-700/50 py-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-bold text-white mb-6">อ่านต่อ</h2>
                <div class="grid gap-6 sm:grid-cols-3">
                    @foreach($related as $a)
                        <a href="{{ $a->url() }}" class="block rounded-2xl bg-gray-800/60 border border-gray-700/60 hover:border-primary-500/60 p-5 transition">
                            <span class="text-xs text-primary-300">{{ $a->categoryLabel() }}</span>
                            <span class="block text-white font-semibold mt-1 leading-snug">{{ $a->title }}</span>
                            <span class="block text-sm text-gray-400 mt-2 line-clamp-2">{{ $a->excerpt }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
