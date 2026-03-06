@extends($customerLayout ?? 'layouts.customer')

@section('title', $workflow->name . ' - Tping Workflow')
@section('page-title', $workflow->name)
@section('page-description', 'รายละเอียด Workflow')

@section('content')
<!-- Back Link -->
<div class="mb-6">
    <a href="{{ route('customer.tping.workflows.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายการ Workflow
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

<!-- Info Card -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $workflow->name }}</h2>
            @if($workflow->target_app_name || $workflow->target_app_package)
                <p class="text-sm text-gray-500 mt-1">
                    แอพเป้าหมาย: {{ $workflow->target_app_name ?: $workflow->target_app_package }}
                </p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                สร้างเมื่อ {{ $workflow->created_at->format('d/m/Y H:i') }}
                &middot; อัปเดตเมื่อ {{ $workflow->updated_at->diffForHumans() }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.tping.workflows.edit', $workflow) }}" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-sm font-medium hover:bg-blue-100 transition-all">
                แก้ไข
            </a>

            @if($workflow->share_token)
                <form method="POST" action="{{ route('customer.tping.workflows.unshare', $workflow) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
                        ยกเลิกแชร์
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('customer.tping.workflows.share', $workflow) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-purple-50 text-purple-600 rounded-xl text-sm font-medium hover:bg-purple-100 transition-all">
                        สร้างลิงก์แชร์
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('customer.tping.workflows.destroy', $workflow) }}" onsubmit="return confirm('ลบ workflow นี้?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-medium hover:bg-red-100 transition-all">
                    ลบ
                </button>
            </form>
        </div>
    </div>

    @if($workflow->share_token)
        <div class="mt-4 p-3 bg-purple-50 rounded-xl border border-purple-100">
            <p class="text-xs text-purple-600 font-medium mb-1">ลิงก์แชร์:</p>
            <div class="flex items-center gap-2">
                <input type="text" value="{{ url('/shared/workflow/' . $workflow->share_token) }}" readonly
                       class="flex-1 text-sm bg-white border border-purple-200 rounded-lg px-3 py-1.5 text-gray-700" id="shareUrl">
                <button onclick="navigator.clipboard.writeText(document.getElementById('shareUrl').value); this.textContent='คัดลอกแล้ว!'; setTimeout(() => this.textContent='คัดลอก', 2000)"
                        class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 transition-all">
                    คัดลอก
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Steps Visualization -->
<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-bold text-gray-900 mb-4">ขั้นตอน ({{ count($steps) }} steps)</h3>

    @if(empty($steps))
        <p class="text-gray-400 text-center py-8">ไม่มีข้อมูลขั้นตอน</p>
    @else
        <div class="space-y-3">
            @foreach($steps as $i => $step)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
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
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $badgeColor }}">
                                {{ $actionType }}
                            </span>
                            @if(!empty($step['description']))
                                <span class="text-sm text-gray-600">{{ $step['description'] }}</span>
                            @endif
                            @if(!empty($step['dataKey']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700">
                                    {{ $step['dataKey'] }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($step['text']))
                            <p class="text-xs text-gray-400 mt-1 truncate">ข้อความ: {{ $step['text'] }}</p>
                        @endif
                        @if(!empty($step['resourceId']))
                            <p class="text-xs text-gray-400 mt-0.5 truncate">ID: {{ $step['resourceId'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
