@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ใบอนุญาตของฉัน')
@section('page-title')<x-bi th="ใบอนุญาตของฉัน" en="My Licenses" />@endsection
@section('page-description')<x-bi th="จัดการ License Key ซอฟต์แวร์ทั้งหมดของคุณ" en="Manage all your software license keys" />@endsection

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-6 sm:p-8 mb-8 shadow-xl">
    <x-page-art art="hero-licenses" :opacity="26" :scrim="false" />
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <x-bi th="ใบอนุญาตซอฟต์แวร์" en="Software Licenses" />
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base"><x-bi th="จัดการ License Key ทั้งหมดของคุณในที่เดียว" en="Manage all your license keys in one place" /></p>
        </div>
        <div class="flex items-center gap-2">
            <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl text-white text-sm font-medium">
                <span class="opacity-80"><x-bi th="รวม" en="Total" /></span>
                <span class="ml-1 text-lg font-bold">{{ $stats['total'] }}</span>
                <span class="opacity-80 ml-1"><x-bi th="ใบอนุญาต" en="Licenses" /></span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards with Gradients -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8">
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><x-bi th="ใบอนุญาตทั้งหมด" en="Total Licenses" /></p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-gray-500 to-gray-700 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><x-bi th="ใช้งานอยู่" en="Active" /></p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl p-5 sm:p-6 transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><x-bi th="หมดอายุ" en="Expired" /></p>
                <p class="text-2xl sm:text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-gradient-to-br from-red-400 to-rose-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Premium Filters -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="" method="GET" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
        <div class="flex-1 sm:flex-none">
            <select name="status" class="w-full sm:w-auto rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500 shadow-sm">
                <option value="all">สถานะทั้งหมด / All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานอยู่ / Active</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ / Expired</option>
                <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>ถูกยกเลิก / Revoked</option>
            </select>
        </div>

        <div class="flex-1 sm:flex-none">
            <select name="type" class="w-full sm:w-auto rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-purple-500 focus:border-purple-500 shadow-sm">
                <option value="all">ประเภททั้งหมด / All Types</option>
                <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>รายเดือน / Monthly</option>
                <option value="yearly" {{ request('type') === 'yearly' ? 'selected' : '' }}>รายปี / Yearly</option>
                <option value="lifetime" {{ request('type') === 'lifetime' ? 'selected' : '' }}>ตลอดชีพ / Lifetime</option>
                <option value="demo" {{ request('type') === 'demo' ? 'selected' : '' }}>ทดลองใช้ / Trial</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 text-sm font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                <span class="flex items-center justify-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <x-bi th="กรอง" en="Filter" />
                </span>
            </button>
            @if(request()->hasAny(['status', 'type']))
            <a href="{{ route('customer.licenses') }}" class="flex-1 sm:flex-none px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all text-center">
                <x-bi th="ล้าง" en="Clear" />
            </a>
            @endif
        </div>
    </form>
</div>

@if(! empty($mobileOffer))
{{-- คีย์ของแอปมือถือออกให้ต่อมือถือ ไม่ใช่ต่อบัญชี จึงไม่เป็นแถวในตาราง — บอกไว้ตรงนี้ให้ผู้ที่มี Anti X --}}
<div class="mb-6 rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-5 flex flex-col sm:flex-row sm:items-center gap-4" data-mobile-offer>
    <div class="flex-1">
        <p class="font-semibold text-gray-900 dark:text-white">
            <x-bi th="แอปมือถือ Anti X (Android) — ฟรีสำหรับผู้ใช้ Anti X" en="Anti X mobile app (Android) — free" />
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            <x-bi th="จับคู่กับเครื่องด้วย QR ในแอป Anti X · ไลเซนส์ฟรีออกให้มือถือเองตอนเปิดครั้งแรก" en="Pair it with a QR in Anti X · its free licence is issued on first start" />
            @if($mobileOffer['version']) · v{{ $mobileOffer['version']->version }}@if($mobileOffer['version']->file_size) · {{ $mobileOffer['version']->file_size_formatted }}@endif @endif
        </p>
    </div>
    @if($mobileOffer['url'])
    <a href="{{ $mobileOffer['url'] }}" data-mobile-download
       class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl hover:from-emerald-600 hover:to-green-700 font-semibold shadow transition-all">
        <x-bi th="ดาวน์โหลดแอปมือถือ" en="Download the mobile app" class="text-white" />
    </a>
    @else
    <span class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-500 text-sm" data-coming-soon>เร็วๆ นี้</span>
    @endif
</div>
@endif

