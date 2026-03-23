{{-- Public Navigation Mobile Menu (shared across themes) --}}
{{-- Usage: @include('partials.public-nav-mobile', ['theme' => 'classic']) --}}
{{-- Usage: @include('partials.public-nav-mobile', ['theme' => 'premium']) --}}

@php
    $isPremium = ($theme ?? 'classic') === 'premium';

    $menuWrapper = $isPremium
        ? 'hidden lg:hidden border-t border-indigo-500/20'
        : 'hidden lg:hidden bg-white dark:bg-gray-800 border-t dark:border-gray-700';

    $menuInner = $isPremium
        ? 'px-4 py-3 space-y-2'
        : 'px-4 py-3 space-y-1.5';

    // Navigation items with colors
    $navItems = [
        ['url' => '/', 'label' => 'หน้าหลัก', 'match' => '/', 'color' => 'emerald-500', 'colorTo' => 'green-500',
         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'],
        ['url' => '/services', 'label' => 'บริการ', 'match' => 'services*', 'color' => 'yellow-400', 'colorTo' => 'amber-500',
         'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z||M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ['url' => '/products', 'label' => 'ผลิตภัณฑ์', 'match' => 'products*', 'color' => 'orange-500', 'colorTo' => 'red-500',
         'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['url' => '/rental', 'label' => 'เช่าบริการ', 'match' => 'rental*', 'color' => 'pink-500', 'colorTo' => 'rose-500',
         'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
        ['url' => '/portfolio', 'label' => 'ผลงาน', 'match' => 'portfolio*', 'color' => 'purple-500', 'colorTo' => 'violet-500',
         'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['url' => '/support', 'label' => 'ติดต่อ/สั่งซื้อ', 'match' => 'support', 'color' => 'blue-500', 'colorTo' => 'cyan-500',
         'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['url' => '/tracking', 'label' => 'ติดตามงาน', 'match' => 'tracking*', 'color' => 'teal-500', 'colorTo' => 'cyan-500',
         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    ];
@endphp

<div id="mobileMenu" class="{{ $menuWrapper }}">
    <div class="{{ $menuInner }}">
        @foreach($navItems as $item)
            @php
                $isActive = request()->is($item['match']);
                $iconPaths = explode('||', $item['icon']);

                if ($isPremium) {
                    $linkClass = $isActive
                        ? 'block px-4 py-2.5 text-base font-medium rounded-lg bg-white/10 text-white transition-all duration-300'
                        : 'block px-4 py-2.5 text-base font-medium rounded-lg text-indigo-200 hover:bg-white/5 hover:text-white transition-all duration-300';
                } else {
                    $activeGradient = "bg-gradient-to-r from-{$item['color']} to-{$item['colorTo']} text-white shadow-md";
                    $linkClass = $isActive
                        ? "flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl {$activeGradient} transition-all duration-300"
                        : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300';
                }
            @endphp
            <a href="{{ $item['url'] }}" class="{{ $linkClass }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    @foreach($iconPaths as $path)
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                    @endforeach
                </svg>
                @endif
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- Divider --}}
        <div class="{{ $isPremium ? 'border-t border-indigo-500/20 my-2' : 'border-t border-gray-200 dark:border-gray-700 my-2' }}"></div>

        {{-- User Section --}}
        @auth
            <a href="/tracking" class="{{ $isPremium ? 'block px-4 py-2.5 text-base font-medium rounded-lg text-indigo-200 hover:bg-white/5 hover:text-white transition-all duration-300' : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl transition-all duration-300 ' . (request()->is('support/tracking*') ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700') }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                @endif
                ติดตามงาน
            </a>
            <a href="{{ route('customer.dashboard') }}" class="{{ $isPremium ? 'block px-4 py-2.5 text-base font-medium rounded-lg text-indigo-200 hover:bg-white/5 hover:text-white transition-all duration-300' : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                @endif
                บัญชีของฉัน
            </a>
            <a href="{{ route('user.wallet.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-{{ $isPremium ? 'lg' : 'xl' }} bg-gradient-to-r from-purple-500 to-indigo-500 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Wallet: {{ number_format(\App\Models\Wallet::getOrCreateForUser(auth()->id())->balance, 0) }} ฿
            </a>
        @endauth
        @guest
            <a href="/tracking" class="{{ $isPremium ? 'block px-4 py-2.5 text-base font-medium rounded-lg text-indigo-200 hover:bg-white/5 hover:text-white transition-all duration-300' : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                @endif
                ติดตามงาน
            </a>
            @if(Route::has('register'))
            <a href="{{ route('register') }}" class="{{ $isPremium ? 'block px-4 py-2.5 text-base font-medium text-indigo-200 hover:bg-white/5 hover:text-white rounded-lg' : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                @endif
                สมัครสมาชิก
            </a>
            @endif
            @if(Route::has('login'))
            <a href="{{ route('login') }}" class="{{ $isPremium ? 'block px-4 py-2.5 text-base font-medium text-white premium-btn rounded-lg text-center' : 'flex items-center gap-3 px-4 py-2.5 text-base font-medium rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-sm' }}">
                @if(!$isPremium)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                @endif
                เข้าสู่ระบบ
            </a>
            @endif
        @endguest
    </div>
</div>
