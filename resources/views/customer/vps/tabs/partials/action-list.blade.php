{{-- รายการสิ่งที่ทำกับเครื่อง (ใหม่สุดก่อน) — $items จาก VpsController::actionsFor() --}}
<ol class="mt-4 relative border-l border-slate-200 dark:border-slate-700 ml-2 space-y-4">
    @foreach($items as $item)
        <li class="ml-5">
            <span class="absolute -left-[7px] mt-1.5 h-3 w-3 rounded-full border-2 border-white dark:border-slate-800 {{ $item['state']['en'] === 'Failed' ? 'bg-red-500' : ($item['state']['en'] === 'Done' ? 'bg-emerald-500' : 'bg-amber-400') }}" aria-hidden="true"></span>
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <p class="text-sm font-semibold text-slate-900 dark:text-white"><x-bi :th="$item['label']['th']" :en="$item['label']['en']" /></p>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $item['state']['classes'] }}">
                    <x-bi :th="$item['state']['th']" :en="$item['state']['en']" />
                </span>
            </div>
            @if($stamp = $when($item['created_at'] ?? null))
                <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ $stamp }}</p>
            @endif
        </li>
    @endforeach
</ol>
