@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Promo Comments')
@section('page-title', 'คอมเม้นต์โปรโมทเรียกยอด')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-pink-600 to-rose-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">Promo Comment Manager</h2>
            <p class="text-pink-200 text-sm">AI สร้างคอมเม้นต์โปรโมทบนวิดีโอของช่อง เพื่อเรียกยอด Engagement</p>
        </div>
        <a href="{{ route('admin.metal-x.automation.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับ
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">แบบร่าง</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['drafts'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">รอโพส</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['scheduled'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">โพสแล้ว</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['posted'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">โพสวันนี้</p>
        <p class="text-2xl font-bold text-purple-600">{{ $stats['posted_today'] }}</p>
    </div>
</div>

<!-- Quota/Failed Management -->
@php
    $failedCount = \App\Models\MetalXPromoComment::where('status', 'failed')->count();
    $quotaFailedCount = \App\Models\MetalXPromoComment::where('status', 'failed')->where('error_message', 'like', '%quotaExceeded%')->count();
@endphp
@if($failedCount > 0)
<div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg shadow p-4 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h4 class="text-sm font-semibold text-red-800 dark:text-red-200">
                คอมเม้นต์ที่ล้มเหลว: {{ $failedCount }} รายการ
                @if($quotaFailedCount > 0)
                    <span class="text-xs text-red-600 dark:text-red-400">({{ $quotaFailedCount }} จาก quota เต็ม)</span>
                @endif
            </h4>
            <p class="text-xs text-red-600 dark:text-red-400 mt-1">YouTube API quota จะรีเซ็ตทุกเที่ยงคืน Pacific Time (ประมาณ 14:00 ไทย)</p>
        </div>
        <div class="flex items-center gap-2">
            @if($quotaFailedCount > 0)
                <button onclick="retryFailedPromos()" class="px-3 py-1.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-800 text-xs font-medium">
                    🔄 ลองโพสใหม่ ({{ $quotaFailedCount }})
                </button>
            @endif
            <button onclick="cleanupFailedPromos()" class="px-3 py-1.5 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 text-xs font-medium">
                🗑️ ลบซ้ำที่ล้มเหลว
            </button>
        </div>
    </div>
</div>
@endif

<!-- Pinned Comment Section -->
<div class="bg-gradient-to-r from-amber-600/20 to-yellow-600/20 border border-amber-500/30 rounded-lg shadow p-6 mb-6" x-data="pinnedManager()">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ปักหมุดคอมเม้นต์เรียกวิว</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">สร้างคอมเม้นต์ปักหมุดสำหรับทุกวิดีโอที่ยังไม่มี</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="checkWithoutPins()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                ตรวจสอบ
            </button>
            <button @click="generateAllPinned()" class="px-4 py-2 bg-gradient-to-r from-amber-600 to-yellow-600 text-white rounded-lg hover:from-amber-700 hover:to-yellow-700 text-sm font-medium" :disabled="generating">
                <svg class="w-4 h-4 inline mr-1" :class="generating ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span x-text="generating ? 'กำลังสร้าง...' : 'สร้างปักหมุดทั้งหมด'"></span>
            </button>
        </div>
    </div>

    <!-- Results -->
    <div x-show="checked" x-cloak class="mt-4">
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-white/10 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">วิดีโอทั้งหมด</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="totalActive"></p>
            </div>
            <div class="bg-white/10 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">มีปักหมุดแล้ว</p>
                <p class="text-xl font-bold text-green-500" x-text="withPins"></p>
            </div>
            <div class="bg-white/10 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">ยังไม่มีปักหมุด</p>
                <p class="text-xl font-bold text-amber-500" x-text="withoutPins"></p>
            </div>
        </div>

        <div x-show="videos.length > 0" class="max-h-60 overflow-y-auto space-y-2">
            <template x-for="video in videos" :key="video.id">
                <div class="flex items-center justify-between bg-white/5 dark:bg-gray-700/30 rounded-lg p-2 text-sm">
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-900 dark:text-white truncate" x-text="video.title"></p>
                        <p class="text-xs text-gray-500" x-text="'👁 ' + Number(video.view_count).toLocaleString() + ' views'"></p>
                    </div>
                    <a :href="video.studio_url" target="_blank" class="px-2 py-1 bg-blue-600/20 text-blue-400 rounded text-xs hover:bg-blue-600/30 shrink-0 ml-2">
                        YouTube Studio
                    </a>
                </div>
            </template>
        </div>

        <div x-show="generateResult" class="mt-3 p-3 bg-green-500/20 border border-green-500/30 rounded-lg text-sm text-green-300" x-text="generateResult"></div>
    </div>
</div>

<!-- Generate Promo -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สร้างคอมเม้นต์โปรโมทด้วย AI</h3>
    <div class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลือกวิดีโอ</label>
            <select id="promo-video-select" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">-- เลือกวิดีโอ --</option>
                @foreach($videos as $video)
                    <option value="{{ $video->id }}">{{ Str::limit($video->title, 60) }}</option>
                @endforeach
            </select>
        </div>
        <button onclick="generatePromo()" class="px-6 py-2 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg hover:from-pink-700 hover:to-rose-700 text-sm font-medium whitespace-nowrap">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            AI สร้างโปรโมท
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกสถานะ</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>แบบร่าง</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>รอโพส</option>
                <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>โพสแล้ว</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
            </select>
        </div>
        <div>
            <select name="video_id" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกวิดีโอ</option>
                @foreach($videos as $video)
                    <option value="{{ $video->id }}" {{ request('video_id') == $video->id ? 'selected' : '' }}>{{ Str::limit($video->title, 40) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">กรอง</button>
        @if(request()->hasAny(['status', 'video_id']))
            <a href="{{ route('admin.metal-x.automation.promo') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm">ล้าง</a>
        @endif
    </form>
</div>

<!-- Promo Comments List -->
<div class="space-y-4">
    @forelse($promos as $promo)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $promo->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                            {{ $promo->status === 'scheduled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $promo->status === 'posted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $promo->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                        ">
                            {{ $promo->status_label }}
                        </span>
                        @if($promo->generated_by_ai)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                AI
                            </span>
                        @endif
                        @if($promo->is_pinned)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                📌 ปักหมุดแล้ว
                            </span>
                        @elseif($promo->should_pin)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600 dark:bg-amber-900/50 dark:text-amber-300">
                                📌 รอปักหมุด
                            </span>
                        @endif
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $promo->created_at->format('d/m/Y H:i') }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                        วิดีโอ: <span class="text-gray-900 dark:text-white font-medium">{{ $promo->video->title ?? '-' }}</span>
                    </p>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 mt-2">
                        <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $promo->comment_text }}</p>
                    </div>

                    @if($promo->error_message)
                        <p class="text-xs text-red-600 dark:text-red-400 mt-2">{{ $promo->error_message }}</p>
                    @endif

                    @if($promo->posted_at)
                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">โพสเมื่อ: {{ $promo->posted_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if($promo->status === 'posted' && $promo->should_pin && !$promo->is_pinned)
                        <a href="https://studio.youtube.com/video/{{ $promo->video->youtube_id ?? '' }}/comments" target="_blank" class="px-3 py-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 text-xs font-medium" title="เปิด YouTube Studio เพื่อปักหมุด">
                            YT Studio
                        </a>
                        <button onclick="markPinned({{ $promo->id }})" class="px-3 py-1.5 bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-800 text-xs font-medium">
                            ✓ ปักหมุดแล้ว
                        </button>
                    @endif
                    @if($promo->status === 'draft')
                        <button onclick="approvePromo({{ $promo->id }})" class="px-3 py-1.5 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200 rounded-lg hover:bg-green-200 dark:hover:bg-green-800 text-xs font-medium">
                            อนุมัติ
                        </button>
                    @endif
                    @if($promo->status !== 'posted')
                        <button onclick="deletePromo({{ $promo->id }})" class="px-3 py-1.5 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 text-xs font-medium">
                            ลบ
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500 dark:text-gray-400">ยังไม่มีคอมเม้นต์โปรโมท</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">เลือกวิดีโอด้านบนแล้วกด "AI สร้างโปรโมท" เพื่อเริ่มต้น</p>
        </div>
    @endforelse

    @if($promos->hasPages())
        <div class="mt-6">
            {{ $promos->withQueryString()->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function generatePromo() {
    const videoId = document.getElementById('promo-video-select').value;
    if (!videoId) {
        alert('กรุณาเลือกวิดีโอก่อน');
        return;
    }
    fetch(`{{ url('admin/metal-x/automation/promo') }}/${videoId}/generate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'กำลังสร้าง...');
        setTimeout(() => location.reload(), 2000);
    })
    .catch(() => alert('เกิดข้อผิดพลาด'));
}

function approvePromo(id) {
    if (!confirm('อนุมัติคอมเม้นต์นี้ เพื่อโพสลง YouTube?')) return;
    fetch(`{{ url('admin/metal-x/automation/promo') }}/${id}/approve`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'เกิดข้อผิดพลาด');
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function deletePromo(id) {
    if (!confirm('ยืนยันการลบคอมเม้นต์นี้?')) return;
    fetch(`{{ url('admin/metal-x/automation/promo') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'เกิดข้อผิดพลาด');
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function markPinned(id) {
    if (!confirm('ยืนยันว่าคุณได้ปักหมุดคอมเม้นต์นี้บน YouTube Studio แล้ว?')) return;
    fetch(`{{ url('admin/metal-x/automation/promo') }}/${id}/mark-pinned`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'เกิดข้อผิดพลาด');
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function retryFailedPromos() {
    if (!confirm('ลองโพสคอมเม้นต์ที่ล้มเหลวจาก quota อีกครั้ง?')) return;
    fetch('{{ route("admin.metal-x.automation.promo.retry-failed") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'สำเร็จ');
        location.reload();
    })
    .catch(() => alert('เกิดข้อผิดพลาด'));
}

function cleanupFailedPromos() {
    if (!confirm('ลบคอมเม้นต์ที่ล้มเหลวซ้ำ? (เก็บไว้ 1 ต่อวิดีโอสำหรับลองใหม่)')) return;
    fetch('{{ route("admin.metal-x.automation.promo.cleanup-failed") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || 'สำเร็จ');
        location.reload();
    })
    .catch(() => alert('เกิดข้อผิดพลาด'));
}

function pinnedManager() {
    return {
        checked: false,
        generating: false,
        totalActive: 0,
        withPins: 0,
        withoutPins: 0,
        videos: [],
        generateResult: '',

        checkWithoutPins() {
            fetch('{{ route("admin.metal-x.automation.promo.without-pins") }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                this.totalActive = data.total_active;
                this.withPins = data.with_pins;
                this.withoutPins = data.without_pins;
                this.videos = data.videos;
                this.checked = true;
            })
            .catch(() => alert('เกิดข้อผิดพลาด'));
        },

        generateAllPinned() {
            if (!confirm(`สร้างคอมเม้นต์ปักหมุดสำหรับวิดีโอที่ยังไม่มี (${this.withoutPins} วิดีโอ)?`)) return;
            this.generating = true;
            this.generateResult = '';

            fetch('{{ route("admin.metal-x.automation.promo.generate-pinned") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                this.generating = false;
                this.generateResult = data.message || 'สำเร็จ!';
                setTimeout(() => location.reload(), 3000);
            })
            .catch(() => {
                this.generating = false;
                alert('เกิดข้อผิดพลาด');
            });
        }
    };
}
</script>
@endpush
@endsection
