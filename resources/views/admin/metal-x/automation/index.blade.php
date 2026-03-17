@extends($adminLayout ?? 'layouts.admin')

@section('title', 'YouTube Automation')
@section('page-title', 'ระบบอัตโนมัติจัดการ YouTube')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-purple-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">YouTube Automation Manager</h2>
            <p class="text-purple-200 text-sm">จัดการระบบอัตโนมัติ: ตอบคอมเม้นต์, กดไลค์, โปรโมท, ตรวจสอบ — ตั้งความถี่ได้ตามต้องการ</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.metal-x.automation.logs') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Logs
            </a>
            <a href="{{ route('admin.metal-x.automation.promo') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Promo
            </a>
            <a href="{{ route('admin.metal-x.engagement.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Engagement
            </a>
            <form action="{{ route('admin.metal-x.automation.quick-setup') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 rounded-lg flex items-center text-sm font-semibold" onclick="return confirm('ตั้งค่าด่วน: สร้างตารางอัตโนมัติทั้ง 5 ประเภท (ซิงค์/ตอบ/ไลค์/ตรวจสอบ/โปรโมท) ทุก 15 นาที สำหรับทุกวิดีโอทุกช่อง?')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Quick Setup
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ตารางที่เปิดใช้</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['active_schedules'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ตารางทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_schedules'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ทำงานวันนี้</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['actions_today'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">ล้มเหลววันนี้</p>
        <p class="text-2xl font-bold text-red-600">{{ $stats['failures_today'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">โปรโมทวันนี้</p>
        <p class="text-2xl font-bold text-purple-600">{{ $stats['promo_posted_today'] }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">แบบร่างรออนุมัติ</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['promo_drafts'] }}</p>
    </div>
</div>

<!-- Add New Schedule -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เพิ่มตารางอัตโนมัติใหม่</h3>
    <form action="{{ route('admin.metal-x.automation.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <select name="action_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @foreach(\App\Models\MetalXAutomationSchedule::ACTION_TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วิดีโอ (ว่าง = ทุกวิดีโอ)</label>
                <select name="video_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <option value="">ทุกวิดีโอ (Global)</option>
                    @foreach($videos as $video)
                        <option value="{{ $video->id }}">{{ Str::limit($video->title, 40) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความถี่</label>
                <select name="frequency_minutes" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @foreach(\App\Models\MetalXAutomationSchedule::FREQUENCY_PRESETS as $minutes => $label)
                        <option value="{{ $minutes }}" {{ $minutes === 360 ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนต่อรอบ</label>
                <input type="number" name="max_actions_per_run" value="10" min="1" max="100" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 text-sm font-medium">
                    + เพิ่มตาราง
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Schedules Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตารางอัตโนมัติทั้งหมด</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วิดีโอ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ความถี่</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน/รอบ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รันล่าสุด</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รันถัดไป</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รันแล้ว</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($schedules as $schedule)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <button onclick="toggleSchedule({{ $schedule->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $schedule->is_enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}" id="toggle-{{ $schedule->id }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $schedule->is_enabled ? 'translate-x-6' : 'translate-x-1' }}" id="toggle-dot-{{ $schedule->id }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $schedule->action_type === 'auto_reply' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $schedule->action_type === 'auto_like' ? 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200' : '' }}
                                {{ $schedule->action_type === 'auto_moderate' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                {{ $schedule->action_type === 'promo_comment' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                {{ $schedule->action_type === 'sync_comments' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            ">
                                {{ $schedule->action_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $schedule->video ? Str::limit($schedule->video->title, 30) : 'ทุกวิดีโอ' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $schedule->frequency_label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $schedule->max_actions_per_run }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $schedule->last_run_at ? $schedule->last_run_at->diffForHumans() : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($schedule->is_enabled && $schedule->next_run_at)
                                <span class="{{ $schedule->next_run_at->isPast() ? 'text-orange-600 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $schedule->next_run_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($schedule->run_count) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="runNow({{ $schedule->id }})" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded hover:bg-blue-200 dark:hover:bg-blue-800" title="รันเดี๋ยวนี้">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('admin.metal-x.automation.destroy', $schedule) }}" method="POST" onsubmit="return confirm('ยืนยันการลบตารางนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded hover:bg-red-200 dark:hover:bg-red-800" title="ลบ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-lg font-medium">ยังไม่มีตารางอัตโนมัติ</p>
                            <p class="text-sm mt-1">เพิ่มตารางด้านบนเพื่อเริ่มใช้งานระบบอัตโนมัติ</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity Logs -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">กิจกรรมล่าสุด</h3>
        <a href="{{ route('admin.metal-x.automation.logs') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">ดูทั้งหมด &rarr;</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วิดีโอ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายละเอียด</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentLogs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $log->action_label }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $log->video ? Str::limit($log->video->title, 30) : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $log->status === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $log->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                {{ $log->status === 'skipped' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            ">
                                {{ $log->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            @if($log->error_message)
                                <span class="text-red-600 dark:text-red-400">{{ Str::limit($log->error_message, 50) }}</span>
                            @elseif($log->details)
                                {{ json_encode($log->details, JSON_UNESCAPED_UNICODE) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มีกิจกรรม
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Auto Reply Progress Modal -->
<div id="replyProgressModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 border border-gray-200 dark:border-gray-700 p-6">
        <div class="text-center mb-4">
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="replyProgressTitle">กำลังตอบคอมเม้นต์...</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="replyProgressMessage"></p>
        </div>
        <div class="space-y-3">
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600 dark:text-gray-400">คอมเม้นต์ที่ต้องตอบ</span>
                    <span class="font-semibold text-gray-900 dark:text-white" id="replyTotal">0</span>
                </div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600 dark:text-gray-400">ส่งไปประมวลผล</span>
                    <span class="font-semibold text-green-600" id="replyDispatched">0</span>
                </div>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full transition-all duration-500" id="replyProgressBar" style="width: 0%"></div>
            </div>
        </div>
        <button type="button" onclick="closeReplyProgress()" class="mt-4 w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm hidden" id="replyCloseBtn">ปิด</button>
    </div>
</div>

@push('scripts')
<script>
function toggleSchedule(id) {
    fetch(`{{ url('admin/metal-x/automation/schedules') }}/${id}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById(`toggle-${id}`);
            const dot = document.getElementById(`toggle-dot-${id}`);
            if (data.is_enabled) {
                btn.classList.remove('bg-gray-300', 'dark:bg-gray-600');
                btn.classList.add('bg-green-500');
                dot.classList.remove('translate-x-1');
                dot.classList.add('translate-x-6');
            } else {
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-gray-300', 'dark:bg-gray-600');
                dot.classList.remove('translate-x-6');
                dot.classList.add('translate-x-1');
            }
        }
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function runNow(id) {
    if (!confirm('รันตารางนี้เดี๋ยวนี้?')) return;
    fetch(`{{ url('admin/metal-x/automation/schedules') }}/${id}/run`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.progress_key) {
            // Show progress modal for auto_reply
            showReplyProgress(data);
        } else {
            alert(data.message || 'เริ่มรันแล้ว');
            location.reload();
        }
    })
    .catch(() => alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'));
}

function showReplyProgress(data) {
    const modal = document.getElementById('replyProgressModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.getElementById('replyTotal').textContent = data.total_unreplied || 0;
    document.getElementById('replyDispatched').textContent = data.dispatched || 0;
    document.getElementById('replyProgressMessage').textContent = data.message || '';

    if (data.dispatched > 0) {
        document.getElementById('replyProgressBar').style.width = '100%';
        document.getElementById('replyProgressTitle').textContent = 'ส่งไปประมวลผลแล้ว!';
        document.getElementById('replyCloseBtn').classList.remove('hidden');
    } else {
        document.getElementById('replyProgressBar').style.width = '100%';
        document.getElementById('replyProgressTitle').textContent = 'ไม่มีคอมเม้นต์ที่ต้องตอบ';
        document.getElementById('replyCloseBtn').classList.remove('hidden');
    }
}

function closeReplyProgress() {
    document.getElementById('replyProgressModal').classList.add('hidden');
    document.getElementById('replyProgressModal').classList.remove('flex');
    location.reload();
}
</script>
@endpush
@endsection
