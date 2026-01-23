@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการทีม Metal-X')
@section('page-title', 'จัดการทีม Metal-X')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">สมาชิกทั้งหมด</div>
        <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">เปิดใช้งาน</div>
        <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">ปิดใช้งาน</div>
        <div class="text-2xl font-bold text-gray-400">{{ $stats['inactive'] }}</div>
    </div>
</div>

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <p class="text-gray-600">จัดการสมาชิกทีม Metal-X</p>
    </div>
    <div class="flex gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา..."
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <select name="active" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                <option value="">ทั้งหมด</option>
                <option value="yes" {{ request('active') === 'yes' ? 'selected' : '' }}>เปิดใช้งาน</option>
                <option value="no" {{ request('active') === 'no' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">ค้นหา</button>
        </form>
        <a href="{{ route('admin.metal-x.create') }}"
           class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            + เพิ่มสมาชิกใหม่
        </a>
    </div>
</div>

<!-- Members Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สมาชิก</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำแหน่ง</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">โซเชียล</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($members as $member)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $member->order }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($member->image)
                                <img src="{{ Storage::url($member->image) }}" alt="{{ $member->name }}"
                                     class="w-10 h-10 rounded-full object-cover mr-3">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                @if($member->name_th)
                                    <div class="text-sm text-gray-500">{{ $member->name_th }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $member->role }}</div>
                        @if($member->role_th)
                            <div class="text-sm text-gray-500">{{ $member->role_th }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex space-x-2">
                            @if($member->youtube_url)
                                <a href="{{ $member->youtube_url }}" target="_blank" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            @endif
                            @if($member->facebook_url)
                                <a href="{{ $member->facebook_url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if($member->instagram_url)
                                <a href="{{ $member->instagram_url }}" target="_blank" class="text-pink-500 hover:text-pink-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                </a>
                            @endif
                            @if($member->twitter_url)
                                <a href="{{ $member->twitter_url }}" target="_blank" class="text-blue-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                            @endif
                            @if($member->tiktok_url)
                                <a href="{{ $member->tiktok_url }}" target="_blank" class="text-gray-800 hover:text-gray-900">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                </a>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            {{ $member->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $member->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.metal-x.edit', $member) }}"
                           class="text-primary-600 hover:underline mr-3">แก้ไข</a>
                        <button type="button" onclick="confirmDelete({{ $member->id }}, '{{ $member->name }}')"
                                class="text-red-600 hover:underline">ลบ</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        ไม่พบข้อมูลสมาชิก
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($members->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $members->links() }}
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยืนยันการลบ</h3>
                <p class="text-gray-600">คุณต้องการลบสมาชิก "<span id="deleteMemberName"></span>" หรือไม่?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideDeleteModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ลบ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(memberId, memberName) {
        document.getElementById('deleteForm').action = `/admin/metal-x/${memberId}`;
        document.getElementById('deleteMemberName').textContent = memberName;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
