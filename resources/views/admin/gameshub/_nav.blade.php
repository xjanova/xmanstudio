{{-- Tabs and validation errors shared by every XGamesHub back-office page --}}
@php
    $gsPending = [
        'donations' => \App\Models\GameDonation::where('status', 'pending')->count(),
        'reviews' => \App\Models\Review::where('reviewable_type', \App\Models\GameCampaign::class)->where('status', 'pending')->count(),
        'comments' => \App\Models\GameComment::where('status', 'pending')->count(),
    ];
    $gsTabs = [
        ['admin.gameshub.dashboard', 'ภาพรวม', null, 'admin.gameshub.dashboard'],
        ['admin.game-support.index', 'สลิปบริจาค', $gsPending['donations'], 'admin.game-support.*'],
        ['admin.gameshub.items', 'ไอเท็มผู้สนับสนุน', null, 'admin.gameshub.items'],
        ['admin.gameshub.reviews', 'รีวิว', $gsPending['reviews'], 'admin.gameshub.reviews'],
        ['admin.gameshub.comments', 'ความเห็น', $gsPending['comments'], 'admin.gameshub.comments'],
        ['admin.gameshub.games', 'เกม · รางวัล · ฮีโร่', null, 'admin.gameshub.games'],
        ['admin.gameshub.announcements', 'ประกาศบนฮับ', null, 'admin.gameshub.announcements'],
    ];
@endphp
<div class="mb-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-500">XGamesHub · หลังบ้านเว็บเกม</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">ควบคุม xgameshub.xman4289.com: บริจาค ไอเท็ม รีวิว ความเห็น และหน้าแรกของฮับ</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="https://xgameshub.xman4289.com/" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">เปิดฮับ ↗</a>
            <a href="{{ route('game-support.index') }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">หน้าชุมชนสาธารณะ ↗</a>
        </div>
    </div>
    <nav class="flex flex-wrap gap-2" aria-label="เมนู XGamesHub">
        @foreach($gsTabs as [$route, $label, $badge, $pattern])
            <a href="{{ route($route) }}" @class([
                'inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg border transition',
                'bg-indigo-600 border-indigo-600 text-white' => request()->routeIs($pattern),
                'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-indigo-400' => ! request()->routeIs($pattern),
            ])>
                {{ $label }}
                @if($badge)
                    <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full bg-amber-400 text-amber-950">{{ $badge }}</span>
                @endif
            </a>
        @endforeach
    </nav>
    {{-- success / error flashes come from the admin layout; validation errors do not --}}
    @if($errors->any())
        <div class="px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm" role="alert">
            <p class="font-semibold">กรุณาตรวจข้อมูลอีกครั้ง</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
</div>
