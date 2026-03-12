@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า Redis')
@section('page-title', 'ตั้งค่า Redis')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-600 via-red-500 to-orange-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Redis Settings</h1>
                </div>
                <p class="text-red-100 text-lg">ตั้งค่า Redis สำหรับ Cache, Session และ Queue</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <div class="flex items-center text-green-700 dark:text-green-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Connection Status -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                <div class="w-8 h-8 bg-{{ $status['connected'] ? 'green' : 'red' }}-100 dark:bg-{{ $status['connected'] ? 'green' : 'red' }}-900/30 rounded-lg flex items-center justify-center mr-3">
                    @if($status['connected'])
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                สถานะการเชื่อมต่อ
            </h3>
            <button onclick="testRedisConnection()" id="testBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                ทดสอบการเชื่อมต่อ
            </button>
        </div>

        <div id="connectionStatus" class="p-4 rounded-xl {{ $status['connected'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
            <p class="text-sm {{ $status['connected'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                {{ $status['message'] }}
            </p>
        </div>

        @if($info)
        <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Version</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['version'] }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Uptime</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['uptime'] }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Clients</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['connected_clients'] }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Memory</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['used_memory'] }}</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Total Keys</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $info['total_keys'] }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Current Services Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Cache Driver</p>
                    <p class="text-lg font-bold {{ $services['cache'] === 'redis' ? 'text-green-600' : 'text-gray-600 dark:text-gray-400' }}">{{ strtoupper($services['cache']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Session Driver</p>
                    <p class="text-lg font-bold {{ $services['session'] === 'redis' ? 'text-green-600' : 'text-gray-600 dark:text-gray-400' }}">{{ strtoupper($services['session']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Queue Driver</p>
                    <p class="text-lg font-bold {{ $services['queue'] === 'redis' ? 'text-green-600' : 'text-gray-600 dark:text-gray-400' }}">{{ strtoupper($services['queue']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="{{ route('admin.redis-settings.update') }}">
        @csrf
        @method('PUT')

        <!-- Redis Connection -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                </div>
                Redis Connection
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Redis Client</label>
                    <select name="redis_client" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500">
                        <option value="phpredis" {{ $config['client'] === 'phpredis' ? 'selected' : '' }}>phpredis (แนะนำ - เร็วกว่า)</option>
                        <option value="predis" {{ $config['client'] === 'predis' ? 'selected' : '' }}>predis (Pure PHP - ไม่ต้องติดตั้ง extension)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">phpredis ต้องติดตั้ง PHP extension, predis ใช้ได้เลย</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Host</label>
                    <input type="text" name="redis_host" value="{{ $config['host'] }}" placeholder="127.0.0.1"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                    <input type="number" name="redis_port" value="{{ $config['port'] }}" placeholder="6379"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input type="password" name="redis_password" value="{{ $config['password'] }}" placeholder="ไม่มี password ให้เว้นว่าง"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Database</label>
                    <input type="number" name="redis_db" value="{{ $config['database'] }}" min="0" max="15"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-red-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Redis database index (0-15)</p>
                </div>
            </div>
        </div>

        <!-- Service Drivers -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                Service Drivers
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">เลือก driver สำหรับแต่ละ service (เลือก redis เพื่อเพิ่มประสิทธิภาพ)</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cache Store</label>
                    <select name="cache_store" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="database" {{ $services['cache'] === 'database' ? 'selected' : '' }}>Database</option>
                        <option value="redis" {{ $services['cache'] === 'redis' ? 'selected' : '' }}>Redis (เร็วที่สุด)</option>
                        <option value="file" {{ $services['cache'] === 'file' ? 'selected' : '' }}>File</option>
                        <option value="array" {{ $services['cache'] === 'array' ? 'selected' : '' }}>Array (ไม่ cache จริง)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session Driver</label>
                    <select name="session_driver" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="database" {{ $services['session'] === 'database' ? 'selected' : '' }}>Database</option>
                        <option value="redis" {{ $services['session'] === 'redis' ? 'selected' : '' }}>Redis (เร็วที่สุด)</option>
                        <option value="file" {{ $services['session'] === 'file' ? 'selected' : '' }}>File</option>
                        <option value="cookie" {{ $services['session'] === 'cookie' ? 'selected' : '' }}>Cookie</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Queue Connection</label>
                    <select name="queue_connection" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="database" {{ $services['queue'] === 'database' ? 'selected' : '' }}>Database</option>
                        <option value="redis" {{ $services['queue'] === 'redis' ? 'selected' : '' }}>Redis (เร็วที่สุด)</option>
                        <option value="sync" {{ $services['queue'] === 'sync' ? 'selected' : '' }}>Sync (ไม่ใช้ queue)</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>แนะนำ:</strong> ใช้ Redis สำหรับทุก service เพื่อประสิทธิภาพสูงสุด
                    หากไม่มี Redis server ให้ใช้ database เป็น driver หลัก
                </p>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 shadow-lg hover:shadow-xl transition-all">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
function testRedisConnection() {
    const btn = document.getElementById('testBtn');
    btn.disabled = true;
    btn.textContent = 'กำลังทดสอบ...';

    fetch('{{ route("admin.redis-settings.test") }}', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('connectionStatus');
        if (data.connected) {
            el.className = 'p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800';
            el.innerHTML = '<p class="text-sm text-green-700 dark:text-green-300">' + data.message + '</p>';
        } else {
            el.className = 'p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
            el.innerHTML = '<p class="text-sm text-red-700 dark:text-red-300">' + data.message + '</p>';
        }
    })
    .catch(err => {
        alert('เกิดข้อผิดพลาด: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'ทดสอบการเชื่อมต่อ';
    });
}
</script>
@endsection
