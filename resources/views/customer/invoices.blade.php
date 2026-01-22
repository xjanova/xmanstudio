@extends('layouts.customer')

@section('title', 'ใบแจ้งหนี้')
@section('page-title', 'ใบแจ้งหนี้')
@section('page-description', 'ดูและดาวน์โหลดใบแจ้งหนี้ทั้งหมดของคุณ')

@section('content')
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">เลขที่ใบแจ้งหนี้</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                @php
                    $statusColors = [
                        'paid' => 'bg-green-100 text-green-700',
                        'sent' => 'bg-blue-100 text-blue-700',
                        'draft' => 'bg-gray-100 text-gray-700',
                        'void' => 'bg-red-100 text-red-700',
                    ];
                    $statusLabels = [
                        'paid' => 'ชำระแล้ว',
                        'sent' => 'ส่งแล้ว',
                        'draft' => 'แบบร่าง',
                        'void' => 'ยกเลิก',
                    ];
                    $typeLabels = [
                        'rental' => 'การเช่า',
                        'order' => 'คำสั่งซื้อ',
                    ];
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <span class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $typeLabels[$invoice->type] ?? ucfirst($invoice->type) }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $invoice->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            {{ $invoice->userRental?->rentalPackage?->display_name ?? 'การสมัครสมาชิก' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <span class="font-semibold text-gray-900">฿{{ number_format($invoice->total ?? 0) }}</span>
                            @if($invoice->vat_amount > 0)
                                <span class="block text-xs text-gray-500 mt-0.5">VAT: ฿{{ number_format($invoice->vat_amount) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button class="inline-flex items-center px-3 py-1.5 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors font-medium">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            ดาวน์โหลด PDF
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-900">ยังไม่มีใบแจ้งหนี้</p>
                        <p class="text-sm text-gray-500 mt-1">ใบแจ้งหนี้ของคุณจะปรากฏที่นี่หลังจากทำการซื้อ</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($invoices as $invoice)
        @php
            $statusColors = [
                'paid' => 'bg-green-100 text-green-700',
                'sent' => 'bg-blue-100 text-blue-700',
                'draft' => 'bg-gray-100 text-gray-700',
                'void' => 'bg-red-100 text-red-700',
            ];
            $statusLabels = [
                'paid' => 'ชำระแล้ว',
                'sent' => 'ส่งแล้ว',
                'draft' => 'แบบร่าง',
                'void' => 'ยกเลิก',
            ];
        @endphp
        <div class="p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $invoice->created_at->format('d/m/Y') }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $invoice->userRental?->rentalPackage?->display_name ?? 'การสมัครสมาชิก' }}
                    </p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-900">฿{{ number_format($invoice->total ?? 0) }}</span>
                        <button class="inline-flex items-center px-3 py-1.5 text-primary-600 hover:bg-primary-50 rounded-lg transition-colors text-sm font-medium">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            ดาวน์โหลด
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900">ยังไม่มีใบแจ้งหนี้</p>
            <p class="text-sm text-gray-500 mt-1">ใบแจ้งหนี้ของคุณจะปรากฏที่นี่หลังจากทำการซื้อ</p>
        </div>
        @endforelse
    </div>

    @if($invoices->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
        {{ $invoices->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