<!-- License List -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"><x-bi th="ผลิตภัณฑ์" en="Product" /></th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">License Key</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"><x-bi th="ประเภท" en="Type" /></th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"><x-bi k="common.status" /></th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"><x-bi th="หมดอายุ" en="Expires" /></th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"><x-bi th="การเปิดใช้งาน" en="Activations" /></th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($licenses as $license)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $license->product?->name ?? 'Software License' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <code class="text-sm bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 px-3 py-1.5 rounded-lg font-mono text-gray-700 dark:text-gray-300">{{ Str::limit($license->license_key, 20) }}</code>
                            <button onclick="copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว! / License key copied!')" class="ml-2 p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-all" title="{{ bi('common.copy') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                            {{ $license->license_type === 'lifetime' ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ / Lifetime' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี / Yearly' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน / Monthly' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง / Trial' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                            {{ $license->status === 'active' && ! $license->isExpired() ? 'bg-gradient-to-r from-emerald-400 to-green-500 text-white' : '' }}
                            {{ $license->status === 'expired' || ($license->status === 'active' && $license->isExpired()) ? 'bg-gradient-to-r from-red-400 to-rose-500 text-white' : '' }}
                            {{ $license->status === 'revoked' ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : '' }}
                        ">
                            {{ $license->status === 'active' && ! $license->isExpired() ? 'ใช้งานอยู่ / Active' : '' }}
                            {{ $license->status === 'expired' || ($license->status === 'active' && $license->isExpired()) ? 'หมดอายุ / Expired' : '' }}
                            {{ $license->status === 'revoked' ? 'ถูกยกเลิก / Revoked' : '' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        @if($license->expires_at)
                            <div>{{ $license->expires_at->format('d/m/Y') }}</div>
                            @if($license->expires_at->isPast())
                                <span class="text-xs text-red-500">(<x-bi th="หมดอายุแล้ว" en="Expired" />)</span>
                            @elseif($license->expires_at->diffInDays() < 30)
                                <span class="text-xs text-yellow-600">({{ $license->expires_at->diffForHumans() }})</span>
                            @endif
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400 font-medium"><x-bi th="ไม่มีวันหมดอายุ" en="No expiry" /></span>
                        @endif
                        @if($license->auto_renew && ($renewable[$license->id] ?? false))
                            <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300"><x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /></span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center">
                            <span class="text-gray-900 dark:text-white font-medium">{{ $license->activation_count }}</span>
                            <span class="text-gray-400 mx-1">/</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $license->max_activations ?? '∞' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                        @if($renewable[$license->id] ?? false)
                        <a href="{{ route('customer.licenses.show', $license) }}#renew" class="inline-flex items-center px-4 py-2 border border-violet-300 dark:border-violet-600 text-violet-700 dark:text-violet-300 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all font-medium">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <x-bi th="ต่ออายุ" en="Renew" />
                        </a>
                        @endif
                        <a href="{{ route('customer.licenses.show', $license) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
                            <x-bi k="common.view_details" />
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full mb-4 shadow-xl">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white"><x-bi th="ไม่พบใบอนุญาต" en="No licenses found" /></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><x-bi th="ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาตแรกของคุณ" en="Purchase a product to get your first license" /></p>
                        <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-medium rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <x-bi th="ดูผลิตภัณฑ์" en="Browse Products" />
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($licenses as $license)
        <a href="{{ route('customer.licenses.show', $license) }}" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $license->product?->name ?? 'Software License' }}</h3>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full shadow-sm
                            {{ $license->status === 'active' && ! $license->isExpired() ? 'bg-gradient-to-r from-emerald-400 to-green-500 text-white' : '' }}
                            {{ $license->status === 'expired' || ($license->status === 'active' && $license->isExpired()) ? 'bg-gradient-to-r from-red-400 to-rose-500 text-white' : '' }}
                            {{ $license->status === 'revoked' ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : '' }}
                        ">
                            {{ $license->status === 'active' && ! $license->isExpired() ? 'ใช้งาน / Active' : '' }}
                            {{ $license->status === 'expired' || ($license->status === 'active' && $license->isExpired()) ? 'หมดอายุ / Expired' : '' }}
                            {{ $license->status === 'revoked' ? 'ยกเลิก / Revoked' : '' }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center">
                        <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono text-gray-600 dark:text-gray-400">{{ Str::limit($license->license_key, 16) }}</code>
                        <button onclick="event.preventDefault(); copyToClipboard('{{ $license->license_key }}', 'คัดลอก License Key แล้ว! / License key copied!')" class="ml-2 p-1 text-gray-400 hover:text-purple-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="px-2 py-0.5 rounded-full shadow-sm
                            {{ $license->license_type === 'lifetime' ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-gradient-to-r from-yellow-400 to-orange-400 text-white' : '' }}
                        ">
                            {{ $license->license_type === 'lifetime' ? 'ตลอดชีพ / Lifetime' : '' }}
                            {{ $license->license_type === 'yearly' ? 'รายปี / Yearly' : '' }}
                            {{ $license->license_type === 'monthly' ? 'รายเดือน / Monthly' : '' }}
                            {{ $license->license_type === 'demo' ? 'ทดลอง / Trial' : '' }}
                        </span>
                        <span>•</span>
                        @if($license->expires_at)
                            <span class="{{ $license->expires_at->isPast() ? 'text-red-500' : '' }}">
                                <x-bi th="หมดอายุ" en="Expires" /> {{ $license->expires_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400"><x-bi th="ไม่มีวันหมดอายุ" en="No expiry" /></span>
                        @endif
                        @if($renewable[$license->id] ?? false)
                            <span class="px-2 py-0.5 rounded-full font-medium {{ $license->auto_renew ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300' }}">
                                @if($license->auto_renew) <x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /> @else <x-bi th="แตะเพื่อต่ออายุ" en="Tap to renew" /> @endif
                            </span>
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
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full mb-4 shadow-xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900 dark:text-white"><x-bi th="ไม่พบใบอนุญาต" en="No licenses found" /></p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><x-bi th="ซื้อผลิตภัณฑ์เพื่อรับใบอนุญาตแรกของคุณ" en="Purchase a product to get your first license" /></p>
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-sm font-medium rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                <x-bi th="ดูผลิตภัณฑ์" en="Browse Products" />
            </a>
        </div>
        @endforelse
    </div>

    @if($licenses->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        {{ $licenses->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
