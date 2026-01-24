@extends($customerLayout ?? 'layouts.customer')

@section('title', 'Ticket #' . $ticket->ticket_number)
@section('page-title', 'Ticket #' . $ticket->ticket_number)

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
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Premium Ticket Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-6 sm:p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @php
                            $statusColors = [
                                'open' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                'in_progress' => 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white',
                                'waiting_reply' => 'bg-gradient-to-r from-orange-400 to-red-500 text-white',
                                'resolved' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                                'closed' => 'bg-white/20 text-white',
                            ];
                            $priorityColors = [
                                'low' => 'bg-white/20 text-white',
                                'medium' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                'high' => 'bg-gradient-to-r from-orange-400 to-amber-500 text-white',
                                'urgent' => 'bg-gradient-to-r from-red-500 to-rose-600 text-white',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow {{ $statusColors[$ticket->status] ?? 'bg-white/20 text-white' }}">
                            {{ $ticket->status_label }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow {{ $priorityColors[$ticket->priority] ?? 'bg-white/20 text-white' }}">
                            {{ $ticket->priority_label }}
                        </span>
                        <span class="text-sm text-purple-100 bg-white/10 px-2 py-0.5 rounded">{{ $ticket->category_label }}</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white mb-2">{{ $ticket->subject }}</h1>
                    <p class="text-purple-100 text-sm">
                        สร้างเมื่อ {{ $ticket->created_at->format('d/m/Y H:i') }}
                        @if($ticket->order)
                            &bull; คำสั่งซื้อ #{{ $ticket->order->order_number }}
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if($ticket->isOpen())
                        <form action="{{ route('customer.support.close', $ticket) }}" method="POST" onsubmit="return confirm('ต้องการปิด Ticket นี้หรือไม่?')">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 text-white bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all font-semibold">
                                ปิด Ticket
                            </button>
                        </form>
                    @else
                        <form action="{{ route('customer.support.reopen', $ticket) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 text-white bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all font-semibold">
                                เปิด Ticket ใหม่
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Conversation Thread --}}
    <div class="space-y-4">
        {{-- Original Message --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-lg">{{ strtoupper(substr($ticket->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $ticket->name }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $ticket->message }}</div>

                        {{-- Attachments --}}
                        @if($ticket->attachments && count($ticket->attachments) > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($ticket->attachments as $attachment)
                            <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                               class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                {{ $attachment['name'] }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Replies --}}
        @foreach($ticket->publicReplies as $reply)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 {{ $reply->isFromStaff() ? 'border-l-4 border-l-green-500' : '' }}">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        @if($reply->isFromStaff())
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        @else
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-lg">{{ strtoupper(substr($reply->user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $reply->user->name ?? 'Unknown' }}</span>
                            @if($reply->isFromStaff())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow">
                                    ทีมสนับสนุน
                                </span>
                            @endif
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $reply->message }}</div>

                        {{-- Attachments --}}
                        @if($reply->attachments && count($reply->attachments) > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($reply->attachments as $attachment)
                            <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                               class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                {{ $attachment['name'] }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Reply Form --}}
    @if($ticket->isOpen())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </div>
                ตอบกลับ
            </h3>
            <form action="{{ route('customer.support.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <textarea name="message" rows="4"
                                  class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 transition-all @error('message') border-red-500 @enderror"
                                  placeholder="พิมพ์ข้อความของคุณ...">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label for="reply_attachments" class="inline-flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 cursor-pointer transition-colors">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                แนบไฟล์
                                <input id="reply_attachments" name="attachments[]" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip">
                            </label>
                            <span id="reply-file-count" class="ml-2 text-sm text-gray-500 dark:text-gray-400"></span>
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white rounded-xl hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 font-semibold shadow-lg transition-all">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                ส่งข้อความ
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center mx-auto mb-4">
            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ticket ปิดแล้ว</h3>
        <p class="mt-2 text-gray-500 dark:text-gray-400">หากต้องการสอบถามเพิ่มเติม กดปุ่ม "เปิด Ticket ใหม่" ด้านบน</p>
    </div>
    @endif

    {{-- Back Link --}}
    <div class="text-center">
        <a href="{{ route('customer.support.index') }}" class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium transition-colors">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปรายการ Ticket
        </a>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('reply_attachments')?.addEventListener('change', function(e) {
    const count = e.target.files.length;
    document.getElementById('reply-file-count').textContent = count > 0 ? `${count} ไฟล์` : '';
});
</script>
@endpush
@endsection
