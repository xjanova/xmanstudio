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
</script>
@endpush
@endsection
