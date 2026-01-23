@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าโลโก้และ Favicon')
@section('page-title', 'ตั้งค่าโลโก้และ Favicon')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
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

    <!-- Logo Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">โลโก้เว็บไซต์</h3>

        <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <!-- Current Logo Preview -->
                @if($settings['site_logo'])
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">โลโก้ปัจจุบัน</label>
                        <div class="flex items-start space-x-4">
                            <img src="{{ asset('storage/' . $settings['site_logo']) }}"
                                 alt="Current Logo"
                                 class="max-h-20 border border-gray-200 rounded p-2 bg-white">
                            <form action="{{ route('admin.branding.logo.delete') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบโลโก้นี้?')"
                                        class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                    ลบโลโก้
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">ยังไม่มีโลโก้</p>
                    </div>
                @endif

                <!-- Logo Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        อัปโหลดโลโก้ใหม่
                    </label>
                    <input type="file"
                           name="logo"
                           accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์: PNG, JPG, JPEG, SVG, WEBP (ขนาดไม่เกิน 2MB)</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        บันทึกโลโก้
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Favicon Settings -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Favicon</h3>

        <form action="{{ route('admin.branding.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <!-- Current Favicon Preview -->
                @if($settings['site_favicon'])
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Favicon ปัจจุบัน</label>
                        <div class="flex items-start space-x-4">
                            <img src="{{ asset('storage/' . $settings['site_favicon']) }}"
                                 alt="Current Favicon"
                                 class="h-8 w-8 border border-gray-200 rounded bg-white">
                            <form action="{{ route('admin.branding.favicon.delete') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบ favicon นี้?')"
                                        class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                    ลบ Favicon
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">ยังไม่มี favicon</p>
                    </div>
                @endif

                <!-- Favicon Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        อัปโหลด Favicon ใหม่
                    </label>
                    <input type="file"
                           name="favicon"
                           accept="image/png,image/jpeg,image/jpg,image/x-icon,image/svg+xml"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์: PNG, JPG, JPEG, ICO, SVG (ขนาดไม่เกิน 512KB, แนะนำ 32x32px)</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        บันทึก Favicon
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-medium text-blue-900 mb-2">คำแนะนำ</h4>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• <strong>โลโก้:</strong> แนะนำให้ใช้ไฟล์ PNG หรือ SVG พื้นหลังโปร่งใส ขนาดประมาณ 200-300px กว้าง</li>
            <li>• <strong>Favicon:</strong> แนะนำให้ใช้ไฟล์ PNG ขนาด 32x32px หรือ ICO ขนาด 16x16px</li>
            <li>• หลังจากอัปโหลดแล้ว โลโก้และ favicon จะถูกแสดงในทุกหน้าของเว็บไซต์</li>
            <li>• หากต้องการเปลี่ยนกลับเป็นค่าเริ่มต้น ให้กดปุ่ม "ลบโลโก้" หรือ "ลบ Favicon"</li>
        </ul>
    </div>
</div>
@endsection
