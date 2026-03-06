@extends($adminLayout ?? 'layouts.admin')

@section('title', $workflow->name . ' - Admin')
@section('page-title', 'Workflow Detail')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tping.workflows.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายการ
    </a>
</div>

<!-- Info -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $workflow->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                โดย: {{ $workflow->user->name ?? 'Unknown' }} ({{ $workflow->user->email ?? '-' }})
            </p>
            @if($workflow->target_app_name)
                <p class="text-sm text-gray-500">แอพ: {{ $workflow->target_app_name }} ({{ $workflow->target_app_package }})</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                ID: #{{ $workflow->id }} &middot;
                สร้าง: {{ $workflow->created_at->format('d/m/Y H:i') }} &middot;
                อัปเดต: {{ $workflow->updated_at->diffForHumans() }}
            </p>
            @if($workflow->share_token)
                <p class="text-xs text-purple-600 mt-1">
                    แชร์: {{ url('/shared/workflow/' . $workflow->share_token) }}
                </p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.tping.workflows.destroy', $workflow) }}" onsubmit="return confirm('ลบ workflow #{{ $workflow->id }}?')">
            @csrf @method('DELETE')
            <button class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-medium hover:bg-red-100 transition-all">ลบ</button>
        </form>
    </div>
</div>

<!-- Steps -->
<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-bold text-gray-900 mb-4">ขั้นตอน ({{ count($steps) }} steps)</h3>

    @if(empty($steps))
        <p class="text-gray-400 text-center py-8">ไม่มีข้อมูลขั้นตอน</p>
    @else
        <div class="space-y-3">
            @foreach($steps as $i => $step)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center text-white text-xs font-bold">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            @php
                                $actionType = $step['actionType'] ?? $step['type'] ?? 'unknown';
                                $badgeColor = match($actionType) {
                                    'CLICK' => 'bg-blue-100 text-blue-700',
                                    'INPUT_TEXT' => 'bg-green-100 text-green-700',
                                    'SCROLL' => 'bg-amber-100 text-amber-700',
                                    'SWIPE' => 'bg-orange-100 text-orange-700',
                                    'BACK' => 'bg-gray-100 text-gray-700',
                                    'WAIT' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }}">{{ $actionType }}</span>
                            @if(!empty($step['description']))
                                <span class="text-sm text-gray-600">{{ $step['description'] }}</span>
                            @endif
                            @if(!empty($step['dataKey']))
                                <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700">{{ $step['dataKey'] }}</span>
                            @endif
                        </div>
                        @if(!empty($step['resourceId']))
                            <p class="text-xs text-gray-400 mt-0.5 truncate">ID: {{ $step['resourceId'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Raw JSON -->
<details class="mt-6">
    <summary class="text-sm text-gray-400 cursor-pointer hover:text-gray-600">ดู Raw JSON</summary>
    <pre class="mt-2 p-4 bg-gray-900 text-gray-300 rounded-xl text-xs overflow-x-auto">{{ json_encode(json_decode($workflow->steps_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</details>
@endsection
