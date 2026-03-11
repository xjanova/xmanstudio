{{-- Admin Sidebar Navigation (shared across themes) --}}
{{-- Uses Alpine.js for collapsible sections --}}
@php
    $pendingCommissions = \App\Models\AffiliateCommission::where('status', 'pending')->count();
    $totalUsers = \App\Models\User::count();
    $newBugReports = \App\Models\BugReport::byStatus('new')->count();
    $pendingQuotations = \App\Models\Quotation::pending()->count();
    $pendingPaymentOrders = \App\Models\Order::where('payment_status', 'verifying')->count();
    $pendingTopups = \App\Models\WalletTopup::where('status', 'pending')->count();
    $activeDevices = \App\Models\SmsCheckerDevice::where('status', 'active')->count();
    $pendingOrders = \App\Models\Order::whereNotNull('unique_payment_amount_id')
        ->whereIn('sms_verification_status', ['pending', 'matched'])
        ->where('payment_status', '!=', 'paid')
        ->count();
@endphp

{{-- Menu link helper class --}}
@php
    $linkClass = 'flex items-center pl-7 pr-2 py-1.5 text-[13px] rounded-md transition-colors';
    $linkActive = 'bg-white/10 text-white font-medium';
    $linkInactive = 'text-gray-500 hover:bg-white/5 hover:text-gray-300';
    $iconClass = 'w-3.5 h-3.5 mr-2 flex-shrink-0 opacity-60';
    $sectionClass = 'mt-1';
    $headerClass = 'flex items-center gap-1.5 px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-300 border-l-2 border-indigo-500/40';
    $headerBtnClass = 'w-full flex items-center justify-between px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-300 border-l-2 border-transparent hover:border-indigo-500/40 hover:text-white transition-all';
    $headerBtnActiveClass = 'w-full flex items-center justify-between px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-gray-200 border-l-2 border-indigo-400 hover:text-white transition-all';
    $subMenuClass = 'ml-2 pl-1 border-l border-white/5';
@endphp

{{-- Dashboard --}}
<div class="pb-1 mb-1 border-b border-white/5">
    <a href="{{ route('admin.analytics.index') }}"
       class="flex items-center px-2 py-1.5 text-[13px] rounded-md transition-colors {{ request()->routeIs('admin.analytics*') ? $linkActive : $linkInactive }}">
        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Analytics
    </a>
    <a href="{{ route('admin.mockup') }}"
       class="flex items-center px-2 py-1.5 text-[13px] rounded-md transition-colors {{ request()->routeIs('admin.mockup') ? $linkActive : $linkInactive }}">
        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        Premium Dashboard
    </a>
</div>

{{-- การเช่า --}}
<div x-data="{ open: {{ request()->routeIs('admin.rentals.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>การเช่า</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.rentals.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.rentals.index') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            รายการเช่า
        </a>
        <a href="{{ route('admin.rentals.payments') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.rentals.payments') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            การชำระเงิน
        </a>
        <a href="{{ route('admin.rentals.packages') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.rentals.packages*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            แพ็กเกจ
        </a>
        <a href="{{ route('admin.rentals.reports') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.rentals.reports') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            รายงาน
        </a>
    </div>
</div>

{{-- เนื้อหา --}}
<div class="{{ $sectionClass }}">
    <div class="{{ $headerClass }}">เนื้อหา</div>
    <a href="{{ route('admin.services.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.services*') ? $linkActive : $linkInactive }}">
        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
        บริการ
    </a>
</div>

{{-- ผลิตภัณฑ์ --}}
<div x-data="{ open: {{ request()->routeIs('admin.products.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>ผลิตภัณฑ์</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.products.categories.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.products.categories*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            หมวดหมู่
        </a>
        <a href="{{ route('admin.products.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.create') || request()->routeIs('admin.products.edit') || request()->routeIs('admin.products.versions*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            รายการผลิตภัณฑ์
        </a>
    </div>
</div>

{{-- License & Devices --}}
<div x-data="{ open: {{ request()->routeIs('admin.licenses*') || request()->routeIs('admin.devices*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>License</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.licenses.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.licenses*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            จัดการ License
        </a>
        <a href="{{ route('admin.devices.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.devices*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            จัดการ Devices
        </a>
    </div>
</div>

{{-- สมาชิก --}}
<div x-data="{ open: {{ request()->routeIs('admin.users*') || request()->routeIs('admin.roles*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>สมาชิก</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        @permission('users.view')
        <a href="{{ route('admin.users.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.users*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            รายชื่อสมาชิก
            <span class="ml-auto bg-gray-700 text-gray-300 text-[10px] px-1.5 py-0.5 rounded-full">{{ $totalUsers }}</span>
        </a>
        @endpermission
        @permission('roles.view')
        <a href="{{ route('admin.roles.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.roles*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            จัดการบทบาท
        </a>
        @endpermission
    </div>
