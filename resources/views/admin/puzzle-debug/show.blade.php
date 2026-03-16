@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Debug Image #' . $record->id)
@section('page-title', 'Debug Image #' . $record->id)

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.puzzle-debug.index') }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับ
        </a>
    </div>

    <!-- Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ข้อมูล Detection</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-sm text-gray-500">Machine ID</div>
                <div class="font-mono text-sm text-gray-900 dark:text-white break-all">{{ $record->machine_id }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">App Version</div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $record->app_version ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Method</div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $record->detection_method ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Captured</div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('d M Y H:i:s') }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Detected Gap X</div>
                <div class="text-2xl font-bold text-blue-600">{{ $record->gap_x ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Slider X</div>
                <div class="text-2xl font-bold text-gray-600">{{ $record->slider_x ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Drag Distance</div>
                <div class="text-2xl font-bold text-gray-600">{{ $record->drag_dist ?? '-' }}px</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Track Width</div>
                <div class="text-2xl font-bold text-gray-600">{{ $record->track_width ?? '-' }}px</div>
            </div>
        </div>
    </div>

    <!-- Label Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Label สำหรับ Training</h2>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.puzzle-debug.label', $record) }}" class="flex flex-wrap items-end gap-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Actual Gap X (ตำแหน่ง gap จริง)</label>
                <input type="number" name="actual_gap_x" value="{{ $record->actual_gap_x }}"
                       class="px-4 py-2 border rounded-lg w-40 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                       placeholder="เช่น 1480" required>
            </div>

            <div>
                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">สำเร็จ?</label>
                <select name="success" class="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">ไม่ทราบ</option>
                    <option value="1" {{ $record->success === true ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="0" {{ $record->success === false ? 'selected' : '' }}>ไม่สำเร็จ</option>
                </select>
            </div>

            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                บันทึก Label
            </button>

            @if($record->actual_gap_x !== null && $record->gap_x)
                <div class="ml-4 p-3 rounded-lg {{ abs($record->gap_x - $record->actual_gap_x) <= 20 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                    Error: <strong>{{ abs($record->gap_x - $record->actual_gap_x) }}px</strong>
                    {{ abs($record->gap_x - $record->actual_gap_x) <= 20 ? '(Good!)' : '(Too far)' }}
                </div>
            @endif
        </form>
    </div>

    <!-- All Images -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Debug Images ({{ count($record->image_paths ?? []) }})</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($record->image_paths ?? [] as $path)
                <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 text-xs font-mono text-gray-600 dark:text-gray-300">
                        {{ basename($path) }}
                    </div>
                    <a href="{{ Storage::disk('public')->url($path) }}" target="_blank">
                        <img src="{{ Storage::disk('public')->url($path) }}"
                             alt="{{ basename($path) }}" class="w-full hover:opacity-90 transition">
                    </a>
                </div>
            @empty
                <div class="col-span-2 text-center py-8 text-gray-400">
                    ไม่มีภาพ
                </div>
            @endforelse
        </div>
    </div>

    <!-- Delete -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <form method="POST" action="{{ route('admin.puzzle-debug.destroy', $record) }}" onsubmit="return confirm('ลบข้อมูลนี้?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                ลบข้อมูลนี้
            </button>
        </form>
    </div>
</div>
@endsection
