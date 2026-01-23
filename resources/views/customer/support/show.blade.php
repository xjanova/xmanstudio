@extends($customerLayout ?? 'layouts.customer')

@section('title', 'Ticket #' . $ticket->ticket_number)
@section('page-title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Ticket Header --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @php
                            $statusColors = [
                                'open' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'waiting_reply' => 'bg-orange-100 text-orange-800',
                                'resolved' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                            ];
                            $priorityColors = [
                                'low' => 'bg-gray-100 text-gray-800',
                                'medium' => 'bg-blue-100 text-blue-800',
                                'high' => 'bg-orange-100 text-orange-800',
                                'urgent' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $ticket->status_label }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityColors[$ticket->priority] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $ticket->priority_label }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $ticket->category_label }}</span>
                    </div>
                    <h1 class="text-xl font-semibold text-gray-900 mt-3">{{ $ticket->subject }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
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
                            <button type="submit" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                                ปิด Ticket
                            </button>
                        </form>
                    @else
                        <form action="{{ route('customer.support.reopen', $ticket) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-primary-700 border border-primary-300 rounded-lg hover:bg-primary-50">
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
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 font-medium">{{ strtoupper(substr($ticket->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">{{ $ticket->name }}</span>
                            <span class="text-sm text-gray-500">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 text-gray-700 whitespace-pre-wrap">{{ $ticket->message }}</div>

                        {{-- Attachments --}}
                        @if($ticket->attachments && count($ticket->attachments) > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($ticket->attachments as $attachment)
                            <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-gray-200">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="bg-white rounded-lg shadow {{ $reply->isFromStaff() ? 'border-l-4 border-green-500' : '' }}">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        @if($reply->isFromStaff())
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        @else
                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 font-medium">{{ strtoupper(substr($reply->user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">{{ $reply->user->name ?? 'Unknown' }}</span>
                            @if($reply->isFromStaff())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    ทีมสนับสนุน
                                </span>
                            @endif
                            <span class="text-sm text-gray-500">{{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 text-gray-700 whitespace-pre-wrap">{{ $reply->message }}</div>

                        {{-- Attachments --}}
                        @if($reply->attachments && count($reply->attachments) > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($reply->attachments as $attachment)
                            <a href="{{ Storage::url($attachment['path']) }}" target="_blank"
                               class="inline-flex items-center px-3 py-1.5 bg-gray-100 rounded-lg text-sm text-gray-700 hover:bg-gray-200">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">ตอบกลับ</h3>
            <form action="{{ route('customer.support.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <textarea name="message" rows="4"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('message') border-red-500 @enderror"
                                  placeholder="พิมพ์ข้อความของคุณ...">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label for="reply_attachments" class="inline-flex items-center px-3 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                แนบไฟล์
                                <input id="reply_attachments" name="attachments[]" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip">
                            </label>
                            <span id="reply-file-count" class="ml-2 text-sm text-gray-500"></span>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            ส่งข้อความ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 text-center">
        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Ticket ปิดแล้ว</h3>
        <p class="mt-2 text-gray-500">หากต้องการสอบถามเพิ่มเติม กดปุ่ม "เปิด Ticket ใหม่" ด้านบน</p>
    </div>
    @endif

    {{-- Back Link --}}
    <div class="text-center">
        <a href="{{ route('customer.support.index') }}" class="text-primary-600 hover:text-primary-700">
            &larr; กลับไปรายการ Ticket
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
