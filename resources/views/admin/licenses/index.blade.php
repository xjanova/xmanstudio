@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการ License')
@section('page-title', 'จัดการ License')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Active</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Activated</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['activated'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Expired</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['expired'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Revoked</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['revoked'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap gap-4 justify-between items-center">
        <form action="{{ route('admin.licenses.index') }}" method="GET" class="flex flex-wrap gap-4 flex-1">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                       placeholder="ค้นหา License Key...">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                </select>
            </div>
            <div>
                <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <option value="">ทุกประเภท</option>
                    <option value="demo" {{ request('type') === 'demo' ? 'selected' : '' }}>Demo</option>
                    <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ request('type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    <option value="lifetime" {{ request('type') === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                ค้นหา
            </button>
        </form>
        <a href="{{ route('admin.licenses.create') }}"
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            + สร้าง License
        </a>
    </div>
</div>

<!-- Licenses Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Key</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เครื่อง</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมดอายุ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($licenses as $license)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-mono font-medium text-gray-900">{{ $license->license_key }}</div>
                            @if($license->product)
                                <div class="text-sm text-gray-500">{{ $license->product->name }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @if($license->license_type === 'lifetime') bg-purple-100 text-purple-800
                            @elseif($license->license_type === 'yearly') bg-blue-100 text-blue-800
                            @elseif($license->license_type === 'monthly') bg-cyan-100 text-cyan-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($license->license_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @if($license->status === 'active' && !$license->isExpired()) bg-green-100 text-green-800
                            @elseif($license->status === 'revoked') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            @if($license->status === 'active' && $license->isExpired())
                                Expired
                            @else
                                {{ ucfirst($license->status) }}
                            @endif
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($license->machine_id)
                            <span class="text-green-600" title="{{ $license->machine_id }}">Activated</span>
                        @else
                            <span class="text-gray-400">Not activated</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($license->license_type === 'lifetime')
                            <span class="text-purple-600">ตลอดชีพ</span>
                        @elseif($license->expires_at)
                            {{ $license->expires_at->format('d/m/Y') }}
                            @if(!$license->isExpired() && $license->daysRemaining() <= 7)
                                <span class="text-red-600">({{ $license->daysRemaining() }} วัน)</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.licenses.show', $license) }}"
                           class="text-primary-600 hover:underline mr-3">ดู</a>
                        @if($license->status === 'active')
                            <button type="button" onclick="showRevokeModal({{ $license->id }}, '{{ $license->license_key }}')"
                                    class="text-red-600 hover:underline mr-3">ยกเลิก</button>
                        @elseif($license->status === 'revoked')
                            <form action="{{ route('admin.licenses.reactivate', $license) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:underline mr-3">เปิดใช้งาน</button>
                            </form>
                        @endif
                        @if($license->machine_id)
                            <form action="{{ route('admin.licenses.reset-machine', $license) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-orange-600 hover:underline">รีเซ็ตเครื่อง</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        ไม่พบ License
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($licenses->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $licenses->links() }}
        </div>
    @endif
</div>

<!-- Revoke Modal -->
<div id="revokeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="revokeForm" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยกเลิก License</h3>
                <p class="text-gray-600 mb-4">License: <span id="revokeLicenseKey" class="font-mono font-bold"></span></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล (ถ้ามี)</label>
                    <textarea name="reason" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideRevokeModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ยืนยันยกเลิก</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showRevokeModal(licenseId, licenseKey) {
        document.getElementById('revokeForm').action = `/admin/licenses/${licenseId}/revoke`;
        document.getElementById('revokeLicenseKey').textContent = licenseKey;
        document.getElementById('revokeModal').classList.remove('hidden');
        document.getElementById('revokeModal').classList.add('flex');
    }

    function hideRevokeModal() {
        document.getElementById('revokeModal').classList.add('hidden');
        document.getElementById('revokeModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
