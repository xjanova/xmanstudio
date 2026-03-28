@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - ตั้งค่า')
@section('page-title', 'LocalVPN - ตั้งค่า Relay Server')

@section('content')
@include('admin.localvpn._tabs')

{{-- Settings Form --}}
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">การตั้งค่า Relay Server</h3>

        <form method="POST" action="{{ route('admin.localvpn.settings.update') }}" class="space-y-6">
            @csrf

            <div>
                <label for="localvpn_max_networks_per_user" class="block text-sm font-medium text-gray-700 mb-1">
                    จำนวนเครือข่ายสูงสุดต่อผู้ใช้
                </label>
                <input type="number" name="localvpn_max_networks_per_user" id="localvpn_max_networks_per_user"
                       value="{{ old('localvpn_max_networks_per_user', $settings['localvpn_max_networks_per_user']) }}"
                       min="1" max="100"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <p class="mt-1 text-xs text-gray-500">จำนวนเครือข่ายที่ผู้ใช้แต่ละคนสามารถสร้างได้ (1-100)</p>
                @error('localvpn_max_networks_per_user')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="localvpn_max_members_per_network" class="block text-sm font-medium text-gray-700 mb-1">
                    จำนวนสมาชิกสูงสุดต่อเครือข่าย
                </label>
                <input type="number" name="localvpn_max_members_per_network" id="localvpn_max_members_per_network"
                       value="{{ old('localvpn_max_members_per_network', $settings['localvpn_max_members_per_network']) }}"
                       min="2" max="254"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <p class="mt-1 text-xs text-gray-500">จำนวนอุปกรณ์สูงสุดที่สามารถเข้าร่วมเครือข่ายเดียว (2-254)</p>
                @error('localvpn_max_members_per_network')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="localvpn_heartbeat_interval" class="block text-sm font-medium text-gray-700 mb-1">
                    Heartbeat Interval (วินาที)
                </label>
                <input type="number" name="localvpn_heartbeat_interval" id="localvpn_heartbeat_interval"
                       value="{{ old('localvpn_heartbeat_interval', $settings['localvpn_heartbeat_interval']) }}"
                       min="5" max="300"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <p class="mt-1 text-xs text-gray-500">ระยะเวลาระหว่างการส่ง heartbeat จากอุปกรณ์ (5-300 วินาที)</p>
                @error('localvpn_heartbeat_interval')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="localvpn_session_timeout" class="block text-sm font-medium text-gray-700 mb-1">
                    Session Timeout (วินาที)
                </label>
                <input type="number" name="localvpn_session_timeout" id="localvpn_session_timeout"
                       value="{{ old('localvpn_session_timeout', $settings['localvpn_session_timeout']) }}"
                       min="30" max="3600"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <p class="mt-1 text-xs text-gray-500">ระยะเวลาที่ไม่มี heartbeat ก่อนจะถือว่า offline (30-3600 วินาที)</p>
                @error('localvpn_session_timeout')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="localvpn_data_relay_limit_mb" class="block text-sm font-medium text-gray-700 mb-1">
                    Data Relay Limit ต่อวัน (MB)
                </label>
                <input type="number" name="localvpn_data_relay_limit_mb" id="localvpn_data_relay_limit_mb"
                       value="{{ old('localvpn_data_relay_limit_mb', $settings['localvpn_data_relay_limit_mb']) }}"
                       min="1" max="10000"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <p class="mt-1 text-xs text-gray-500">ขีดจำกัดข้อมูลที่ relay ผ่านเซิร์ฟเวอร์ต่อวันต่อเครือข่าย (1-10000 MB)</p>
                @error('localvpn_data_relay_limit_mb')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 border-t border-gray-200">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-sm">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>

    {{-- Premium Proxy Servers --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Premium Proxy Servers</h3>
        <p class="text-sm text-gray-500 mb-4">เซิร์ฟเวอร์ VPN Proxy สำหรับผู้ใช้ Premium (xman4289.com)</p>

        <form method="POST" action="{{ route('admin.localvpn.settings.update') }}" class="space-y-4">
            @csrf

            <div class="flex items-center">
                <input type="hidden" name="localvpn_premium_proxy_enabled" value="0">
                <input type="checkbox" name="localvpn_premium_proxy_enabled" value="1" id="premium_proxy_enabled"
                       {{ old('localvpn_premium_proxy_enabled', $settings['localvpn_premium_proxy_enabled']) == '1' ? 'checked' : '' }}
                       class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                <label for="premium_proxy_enabled" class="ml-2 text-sm text-gray-700">เปิดใช้ Premium Proxy Servers</label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    รายการ Proxy Servers (JSON)
                </label>
                <textarea name="localvpn_premium_proxy_servers" rows="10"
                          class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 font-mono text-xs"
                          placeholder='[{"hostname":"proxy1.xman4289.com","ip":"1.2.3.4","country_code":"TH","country_name":"Thailand","speed":100000000,"score":100,"ping":20,"sessions":1,"uptime":99999,"openvpn_config":"BASE64_CONFIG"}]'
                >{{ old('localvpn_premium_proxy_servers', $settings['localvpn_premium_proxy_servers']) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">
                    JSON array ของ proxy servers. Fields: hostname, ip, country_code, country_name, speed, score, openvpn_config (Base64)
                </p>
                @error('localvpn_premium_proxy_servers')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Pass through existing settings to avoid overwriting --}}
            <input type="hidden" name="localvpn_max_networks_per_user" value="{{ $settings['localvpn_max_networks_per_user'] }}">
            <input type="hidden" name="localvpn_max_members_per_network" value="{{ $settings['localvpn_max_members_per_network'] }}">
            <input type="hidden" name="localvpn_heartbeat_interval" value="{{ $settings['localvpn_heartbeat_interval'] }}">
            <input type="hidden" name="localvpn_session_timeout" value="{{ $settings['localvpn_session_timeout'] }}">
            <input type="hidden" name="localvpn_data_relay_limit_mb" value="{{ $settings['localvpn_data_relay_limit_mb'] }}">

            <div class="pt-4 border-t border-gray-200">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors shadow-sm">
                    บันทึก Premium Proxy
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
