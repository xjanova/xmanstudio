@extends($publicLayout ?? 'layouts.app')

@php
    $catName = $category ? \App\Models\Article::CATEGORIES[$category][0] : null;
    $pageTitle = $catName
        ? $catName . ' — บทความไอทีน่ารู้ | XMAN Studio'
        : 'บทความไอทีน่ารู้ — AI เว็บไซต์ ความปลอดภัย และเทคโนโลยีสำหรับธุรกิจ | XMAN Studio';
    $pageDesc = $catName
        ? 'รวมบทความหมวด' . $catName . ' จาก XMAN Studio อธิบายเข้าใจง่าย อัปเดตตามเรื่องที่คนไทยกำลังค้นหา พร้อมวิธีนำไปใช้จริงกับงานและธุรกิจของคุณ'
        : 'คลังความรู้ไอทีภาษาไทยจาก XMAN Studio: AI, การทำเว็บไซต์และแอป, ความปลอดภัยไซเบอร์, Blockchain, เซิร์ฟเวอร์และคลาวด์ อธิบายเข้าใจง่าย อัปเดตตามเทรนด์ที่คนไทยค้นหา';
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDesc)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <section class="relative py-12 sm:py-16 overflow-hidden">
        <x-page-art art="hero-products" :opacity="30" />
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <nav aria-label="breadcrumb" class="text-xs sm:text-sm text-gray-400 mb-4">
                <a href="{{ url('/') }}" class="hover:text-white">หน้าแรก</a>
                <span class="mx-1.5">/</span>
                @if($catName)
                    <a href="{{ route('articles.index') }}" class="hover:text-white">บทความ</a>
                    <span class="mx-1.5">/</span>
                    <span class="text-gray-200">{{ $catName }}</span>
                @else
                    <span class="text-gray-200">บทความ</span>
                @endif
            </nav>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-4">
                {{ $catName ?? 'บทความไอทีน่ารู้' }}
            </h1>
            <p class="text-base sm:text-lg text-gray-300 max-w-2xl mx-auto">
                เรื่องไอทีที่คนไทยกำลังค้นหา อธิบายให้เข้าใจง่าย และบอกวิธีนำไปใช้กับงานจริง
                <span class="block text-sm text-gray-400 mt-1">IT knowledge in plain Thai, updated with what people search for</span>
            </p>
        </div>
    </section>

    @if($counts->isNotEmpty())
    <nav class="border-y border-gray-700/50 bg-gray-900/40" aria-label="หมวดบทความ">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex gap-2 overflow-x-auto">
            <a href="{{ route('articles.index') }}"
               class="shrink-0 px-3.5 py-1.5 rounded-full text-sm border {{ $category ? 'border-gray-700 text-gray-300 hover:border-gray-500' : 'border-primary-500 bg-primary-500/20 text-white' }}">
                ทั้งหมด
            </a>
            @foreach(\App\Models\Article::CATEGORIES as $key => [$th, $en])
                @if(($counts[$key] ?? 0) > 0)
                    <a href="{{ route('articles.index', ['category' => $key]) }}"
                       class="shrink-0 px-3.5 py-1.5 rounded-full text-sm border {{ $category === $key ? 'border-primary-500 bg-primary-500/20 text-white' : 'border-gray-700 text-gray-300 hover:border-gray-500' }}">
                        {{ $th }} <span class="text-gray-500">({{ $counts[$key] }})</span>
                    </a>
                @endif
            @endforeach
        </div>
    </nav>
    @endif

    <section class="py-10 sm:py-14">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($articles->isEmpty())
                <div class="text-center py-20 text-gray-400">
                    <p class="text-lg">ยังไม่มีบทความในหมวดนี้</p>
                    <p class="text-sm mt-1">บทความใหม่จะขึ้นที่นี่ทุกวัน · New articles appear here every day</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($articles as $a)
                        <article class="group flex flex-col rounded-2xl overflow-hidden bg-gray-800/60 border border-gray-700/60 hover:border-primary-500/60 transition">
                            <a href="{{ $a->url() }}" class="block aspect-[1200/630] bg-gray-900 overflow-hidden">
                                @if($a->coverUrl())
                                    <img src="{{ $a->coverUrl() }}" alt="{{ $a->title }}" loading="lazy" decoding="async" width="1200" height="630"
                                         class="w-full h-full object-cover group-hover:scale-[1.03] transition duration-500">
                                @else
                                    <div class="w-full h-full grid place-items-center bg-gradient-to-br from-primary-900/60 via-gray-900 to-purple-900/50 p-6">
                                        <span class="text-center text-white/90 font-bold text-lg leading-snug line-clamp-3">{{ $a->title }}</span>
                                    </div>
                                @endif
                            </a>
                            <div class="flex-1 flex flex-col p-5">
                                <div class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                                    <span class="text-primary-300">{{ $a->categoryLabel() }}</span>
                                    <span>·</span>
                                    <time datetime="{{ $a->published_at->toIso8601String() }}">{{ $a->published_at->locale('th')->translatedFormat('j M Y') }}</time>
                                    <span>·</span>
                                    <span>อ่าน {{ $a->reading_minutes }} นาที</span>
                                </div>
                                <h2 class="text-lg font-bold text-white leading-snug mb-2">
                                    <a href="{{ $a->url() }}" class="hover:text-primary-300">{{ $a->title }}</a>
                                </h2>
                                <p class="text-sm text-gray-400 line-clamp-3 flex-1">{{ $a->excerpt }}</p>
                                @if($a->rating_count > 0)
                                    <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                                        <x-star-rating :rating="(int) round($a->ratingAverage())" size="w-3.5 h-3.5" />
                                        {{ number_format($a->ratingAverage(), 1) }} ({{ $a->rating_count }})
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-10">{{ $articles->links() }}</div>
            @endif
        </div>
    </section>
</div>
@endsection
