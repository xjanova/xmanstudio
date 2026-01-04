@extends('layouts.admin')

@section('title', 'จัดการ Line UID')
@section('page-title', 'จัดการ Line UID ผู้ใช้')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between gap-4">
    {{-- Search & Filter --}}
    <form action="" method="GET" class="flex gap-2 flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="ค้นหาชื่อ, อีเมล, Line UID..."
               class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
        <select name="filter" class="rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            <option value="">ทั้งหมด</option>
            <option value="with_line" {{ request('filter') === 'with_line' ? 'selected' : '' }}>มี Line UID</option>
            <option value="without_line" {{ request('filter') === 'without_line' ? 'selected' : '' }}>ไม่มี Line UID</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
            ค้นหา
        </button>
    </form>

    <a href="{{ route('admin.line-messaging.index') }}"
       class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center justify-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
        ส่งข้อความ
    </a>
</div>

{{-- Users Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line UID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50" id="user-row-{{ $user->id }}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-gray-600 font-medium">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->role }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $user->email }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($user->line_uid)
                        <span class="font-mono text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                            {{ Str::limit($user->line_uid, 25) }}
                        </span>
                    @else
                        <span class="text-gray-400 text-sm">- ยังไม่มี -</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($user->line_display_name)
                        <span class="text-green-600">{{ $user->line_display_name }}</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button type="button" onclick="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->line_uid ?? '' }}', '{{ $user->line_display_name ?? '' }}')"
                            class="text-primary-600 hover:text-primary-800">
                        แก้ไข
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    ไม่พบผู้ใช้
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Edit Modal --}}
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="editForm" action="" method="POST">
            @csrf
            <input type="hidden" name="user_id" id="editUserId">

            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">แก้ไข Line UID - <span id="editUserName"></span></h3>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Line UID</label>
                    <input type="text" name="line_uid" id="editLineUid"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 font-mono"
                           placeholder="Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <p class="mt-1 text-xs text-gray-500">Line UID ขึ้นต้นด้วย U และมี 33 ตัวอักษร</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Line Display Name</label>
                    <input type="text" name="line_display_name" id="editLineDisplayName"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                           placeholder="ชื่อที่แสดงใน Line">
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditModal(userId, userName, lineUid, lineDisplayName) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editUserName').textContent = userName;
    document.getElementById('editLineUid').value = lineUid;
    document.getElementById('editLineDisplayName').value = lineDisplayName;
    document.getElementById('editForm').action = '{{ route("admin.line-messaging.update-uid") }}';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});
</script>
@endpush
@endsection
