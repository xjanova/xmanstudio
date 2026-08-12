{{--
    Mega menu for the public top bar.

    Why it exists: the bar previously carried 8 destinations, each with an icon
    and a stacked TH+EN label, inside a 64px row. It ran out of room well before
    the viewport did.

    Idle    : the destinations scroll past as a slow marquee, with a hint chip
              telling the visitor to point at it.
    Hover   : the marquee parks and a full panel fades down, split into zones.
    No JS   : Alpine never initialises, x-cloak never clears, so the panel stays
              hidden and the marquee track simply renders as a normal row of
              real links. Nothing here is the only route to a page.
--}}
@php
    // Same artwork set as the rest of the site (public_html/artwork/menu/*).
    $megaExplore = [
        ['th' => 'หน้าหลัก',    'en' => 'Home',      'url' => url('/'),                        'art' => 'home',      'desc' => 'ภาพรวมทั้งหมด'],
        ['th' => 'บริการ',      'en' => 'Services',  'url' => route('services.index'),         'art' => 'services',  'desc' => 'รับพัฒนาครบวงจร'],
        // Keep pointing at the external product site, as the old bar did — the
        // internal catalogue is reachable from the Featured zone below.
        ['th' => 'ผลิตภัณฑ์',   'en' => 'Products',  'url' => config('app.product_site_url'),  'art' => 'products',  'desc' => 'ซอฟต์แวร์พร้อมใช้'],
        ['th' => 'เช่าใช้งาน',  'en' => 'Rentals',   'url' => route('rental.index'),           'art' => 'rental',    'desc' => 'จ่ายรายเดือน'],
        ['th' => 'ผลงาน',       'en' => 'Portfolio', 'url' => url('/portfolio'),               'art' => 'portfolio', 'desc' => 'โปรเจคที่ส่งมอบแล้ว'],
        ['th' => 'ทีมงาน',      'en' => 'Team',      'url' => url('/team'),                    'art' => 'team',      'desc' => 'คนเบื้องหลัง'],
        ['th' => 'ติดต่อ/สั่งซื้อ', 'en' => 'Contact', 'url' => route('support.index'),        'art' => 'support',   'desc' => 'ขอใบเสนอราคา'],
        ['th' => 'ติดตามงาน',   'en' => 'Tracking',  'url' => url('/tracking'),                'art' => 'tracking',  'desc' => 'เช็คสถานะโปรเจค'],
        // Was in the old desktop bar; dropping it would have left donate
        // reachable only from the mobile menu.
        ['th' => 'บริจาค',      'en' => 'Donate',    'url' => url('/apps/aipray/donate'),      'art' => 'donate',    'desc' => 'สนับสนุน Aipray'],
    ];

    // Cached: this renders on every public page load.
    $megaProducts = \Illuminate\Support\Facades\Cache::remember('nav_mega_products', 600, function () {
        return \App\Models\Product::available()->latest()->take(3)->get(['id', 'name', 'slug', 'image', 'price']);
    });
@endphp

{{-- No `hidden lg:block` here on purpose: this element's own rule is unlayered
     and would beat those utilities. The breakpoint is in mega-nav.css. --}}
