@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Custom Code')
@section('page-title', 'Custom Code (โค้ด Tracking & Verification)')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 mb-1">การใช้งาน Custom Code</h4>
                <p class="text-sm text-blue-800">
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
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Head Code</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        โค้ดจะถูกใส่ก่อน <code class="bg-gray-100 px-1 rounded">&lt;/head&gt;</code>
                    </p>
                </div>
                @if(!empty($settings['custom_code_head']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-2">
                    <strong>ตัวอย่างการใช้งาน:</strong> Google Analytics, Meta tags, Google Search Console verification, Facebook Domain Verification
                </p>
            </div>

            <textarea
                name="custom_code_head"
                rows="8"
                class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
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
                <div class="mt-2 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_head')" class="text-sm text-red-600 hover:text-red-800">
                        ล้างโค้ด Head
                    </button>
                </div>
            @endif
        </div>

        <!-- Body Start Code Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Body Start Code</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        โค้ดจะถูกใส่หลัง <code class="bg-gray-100 px-1 rounded">&lt;body&gt;</code>
                    </p>
                </div>
                @if(!empty($settings['custom_code_body_start']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-2">
                    <strong>ตัวอย่างการใช้งาน:</strong> Google Tag Manager (noscript), Facebook Pixel (noscript)
                </p>
            </div>

            <textarea
                name="custom_code_body_start"
                rows="6"
                class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="<!-- Google Tag Manager (noscript) -->
<noscript><iframe src='https://www.googletagmanager.com/ns.html?id=GTM-XXXX'
height='0' width='0' style='display:none;visibility:hidden'></iframe></noscript>"
            >{{ old('custom_code_body_start', $settings['custom_code_body_start']) }}</textarea>

            @if(!empty($settings['custom_code_body_start']))
                <div class="mt-2 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_body_start')" class="text-sm text-red-600 hover:text-red-800">
                        ล้างโค้ด Body Start
                    </button>
                </div>
            @endif
        </div>

        <!-- Body End Code Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Body End Code</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        โค้ดจะถูกใส่ก่อน <code class="bg-gray-100 px-1 rounded">&lt;/body&gt;</code>
                    </p>
                </div>
                @if(!empty($settings['custom_code_body_end']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Active
                    </span>
                @endif
            </div>

            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-2">
                    <strong>ตัวอย่างการใช้งาน:</strong> Chat widgets (Tawk.to, LINE), Tracking pixels, Conversion tracking
                </p>
            </div>

            <textarea
                name="custom_code_body_end"
                rows="6"
                class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
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
                <div class="mt-2 flex justify-end">
                    <button type="button" onclick="clearField('custom_code_body_end')" class="text-sm text-red-600 hover:text-red-800">
                        ล้างโค้ด Body End
                    </button>
                </div>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกทั้งหมด
            </button>
        </div>
    </form>

    <!-- Warning Box -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-yellow-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-medium text-yellow-900 mb-1">ข้อควรระวัง</h4>
                <ul class="text-sm text-yellow-800 space-y-1">
                    <li>- ใส่โค้ดที่ได้รับจากบริการที่เชื่อถือได้เท่านั้น (Google, Facebook, LINE, etc.)</li>
                    <li>- โค้ดที่ไม่ถูกต้องอาจทำให้เว็บไซต์ทำงานผิดพลาด</li>
                    <li>- หากเว็บไซต์มีปัญหาหลังจากใส่โค้ด ให้ลบโค้ดที่ใส่ล่าสุดออก</li>
                    <li>- โค้ด PHP และ Blade directives จะถูกลบออกโดยอัตโนมัติเพื่อความปลอดภัย</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Common Codes Reference -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ตัวอย่างโค้ดที่ใช้บ่อย</h3>

        <div class="space-y-4">
            <!-- Google Analytics -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.84 2.998c-.648 0-1.18.531-1.18 1.18v15.645c0 .648.532 1.179 1.18 1.179.649 0 1.18-.531 1.18-1.18V4.179c0-.649-.531-1.18-1.18-1.18zm-6.296 5.898c-.648 0-1.18.531-1.18 1.18v9.746c0 .648.532 1.18 1.18 1.18.649 0 1.18-.532 1.18-1.18v-9.747c0-.648-.531-1.18-1.18-1.18zm-6.297 3.538c-.648 0-1.18.531-1.18 1.18v6.208c0 .648.532 1.18 1.18 1.18.649 0 1.18-.532 1.18-1.18v-6.209c0-.648-.531-1.18-1.18-1.18zm-6.296 3.538c-.649 0-1.18.531-1.18 1.18v2.67c0 .648.531 1.18 1.18 1.18.648 0 1.18-.532 1.18-1.18v-2.67c0-.649-.532-1.18-1.18-1.18z"/>
                    </svg>
                    Google Analytics 4 (GA4)
                </h4>
                <p class="text-sm text-gray-500 mb-2">ใส่ใน <strong>Head Code</strong></p>
                <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto">&lt;!-- Google tag (gtag.js) --&gt;
&lt;script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
&lt;/script&gt;</pre>
            </div>

            <!-- Google Search Console -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Google Search Console Verification
                </h4>
                <p class="text-sm text-gray-500 mb-2">ใส่ใน <strong>Head Code</strong></p>
                <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto">&lt;meta name="google-site-verification" content="XXXXXXXXXXXXXXXXXXXXXXXX" /&gt;</pre>
            </div>

            <!-- Facebook Pixel -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook Pixel
                </h4>
                <p class="text-sm text-gray-500 mb-2">ใส่ใน <strong>Head Code</strong></p>
                <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto">&lt;!-- Meta Pixel Code --&gt;
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
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                    LINE Tag
                </h4>
                <p class="text-sm text-gray-500 mb-2">ใส่ใน <strong>Head Code</strong></p>
                <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto">&lt;!-- LINE Tag Base Code --&gt;
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
