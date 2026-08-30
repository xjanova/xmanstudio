@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ชุดตัวมายด์')
@section('page-title', 'ชุดตัวมายด์')

@section('content')
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-fuchsia-600 via-purple-600 to-indigo-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">ชุดตัวมายด์ (GigGok)</h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">
                ชุดแต่งตัว ตัวละครใหม่ และของประดับเวที ที่แอปโหลดไปใช้
            </p>
        </div>
        <a href="{{ route('admin.packs.create') }}"
           class="inline-flex items-center px-4 py-2 bg-white text-purple-600 rounded-xl hover:bg-gray-100 transition-all font-medium shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            เพิ่มชุด
        </a>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <th class="px-6 py-3">ชุด</th>
                    <th class="px-6 py-3">ชนิด</th>
                    <th class="px-6 py-3">ราคา</th>
                    <th class="px-6 py-3">ไฟล์</th>
                    <th class="px-6 py-3">สถานะ</th>
                    <th class="px-6 py-3 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($packs as $pack)
                    @php($version = $pack->product?->latestVersion())
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($pack->preview_path)
                                    <img src="{{ asset($pack->preview_path) }}" alt=""
                                         class="w-12 h-12 rounded-lg object-cover bg-gray-100">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
                                @endif
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $pack->product?->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $pack->pack_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ ['character' => 'ตัวละคร', 'outfit' => 'ชุดแต่งตัว', 'prop' => 'ของประดับ'][$pack->kind] ?? $pack->kind }}
                            @if($pack->requires)
                                <div class="text-xs text-gray-400">ต้องมี {{ $pack->requires }} ก่อน</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                            @if((float) ($pack->product?->price ?? 0) <= 0)
                                <span class="text-emerald-600 font-medium">แจกฟรี</span>
                            @else
                                {{ number_format((float) $pack->product->price, 0) }} ฿
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($version && $version->storage_path)
                                <span class="text-gray-700 dark:text-gray-300">{{ $version->file_size_formatted }}</span>
                                <div class="text-xs text-gray-400">v{{ $version->version }}</div>
                            @else
                                {{-- ชุดที่ยังไม่มีไฟล์ขึ้นร้านได้ แต่กดโหลดแล้วจะได้ 404
                                     ต้องเห็นชัดตรงนี้ ไม่ใช่ไปรู้ตอนลูกค้าจ่ายเงินแล้ว --}}
                                <span class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-medium">
                                    ยังไม่มีไฟล์
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.packs.toggle', $pack) }}">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-medium {{ $pack->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $pack->is_active ? 'เปิดขาย' : 'ปิดขาย' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.packs.edit', $pack) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">แก้ไข</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                            ยังไม่มีชุดในร้าน — กด "เพิ่มชุด" เพื่อเริ่ม
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($packs->hasPages())
        <div class="px-6 py-4">{{ $packs->links() }}</div>
    @endif
</div>
@endsection
