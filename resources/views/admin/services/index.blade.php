@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการบริการ')
@section('page-title', 'จัดการบริการ')

@section('content')
<!-- Header -->
<div class="mb-6 flex justify-between items-center">
    <div>
        <p class="text-gray-600">จัดการรายการบริการทั้งหมดของบริษัท</p>
    </div>
    <a href="{{ route('admin.services.create') }}"
       class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        + เพิ่มบริการใหม่
    </a>
</div>

<!-- Services Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บริการ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ราคาเริ่มต้น</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($services as $service)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $service->order }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($service->icon)
                                <span class="text-2xl mr-3">{{ $service->icon }}</span>
                            @endif
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $service->name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($service->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $service->formatted_price }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                {{ $service->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $service->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                            </span>
                            @if($service->is_featured)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    แนะนำ
                                </span>
                            @endif
                            @if($service->is_coming_soon)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                    Coming Soon
                                    @if($service->coming_soon_until)
                                        <span class="ml-1">({{ $service->coming_soon_until->format('d/m') }})</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.services.edit', $service) }}"
                           class="text-primary-600 hover:underline mr-3">แก้ไข</a>
                        <form action="{{ route('admin.services.toggle-coming-soon', $service) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="{{ $service->is_coming_soon ? 'text-orange-600' : 'text-gray-400' }} hover:underline mr-3" title="{{ $service->is_coming_soon ? 'ปิด Coming Soon' : 'เปิด Coming Soon' }}">
                                CS
                            </button>
                        </form>
                        <form action="{{ route('admin.services.toggle', $service) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="{{ $service->is_active ? 'text-yellow-600' : 'text-green-600' }} hover:underline mr-3">
                                {{ $service->is_active ? 'ปิด' : 'เปิด' }}
                            </button>
                        </form>
                        <button type="button" onclick="confirmDelete({{ $service->id }}, '{{ $service->name }}')"
                                class="text-red-600 hover:underline">ลบ</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        ไม่พบข้อมูลบริการ
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($services->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $services->links() }}
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
                <p class="text-gray-600">คุณต้องการลบบริการ "<span id="deleteServiceName"></span>" หรือไม่?</p>
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
    function confirmDelete(serviceId, serviceName) {
        document.getElementById('deleteForm').action = `/admin/services/${serviceId}`;
        document.getElementById('deleteServiceName').textContent = serviceName;
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
