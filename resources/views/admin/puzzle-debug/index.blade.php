@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Puzzle Debug Images')
@section('page-title', 'Puzzle Debug Images')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Puzzle Debug Images</h1>
                </div>
                <p class="text-indigo-100 text-lg">ภาพ Debug จาก CAPTCHA Solver สำหรับ AI Learning</p>
            </div>
        </div>
    </div>

    <!-- AI Learning Panel -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="p-6 border-b dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">AI Learning Center</h2>
                    <p class="text-sm text-gray-500 mt-1">คำนวณ correction model จากข้อมูลที่เก็บมา</p>
                </div>
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('admin.puzzle-debug.auto-label') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition">
                            Auto-Label (success=true)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.puzzle-debug.train') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg text-sm font-bold hover:from-purple-700 hover:to-indigo-700 transition shadow-lg">
                            AI Learning
                        </button>
                    </form>
                </div>
            </div>

            @if(session('success'))
                <div class="mt-4 p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
        </div>

        <!-- AI Model Status -->
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold {{ ($aiModel['correction'] ?? 0) == 0 ? 'text-gray-400' : 'text-purple-600' }}">
                        {{ ($aiModel['correction'] ?? 0) > 0 ? '+' : '' }}{{ $aiModel['correction'] ?? 0 }}px
                    </div>
                    <div class="text-xs text-gray-500">Correction Offset</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $aiModel['samples'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Training Samples</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold {{ ($stats['success_rate'] ?? 0) >= 80 ? 'text-green-600' : (($stats['success_rate'] ?? 0) >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                        {{ $stats['success_rate'] }}%
                    </div>
                    <div class="text-xs text-gray-500">Success Rate ({{ $stats['success_count'] }}/{{ $stats['success_count'] + $stats['fail_count'] }})</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Last Trained</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $aiModel['trained_at'] ? \Carbon\Carbon::parse($aiModel['trained_at'])->diffForHumans() : 'ยังไม่เคย' }}
                    </div>
                </div>
            </div>

            @if(!empty($aiModel['by_method']))
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">Per Method:</span>
                    @foreach($aiModel['by_method'] as $method => $data)
                        <span class="ml-3 px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded text-indigo-700 dark:text-indigo-300">
                            {{ $method }}: {{ $data['avg_correction'] > 0 ? '+' : '' }}{{ round($data['avg_correction'], 1) }}px ({{ $data['samples'] }} samples)
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">ทั้งหมด</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-green-600">{{ $stats['labeled'] }}</div>
            <div class="text-sm text-gray-500">Labeled แล้ว</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-amber-600">{{ $stats['unlabeled'] }}</div>
            <div class="text-sm text-gray-500">ยัง Unlabeled</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['avg_error'] }}px</div>
            <div class="text-sm text-gray-500">Avg Error</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['accuracy_pct'] }}%</div>
            <div class="text-sm text-gray-500">Accuracy (within 20px)</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="text" name="machine_id" value="{{ request('machine_id') }}"
                   placeholder="Machine ID..." class="px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <select name="method" class="px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">ทุก Method</option>
                <option value="diff" {{ request('method') === 'diff' ? 'selected' : '' }}>Diff</option>
                <option value="static" {{ request('method') === 'static' ? 'selected' : '' }}>Static</option>
            </select>
            <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="unlabeled" value="1" {{ request('unlabeled') === '1' ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <span>Unlabeled เท่านั้น</span>
            </label>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                ค้นหา
            </button>
            <a href="{{ route('admin.puzzle-debug.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white rounded-lg text-sm hover:bg-gray-300 transition">
                ล้าง
            </a>
        </form>
    </div>

    <!-- Image Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($records as $record)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <!-- Image Preview -->
            <div class="relative">
                @if($record->image_paths && count($record->image_paths) > 0)
                    <img src="{{ Storage::disk('public')->url($record->image_paths[0]) }}"
                         alt="Debug Image" class="w-full h-48 object-cover cursor-pointer"
                         onclick="window.location='{{ route('admin.puzzle-debug.show', $record) }}'">
                    @if(count($record->image_paths) > 1)
                        <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full">
                            {{ count($record->image_paths) }} images
                        </div>
                    @endif
                @else
                    <div class="w-full h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                        No images
                    </div>
                @endif

                <!-- Status badge -->
                <div class="absolute top-2 left-2">
                    @if($record->actual_gap_x !== null)
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Labeled</span>
                    @else
                        <span class="bg-amber-500 text-white text-xs px-2 py-1 rounded-full">Unlabeled</span>
                    @endif
                </div>
            </div>

            <!-- Info -->
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-mono text-gray-500">{{ substr($record->machine_id, 0, 12) }}...</span>
                    <span class="text-xs text-gray-400">{{ $record->created_at->diffForHumans() }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">Method:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $record->detection_method ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Gap X:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $record->gap_x ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Slider X:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $record->slider_x ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Distance:</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $record->drag_dist ?? '-' }}px</span>
                    </div>
                </div>

                @if($record->actual_gap_x !== null)
                    <div class="mt-2 p-2 rounded-lg {{ abs($record->gap_x - $record->actual_gap_x) <= 20 ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <span class="text-xs">
                            Actual: <strong>{{ $record->actual_gap_x }}</strong>
                            | Error: <strong>{{ abs($record->gap_x - $record->actual_gap_x) }}px</strong>
                        </span>
                    </div>
                @endif

                <!-- Quick Label Form -->
                <form method="POST" action="{{ route('admin.puzzle-debug.label', $record) }}" class="mt-3 flex items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="number" name="actual_gap_x" value="{{ $record->actual_gap_x }}"
                           placeholder="Actual gap X" class="flex-1 px-2 py-1 border rounded text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition">
                        Label
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-400">
            ยังไม่มีข้อมูล Debug Images
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $records->links() }}
    </div>
</div>
@endsection
