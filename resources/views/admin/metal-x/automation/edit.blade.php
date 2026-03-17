@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขตาราง: ' . $schedule->action_label)
@section('page-title', 'แก้ไขตารางอัตโนมัติ')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-purple-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.metal-x.automation.index') }}" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold">แก้ไข: {{ $schedule->action_label }}</h2>
            <p class="text-purple-200 text-sm">
                {{ $schedule->video ? $schedule->video->title : 'ทุกวิดีโอ (Global)' }}
                — รันแล้ว {{ number_format($schedule->run_count) }} ครั้ง
            </p>
        </div>
    </div>
</div>

<form action="{{ route('admin.metal-x.automation.update', $schedule) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="action_type" value="{{ $schedule->action_type }}">
    <input type="hidden" name="video_id" value="{{ $schedule->video_id }}">

    <!-- Basic Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าพื้นฐาน</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Enable/Disable -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_enabled" value="0">
                    <input type="checkbox" name="is_enabled" value="1" {{ $schedule->is_enabled ? 'checked' : '' }}
                        class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">เปิดใช้งาน</span>
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เปิด/ปิดการทำงานของตารางนี้</p>
            </div>

            <!-- Frequency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความถี่</label>
                <select name="frequency_minutes" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @foreach(\App\Models\MetalXAutomationSchedule::FREQUENCY_PRESETS as $minutes => $label)
                        <option value="{{ $minutes }}" {{ $schedule->frequency_minutes === $minutes ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Max Actions Per Run -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนต่อรอบ (สูงสุด)</label>
                <input type="number" name="max_actions_per_run" value="{{ $schedule->max_actions_per_run }}" min="1" max="100"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนคลิปที่จะดำเนินการสูงสุดในแต่ละรอบ</p>
            </div>
        </div>

        @if($schedule->action_type === 'promo_comment')
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">ตั้งค่าเฉพาะ: โพสเรียกยอด</h4>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="require_approval" value="0">
                <input type="checkbox" name="require_approval" value="1" {{ $schedule->getSetting('require_approval', true) ? 'checked' : '' }}
                    class="w-5 h-5 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">ต้องอนุมัติก่อนโพส (ถ้าปิด = โพสอัตโนมัติทันที)</span>
            </label>
        </div>
        @endif

        @if($schedule->action_type === 'sync_comments')
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">ตั้งค่าเฉพาะ: ซิงค์คอมเม้นต์</h4>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนคอมเม้นต์ต่อวิดีโอ</label>
                <input type="number" name="max_comments" value="{{ $schedule->getSetting('max_comments', 50) }}" min="10" max="500"
                    class="w-48 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            </div>
        </div>
        @endif
    </div>

    <!-- Video Selection (only for Global schedules) -->
    @if(!$schedule->video_id)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6" x-data="videoSelector()">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เลือกวิดีโอที่ใช้งาน</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ติ๊กเอาออกคลิปที่ไม่ต้องการ — ค่าเริ่มต้นคือเปิดทั้งหมด</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    เปิดใช้: <strong class="text-green-600" x-text="enabledCount">0</strong> /
                    ทั้งหมด: <strong x-text="totalCount">0</strong>
                </span>
                <button type="button" @click="toggleAll()" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    <span x-text="allEnabled ? 'ยกเลิกทั้งหมด' : 'เลือกทั้งหมด'"></span>
                </button>
            </div>
        </div>

        <!-- Filter -->
        <div class="mb-4 flex flex-wrap gap-3">
            <input type="text" x-model="search" placeholder="ค้นหาวิดีโอ..."
                class="flex-1 min-w-[200px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <select x-model="filterChannel" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกช่อง</option>
                @foreach($channels as $ch)
                    <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Video List -->
        <div class="max-h-[500px] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-12">ใช้</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วิดีโอ</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-28">ช่อง</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-20">ยอดวิว</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($videos as $video)
                    <tr x-show="matchesFilter('{{ addslashes($video->title) }}', '{{ $video->metal_x_channel_id }}')"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-2">
                            <input type="checkbox"
                                name="excluded_video_ids[]"
                                value="{{ $video->id }}"
                                {{ in_array($video->id, $excludedVideoIds) ? 'checked' : '' }}
                                class="exclude-checkbox w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                @change="updateCount()">
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-2">
                                @if($video->thumbnail_url)
                                    <img src="{{ $video->thumbnail_url }}" alt="" class="w-16 h-9 object-cover rounded flex-shrink-0">
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white truncate">{{ Str::limit($video->title, 60) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $video->youtube_id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                            {{ $video->metalXChannel->name ?? '-' }}
                        </td>
                        <td class="px-4 py-2 text-right text-xs text-gray-600 dark:text-gray-400">
                            {{ number_format($video->view_count ?? 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-red-500 dark:text-red-400 mt-2">
            * ติ๊กถูก = <strong>ยกเว้น</strong>คลิปนี้ (ไม่ดำเนินการ)
        </p>
    </div>
    @endif

    <!-- Submit -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.metal-x.automation.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
            ยกเลิก
        </a>
        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 text-sm font-semibold shadow-lg">
            บันทึกการตั้งค่า
        </button>
    </div>
</form>

@push('scripts')
<script>
function videoSelector() {
    return {
        search: '',
        filterChannel: '',
        totalCount: {{ $videos->count() }},
        enabledCount: {{ $videos->count() - count($excludedVideoIds) }},
        allEnabled: {{ count($excludedVideoIds) === 0 ? 'true' : 'false' }},

        matchesFilter(title, channelId) {
            const searchMatch = !this.search || title.toLowerCase().includes(this.search.toLowerCase());
            const channelMatch = !this.filterChannel || channelId == this.filterChannel;
            return searchMatch && channelMatch;
        },

        toggleAll() {
            const checkboxes = document.querySelectorAll('.exclude-checkbox');
            const shouldExclude = this.allEnabled; // if all enabled, check all (=exclude all)
            checkboxes.forEach(cb => cb.checked = shouldExclude);
            this.updateCount();
        },

        updateCount() {
            const checkboxes = document.querySelectorAll('.exclude-checkbox');
            const excluded = Array.from(checkboxes).filter(cb => cb.checked).length;
            this.enabledCount = this.totalCount - excluded;
            this.allEnabled = excluded === 0;
        }
    };
}
</script>
@endpush
@endsection
