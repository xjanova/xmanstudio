@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Google Ads Management')
@section('page-title', 'Google Ads Management (จัดการตำแหน่งโฆษณา)')

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

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 mb-1">เกี่ยวกับ Google Ads</h4>
                <p class="text-sm text-blue-800">
                    จัดการตำแหน่งโฆษณา Google AdSense ในเว็บไซต์ สามารถเปิด/ปิด และกำหนดว่าจะแสดงในหน้าไหนได้
                </p>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">ตำแหน่งโฆษณาทั้งหมด</h3>
        <a href="{{ route('admin.ads.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            เพิ่มตำแหน่งโฆษณา
        </a>
    </div>

    <!-- Ad Placements List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำแหน่ง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หน้าที่แสดง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($placements as $placement)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $placement->name }}</div>
                                <div class="text-xs text-gray-500">{{ $placement->slug }}</div>
                                @if($placement->description)
                                    <div class="text-xs text-gray-400 mt-1">{{ $placement->description }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $placement->position === 'header' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $placement->position === 'sidebar' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $placement->position === 'in-content' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $placement->position === 'footer' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $placement->position === 'between-products' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ ucfirst($placement->position) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                @if($placement->pages && in_array('all', $placement->pages))
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">ทุกหน้า</span>
                                @else
                                    @foreach($placement->pages ?? [] as $page)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs mr-1">{{ $page }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $placement->priority }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('admin.ads.toggle', $placement) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-11 h-6 {{ $placement->enabled ? 'bg-primary-600' : 'bg-gray-200' }} rounded-full peer peer-focus:ring-4 peer-focus:ring-primary-300">
                                        <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-all {{ $placement->enabled ? 'translate-x-full' : '' }}"></div>
                                    </div>
                                    <span class="ml-2 text-sm font-medium {{ $placement->enabled ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $placement->enabled ? 'เปิด' : 'ปิด' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.ads.edit', $placement) }}" class="text-primary-600 hover:text-primary-900 mr-3">แก้ไข</a>
                            <form action="{{ route('admin.ads.destroy', $placement) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            ไม่มีตำแหน่งโฆษณา
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Position Guide -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">คำแนะนำตำแหน่งโฆษณา</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-semibold mr-2">HEADER</span>
                    <h4 class="font-medium text-gray-900">Header Top</h4>
                </div>
                <p class="text-sm text-gray-600">แสดงที่ด้านบนสุดของเว็บไซต์ ก่อน navigation bar - เหมาะกับ banner ads</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold mr-2">SIDEBAR</span>
                    <h4 class="font-medium text-gray-900">Sidebar</h4>
                </div>
                <p class="text-sm text-gray-600">แสดงที่ด้านข้างของเนื้อหา - เหมาะกับ skyscraper ads (160x600)</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold mr-2">IN-CONTENT</span>
                    <h4 class="font-medium text-gray-900">In Content</h4>
                </div>
                <p class="text-sm text-gray-600">แสดงระหว่างเนื้อหา ตรงกลางหน้า - มี engagement สูง</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-semibold mr-2">FOOTER</span>
                    <h4 class="font-medium text-gray-900">Footer Above</h4>
                </div>
                <p class="text-sm text-gray-600">แสดงก่อน footer ด้านล่างของหน้า</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold mr-2">BETWEEN</span>
                    <h4 class="font-medium text-gray-900">Between Products</h4>
                </div>
                <p class="text-sm text-gray-600">แสดงระหว่างรายการสินค้า/บริการ - native ads</p>
            </div>
        </div>
    </div>
</div>
@endsection
