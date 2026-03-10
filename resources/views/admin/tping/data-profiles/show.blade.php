@extends($adminLayout ?? 'layouts.admin')

@section('title', $profile->name . ' - Admin')
@section('page-title', 'Data Profile Detail')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tping.data-profiles.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-emerald-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายการ
    </a>
</div>

<!-- Info -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $profile->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                โดย: {{ $profile->user->name ?? 'Unknown' }} ({{ $profile->user->email ?? '-' }})
            </p>
            @if($profile->category)
                <p class="text-sm text-gray-500">หมวดหมู่: {{ $profile->category }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                ID: #{{ $profile->id }} &middot;
                สร้าง: {{ $profile->created_at->format('d/m/Y H:i') }} &middot;
                อัปเดต: {{ $profile->updated_at->diffForHumans() }}
            </p>
        </div>
        <form method="POST" action="{{ route('admin.tping.data-profiles.destroy', $profile) }}" onsubmit="return confirm('ลบ profile #{{ $profile->id }}?')">
            @csrf @method('DELETE')
            <button class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-medium hover:bg-red-100 transition-all">ลบ</button>
        </form>
    </div>
</div>

<!-- Fields -->
<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูล ({{ count($fields) }} fields)</h3>

    @if(empty($fields))
        <p class="text-gray-400 text-center py-8">ไม่มีข้อมูล</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Key</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($fields as $i => $field)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900">{{ $field['key'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $field['value'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Raw JSON -->
<details class="mt-6">
    <summary class="text-sm text-gray-400 cursor-pointer hover:text-gray-600">ดู Raw JSON</summary>
    <pre class="mt-2 p-4 bg-gray-900 text-gray-300 rounded-xl text-xs overflow-x-auto">{{ json_encode(json_decode($profile->fields_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
@endsection
