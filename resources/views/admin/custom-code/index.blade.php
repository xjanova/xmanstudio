@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Custom Code')
@section('page-title', 'Custom Code (โค้ด Tracking & Verification)')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-700 via-gray-800 to-zinc-800 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-cyan-500 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Custom Code</h1>
                    </div>
                    <p class="text-gray-300 text-lg">จัดการโค้ด Tracking, Analytics และ Verification</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-24 h-24 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl shadow-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 shadow-lg">
        <div class="flex">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center mr-4 flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">การใช้งาน Custom Code</h4>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    ใส่โค้ดจากบริการต่างๆ เช่น Google Analytics, Google Tag Manager, Facebook Pixel, Google Search Console Verification
                    หรือโค้ด tracking อื่นๆ โค้ดจะถูกใส่ในทุกหน้าของเว็บไซต์โดยอัตโนมัติ
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.custom-code.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Head Code Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Head Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            โค้ดจะถูกใส่ก่อน <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">&lt;/head&gt;</code>
                        </p>
                    </div>
                </div>
                @if(!empty($settings['custom_code_head']))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-4 p-4 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl border border-violet-200 dark:border-violet-800">
                <p class="text-xs text-violet-800 dark:text-violet-200">
                    <strong>ตัวอย่างการใช้งาน:</strong> Google Analytics, Meta tags, Google Search Console verification, Facebook Domain Verification
                </p>
            </div>

            <textarea
                name="custom_code_head"
                rows="8"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                placeholder="<!-- Google Analytics -->
<script async src='https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID'></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>"
            >{{ old('custom_code_head', $settings['custom_code_head']) }}</textarea>

            @if(!empty($settings['custom_code_head']))
                <div class="mt-3 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_head')" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        ล้างโค้ด Head
                    </button>
                </div>
            @endif
        </div>

        <!-- Body Start Code Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Body Start Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            โค้ดจะถูกใส่หลัง <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">&lt;body&gt;</code>
                        </p>
                    </div>
                </div>
                @if(!empty($settings['custom_code_body_start']))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-4 p-4 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-800 dark:text-amber-200">
                    <strong>ตัวอย่างการใช้งาน:</strong> Google Tag Manager (noscript), Facebook Pixel (noscript)
                </p>
            </div>

            <textarea
                name="custom_code_body_start"
                rows="6"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                placeholder="<!-- Google Tag Manager (noscript) -->
<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=GTM-XXXX'
height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>"
            >{{ old('custom_code_body_start', $settings['custom_code_body_start']) }}</textarea>

            @if(!empty($settings['custom_code_body_start']))
                <div class="mt-3 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_body_start')" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        ล้างโค้ด Body Start
                    </button>
                </div>
            @endif
        </div>

        <!-- Body End Code Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Body End Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            โค้ดจะถูกใส่ก่อน <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">&lt;/body&gt;</code>
                        </p>
                    </div>
                </div>
                @if(!empty($settings['custom_code_body_end']))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-4 p-4 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl border border-cyan-200 dark:border-cyan-800">
                <p class="text-xs text-cyan-800 dark:text-cyan-200">
                    <strong>ตัวอย่างการใช้งาน:</strong> Chat widgets (Tawk.to, LINE), Tracking pixels, Conversion tracking
                </p>
            </div>

            <textarea
                name="custom_code_body_end"
                rows="6"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all"
                placeholder="<!-- Tawk.to Chat Widget -->
