{{-- LocalVPN → Fleet sub-navigation: nodes, policy, cost --}}
@php
    $fleetTabs = [
        ['route' => 'admin.localvpn.fleet.nodes', 'match' => 'admin.localvpn.fleet.nodes*', 'label' => 'โหนด'],
        ['route' => 'admin.localvpn.fleet.policy', 'match' => 'admin.localvpn.fleet.policy*', 'label' => 'นโยบาย & เกม'],
        ['route' => 'admin.localvpn.fleet.cost', 'match' => 'admin.localvpn.fleet.cost*', 'label' => 'ค่าใช้จ่าย'],
    ];
@endphp
<div class="flex items-center gap-2 mb-6 -mt-2">
    <span class="text-xs font-semibold text-sky-600 uppercase tracking-wider">Relay Fleet</span>
    <nav class="flex gap-1 overflow-x-auto" aria-label="Fleet Navigation">
        @foreach($fleetTabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="whitespace-nowrap px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200
                   {{ request()->routeIs($tab['match']) ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-200 shadow-sm' : 'text-gray-500 hover:text-sky-600 hover:bg-sky-50/50' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
    <div class="flex-1 h-px bg-gradient-to-r from-sky-200 to-transparent"></div>
</div>
