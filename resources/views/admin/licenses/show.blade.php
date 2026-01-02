@extends('layouts.admin')

@section('title', 'รายละเอียด License')
@section('page-title', 'รายละเอียด License')

@section('content')
<div class="max-w-4xl">
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-2xl font-mono font-bold text-gray-900">{{ $license->license_key }}</h3>
                <div class="mt-2 flex items-center space-x-3">
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                        @if($license->license_type === 'lifetime') bg-purple-100 text-purple-800
                        @elseif($license->license_type === 'yearly') bg-blue-100 text-blue-800
                        @elseif($license->license_type === 'monthly') bg-cyan-100 text-cyan-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($license->license_type) }}
                    </span>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                        @if($license->isValid()) bg-green-100 text-green-800
                        @elseif($license->status === 'revoked') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        @if($license->isValid())
                            Active
                        @elseif($license->status === 'revoked')
                            Revoked
                        @else
                            Expired
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex space-x-2">
                @if($license->status === 'active')
                    <form action="{{ route('admin.licenses.revoke', $license) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            ยกเลิก License
                        </button>
                    </form>
                @elseif($license->status === 'revoked')
                    <form action="{{ route('admin.licenses.reactivate', $license) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            เปิดใช้งานใหม่
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">ข้อมูลทั่วไป</h4>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">สร้างเมื่อ</dt>
                        <dd class="text-gray-900">{{ $license->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if($license->product)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">ผลิตภัณฑ์</dt>
                        <dd class="text-gray-900">{{ $license->product->name }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">จำนวน Activation</dt>
                        <dd class="text-gray-900">{{ $license->activations }} / {{ $license->max_activations }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">ระยะเวลา</h4>
                <dl class="space-y-3">
                    @if($license->activated_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">เปิดใช้งานเมื่อ</dt>
                        <dd class="text-gray-900">{{ $license->activated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">หมดอายุ</dt>
                        <dd class="text-gray-900">
                            @if($license->license_type === 'lifetime')
                                <span class="text-purple-600">ตลอดชีพ</span>
                            @elseif($license->expires_at)
                                {{ $license->expires_at->format('d/m/Y H:i') }}
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if($license->expires_at && $license->license_type !== 'lifetime')
                    <div class="flex justify-between">
                        <dt class="text-gray-500">เหลือ</dt>
                        <dd class="{{ $license->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                            @if($license->isExpired())
                                หมดอายุแล้ว
                            @else
                                {{ $license->daysRemaining() }} วัน
                            @endif
                        </dd>
                    </div>
                    @endif
                    @if($license->last_validated_at)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">ตรวจสอบล่าสุด</dt>
                        <dd class="text-gray-900">{{ $license->last_validated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Machine Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-semibold text-gray-900">ข้อมูลเครื่อง</h4>
            @if($license->machine_id)
                <form action="{{ route('admin.licenses.reset-machine', $license) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-orange-600 hover:underline">รีเซ็ตเครื่อง</button>
                </form>
            @endif
        </div>

        @if($license->machine_id)
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Machine ID</dt>
                    <dd class="text-gray-900 font-mono text-sm">{{ $license->machine_id }}</dd>
                </div>
            </dl>
        @else
            <p class="text-gray-500">ยังไม่ได้เปิดใช้งานบนเครื่องใด</p>
        @endif
    </div>

    <!-- Extend License -->
    @if($license->license_type !== 'lifetime' && $license->status !== 'revoked')
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">ขยายเวลา</h4>
        <form action="{{ route('admin.licenses.extend', $license) }}" method="POST" class="flex items-end space-x-4">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนวัน</label>
                <input type="number" name="days" min="1" max="365" value="30" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                ขยายเวลา
            </button>
        </form>
    </div>
    @endif

    <!-- Metadata -->
    @if($license->metadata)
    <div class="bg-white rounded-lg shadow p-6">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">Metadata</h4>
        <pre class="bg-gray-50 p-4 rounded-lg text-sm overflow-auto">{{ json_encode($license->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('admin.licenses.index') }}" class="text-primary-600 hover:underline">&larr; กลับไปรายการ License</a>
    </div>
</div>
@endsection
