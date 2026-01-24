@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Line Messaging')
@section('page-title', 'ส่งข้อความ Line')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-emerald-600 to-teal-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-lime-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-green-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">Line Messaging</h1>
                    </div>
                    <p class="text-green-100 text-lg">ส่งข้อความหาผู้ใช้ผ่าน Line Messaging API</p>
                </div>
                <a href="{{ route('admin.line-messaging.users') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-semibold shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    จัดการ Line UID
                </a>
            </div>
        </div>
    </div>

    {{-- Config Warning --}}
    @unless($isConfigured)
    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-5 shadow-xl">
        <div class="flex">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center mr-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-1">Line Messaging API ยังไม่ได้ตั้งค่า</h4>
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    กรุณาตั้งค่า <code class="bg-yellow-100 dark:bg-yellow-800 px-2 py-0.5 rounded">LINE_CHANNEL_ACCESS_TOKEN</code> ใน .env หรือในหน้าตั้งค่าระบบ
                </p>
            </div>
        </div>
    </div>
    @endunless

    {{-- Send Message Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-5 bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 flex items-center">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white">ส่งข้อความ Line</h2>
        </div>

        <form action="{{ route('admin.line-messaging.send') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- Recipients --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ผู้รับ (Line UID)
                </label>
                <div class="flex gap-2 mb-2">
                    <input type="text" name="recipients" id="recipients"
                           class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all"
                           placeholder="U1234567890abcdef..., U0987654321fedcba..."
                           value="{{ old('recipients') }}"
                           required>
                    <button type="button" onclick="openUserSelector()"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all shadow">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    ใส่ Line UID คั่นด้วยเครื่องหมายจุลภาค (,) หรือกดปุ่มค้นหาเพื่อเลือกจากรายชื่อ
                </p>
                @error('recipients')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ข้อความ
                </label>
                <textarea name="message" id="message" rows="6"
                          class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500 transition-all"
                          placeholder="พิมพ์ข้อความที่ต้องการส่ง..."
                          required>{{ old('message') }}</textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">สูงสุด 5,000 ตัวอักษร</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><span id="charCount">0</span>/5000</p>
                </div>
                @error('message')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quick Templates --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เทมเพลตด่วน
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="useTemplate('greeting')"
                            class="px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-300 rounded-xl text-sm hover:from-green-200 hover:to-emerald-200 dark:hover:from-green-800/30 dark:hover:to-emerald-800/30 font-medium transition-all shadow">
                        ทักทาย
                    </button>
                    <button type="button" onclick="useTemplate('promo')"
                            class="px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 text-blue-700 dark:text-blue-300 rounded-xl text-sm hover:from-blue-200 hover:to-indigo-200 dark:hover:from-blue-800/30 dark:hover:to-indigo-800/30 font-medium transition-all shadow">
                        โปรโมชั่น
                    </button>
                    <button type="button" onclick="useTemplate('reminder')"
                            class="px-4 py-2 bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 text-yellow-700 dark:text-yellow-300 rounded-xl text-sm hover:from-yellow-200 hover:to-amber-200 dark:hover:from-yellow-800/30 dark:hover:to-amber-800/30 font-medium transition-all shadow">
                        แจ้งเตือน
                    </button>
                    <button type="button" onclick="useTemplate('thankyou')"
                            class="px-4 py-2 bg-gradient-to-r from-pink-100 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/30 text-pink-700 dark:text-pink-300 rounded-xl text-sm hover:from-pink-200 hover:to-rose-200 dark:hover:from-pink-800/30 dark:hover:to-rose-800/30 font-medium transition-all shadow">
                        ขอบคุณ
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.line-messaging.users') }}"
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-medium shadow">
                    จัดการ Line UID
                </a>
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all flex items-center font-medium shadow-lg">
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
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ผู้ใช้ที่มี Line UID</h3>
            </div>
            <span class="px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white rounded-full text-sm font-semibold shadow">
                {{ $usersWithLine->count() }} คน
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Line</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($usersWithLine as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $user->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ Str::limit($user->line_uid, 20) }}</span>
                            @if($user->line_display_name)
                                <br><span class="text-green-600 dark:text-green-400">{{ $user->line_display_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button type="button" onclick="addRecipient('{{ $user->line_uid }}')"
                                    class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-400 to-emerald-500 text-white rounded-lg hover:from-green-500 hover:to-emerald-600 text-sm font-medium transition-all shadow">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                เพิ่มเป็นผู้รับ
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
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ค้นหาผู้ใช้</h3>
            <button onclick="closeUserSelector()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input type="text" id="userSearch" placeholder="ค้นหาชื่อ, อีเมล, Line UID..."
                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-green-500"
                   onkeyup="searchUsers(this.value)">
        </div>
        <div class="flex-1 overflow-y-auto p-4" id="userSearchResults">
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">พิมพ์เพื่อค้นหาผู้ใช้...</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeUserSelector()" class="w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all">
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
        document.getElementById('userSearchResults').innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">พิมพ์อย่างน้อย 2 ตัวอักษร...</p>';
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`{{ route('admin.line-messaging.search') }}?q=${encodeURIComponent(query)}`);
            const users = await response.json();

            if (users.length === 0) {
                document.getElementById('userSearchResults').innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-8">ไม่พบผู้ใช้</p>';
                return;
            }

            let html = '<div class="space-y-2">';
            users.forEach(user => {
                html += `
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">${user.name}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">${user.email}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">${user.line_uid}</div>
                        </div>
                        <button type="button" onclick="addRecipient('${user.line_uid}'); closeUserSelector();"
                                class="px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 text-sm font-medium transition-all">
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
