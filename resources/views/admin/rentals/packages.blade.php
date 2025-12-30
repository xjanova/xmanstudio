@extends('layouts.admin')

@section('title', 'จัดการแพ็กเกจ')
@section('page-title', 'จัดการแพ็กเกจ')

@section('content')
<div class="mb-6 flex justify-end">
    <a href="{{ route('admin.rentals.packages.create') }}"
       class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        + สร้างแพ็กเกจใหม่
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แพ็กเกจ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ราคา</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ระยะเวลา</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้งาน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($packages as $package)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $package->display_name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($package->display_description, 50) }}</div>
                            </div>
                            @if($package->is_featured)
                                <span class="ml-2 px-2 py-1 text-xs bg-primary-100 text-primary-700 rounded">แนะนำ</span>
                            @endif
                            @if($package->is_popular)
                                <span class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded">ยอดนิยม</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-gray-900">฿{{ number_format($package->price) }}</div>
                        @if($package->original_price && $package->original_price > $package->price)
                            <div class="text-xs text-gray-400 line-through">฿{{ number_format($package->original_price) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $package->duration_text }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="text-green-600 font-semibold">{{ $package->active_count }}</span>
                        <span class="text-gray-400">/ {{ $package->user_rentals_count }} ทั้งหมด</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form action="{{ route('admin.rentals.packages.toggle', $package) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $package->is_active ? 'bg-green-500' : 'bg-gray-200' }}">
                                <span class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $package->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('admin.rentals.packages.edit', $package) }}"
                           class="text-primary-600 hover:underline mr-3">แก้ไข</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        ยังไม่มีแพ็กเกจ
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
