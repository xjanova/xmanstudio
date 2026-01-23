@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า Line')
@section('page-title', 'ตั้งค่า Line')

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

    <form action="{{ route('admin.line-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Line Messaging API -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-green-500 rounded flex items-center justify-center mr-2">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.349 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                </div>
                Line Messaging API
            </h3>
            <p class="text-sm text-gray-500 mb-4">ใช้สำหรับส่งข้อความหาลูกค้าผ่าน Line Official Account</p>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน Line Messaging</label>
                        <p class="text-sm text-gray-500">ส่งข้อความอัตโนมัติผ่าน Line</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_messaging_enabled" value="1" {{ $settings['line_messaging_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel Access Token</label>
                    <input type="password" name="line_channel_access_token" value="{{ $settings['line_channel_access_token'] }}" placeholder="Channel Access Token จาก Line Developers Console"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel Secret</label>
                    <input type="password" name="line_channel_secret" value="{{ $settings['line_channel_secret'] }}" placeholder="Channel Secret"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin User ID</label>
                    <input type="text" name="line_admin_user_id" value="{{ $settings['line_admin_user_id'] }}" placeholder="U1234567890abcdef..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <p class="mt-1 text-xs text-gray-500">User ID ของแอดมินสำหรับรับการแจ้งเตือน</p>
                </div>

                <button type="button" onclick="testMessaging()" class="px-4 py-2 border border-green-500 text-green-600 rounded-lg hover:bg-green-50 font-medium transition-colors">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        ทดสอบการเชื่อมต่อ
                    </span>
                </button>
            </div>
        </div>

        <!-- Line Login -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-green-500 rounded flex items-center justify-center mr-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                Line Login
            </h3>
            <p class="text-sm text-gray-500 mb-4">ให้ผู้ใช้สามารถล็อกอินด้วยบัญชี Line</p>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน Line Login</label>
                        <p class="text-sm text-gray-500">อนุญาตให้ล็อกอินด้วย Line</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_login_enabled" value="1" {{ $settings['line_login_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel ID</label>
                    <input type="text" name="line_login_channel_id" value="{{ $settings['line_login_channel_id'] }}" placeholder="1234567890"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel Secret</label>
                    <input type="password" name="line_login_channel_secret" value="{{ $settings['line_login_channel_secret'] }}" placeholder="Channel Secret"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2"><strong>Callback URL:</strong></p>
                    <code class="block p-2 bg-white border rounded text-sm text-gray-800">{{ url('/auth/line/callback') }}</code>
                    <p class="mt-2 text-xs text-gray-500">ใส่ URL นี้ใน Line Developers Console &gt; Line Login &gt; Callback URL</p>
                </div>
            </div>
        </div>

        <!-- Line Notify -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-yellow-500 rounded flex items-center justify-center mr-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                Line Notify
            </h3>
            <p class="text-sm text-gray-500 mb-4">ส่งการแจ้งเตือนไปยังกลุ่มหรือบุคคล (ฟรี ไม่จำกัด)</p>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน Line Notify</label>
                        <p class="text-sm text-gray-500">ส่งการแจ้งเตือนผ่าน Line Notify</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_enabled" value="1" {{ $settings['line_notify_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                    <input type="password" name="line_notify_token" value="{{ $settings['line_notify_token'] }}" placeholder="Line Notify Access Token"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                    <p class="mt-1 text-xs text-gray-500">รับ Token ได้ที่ <a href="https://notify-bot.line.me/my/" target="_blank" class="text-primary-600 hover:underline">notify-bot.line.me</a></p>
                </div>

                <button type="button" onclick="testNotify()" class="px-4 py-2 border border-yellow-500 text-yellow-600 rounded-lg hover:bg-yellow-50 font-medium transition-colors">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        ส่งข้อความทดสอบ
                    </span>
                </button>
            </div>
        </div>

        <!-- Line LIFF -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center mr-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                Line LIFF (Line Front-end Framework)
            </h3>
            <p class="text-sm text-gray-500 mb-4">สร้างประสบการณ์ภายใน Line App</p>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน LIFF</label>
                        <p class="text-sm text-gray-500">ใช้งาน Line LIFF Apps</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_liff_enabled" value="1" {{ $settings['line_liff_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LIFF ID</label>
                    <input type="text" name="line_liff_id" value="{{ $settings['line_liff_id'] }}" placeholder="1234567890-abcdefgh"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Notification Events -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                เหตุการณ์ที่ต้องการแจ้งเตือน
            </h3>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">คำสั่งซื้อใหม่</label>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีคำสั่งซื้อใหม่</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_new_order" value="1" {{ $settings['line_notify_new_order'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">ใบเสนอราคาใหม่</label>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีการขอใบเสนอราคาใหม่</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_new_quotation" value="1" {{ $settings['line_notify_new_quotation'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">รับชำระเงิน</label>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีการแจ้งชำระเงิน</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_payment_received" value="1" {{ $settings['line_notify_payment_received'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">ทิกเก็ตสนับสนุนใหม่</label>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีทิกเก็ตสนับสนุนใหม่</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_new_support_ticket" value="1" {{ $settings['line_notify_new_support_ticket'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">สมาชิกใหม่</label>
                        <p class="text-sm text-gray-500">แจ้งเตือนเมื่อมีสมาชิกลงทะเบียนใหม่</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="line_notify_new_user" value="1" {{ $settings['line_notify_new_user'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
function testMessaging() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>กำลังทดสอบ...';
    btn.disabled = true;

    fetch('{{ route("admin.line-settings.test-messaging") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('สำเร็จ: ' + data.message);
        } else {
            alert('ผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}

function testNotify() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>กำลังส่ง...';
    btn.disabled = true;

    fetch('{{ route("admin.line-settings.test-notify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('สำเร็จ: ' + data.message);
        } else {
            alert('ผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}
</script>
@endsection
