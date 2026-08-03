{{-- Nova products — real rows from the `products` table, passed in by
     HomeController as $featuredProducts (is_active, newest 6).
     The section is skipped entirely when the catalogue is empty so a fresh
     install never renders an empty grid. --}}
@if(isset($featuredProducts) && $featuredProducts->isNotEmpty())
@php
    // Cycle accents so the grid stays varied without hard-coding per product.
    $novaAccents = ['#22d3ee', '#8b5cf6', '#e879f9', '#34d399', '#fb7185', '#ffd479'];
@endphp

<section id="nova-products" class="nova-section">
    <div class="nova-shell">
        <header class="nova-head nova-reveal">
            <span class="nova-eyebrow">
                <span class="nova-eyebrow__dot"></span>
                ผลิตภัณฑ์ / Products
            </span>
            <h2 class="nova-h2">
                ซอฟต์แวร์ที่ <span class="nova-grad">พร้อมใช้งานจริง</span>
            </h2>
            <p class="nova-lede">
                ผลิตภัณฑ์ที่เราพัฒนาและดูแลเอง พร้อมไลเซนส์และการอัปเดตต่อเนื่อง<br>
                <span style="color:var(--nv-fg-3);">Built and maintained in-house — licensed, updated, supported.</span>
            </p>
        </header>

        <div class="nova-grid">
            @foreach($featuredProducts as $product)
                @php $accent = $novaAccents[$loop->index % count($novaAccents)]; @endphp
                <a href="{{ route('products.show', $product->slug) }}"
                   class="nova-card nova-reveal"
                   style="--nv-accent: {{ $accent }}; transition-delay: {{ $loop->index * 0.05 }}s;">

                    @if($product->is_coming_soon)
                        <span class="nova-badge nova-badge--soon">เร็วๆ นี้ / Soon</span>
                    @endif

                    <span class="nova-card__thumb">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}"
                                 alt="{{ $product->name }}"
                                 loading="lazy" decoding="async">
                        @else
                            <span class="nova-card__thumb-fallback" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($product->name, 0, 2)) }}
                            </span>
                        @endif
                    </span>

                    <h3 class="nova-card__title">{{ $product->name }}</h3>

                    @if($product->short_description)
                        <p class="nova-card__body">{{ Str::limit($product->short_description, 110) }}</p>
                    @elseif($product->description)
                        <p class="nova-card__body">{{ Str::limit(strip_tags($product->description), 110) }}</p>
                    @else
                        <p class="nova-card__body">ดูรายละเอียดผลิตภัณฑ์และแพ็กเกจราคาทั้งหมด</p>
                    @endif

                    @if($product->is_coming_soon)
                        <div class="nova-price" style="color:var(--nv-fg-3);font-size:15px;">
                            กำลังจะเปิดตัว <span class="nova-price__unit">Coming soon</span>
                        </div>
                    @elseif($product->price > 0)
                        <div class="nova-price">
                            ฿{{ number_format($product->price, 0) }}
                            <span class="nova-price__unit">THB</span>
                        </div>
                    @else
                        <div class="nova-price" style="color:var(--nv-mint);">
                            ฟรี <span class="nova-price__unit">Free</span>
                        </div>
                    @endif

                    <span class="nova-card__cta">
                        ดูรายละเอียด / View
                        @include('partials.nova-icon', ['name' => 'arrow'])
                    </span>
                </a>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:44px;" class="nova-reveal">
            <a href="{{ route('products.index') }}" class="nova-btn nova-btn--ghost">
                ดูผลิตภัณฑ์ทั้งหมด / All products
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
@endif
