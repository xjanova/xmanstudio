@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Line Messaging')
@section('page-title', 'ส่งข้อความ Line')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Config Warning --}}
    @unless($isConfigured)
    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>Line Messaging API ยังไม่ได้ตั้งค่า</strong><br>
                    กรุณาตั้งค่า <code>LINE_CHANNEL_ACCESS_TOKEN</code> ใน .env หรือในหน้าตั้งค่าระบบ
                </p>
            </div>
        </div>
    </div>
    @endunless

    {{-- Send Message Form --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600">
            <h2 class="text-xl font-bold text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                ส่งข้อความ Line
            </h2>
        </div>

        <form action="{{ route('admin.line-messaging.send') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- Recipients --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ผู้รับ (Line UID)
                </label>
                <div class="flex gap-2 mb-2">
                    <input type="text" name="recipients" id="recipients"
                           class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                           placeholder="U1234567890abcdef..., U0987654321fedcba..."
                           value="{{ old('recipients') }}"
                           required>
                    <button type="button" onclick="openUserSelector()"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500">
                    ใส่ Line UID คั่นด้วยเครื่องหมายจุลภาค (,) หรือกดปุ่มค้นหาเพื่อเลือกจากรายชื่อ
                </p>
                @error('recipients')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ข้อความ
                </label>
                <textarea name="message" id="message" rows="6"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                          required>{{ old('message') }}</textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-500">สูงสุด 5,000 ตัวอักษร</p>
                    <p class="text-xs text-gray-500"><span id="charCount">0</span>/5000</p>
                </div>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quick Templates --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    เทมเพลตด่วน
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="useTemplate('greeting')"
                            class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                        ทักทาย
                    </button>
                    <button type="button" onclick="useTemplate('promo')"
                            class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                        โปรโมชั่น
                    </button>
                    <button type="button" onclick="useTemplate('reminder')"
                            class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                        แจ้งเตือน
                    </button>
                    <button type="button" onclick="useTemplate('thankyou')"
                            class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                        ขอบคุณ
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.line-messaging.users') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    จัดการ Line UID
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    ส่งข้อความ
                </button>
            </div>
        </form>
    </div>

    {{-- Users with Line UID --}}
    @if($usersWithLine->count() > 0)
    <div class="mt-8 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">ผู้ใช้ที่มี Line UID ({{ $usersWithLine->count() }} คน)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Line</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($usersWithLine as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="font-mono text-xs">{{ Str::limit($user->line_uid, 20) }}</span>
                            @if($user->line_display_name)
                                <br><span class="text-green-600">{{ $user->line_display_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button type="button" onclick="addRecipient('{{ $user->line_uid }}')"
                                    class="text-green-600 hover:text-green-800">
                                + เพิ่มเป็นผู้รับ
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- User Selector Modal --}}
<div id="userSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">ค้นหาผู้ใช้</h3>
            <button onclick="closeUserSelector()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-4 border-b border-gray-200">
            <input type="text" id="userSearch" placeholder="ค้นหาชื่อ, อีเมล, Line UID..."
                   class="w-full rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500"
                   onkeyup="searchUsers(this.value)">
        </div>
        <div class="flex-1 overflow-y-auto p-4" id="userSearchResults">
            <p class="text-gray-500 text-center py-8">พิมพ์เพื่อค้นหาผู้ใช้...</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            <button onclick="closeUserSelector()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                ปิด
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Character counter
document.getElementById('message').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});

// Templates
const templates = {
    greeting: `สวัสดีครับ/ค่ะ

ขอบคุณที่ใช้บริการ XMAN Studio
หากมีข้อสงสัยหรือต้องการความช่วยเหลือ สามารถติดต่อเราได้ตลอดเวลาครับ/ค่ะ`,

    promo: `โปรโมชั่นพิเศษ!

รับส่วนลดพิเศษสำหรับลูกค้า XMAN Studio
สนใจดูรายละเอียดเพิ่มเติมได้ที่เว็บไซต์ของเรา`,

    reminder: `แจ้งเตือน

นี่คือข้อความแจ้งเตือนจาก XMAN Studio
กรุณาตรวจสอบรายละเอียดในระบบ`,

    thankyou: `ขอบคุณมากครับ/ค่ะ

ขอบคุณที่ไว้วางใจใช้บริการ XMAN Studio
หวังว่าจะได้รับใช้คุณอีกในครั้งต่อไป`
};

function useTemplate(key) {
    if (templates[key]) {
        document.getElementById('message').value = templates[key];
        document.getElementById('charCount').textContent = templates[key].length;
    }
}

// Add recipient
function addRecipient(lineUid) {
    const input = document.getElementById('recipients');
    const current = input.value.trim();
    if (current) {
        if (!current.includes(lineUid)) {
            input.value = current + ', ' + lineUid;
        }
    } else {
        input.value = lineUid;
    }
}

// Modal functions
function openUserSelector() {
    document.getElementById('userSelectorModal').classList.remove('hidden');
    document.getElementById('userSearch').focus();
}

function closeUserSelector() {
    document.getElementById('userSelectorModal').classList.add('hidden');
}

let searchTimeout;
function searchUsers(query) {
    clearTimeout(searchTimeout);
    if (query.length < 2) {
        document.getElementById('userSearchResults').innerHTML = '<p class="text-gray-500 text-center py-8">พิมพ์อย่างน้อย 2 ตัวอักษร...</p>';
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('admin.line-messaging.search') }}?q=${encodeURIComponent(query)}`);
            const users = await response.json();

            if (users.length === 0) {
                document.getElementById('userSearchResults').innerHTML = '<p class="text-gray-500 text-center py-8">ไม่พบผู้ใช้</p>';
                return;
            }

            let html = '<div class="space-y-2">';
            users.forEach(user => {
                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                        <div>
                            <div class="font-medium text-gray-900">${user.name}</div>
                            <div class="text-sm text-gray-500">${user.email}</div>
                            <div class="text-xs text-gray-400 font-mono">${user.line_uid}</div>
                        </div>
                        <button type="button" onclick="addRecipient('${user.line_uid}'); closeUserSelector();"
                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                            เลือก
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('userSearchResults').innerHTML = html;
        } catch (error) {
            document.getElementById('userSearchResults').innerHTML = '<p class="text-red-500 text-center py-8">เกิดข้อผิดพลาด</p>';
        }
    }, 300);
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserSelector();
    }
});
</script>
@endpush
@endsection
