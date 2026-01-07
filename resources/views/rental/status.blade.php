@extends('layouts.app')

@section('title', 'สถานะการเช่า - XMAN Studio')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">สถานะการเช่า</h1>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if($activeRental)
            <!-- Active Rental Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-primary-600 to-primary-800 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-primary-100 text-sm">แพ็กเกจปัจจุบัน</p>
                            <h2 class="text-2xl font-bold text-white">{{ $activeRental->rentalPackage->display_name }}</h2>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold
                                {{ $activeRental->status === 'active' ? 'bg-green-500' : 'bg-yellow-500' }} text-white">
                                {{ $activeRental->status_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">วันที่เริ่มต้น</p>
                            <p class="font-semibold text-gray-900">{{ $activeRental->starts_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">วันหมดอายุ</p>
                            <p class="font-semibold text-gray-900">{{ $activeRental->expires_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">เหลืออีก</p>
                            <p class="font-semibold {{ $activeRental->days_remaining <= 7 ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $activeRental->days_remaining }} วัน
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Auto Renew</p>
                            <p class="font-semibold text-gray-900">{{ $activeRental->auto_renew ? 'เปิด' : 'ปิด' }}</p>
                        </div>
                    </div>

                    <!-- Usage Progress (if limits exist) -->
                    @if($activeRental->rentalPackage->limits)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">การใช้งาน</h3>
                            <div class="space-y-4">
                                @if(isset($activeRental->rentalPackage->limits['max_posts']))
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">โพสต์</span>
                                            <span class="text-gray-900">
                                                {{ $activeRental->usage['posts'] ?? 0 }} / {{ $activeRental->rentalPackage->limits['max_posts'] }}
                                            </span>
                                        </div>
                                        @php
                                            $usedPosts = $activeRental->usage['posts'] ?? 0;
                                            $maxPosts = $activeRental->rentalPackage->limits['max_posts'];
                                            $percentPosts = min(100, ($usedPosts / $maxPosts) * 100);
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentPosts }}%"></div>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($activeRental->rentalPackage->limits['ai_credits']))
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">AI Credits</span>
                                            <span class="text-gray-900">
                                                {{ $activeRental->usage['ai_credits'] ?? 0 }} / {{ $activeRental->rentalPackage->limits['ai_credits'] }}
                                            </span>
                                        </div>
                                        @php
                                            $usedCredits = $activeRental->usage['ai_credits'] ?? 0;
                                            $maxCredits = $activeRental->rentalPackage->limits['ai_credits'];
                                            $percentCredits = min(100, ($usedCredits / $maxCredits) * 100);
                                        @endphp
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentCredits }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="mt-6 pt-6 border-t border-gray-200 flex flex-wrap gap-4">
                        <a href="{{ route('rental.index') }}"
                           class="px-6 py-2 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                            อัพเกรดแพ็กเกจ
                        </a>

                        @if($activeRental->days_remaining <= 14)
                            <a href="{{ route('rental.checkout', $activeRental->rentalPackage) }}"
                               class="px-6 py-2 rounded-lg border border-primary-600 text-primary-600 hover:bg-primary-50 transition-colors">
                                ต่ออายุ
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <!-- No Active Rental -->
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center mb-8">
                <svg class="w-16 h-16 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <h2 class="mt-4 text-xl font-semibold text-gray-900">ยังไม่มีแพ็กเกจใช้งาน</h2>
                <p class="mt-2 text-gray-500">เลือกแพ็กเกจที่เหมาะกับคุณเพื่อเริ่มต้นใช้งาน</p>
                <a href="{{ route('rental.index') }}"
                   class="mt-6 inline-block px-6 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                    ดูแพ็กเกจทั้งหมด
                </a>
            </div>
        @endif

        <!-- Rental History -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">ประวัติการเช่า</h2>
            </div>

            <div class="divide-y divide-gray-200">
                @forelse($rentals as $rental)
                    <div class="p-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $rental->rentalPackage->display_name }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $rental->starts_at->format('d/m/Y') }} - {{ $rental->expires_at->format('d/m/Y') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-2 py-1 rounded text-xs font-semibold
                                    @if($rental->status === 'active') bg-green-100 text-green-700
                                    @elseif($rental->status === 'expired') bg-gray-100 text-gray-700
                                    @elseif($rental->status === 'pending') bg-yellow-100 text-yellow-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ $rental->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        ยังไม่มีประวัติการเช่า
                    </div>
                @endforelse
            </div>

            @if($rentals->hasPages())
                <div class="p-6 border-t border-gray-200">
                    {{ $rentals->links() }}
                </div>
            @endif
        </div>

        <!-- Payment History -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mt-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">ประวัติการชำระเงิน</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายการ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($payments as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payment->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payment->userRental?->rentalPackage?->display_name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ฿{{ number_format($payment->amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 rounded text-xs font-semibold
                                        @if($payment->status === 'completed') bg-green-100 text-green-700
                                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-700
                                        @elseif($payment->status === 'processing') bg-blue-100 text-blue-700
                                        @else bg-red-100 text-red-700 @endif">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($payment->status === 'completed' && $payment->invoice)
                                        <a href="{{ route('rental.invoice', $payment->invoice) }}"
                                           class="text-primary-600 hover:underline">
                                            ดูใบเสร็จ
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    ยังไม่มีประวัติการชำระเงิน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div class="p-6 border-t border-gray-200">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
