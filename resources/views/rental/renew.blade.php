@extends($publicLayout ?? 'layouts.app')

@section('title', 'ต่ออายุ - ' . $current->display_name . ' - XMAN Studio')

@php
    $money = fn (int $satang) => \App\Services\Rentals\RentalAddons::money($satang);
@endphp

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 px-6 py-8 mb-8">
            <x-page-art art="hero-payment" :opacity="14" :scrim="false" fade="bottom" />
            <div class="relative">
                <nav class="mb-3">
                    <ol class="flex items-center space-x-2 text-sm text-gray-300">
                        <li><a href="{{ route('rental.status') }}" class="hover:text-white"><x-bi th="สถานะการเช่า" en="Rental status" /></a></li>
                        <li><span>/</span></li>
                        <li class="text-white"><x-bi th="ต่ออายุ" en="Renew" /></li>
                    </ol>
                </nav>
                <h1 class="text-2xl md:text-3xl font-bold text-white"><x-bi th="ต่ออายุแพ็กเกจ" en="Renew your package" /></h1>
                <p class="mt-1 text-gray-300">{{ $current->display_name }}</p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <form action="{{ route('rental.renew.process', $rental) }}" method="POST" class="bg-white rounded-2xl shadow-xl overflow-hidden">
            @csrf
            <input type="hidden" name="cycle" value="{{ $targetCycle }}">
            <div class="md:flex">
                <div class="md:w-1/3 bg-gray-50 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi k="common.order_summary" /></h2>

                    @if(count($cycles) > 1)
                        <div class="grid grid-cols-2 gap-2 mb-6">
                            @foreach($cycles as $c => $row)
                                <a href="{{ route('rental.renew', ['rental' => $rental->id, 'cycle' => $c]) }}"
                                   class="text-center px-3 py-2 rounded-lg text-sm font-semibold border-2 {{ $c === $targetCycle ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-primary-300' }}">
                                    {{ $c === 'yearly' ? 'รายปี' : 'รายเดือน' }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-3 text-sm">
                        @foreach($lines as $line)
                            <div class="flex justify-between gap-3">
                                <span class="{{ $line['type'] === 'package' ? 'text-gray-900 font-semibold' : 'text-gray-600' }}">
                                    {{ $line['name'] }}@if(($line['quantity'] ?? 1) > 1) × {{ $line['quantity'] }}@endif
                                </span>
                                <span class="text-gray-900 whitespace-nowrap">{{ $money((int) $line['amount_satang']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-900"><x-bi th="รวมทั้งสิ้น" en="Total" /></span>
                            <span class="text-2xl font-bold text-primary-600">{{ $money($total) }}</span>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500"><x-bi th="หมดอายุเดิม" en="Expires now" /></dt>
                            <dd class="text-gray-900">{{ $rental->expires_at?->timezone('Asia/Bangkok')->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500"><x-bi th="หลังต่ออายุ" en="After renewal" /></dt>
                            <dd class="font-semibold text-gray-900">{{ $newExpiry->copy()->timezone('Asia/Bangkok')->format('d/m/Y') }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-500">
                        <x-bi th="ต่อก่อนหมดอายุไม่เสียวันที่เหลือ — รอบใหม่เริ่มนับจากวันหมดอายุเดิม (นับจากวันที่ชำระเงินจริง)" en="Renewing early loses no days — the new cycle starts at the current expiry" />
                    </p>
                </div>

                <div class="md:w-2/3 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi th="เลือกวิธีชำระเงิน" en="Select payment method" /></h2>

                    @include('rental.partials.payment-methods')

                    <div class="mt-8">
                        <button type="submit" class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold text-lg">
                            <x-bi k="common.proceed_to_payment" />
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
