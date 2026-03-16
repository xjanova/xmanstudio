{{-- Public Navigation Right-Side Icons (shared across themes) --}}
{{-- Includes: Dark Mode Toggle, Wallet, Cart, Notifications, User Menu --}}
{{-- Usage: @include('partials.public-nav-icons', ['theme' => 'classic']) --}}
{{-- Usage: @include('partials.public-nav-icons', ['theme' => 'premium']) --}}

@php
    $isPremium = ($theme ?? 'classic') === 'premium';

    // Icon button styles
    $iconBtn = $isPremium
        ? 'p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300'
        : 'p-2 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors';

    // Cart badge
    $cartBadge = $isPremium
        ? 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-rose-500 rounded-full animate-pulse'
        : 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-primary-600 rounded-full';

    // Notification badge (with license warning)
    $notifBadgeLicense = $isPremium
        ? 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-amber-500 to-orange-500 rounded-full animate-pulse'
        : 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 animate-pulse rounded-full';

    // Notification badge (regular)
    $notifBadgeRegular = $isPremium
        ? 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-red-500 to-pink-500 rounded-full animate-pulse'
        : 'absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full';

    // Notification dropdown
    $notifDropdown = $isPremium
        ? 'absolute right-0 mt-2 w-80 premium-card rounded-xl shadow-2xl py-2 z-50'
        : 'absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50';
    $notifHeader = $isPremium
        ? 'px-4 py-2 border-b border-indigo-500/20'
        : 'px-4 py-2 border-b dark:border-gray-700';
    $notifHeaderText = $isPremium
        ? 'text-sm font-semibold text-white'
        : 'text-sm font-semibold text-gray-900 dark:text-white';

    // License alert styles
    $expiredBg = $isPremium
        ? 'block px-4 py-3 hover:bg-white/5 bg-red-500/10 border-l-4 border-red-500'
        : 'block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500';
    $expiredText = $isPremium ? 'text-sm font-medium text-red-400' : 'text-sm font-medium text-red-700 dark:text-red-400';
    $expiredSubText = $isPremium ? 'text-xs text-indigo-300 mt-1' : 'text-xs text-gray-600 dark:text-gray-300 mt-1';
    $expiredNote = $isPremium ? 'text-xs text-red-400 mt-1' : 'text-xs text-red-600 dark:text-red-400 mt-1';

    $expiringBg = $isPremium
        ? 'block px-4 py-3 hover:bg-white/5 bg-amber-500/10 border-l-4 border-amber-500'
        : 'block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500';
    $expiringText = $isPremium ? 'text-sm font-medium text-amber-400' : 'text-sm font-medium text-amber-700 dark:text-amber-400';
    $expiringSubText = $isPremium ? 'text-xs text-indigo-300 mt-1' : 'text-xs text-gray-600 dark:text-gray-300 mt-1';
    $expiringNote = $isPremium ? 'text-xs text-amber-400 mt-1' : 'text-xs text-amber-600 dark:text-amber-400 mt-1';

    // Regular notification
    $notifItem = $isPremium
        ? 'block px-4 py-3 hover:bg-white/5'
        : 'block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700';
    $notifUnread = $isPremium ? 'bg-indigo-500/10' : 'bg-primary-50 dark:bg-primary-900/20';
    $notifText = $isPremium ? 'text-sm text-white' : 'text-sm text-gray-900 dark:text-white';
    $notifTime = $isPremium ? 'text-xs text-indigo-400 mt-1' : 'text-xs text-gray-500 dark:text-gray-400 mt-1';
    $notifEmpty = $isPremium ? 'text-indigo-400' : 'text-gray-500 dark:text-gray-400';
    $notifFooter = $isPremium
        ? 'px-4 py-2 border-t border-indigo-500/20 flex justify-between'
        : 'px-4 py-2 border-t dark:border-gray-700 flex justify-between';
    $notifViewAll = $isPremium
        ? 'text-sm text-indigo-400 hover:text-indigo-200 transition-colors'
        : 'text-sm text-primary-600 dark:text-primary-400 hover:underline';
    $notifViewLicense = $isPremium
        ? 'text-sm text-amber-400 hover:text-amber-200 transition-colors'
        : 'text-sm text-amber-600 dark:text-amber-400 hover:underline';

    // User menu button
    $userMenuBtn = $isPremium
        ? 'flex items-center space-x-2 p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-all duration-300'
        : 'flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200';
    $userAvatar = 'w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold';
    $userAvatarExtra = $isPremium ? '' : 'shadow-md ring-2 ring-white dark:ring-gray-800';
    $userName = $isPremium
        ? 'text-sm font-medium hidden sm:block'
        : 'text-sm font-medium text-gray-700 dark:text-gray-300 hidden sm:block max-w-[100px] truncate';
    $userChevron = $isPremium
        ? 'w-4 h-4'
        : 'w-4 h-4 text-gray-400 transition-transform duration-200';

    // User dropdown
    $userDropdown = $isPremium
        ? 'absolute right-0 mt-2 w-64 premium-card rounded-xl shadow-2xl py-1 z-50 overflow-hidden'
        : 'absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden';
    $userInfoBg = $isPremium
        ? 'px-4 py-3 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 border-b border-indigo-500/20'
        : 'px-4 py-3 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 dark:from-indigo-500/20 dark:to-purple-500/20 border-b border-gray-100 dark:border-gray-700';
    $userInfoName = $isPremium
        ? 'text-sm font-semibold text-white truncate'
        : 'text-sm font-semibold text-gray-900 dark:text-white truncate';
    $userInfoEmail = $isPremium
        ? 'text-xs text-indigo-300 truncate'
        : 'text-xs text-gray-500 dark:text-gray-400 truncate';
    $menuItem = $isPremium
        ? 'flex items-center gap-2.5 px-4 py-2 text-sm text-indigo-200 hover:text-white hover:bg-white/10 transition-colors'
        : 'flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors';
    $logoutDivider = $isPremium
        ? 'border-t border-indigo-500/20 pt-1 mt-1'
        : 'border-t border-gray-100 dark:border-gray-700 pt-1 mt-1';
    $logoutBtn = $isPremium
        ? 'flex items-center gap-2.5 w-full px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-white/10 transition-colors'
        : 'flex items-center gap-2.5 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors';

    // Guest buttons
    $registerBtn = $isPremium
        ? 'hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white transition-colors'
        : 'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all';
    $loginBtn = $isPremium
        ? 'hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-white premium-btn rounded-lg'
        : 'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 rounded-xl transition-all shadow-sm hover:shadow-md';

    // Mobile menu button
    $mobileBtn = $isPremium
        ? 'lg:hidden p-2 text-indigo-300 hover:text-white hover:bg-white/10 rounded-lg transition-colors'
        : 'lg:hidden p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg';
