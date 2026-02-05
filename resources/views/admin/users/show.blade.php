@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'รายละเอียดสมาชิก - ' . $user->name)

@section('content')
<!-- Premium Gradient Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-20 h-20 rounded-2xl overflow-hidden bg-white/20 flex items-center justify-center shadow-xl">
                @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="" class="w-full h-full object-cover">
                @else
                <span class="text-3xl font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">{{ $user->name }}</h1>
                <p class="text-blue-100 text-lg">{{ $user->email }}</p>
                <div class="flex items-center gap-2 mt-2">
                    @if($user->role === 'super_admin')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-white border border-rose-400/50">
                        Super Admin
                    </span>
                    @elseif($user->role === 'admin')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/20 text-white border border-purple-400/50">
                        แอดมิน
                    </span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                        ผู้ใช้
                    </span>
                    @endif

                    @if($user->is_active)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-white border border-emerald-400/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-2"></span>
                        ใช้งานอยู่
                    </span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-white border border-rose-400/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-2"></span>
                        ปิดใช้งาน
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                กลับ
            </a>
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center px-5 py-2.5 bg-white hover:bg-gray-100 text-indigo-600 font-semibold rounded-xl transition-all duration-200 shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                แก้ไข
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column - User Info -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Basic Info Card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลทั่วไป</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">อีเมล</label>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $user->email }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">เบอร์โทร</label>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $user->phone ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">สมัครเมื่อ</label>
                    <p class="mt-1 text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ยืนยันอีเมลเมื่อ</label>
                    <p class="mt-1 text-gray-900 dark:text-white">
                        {{ $user->email_verified_at ? $user->email_verified_at->format('d/m/Y H:i') : 'ยังไม่ยืนยัน' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Roles Card -->
        @if($user->roles && $user->roles->count() > 0)
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">บทบาท</h3>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">
                    {{ $user->roles->count() }} บทบาท
                </span>
            </div>
            <div class="p-6 space-y-3">
                @foreach($user->roles as $role)
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $role->display_name ?? $role->name }}</p>
                        @if($role->description)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $role->description }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400">
                        {{ $role->permissions->count() }} สิทธิ์
                    </span>
                </div>
                @endforeach
                <a href="{{ route('admin.users.edit', $user) }}" class="block text-center text-sm text-indigo-600 dark:text-indigo-400 hover:underline mt-3">
                    จัดการบทบาท →
                </a>
            </div>
        </div>
        @endif

        <!-- LINE Connection Card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">LINE</h3>
                @if($user->hasLineUid())
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                    เชื่อมต่อแล้ว
                </span>
                @endif
            </div>
            <div class="p-6">
                @if($user->hasLineUid())
                <div class="flex items-center gap-4 mb-4">
                    @if($user->line_picture_url)
                    <img src="{{ $user->line_picture_url }}" alt="" class="w-12 h-12 rounded-full">
                    @else
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755z"/>
                        </svg>
                    </div>
                    @endif
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $user->line_display_name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-32" title="{{ $user->line_uid }}">{{ $user->line_uid }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.users.disconnect-line', $user) }}" onsubmit="return confirm('ยืนยันการยกเลิกการเชื่อมต่อ LINE?')">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                        </svg>
                        ยกเลิกการเชื่อมต่อ LINE
                    </button>
                </form>
                @else
                <div class="text-center py-6">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่ได้เชื่อมต่อ LINE</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Wallet Card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">กระเป๋าเงิน</h3>
                @if($wallet)
                <a href="{{ route('admin.wallets.show', $wallet) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">ดูรายละเอียด</a>
                @endif
            </div>
            <div class="p-6">
                @if($wallet)
                <div class="text-center">
                    <p class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">฿{{ number_format($wallet->balance, 2) }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ยอดคงเหลือ</p>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($wallet->total_deposited, 2) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เติมเงินรวม</p>
                    </div>
                    <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($wallet->total_spent, 2) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ใช้จ่ายรวม</p>
                    </div>
                </div>
                @else
                <div class="text-center py-6">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกระเป๋าเงิน</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions Card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การดำเนินการ</h3>
            </div>
            <div class="p-6 space-y-3">
                @if($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                    @csrf
                    @if($user->is_active)
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 rounded-xl hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        ปิดใช้งานบัญชี
                    </button>
                    @else
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        เปิดใช้งานบัญชี
                    </button>
                    @endif
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('ยืนยันการลบบัญชีนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 rounded-xl hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        ลบบัญชี
                    </button>
                </form>
                @else
                <div class="text-center py-4">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">ไม่สามารถดำเนินการกับบัญชีของตัวเองได้</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column - Activity -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อ</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $orderStats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ชำระแล้ว</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $orderStats['paid'] }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยอดซื้อรวม</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($orderStats['total_spent'], 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">คำสั่งซื้อล่าสุด</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">เลขที่</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">ยอดรวม</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">วันที่</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($user->orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">฿{{ number_format($order->total, 2) }}</td>
                            <td class="px-6 py-4">
                                @if($order->payment_status === 'paid')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">ชำระแล้ว</span>
                                @elseif($order->payment_status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">รอชำระ</span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $order->payment_status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $order->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">ไม่มีคำสั่งซื้อ</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rentals -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">การเช่าใช้งาน</h3>
                @if($activeRental)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                    มีแพ็คเกจใช้งานอยู่
                </span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">แพ็คเกจ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">หมดอายุ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($rentals as $rental)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $rental->package?->name ?? 'ไม่ระบุ' }}</td>
                            <td class="px-6 py-4">
                                @if($rental->status === 'active')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">ใช้งานอยู่</span>
                                @elseif($rental->status === 'expired')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">หมดอายุ</span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">{{ $rental->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $rental->expires_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">ไม่มีประวัติการเช่า</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Licenses -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">License Keys</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">สินค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">License Key</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $license->product?->name ?? 'ไม่ระบุ' }}</td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-300">{{ Str::mask($license->license_key, '*', 8) }}</td>
                            <td class="px-6 py-4">
                                @if($license->status === 'active')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">ใช้งานอยู่</span>
                                @elseif($license->status === 'expired')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">หมดอายุ</span>
                                @elseif($license->status === 'revoked')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">ยกเลิก</span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $license->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">ไม่มี License Keys</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
