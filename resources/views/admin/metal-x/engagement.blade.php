@extends('layouts.admin')

@section('title', 'Metal-X Engagement')
@section('page-title', 'AI-Powered Comment Management & Engagement')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-blue-600 to-cyan-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">AI Engagement Manager</h2>
            <p class="text-blue-200 text-sm">ตอบคอมเมนต์อัตโนมัติ, กดไลค์, และจัดการการมีส่วนร่วมด้วย AI</p>
        </div>
        <div class="flex gap-4">
            <button onclick="syncAllComments()" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync Comments
            </button>
            <a href="{{ route('admin.metal-x.videos.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">คอมเมนต์ทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">ต้องตอบ</p>
        <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['needs_reply']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">AI ตอบแล้ว</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['ai_replied']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">ต้องตรวจสอบ</p>
        <p class="text-2xl font-bold text-red-600">{{ number_format($stats['requires_attention']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">ดี/บวก</p>
        <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['positive']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">คำถาม</p>
        <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['questions']) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-500">กดไลค์แล้ว</p>
        <p class="text-2xl font-bold text-pink-600">{{ number_format($stats['liked']) }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="?filter=all" class="px-4 py-2 rounded-lg {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            ทั้งหมด
        </a>
        <a href="?filter=needs_reply" class="px-4 py-2 rounded-lg {{ $filter === 'needs_reply' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            ต้องตอบ ({{ $stats['needs_reply'] }})
        </a>
        <a href="?filter=questions" class="px-4 py-2 rounded-lg {{ $filter === 'questions' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            คำถาม ({{ $stats['questions'] }})
        </a>
        <a href="?filter=negative" class="px-4 py-2 rounded-lg {{ $filter === 'negative' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            เชิงลบ
        </a>
        <a href="?filter=requires_attention" class="px-4 py-2 rounded-lg {{ $filter === 'requires_attention' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            ต้องตรวจสอบ ({{ $stats['requires_attention'] }})
        </a>
        <a href="?filter=ai_replied" class="px-4 py-2 rounded-lg {{ $filter === 'ai_replied' ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' }}">
            AI ตอบแล้ว ({{ $stats['ai_replied'] }})
        </a>
    </div>
</div>

<!-- Batch Actions -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <span id="selected-count">0</span> รายการที่เลือก
        </div>
        <div class="flex gap-2">
            <button onclick="batchAction('reply')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                ตอบที่เลือก
            </button>
            <button onclick="batchAction('like')" class="px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg">
                ไลค์ที่เลือก
            </button>
            <button onclick="batchAction('spam')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                แจ้ง Spam
            </button>
        </div>
    </div>
</div>

<!-- Comments List -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold">คอมเมนต์ ({{ $comments->total() }})</h3>
    </div>

    @if($comments->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($comments as $comment)
                <div class="p-6 hover:bg-gray-50" id="comment-{{ $comment->id }}">
                    <div class="flex items-start gap-4">
                        <input type="checkbox" class="comment-checkbox mt-1 rounded" value="{{ $comment->id }}" onchange="updateSelectedCount()">

                        <img src="{{ $comment->author_profile_image }}" alt="{{ $comment->author_name }}" class="w-10 h-10 rounded-full">

                        <div class="flex-1">
                            <!-- Comment Header -->
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <div class="font-medium">{{ $comment->author_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $comment->published_at?->diffForHumans() }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($comment->sentiment)
                                        @php
                                            $colors = [
                                                'positive' => 'green',
                                                'negative' => 'red',
                                                'question' => 'purple',
                                                'neutral' => 'gray'
                                            ];
                                            $color = $colors[$comment->sentiment] ?? 'gray';
                                        @endphp
                                        <span class="px-2 py-1 bg-{{ $color }}-100 text-{{ $color }}-700 text-xs rounded">
                                            {{ ucfirst($comment->sentiment) }}
                                        </span>
                                    @endif
                                    @if($comment->ai_replied)
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">AI Replied</span>
                                    @endif
                                    @if($comment->liked_by_channel)
                                        <span class="px-2 py-1 bg-pink-100 text-pink-700 text-xs rounded">❤️ Liked</span>
                                    @endif
                                    @if($comment->requires_attention)
                                        <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">⚠️ Attention</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Video Info -->
                            <div class="mb-2 text-sm text-gray-600">
                                <a href="{{ $comment->video->youtube_url }}" target="_blank" class="hover:text-blue-600">
                                    📹 {{ Str::limit($comment->video->title_en, 60) }}
                                </a>
                            </div>

                            <!-- Comment Text -->
                            <div class="mb-3 text-gray-800">
                                {{ $comment->text }}
                            </div>

                            <!-- Comment Stats -->
                            <div class="flex items-center gap-4 mb-3 text-sm text-gray-500">
                                <span>👍 {{ number_format($comment->like_count) }} likes</span>
                                @if($comment->ai_reply_confidence)
                                    <span>🎯 Confidence: {{ $comment->ai_reply_confidence }}%</span>
                                @endif
                            </div>

                            <!-- AI Reply Preview -->
                            @if($comment->ai_reply_text)
                                <div class="mb-3 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                    <div class="text-sm font-medium text-blue-900 mb-1">AI Generated Reply:</div>
                                    <div class="text-sm text-blue-800">{{ $comment->ai_reply_text }}</div>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2">
                                @if(!$comment->ai_reply_text)
                                    <button onclick="generateReply({{ $comment->id }})" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded">
                                        🤖 Generate Reply
                                    </button>
                                @elseif(!$comment->ai_replied)
                                    <button onclick="postReply({{ $comment->id }})" class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-sm rounded">
                                        ✅ Post Reply
                                    </button>
                                    <button onclick="regenerateReply({{ $comment->id }})" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm rounded">
                                        🔄 Regenerate
                                    </button>
                                @endif

                                @if(!$comment->liked_by_channel)
                                    <button onclick="likeComment({{ $comment->id }})" class="px-3 py-1 bg-pink-100 hover:bg-pink-200 text-pink-700 text-sm rounded">
                                        ❤️ Like
                                    </button>
                                @endif

                                <button onclick="toggleAttention({{ $comment->id }})" class="px-3 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-sm rounded">
                                    {{ $comment->requires_attention ? '✓ Remove Flag' : '⚠️ Flag' }}
                                </button>

                                @if(!$comment->is_spam)
                                    <button onclick="markSpam({{ $comment->id }})" class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-sm rounded">
                                        🚫 Spam
                                    </button>
                                @endif

                                <button onclick="detectViolation({{ $comment->id }})" class="px-3 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 text-sm rounded">
                                    🔍 Check Violation
                                </button>

                                @if(!$comment->deleted_at)
                                    <button onclick="deleteComment({{ $comment->id }})" class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-sm rounded">
                                        🗑️ Delete
                                    </button>
                                @endif

                                @if(!$comment->is_blacklisted_author)
                                    <button onclick="blockChannel({{ $comment->id }})" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded">
                                        🚫 Block Channel
                                    </button>
                                @endif

                                <a href="{{ $comment->video->youtube_url }}?lc={{ $comment->comment_id }}" target="_blank" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded">
                                    🔗 View on YouTube
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $comments->links() }}
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-lg">ไม่พบคอมเมนต์</p>
            <p class="text-sm">คลิก "Sync Comments" เพื่อดึงคอมเมนต์จาก YouTube</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function updateSelectedCount() {
    const count = document.querySelectorAll('.comment-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
}

function syncAllComments() {
    if (!confirm('ดึงคอมเมนต์จากวิดีโอทั้งหมดหรือไม่?')) return;

    fetch('{{ route("admin.metal-x.engagement.sync-all-comments") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            max_comments: 50,
            process_engagement: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => location.reload(), 3000);
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    });
}

function generateReply(commentId) {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ กำลังสร้าง...';

    fetch(`/admin/metal-x/engagement/comment/${commentId}/generate-reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
            btn.disabled = false;
            btn.textContent = '🤖 Generate Reply';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
        btn.disabled = false;
        btn.textContent = '🤖 Generate Reply';
    });
}

function postReply(commentId) {
    if (!confirm('โพสต์คำตอบนี้หรือไม่?')) return;

    const comment = document.querySelector(`#comment-${commentId}`);
    const replyText = comment.querySelector('.bg-blue-50 .text-blue-800').textContent;

    fetch(`/admin/metal-x/engagement/comment/${commentId}/post-reply`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ reply_text: replyText })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('โพสต์คำตอบเรียบร้อย!');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function regenerateReply(commentId) {
    generateReply(commentId);
}

function likeComment(commentId) {
    fetch(`/admin/metal-x/engagement/comment/${commentId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function toggleAttention(commentId) {
    fetch(`/admin/metal-x/engagement/comment/${commentId}/toggle-attention`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function markSpam(commentId) {
    if (!confirm('ทำเครื่องหมายเป็น spam หรือไม่?')) return;

    fetch(`/admin/metal-x/engagement/comment/${commentId}/mark-spam`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function batchAction(action) {
    const selected = Array.from(document.querySelectorAll('.comment-checkbox:checked')).map(cb => cb.value);

    if (selected.length === 0) {
        alert('กรุณาเลือกคอมเมนต์อย่างน้อย 1 รายการ');
        return;
    }

    let message = '';
    switch(action) {
        case 'reply': message = `สร้างคำตอบสำหรับ ${selected.length} คอมเมนต์?`; break;
        case 'like': message = `กดไลค์ ${selected.length} คอมเมนต์?`; break;
        case 'spam': message = `ทำเครื่องหมาย ${selected.length} คอมเมนต์เป็น spam?`; break;
    }

    if (!confirm(message)) return;

    fetch('{{ route("admin.metal-x.engagement.batch-process") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            comment_ids: selected,
            action: action
        })
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
        alert('เกิดข้อผิดพลาด');
    });
}

function detectViolation(commentId) {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '🔍 กำลังตรวจสอบ...';

    fetch(`/admin/metal-x/engagement/comment/${commentId}/detect-violation`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = '🔍 Check Violation';

        if (data.success && data.violation) {
            const v = data.violation;
            let message = `Violation Detection Result:\n\n`;
            message += `Type: ${v.violation_type}\n`;
            message += `Severity: ${v.severity}\n`;
            message += `Confidence: ${v.confidence}%\n`;
            message += `Should Delete: ${v.should_delete ? 'Yes' : 'No'}\n`;
            message += `Should Block: ${v.should_block ? 'Yes' : 'No'}\n`;
            message += `\nReason: ${v.reasoning}`;

            alert(message);

            if (v.is_violation && confirm('Violation detected! Auto-moderate this comment?')) {
                autoModerate(commentId);
            }
        } else {
            alert('ไม่พบการละเมิด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
        btn.disabled = false;
        btn.textContent = '🔍 Check Violation';
    });
}

function deleteComment(commentId) {
    if (!confirm('ลบคอมเมนต์นี้หรือไม่?\n(จะลบจาก YouTube และฐานข้อมูล)')) return;

    fetch(`/admin/metal-x/engagement/comment/${commentId}/delete`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ลบคอมเมนต์เรียบร้อย');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function blockChannel(commentId) {
    const reason = prompt('เหตุผลในการบล็อก:\n\ngambling = การพนัน\nscam = หลอกลวง\ninappropriate = เนื้อหาไม่เหมาะสม\nharassment = การคุกคาม\nspam = สแปม\nimpersonation = ปลอมแปลง\n\nกรอก:', 'gambling');

    if (!reason) return;

    const validReasons = ['gambling', 'scam', 'inappropriate', 'harassment', 'spam', 'impersonation'];
    if (!validReasons.includes(reason)) {
        alert('เหตุผลไม่ถูกต้อง');
        return;
    }

    if (!confirm(`บล็อกช่องนี้และลบคอมเมนต์ทั้งหมดเนื่องจาก "${reason}"?\n\n⚠️ การกระทำนี้ไม่สามารถย้อนกลับได้`)) return;

    fetch(`/admin/metal-x/engagement/comment/${commentId}/block-channel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}

function autoModerate(commentId) {
    fetch(`/admin/metal-x/engagement/comment/${commentId}/auto-moderate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Auto-moderation started');
            setTimeout(() => location.reload(), 2000);
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}
</script>
@endpush
