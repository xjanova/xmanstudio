@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - หมวดหมู่')
@section('page-title', 'LocalVPN - จัดการหมวดหมู่ BitTorrent')

@section('content')
@include('admin.localvpn._tabs')

{{-- Create Category Form --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">เพิ่มหมวดหมู่ใหม่</h3>
    <form method="POST" action="{{ route('admin.localvpn.torrent.categories.store') }}" class="flex flex-wrap gap-4 items-end">
        @csrf
        <div class="w-16">
            <label class="block text-sm font-medium text-gray-700 mb-1">ไอคอน</label>
            <input type="text" name="icon" placeholder="📁" maxlength="4"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 text-center text-xl">
        </div>
        <div class="flex-1 min-w-[180px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหมวดหมู่</label>
            <input type="text" name="name" required placeholder="เช่น ซอฟต์แวร์, เกม..."
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" name="slug" placeholder="auto-generate"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div class="w-24">
            <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
            <input type="number" name="sort_order" value="0" min="0"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div class="flex items-center gap-4">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_adult" value="1" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span class="text-sm text-gray-700">18+</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                <span class="text-sm text-gray-700">ใช้งาน</span>
            </label>
        </div>
        <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            เพิ่ม
        </button>
    </form>
    @if($errors->any())
        <div class="mt-3 text-sm text-red-600">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
</div>

{{-- Categories Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">หมวดหมู่ทั้งหมด ({{ count($categories ?? []) }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium w-16">ไอคอน</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Slug</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ไฟล์</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">18+</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ลำดับ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories ?? [] as $category)
                <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                    {{-- View Mode --}}
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center text-xl">{{ $category->icon ?? '📁' }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 font-medium text-gray-900">{{ $category->name }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-gray-500 font-mono text-xs">{{ $category->slug }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center text-gray-600">{{ number_format($category->files_count ?? 0) }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center">
                            @if($category->is_adult)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">18+</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center">
                            @if($category->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                            @endif
                        </td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center text-gray-500">{{ $category->sort_order }}</td>
                    </template>
                    <template x-if="!editing">
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="editing = true" class="text-violet-600 hover:text-violet-800" title="แก้ไข">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="{{ route('admin.localvpn.torrent.categories.delete', $category) }}"
                                      onsubmit="return confirm('ลบหมวดหมู่ \'{{ $category->name }}\'? ไฟล์ในหมวดหมู่นี้จะไม่มีหมวดหมู่')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="ลบ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </template>

                    {{-- Edit Mode --}}
                    <template x-if="editing">
                        <td colspan="8" class="py-3 px-4">
                            <form method="POST" action="{{ route('admin.localvpn.torrent.categories.update', $category) }}" class="flex flex-wrap gap-3 items-end">
                                @csrf
                                @method('PUT')
                                <input type="text" name="icon" value="{{ $category->icon }}" class="w-16 border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500 text-center text-xl" maxlength="4">
                                <input type="text" name="name" value="{{ $category->name }}" required class="flex-1 min-w-[150px] border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                                <input type="text" name="slug" value="{{ $category->slug }}" class="w-32 border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" class="w-20 border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="is_adult" value="1" {{ $category->is_adult ? 'checked' : '' }} class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                    <span class="text-xs text-gray-700">18+</span>
                                </label>
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                    <span class="text-xs text-gray-700">ใช้งาน</span>
                                </label>
                                <button type="submit" class="px-3 py-1.5 bg-violet-600 text-white rounded-lg text-sm hover:bg-violet-700 transition-colors">บันทึก</button>
                                <button type="button" @click="editing = false" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-colors">ยกเลิก</button>
                            </form>
                        </td>
                    </template>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-500">ยังไม่มีหมวดหมู่</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
