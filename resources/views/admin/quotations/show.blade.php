@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ใบเสนอราคา #' . $quotation->quote_number)
@section('page-title', 'ใบเสนอราคา #' . $quotation->quote_number)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.quotations.list') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                กลับไปรายการ
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">#{{ $quotation->quote_number }}</h1>
            <p class="text-sm text-gray-500">สร้างเมื่อ {{ $quotation->created_at->format('d/m/Y H:i') }} &middot; ใช้ได้ถึง {{ $quotation->valid_until?->format('d/m/Y') ?? '-' }}</p>
        </div>
        <div class="flex items-center gap-2">
            @php
                $statusBadge = ['draft'=>'bg-gray-200 text-gray-800','sent'=>'bg-blue-100 text-blue-800','viewed'=>'bg-purple-100 text-purple-800','accepted'=>'bg-green-100 text-green-800','paid'=>'bg-emerald-100 text-emerald-800','expired'=>'bg-red-100 text-red-800','rejected'=>'bg-red-100 text-red-800'];
                $statusLabel = ['draft'=>'ร่าง','sent'=>'ส่งแล้ว','viewed'=>'เปิดดูแล้ว','accepted'=>'ยอมรับ','paid'=>'ชำระแล้ว','expired'=>'หมดอายุ','rejected'=>'ปฏิเสธ'];
            @endphp
            <span class="px-3 py-1.5 text-sm font-semibold rounded-full {{ $statusBadge[$quotation->status] ?? 'bg-gray-200 text-gray-800' }}">
                {{ $statusLabel[$quotation->status] ?? $quotation->status }}
            </span>
            @if($quotation->action_type === 'order')
                <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">สั่งซื้อ</span>
            @else
                <span class="px-3 py-1.5 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">ใบเสนอราคา</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Service & Options -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">{{ $quotation->service_name }}</h2>
                    <p class="text-indigo-200 text-sm">{{ $quotation->service_type }}</p>
                </div>
                <div class="p-6">
                    @if($quotation->service_options)
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ตัวเลือกบริการ</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($quotation->service_options as $opt)
                                <span class="px-3 py-1 text-sm bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 rounded-lg">{{ $opt['name'] ?? $opt }} — ฿{{ number_format($opt['price'] ?? 0) }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($quotation->additional_options)
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ตัวเลือกเพิ่มเติม</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($quotation->additional_options as $opt)
                                <span class="px-3 py-1 text-sm bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg">{{ $opt['name'] ?? $opt }} — ฿{{ number_format($opt['price'] ?? 0) }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @if($quotation->project_description)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">รายละเอียดโครงการ</h4>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quotation->project_description }}</div>
                    </div>
                    @endif
                </div>

                <!-- Pricing -->
                <div class="border-t border-gray-100 dark:border-gray-700 p-6">
                    <table class="w-full text-sm">
                        <tr><td class="py-1 text-gray-500">ยอดรวมบริการ</td><td class="py-1 text-right font-medium text-gray-900 dark:text-white">฿{{ number_format($quotation->subtotal, 2) }}</td></tr>
                        @if($quotation->discount > 0)
                        <tr><td class="py-1 text-green-600">ส่วนลด {{ $quotation->discount_percent ? $quotation->discount_percent . '%' : '' }}</td><td class="py-1 text-right font-medium text-green-600">-฿{{ number_format($quotation->discount, 2) }}</td></tr>
                        @endif
                        @if($quotation->rush_fee > 0)
                        <tr><td class="py-1 text-orange-600">ค่าเร่งด่วน</td><td class="py-1 text-right font-medium text-orange-600">+฿{{ number_format($quotation->rush_fee, 2) }}</td></tr>
                        @endif
                        @if($quotation->vat > 0)
                        <tr><td class="py-1 text-gray-500">VAT 7%</td><td class="py-1 text-right font-medium text-gray-900 dark:text-white">฿{{ number_format($quotation->vat, 2) }}</td></tr>
                        @endif
                        <tr class="border-t border-gray-200 dark:border-gray-600"><td class="py-2 font-bold text-gray-900 dark:text-white">ยอดรวมทั้งหมด</td><td class="py-2 text-right font-bold text-xl text-indigo-600">฿{{ number_format($quotation->grand_total, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- Linked Project -->
            @if($project)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-green-200 dark:border-green-700 p-6">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">โครงการที่เชื่อมต่อ</h3>
                </div>
                <a href="{{ route('admin.projects.show', $project) }}" class="block p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl hover:from-blue-100 hover:to-indigo-100 transition border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono text-sm text-blue-600 font-bold bg-blue-100 dark:bg-blue-800 px-2 py-0.5 rounded">{{ $project->project_number }}</span>
                        @php $statusColor = \App\Models\ProjectOrder::STATUS_COLORS[$project->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                            {{ \App\Models\ProjectOrder::STATUS_LABELS[$project->status] ?? $project->status }}
                        </span>
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ $project->project_name }}</p>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $project->progress_percent }}%"></div>
                        </div>
                        <span class="text-sm font-bold text-blue-600">{{ $project->progress_percent }}%</span>
                    </div>
                    @if($project->members->count())
                    <p class="text-xs text-gray-500 mt-2">ทีมงาน: {{ $project->members->count() }} คน</p>
                    @endif
                </a>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        ดูโครงการ
                    </a>
                    <a href="{{ route('admin.projects.edit', $project) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        แก้ไข / มอบหมายทีม
                    </a>
                </div>
            </div>
            @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">โครงการ</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">ยังไม่มีโครงการที่เชื่อมต่อกับใบเสนอราคานี้</p>
                @if(in_array($quotation->status, ['accepted', 'paid']))
                <p class="text-amber-600 text-xs mb-3">* ลูกค้ายอมรับแล้ว ควรสร้างโครงการเพื่อเริ่มดำเนินการ</p>
                @endif
                <a href="{{ route('admin.projects.from-quotation', $quotation) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    สร้างโครงการ + รันหมายเลขงาน
                </a>
            </div>
            @endif

            <!-- Update Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">อัปเดตสถานะ</h3>
                <form action="{{ route('admin.quotations.update-status', $quotation) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex gap-3">
                        <select name="status" class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                            <option value="draft" {{ $quotation->status === 'draft' ? 'selected' : '' }}>ร่าง</option>
                            <option value="sent" {{ $quotation->status === 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                            <option value="viewed" {{ $quotation->status === 'viewed' ? 'selected' : '' }}>เปิดดูแล้ว</option>
                            <option value="accepted" {{ $quotation->status === 'accepted' ? 'selected' : '' }}>ยอมรับ</option>
                            <option value="paid" {{ $quotation->status === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                            <option value="rejected" {{ $quotation->status === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                            <option value="expired" {{ $quotation->status === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">อัปเดต</button>
                    </div>
                    <input type="text" name="admin_notes" placeholder="หมายเหตุ (ไม่บังคับ)"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                </form>
            </div>

            <!-- Notes -->
            @if($quotation->admin_notes || $quotation->customer_notes)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">หมายเหตุ</h3>
                @if($quotation->customer_notes)
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-1">จากลูกค้า</p>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quotation->customer_notes }}</div>
                </div>
                @endif
                @if($quotation->admin_notes)
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-1">จากแอดมิน</p>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $quotation->admin_notes }}</div>
                </div>
                @endif
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลลูกค้า</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ชื่อ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->customer_name }}</dd>
                    </div>
                    @if($quotation->customer_company)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">บริษัท</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->customer_company }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">อีเมล</dt>
                        <dd class="font-medium text-gray-900 dark:text-white break-all">{{ $quotation->customer_email }}</dd>
                    </div>
                    @if($quotation->customer_phone)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">โทรศัพท์</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->customer_phone }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ไทม์ไลน์</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">ความเร่งด่วน</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            @switch($quotation->timeline)
                                @case('urgent') <span class="text-red-600">เร่งด่วน</span> @break
                                @case('normal') ปกติ @break
                                @case('flexible') ยืดหยุ่น @break
                                @default {{ $quotation->timeline }}
                            @endswitch
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">สร้าง</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->created_at->format('d/m/Y H:i') }}</dd></div>
                    @if($quotation->sent_at)<div><dt class="text-gray-500">ส่ง</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->sent_at->format('d/m/Y H:i') }}</dd></div>@endif
                    @if($quotation->viewed_at)<div><dt class="text-gray-500">เปิดดู</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->viewed_at->format('d/m/Y H:i') }}</dd></div>@endif
                    @if($quotation->accepted_at)<div><dt class="text-gray-500">ยอมรับ</dt><dd class="font-medium text-green-600">{{ $quotation->accepted_at->format('d/m/Y H:i') }}</dd></div>@endif
                    @if($quotation->paid_at)<div><dt class="text-gray-500">ชำระเงิน</dt><dd class="font-medium text-emerald-600">{{ $quotation->paid_at->format('d/m/Y H:i') }}</dd></div>@endif
                </dl>
            </div>

            <!-- Payment Info -->
            @if($quotation->payment_method)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การชำระเงิน</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">ช่องทาง</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            @switch($quotation->payment_method)
                                @case('bank_transfer') โอนเงิน @break
                                @case('installment') ผ่อนชำระ @break
                                @default {{ $quotation->payment_method }}
                            @endswitch
                        </dd>
                    </div>
                    @if($quotation->payment_status)
                    <div>
                        <dt class="text-gray-500">สถานะชำระ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $quotation->payment_status }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
