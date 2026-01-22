@extends('layouts.admin')

@section('title', 'Metal-X AI Tools')
@section('page-title', 'AI-Powered YouTube Metadata Management')

@section('content')
<!-- Header Section -->
<div class="bg-gradient-to-r from-purple-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">AI Metadata Generation</h2>
            <p class="text-purple-200 text-sm">ให้ AI สร้างข้อมูลภาษาไทยสำหรับวิดีโอของคุณโดยอัตโนมัติ</p>
        </div>
        <div class="flex gap-4">
            <button onclick="refreshStatus()" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                รีเฟรช
            </button>
            <a href="{{ route('admin.metal-x.videos.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปวิดีโอ
            </a>
        </div>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">วิดีโอทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_videos']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">สร้างด้วย AI</p>
        <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['videos_with_ai']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">อนุมัติแล้ว</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['approved_ai']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">รออนุมัติ</p>
        <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending_review']) }}</p>
    </div>
</div>

<!-- Batch Actions -->
<div class="bg-white rounded-lg shadow mb-6 p-6">
    <h3 class="text-lg font-bold mb-4">การดำเนินการแบบกลุ่ม</h3>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold mb-2">สร้าง Metadata สำหรับวิดีโอทั้งหมด</h4>
            <p class="text-sm text-gray-600 mb-4">สร้าง metadata ภาษาไทยสำหรับวิดีโอทั้งหมดที่ยังไม่มี</p>
            <div class="flex gap-2">
                <button onclick="generateAll(false)" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                    สร้างทั้งหมด
                </button>
                <button onclick="generateAll(true)" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                    สร้างและอนุมัติอัตโนมัติ
                </button>
            </div>
        </div>
        <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold mb-2">อนุมัติรายการที่เลือก</h4>
            <p class="text-sm text-gray-600 mb-4">อนุมัติ metadata ที่ AI สร้างสำหรับวิดีโอที่เลือก</p>
            <button onclick="approveSelected()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                อนุมัติรายการที่เลือก
            </button>
        </div>
    </div>
</div>

<!-- Videos Pending Review -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold">วิดีโอรออนุมัติ ({{ $pendingVideos->total() }})</h3>
    </div>

    @if($pendingVideos->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            วิดีโอ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            AI Metadata
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            คะแนนความเชื่อมั่น
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            สร้างเมื่อ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            การดำเนินการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($pendingVideos as $video)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input type="checkbox" class="video-checkbox rounded" value="{{ $video->id }}">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title_en }}" class="w-20 h-auto rounded">
                                    <div>
                                        <div class="font-medium text-gray-900">{{ Str::limit($video->title_en, 50) }}</div>
                                        <div class="text-sm text-gray-500">{{ $video->youtube_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">{{ Str::limit($video->ai_title_th, 40) }}</div>
                                    <div class="text-gray-500 mt-1">{{ Str::limit($video->ai_description_th, 60) }}</div>
                                    @if($video->ai_tags)
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach(array_slice($video->ai_tags, 0, 3) as $tag)
                                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">{{ $tag }}</span>
                                            @endforeach
                                            @if(count($video->ai_tags) > 3)
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+{{ count($video->ai_tags) - 3 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $score = $video->ai_confidence_score ?? 0;
                                    $color = $score >= 80 ? 'green' : ($score >= 60 ? 'yellow' : 'red');
                                @endphp
                                <span class="px-3 py-1 bg-{{ $color }}-100 text-{{ $color }}-800 text-sm font-medium rounded-full">
                                    {{ $score }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $video->ai_generated_at?->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <button onclick="previewMetadata({{ $video->id }})"
                                            class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded">
                                        ดูตัวอย่าง
                                    </button>
                                    <button onclick="approveVideo({{ $video->id }})"
                                            class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-sm rounded">
                                        อนุมัติ
                                    </button>
                                    <button onclick="rejectVideo({{ $video->id }})"
                                            class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-sm rounded">
                                        ปฏิเสธ
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $pendingVideos->links() }}
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-lg">ไม่มีวิดีโอรออนุมัติ</p>
            <p class="text-sm">คลิก "สร้างทั้งหมด" เพื่อสร้าง metadata ด้วย AI</p>
        </div>
    @endif
</div>

<!-- Preview Modal -->
<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold">ตัวอย่าง AI Metadata</h3>
            <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="previewContent" class="p-6">
            <!-- Content will be loaded via JS -->
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.video-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function refreshStatus() {
    fetch('{{ route("admin.metal-x.ai.status") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

function generateAll(autoApprove) {
    if (!confirm(autoApprove ? 'สร้างและอนุมัติ metadata สำหรับวิดีโอทั้งหมดหรือไม่?' : 'สร้าง metadata สำหรับวิดีโอทั้งหมดหรือไม่?')) {
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('auto_approve', autoApprove ? '1' : '0');
    formData.append('min_confidence', '80');

    fetch('{{ route("admin.metal-x.ai.generate-all") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

function approveSelected() {
    const selected = Array.from(document.querySelectorAll('.video-checkbox:checked')).map(cb => cb.value);

    if (selected.length === 0) {
        alert('กรุณาเลือกวิดีโออย่างน้อย 1 รายการ');
        return;
    }

    if (!confirm(`อนุมัติ metadata สำหรับ ${selected.length} วิดีโอหรือไม่?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    selected.forEach(id => formData.append('video_ids[]', id));

    fetch('{{ route("admin.metal-x.ai.approve-batch") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

function approveVideo(videoId) {
    if (!confirm('อนุมัติ metadata นี้หรือไม่?')) {
        return;
    }

    fetch(`/admin/metal-x/ai/${videoId}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

function rejectVideo(videoId) {
    if (!confirm('ปฏิเสธ metadata นี้หรือไม่?')) {
        return;
    }

    fetch(`/admin/metal-x/ai/${videoId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

function previewMetadata(videoId) {
    fetch(`/admin/metal-x/ai/${videoId}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = `
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-bold text-lg mb-3 text-purple-600">AI Generated</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Title (TH)</label>
                                    <p class="mt-1 p-3 bg-purple-50 rounded">${data.metadata.title_th || '-'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Description (TH)</label>
                                    <p class="mt-1 p-3 bg-purple-50 rounded max-h-40 overflow-y-auto">${data.metadata.description_th || '-'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Tags</label>
                                    <div class="mt-1 p-3 bg-purple-50 rounded flex flex-wrap gap-1">
                                        ${data.metadata.tags ? data.metadata.tags.map(tag => `<span class="px-2 py-1 bg-purple-200 text-purple-800 text-xs rounded">${tag}</span>`).join('') : '-'}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Category</label>
                                    <p class="mt-1 p-3 bg-purple-50 rounded">${data.metadata.category || '-'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Confidence Score</label>
                                    <p class="mt-1 p-3 bg-purple-50 rounded font-bold">${data.metadata.confidence_score}%</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-lg mb-3 text-gray-600">Current</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Title (TH)</label>
                                    <p class="mt-1 p-3 bg-gray-50 rounded">${data.current.title_th || 'ไม่มี'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Description (TH)</label>
                                    <p class="mt-1 p-3 bg-gray-50 rounded max-h-40 overflow-y-auto">${data.current.description_th || 'ไม่มี'}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Tags</label>
                                    <p class="mt-1 p-3 bg-gray-50 rounded">${data.current.tags || 'ไม่มี'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('previewContent').innerHTML = content;
                document.getElementById('previewModal').classList.remove('hidden');
            } else {
                alert('ไม่สามารถโหลดข้อมูลได้');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        });
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
}
</script>
@endpush
