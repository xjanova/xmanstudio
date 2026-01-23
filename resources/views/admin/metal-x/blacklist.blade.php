@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Metal-X Blacklist')
@section('page-title', 'Blacklisted Channels Management')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-red-600 to-red-900 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">Blacklisted Channels</h2>
            <p class="text-red-200 text-sm">ช่องที่ถูกบล็อกเนื่องจากละเมิดกฎ (การพนัน, spam, เนื้อหาไม่เหมาะสม)</p>
        </div>
        <div class="flex gap-4">
            <span class="px-4 py-2 bg-white/20 rounded-lg">
                Total: {{ $blacklist->total() }}
            </span>
            <a href="{{ route('admin.metal-x.engagement.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>
</div>

@if($blacklist->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Violations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">First / Last</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blocked By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($blacklist as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $entry->channel_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $entry->channel_id }}</div>
                                    @if($entry->notes)
                                        <div class="text-xs text-gray-400 mt-1">{{ Str::limit($entry->notes, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    // XSS-safe: Use complete class names from whitelist
                                    $reasonBadges = [
                                        'gambling' => ['class' => 'px-3 py-1 bg-red-100 text-red-800 text-sm rounded-full', 'icon' => '🎰'],
                                        'scam' => ['class' => 'px-3 py-1 bg-orange-100 text-orange-800 text-sm rounded-full', 'icon' => '🚨'],
                                        'inappropriate' => ['class' => 'px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full', 'icon' => '⚠️'],
                                        'harassment' => ['class' => 'px-3 py-1 bg-pink-100 text-pink-800 text-sm rounded-full', 'icon' => '😡'],
                                        'spam' => ['class' => 'px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full', 'icon' => '📧'],
                                        'impersonation' => ['class' => 'px-3 py-1 bg-indigo-100 text-indigo-800 text-sm rounded-full', 'icon' => '👥'],
                                    ];
                                    $badge = $reasonBadges[$entry->reason] ?? ['class' => 'px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full', 'icon' => '🚫'];
                                @endphp
                                <span class="{{ $badge['class'] }}">
                                    {{ $badge['icon'] }} {{ ucfirst($entry->reason) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-2xl font-bold text-red-600">{{ $entry->violation_count }}</span>
                                <span class="text-sm text-gray-500">times</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>First: {{ $entry->first_violation_at->format('Y-m-d') }}</div>
                                <div>Last: {{ $entry->last_violation_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($entry->blocked_by)
                                    {{ $entry->blockedBy->name ?? 'User #' . $entry->blocked_by }}
                                @else
                                    <span class="text-gray-400">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($entry->is_blocked)
                                    <span class="px-3 py-1 bg-red-100 text-red-800 text-sm rounded-full">
                                        🚫 Blocked
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                                        ✓ Unblocked
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($entry->is_blocked)
                                    <button onclick="unblock({{ $entry->id }})" class="px-3 py-1 bg-green-100 hover:bg-green-200 text-green-700 text-sm rounded">
                                        Unblock
                                    </button>
                                @else
                                    <span class="text-sm text-gray-400">No actions</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $blacklist->links() }}
        </div>
    </div>
@else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-lg text-gray-600">ไม่มีช่องที่ถูกบล็อก</p>
        <p class="text-sm text-gray-500">ช่องที่ละเมิดกฎจะถูกเพิ่มที่นี่อัตโนมัติ</p>
    </div>
@endif
@endsection

@push('scripts')
<script>
function unblock(id) {
    if (!confirm('ปลดบล็อกช่องนี้หรือไม่?')) return;

    fetch(`/admin/metal-x/engagement/blacklist/${id}/unblock`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ปลดบล็อกเรียบร้อย');
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
    });
}
</script>
@endpush
