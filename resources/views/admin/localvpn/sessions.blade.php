@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - Sessions')
@section('page-title', 'LocalVPN - Active Relay Sessions')

@section('content')
@include('admin.localvpn._tabs')

{{-- Sessions Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Active Relay Sessions ({{ $sessions->total() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ID</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เครือข่าย</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Source</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium"></th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Target</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">Bytes Relayed</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เริ่มเมื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ระยะเวลา</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sessions as $session)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 text-gray-500 font-mono text-xs">#{{ $session->id }}</td>
                    <td class="py-3 px-4">
                        <a href="{{ route('admin.localvpn.networks.show', $session->network_id) }}" class="text-emerald-600 hover:text-emerald-800 hover:underline">
                            {{ $session->network->name ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="py-3 px-4">
                        <div>
                            <span class="font-medium text-gray-900">{{ $session->sourceMember->display_name ?? 'N/A' }}</span>
                            <br>
                            <span class="font-mono text-xs text-gray-500">{{ $session->sourceMember->virtual_ip ?? '' }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-center text-gray-400">
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </td>
                    <td class="py-3 px-4">
                        <div>
                            <span class="font-medium text-gray-900">{{ $session->targetMember->display_name ?? 'N/A' }}</span>
                            <br>
                            <span class="font-mono text-xs text-gray-500">{{ $session->targetMember->virtual_ip ?? '' }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-right font-mono text-xs">
                        @if($session->bytes_relayed >= 1048576)
                            {{ number_format($session->bytes_relayed / 1048576, 2) }} MB
                        @elseif($session->bytes_relayed >= 1024)
                            {{ number_format($session->bytes_relayed / 1024, 2) }} KB
                        @else
                            {{ number_format($session->bytes_relayed) }} B
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $session->started_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $session->started_at?->diffForHumans(null, true) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-500">ไม่มี Active Relay Sessions</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $sessions->links() }}
    </div>
    @endif
</div>
@endsection
