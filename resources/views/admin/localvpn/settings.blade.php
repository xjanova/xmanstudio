@extends('layouts.admin')

@section('title', 'LocalVPN - ตั้งค่า')
@section('page-title', 'LocalVPN - ตั้งค่า Relay Server')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.localvpn.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.localvpn.networks') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">เครือข่าย</a>
        <a href="{{ route('admin.localvpn.members') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">อุปกรณ์</a>
        <a href="{{ route('admin.localvpn.sessions') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Sessions</a>
        <a href="{{ route('admin.localvpn.traffic') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Traffic Logs</a>
        <a href="{{ route('admin.localvpn.settings') }}" class="whitespace-nowrap border-b-2 border-emerald-500 pb-3 px-1 text-sm font-medium text-emerald-600">ตั้งค่า</a>
    </nav>
</div>

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
</div>
@endsection
