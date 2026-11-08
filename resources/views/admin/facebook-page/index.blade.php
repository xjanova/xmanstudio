@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Facebook เพจ')
@section('page-title', 'Facebook เพจ')

@section('content')
@php
    $input = 'w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $hint = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
    $card = 'rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm';
@endphp
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 p-8 shadow-xl">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Facebook เพจบริษัท</h1>
        </div>
        <p class="text-blue-100">บทความความรู้ใหม่บน /blog จะถูกแชร์ลงเพจนี้อัตโนมัติ — ใช้แอป Meta ตัวเดียวกับเพจแม่หมอจันทรา (XmanApp)</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 px-4 py-3 text-green-800 dark:text-green-200 break-words">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200 break-words">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- One click: Facebook login dialog → the page's own token, no copying from Graph API Explorer --}}
    <div class="{{ $card }} border-blue-300 dark:border-blue-700">
        <div class="flex flex-col md:flex-row md:items-center gap-4 justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $hasToken ? 'เชื่อมเพจใหม่ / ต่ออายุ token' : 'สร้าง token ของเพจ' }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">กดปุ่ม แล้วล็อกอิน Facebook ด้วยบัญชีที่ดูแลเพจ XmanEnterprise กดอนุญาตแอป XmanApp และติ๊กเลือกเพจ — ระบบจะสร้าง Page Token แบบไม่หมดอายุแล้วเก็บให้เอง ไม่ต้องคัดลอกอะไร</p>
            </div>
            @if($canLogin)
                <a href="{{ route('admin.facebook-page.connect') }}" class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-[#1877F2] px-5 py-3 text-sm font-semibold text-white hover:bg-[#166FE5]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
                    เชื่อมเพจด้วย Facebook
                </a>
            @else
                <span class="shrink-0 rounded-xl bg-gray-100 dark:bg-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">ใส่ App ID และ App Secret ด้านล่างก่อน</span>
            @endif
        </div>
        <p class="{{ $hint }} mt-3">ครั้งแรกต้องเพิ่มลิงก์นี้ใน XmanApp: developers.facebook.com → XmanApp → Facebook Login → การตั้งค่า → Valid OAuth Redirect URIs
            <code class="select-all break-all rounded bg-gray-100 dark:bg-gray-900 px-1.5 py-0.5">{{ $callbackUrl }}</code></p>
    </div>

    {{-- Status --}}
    <div class="{{ $card }}">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">เพจ</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    @if($pageId)
                        <a href="https://www.facebook.com/{{ $pageId }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $pageName ?: $pageId }}</a>
                    @else
                        <span class="text-red-600">ยังไม่ได้ตั้ง</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Page Token</dt>
                <dd class="font-medium {{ $hasToken ? 'text-green-600 dark:text-green-400' : 'text-red-600' }}">
                    {{ $hasToken ? 'เก็บไว้แล้ว (เข้ารหัส)' : 'ยังไม่มี' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">อายุ token</dt>
                <dd class="font-medium text-gray-900 dark:text-white">
                    @if(! $hasToken) — @elseif($expiresAt) หมดอายุ {{ $expiresAt->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} @else ไม่มีวันหมดอายุ @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">โพสต์บทความอัตโนมัติ</dt>
                <dd class="font-medium {{ $autopost && $hasToken ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                    {{ $autopost ? ($hasToken ? 'เปิดอยู่' : 'เปิดอยู่ แต่ยังไม่ได้เชื่อมเพจ') : 'ปิดอยู่' }}
                </dd>
            </div>
        </dl>
        <div class="mt-5 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.facebook-page.test') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-gray-900 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50" @disabled(! $hasToken)>ทดสอบการเชื่อมต่อ</button>
            </form>
            @if($hasToken)
                <form method="POST" action="{{ route('admin.facebook-page.forget') }}" onsubmit="return confirm('ลบ Page Token ที่เก็บไว้? บทความใหม่จะไม่ถูกโพสต์ลงเพจจนกว่าจะเชื่อมใหม่')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-red-300 dark:border-red-700 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">ลบ token</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Settings --}}
    <form method="POST" action="{{ route('admin.facebook-page.update') }}" class="{{ $card }} space-y-5">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่าการเชื่อมต่อ</h2>

        <label class="flex items-center gap-3">
            <input type="hidden" name="autopost" value="0">
            <input type="checkbox" name="autopost" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600" @checked(old('autopost', $autopost))>
            <span class="text-sm text-gray-800 dark:text-gray-200">แชร์บทความใหม่ลงเพจอัตโนมัติเมื่อเผยแพร่</span>
        </label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="page_id" class="{{ $label }}">Page ID ของเพจบริษัท</label>
                <input id="page_id" name="page_id" inputmode="numeric" value="{{ old('page_id', $pageId) }}" class="{{ $input }}" placeholder="106036940932060">
                <p class="{{ $hint }}">XmanEnterprise = 106036940932060</p>
            </div>
            <div>
                <label for="app_id" class="{{ $label }}">App ID (XmanApp)</label>
                <input id="app_id" name="app_id" inputmode="numeric" value="{{ old('app_id', $appId) }}" class="{{ $input }}" placeholder="664172615253513">
            </div>
            <div>
                <label for="app_secret" class="{{ $label }}">App Secret</label>
                <input id="app_secret" name="app_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $hasAppSecret ? '•••••••• (เก็บไว้แล้ว — เว้นว่างเพื่อใช้ค่าเดิม)' : 'ใช้แลก token ให้เป็นแบบไม่หมดอายุ' }}">
                <p class="{{ $hint }}">developers.facebook.com > XmanApp > การตั้งค่าแอป > พื้นฐาน</p>
            </div>
            <div>
                <label for="token" class="{{ $label }}">User Token หรือ Page Token (เว้นว่างถ้าไม่เปลี่ยน)</label>
                <textarea id="token" name="token" rows="3" autocomplete="off" spellcheck="false" class="{{ $input }} font-mono" placeholder="EAAJ..."></textarea>
                <p class="{{ $hint }}">วาง User Token ได้เลย ระบบจะแลกเป็น token ของเพจแบบไม่หมดอายุให้เอง (ต้องใส่ App ID / Secret ก่อน)</p>
            </div>
        </div>

        <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">บันทึก</button>
    </form>

    {{-- How to --}}
    <div class="{{ $card }} text-sm text-gray-700 dark:text-gray-300 space-y-2">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">อีกทาง: วาง token เอง (ถ้าปุ่มเชื่อมเพจด้านบนใช้ไม่ได้)</h2>
        <ol class="list-decimal pl-5 space-y-1">
            <li>เปิด <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Graph API Explorer</a> ด้วยบัญชีที่เป็นผู้ดูแลเพจ XmanEnterprise</li>
            <li>ช่อง Meta App เลือก <b>XmanApp</b> · User or Page เลือก <b>User Token</b></li>
            <li>เพิ่มสิทธิ์ <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>pages_manage_posts</code> แล้วกด Generate Access Token — ในกล่องอนุญาตต้องติ๊กเพจ XmanEnterprise</li>
            <li>คัดลอก token มาวางช่องด้านบน แล้วกดบันทึก — ระบบจะแลกเป็น token ของเพจที่ไม่หมดอายุ และไม่เก็บ User Token ไว้</li>
        </ol>
        <p class="text-xs text-gray-500 dark:text-gray-400">บนเซิร์ฟเวอร์ทำได้อีกทาง: ส่ง token ทาง stdin ให้ <code>php artisan facebook:page-token --page=106036940932060</code> (ห้ามใส่ token เป็น argument) · ทดสอบ: <code>php artisan facebook:page-token --check</code></p>
    </div>

    {{-- Recent articles --}}
    <div class="{{ $card }}">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">บทความล่าสุดกับเพจ</h2>
        @if($recent->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบทความที่เผยแพร่</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-4">บทความ</th>
                            <th class="py-2 pr-4">เผยแพร่</th>
                            <th class="py-2 pr-4">Facebook</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($recent as $article)
                            <tr>
                                <td class="py-2 pr-4"><a href="{{ $article->url() }}" target="_blank" rel="noopener" class="text-gray-900 dark:text-gray-100 hover:underline">{{ \Illuminate\Support\Str::limit($article->title, 70) }}</a></td>
                                <td class="py-2 pr-4 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $article->published_at?->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap">
                                    @if($article->facebook_post_id)
                                        <a href="{{ \App\Services\FacebookPageService::postUrl($article->facebook_post_id) }}" target="_blank" rel="noopener" class="text-green-600 dark:text-green-400 hover:underline">โพสต์แล้ว {{ $article->facebook_posted_at?->timezone('Asia/Bangkok')->format('d/m H:i') }}</a>
                                    @else
                                        <span class="text-gray-500">ยังไม่ได้โพสต์</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right">
                                    @unless($article->facebook_post_id)
                                        <form method="POST" action="{{ route('admin.articles.facebook', $article) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-50" @disabled(! $hasToken)>โพสต์ลงเพจ</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