<script type='text/javascript'>
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement('script'),s0=document.getElementsByTagName('script')[0];
s1.async=true;
s1.src='https://embed.tawk.to/XXXXXXXX/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>"
            >{{ old('custom_code_body_end', $settings['custom_code_body_end']) }}</textarea>

            @if(!empty($settings['custom_code_body_end']))
                <div class="mt-3 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_body_end')" class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        ล้างโค้ด Body End
                    </button>
                </div>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-slate-700 via-gray-800 to-zinc-800 text-white rounded-xl hover:from-slate-800 hover:via-gray-900 hover:to-zinc-900 focus:ring-4 focus:ring-gray-300 transition-all font-semibold shadow-lg hover:shadow-xl flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกทั้งหมด
            </button>
        </div>
    </form>

    <!-- Warning Box -->
    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 shadow-lg">
        <div class="flex">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center mr-4 flex-shrink-0">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">ข้อควรระวัง</h4>
                <ul class="text-sm text-amber-800 dark:text-amber-200 space-y-1">
                    <li class="flex items-start">
                        <span class="mr-2">-</span>
                        <span>ใส่โค้ดที่ได้รับจากบริการที่เชื่อถือได้เท่านั้น (Google, Facebook, LINE, etc.)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">-</span>
                        <span>โค้ดที่ไม่ถูกต้องอาจทำให้เว็บไซต์ทำงานผิดพลาด</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">-</span>
                        <span>หากเว็บไซต์มีปัญหาหลังจากใส่โค้ด ให้ลบโค้ดที่ใส่ล่าสุดออก</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">-</span>
                        <span>โค้ด PHP และ Blade directives จะถูกลบออกโดยอัตโนมัติเพื่อความปลอดภัย</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Common Codes Reference -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center mr-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">ตัวอย่างโค้ดที่ใช้บ่อย</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">คัดลอกและวางในช่องที่เหมาะสม</p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Google Analytics -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-lg transition-shadow">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.84 2.998c-.648 0-1.18.531-1.18 1.18v15.645c0 .648.532 1.179 1.18 1.179.649 0 1.18-.531 1.18-1.18V4.179c0-.649-.531-1.18-1.18-1.18zm-6.296 5.898c-.648 0-1.18.531-1.18 1.18v9.746c0 .648.532 1.18 1.18 1.18.649 0 1.18-.532 1.18-1.18v-9.747c0-.648-.531-1.18-1.18-1.18zm-6.297 3.538c-.648 0-1.18.531-1.18 1.18v6.208c0 .648.532 1.18 1.18 1.18.649 0 1.18-.532 1.18-1.18v-6.209c0-.648-.531-1.18-1.18-1.18zm-6.296 3.538c-.649 0-1.18.531-1.18 1.18v2.67c0 .648.531 1.18 1.18 1.18.648 0 1.18-.532 1.18-1.18v-2.67c0-.649-.532-1.18-1.18-1.18z"/>
                        </svg>
                    </div>
                    Google Analytics 4 (GA4)
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใส่ใน <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded font-medium">Head Code</span></p>
                <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200">&lt;!-- Google tag (gtag.js) --&gt;
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
&lt;/script&gt;</pre>
            </div>

            <!-- Google Search Console -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-lg transition-shadow">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </div>
                    Google Search Console Verification
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใส่ใน <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded font-medium">Head Code</span></p>
                <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200">&lt;meta name="google-site-verification" content="XXXXXXXXXXXXXXXXXXXXXXXX" /&gt;</pre>
            </div>

            <!-- Facebook Pixel -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-lg transition-shadow">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    Facebook Pixel
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใส่ใน <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded font-medium">Head Code</span></p>
                <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200">&lt;!-- Meta Pixel Code --&gt;
&lt;script&gt;
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', 'YOUR_PIXEL_ID');
fbq('track', 'PageView');
&lt;/script&gt;</pre>
            </div>

            <!-- LINE Tag -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-lg transition-shadow">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </div>
                    LINE Tag
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ใส่ใน <span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded font-medium">Head Code</span></p>
                <pre class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto text-gray-800 dark:text-gray-200">&lt;!-- LINE Tag Base Code --&gt;
&lt;script&gt;
(function(g,d,o){
  g._ltq=g._ltq||[];g._lt=g._lt||function(){g._ltq.push(arguments)};
  var h=d.getElementsByTagName(o)[0];
  var s=d.createElement(o);s.async=1;
  s.src="https://d.line-scdn.net/n/line_tag/public/release/v1/lt.js";
  h.parentNode.insertBefore(s,h);
})(window,document,'script');
_lt('init', {
  customerType: 'lap',
  tagId: 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'
});
_lt('send', 'pv', ['XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX']);
&lt;/script&gt;</pre>
            </div>
        </div>
    </div>
</div>

<!-- Clear Field Form (Hidden) -->
<form id="clearForm" action="{{ route('admin.custom-code.clear') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="field" id="clearFieldName">
</form>

@push('scripts')
<script>
function clearField(fieldName) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะล้างโค้ดในส่วนนี้?')) {
        document.getElementById('clearFieldName').value = fieldName;
        document.getElementById('clearForm').submit();
    }
}
</script>
@endpush
@endsection
