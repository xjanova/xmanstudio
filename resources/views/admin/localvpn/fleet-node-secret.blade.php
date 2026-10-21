@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - รหัสลับโหนด')
@section('page-title', 'LocalVPN - รหัสลับโหนด ' . $node->code)

@section('content')
@include('admin.localvpn._tabs')
@include('admin.localvpn._fleet_tabs')

<div class="max-w-3xl">
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800">{{ $message }}</div>

    <div class="bg-white rounded-2xl shadow-lg border border-amber-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">รหัสลับของโหนด {{ $node->code }} (#{{ $node->id }})</h3>
        <p class="text-sm text-amber-700 mb-4">แสดงครั้งเดียวเท่านั้น — ระบบเก็บแบบเข้ารหัสและจะไม่แสดงอีก ถ้าทำหายให้สร้างรหัสลับใหม่</p>

        <label class="block text-sm font-medium text-gray-700 mb-1">Secret</label>
        <div class="flex gap-2 mb-6">
            <input type="text" readonly id="node-secret" value="{{ $secret }}" class="flex-1 border-gray-300 rounded-lg font-mono text-sm bg-gray-50" onclick="this.select()">
            <button type="button" data-copy="node-secret" class="copy-btn px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">คัดลอก</button>
        </div>

        <label class="block text-sm font-medium text-gray-700 mb-1">ติดตั้งบนโหนด (รันด้วย sudo บน VPS)</label>
        <div class="space-y-2">
            <div class="flex gap-2">
                <input type="text" readonly id="install-download" value="curl -fsSL https://xman4289.com/downloads/lvnode/install.sh -o install.sh" class="flex-1 border-gray-300 rounded-lg font-mono text-xs bg-gray-50" onclick="this.select()">
                <button type="button" data-copy="install-download" class="copy-btn px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">คัดลอก</button>
            </div>
            <div class="flex gap-2">
                <input type="text" readonly id="install-command" value="{{ $command }}" class="flex-1 border-gray-300 rounded-lg font-mono text-xs bg-gray-50" onclick="this.select()">
                <button type="button" data-copy="install-command" class="copy-btn px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-900">คัดลอก</button>
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">โหนดใช้ตัวอักษรของรหัสลับนี้ตรง ๆ เป็นกุญแจ HMAC (ไม่ต้องแปลง hex) · หลังติดตั้ง โหนดจะซิงก์ทุก 10 วินาที และขึ้นสถานะออนไลน์ในหน้ารายการ</p>

        <div class="mt-6">
            <a href="{{ route('admin.localvpn.fleet.nodes') }}" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-sm">บันทึกรหัสแล้ว กลับไปหน้ารายการ</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.copy-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const field = document.getElementById(button.dataset.copy);
            const done = function () {
                const label = button.textContent;
                button.textContent = 'คัดลอกแล้ว';
                setTimeout(function () { button.textContent = label; }, 1500);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(field.value).then(done);
            } else {
                field.select();
                document.execCommand('copy');
                done();
            }
        });
    });
</script>
@endpush
