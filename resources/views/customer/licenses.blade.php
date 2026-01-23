@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ใบอนุญาตของฉัน')
@section('page-title', 'ใบอนุญาตของฉัน')
@section('page-description', 'จัดการ License Key ซอฟต์แวร์ทั้งหมดของคุณ')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">ใบอนุญาตทั้งหมด</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-xl">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">ใช้งานอยู่</p>
                <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">หมดอายุ</p>
                <p class="text-2xl sm:text-3xl font-bold text-red-600 mt-1">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-red-100 rounded-xl">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="all">สถานะทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>ถูกยกเลิก</option>
            </select>
        </div>

        <div class="flex-1 sm:flex-none">
            <select name="type" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="all">ประเภททั้งหมด</option>
                <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>รายเดือน</option>
                <option value="yearly" {{ request('type') === 'yearly' ? 'selected' : '' }}>รายปี</option>
                <option value="lifetime" {{ request('type') === 'lifetime' ? 'selected' : '' }}>ตลอดชีพ</option>
                <option value="demo" {{ request('type') === 'demo' ? 'selected' : '' }}>ทดลองใช้</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium transition-colors">
                <span class="flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    กรอง
                </span>
            </button>
            @if(request()->hasAny(['status', 'type']))
            <a href="{{ route('customer.licenses') }}" class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors text-center">
                ล้าง
            </a>
            @endif
        </div>
    </form>
</div>

<!-- License List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ผลิตภัณฑ์</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">License Key</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">หมดอายุ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">การเปิดใช้งาน</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($licenses as $license)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $license->product?->name ?? 'Software License' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <code class="text-sm bg-gray-100 px-2.5 py-1 rounded-lg font-mono text-gray-700">{{ Str::limit($license->license_key, 20) }}</code>
                            <button onclick="copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว!')" class="ml-2 p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="คัดลอก">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                            {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                            {{ $license->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $license->status === 'expired' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $license->status === 'revoked' ? 'bg-gray-100 text-gray-700' : '' }}
                        ">
                            {{ $license->status === 'active' ? 'ใช้งานอยู่' : '' }}
                            {{ $license->status === 'expired' ? 'หมดอายุ' : '' }}
                            {{ $license->status === 'revoked' ? 'ถูกยกเลิก' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($license->expires_at)
                            <div>{{ $license->expires_at->format('d/m/Y') }}</div>
                            @if($license->expires_at->isPast())
                                <span class="text-xs text-red-500">(หมดอายุแล้ว)</span>
                            @elseif($license->expires_at->diffInDays() < 30)
                                <span class="text-xs text-yellow-600">({{ $license->expires_at->diffForHumans() }})</span>
                            @endif
                        @else
                            <span class="text-green-600 font-medium">ไม่มีวันหมดอายุ</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center">
                            <span class="text-gray-900 font-medium">{{ $license->activation_count }}</span>
                            <span class="text-gray-400 mx-1">/</span>
                            <span class="text-gray-500">{{ $license->max_activations ?? '∞' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('customer.licenses.show', $license) }}" class="inline-flex items-center px-3 py-1.5 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors font-medium">
                            ดูรายละเอียด
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-900">ไม่พบใบอนุญาต</p>
                        <p class="text-sm text-gray-500 mt-1">ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาตแรกของคุณ</p>
                        <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            ดูผลิตภัณฑ์
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($licenses as $license)
        <a href="{{ route('customer.licenses.show', $license) }}" class="block p-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-medium text-gray-900 truncate">{{ $license->product?->name ?? 'Software License' }}</h3>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                            {{ $license->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $license->status === 'expired' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $license->status === 'revoked' ? 'bg-gray-100 text-gray-700' : '' }}
                        ">
                            {{ $license->status === 'active' ? 'ใช้งาน' : '' }}
                            {{ $license->status === 'expired' ? 'หมดอายุ' : '' }}
                            {{ $license->status === 'revoked' ? 'ยกเลิก' : '' }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center">
                        <code class="text-xs bg-gray-100 px-2 py-0.5 rounded font-mono text-gray-600">{{ Str::limit($license->license_key, 16) }}</code>
                        <button onclick="event.preventDefault(); copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว!')" class="ml-2 p-1 text-gray-400 hover:text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span class="px-2 py-0.5 rounded-full
                            {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง' : '' }}
                        </span>
                        <span>•</span>
                        @if($license->expires_at)
                            <span class="{{ $license->expires_at->isPast() ? 'text-red-500' : '' }}">
                                หมดอายุ {{ $license->expires_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-green-600">ไม่มีวันหมดอายุ</span>
                        @endif
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @empty
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900">ไม่พบใบอนุญาต</p>
            <p class="text-sm text-gray-500 mt-1">ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาตแรกของคุณ</p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                ดูผลิตภัณฑ์
            </a>
        </div>
        @endforelse
    </div>

    @if($licenses->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
        {{ $licenses->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
