{{--
    Stop 4 — the product nebula: real rows from $featuredProducts (active,
    on the website, newest six). The card track slides sideways as the
    visitor scrolls on. Only included when there is something to show.
    Pictures: key art drawn for this page where there is some, else the
    product's own (HomeContent::productArt).
--}}
<section id="xu-products" class="xu-st" data-station="products" data-len="2.1"
         data-label-th="เนบิวลาผลิตภัณฑ์" data-label-en="Products" aria-labelledby="xu-products-title">
    <div class="xu-panel xu-products">
        <header class="xu-head">
            <p class="xu-eyebrow xu-r" style="--i: 0;">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                ผลิตภัณฑ์ / Products
            </p>
            <h2 id="xu-products-title" class="xu-h2 xu-r" style="--i: 0.6;">
                ซอฟต์แวร์ที่ <span class="xu-grad">พร้อมใช้งานจริง</span>
            </h2>
            <p class="xu-lede xu-r" style="--i: 1.2;">
                ผลิตภัณฑ์ที่เราพัฒนาและดูแลเอง พร้อมไลเซนส์และการอัปเดตต่อเนื่อง
                <small>Built and maintained in-house — licensed, updated, supported.</small>
            </p>
        </header>

        <div class="xu-track" data-xu-track>
            <div class="xu-track__rail">
                @foreach($featuredProducts as $product)
                    @php
                        $xuAccent = ['#22d3ee', '#8b5cf6', '#e879f9', '#34d399', '#fb7185', '#ffd479'][$loop->index % 6];
                        $xuArt = \App\Support\HomeContent::productArt($product);
                    @endphp
                    <a href="{{ route('products.show', $product->slug) }}"
                       class="xu-card xu-prod xu-r"
                       style="--i: {{ 1.6 + $loop->index * 0.4 }}; --accent: {{ $xuAccent }};">
                        <span class="xu-prod__art">
                            @if($xuArt)
                                <img src="{{ $xuArt }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
                            @else
                                <span class="xu-prod__initials" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->name, 0, 2)) }}</span>
                            @endif
                            @if($product->is_coming_soon)
                                <span class="xu-badge xu-badge--soon">เร็วๆ นี้ / Soon</span>
                            @endif
                        </span>
                        <span class="xu-prod__body">
                            <h3 class="xu-prod__name">{{ $product->name }}</h3>
                            @if($product->short_description)
                                <p class="xu-prod__desc">{{ Str::limit($product->short_description, 110) }}</p>
                            @elseif($product->description)
                                <p class="xu-prod__desc">{{ Str::limit(strip_tags($product->description), 110) }}</p>
                            @else
                                <p class="xu-prod__desc">ดูรายละเอียดผลิตภัณฑ์และแพ็กเกจราคาทั้งหมด</p>
                            @endif
                            <span class="xu-prod__foot">
                                @if($product->is_coming_soon)
                                    <span class="xu-price xu-price--soon">กำลังจะเปิดตัว <small>Coming soon</small></span>
                                @elseif($product->price > 0)
                                    <span class="xu-price">฿{{ number_format($product->price, 0) }} <small>THB</small></span>
                                @else
                                    <span class="xu-price xu-price--free">ฟรี <small>Free</small></span>
                                @endif
                                <span class="xu-go">ดูรายละเอียด / View @include('partials.nova-icon', ['name' => 'arrow'])</span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="xu-center xu-r" style="--i: 4.4;">
            <a href="{{ route('products.index') }}" class="xu-btn xu-btn--ghost">
                <span>ดูผลิตภัณฑ์ทั้งหมด <small>All products</small></span>
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
