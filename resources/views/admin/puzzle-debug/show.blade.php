@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Debug Image #' . $record->id)
@section('page-title', 'Debug Image #' . $record->id)

@section('content')
<div class="space-y-6" x-data="labelTool()" x-init="init()">
    <!-- Back + Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.puzzle-debug.index', ['unlabeled' => '1']) }}" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับ (Unlabeled)
        </a>
        <div class="flex items-center gap-2">
            @if($prevRecord)
                <a href="{{ route('admin.puzzle-debug.show', $prevRecord) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm hover:bg-gray-300 transition">Prev</a>
            @endif
            @if($nextRecord)
                <a href="{{ route('admin.puzzle-debug.show', $nextRecord) }}" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded text-sm hover:bg-gray-300 transition">Next</a>
            @endif
        </div>
    </div>

    <!-- Status Banner -->
    @if($record->success === true)
        <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium">
            Puzzle แก้สำเร็จ (gap_x={{ $record->gap_x }})
        </div>
    @elseif($record->success === false)
        <div class="p-3 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg text-sm font-medium">
            Puzzle แก้ไม่ได้ — คลิกบนภาพด้านล่างเพื่อบอกตำแหน่ง gap ที่ถูกต้อง
        </div>
    @else
        <div class="p-3 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-lg text-sm font-medium">
            ยังไม่ทราบผล — คลิกบนภาพเพื่อ label ตำแหน่ง gap ที่ถูกต้อง
        </div>
    @endif

    @if(session('success'))
        <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Info Card (compact) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
        <div class="grid grid-cols-3 md:grid-cols-6 gap-3 text-center">
            <div>
                <div class="text-xs text-gray-500">Method</div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $record->detection_method ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Detected Gap</div>
                <div class="font-bold text-blue-600">{{ $record->gap_x ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Slider X</div>
                <div class="font-medium text-gray-600">{{ $record->slider_x ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Distance</div>
                <div class="font-medium text-gray-600">{{ $record->drag_dist ?? '-' }}px</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Track W</div>
                <div class="font-medium text-gray-600">{{ $record->track_width ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Version</div>
                <div class="font-medium text-gray-600">{{ $record->app_version ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- ===== CLICK-TO-LABEL: Main Interactive Image ===== -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-gray-700">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                คลิกบนภาพเพื่อกำหนดตำแหน่ง Gap ที่ถูกต้อง
            </h2>
            <p class="text-sm text-gray-500 mt-1">คลิกตรงกลางของช่องว่าง (gap) ที่จิ๊กซอต้องเลื่อนไปทับ</p>
        </div>

        <div class="p-4">
            @php
                // Find the "search_masked" or "before" image for labeling
                $labelImage = null;
                $allImages = $record->image_paths ?? [];
                foreach ($allImages as $p) {
                    if (str_contains(basename($p), 'search_masked') || str_contains(basename($p), 'before')) {
                        $labelImage = $p;
                        break;
                    }
                }
                if (!$labelImage && count($allImages) > 0) {
                    $labelImage = $allImages[0];
                }
            @endphp

            @if($labelImage)
                <div class="relative inline-block w-full" id="label-container">
                    <!-- The clickable image -->
                    <img src="{{ Storage::disk('public')->url($labelImage) }}"
                         alt="Click to label"
                         class="w-full cursor-crosshair select-none"
                         id="label-image"
                         @click="onImageClick($event)"
                         draggable="false">

                    <!-- Detected gap marker (blue) -->
                    @if($record->gap_x)
                        <div class="absolute top-0 bottom-0 pointer-events-none"
                             :style="'left: ' + detectedMarkerPct + '%; width: 2px;'"
                             style="background: rgba(59, 130, 246, 0.7);">
                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-[10px] px-1.5 py-0.5 rounded whitespace-nowrap">
                                Detected: {{ $record->gap_x }}
                            </div>
                        </div>
                    @endif

                    <!-- User-clicked marker (green/red) -->
                    <template x-if="clickedX !== null">
                        <div class="absolute top-0 bottom-0 pointer-events-none"
                             :style="'left: ' + clickedPct + '%; width: 3px; background: rgba(16, 185, 129, 0.9);'">
                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[10px] px-1.5 py-0.5 rounded whitespace-nowrap">
                                Actual: <span x-text="clickedX"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Actual gap marker (if already labeled) -->
                    @if($record->actual_gap_x)
                        <div class="absolute top-0 bottom-0 pointer-events-none"
                             :style="'left: ' + actualMarkerPct + '%; width: 2px;'"
                             style="background: rgba(16, 185, 129, 0.7);">
                            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-[10px] px-1.5 py-0.5 rounded whitespace-nowrap">
                                Labeled: {{ $record->actual_gap_x }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Label action bar -->
                <div class="mt-4 flex items-center gap-4 flex-wrap">
                    <form method="POST" action="{{ route('admin.puzzle-debug.label', $record) }}" id="label-form" class="flex items-center gap-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="actual_gap_x" :value="clickedX ?? '{{ $record->actual_gap_x }}'">
                        <input type="hidden" name="success" value="0">

                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Actual Gap X:</span>
                            <span class="text-xl font-bold" :class="clickedX ? 'text-emerald-600' : 'text-gray-400'"
                                  x-text="clickedX ?? '{{ $record->actual_gap_x ?? 'คลิกภาพ' }}'"></span>
                        </div>

                        @if($record->gap_x)
                            <template x-if="clickedX !== null">
                                <div class="text-sm px-2 py-1 rounded"
                                     :class="Math.abs(clickedX - {{ $record->gap_x }}) <= 20 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                    Error: <span x-text="Math.abs(clickedX - {{ $record->gap_x }})"></span>px
                                </div>
                            </template>
                        @endif

                        <button type="submit"
                                :disabled="clickedX === null && !{{ $record->actual_gap_x ? 'true' : 'false' }}"
                                class="px-5 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            บันทึก Label
                        </button>
                    </form>

                    <button @click="clickedX = null" class="text-sm text-gray-500 hover:text-gray-700 underline">
                        รีเซ็ต
                    </button>

                    @if($nextRecord)
                        <a href="{{ route('admin.puzzle-debug.show', $nextRecord) }}"
                           class="ml-auto px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                            Next Unlabeled &rarr;
                        </a>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-gray-400">ไม่มีภาพสำหรับ label</div>
            @endif
        </div>
    </div>

    <!-- All Debug Images (thumbnails) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Debug Images ทั้งหมด ({{ count($record->image_paths ?? []) }})</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @forelse($record->image_paths ?? [] as $path)
                <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-2 py-1 bg-gray-50 dark:bg-gray-700 text-[10px] font-mono text-gray-500 truncate">
                        {{ basename($path) }}
                    </div>
                    <a href="{{ Storage::disk('public')->url($path) }}" target="_blank">
                        <img src="{{ Storage::disk('public')->url($path) }}"
                             alt="{{ basename($path) }}" class="w-full hover:opacity-90 transition">
                    </a>
                </div>
            @empty
                <div class="col-span-4 text-center py-4 text-gray-400">ไม่มีภาพ</div>
            @endforelse
        </div>
    </div>

    <!-- Delete -->
    <div class="flex justify-end">
        <form method="POST" action="{{ route('admin.puzzle-debug.destroy', $record) }}" onsubmit="return confirm('ลบข้อมูลนี้?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition">
                ลบ
            </button>
        </form>
    </div>
</div>

<script>
function labelTool() {
    return {
        clickedX: null,
        clickedPct: 0,
        imageNaturalWidth: {{ $record->track_width ?? 1080 }},
        detectedMarkerPct: 0,
        actualMarkerPct: 0,

        init() {
            const img = document.getElementById('label-image');
            if (img) {
                img.onload = () => {
                    this.imageNaturalWidth = img.naturalWidth;
                    this.updateMarkers();
                };
                if (img.complete) {
                    this.imageNaturalWidth = img.naturalWidth;
                    this.updateMarkers();
                }
            }
        },

        updateMarkers() {
            const w = this.imageNaturalWidth || 1;
            @if($record->gap_x)
                this.detectedMarkerPct = ({{ $record->gap_x }} / w) * 100;
            @endif
            @if($record->actual_gap_x)
                this.actualMarkerPct = ({{ $record->actual_gap_x }} / w) * 100;
            @endif
        },

        onImageClick(event) {
            const img = event.target;
            const rect = img.getBoundingClientRect();
            const clickXPx = event.clientX - rect.left;
            const pct = clickXPx / rect.width;
            // Convert to original image pixel coordinate
            this.clickedX = Math.round(pct * this.imageNaturalWidth);
            this.clickedPct = pct * 100;
        }
    };
}
</script>
@endsection