</div>

{{-- My Cloud --}}
<div class="{{ $sectionClass }}">
    <div class="{{ $headerClass }}">
        <svg class="w-3 h-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
        My Cloud
    </div>
    <a href="{{ route('admin.tping.workflows.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.tping.*') ? $linkActive : $linkInactive }}">
        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
        TPING
    </a>
</div>

{{-- Affiliate --}}
<div x-data="{ open: {{ request()->routeIs('admin.affiliates.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>Affiliate</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.affiliates.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.affiliates.index') || request()->routeIs('admin.affiliates.show') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            จัดการ
            @if($pendingCommissions > 0)
            <span class="ml-auto bg-yellow-600 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingCommissions }}</span>
            @endif
        </a>
        <a href="{{ route('admin.affiliates.commissions') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.affiliates.commissions') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            ค่าคอมมิชชั่น
        </a>
        <a href="{{ route('admin.affiliates.tree') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.affiliates.tree') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
            แผนผังสายงาน
        </a>
    </div>
</div>

{{-- Line OA --}}
<div x-data="{ open: {{ request()->routeIs('admin.line-messaging.*') || request()->routeIs('admin.line-settings*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>Line OA</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.line-messaging.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.line-messaging.index') || request()->routeIs('admin.line-messaging.send') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 24 24"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
            ส่งข้อความ
        </a>
        <a href="{{ route('admin.line-messaging.users') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.line-messaging.users') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            จัดการ Line UID
        </a>
        <a href="{{ route('admin.line-settings.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.line-settings*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            ตั้งค่า Line OA
        </a>
    </div>
</div>

{{-- Metal-X YouTube --}}
<div x-data="{ open: {{ request()->routeIs('admin.metal-x.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>Metal-X YouTube</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.metal-x.analytics') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.metal-x.analytics*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <a href="{{ route('admin.metal-x.videos.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.metal-x.videos*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
            วิดีโอ
        </a>
        <a href="{{ route('admin.metal-x.playlists.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.metal-x.playlists*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            เพลย์ลิสต์
        </a>
        <a href="{{ route('admin.metal-x.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.metal-x.index') || request()->routeIs('admin.metal-x.create') || request()->routeIs('admin.metal-x.edit') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            สมาชิกทีม
        </a>
        <a href="{{ route('admin.metal-x.settings') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.metal-x.settings*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            ตั้งค่า Channel
        </a>
    </div>
</div>

{{-- ใบสั่งงาน & โครงการ --}}
<div x-data="{ open: {{ request()->routeIs('admin.quotations.*') || request()->routeIs('admin.projects*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>ใบสั่งงาน</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.quotations.categories.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.quotations.categories*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            หมวดหมู่บริการ
        </a>
        <a href="{{ route('admin.quotations.options.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.quotations.options*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            ตัวเลือก & ราคา
        </a>
        <a href="{{ route('admin.projects.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.projects*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            จัดการโครงการ
        </a>
    </div>
</div>

{{-- Support --}}
<div x-data="{ open: {{ request()->routeIs('admin.support*') || request()->routeIs('admin.bug-reports*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>Support</span>
        @if($newBugReports > 0)<span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $newBugReports }}</span>@endif
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.support.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.support*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Support Tickets
        </a>
        <a href="{{ route('admin.bug-reports.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.bug-reports*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            Bug Reports
            @if($newBugReports > 0)
            <span class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $newBugReports }}</span>
            @endif
        </a>
    </div>
</div>

{{-- ใบเสนอราคา & คำสั่งซื้อ --}}
<div x-data="{ open: {{ request()->routeIs('admin.quotations.list') || request()->routeIs('admin.quotations.detail') || request()->routeIs('admin.orders.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>ใบเสนอราคา</span>
        @if($pendingQuotations > 0)<span class="bg-indigo-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingQuotations }}</span>@endif
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.quotations.list') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.quotations.list') || request()->routeIs('admin.quotations.detail') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            ใบเสนอราคา
            @if($pendingQuotations > 0)
            <span class="ml-auto bg-indigo-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingQuotations }}</span>
            @endif
        </a>
        <a href="{{ route('admin.orders.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.orders.*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            คำสั่งซื้อ
            @if($pendingPaymentOrders > 0)
            <span class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingPaymentOrders }}</span>
            @endif
        </a>
    </div>
</div>

