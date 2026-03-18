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
        <div class="p-6 space-y-5">
            <!-- Row 1: Model core stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold {{ ($aiModel['correction'] ?? 0) == 0 ? 'text-gray-400' : 'text-purple-600' }}">
                        {{ ($aiModel['correction'] ?? 0) > 0 ? '+' : '' }}{{ $aiModel['correction'] ?? 0 }}px
                    </div>
                    <div class="text-xs text-gray-500">Correction Offset</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $aiModel['samples'] ?? 0 }}</div>
                    <div class="text-xs text-gray-500">Training Samples</div>
                    @if(($aiModel['samples'] ?? 0) < 10)
                        <div class="text-[10px] text-amber-500 mt-1">ต้องการ 10+ เพื่อเปิดใช้</div>
                    @else
                        <div class="text-[10px] text-green-500 mt-1">เปิดใช้กับ App แล้ว</div>
                    @endif
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold {{ ($stats['success_rate'] ?? 0) >= 80 ? 'text-green-600' : (($stats['success_rate'] ?? 0) >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                        {{ $stats['success_rate'] }}%
                    </div>
                    <div class="text-xs text-gray-500">Success Rate</div>
                    <div class="text-[10px] text-gray-400">({{ $stats['success_count'] }}/{{ $stats['success_count'] + $stats['fail_count'] }})</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl font-bold {{ ($stats['recent_24h_rate'] ?? 0) >= 80 ? 'text-green-600' : (($stats['recent_24h_rate'] ?? 0) >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                        {{ $stats['recent_24h_rate'] }}%
                    </div>
                    <div class="text-xs text-gray-500">24 ชม. ล่าสุด</div>
                    <div class="text-[10px] text-gray-400">({{ $stats['recent_24h_success'] }}/{{ $stats['recent_24h'] }})</div>
                </div>
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Last Trained</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $aiModel['trained_at'] ? \Carbon\Carbon::parse($aiModel['trained_at'])->diffForHumans() : 'ยังไม่เคย' }}
                    </div>
                    @if($aiModel['std_dev'] ?? 0)
                        <div class="text-[10px] text-gray-400">std: {{ $aiModel['std_dev'] }}px</div>
                    @endif
                </div>
            </div>

            <!-- Row 2: Model intelligence progress -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-purple-800 dark:text-purple-300">ความฉลาดของ Model</h3>
                    <span class="text-xs px-2 py-1 rounded-full font-medium
                        @if(($aiModel['samples'] ?? 0) >= 50) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300
                        @elseif(($aiModel['samples'] ?? 0) >= 10) bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                        @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400
                        @endif">
                        @if(($aiModel['samples'] ?? 0) >= 100) Expert
                        @elseif(($aiModel['samples'] ?? 0) >= 50) Advanced
                        @elseif(($aiModel['samples'] ?? 0) >= 20) Intermediate
                        @elseif(($aiModel['samples'] ?? 0) >= 10) Beginner (Active)
                        @elseif(($aiModel['samples'] ?? 0) >= 3) Learning (Inactive)
                        @else No Data
                        @endif
                    </span>
                </div>

                <!-- Progress bar -->
                @php
                    $samples = $aiModel['samples'] ?? 0;
                    $milestones = [
                        ['at' => 3, 'label' => 'เริ่มเรียน', 'pct' => 6],
                        ['at' => 10, 'label' => 'เปิดใช้งาน', 'pct' => 20],
                        ['at' => 20, 'label' => 'เข้าใจ pattern', 'pct' => 40],
                        ['at' => 50, 'label' => 'แม่นยำ', 'pct' => 70],
                        ['at' => 100, 'label' => 'Expert', 'pct' => 100],
                    ];
                    $progressPct = 0;
                    foreach ($milestones as $m) {
                        if ($samples >= $m['at']) $progressPct = $m['pct'];
                    }
                    if ($samples > 0 && $samples < 3) $progressPct = (int)($samples / 3 * 6);
                @endphp
                <div class="relative mb-2">
                    <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500
                            @if($progressPct >= 70) bg-gradient-to-r from-green-400 to-emerald-500
                            @elseif($progressPct >= 20) bg-gradient-to-r from-blue-400 to-indigo-500
                            @else bg-gradient-to-r from-gray-300 to-gray-400
                            @endif"
                             style="width: {{ $progressPct }}%"></div>
                    </div>
                    <!-- Milestone markers -->
                    <div class="flex justify-between mt-1">
                        @foreach($milestones as $m)
                            <div class="text-center" style="width: 1px;">
                                <div class="text-[9px] {{ $samples >= $m['at'] ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-gray-400' }}">
                                    {{ $m['at'] }}
                                </div>
                                <div class="text-[8px] {{ $samples >= $m['at'] ? 'text-purple-500' : 'text-gray-300' }}">
                                    {{ $m['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Key metrics row -->
                <div class="grid grid-cols-4 gap-2 mt-3">
                    <div class="text-center">
                        <div class="text-lg font-bold text-purple-700 dark:text-purple-300">{{ $stats['human_labeled'] }}</div>
                        <div class="text-[10px] text-gray-500">Human Labels</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600">{{ $stats['with_images'] }}</div>
                        <div class="text-[10px] text-gray-500">มีภาพ</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold {{ ($stats['avg_error'] ?? 0) <= 15 ? 'text-green-600' : (($stats['avg_error'] ?? 0) <= 30 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ $stats['avg_error'] }}px
                        </div>
                        <div class="text-[10px] text-gray-500">Avg Error</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold {{ ($stats['accuracy_pct'] ?? 0) >= 80 ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $stats['accuracy_pct'] }}%
                        </div>
                        <div class="text-[10px] text-gray-500">within 20px</div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Error distribution -->
            @if($stats['human_labeled'] > 0)
            <div>
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">การกระจายความผิดพลาด (Human-labeled)</h3>
                <div class="flex gap-1 h-6 rounded-lg overflow-hidden">
                    @php
                        $buckets = $stats['error_buckets'];
                        $totalBucket = array_sum($buckets) ?: 1;
                        $colors = [
                            'perfect' => 'bg-emerald-500',
                            'good' => 'bg-green-400',
                            'ok' => 'bg-amber-400',
                            'bad' => 'bg-orange-500',
                            'miss' => 'bg-red-500',
                        ];
                        $labels = [
                            'perfect' => '0-5px',
                            'good' => '6-15px',
                            'ok' => '16-30px',
                            'bad' => '31-50px',
                            'miss' => '51+px',
                        ];
                    @endphp
                    @foreach($buckets as $key => $count)
                        @if($count > 0)
                            <div class="{{ $colors[$key] }} flex items-center justify-center text-[9px] text-white font-bold"
                                 style="width: {{ round($count / $totalBucket * 100, 1) }}%"
                                 title="{{ $labels[$key] }}: {{ $count }} ({{ round($count / $totalBucket * 100, 1) }}%)">
                                @if(round($count / $totalBucket * 100) >= 10){{ $count }}@endif
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="flex gap-3 mt-1 flex-wrap">
                    @foreach($buckets as $key => $count)
                        <span class="text-[10px] text-gray-500 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full {{ $colors[$key] }}"></span>
                            {{ $labels[$key] }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Row 4: Per-method breakdown -->
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
            <div class="text-2xl font-bold text-green-600">{{ $stats['human_labeled'] }}</div>
            <div class="text-sm text-gray-500">Human Labeled</div>
            @if($stats['labeled'] > $stats['human_labeled'])
                <div class="text-xs text-gray-400">+{{ $stats['labeled'] - $stats['human_labeled'] }} auto</div>
            @endif
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
                <input type="checkbox" name="show_all" value="1" {{ request('show_all') === '1' ? 'checked' : '' }}
                       class="rounded border-gray-300">
                <span>แสดงที่ Label แล้วด้วย</span>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden cursor-pointer hover:ring-2 hover:ring-indigo-400 transition"
             onclick="window.location='{{ route('admin.puzzle-debug.show', $record) }}'">
            <!-- Image Preview -->
            <div class="relative">
                @if($record->image_paths && count($record->image_paths) > 0)
                    <img src="{{ Storage::disk('public')->url($record->image_paths[0]) }}"
                         alt="Debug Image" class="w-full h-48 object-cover">
                    @if(count($record->image_paths) > 1)
                        <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full">
                            {{ count($record->image_paths) }} images
                        </div>
                    @endif
                @else
                    <div class="w-full h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                        No images (feedback only)
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