<div class="xm-mega" x-data="{ open: false, t: null }"
     @mouseenter="clearTimeout(t); open = true"
     @mouseleave="t = setTimeout(() => { if (!$el.contains(document.activeElement)) open = false }, 160)"
     @focusin="clearTimeout(t); open = true"
     @focusout="t = setTimeout(() => open = false, 160)"
     @keydown.escape.window="open = false">

    {{-- Idle strip: real links, scrolling. Duplicated once so the loop meets seamlessly. --}}
    <div class="xm-mega__strip" :class="open && 'is-parked'">
        <div class="xm-mega__track">
            @foreach([1, 2] as $pass)
                @foreach($megaExplore as $item)
                    <a href="{{ $item['url'] }}" class="xm-mega__chip" @if($pass === 2) aria-hidden="true" tabindex="-1" @endif>
                        <span class="xm-mega__chip-th">{{ $item['th'] }}</span>
                        <span class="xm-mega__chip-en">{{ $item['en'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Deliberately a sibling of the strip, not a child: the strip carries a
         right-edge fade mask, and the hint sits at right:0 — inside it, the mask
         would fade away the very affordance telling people to point at it. --}}
    <span class="xm-mega__hint" :class="open && 'is-hidden'" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
        </svg>
        ชี้เพื่อเปิดเมนู
    </span>

    {{-- Expanded panel --}}
    <div class="xm-mega__panel" x-cloak x-show="open"
         x-transition:enter="xm-mega__panel--in"
         x-transition:enter-start="xm-mega__panel--from"
         x-transition:enter-end="xm-mega__panel--to"
         x-transition:leave="xm-mega__panel--out"
         x-transition:leave-start="xm-mega__panel--to"
         x-transition:leave-end="xm-mega__panel--from">

        <div class="xm-mega__art" aria-hidden="true"></div>

        <div class="xm-mega__inner">

            {{-- Zone 1 — destinations, with artwork --}}
            <section class="xm-mega__zone xm-mega__zone--explore">
                <h3 class="xm-mega__title">สำรวจ <span>/ Explore</span></h3>
                <div class="xm-mega__grid">
                    @foreach($megaExplore as $item)
                        <a href="{{ $item['url'] }}" class="xm-mega__card">
                            <span class="xm-mega__card-media">
                                <img src="{{ asset('artwork/menu/' . $item['art'] . '.webp') }}" alt=""
                                     width="208" height="116" loading="lazy" decoding="async">
                            </span>
                            <span class="xm-mega__card-body">
                                <span class="xm-mega__card-th">{{ $item['th'] }}</span>
                                <span class="xm-mega__card-desc">{{ $item['desc'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Zone 2 — the visitor's own things --}}
            <section class="xm-mega__zone xm-mega__zone--account">
                <h3 class="xm-mega__title">บัญชีของฉัน <span>/ My account</span></h3>

                @auth
                    @php
                        // Supplied by layouts/app.blade.php; the fallbacks keep this
                        // partial usable if it is ever included on its own.
                        $megaWallet = $navWallet ?? \App\Models\Wallet::getOrCreateForUser(auth()->id());
                        $megaCartCount = $navCartCount
                            ?? optional(\App\Models\Cart::where('user_id', auth()->id())->first())->items()?->sum('quantity')
                            ?? 0;
                    @endphp

                    <a href="{{ route('user.wallet.index') }}" class="xm-mega__stat xm-mega__stat--wallet">
                        <span class="xm-mega__stat-label">กระเป๋าเงิน / Wallet</span>
                        <span class="xm-mega__stat-value">฿{{ number_format($megaWallet->balance, 0) }}</span>
                    </a>

                    <a href="/cart" class="xm-mega__stat xm-mega__stat--cart">
                        <span class="xm-mega__stat-label">ตะกร้า / Cart</span>
                        <span class="xm-mega__stat-value">{{ $megaCartCount }} <small>รายการ</small></span>
                    </a>

                    <div class="xm-mega__links">
                        <a href="{{ route('customer.orders') }}">ประวัติคำสั่งซื้อ <span>/ Order history</span></a>
                        <a href="{{ route('customer.licenses') }}">ใบอนุญาต <span>/ Licenses</span></a>
                        <a href="{{ route('customer.downloads') }}">ดาวน์โหลด <span>/ Downloads</span></a>
                        <a href="{{ route('customer.dashboard') }}">แดชบอร์ด <span>/ Dashboard</span></a>
                    </div>
                @else
                    <p class="xm-mega__guest">
                        เข้าสู่ระบบเพื่อดูกระเป๋าเงิน ตะกร้า และประวัติการสั่งซื้อของคุณ<br>
                        <span>Sign in to see your wallet, cart and order history.</span>
                    </p>
                    <div class="xm-mega__links">
                        <a href="/cart">ตะกร้า <span>/ Cart</span></a>
                        <a href="{{ url('/tracking') }}">ติดตามงาน <span>/ Track an order</span></a>
                    </div>
                    <div class="xm-mega__auth">
                        <a href="{{ route('login') }}" class="xm-mega__btn xm-mega__btn--primary">เข้าสู่ระบบ / Sign in</a>
                        <a href="{{ route('register') }}" class="xm-mega__btn">สมัคร / Register</a>
                    </div>
                @endauth
            </section>

            {{-- Zone 3 — something to actually buy --}}
            <section class="xm-mega__zone xm-mega__zone--products">
                <h3 class="xm-mega__title">สินค้าแนะนำ <span>/ Featured</span></h3>
                @forelse($megaProducts as $product)
                    <a href="{{ route('products.show', $product->slug) }}" class="xm-mega__product">
                        <span class="xm-mega__product-media">
                            @if($product->artwork_url)
                                <img src="{{ $product->artwork_url }}" alt="" width="208" height="116" loading="lazy" decoding="async">
                            @endif
                        </span>
                        <span class="xm-mega__product-body">
                            <span class="xm-mega__product-name">{{ $product->name }}</span>
                            @if($product->price > 0)
                                <span class="xm-mega__product-price">฿{{ number_format($product->price, 0) }}</span>
                            @endif
                        </span>
                    </a>
                @empty
                    <p class="xm-mega__guest">กำลังเตรียมสินค้าใหม่ <span>/ New products on the way.</span></p>
                @endforelse
                <a href="{{ route('products.index') }}" class="xm-mega__more">ดูทั้งหมด / View all &rarr;</a>
            </section>
        </div>
    </div>
</div>
