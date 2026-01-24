@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ใบแจ้งหนี้')
@section('page-title', 'ใบแจ้งหนี้')
@section('page-description', 'ดูและดาวน์โหลดใบแจ้งหนี้ทั้งหมดของคุณ')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-600 via-gray-700 to-zinc-800 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-gray-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                ใบแจ้งหนี้
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">ดูและดาวน์โหลดใบแจ้งหนี้ทั้งหมดของคุณ</p>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <!-- Desktop Table -->
    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เลขที่ใบแจ้งหนี้</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รายละเอียด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($invoices as $invoice)
                @php
                    $statusGradients = [
                        'paid' => 'from-emerald-400 to-green-500',
                        'sent' => 'from-blue-400 to-indigo-500',
                        'draft' => 'from-gray-400 to-gray-500',
                        'void' => 'from-red-400 to-rose-500',
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
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="p-2 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl shadow-lg mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $typeLabels[$invoice->type] ?? ucfirst($invoice->type) }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                        {{ $invoice->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-white">
                            {{ $invoice->userRental?->rentalPackage?->display_name ?? 'การสมัครสมาชิก' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <span class="font-bold text-lg text-gray-900 dark:text-white">฿{{ number_format($invoice->total ?? 0) }}</span>
                            @if($invoice->vat_amount > 0)
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5">VAT: ฿{{ number_format($invoice->vat_amount) }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$invoice->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-slate-700 text-white rounded-xl hover:from-gray-700 hover:to-slate-800 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
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
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-500 to-slate-600 rounded-full mb-4 shadow-xl">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">ยังไม่มีใบแจ้งหนี้</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ใบแจ้งหนี้ของคุณจะปรากฏที่นี่หลังจากทำการซื้อ</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($invoices as $invoice)
        @php
            $statusGradients = [
                'paid' => 'from-emerald-400 to-green-500',
                'sent' => 'from-blue-400 to-indigo-500',
                'draft' => 'from-gray-400 to-gray-500',
                'void' => 'from-red-400 to-rose-500',
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
                        <div class="p-1.5 bg-gradient-to-br from-gray-500 to-slate-600 rounded-lg shadow">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gradient-to-r {{ $statusGradients[$invoice->status] ?? 'from-gray-400 to-gray-500' }} text-white shadow-sm">
                            {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-8">
                        {{ $invoice->created_at->format('d/m/Y') }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 ml-8">
                        {{ $invoice->userRental?->rentalPackage?->display_name ?? 'การสมัครสมาชิก' }}
                    </p>
                    <div class="mt-3 ml-8 flex items-center justify-between">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">฿{{ number_format($invoice->total ?? 0) }}</span>
                        <button class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-gray-600 to-slate-700 text-white rounded-xl hover:from-gray-700 hover:to-slate-800 text-sm font-medium transition-all shadow">
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
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-500 to-slate-600 rounded-full mb-4 shadow-xl">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีใบแจ้งหนี้</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ใบแจ้งหนี้ของคุณจะปรากฏที่นี่หลังจากทำการซื้อ</p>
        </div>
        @endforelse
    </div>

    @if($invoices->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        {{ $invoices->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
