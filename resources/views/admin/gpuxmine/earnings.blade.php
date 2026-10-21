@extends($adminLayout ?? 'layouts.admin')

@section('title', 'GPUxMINE · รายได้และคิวตรวจ')
@section('page-title', 'GPUxMINE · รายได้และคิวตรวจ')

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $tabs = [
        'review' => 'รอตรวจ',
        'pending' => 'อยู่ในระยะพัก',
        'cleared' => 'รอโอน',
        'paid' => 'เข้ากระเป๋าแล้ว',
        'void' => 'ยกเลิก',
        'all' => 'ทั้งหมด',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">GPUxMINE — รายได้และคิวตรวจ</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ทุกงานพัก {{ $holdHours }} ชม. แล้วเข้ากระเป๋า XMAN ของเจ้าของเครื่องทุกชั่วโมง ·
                “รอตรวจ” ไม่ถูกปล่อยเอง: อนุมัติ (ถ้ายังไม่พ้นระยะพักจะกลับไปพักจนครบ) หรือยกเลิกพร้อมเหตุผล ·
                ยกเลิกได้เฉพาะเงินที่ยังไม่เข้ากระเป๋า
            </p>
        </div>
        <a href="{{ route('admin.gpuxmine.index') }}"
           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-medium text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
            ← เครื่องในเครือข่าย
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    @include('admin.gpuxmine.partials.money-tiles', ['money' => $money])

    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex flex-wrap gap-1.5">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.gpuxmine.earnings', array_filter(['status' => $key, 'q' => $search ?: null])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $status === $key
                           ? 'bg-slate-900 text-amber-300 dark:bg-amber-500 dark:text-slate-900'
                           : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                        @if ($key !== 'all')
                            <span class="opacity-70 tabular-nums">{{ number_format($money[$key]['jobs'] ?? 0) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.gpuxmine.earnings') }}" class="flex flex-wrap gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" value="{{ $search }}" maxlength="100"
                       placeholder="ค้นหา job id, prompt id, worker id, ชื่อหรืออีเมลเจ้าของ"
                       class="flex-1 min-w-[16rem] px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium transition">ค้นหา</button>
                @if ($search !== '')
                    <a href="{{ route('admin.gpuxmine.earnings', ['status' => $status]) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">ล้าง</a>
                @endif
            </form>
        </div>

        @if ($earnings->isEmpty())
            <div class="p-10 text-center">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ $status === 'review' && $search === '' ? 'ไม่มีงานที่รอตรวจ' : 'ไม่มีรายการที่ตรงกับตัวกรองนี้' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">งานที่ aixman ตรวจแล้วผลดูผิดปกติ หรือยอดติดลบ จะมารอที่นี่</p>
            </div>
        @else
            @include('admin.gpuxmine.partials.earnings-table', ['earnings' => $earnings, 'holdHours' => $holdHours, 'showNode' => true])
            @if ($earnings->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">{{ $earnings->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
