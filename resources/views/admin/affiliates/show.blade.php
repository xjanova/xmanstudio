@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Affiliate: ' . $affiliate->user->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.affiliates.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    {{ $affiliate->user->name }}
                    <span class="text-xs px-2 py-1 rounded-full
                        {{ $affiliate->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}
                    ">{{ $affiliate->status_label }}</span>
                </h1>
                <p class="text-sm text-gray-500">{{ $affiliate->user->email }} | Code: {{ $affiliate->referral_code }}</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            @if($affiliate->status === 'active')
                <form action="{{ route('admin.affiliates.suspend', $affiliate) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition" onclick="return confirm('ระงับ Affiliate นี้?')">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        บล๊อก
                    </button>
                </form>
            @else
                <form action="{{ route('admin.affiliates.activate', $affiliate) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        เปิดใช้งาน
                    </button>
                </form>
            @endif
            <form action="{{ route('admin.affiliates.destroy', $affiliate) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition" onclick="return confirm('ลบ Affiliate นี้? ลูกทีมจะถูกย้ายไปอยู่กับ Upline')">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    ลบ
                </button>
            </form>
        </div>
    </div>

    <!-- Upline Info -->
    @if($affiliate->parent)
    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl p-4 border border-indigo-200 dark:border-indigo-700">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                {{ strtoupper(substr($affiliate->parent->user->name ?? 'N', 0, 1)) }}
            </div>
            <div class="flex-1">
                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Upline</span>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $affiliate->parent->user->name ?? 'N/A' }}
                    <code class="text-xs ml-1 px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-800 rounded text-indigo-600 dark:text-indigo-300">{{ $affiliate->parent->referral_code }}</code>
                </p>
            </div>
            <a href="{{ route('admin.affiliates.show', $affiliate->parent) }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">ดูรายละเอียด &rarr;</a>
        </div>
    </div>
    @endif

    <!-- Stats + Edit Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Stats --}}
        <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">รายได้รวม</p>
                <p class="text-xl font-bold text-green-600">฿{{ number_format($affiliate->total_earned) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">จ่ายแล้ว</p>
                <p class="text-xl font-bold text-blue-600">฿{{ number_format($affiliate->total_paid) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">รอจ่าย</p>
                <p class="text-xl font-bold text-yellow-600">฿{{ number_format($affiliate->total_pending) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">คลิก / ขาย</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $affiliate->total_clicks }} / {{ $affiliate->total_conversions }}</p>
                <p class="text-xs text-gray-400">{{ $affiliate->conversion_rate }}% conv.</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">ลูกทีมตรง</p>
                <p class="text-xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
            </div>
        </div>

        {{-- Edit Form + Move --}}
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า</h3>
                <form action="{{ route('admin.affiliates.update', $affiliate) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">สถานะ</label>
                        <select name="status" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="active" {{ $affiliate->status === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                            <option value="suspended" {{ $affiliate->status === 'suspended' ? 'selected' : '' }}>ระงับ</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">อัตราคอมมิชชั่น (%)</label>
                        <input type="number" name="commission_rate" value="{{ $affiliate->commission_rate }}" min="1" max="50" step="0.5"
                               class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">หมายเหตุ</label>
                        <textarea name="notes" rows="2" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">{{ $affiliate->notes }}</textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-sm transition">บันทึก</button>
                </form>
            </div>

            {{-- Move Team --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ย้ายสายงาน</h3>
                <form action="{{ route('admin.affiliates.move', $affiliate) }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">เลือก Upline ใหม่</label>
                        <select name="new_parent_id" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="">ไม่มี Upline (ระดับบนสุด)</option>
                            @foreach($moveOptions as $opt)
                                <option value="{{ $opt->id }}" {{ $affiliate->parent_id == $opt->id ? 'selected' : '' }}>
                                    {{ $opt->user->name ?? 'N/A' }} ({{ $opt->referral_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition" onclick="return confirm('ย้ายสายงาน?')">ย้ายทีม</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Direct Downline -->
    @if($affiliate->children->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">ลูกทีมตรง ({{ $affiliate->children->count() }} คน)</h3>
            <a href="{{ route('admin.affiliates.tree') }}" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">ดูผังสายงาน</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">โค้ด</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายได้</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ลูกทีม</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่สมัคร</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($affiliate->children as $child)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white {{ $child->status === 'active' ? 'bg-green-500' : 'bg-red-400' }}">
                                    {{ strtoupper(substr($child->user->name ?? 'N', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $child->user->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $child->user->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ $child->referral_code }}</code></td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $child->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}
                            ">{{ $child->status_label }}</span>
                        </td>
                        <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">฿{{ number_format($child->total_earned) }}</td>
                        <td class="px-6 py-3 text-sm text-center text-gray-600 dark:text-gray-400">{{ $child->children_count ?? $child->children->count() }}</td>
                        <td class="px-6 py-3 text-sm text-gray-500">{{ $child->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-3">
                            <a href="{{ route('admin.affiliates.show', $child) }}" class="text-xs text-blue-600 hover:text-blue-700">ดู</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Commissions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">ประวัติคอมมิชชั่น</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายละเอียด</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ซื้อ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $typeColors = [
                                        'tping' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        'order' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'rental_payment' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                        'autotradex' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
                                    ];
                                    $typeColor = $typeColors[$c->source_type ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColor }}">
                                    {{ $c->source_label }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $c->source_description ?: ($c->order->order_number ?? '-') }}
                            </td>
                            <td class="px-6 py-3 text-sm">{{ $c->referredUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right">฿{{ number_format($c->order_amount) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">+฿{{ number_format($c->commission_amount) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $c->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $c->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $c->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                ">{{ $c->status_label }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if($c->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.affiliates.commission.approve', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg" onclick="return confirm('จ่ายค่าคอมมิชชั่น ฿{{ number_format($c->commission_amount) }} เข้า Wallet?')">จ่าย</button>
                                        </form>
                                        <form action="{{ route('admin.affiliates.commission.reject', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg" onclick="return confirm('ปฏิเสธคอมมิชชั่นนี้?')">ปฏิเสธ</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ $c->paid_at?->format('d/m/y') ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">ไม่มีข้อมูลคอมมิชชั่น</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $commissions->links() }}
        </div>
    </div>
</div>
@endsection
