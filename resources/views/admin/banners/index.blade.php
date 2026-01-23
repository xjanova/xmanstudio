@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Banner Management')
@section('page-title', 'Banner Management (จัดการแบนเนอร์)')

@section('content')
<div class="space-y-6">
    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 mb-1">เกี่ยวกับแบนเนอร์</h4>
                <p class="text-sm text-blue-800">
                    อัปโหลดรูปภาพแบนเนอร์เองและกำหนดเวลาการแสดงผล พร้อมติดตาม Views และ Clicks
                </p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">แบนเนอร์ทั้งหมด</h3>
        <a href="{{ route('admin.banners.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            เพิ่มแบนเนอร์ใหม่
        </a>
    </div>

    <!-- Banners List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แบนเนอร์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำแหน่ง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หน้าที่แสดง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ระยะเวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถิติ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($banners as $banner)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-16 w-auto object-contain rounded mr-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $banner->title }}</div>
                                        @if ($banner->description)
                                            <div class="text-xs text-gray-500 mt-1">{{ Str::limit($banner->description, 50) }}</div>
                                        @endif
                                        @if ($banner->link_url)
                                            <a href="{{ $banner->link_url }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-1 block">
                                                {{ Str::limit($banner->link_url, 40) }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $banner->position === 'header' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $banner->position === 'sidebar' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $banner->position === 'in-content' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $banner->position === 'footer' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $banner->position === 'between-products' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($banner->position) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    @if ($banner->pages && in_array('all', $banner->pages))
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">ทุกหน้า</span>
                                    @else
                                        @foreach ($banner->pages ?? [] as $page)
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs mr-1">{{ $page }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if ($banner->start_date)
                                    <div>{{ $banner->start_date->format('d/m/Y H:i') }}</div>
                                @else
                                    <div class="text-gray-400">ไม่กำหนด</div>
                                @endif
                                @if ($banner->end_date)
                                    <div>{{ $banner->end_date->format('d/m/Y H:i') }}</div>
                                @else
                                    <div class="text-gray-400">ไม่กำหนด</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="text-gray-900">👁️ {{ number_format($banner->views) }}</div>
                                <div class="text-gray-900">👆 {{ number_format($banner->clicks) }}</div>
                                @if ($banner->views > 0)
                                    <div class="text-xs text-gray-500">CTR: {{ $banner->ctr }}%</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded
                                        {{ $banner->status_text === 'กำลังแสดง' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $banner->status_text === 'รอเริ่ม' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $banner->status_text === 'หมดอายุ' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $banner->status_text === 'ปิดใช้งาน' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $banner->status_text }}
                                    </span>
                                </div>
                                <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="relative inline-flex items-center cursor-pointer">
                                        <div class="w-11 h-6 {{ $banner->enabled ? 'bg-primary-600' : 'bg-gray-200' }} rounded-full peer peer-focus:ring-4 peer-focus:ring-primary-300">
                                            <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-all {{ $banner->enabled ? 'translate-x-full' : '' }}"></div>
                                        </div>
                                        <span class="ml-2 text-sm font-medium {{ $banner->enabled ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $banner->enabled ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="text-primary-600 hover:text-primary-900 mr-3">แก้ไข</a>
                                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2">ยังไม่มีแบนเนอร์</p>
                                <a href="{{ route('admin.banners.create') }}" class="mt-4 inline-block text-primary-600 hover:text-primary-900">
                                    สร้างแบนเนอร์แรกของคุณ
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
