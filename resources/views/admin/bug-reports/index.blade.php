@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Bug Reports')
@section('page-title', 'Bug Reports')

@section('content')
<div class="space-y-6" x-data="bugReportManager()">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-600 via-orange-600 to-amber-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Bug Reports</h1>
                </div>
                <p class="text-orange-100 text-lg">รายงาน Bug & SMS Misclassification จาก SmsChecker App</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Auto-Delete Settings Button -->
                <button @click="showAutoDelete = true" class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-lg text-sm font-medium hover:bg-white/30 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ตั้งเวลาลบอัตโนมัติ
                    @if($autoDeleteEnabled)
                        <span class="ml-2 px-2 py-0.5 text-xs bg-white/30 rounded-full">{{ $autoDeleteDays }} วัน</span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.bug-reports.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ !request('status') && !request('report_type') ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['status' => 'new']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'new' ? 'ring-2 ring-yellow-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">New</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $counts['new'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['report_type' => 'misclassification']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('report_type') === 'misclassification' ? 'ring-2 ring-orange-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">SMS Misclassification</p>
            <p class="text-2xl font-bold text-orange-600">{{ $counts['misclassification'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['status' => 'fixed']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'fixed' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">Fixed</p>
            <p class="text-2xl font-bold text-green-600">{{ $counts['fixed'] }}</p>
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, description, device ID..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                <select name="report_type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="misclassification" {{ request('report_type') === 'misclassification' ? 'selected' : '' }}>SMS Misclassification</option>
                    <option value="bug" {{ request('report_type') === 'bug' ? 'selected' : '' }}>Bug</option>
                    <option value="crash" {{ request('report_type') === 'crash' ? 'selected' : '' }}>Crash</option>
                    <option value="feature_request" {{ request('report_type') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                    <option value="performance" {{ request('report_type') === 'performance' ? 'selected' : '' }}>Performance</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="fixed" {{ request('status') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="wont_fix" {{ request('status') === 'wont_fix' ? 'selected' : '' }}>Won't Fix</option>
                </select>
            </div>
            @if($products->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Product</label>
                <select name="product_name" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    @foreach($products as $product)
                        <option value="{{ $product }}" {{ request('product_name') === $product ? 'selected' : '' }}>{{ $product }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                ค้นหา
            </button>
            @if(request()->hasAny(['search', 'report_type', 'status', 'product_name']))
                <a href="{{ route('admin.bug-reports.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div x-show="selectedIds.length > 0" x-cloak class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-red-700 dark:text-red-300">
                เลือกแล้ว <span x-text="selectedIds.length" class="font-bold"></span> รายการ
            </span>
        </div>
        <button @click="confirmBulkDelete()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            ลบที่เลือก
        </button>
    </div>

    <!-- Reports Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Analyzed</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="{{ $report->id }}" x-model.number="selectedIds" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">
                            #{{ $report->id }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.bug-reports.show', $report) }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ Str::limit($report->title, 50) }}
                            </a>
                            @if($report->report_type === 'misclassification' && $report->metadata)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $report->metadata['bank'] ?? '' }} &middot; {{ $report->metadata['amount'] ?? '' }} THB
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $report->product_name }}</span>
                            @if($report->app_version)
                                <div class="text-xs text-gray-400">v{{ $report->app_version }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->report_type)
                                @case('misclassification')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">SMS Misclass</span>
                                    @break
                                @case('bug')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">Bug</span>
                                    @break
                                @case('crash')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">Crash</span>
                                    @break
                                @case('feature_request')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Feature</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">{{ $report->report_type }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->priority)
                                @case('critical')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 animate-pulse">Critical</span>
                                    @break
                                @case('high')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">High</span>
                                    @break
                                @case('medium')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">Medium</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Low</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->status)
                                @case('new')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">New</span>
                                    @break
                                @case('in_progress')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">In Progress</span>
                                    @break
                                @case('fixed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Fixed</span>
                                    @break
                                @case('closed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Closed</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">{{ $report->status }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($report->is_analyzed)
                                <span class="text-green-500" title="Analyzed">&#10003;</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $report->created_at->format('d/m/Y') }}<br>
                            {{ $report->created_at->format('H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.bug-reports.show', $report) }}" class="px-3 py-1.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 transition">
                                    ดูรายละเอียด
                                </a>
                                <button @click="confirmDelete({{ $report->id }})" class="px-3 py-1.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 transition">
                                    ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบ bug reports</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $reports->links() }}
        </div>
        @endif
    </div>

    <!-- Auto-Delete Settings Modal -->
    <div x-show="showAutoDelete" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showAutoDelete = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ตั้งเวลาลบอัตโนมัติ</h3>
                    <button @click="showAutoDelete = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.bug-reports.auto-delete') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลบ Bug Reports ที่เก่ากว่า (วัน)</label>
                        <input type="number" name="auto_delete_days" value="{{ $autoDeleteDays }}" min="0" max="365"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0 = ปิดการลบอัตโนมัติ">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            ระบุ <strong>0</strong> เพื่อปิดการลบอัตโนมัติ หรือระบุจำนวนวัน (1-365)<br>
                            ระบบจะลบ Bug Reports ที่เก่ากว่าจำนวนวันที่กำหนด ทุกวันตอน 02:00 น.
                        </p>
                    </div>

                    @if($autoDeleteEnabled)
                        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <div class="flex items-center text-sm text-amber-700 dark:text-amber-300">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                กำลังใช้งาน: ลบ Bug Reports เก่ากว่า {{ $autoDeleteDays }} วัน อัตโนมัติ
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="showAutoDelete = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6 z-10">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" x-text="deleteTitle"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" x-text="deleteMessage"></p>

                    <!-- Single Delete Form -->
                    <form x-show="deleteMode === 'single'" :action="deleteAction" method="POST" class="flex justify-center space-x-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                            ลบ
                        </button>
                    </form>

                    <!-- Bulk Delete Form -->
                    <form x-show="deleteMode === 'bulk'" action="{{ route('admin.bug-reports.bulk-delete') }}" method="POST" class="flex justify-center space-x-3">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="button" @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                            ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bugReportManager() {
    return {
        selectedIds: [],
        showAutoDelete: false,
        showDeleteModal: false,
        deleteMode: 'single',
        deleteAction: '',
        deleteTitle: '',
        deleteMessage: '',
        reportIds: @json($reports->pluck('id')->toArray()),

        get allSelected() {
            return this.reportIds.length > 0 && this.reportIds.every(id => this.selectedIds.includes(id));
        },

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.reportIds];
            } else {
                this.selectedIds = [];
            }
        },

        confirmDelete(id) {
            this.deleteMode = 'single';
            this.deleteAction = '{{ route("admin.bug-reports.index") }}/' + id;
            this.deleteTitle = 'ยืนยันการลบ';
            this.deleteMessage = 'คุณต้องการลบ Bug Report #' + id + ' หรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้';
            this.showDeleteModal = true;
        },

        confirmBulkDelete() {
            this.deleteMode = 'bulk';
            this.deleteTitle = 'ยืนยันการลบหลายรายการ';
            this.deleteMessage = 'คุณต้องการลบ Bug Reports ' + this.selectedIds.length + ' รายการที่เลือกหรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้';
            this.showDeleteModal = true;
        }
    };
}
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-up';
        toast.textContent = @json(session('success'));
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });
</script>
@endif
@endsection
