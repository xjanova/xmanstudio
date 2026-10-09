<form method="post" action="{{ $action }}" class="grid gap-3 md:grid-cols-2" onsubmit="this.querySelector('button').disabled = true">
    @csrf
    <label class="{{ $label }} md:col-span-2">ข้อความ (ไม่เกิน 300 ตัวอักษร)
        <input name="message" required maxlength="300" value="{{ $a?->message ?? old('message') }}" placeholder="เปิดให้ลองเล่นเกมใหม่แล้ว!" class="{{ $input }} mt-1">
    </label>
    <label class="{{ $label }}">ลิงก์ (https://… หรือ /play/… ไม่บังคับ)
        <input name="link_url" maxlength="500" value="{{ $a?->link_url ?? old('link_url') }}" class="{{ $input }} mt-1">
    </label>
    <label class="{{ $label }}">ข้อความบนปุ่มลิงก์
        <input name="link_label" maxlength="60" value="{{ $a?->link_label ?? old('link_label') }}" placeholder="เล่นเลย" class="{{ $input }} mt-1">
    </label>
    <label class="{{ $label }}">ประเภท
        <select name="tone" class="{{ $input }} mt-1">
            @foreach(\App\Models\GamesHubAnnouncement::TONES as $k => $v)<option value="{{ $k }}" @selected(($a?->tone ?? 'info') === $k)>{{ $v }}</option>@endforeach
        </select>
    </label>
    <label class="flex gap-2 items-center text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="active" value="1" @checked($a?->active ?? true)> เปิดใช้</label>
    <label class="{{ $label }}">เริ่มแสดง (เวลาไทย)
        <input type="datetime-local" name="starts_at" value="{{ $a?->starts_at?->timezone('Asia/Bangkok')->format('Y-m-d\TH:i') }}" class="{{ $input }} mt-1">
    </label>
    <label class="{{ $label }}">หยุดแสดง (เวลาไทย)
        <input type="datetime-local" name="ends_at" value="{{ $a?->ends_at?->timezone('Asia/Bangkok')->format('Y-m-d\TH:i') }}" class="{{ $input }} mt-1">
    </label>
    <div><button class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">{{ $a ? 'บันทึกประกาศ' : 'เพิ่มประกาศ' }}</button></div>
</form>