@endphp

{{-- Dark Mode Toggle (classic only) --}}
@if(!$isPremium)
<button id="darkModeToggle" type="button" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" aria-label="Toggle dark mode">
    <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
    </svg>
    <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
    </svg>
</button>
@endif

{{-- Wallet Balance --}}
@auth
@php
    $userWallet = \App\Models\Wallet::getOrCreateForUser(auth()->id());
@endphp
<a href="{{ route('user.wallet.index') }}" class="hidden sm:flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-white hover:from-purple-600 hover:to-indigo-600 transition-all shadow-sm">
    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
    </svg>
    <span>{{ number_format($userWallet->balance, 0) }}</span>
</a>
@endauth

{{-- Cart --}}
@php
    $cartCount = 0;
    if (auth()->check()) {
        $cart = \App\Models\Cart::where('user_id', auth()->id())->first();
    } else {
        $cart = \App\Models\Cart::where('session_id', session()->getId())->first();
    }
    if ($cart) {
        $cartCount = $cart->items()->sum('quantity');
    }
@endphp
<a href="/cart" class="relative {{ $iconBtn }}">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    @if($cartCount > 0)
    <span class="{{ $cartBadge }}">{{ $cartCount }}</span>
    @endif
</a>

{{-- Notifications --}}
@auth
@php
    $unreadNotifications = auth()->user()->unreadNotifications()->count();

    $expiringLicenses = \App\Models\LicenseKey::whereHas('order', function ($query) {
        $query->where('user_id', auth()->id());
    })
    ->where('status', 'active')
    ->where('license_type', '!=', 'lifetime')
    ->where(function ($q) {
        $q->where('expires_at', '<=', now()->addDays(7))
          ->where('expires_at', '>', now());
    })
    ->with('product')
    ->get();

    $expiredLicenses = \App\Models\LicenseKey::whereHas('order', function ($query) {
        $query->where('user_id', auth()->id());
    })
    ->where('status', 'active')
    ->where('license_type', '!=', 'lifetime')
    ->where('expires_at', '<=', now())
    ->with('product')
    ->get();

    $licenseAlertCount = $expiringLicenses->count() + $expiredLicenses->count();
    $totalAlerts = $unreadNotifications + $licenseAlertCount;
