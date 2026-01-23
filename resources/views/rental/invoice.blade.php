@extends($publicLayout ?? 'layouts.app')

@section('title', $invoice->invoice_number . ' - XMAN Studio')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Actions -->
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('rental.status') }}" class="text-primary-600 hover:underline">
                &larr; กลับ
            </a>
            <button onclick="window.print()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                พิมพ์ใบเสร็จ
            </button>
        </div>

        <!-- Invoice -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden print:shadow-none" id="invoice">
            <div class="p-8">
                <!-- Header -->
                <div class="flex justify-between items-start border-b border-gray-200 pb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">XMAN STUDIO</h1>
                        <p class="text-gray-500">IT Solutions & Software Development</p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-bold text-primary-600">{{ $invoice->getTypeLabel() }}</h2>
                        <p class="text-gray-600">{{ $invoice->invoice_number }}</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-8 py-6 border-b border-gray-200">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">ออกให้</h3>
                        <p class="text-gray-900 font-semibold">{{ $invoice->user->name }}</p>
                        <p class="text-gray-600">{{ $invoice->user->email }}</p>
                        @if($invoice->company_name)
                            <p class="text-gray-600 mt-2">{{ $invoice->company_name }}</p>
                            @if($invoice->tax_id)
                                <p class="text-gray-600">เลขประจำตัวผู้เสียภาษี: {{ $invoice->tax_id }}</p>
                            @endif
                            @if($invoice->company_address)
                                <p class="text-gray-600">{{ $invoice->company_address }}</p>
                            @endif
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">วันที่ออก</p>
                            <p class="text-gray-900">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                        </div>
                        @if($invoice->paid_at)
                            <div>
                                <p class="text-sm text-gray-500">วันที่ชำระ</p>
                                <p class="text-gray-900">{{ $invoice->paid_at->format('d/m/Y') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Line Items -->
                <div class="py-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-3 text-left text-sm font-semibold text-gray-900">รายการ</th>
                                <th class="py-3 text-right text-sm font-semibold text-gray-900">จำนวน</th>
                                <th class="py-3 text-right text-sm font-semibold text-gray-900">ราคา/หน่วย</th>
                                <th class="py-3 text-right text-sm font-semibold text-gray-900">รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->line_items ?? [] as $item)
                                <tr class="border-b border-gray-100">
                                    <td class="py-4 text-gray-900">{{ $item['description'] }}</td>
                                    <td class="py-4 text-right text-gray-600">{{ $item['quantity'] }}</td>
                                    <td class="py-4 text-right text-gray-600">฿{{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="py-4 text-right text-gray-900">฿{{ number_format($item['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>ยอดรวม</span>
                                <span>฿{{ number_format($invoice->subtotal, 2) }}</span>
                            </div>

                            @if($invoice->discount > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>ส่วนลด</span>
                                    <span class="text-red-600">-฿{{ number_format($invoice->discount, 2) }}</span>
                                </div>
                            @endif

                            @if($invoice->vat > 0)
                                <div class="flex justify-between text-gray-600">
                                    <span>ภาษีมูลค่าเพิ่ม (7%)</span>
                                    <span>฿{{ number_format($invoice->vat, 2) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-200">
                                <span>รวมทั้งสิ้น</span>
                                <span>฿{{ number_format($invoice->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="mt-8 text-center">
                    @if($invoice->status === 'paid')
                        <span class="inline-flex px-6 py-2 rounded-full text-lg font-semibold bg-green-100 text-green-700">
                            ชำระแล้ว
                        </span>
                    @elseif($invoice->status === 'void')
                        <span class="inline-flex px-6 py-2 rounded-full text-lg font-semibold bg-red-100 text-red-700">
                            ยกเลิก
                        </span>
                    @endif
                </div>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                    <p>XMAN Studio - IT Solutions & Software Development</p>
                    <p>Email: info@xmanstudio.com | Line OA: @xmanstudio</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #invoice, #invoice * {
            visibility: visible;
        }
        #invoice {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
@endsection
