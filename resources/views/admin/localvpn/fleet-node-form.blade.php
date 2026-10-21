@extends($adminLayout ?? 'layouts.admin')

@php $editing = $node->exists; @endphp

@section('title', $editing ? 'LocalVPN - แก้ไขโหนด' : 'LocalVPN - เพิ่มโหนด')
@section('page-title', $editing ? 'LocalVPN - แก้ไขโหนด ' . $node->code : 'LocalVPN - เพิ่มโหนด')

@section('content')
@include('admin.localvpn._tabs')
@include('admin.localvpn._fleet_tabs')

@php
    $input = 'w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500';
    $roles = old('roles', (array) $node->roles);
    $originalAddress = $server?->address ?? '';
@endphp

<div class="max-w-4xl">
    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
            <p class="font-semibold mb-1">บันทึกไม่ได้ — ตรวจสอบช่องที่มีปัญหา</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" id="node-form"
          action="{{ $editing ? route('admin.localvpn.fleet.nodes.update', $node) : route('admin.localvpn.fleet.nodes.store') }}"
          class="space-y-6">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- Identity --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลโหนด</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="name">ชื่อ</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $node->name) }}" maxlength="100" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="code">รหัสโหนด</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $node->code) }}" maxlength="32" required placeholder="th-bkk-1" class="{{ $input }} font-mono">
                    <p class="mt-1 text-xs text-gray-500">แสดงให้ผู้ใช้เห็นในแอป — a-z 0-9 และขีด</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="country">ประเทศ (ISO 2 ตัว)</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $node->country) }}" maxlength="2" required class="{{ $input }} uppercase">
                    <p class="mt-1 text-xs text-gray-500">โหนด TH ถูกเลือกก่อนสำหรับ LAN relay</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="city">เมือง</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $node->city) }}" maxlength="100" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="provider">ผู้ให้บริการ</label>
                    <input type="text" name="provider" id="provider" value="{{ old('provider', $node->provider) }}" maxlength="100" placeholder="Vultr, DigitalOcean, …" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="host">Host (IP หรือโดเมน)</label>
                    <input type="text" name="host" id="host" value="{{ old('host', $node->host) }}" maxlength="255" required placeholder="203.0.113.10" class="{{ $input }} font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="relay_port">พอร์ต Relay (UDP)</label>
                    <input type="number" name="relay_port" id="relay_port" value="{{ old('relay_port', $node->relay_port) }}" min="1" max="65535" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="wg_port">พอร์ต WireGuard (UDP)</label>
                    <input type="number" name="wg_port" id="wg_port" value="{{ old('wg_port', $node->wg_port) }}" min="1" max="65535" required class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500">โหนดรายงานพอร์ตจริงทุกครั้งที่ซิงก์และจะเขียนทับค่านี้</p>
                </div>
            </div>
        </div>

        {{-- Roles & status --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">บทบาทและสถานะ</h3>
            <div class="space-y-2 mb-4">
                @foreach(['relay' => 'relay — ส่งต่อแพ็กเก็ต LAN ที่เข้ารหัสแล้ว เมื่อเจาะ NAT ไม่ผ่าน', 'game' => 'game — WireGuard exit เฉพาะช่วง IP ของเกม (ลดปิง)', 'vpn' => 'vpn — WireGuard exit เต็มรูปแบบ (เฉพาะ Pro)'] as $role => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="roles[]" value="{{ $role }}" {{ in_array($role, $roles, true) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        {{ $label }}
                    </label>
                @endforeach
                <p class="text-xs text-gray-500">ถ้าเลือกทั้ง game และ vpn โหนดจะทำงานแบบ vpn (เต็มรูปแบบ, เฉพาะ Pro)</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="status">สถานะ</label>
                    <select name="status" id="status" class="{{ $input }}">
                        @foreach(['active' => 'ใช้งาน', 'draining' => 'ระบายผู้ใช้ (ไม่รับใหม่)', 'disabled' => 'ปิดใช้งาน'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $node->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="max_clients">ผู้ใช้ relay สูงสุด</label>
                    <input type="number" name="max_clients" id="max_clients" value="{{ old('max_clients', $node->max_clients) }}" min="1" max="1000000" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="weight">น้ำหนัก</label>
                    <input type="number" name="weight" id="weight" value="{{ old('weight', $node->weight) }}" min="1" max="10000" required class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500">มาก = รับผู้ใช้มากกว่า (ผู้ใช้ ÷ น้ำหนัก ต่ำสุดถูกเลือก)</p>
                </div>
            </div>
        </div>

        {{-- Billing --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ค่าใช้จ่ายและแพ็กเกจ</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="monthly_cost_thb">ค่าเช่ารายเดือน (บาท)</label>
                    <input type="number" step="0.01" name="monthly_cost_thb" id="monthly_cost_thb" value="{{ old('monthly_cost_thb', $node->monthly_cost_thb ?? 0) }}" min="0" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="included_gb">ปริมาณที่รวมในแพ็กเกจ (GB)</label>
                    <input type="number" name="included_gb" id="included_gb" value="{{ old('included_gb', $node->included_gb) }}" min="1" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="overage_thb_per_gb">ค่าส่วนเกิน (บาท/GB)</label>
                    <input type="number" step="0.0001" name="overage_thb_per_gb" id="overage_thb_per_gb" value="{{ old('overage_thb_per_gb', $node->overage_thb_per_gb ?? 0) }}" min="0" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="billing_day">วันเริ่มรอบบิล (1–28)</label>
                    <input type="number" name="billing_day" id="billing_day" value="{{ old('billing_day', $node->billing_day) }}" min="1" max="28" required class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="billing_mode">นับปริมาณแบบ</label>
                    <select name="billing_mode" id="billing_mode" class="{{ $input }}">
                        @foreach(['out' => 'ขาออกเท่านั้น (out)', 'in_out' => 'เข้า + ออก (in_out)', 'max' => 'ทิศที่มากกว่า (max)'] as $value => $label)
                            <option value="{{ $value }}" {{ old('billing_mode', $node->billing_mode) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                        <input type="hidden" name="allow_overage" value="0">
                        <input type="checkbox" name="allow_overage" value="1" {{ old('allow_overage', $node->allow_overage) ? 'checked' : '' }} class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        ยอมให้ใช้เกินแพ็กเกจ (เฉพาะ Pro, ถูกปิดอัตโนมัติเมื่อเกินงบรวม)
                    </label>
                </div>
            </div>
        </div>

        {{-- WireGuard exit --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">WireGuard exit</h3>
            <p class="text-sm text-gray-500 mb-4">ใช้เมื่อเลือกบทบาท game หรือ vpn — โหนดเก็บ private key เอง ระบบเก็บเฉพาะ public key ที่โหนดรายงาน</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="wg_address">ที่อยู่ exit / ซับเน็ต</label>
                    <input type="text" name="wg_address" id="wg_address" value="{{ old('wg_address', $originalAddress) }}" maxlength="18" placeholder="10.66.0.1/24" class="{{ $input }} font-mono">
                    @if($originalAddress)
                        <p class="mt-1 text-xs text-amber-600">เปลี่ยนซับเน็ต = ล้าง IP ที่แจกไปแล้ว ผู้ใช้ต้องเชื่อมต่อใหม่</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="wg_dns">DNS</label>
                    <input type="text" name="wg_dns" id="wg_dns" value="{{ old('wg_dns', $server?->dns ?? '1.1.1.1, 8.8.8.8') }}" maxlength="255" class="{{ $input }} font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="wg_max_clients">WireGuard peers สูงสุด</label>
                    <input type="number" name="wg_max_clients" id="wg_max_clients" value="{{ old('wg_max_clients', $server?->max_clients ?? 250) }}" min="1" max="65000" class="{{ $input }}">
                </div>
            </div>
            @if($editing && $node->wg_public_key)
                <p class="mt-3 text-xs text-gray-500">Public key จากโหนด: <span class="font-mono">{{ $node->wg_public_key }}</span></p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-sm">
                {{ $editing ? 'บันทึก' : 'เพิ่มโหนดและสร้างรหัสลับ' }}
            </button>
            <a href="{{ route('admin.localvpn.fleet.nodes') }}" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors">ยกเลิก</a>
        </div>
    </form>

    @if($editing)
        <div class="bg-white rounded-2xl shadow-lg border border-red-100 p-6 mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">จัดการรหัสลับและการลบ</h3>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.localvpn.fleet.nodes.rotate', $node) }}"
                      onsubmit="return confirm('สร้างรหัสลับใหม่ให้โหนด ' + @js($node->code) + '? รหัสเดิมจะใช้ไม่ได้ทันที และตั๋ว relay ที่แจกไปแล้วจะถูกโหนดปฏิเสธ — ต้องติดตั้งรหัสใหม่บนโหนด')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors">สร้างรหัสลับใหม่</button>
                </form>
                <form method="POST" action="{{ route('admin.localvpn.fleet.nodes.delete', $node) }}"
                      onsubmit="return confirm('ลบโหนด ' + @js($node->code) + '? ประวัติการใช้งาน WireGuard exit และลูกข่ายของโหนดนี้จะถูกลบด้วย การกระทำนี้ย้อนกลับไม่ได้')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">ลบโหนด</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@if($editing && $originalAddress)
@push('scripts')
<script>
    document.getElementById('node-form').addEventListener('submit', function (event) {
        const original = @js($originalAddress);
        const current = document.getElementById('wg_address').value.trim();
        if (current !== original && !confirm('เปลี่ยนซับเน็ต WireGuard จาก ' + original + ' เป็น ' + current + '? IP ที่แจกไปแล้วจะถูกล้างและผู้ใช้ต้องเชื่อมต่อใหม่')) {
            event.preventDefault();
        }
    });
</script>
@endpush
@endif
