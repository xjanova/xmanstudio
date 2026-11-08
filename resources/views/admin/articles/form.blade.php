@extends($adminLayout ?? 'layouts.admin')

@section('title', $article->exists ? 'แก้ไขบทความ' : 'เขียนบทความ')
@section('page-title', $article->exists ? 'แก้ไขบทความ' : 'เขียนบทความใหม่')

@php
    $card = 'bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700';
    $input = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm';
    $btnPrimary = 'px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 shadow-lg transition';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.articles.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">← บทความทั้งหมด</a>
        @if ($article->exists)
            <a href="{{ $article->url() }}" target="_blank" class="text-sm text-indigo-600 dark:text-indigo-300 hover:underline">ดูหน้าบทความ ↗</a>
        @endif
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $article->exists ? route('admin.articles.update', $article->id) : route('admin.articles.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div class="{{ $card }} lg:col-span-2 space-y-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">ชื่อบทความ
                <input name="title" required maxlength="200" value="{{ old('title', $article->title) }}" class="{{ $input }} mt-1 text-base font-semibold">
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">คำอธิบายสำหรับ Google (120–155 ตัวอักษร)
                <textarea name="excerpt" rows="2" maxlength="300" class="{{ $input }} mt-1">{{ old('excerpt', $article->excerpt) }}</textarea>
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">เนื้อหา (Markdown: ## หัวข้อ, **ตัวหนา**, - รายการ, [ข้อความ](ลิงก์))
                <textarea name="body_markdown" required rows="28" class="{{ $input }} mt-1 font-mono leading-relaxed">{{ old('body_markdown', $article->body_markdown) }}</textarea>
            </label>
        </div>

        <div class="space-y-6">
            <div class="{{ $card }} space-y-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">สถานะ
                    <select name="status" class="{{ $input }} mt-1">
                        @foreach (['draft' => 'ฉบับร่าง', 'published' => 'เผยแพร่', 'archived' => 'เก็บ (ซ่อนจากเว็บ)'] as $k => $l)
                            <option value="{{ $k }}" @selected(old('status', $article->status) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">หมวด
                    <select name="category" class="{{ $input }} mt-1">
                        @foreach (\App\Models\Article::CATEGORIES as $k => [$th, $en])
                            <option value="{{ $k }}" @selected(old('category', $article->category) === $k)>{{ $th }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">ลิงก์ (slug)
                    <input name="slug" maxlength="120" pattern="[a-z0-9-]+" value="{{ old('slug', $article->slug) }}" class="{{ $input }} mt-1 font-mono" placeholder="เว้นว่าง = สร้างจากชื่อ">
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">คำค้นหลัก
                    <input name="focus_keyword" maxlength="120" value="{{ old('focus_keyword', $article->focus_keyword) }}" class="{{ $input }} mt-1">
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">แท็ก (คั่นด้วย ,)
                    <input name="tags" maxlength="300" value="{{ old('tags', implode(', ', $article->tags ?? [])) }}" class="{{ $input }} mt-1">
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">ภาพปก (1200×630 · ไม่ใส่ = ระบบวาดจากชื่อเรื่อง)
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300">
                </label>
                @if ($article->exists)
                    <img src="{{ $article->coverUrl() }}" alt="" class="rounded-xl border border-gray-200 dark:border-gray-700 w-full">
                @endif
                <button class="{{ $btnPrimary }} w-full">บันทึก</button>
            </div>

            @if ($article->exists)
                <div class="{{ $card }} text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <div>ที่มา: {{ $article->source }} @if($article->source_topic) — {{ $article->source_topic }} @endif</div>
                    @if ($article->ai_model)<div>เขียนโดย: {{ $article->ai_model }}</div>@endif
                    <div>ยอดอ่าน {{ number_format($article->views) }} · ดาว {{ $article->rating_count ? number_format($article->ratingAverage(), 1) . ' จาก ' . $article->rating_count . ' คน' : 'ยังไม่มี' }}</div>
                    @if ($article->related)
                        <div>สินค้าที่แนะนำ: {{ collect($article->related)->pluck('name')->implode(', ') }}</div>
                    @endif
                </div>
            @endif
        </div>
    </form>

    @if ($article->exists)
        <form method="POST" action="{{ route('admin.articles.destroy', $article->id) }}"
              onsubmit="return confirm('ลบบทความ “{{ addslashes($article->title) }}” ถาวร? กู้คืนไม่ได้ (ถ้าแค่อยากซ่อน ให้เปลี่ยนสถานะเป็น “เก็บ” แทน)')">
            @csrf @method('DELETE')
            <button class="text-sm text-red-600 hover:underline">ลบบทความนี้</button>
        </form>
    @endif
</div>
@endsection