@endphp
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative {{ $iconBtn }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($totalAlerts > 0)
        <span class="{{ $licenseAlertCount > 0 ? $notifBadgeLicense : $notifBadgeRegular }}">{{ $totalAlerts > 9 ? '9+' : $totalAlerts }}</span>
        @endif
    </button>
    <div x-show="open" @click.away="open = false" x-cloak class="{{ $notifDropdown }}">
        <div class="{{ $notifHeader }}">
            <h3 class="{{ $notifHeaderText }}">การแจ้งเตือน</h3>
        </div>
        <div class="max-h-80 overflow-y-auto {{ $isPremium ? 'premium-scrollbar' : '' }}">
            @foreach($expiredLicenses as $license)
                <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="{{ $expiredBg }}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 {{ $isPremium ? 'text-red-400' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="{{ $expiredText }}">License หมดอายุแล้ว!</p>
                            <p class="{{ $expiredSubText }}">{{ $license->product->name ?? 'Product' }}</p>
                            <p class="{{ $expiredNote }}">กรุณาต่ออายุเพื่อใช้งานต่อ</p>
                        </div>
                    </div>
                </a>
            @endforeach

            @foreach($expiringLicenses as $license)
                @php $daysLeft = max(0, (int) now()->diffInDays($license->expires_at, false)); @endphp
                <a href="{{ route('products.show', $license->product->slug ?? 'products') }}" class="{{ $expiringBg }}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 {{ $isPremium ? 'text-amber-400' : 'text-amber-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="{{ $expiringText }}">License ใกล้หมดอายุ!</p>
                            <p class="{{ $expiringSubText }}">{{ $license->product->name ?? 'Product' }}</p>
                            <p class="{{ $expiringNote }}">เหลืออีก {{ $daysLeft }} วัน</p>
                        </div>
                    </div>
                </a>
            @endforeach

            @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}" class="{{ $notifItem }} {{ $notification->read_at ? '' : $notifUnread }}">
                    <p class="{{ $notifText }}">{{ $notification->data['message'] ?? 'การแจ้งเตือนใหม่' }}</p>
                    <p class="{{ $notifTime }}">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                @if($licenseAlertCount == 0)
                <div class="px-4 py-6 text-center {{ $notifEmpty }}">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm">ไม่มีการแจ้งเตือน</p>
                </div>
                @endif
            @endforelse
        </div>
        @if(auth()->user()->notifications()->count() > 0 || $licenseAlertCount > 0)
        <div class="{{ $notifFooter }}">
            <a href="{{ route('customer.dashboard') }}" class="{{ $notifViewAll }}">ดูทั้งหมด</a>
            @if($licenseAlertCount > 0)
            <a href="{{ route('products.index') }}" class="{{ $notifViewLicense }}">ดู License</a>
            @endif
        </div>
        @endif
    </div>
</div>
@endauth

{{-- User Menu / Login --}}
@auth
    <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="{{ $userMenuBtn }}">
            <div class="{{ $userAvatar }} {{ $userAvatarExtra }}">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <span class="{{ $userName }}">{{ Auth::user()->name }}</span>
            <svg class="{{ $userChevron }}" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" @click.away="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
             x-cloak
             class="{{ $userDropdown }}">
            {{-- User Info Header --}}
            <div class="{{ $userInfoBg }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="{{ $userInfoName }}">{{ Auth::user()->name }}</p>
                        <p class="{{ $userInfoEmail }}">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Wallet Balance Card --}}
            <a href="{{ route('user.wallet.index') }}" class="block mx-3 my-2 p-3 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 text-white hover:from-purple-600 hover:to-indigo-700 transition-all shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <span class="text-xs opacity-80">Wallet</span>
                    </div>
                    <span class="text-sm font-bold">{{ number_format($userWallet->balance, 0) }} ฿</span>
                </div>
            </a>

            {{-- Menu Items --}}
            <div class="py-1">
                @if(Auth::user()->isAdmin())
                <a href="/admin/rentals" class="{{ $menuItem }}">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Admin Panel
                </a>
                @endif
                <a href="{{ route('customer.dashboard') }}" class="{{ $menuItem }}">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    บัญชีของฉัน
                </a>
                <a href="/support/tracking" class="{{ $menuItem }} {{ request()->is('support/tracking*') ? ($isPremium ? 'bg-white/5' : 'bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-400') : '' }}">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    ติดตามงาน
                </a>
            </div>

            {{-- Logout --}}
            @if(Route::has('logout'))
            <div class="{{ $logoutDivider }}">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $logoutBtn }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        ออกจากระบบ
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
@else
    @if(!$isPremium)
    <div class="hidden sm:flex items-center gap-2">
    @endif
        @if(Route::has('register'))
        <a href="{{ route('register') }}" class="{{ $registerBtn }}">
            @if(!$isPremium)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            @endif
            สมัครสมาชิก
        </a>
        @endif
        @if(Route::has('login'))
        <a href="{{ route('login') }}" class="{{ $loginBtn }}">
            @if(!$isPremium)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            @endif
            เข้าสู่ระบบ
        </a>
        @endif
    @if(!$isPremium)
    </div>
    @endif
@endauth

{{-- Mobile Menu Button --}}
<button id="mobileMenuBtn" type="button" class="{{ $mobileBtn }}" aria-label="Toggle menu" aria-expanded="false">
    <svg id="hamburgerIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
    @if($isPremium)
    <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
    @endif
</button>
