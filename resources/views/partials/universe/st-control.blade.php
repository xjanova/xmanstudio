{{--
    Last stop — mission control: the site footer, seen from inside the
    wormhole. Carries every link nova-footer.blade.php does, so the whole
    site stays reachable (and crawlable) from the universe home too.
--}}
<footer id="xu-control" class="xu-st" data-station="control" data-len="1.15"
        data-label-th="ศูนย์ควบคุม" data-label-en="Control" aria-labelledby="xu-control-title">
    <div class="xu-panel xu-control">
        <div class="xu-control__grid">
            <div class="xu-control__brand">
                <h2 id="xu-control-title" class="xu-control__name">XMAN <span class="xu-grad">Studio</span></h2>
                <p>ทีมพัฒนาซอฟต์แวร์และโซลูชั่น IT ครบวงจร<small>Full-stack IT solutions · Bangkok · Est. 2018</small></p>
                <a href="{{ route('quote.index') }}" class="xu-go">ปรึกษาฟรี / Free consultation @include('partials.nova-icon', ['name' => 'arrow'])</a>
            </div>

            <div>
                <h3 class="xu-control__title">บริการ / Services</h3>
                <ul class="xu-control__links">
                    <li><a href="{{ route('services.index') }}">บริการทั้งหมด / All services</a></li>
                    <li><a href="{{ config('app.product_site_url') }}">ผลิตภัณฑ์ / Products</a></li>
                    <li><a href="{{ route('rental.index') }}">เช่าใช้งาน / Rental</a></li>
                    <li><a href="{{ route('domains.index') }}">จดโดเมน / Domains</a></li>
                    <li><a href="{{ route('domains.pricing') }}">ราคาโดเมน / Domain pricing</a></li>
                    <li><a href="{{ route('vps.index') }}">เช่า VPS / VPS hosting</a></li>
                    <li><a href="{{ route('quote.index') }}">ขอใบเสนอราคา / Get a quote</a></li>
                    <li><a href="{{ route('contact.show') }}">ติดต่อเรา / Contact us</a></li>
                    <li><a href="{{ route('portfolio') }}">ผลงาน / Portfolio</a></li>
                </ul>
            </div>

            <div>
                <h3 class="xu-control__title">แพลตฟอร์ม / Platforms</h3>
                <ul class="xu-control__links">
                    <li><a href="{{ config('services.aixman.site_url') }}" target="_blank" rel="noopener noreferrer">XDreamer AI Studio</a></li>
                    <li><a href="{{ config('services.aixman.site_url') }}/gallery" target="_blank" rel="noopener noreferrer">XDreamer Gallery</a></li>
                    <li><a href="{{ route('code-academy') }}">Code Academy</a></li>
                    <li><a href="{{ route('metal-x.index') }}">Metal-X Project</a></li>
                    <li><a href="{{ config('app.product_site_url') }}" target="_blank" rel="noopener noreferrer">BrainX</a></li>
                </ul>
            </div>

            <div>
                <h3 class="xu-control__title">บริษัท / Company</h3>
                <ul class="xu-control__links">
                    <li><a href="{{ route('about') }}">เกี่ยวกับเรา / About</a></li>
                    <li><a href="{{ route('team') }}">ทีมงาน / Team</a></li>
                    <li><a href="{{ route('changelog') }}">อัปเดต / Changelog</a></li>
                    <li><a href="{{ route('tracking') }}">ติดตามงาน / Track order</a></li>
                    <li><a href="{{ route('quote.index') }}">ติดต่อ / Contact</a></li>
                </ul>
            </div>

            <div>
                <h3 class="xu-control__title">บัญชี / Account</h3>
                <ul class="xu-control__links">
                    @auth
                        <li><a href="{{ route('customer.dashboard') }}">แดชบอร์ด / Dashboard</a></li>
                        <li><a href="{{ route('customer.licenses') }}">ไลเซนส์ / Licenses</a></li>
                        <li><a href="{{ route('customer.orders') }}">คำสั่งซื้อ / Orders</a></li>
                        <li><a href="{{ route('customer.domains.index') }}">โดเมนของฉัน / My domains</a></li>
                        <li><a href="{{ route('customer.vps.index') }}">VPS ของฉัน / My servers</a></li>
                        <li><a href="{{ route('user.wallet.index') }}">กระเป๋าเงิน / Wallet</a></li>
                        {{-- customer.affiliate.dashboard is the GET entry point (register is POST-only). --}}
                        <li><a href="{{ route('customer.affiliate.dashboard') }}">ตัวแทนจำหน่าย / Affiliate</a></li>
                    @else
                        <li><a href="{{ route('login') }}">เข้าสู่ระบบ / Log in</a></li>
                        <li><a href="{{ route('register') }}">สมัครสมาชิก / Register</a></li>
                    @endauth
                    <li><a href="{{ route('cart.index') }}">ตะกร้า / Cart</a></li>
                </ul>
            </div>
        </div>

        <div class="xu-control__base">
            <span>&copy; {{ date('Y') }} XMAN Studio. All rights reserved.</span>
            <span class="xu-control__legal">
                <a href="{{ route('terms') }}">ข้อกำหนด / Terms</a>
                <a href="{{ route('privacy') }}">ความเป็นส่วนตัว / Privacy</a>
                <a href="{{ route('sitemap') }}">Sitemap</a>
                <a href="{{ $classicUrl }}" data-xu-classic>หน้าเว็บแบบปกติ / Classic view</a>
            </span>
        </div>
    </div>
</footer>
