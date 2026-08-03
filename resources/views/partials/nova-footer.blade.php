{{-- Nova footer — every link the star menu does not carry lives here, so the
     full site stays reachable (and crawlable) from the home page. --}}
<footer class="nova-footer">
    <div class="nova-shell">
        <div class="nova-footer__grid">
            <div>
                <h2 class="nova-footer__title">XMAN Studio</h2>
                <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:var(--nv-fg-2);max-width:280px;">
                    ทีมพัฒนาซอฟต์แวร์และโซลูชั่น IT ครบวงจร<br>
                    <span style="color:var(--nv-fg-3);">Full-stack IT solutions · Bangkok · Est. 2018</span>
                </p>
                <a href="{{ route('support.index') }}" class="nova-card__cta" style="--nv-accent: var(--nv-cyan);">
                    ปรึกษาฟรี / Free consultation
                    @include('partials.nova-icon', ['name' => 'arrow'])
                </a>
            </div>

            <div>
                <h2 class="nova-footer__title">บริการ / Services</h2>
                <ul class="nova-footer__links">
                    <li><a href="{{ route('services.index') }}">บริการทั้งหมด / All services</a></li>
                    <li><a href="{{ config('app.product_site_url') }}">ผลิตภัณฑ์ / Products</a></li>
                    <li><a href="{{ route('rental.index') }}">เช่าใช้งาน / Rental</a></li>
                    <li><a href="{{ route('quotation.services') }}">ขอใบเสนอราคา / Get a quote</a></li>
                    <li><a href="{{ route('portfolio') }}">ผลงาน / Portfolio</a></li>
                </ul>
            </div>

            <div>
                <h2 class="nova-footer__title">แพลตฟอร์ม / Platforms</h2>
                <ul class="nova-footer__links">
                    <li><a href="{{ route('xdreamer.home') }}">XDreamer AI Studio</a></li>
                    <li><a href="{{ route('xdreamer.gallery') }}">XDreamer Gallery</a></li>
                    <li><a href="{{ route('code-academy') }}">Code Academy</a></li>
                    <li><a href="{{ route('metal-x.index') }}">Metal-X Project</a></li>
                    <li><a href="{{ config('app.product_site_url') }}" target="_blank" rel="noopener noreferrer">BrainX</a></li>
                </ul>
            </div>

            <div>
                <h2 class="nova-footer__title">บริษัท / Company</h2>
                <ul class="nova-footer__links">
                    <li><a href="{{ route('about') }}">เกี่ยวกับเรา / About</a></li>
                    <li><a href="{{ route('team') }}">ทีมงาน / Team</a></li>
                    <li><a href="{{ route('changelog') }}">อัปเดต / Changelog</a></li>
                    <li><a href="{{ route('tracking') }}">ติดตามงาน / Track order</a></li>
                    <li><a href="{{ route('support.index') }}">ติดต่อ / Contact</a></li>
                </ul>
            </div>

            <div>
                <h2 class="nova-footer__title">บัญชี / Account</h2>
                <ul class="nova-footer__links">
                    @auth
                        <li><a href="{{ route('customer.dashboard') }}">แดชบอร์ด / Dashboard</a></li>
                        <li><a href="{{ route('customer.licenses') }}">ไลเซนส์ / Licenses</a></li>
                        <li><a href="{{ route('customer.orders') }}">คำสั่งซื้อ / Orders</a></li>
                        <li><a href="{{ route('user.wallet.index') }}">กระเป๋าเงิน / Wallet</a></li>
                        {{-- customer.affiliate.register is POST-only; dashboard is the GET entry
                             point. Note the group is nested under the `customer.` prefix, so the
                             name is customer.affiliate.dashboard — plain affiliate.dashboard is
                             the ADMIN side and does not exist here. --}}
                        <li><a href="{{ route('customer.affiliate.dashboard') }}">ตัวแทนจำหน่าย / Affiliate</a></li>
                    @else
                        <li><a href="{{ route('login') }}">เข้าสู่ระบบ / Log in</a></li>
                        <li><a href="{{ route('register') }}">สมัครสมาชิก / Register</a></li>
                    @endauth
                    <li><a href="{{ route('cart.index') }}">ตะกร้า / Cart</a></li>
                </ul>
            </div>
        </div>

        <div class="nova-footer__base">
            <span>&copy; {{ date('Y') }} XMAN Studio. All rights reserved.</span>
            <span style="display:flex;gap:18px;flex-wrap:wrap;">
                <a href="{{ route('terms') }}" style="color:var(--nv-fg-3);text-decoration:none;">ข้อกำหนด / Terms</a>
                <a href="{{ route('privacy') }}" style="color:var(--nv-fg-3);text-decoration:none;">ความเป็นส่วนตัว / Privacy</a>
                <a href="{{ route('sitemap') }}" style="color:var(--nv-fg-3);text-decoration:none;">Sitemap</a>
            </span>
        </div>
    </div>
</footer>
