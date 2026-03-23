@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการรีวิว')
@section('page-title', 'จัดการรีวิว')

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="flex items-center gap-4">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
            รอตรวจสอบ: {{ $pendingCount }}
        </span>
        <div class="flex gap-2">
            <a href="{{ route('admin.reviews.index') }}" class="px-3 py-1 text-sm rounded-lg {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">ทั้งหมด</a>
            <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="px-3 py-1 text-sm rounded-lg {{ request('status') === 'pending' ? 'bg-amber-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">รอตรวจสอบ</a>
            <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" class="px-3 py-1 text-sm rounded-lg {{ request('status') === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">อนุมัติแล้ว</a>
            <a href="{{ route('admin.reviews.index', ['status' => 'rejected']) }}" class="px-3 py-1 text-sm rounded-lg {{ request('status') === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">ปฏิเสธ</a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้รีวิว</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สินค้า/บริการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คะแนน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ความคิดเห็น</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($reviews as $review)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            @if($review->user?->avatar)
                                <img src="{{ $review->user->avatar_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                            @else
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ substr($review->user?->name ?? '?', 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->user?->name ?? 'ลบแล้ว' }}</p>
                                <p class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $review->reviewable_name }}</p>
                        <p class="text-xs text-gray-500">{{ class_basename($review->reviewable_type) }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <x-star-rating :rating="$review->rating" size="w-4 h-4" />
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        @if($review->title)
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->title }}</p>
                        @endif
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $review->comment }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$review->status] ?? '' }}">
                            {{ $review->status_label }}
                        </span>
                        @if($review->is_featured)
                            <span class="ml-1 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">แนะนำ</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-1">
                            @if($review->status !== 'approved')
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-1.5 text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg" title="อนุมัติ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            @endif
                            @if($review->status !== 'rejected')
                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg" title="ปฏิเสธ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                            @endif
                            <form action="{{ route('admin.reviews.feature', $review) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="p-1.5 {{ $review->is_featured ? 'text-purple-600' : 'text-gray-400' }} hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg" title="แนะนำ">
                                    <svg class="w-4 h-4" fill="{{ $review->is_featured ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                </button>
                            </form>
                            <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="inline" onsubmit="return confirm('ลบรีวิวนี้?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg" title="ลบ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มีรีวิว</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reviews->links() }}
</div>
@endsection
