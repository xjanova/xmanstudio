@extends('layouts.admin')

@section('title', 'Download Logs - ' . $product->name)
@section('page-title', 'Download Logs: ' . $product->name)

@section('content')
<!-- Breadcrumb -->
<div class="mb-6">
    <a href="{{ route('admin.products.versions.index', $product) }}" class="text-primary-600 hover:underline flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        กลับไปจัดการเวอร์ชัน
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ดาวน์โหลดทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($logs->total()) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวอร์ชัน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เวลา</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($logs as $log)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($log->user)
                            <div class="text-sm font-medium text-gray-900">{{ $log->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $log->user->email }}</div>
                        @else
                            <span class="text-gray-400">Guest</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($log->licenseKey)
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ Str::limit($log->licenseKey->license_key, 20) }}</code>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        v{{ $log->productVersion->version }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->ip_address ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->downloaded_at->format('d/m/Y H:i:s') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        ยังไม่มีประวัติการดาวน์โหลด
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
