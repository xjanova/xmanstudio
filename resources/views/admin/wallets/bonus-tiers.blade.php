@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'โบนัสเติมเงิน')

@section('content')
<!-- Premium Gradient Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-purple-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">โบนัสเติมเงิน</h1>
            </div>
            <p class="text-purple-100 text-lg">ตั้งค่าโบนัสเมื่อเติมเงินตามยอด</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="inline-flex items-center px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all duration-200 border border-white/25">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <!-- Existing Tiers -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">รายการโบนัส</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ช่วงยอดเติมเงิน</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">โบนัส</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($tiers as $tier)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $tier->range_label }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-lg shadow-emerald-500/30">
                                    {{ $tier->bonus_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($tier->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></span>
                                    เปิดใช้งาน
                                </span>
                                @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-2"></span>
                                    ปิดใช้งาน
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('editModal{{ $tier->id }}').classList.remove('hidden')" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.wallets.bonus-tiers.destroy', $tier) }}" method="POST" class="inline" onsubmit="return confirm('ต้องการลบ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 hover:bg-rose-200 dark:hover:bg-rose-900/50 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div id="editModal{{ $tier->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto">
                            <div class="flex items-center justify-center min-h-screen px-4">
                                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="document.getElementById('editModal{{ $tier->id }}').classList.add('hidden')"></div>
                                <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                                    <form action="{{ route('admin.wallets.bonus-tiers.update', $tier) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex items-center justify-between mb-6">
                                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">แก้ไขโบนัส</h5>
                                            <button type="button" onclick="document.getElementById('editModal{{ $tier->id }}').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดขั้นต่ำ <span class="text-rose-500">*</span></label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                                                    <input type="number" name="min_amount" value="{{ $tier->min_amount }}" required min="0"
                                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดสูงสุด</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                                                    <input type="number" name="max_amount" value="{{ $tier->max_amount }}" min="0" placeholder="ไม่จำกัด"
                                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท <span class="text-rose-500">*</span></label>
                                                <select name="bonus_type" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                                    <option value="percentage" {{ $tier->bonus_type === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์</option>
                                                    <option value="fixed" {{ $tier->bonus_type === 'fixed' ? 'selected' : '' }}>จำนวนเงิน</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าโบนัส <span class="text-rose-500">*</span></label>
                                                <input type="number" name="bonus_value" value="{{ $tier->bonus_value }}" required min="0" step="0.01"
                                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                            </div>
                                        </div>
                                        <div class="mb-6">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" name="is_active" value="1" {{ $tier->is_active ? 'checked' : '' }}
                                                       class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500 focus:ring-offset-0">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                                            </label>
                                        </div>
                                        <div class="flex gap-3">
                                            <button type="button" onclick="document.getElementById('editModal{{ $tier->id }}').classList.add('hidden')" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                                                ยกเลิก
                                            </button>
                                            <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium rounded-xl hover:from-purple-700 hover:to-indigo-700 shadow-lg shadow-purple-500/30 transition-all duration-200">
                                                บันทึก
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-900/30 mb-4">
                                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีโบนัสที่ตั้งค่า</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-8">
        <!-- Add New Tier -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">เพิ่มโบนัสใหม่</h5>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.wallets.bonus-tiers.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดขั้นต่ำ <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="min_amount" required min="0" placeholder="0"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยอดสูงสุด</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">฿</span>
                            <input type="number" name="max_amount" min="0" placeholder="ไม่จำกัด"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทโบนัส <span class="text-rose-500">*</span></label>
                        <select name="bonus_type" class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                            <option value="percentage">เปอร์เซ็นต์ (%)</option>
                            <option value="fixed">จำนวนเงิน (฿)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าโบนัส <span class="text-rose-500">*</span></label>
                        <input type="number" name="bonus_value" required min="0" step="0.01" placeholder="เช่น 5 หรือ 100"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                    </div>
                    <div class="mb-6">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500 focus:ring-offset-0">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        </label>
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-purple-500/30 hover:shadow-purple-500/40 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        เพิ่มโบนัส
                    </button>
                </form>
            </div>
        </div>

        <!-- Example -->
        <div class="rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-100 dark:border-amber-800 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <h6 class="text-base font-semibold text-amber-800 dark:text-amber-300">ตัวอย่างโบนัส</h6>
            </div>
            <ul class="space-y-3">
                <li class="flex items-center gap-3 text-sm text-amber-800 dark:text-amber-300">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>เติม 500-999 บาท รับโบนัส 5%</span>
                </li>
                <li class="flex items-center gap-3 text-sm text-amber-800 dark:text-amber-300">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>เติม 1,000-2,999 บาท รับโบนัส 10%</span>
                </li>
                <li class="flex items-center gap-3 text-sm text-amber-800 dark:text-amber-300">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>เติม 3,000+ บาท รับโบนัส 15%</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
