{{--
    The command ring — the universe's main menu.

    The same ten destinations as Nova's star menu (HomeContent::menu()), on a
    3D ring that spins with the wheel, a drag, the arrow keys or Tab. Every
    item is a real <a>: the ring is presentation, the links are the menu.
    Below it, the account row that the classic layouts carry in their header.
--}}
@php
    $xuMenu = \App\Support\HomeContent::menu();
@endphp
<div id="xu-menu" class="xu-menu" role="dialog" aria-modal="true" aria-labelledby="xu-menu-title" hidden>
    <div class="xu-menu__veil" data-xu-menu-close aria-hidden="true"></div>

    <div class="xu-menu__top">
        <p id="xu-menu-title" class="xu-menu__title">
            <span class="xu-menu__pulse" aria-hidden="true"></span>
            เลือกจุดหมาย <small>SELECT DESTINATION</small>
        </p>
        <button type="button" class="xu-menu__close" data-xu-menu-close>
            <span aria-hidden="true">✕</span> ปิด <small>ESC</small>
        </button>
    </div>

    <div class="xu-menu__stage" id="xu-menu-stage">
        <div class="xu-ring" id="xu-ring" style="--n: {{ count($xuMenu) }};">
            @foreach($xuMenu as $i => $item)
                <a href="{{ $item['href'] }}"
                   class="xu-ring__item"
                   data-xu-ring="{{ $i }}"
                   data-about-th="{{ $item['about_th'] }}"
                   data-about-en="{{ $item['about_en'] }}"
                   data-desc-th="{{ $item['desc_th'] }}"
                   data-desc-en="{{ $item['desc_en'] }}"
                   data-points="{{ implode('|', $item['points']) }}"
                   aria-description="{{ $item['desc_th'] }}"
                   style="--i: {{ $i }}; --accent: {{ $item['accent'] }};">
                    <span class="xu-ring__card">
                        <img src="{{ asset('artwork/menu/labelled/' . $item['art'] . '.webp') }}"
                             alt="" width="208" height="116" decoding="async">
                        <span class="xu-ring__sheen" aria-hidden="true"></span>
                        <span class="xu-ring__num" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </span>
                    <span class="xu-ring__label">
                        <span class="xu-ring__icon" aria-hidden="true">@include('partials.nova-icon', ['name' => $item['icon']])</span>
                        <b>{{ $item['th'] }}</b>
                        <small>{{ $item['en'] }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- The card in front, explained (ui/Menu.js fills it). Hidden from assistive tech:
         every ring card already carries its name and description itself. --}}
    <div class="xu-menu__info" aria-hidden="true">
        <div class="xu-menu__story">
            <p class="xu-menu__now"><span id="xu-menu-now-th"></span><small id="xu-menu-now-en"></small></p>
            <p class="xu-menu__desc"><span id="xu-menu-desc-th"></span><small id="xu-menu-desc-en"></small></p>
            <ul class="xu-menu__points" id="xu-menu-points"></ul>
        </div>
        <a href="{{ url('/') }}" class="xu-btn xu-btn--primary xu-menu__go" id="xu-menu-go" tabindex="-1">
            <span>ไปที่หน้านี้ <small>Go</small></span>
            @include('partials.nova-icon', ['name' => 'arrow'])
        </a>
        <p class="xu-menu__keys">
            ชี้เมาส์ไปทางซ้ายหรือขวาเพื่อหมุน · ชี้ที่การ์ดตรงกลางให้หยุดอ่าน · <kbd>←</kbd><kbd>→</kbd> ก็ได้
            <small>Point left or right to spin · point at the middle card to stop it · or use the arrow keys</small>
        </p>
    </div>

    <nav class="xu-menu__dock" aria-label="บัญชีและอื่น ๆ / Account and more">
        @auth
            <a href="{{ route('customer.dashboard') }}" class="xu-dock">แดชบอร์ด <small>Dashboard</small></a>
            <a href="{{ route('customer.orders') }}" class="xu-dock">คำสั่งซื้อ <small>Orders</small></a>
            <a href="{{ route('user.wallet.index') }}" class="xu-dock">กระเป๋าเงิน <small>Wallet</small></a>
        @else
            <a href="{{ route('login') }}" class="xu-dock xu-dock--hi">เข้าสู่ระบบ <small>Log in</small></a>
            <a href="{{ route('register') }}" class="xu-dock">สมัครสมาชิก <small>Register</small></a>
        @endauth
        <a href="{{ route('cart.index') }}" class="xu-dock">ตะกร้า <small>Cart</small></a>
        <a href="{{ route('portfolio') }}" class="xu-dock">ผลงาน <small>Portfolio</small></a>
        <a href="{{ route('about') }}" class="xu-dock">เกี่ยวกับเรา <small>About</small></a>
        <a href="{{ $classicUrl }}" class="xu-dock xu-dock--ghost" data-xu-classic>หน้าเว็บแบบปกติ <small>Classic view</small></a>
    </nav>
</div>
