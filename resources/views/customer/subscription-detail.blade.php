@extends($customerLayout ?? 'layouts.customer')

@section('title', 'รายละเอียดการสมัคร - ' . ($rental->rentalPackage->display_name ?? 'Subscription'))
@section('page-title', 'รายละเอียดการสมัคร')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.subscriptions') }}" class="text-cyan-600 dark:text-cyan-400 hover:text-cyan-700 dark:hover:text-cyan-300 flex items-center font-medium transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        กลับไปรายการสมัครสมาชิก
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Subscription Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Subscription Card with Premium Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            @php
                $headerGradients = [
                    'active' => 'from-emerald-600 via-green-600 to-teal-600',
                    'pending' => 'from-yellow-500 via-orange-500 to-amber-500',
                    'expired' => 'from-gray-500 via-gray-600 to-gray-700',
                    'cancelled' => 'from-red-500 via-rose-500 to-pink-500',
                    'suspended' => 'from-orange-500 via-red-500 to-rose-500',
                ];
                $statusLabels = [
                    'active' => 'ใช้งานอยู่',
                    'pending' => 'รอชำระเงิน',
                    'expired' => 'หมดอายุ',
                    'cancelled' => 'ยกเลิกแล้ว',
                    'suspended' => 'ระงับชั่วคราว',
                ];
            @endphp
            <div class="relative overflow-hidden bg-gradient-to-r {{ $headerGradients[$rental->status] ?? 'from-blue-600 via-cyan-600 to-teal-600' }} p-6">
                <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob"></div>
                <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob animation-delay-2000"></div>

                <div class="relative flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ $rental->rentalPackage->display_name ?? 'Subscription' }}</h2>
                        <p class="text-white/80 mt-1">{{ $rental->rentalPackage->display_description ?? '' }}</p>
                    </div>
                    <span class="px-4 py-1.5 text-sm font-semibold rounded-full bg-white/20 backdrop-blur-sm text-white shadow">
                        {{ $statusLabels[$rental->status] ?? ucfirst($rental->status) }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                <!-- Subscription Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">แพ็คเกจ</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $rental->rentalPackage->display_name ?? '-' }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">ราคา</label>
                        <p class="text-gray-900 dark:text-white font-medium">฿{{ number_format($rental->rentalPackage->price ?? 0) }}</p>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $rental->rentalPackage->duration_text ?? '' }}</span>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">วันที่เริ่มใช้งาน</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $rental->starts_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">วันหมดอายุ</label>
                        @if($rental->expires_at)
                            <p class="{{ $rental->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} font-medium">
                                {{ $rental->expires_at->format('d/m/Y H:i') }}
                            </p>
                            @if(!$rental->expires_at->isPast())
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $rental->expires_at->diffForHumans() }})</span>
                            @else
                                <span class="text-xs text-red-500 dark:text-red-400">(หมดอายุแล้ว)</span>
                            @endif
                        @else
                            <p class="text-gray-900 dark:text-white font-medium">-</p>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">วันที่เหลือ</label>
                        <p class="text-gray-900 dark:text-white font-medium">
                            @if($rental->is_active)
                                <span class="text-emerald-600 dark:text-emerald-400">{{ $rental->days_remaining }} วัน</span>
                            @else
                                <span class="text-gray-500">-</span>
                            @endif
                        </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">ต่ออายุอัตโนมัติ</label>
                        <p class="font-medium">
                            @if($rental->auto_renew)
                                <span class="px-2.5 py-1 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg text-xs font-medium inline-flex items-center shadow-sm">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    เปิดใช้งาน
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">ปิด</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($rental->amount_paid)
                <div class="mt-4 bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl p-4 border border-cyan-200 dark:border-cyan-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-cyan-800 dark:text-cyan-200">ยอดชำระ</span>
                        <span class="text-lg font-bold text-cyan-700 dark:text-cyan-300">฿{{ number_format($rental->amount_paid, 2) }}</span>
                    </div>
                    @if($rental->payment_method)
                    <p class="text-xs text-cyan-600 dark:text-cyan-400 mt-1">ผ่าน {{ $rental->payment_method }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Payment History -->
        @if($rental->payments->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                ประวัติการชำระเงิน
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ช่องทาง</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rental->payments->sortByDesc('created_at') as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">฿{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->payment_method ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $paymentStatusColors = [
                                        'completed' => 'from-green-400 to-emerald-500 text-white',
                                        'paid' => 'from-green-400 to-emerald-500 text-white',
                                        'pending' => 'from-yellow-400 to-orange-400 text-white',
                                        'failed' => 'from-red-400 to-rose-500 text-white',
                                        'refunded' => 'from-gray-400 to-gray-500 text-white',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $paymentStatusColors[$payment->status] ?? 'from-gray-400 to-gray-500 text-white' }} shadow-sm">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Invoices -->
        @if($rental->invoices->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                ใบเสร็จ / ใบแจ้งหนี้
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">เลขที่</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($rental->invoices->sortByDesc('created_at') as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $invoice->getTypeLabel() }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">฿{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $invoiceStatusColors = [
                                        'paid' => 'from-green-400 to-emerald-500 text-white',
                                        'sent' => 'from-blue-400 to-indigo-500 text-white',
                                        'draft' => 'from-gray-300 to-gray-400 text-white',
                                        'void' => 'from-red-400 to-rose-500 text-white',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $invoiceStatusColors[$invoice->status] ?? 'from-gray-400 to-gray-500 text-white' }} shadow-sm">
                                    {{ $invoice->getStatusLabel() }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 text-center">
            @if($rental->is_active)
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-400 to-green-600 rounded-full mb-4 shadow-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-emerald-600 dark:text-emerald-400">ใช้งานได้</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เหลืออีก {{ $rental->days_remaining }} วัน</p>
            @elseif($rental->status === 'pending')
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mb-4 shadow-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-yellow-600 dark:text-yellow-400">รอชำระเงิน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">กรุณาชำระเงินเพื่อเปิดใช้งาน</p>
            @else
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full mb-4 shadow-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ $statusLabels[$rental->status] ?? ucfirst($rental->status) }}</h3>
                @if($rental->expires_at)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">หมดอายุ {{ $rental->expires_at->format('d/m/Y') }}</p>
                @endif
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                ดำเนินการ
            </h3>
            <div class="space-y-3">
                @if($rental->rentalPackage && ($rental->is_expired || ($rental->is_active && $rental->days_remaining < 30)))
                <a href="{{ route('rental.checkout', $rental->rentalPackage) }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    ต่ออายุสมาชิก
                </a>
                @endif

                <a href="{{ route('customer.invoices') }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    ใบเสร็จทั้งหมด
                </a>

                <a href="{{ route('customer.support.create') }}"
                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    ติดต่อ Support
                </a>
            </div>
        </div>

        <!-- Help Card -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-700">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mb-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">ต้องการความช่วยเหลือ?</h3>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                หากมีปัญหาเกี่ยวกับการสมัครสมาชิก ทีมงานพร้อมช่วยเหลือ
            </p>
            <a href="{{ route('customer.support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold flex items-center transition-colors">
                เปิด Ticket
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection
