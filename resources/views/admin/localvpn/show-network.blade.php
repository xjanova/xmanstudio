@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - ' . $network->name)
@section('page-title', 'LocalVPN - รายละเอียดเครือข่าย')

@section('content')
{{-- Back Button --}}
<div class="mb-6">
    <a href="{{ route('admin.localvpn.networks') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปหน้ารายการ
    </a>
</div>

{{-- Network Info Card --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h2 class="text-2xl font-bold text-gray-900">{{ $network->name }}</h2>
                @if($network->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                @endif
                @if($network->is_public)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">สาธารณะ</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">ส่วนตัว</span>
                @endif
                @if($network->password_hash)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">มีรหัสผ่าน</span>
                @endif
            </div>
            <p class="text-gray-500 mb-3">{{ $network->description ?: 'ไม่มีคำอธิบาย' }}</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Slug:</span>
                    <span class="font-mono text-gray-900">{{ $network->slug }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Subnet:</span>
                    <span class="font-mono text-gray-900">{{ $network->virtual_subnet }}</span>
                </div>
                <div>
                    <span class="text-gray-500">เจ้าของ:</span>
                    <span class="text-gray-900">{{ $network->owner->name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">สร้างเมื่อ:</span>
                    <span class="text-gray-900">{{ $network->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">สมาชิก:</span>
                    <span class="text-gray-900">{{ $network->members->count() }}/{{ $network->max_members }}</span>
                </div>
                <div>
                    <span class="text-gray-500">ออนไลน์:</span>
                    <span class="text-green-600 font-medium">{{ $network->members->where('is_online', true)->count() }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Relay Sessions:</span>
                    <span class="text-gray-900">{{ $activeSessions->count() }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Traffic:</span>
                    <span class="text-gray-900">
                        @if($totalTraffic >= 1048576)
                            {{ number_format($totalTraffic / 1048576, 2) }} MB
                        @elseif($totalTraffic >= 1024)
                            {{ number_format($totalTraffic / 1024, 2) }} KB
                        @else
                            {{ number_format($totalTraffic) }} B
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.localvpn.networks.toggle', $network) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium {{ $network->is_active ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }} transition-colors">
                    {{ $network->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.localvpn.networks.delete', $network) }}"
                  onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการลบเครือข่ายนี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-100 text-red-800 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors">ลบเครือข่าย</button>
            </form>
        </div>
    </div>
</div>

{{-- Members Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">สมาชิก ({{ $network->members->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Virtual IP</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Public IP</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">Port</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Heartbeat ล่าสุด</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เข้าร่วมเมื่อ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($network->members as $member)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $member->display_name }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ $member->virtual_ip }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ $member->public_ip ?? '-' }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ $member->public_port ?? '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        @if($member->is_online)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                Online
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Offline</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $member->last_heartbeat_at?->diffForHumans() ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $member->joined_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        <form method="POST" action="{{ route('admin.localvpn.members.kick', $member) }}"
                              onsubmit="return confirm('เตะ \'{{ $member->display_name }}\' ออกจากเครือข่าย?')">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">เตะ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-500">ยังไม่มีสมาชิก</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Active Relay Sessions --}}
@if($activeSessions->isNotEmpty())
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Active Relay Sessions ({{ $activeSessions->count() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Source</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">-></th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Target</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">Bytes Relayed</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เริ่มเมื่อ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($activeSessions as $session)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4">{{ $session->sourceMember->display_name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-center text-gray-400">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </td>
                    <td class="py-3 px-4">{{ $session->targetMember->display_name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-right font-mono text-xs">{{ number_format($session->bytes_relayed) }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $session->started_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
