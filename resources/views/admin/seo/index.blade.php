@extends($adminLayout ?? 'layouts.admin')

@section('title', 'SEO Management')
@section('page-title', 'SEO Management (เพิ่มประสิทธิภาพการค้นหา Google)')

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
                <h4 class="font-medium text-blue-900 mb-1">เกี่ยวกับ SEO</h4>
                <p class="text-sm text-blue-800">
                    SEO (Search Engine Optimization) ช่วยให้เว็บไซต์ของคุณปรากฏใน Google Search และเพิ่มโอกาสที่ลูกค้าจะค้นหาและเข้าถึงบริการของคุณ
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.seo.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Basic SEO -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูล SEO พื้นฐาน</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อเว็บไซต์ *</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $setting->site_name) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500" required>
                    <p class="text-xs text-gray-500 mt-1">ชื่อเว็บไซต์ของคุณ (จะแสดงในผลลัพธ์การค้นหา)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวข้อหลัก (Title Tag)</label>
                    <input type="text" name="site_title" value="{{ old('site_title', $setting->site_title) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           maxlength="60"
                           placeholder="XMAN Studio - รับทำเว็บไซต์ ออกแบบเว็บไซต์">
                    <p class="text-xs text-gray-500 mt-1">หัวข้อหลักที่จะแสดงใน Google (แนะนำ 50-60 ตัวอักษร)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำอธิบาย (Meta Description)</label>
                    <textarea name="site_description" rows="3"
                              class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                              maxlength="160"
                              placeholder="XMAN Studio ให้บริการรับทำเว็บไซต์ ออกแบบเว็บไซต์ พัฒนาระบบ CMS ครบวงจร ด้วยทีมงานมืออาชีพ ราคาย่อมเยา">{{ old('site_description', $setting->site_description) }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">คำอธิบายที่จะแสดงใน Google (แนะนำ 150-160 ตัวอักษร)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คำค้นหา (Keywords)</label>
                    <input type="text" name="site_keywords" value="{{ old('site_keywords', $setting->site_keywords) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="รับทำเว็บไซต์, ออกแบบเว็บไซต์, CMS, พัฒนาเว็บ">
                    <p class="text-xs text-gray-500 mt-1">คำค้นหาหลักที่เกี่ยวข้องกับเว็บไซต์ (คั่นด้วยเครื่องหมาย ,)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ผู้เขียน</label>
                    <input type="text" name="site_author" value="{{ old('site_author', $setting->site_author) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Social Media (Facebook, Twitter)</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพ Open Graph (OG Image)</label>
                    @if($setting->og_image)
                        <div class="mb-2">
                            <img src="{{ asset('storage/' . $setting->og_image) }}" alt="OG Image" class="max-w-xs rounded">
                        </div>
                    @endif
                    <input type="file" name="og_image" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-gray-500 mt-1">รูปที่จะแสดงเมื่อแชร์ลิงก์บน Facebook, Twitter (แนะนำ 1200x630 pixels)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Site (@username)</label>
                    <input type="text" name="twitter_site" value="{{ old('twitter_site', $setting->twitter_site) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="@xmanstudio">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Twitter Creator (@username)</label>
                    <input type="text" name="twitter_creator" value="{{ old('twitter_creator', $setting->twitter_creator) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="@username">
                </div>
            </div>
        </div>

        <!-- Google Services -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Google Services</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google Site Verification</label>
                    <input type="text" name="google_site_verification" value="{{ old('google_site_verification', $setting->google_site_verification) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <p class="text-xs text-gray-500 mt-1">รหัสยืนยันจาก Google Search Console</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Google Analytics ID</label>
                    <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $setting->google_analytics_id) }}"
                           class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-primary-500"
                           placeholder="G-XXXXXXXXXX">
                    <p class="text-xs text-gray-500 mt-1">Google Analytics 4 Measurement ID</p>
                </div>
            </div>
        </div>

        <!-- Sitemap -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Sitemap.xml</h3>
                    <p class="text-sm text-gray-500 mt-1">ไฟล์แผนผังเว็บไซต์สำหรับ Google Search</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="sitemap_enabled" value="1" class="sr-only peer" {{ $setting->sitemap_enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">เปิดใช้งาน</span>
                </label>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ url('/sitemap.xml') }}" target="_blank"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    ดู Sitemap
                </a>
                <button type="button" onclick="generateSitemap()"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    สร้าง Sitemap ใหม่
                </button>
            </div>
        </div>

        <!-- Robots.txt -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Robots.txt</h3>
                    <p class="text-sm text-gray-500 mt-1">ควบคุมว่าหน้าไหนสามารถถูก index ได้</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="robots_txt_enabled" value="1" class="sr-only peer" {{ $setting->robots_txt_enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">เปิดใช้งาน</span>
                </label>
            </div>

            <textarea name="robots_txt_content" rows="10"
                      class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500"
                      placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;&#10;Sitemap: {{ url('/sitemap.xml') }}">{{ old('robots_txt_content', $setting->robots_txt_content) }}</textarea>
        </div>

        <!-- Structured Data -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Structured Data (JSON-LD)</h3>
            <p class="text-sm text-gray-500 mb-4">ข้อมูลโครงสร้างสำหรับ Google Rich Results</p>

            <textarea name="structured_data_json" rows="15"
                      class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500"
                      placeholder='{"@context": "https://schema.org", "@type": "Organization", "name": "XMAN Studio"}'>{{ old('structured_data_json', json_encode($setting->structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">รูปแบบ JSON-LD สำหรับ Schema.org</p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการตั้งค่า SEO
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function generateSitemap() {
    if (confirm('สร้าง sitemap.xml ใหม่?')) {
        window.location.href = '{{ route("admin.seo.generate-sitemap") }}';
    }
}
</script>
@endpush
@endsection
