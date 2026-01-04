@extends('layouts.customer')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                        <br>
                        <span class="text-xs text-gray-400">{{ ucfirst($invoice->type) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $invoice->created_at->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            {{ $invoice->userRental?->rentalPackage?->display_name ?? 'Subscription' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-gray-900">฿{{ number_format($invoice->total ?? 0) }}</span>
                        @if($invoice->vat_amount > 0)
                            <br><span class="text-xs text-gray-500">VAT: ฿{{ number_format($invoice->vat_amount) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $invoice->status === 'sent' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $invoice->status === 'draft' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $invoice->status === 'void' ? 'bg-red-100 text-red-700' : '' }}
                        ">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="#" class="text-primary-600 hover:underline">Download PDF</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-lg font-medium">No invoices yet</p>
                        <p class="text-sm mt-1">Your invoices will appear here after purchase</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $invoices->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
