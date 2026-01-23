@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Ticket #' . $ticket->ticket_number)
@section('page-title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Content --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Ticket Header --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap items-center gap-2 mb-3">
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
            <h1 class="text-xl font-semibold text-gray-900">{{ $ticket->subject }}</h1>
            <p class="text-sm text-gray-500 mt-1">สร้างเมื่อ {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
        </div>

        {{-- Conversation Thread --}}
        <div class="space-y-4">
            {{-- Original Message --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-700 font-medium">{{ strtoupper(substr($ticket->name, 0, 1)) }}</span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">{{ $ticket->name }}</span>
                            <span class="text-sm text-gray-500">{{ $ticket->email }}</span>
                            <span class="text-sm text-gray-400">&bull; {{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-3 text-gray-700 whitespace-pre-wrap">{{ $ticket->message }}</div>

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

            {{-- Replies --}}
            @foreach($ticket->replies as $reply)
            <div class="bg-white rounded-lg shadow p-6 {{ $reply->is_internal ? 'bg-yellow-50 border-l-4 border-yellow-400' : ($reply->isFromStaff() ? 'border-l-4 border-green-500' : '') }}">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        @if($reply->isFromStaff())
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        @else
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-700 font-medium">{{ strtoupper(substr($reply->user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-medium text-gray-900">{{ $reply->user->name ?? 'Unknown' }}</span>
                            @if($reply->is_internal)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800">
                                    Internal Note
                                </span>
                            @elseif($reply->isFromStaff())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    ทีมสนับสนุน
                                </span>
                            @endif
                            <span class="text-sm text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-3 text-gray-700 whitespace-pre-wrap">{{ $reply->message }}</div>

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
            @endforeach
        </div>

        {{-- Reply Form --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">ตอบกลับ</h3>
            <form action="{{ route('admin.support.reply', $ticket) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-4">
                    <div>
                        <textarea name="message" rows="5"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="พิมพ์ข้อความตอบกลับ...">{{ old('message') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                <span class="ml-2 text-sm text-gray-700">Internal Note (ไม่แสดงให้ลูกค้า)</span>
                            </label>

                            <label for="admin_attachments" class="inline-flex items-center px-3 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                แนบไฟล์
                                <input id="admin_attachments" name="attachments[]" type="file" class="sr-only" multiple>
                            </label>
                        </div>

                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            ส่งข้อความ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Customer Info --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลลูกค้า</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-500">ชื่อ:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ $ticket->name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">อีเมล:</span>
                    <span class="ml-2 text-gray-900">{{ $ticket->email }}</span>
                </div>
                @if($ticket->user)
                <div>
                    <span class="text-gray-500">User ID:</span>
                    <span class="ml-2 text-gray-900">#{{ $ticket->user_id }}</span>
                </div>
                @endif
                @if($ticket->order)
                <div class="pt-3 border-t border-gray-200">
                    <span class="text-gray-500">คำสั่งซื้อ:</span>
                    <a href="#" class="ml-2 text-blue-600 hover:text-blue-800">#{{ $ticket->order->order_number }}</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Ticket Actions --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">จัดการ Ticket</h3>

            {{-- Status --}}
            <form action="{{ route('admin.support.update-status', $ticket) }}" method="POST" class="mb-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                <div class="flex gap-2">
                    <select name="status" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ $ticket->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                        บันทึก
                    </button>
                </div>
            </form>

            {{-- Priority --}}
            <form action="{{ route('admin.support.update-priority', $ticket) }}" method="POST" class="mb-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <div class="flex gap-2">
                    <select name="priority" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" {{ $ticket->priority === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                        บันทึก
                    </button>
                </div>
            </form>

            {{-- Assign --}}
            <form action="{{ route('admin.support.assign', $ticket) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">มอบหมายให้</label>
                <div class="flex gap-2">
                    <select name="assigned_to" class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">ยังไม่มอบหมาย</option>
                        @foreach($staff as $user)
                            <option value="{{ $user->id }}" {{ $ticket->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Timeline</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-blue-500"></div>
                    <div>
                        <p class="text-gray-900">สร้าง Ticket</p>
                        <p class="text-gray-500">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @if($ticket->responded_at)
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-green-500"></div>
                    <div>
                        <p class="text-gray-900">ตอบกลับครั้งแรก</p>
                        <p class="text-gray-500">{{ $ticket->responded_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
                @if($ticket->last_reply_at)
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-gray-500"></div>
                    <div>
                        <p class="text-gray-900">ข้อความล่าสุด</p>
                        <p class="text-gray-500">{{ $ticket->last_reply_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
                @if($ticket->closed_at)
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 mt-1.5 rounded-full bg-red-500"></div>
                    <div>
                        <p class="text-gray-900">ปิด Ticket</p>
                        <p class="text-gray-500">{{ $ticket->closed_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Back Button --}}
        <div>
            <a href="{{ route('admin.support.index') }}" class="block w-full px-4 py-2 text-center text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                &larr; กลับไปรายการ
            </a>
        </div>
    </div>
</div>
@endsection
