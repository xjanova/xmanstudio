@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Ads.txt Management')
@section('page-title', 'Ads.txt Management (จัดการไฟล์ Ads.txt สำหรับ Google Ads)')

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
                <h4 class="font-medium text-blue-900 mb-1">เกี่ยวกับ ads.txt</h4>
                <p class="text-sm text-blue-800 space-y-1">
                    <span class="block">ads.txt (Authorized Digital Sellers) คือไฟล์ที่ใช้ระบุผู้ขายโฆษณาที่ได้รับอนุญาตบนเว็บไซต์ของคุณ</span>
                    <span class="block">ไฟล์นี้ช่วยป้องกันการฉ้อโกงโฆษณาและเพิ่มความน่าเชื่อถือให้กับเว็บไซต์</span>
                    <span class="block">URL: <a href="{{ route('ads-txt') }}" target="_blank" class="underline font-medium">{{ url('/ads.txt') }}</a></span>
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.ads-txt.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Enable/Disable Toggle -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">เปิด/ปิดใช้งาน Ads.txt</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        เมื่อปิดใช้งาน ไฟล์ ads.txt จะไม่สามารถเข้าถึงได้ (404 Not Found)
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $setting->enabled ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $setting->enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                </label>
            </div>
        </div>

        <!-- Content Editor -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">เนื้อหา Ads.txt</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        ระบุรายการผู้ขายโฆษณาที่ได้รับอนุญาต (หนึ่งรายการต่อบรรทัด)
                    </p>
                </div>
                @if($setting->enabled && !empty($setting->content))
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
                    <strong>รูปแบบ:</strong> domain, publisher_id, relationship, certification_authority_id
                </p>
            </div>

            <textarea
                name="content"
                rows="15"
                class="w-full font-mono text-sm border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="# Google AdSense
google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0

# Google Ad Manager
google.com, pub-0000000000000000, RESELLER, f08c47fec0942fa0

# Add more entries here..."
            >{{ old('content', $setting->content) }}</textarea>

            <div class="mt-3 text-xs text-gray-500">
                <strong>คำแนะนำ:</strong>
                <ul class="list-disc list-inside mt-1 space-y-1">
                    <li>บรรทัดที่ขึ้นต้นด้วย # คือหมายเหตุ (comment)</li>
                    <li>ใช้ DIRECT สำหรับบัญชีที่คุณเป็นเจ้าของโดยตรง</li>
                    <li>ใช้ RESELLER สำหรับบัญชีที่ขายต่อจากผู้อื่น</li>
                    <li>Google AdSense ใช้ certification authority ID: f08c47fec0942fa0</li>
                </ul>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('ads-txt') }}" target="_blank"
               class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                ดูไฟล์ Ads.txt
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>

    <!-- How to get Google AdSense Publisher ID -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">วิธีหา Publisher ID จาก Google AdSense</h3>

        <div class="space-y-4 text-sm text-gray-700">
            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 mt-0.5">1</span>
                <div>
                    <p class="font-medium">เข้าสู่ระบบ Google AdSense</p>
                    <p class="text-gray-600">ไปที่ <a href="https://www.google.com/adsense/" target="_blank" class="text-primary-600 hover:underline">https://www.google.com/adsense/</a></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 mt-0.5">2</span>
                <div>
                    <p class="font-medium">ไปที่เมนู Settings (การตั้งค่า)</p>
                    <p class="text-gray-600">คลิกที่เมนูด้านซ้าย แล้วเลือก Settings > Account > Account information</p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 mt-0.5">3</span>
                <div>
                    <p class="font-medium">คัดลอก Publisher ID</p>
                    <p class="text-gray-600">จะเป็นรูปแบบ <code class="bg-gray-100 px-2 py-1 rounded">pub-0000000000000000</code></p>
                </div>
            </div>

            <div class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 bg-primary-600 text-white rounded-full flex items-center justify-center mr-3 mt-0.5">4</span>
                <div>
                    <p class="font-medium">ใส่ในไฟล์ ads.txt</p>
                    <p class="text-gray-600">ใช้รูปแบบ: <code class="bg-gray-100 px-2 py-1 rounded text-xs">google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0</code></p>
                </div>
            </div>
        </div>

        <!-- Example -->
        <div class="mt-6 border-t pt-4">
            <h4 class="font-medium text-gray-900 mb-2">ตัวอย่างไฟล์ ads.txt ที่ถูกต้อง:</h4>
            <pre class="bg-gray-100 rounded p-3 text-xs overflow-x-auto"># Google AdSense - บัญชีหลัก
google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0

# Google Ad Manager (ถ้ามี)
google.com, pub-9876543210987654, RESELLER, f08c47fec0942fa0

# Other ad networks (ถ้ามี)
# partner.com, partner-id, DIRECT, partner-cert-id</pre>
        </div>
    </div>
</div>
@endsection
