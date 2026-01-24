@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการโครงการ')
@section('page-title', 'จัดการโครงการ')

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
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">จัดการโครงการ</h1>
                    <p class="text-cyan-100 text-lg">ติดตามและจัดการโครงการทั้งหมดของคุณ</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('admin.projects.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 flex items-center font-semibold border border-white/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        สร้างโครงการ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total'] }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">กำลังดำเนินการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['active'] }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เสร็จสิ้น</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['completed'] }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Overdue -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เกินกำหนด</p>
                    <p class="text-3xl font-bold {{ $stats['overdue'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }} mt-2">{{ $stats['overdue'] }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap gap-4 justify-between items-center">
            <form action="{{ route('admin.projects.index') }}" method="GET" class="flex flex-wrap gap-4 flex-1">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                               placeholder="ค้นหาโครงการ...">
                    </div>
                </div>
                <div>
                    <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทุกสถานะ</option>
                        @foreach(\App\Models\ProjectOrder::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="type" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทุกประเภท</option>
                        @foreach(\App\Models\ProjectOrder::TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                    ค้นหา
                </button>
            </form>
            <a href="{{ route('admin.projects.create') }}"
               class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                สร้างโครงการ
            </a>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">โครงการ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ลูกค้า</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ความคืบหน้า</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">กำหนดส่ง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $project->project_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->project_number }}</div>
                                    <span class="inline-flex px-2.5 py-1 text-xs rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300 mt-1 font-medium">
                                        {{ $project->type_label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-lg">
                                        {{ strtoupper(substr($project->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $project->user->name ?? '-' }}</div>
                                        @if($project->project_lead)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">หัวหน้า: {{ $project->project_lead->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-36">
                                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1.5">
                                        <span class="font-semibold">{{ $project->progress_percent }}%</span>
                                        <span>{{ $project->completed_features_count }}/{{ $project->total_features_count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full transition-all duration-500 ease-out
                                            @if($project->progress_percent >= 100) bg-gradient-to-r from-green-400 to-emerald-500
                                            @elseif($project->progress_percent >= 50) bg-gradient-to-r from-blue-400 to-cyan-500
                                            @else bg-gradient-to-r from-yellow-400 to-amber-500 @endif"
                                            style="width: {{ $project->progress_percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($project->expected_end_date)
                                    <div class="{{ $project->isOverdue() ? 'text-red-600 font-bold' : 'text-gray-900 dark:text-white' }}">
                                        {{ $project->expected_end_date->format('d/m/Y') }}
                                    </div>
                                    @if($project->isOverdue())
                                        <div class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-700 dark:text-red-400 mt-1">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            เกิน {{ $project->expected_end_date->diffInDays(now()) }} วัน
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                    @switch($project->status_color)
                                        @case('green') bg-gradient-to-r from-green-400 to-emerald-500 text-white @break
                                        @case('blue') bg-gradient-to-r from-blue-400 to-cyan-500 text-white @break
                                        @case('yellow') bg-gradient-to-r from-yellow-400 to-amber-500 text-white @break
                                        @case('purple') bg-gradient-to-r from-purple-400 to-violet-500 text-white @break
                                        @case('orange') bg-gradient-to-r from-orange-400 to-amber-500 text-white @break
                                        @case('red') bg-gradient-to-r from-red-400 to-rose-500 text-white @break
                                        @default bg-gradient-to-r from-gray-400 to-gray-500 text-white
                                    @endswitch">
                                    {{ $project->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center space-x-1">
                                    <!-- View -->
                                    <a href="{{ route('admin.projects.show', $project) }}"
                                       class="p-2 text-cyan-600 hover:text-cyan-800 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-lg transition-colors duration-200"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.projects.edit', $project) }}"
                                       class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200"
                                       title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <!-- Delete -->
                                    <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ยืนยันการลบโครงการนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200"
                                                title="ลบ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-cyan-100 to-blue-200 dark:from-cyan-900/30 dark:to-blue-900/30 flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium mb-2">ไม่พบโครงการ</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">เริ่มต้นด้วยการสร้างโครงการแรก</p>
                                    <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl hover:from-cyan-600 hover:to-blue-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        สร้างโครงการใหม่
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
