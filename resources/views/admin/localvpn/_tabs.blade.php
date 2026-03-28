{{-- Shared LocalVPN + BitTorrent Admin Navigation Tabs --}}
<div class="mb-6">
    {{-- VPN Section --}}
    <div class="flex items-center gap-2 mb-2">
        <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            VPN
        </span>
        <div class="flex-1 h-px bg-gradient-to-r from-emerald-200 to-transparent"></div>
    </div>
    <nav class="-mb-px flex space-x-1 overflow-x-auto pb-2" aria-label="VPN Navigation">
        {{-- Dashboard --}}
        <a href="{{ route('admin.localvpn.dashboard') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.dashboard') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>

        {{-- Networks --}}
        <a href="{{ route('admin.localvpn.networks') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.networks*') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            <span class="hidden sm:inline">เครือข่าย</span>
            <span class="sm:hidden">Net</span>
        </a>

        {{-- Members --}}
        <a href="{{ route('admin.localvpn.members') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.members*') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="hidden sm:inline">อุปกรณ์</span>
            <span class="sm:hidden">Dev</span>
        </a>

        {{-- Sessions --}}
        <a href="{{ route('admin.localvpn.sessions') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.sessions*') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            Sessions
        </a>

        {{-- Traffic --}}
        <a href="{{ route('admin.localvpn.traffic') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.traffic*') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Traffic Logs
        </a>

        {{-- Settings --}}
        <a href="{{ route('admin.localvpn.settings') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.settings*') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 shadow-sm' : 'text-gray-500 hover:text-emerald-600 hover:bg-emerald-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="hidden sm:inline">ตั้งค่า</span>
            <span class="sm:hidden">Config</span>
        </a>
    </nav>

    {{-- BitTorrent Section --}}
    <div class="flex items-center gap-2 mb-2 mt-4">
        <span class="text-xs font-semibold text-violet-600 uppercase tracking-wider flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            BitTorrent
        </span>
        <div class="flex-1 h-px bg-gradient-to-r from-violet-200 to-transparent"></div>
    </div>
    <nav class="-mb-px flex space-x-1 overflow-x-auto pb-2" aria-label="BitTorrent Navigation">
        {{-- BT Dashboard --}}
        <a href="{{ route('admin.localvpn.torrent.dashboard') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.dashboard') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            BT Dashboard
        </a>

        {{-- Categories --}}
        <a href="{{ route('admin.localvpn.torrent.categories') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.categories*') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            <span class="hidden sm:inline">หมวดหมู่</span>
            <span class="sm:hidden">Cat</span>
        </a>

        {{-- Files --}}
        <a href="{{ route('admin.localvpn.torrent.files') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.files*') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="hidden sm:inline">ไฟล์</span>
            <span class="sm:hidden">Files</span>
        </a>

        {{-- KYC --}}
        <a href="{{ route('admin.localvpn.torrent.kyc') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.kyc*') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            KYC
        </a>

        {{-- Leaderboard --}}
        <a href="{{ route('admin.localvpn.torrent.leaderboard') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.leaderboard*') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Leaderboard
        </a>

        {{-- Trophies --}}
        <a href="{{ route('admin.localvpn.torrent.trophies') }}"
           class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200
               {{ request()->routeIs('admin.localvpn.torrent.trophies*') ? 'bg-violet-50 text-violet-700 ring-1 ring-violet-200 shadow-sm' : 'text-gray-500 hover:text-violet-600 hover:bg-violet-50/50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            Trophies
        </a>
    </nav>
</div>