{{-- การเงิน --}}
<div x-data="{ open: {{ request()->routeIs('admin.wallets.*') || request()->routeIs('admin.coupons*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>การเงิน</span>
        @if($pendingTopups > 0)<span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingTopups }}</span>@endif
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.wallets.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.index') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <a href="{{ route('admin.wallets.wallets') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.wallets') || request()->routeIs('admin.wallets.show') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            กระเป๋าเงิน
        </a>
        <a href="{{ route('admin.wallets.topups') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.topups*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            การเติมเงิน
            @if($pendingTopups > 0)
            <span class="ml-auto bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingTopups }}</span>
            @endif
        </a>
        <a href="{{ route('admin.wallets.transactions') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.transactions') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            ธุรกรรม
        </a>
        <a href="{{ route('admin.wallets.bonus-tiers') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.bonus-tiers') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            โบนัสเติมเงิน
        </a>
        <a href="{{ route('admin.wallets.settings') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.wallets.settings') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            ตั้งค่า Wallet
        </a>
        <a href="{{ route('admin.coupons.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.coupons*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            คูปอง
        </a>
    </div>
</div>

{{-- SMS Payment --}}
<div x-data="{ open: {{ request()->routeIs('admin.sms-payment.*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>SMS Payment</span>
        @if($pendingOrders > 0)<span class="bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingOrders }}</span>@endif
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.sms-payment.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.sms-payment.index') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
        <a href="{{ route('admin.sms-payment.settings') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.sms-payment.settings') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            ตั้งค่า
        </a>
        <a href="{{ route('admin.sms-payment.devices') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.sms-payment.devices*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            อุปกรณ์
            @if($activeDevices > 0)
            <span class="ml-auto bg-green-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $activeDevices }}</span>
            @endif
        </a>
        <a href="{{ route('admin.sms-payment.notifications') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.sms-payment.notifications*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            SMS Notifications
        </a>
        <a href="{{ route('admin.sms-payment.pending-orders') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.sms-payment.pending-orders') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            รอตรวจสอบ
            @if($pendingOrders > 0)
            <span class="ml-auto bg-amber-500 text-white text-[10px] px-1.5 py-0.5 rounded-full">{{ $pendingOrders }}</span>
            @endif
        </a>
    </div>
</div>

{{-- การตั้งค่า --}}
<div x-data="{ open: {{ request()->routeIs('admin.theme*') || request()->routeIs('admin.branding*') || request()->routeIs('admin.contact-settings*') || request()->routeIs('admin.payment-settings*') || request()->routeIs('admin.custom-code*') || request()->routeIs('admin.ads-txt*') || request()->routeIs('admin.seo*') || request()->routeIs('admin.ads*') || request()->routeIs('admin.banners*') || request()->routeIs('admin.ai-settings*') || request()->routeIs('admin.ai-playground*') || request()->routeIs('admin.turnstile*') ? 'true' : 'false' }} }" class="{{ $sectionClass }}">
    <button @click="open = !open" :class="open ? '{{ $headerBtnActiveClass }}' : '{{ $headerBtnClass }}'">
        <span>ตั้งค่า</span>
        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse class="{{ $subMenuClass }}">
        <a href="{{ route('admin.theme.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.theme*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            ธีม
        </a>
        <a href="{{ route('admin.branding.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.branding*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            โลโก้ & Favicon
        </a>
        <a href="{{ route('admin.contact-settings.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.contact-settings*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            ข้อมูลติดต่อ
        </a>
        <a href="{{ route('admin.payment-settings.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.payment-settings*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            ชำระเงิน
        </a>
        <a href="{{ route('admin.custom-code.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.custom-code*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            Custom Code
        </a>
        <a href="{{ route('admin.ads-txt.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.ads-txt*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Ads.txt
        </a>
        <a href="{{ route('admin.seo.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.seo*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            SEO
        </a>
        <a href="{{ route('admin.ads.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.ads*') && !request()->routeIs('admin.ads-txt*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Google Ads
        </a>
        <a href="{{ route('admin.banners.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.banners*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Banners
        </a>
        <a href="{{ route('admin.ai-settings.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.ai-settings*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            AI Settings
        </a>
        <a href="{{ route('admin.ai-playground.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.ai-playground*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            AI Playground
        </a>
        <a href="{{ route('admin.turnstile.index') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.turnstile*') ? $linkActive : $linkInactive }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Turnstile
        </a>
    </div>
</div>

{{-- อื่นๆ --}}
<div class="{{ $sectionClass }}">
    <div class="{{ $headerClass }}">อื่นๆ</div>
    <a href="/" class="{{ $linkClass }} {{ $linkInactive }}">
        <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        กลับหน้าเว็บ
    </a>
</div>
