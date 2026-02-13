@extends('layouts.app')

@section('title', 'ติดตามใบสั่งงาน - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ติดตามใบสั่งงาน</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">ค้นหาด้วยเลขที่ใบเสนอราคา, เลขโครงการ หรืออีเมล</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-100 dark:border-gray-700">
            <form method="GET" action="{{ route('support.tracking.search') }}" class="flex gap-3">
                <div class="flex-1 relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="query" value="{{ $query ?? '' }}" required minlength="3"
                           placeholder="QUO-20260213-ABCD / PRJ-20260213-ABCD / email@example.com"
                           class="w-full pl-12 pr-4 py-3.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>
                <button type="submit" class="px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition shadow-lg hover:shadow-xl whitespace-nowrap">
                    ค้นหา
                </button>
            </form>
        </div>

        @isset($query)
            @if((!isset($quotations) || $quotations->isEmpty()) && (!isset($projects) || $projects->isEmpty()))
                <!-- No Results -->
                <div class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">ไม่พบข้อมูล</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">ลองค้นหาด้วยเลขที่ใบเสนอราคา, เลขโครงการ หรืออีเมลที่ใช้</p>
                </div>
            @endif

            <!-- Projects -->
            @if(isset($projects) && $projects->isNotEmpty())
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    โครงการ
                </h2>
                <div class="space-y-4">
                    @foreach($projects as $project)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div>
                                    <p class="font-mono text-sm text-blue-600 dark:text-blue-400 font-semibold">{{ $project->project_number }}</p>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $project->project_name }}</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                            'on_hold' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                            'review' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                            'revision' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ \App\Models\ProjectOrder::STATUS_LABELS[$project->status] ?? $project->status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500 dark:text-gray-400">ความคืบหน้า</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $project->progress_percent ?? 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500"
                                         style="width: {{ $project->progress_percent ?? 0 }}%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">ประเภท</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ \App\Models\ProjectOrder::TYPE_LABELS[$project->project_type] ?? $project->project_type }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">เริ่มงาน</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">กำหนดส่ง</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $project->expected_end_date?->format('d/m/Y') ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">มูลค่า</p>
                                    <p class="font-bold text-blue-600 dark:text-blue-400">฿{{ number_format($project->total_price, 0) }}</p>
                                </div>
                            </div>

                            <!-- Features -->
                            @if($project->features->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ฟีเจอร์</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    @foreach($project->features as $feature)
                                    <div class="flex items-center text-sm">
                                        @if($feature->status === 'completed')
                                            <svg class="w-4 h-4 mr-2 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @elseif($feature->status === 'in_progress')
                                            <svg class="w-4 h-4 mr-2 text-blue-500 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        @else
                                            <div class="w-4 h-4 mr-2 rounded-full border-2 border-gray-300 dark:border-gray-600 flex-shrink-0"></div>
                                        @endif
                                        <span class="text-gray-700 dark:text-gray-300 {{ $feature->status === 'completed' ? 'line-through text-gray-400' : '' }}">{{ $feature->name }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Recent Progress -->
                            @if($project->progress->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">อัปเดตล่าสุด</p>
                                <div class="space-y-2">
                                    @foreach($project->progress as $update)
                                    <div class="flex items-start text-sm">
                                        <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-500 mr-3 flex-shrink-0"></div>
                                        <div>
                                            <p class="text-gray-700 dark:text-gray-300">{{ $update->description }}</p>
                                            <p class="text-xs text-gray-400">{{ $update->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Quotations -->
            @if(isset($quotations) && $quotations->isNotEmpty())
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    ใบเสนอราคา
                </h2>
                <div class="space-y-3">
                    @foreach($quotations as $quote)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p class="font-mono text-sm text-indigo-600 dark:text-indigo-400 font-semibold">{{ $quote->quote_number }}</p>
                                <p class="text-gray-900 dark:text-white font-medium">{{ $quote->service_name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $quote->customer_name }} &middot; {{ $quote->created_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @php
                                    $quoteStatusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'viewed' => 'bg-purple-100 text-purple-700',
                                        'accepted' => 'bg-green-100 text-green-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'expired' => 'bg-red-100 text-red-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                    ];
                                    $quoteStatusLabels = [
                                        'draft' => 'ร่าง',
                                        'sent' => 'ส่งแล้ว',
                                        'viewed' => 'เปิดดูแล้ว',
                                        'accepted' => 'ยอมรับ',
                                        'paid' => 'ชำระแล้ว',
                                        'expired' => 'หมดอายุ',
                                        'rejected' => 'ปฏิเสธ',
                                    ];
                                @endphp
                                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $quoteStatusColors[$quote->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $quoteStatusLabels[$quote->status] ?? $quote->status }}
                                </span>
                                <p class="font-bold text-lg text-gray-900 dark:text-white whitespace-nowrap">฿{{ number_format($quote->grand_total, 0) }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endisset

        @empty($query)
        <!-- Help Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">เลขที่ใบเสนอราคา</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เช่น QUO-20260213-ABCD</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">เลขโครงการ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เช่น PRJ-20260213-ABCD</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">อีเมล</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">อีเมลที่ใช้ในใบเสนอราคา</p>
            </div>
        </div>
        @endempty

        <!-- Back to Support -->
        <div class="text-center mt-10">
            <a href="{{ route('support.index') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline font-medium">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับหน้าสั่งซื้อ/ใบเสนอราคา
            </a>
        </div>
    </div>
</div>
@endsection
