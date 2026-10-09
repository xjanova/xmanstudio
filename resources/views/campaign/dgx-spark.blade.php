@extends($publicLayout ?? 'layouts.app')

{{--
    แคมเปญหลัก: NVIDIA DGX Spark + CluadeX + BrainX Cloud ตลอดชีพ — https://xman4289.com/dgx-spark
    (แอป CluadeX ลิงก์มาที่ path นี้ตรง ๆ ห้ามย้าย)

    กติกาเจ้าของ: ขายในนามเราเท่านั้น ไม่มีลิงก์ไปซื้อร้านอื่น (ลิงก์ JIB เป็นแค่ที่มาของราคาอ้างอิง),
    ไม่ใช้โลโก้ NVIDIA ไม่อ้างเป็นพาร์ตเนอร์, ไม่มีรีวิว/ยอดขาย/ความเร่งด่วนที่แต่งขึ้น — ตัวเลขเหลือกี่ชุดนับจากออเดอร์จริง
    ไม่มีลิงก์ GitHub บนหน้าลูกค้า

    สไตล์อยู่ในหน้านี้ทั้งหมด (คลาส dgx-*) — หน้านี้ไม่พึ่งคลาส Tailwind ที่ต้อง build ใหม่
    ไฟล์ภาพ/วิดีโอ (public_html/images/campaign/dgx-spark/) ยังไม่มี = ไล่สีแทน / ซ่อนบล็อกวิดีโอ
--}}

@use('App\Support\DgxSparkCampaign', 'Dgx')
@php
    $cap = $availability['cap'];
    $sold = $availability['sold'];
    $reserved = $availability['reserved'];
    $remaining = $availability['remaining'];
    $priceText = Dgx::baht($price);
    $referenceText = Dgx::baht($referencePrice);
    $referenceDateText = Dgx::thaiDate($referenceDate);
    $reference = Dgx::config('reference');
    $sources = Dgx::config('sources', []);
    $benchmarks = Dgx::config('benchmarks', []);
    $maxTps = max(array_column($benchmarks, 'tps') ?: [1]);
    $holdHours = Dgx::holdHours();
    $delivery = Dgx::config('delivery_estimate');
    $vatIncluded = Dgx::priceIncludesVat();
    $ogImage = $media['hero'] ?? $media['square'] ?? '';
@endphp

@section('title', 'NVIDIA DGX Spark + CluadeX และ BrainX ตลอดชีพ — ชุดแคมเปญจำกัด ' . $cap . ' ชุด | XMAN Studio')
@section('meta_description', 'ซูเปอร์คอมพิวเตอร์ AI บนโต๊ะ NVIDIA DGX Spark (128 GB) พร้อม CluadeX และ BrainX Cloud แบบตลอดชีพ ' . $priceText . ($vatIncluded ? ' รวม VAT' : '') . ' — รันโมเดลเขียนโค้ดระดับ 120B ในเครื่องของคุณเอง ข้อมูลไม่ออกนอกออฟฟิศ จำกัด ' . $cap . ' ชุด')
@if($ogImage)
    @section('og_image', $ogImage)
@endif

@section('content')
<div class="dgx">

    {{-- ═══════════════════════════ HERO ═══════════════════════════ --}}
    {{-- Phones get the 9:16 story artwork behind the hero when it exists, the 16:9 key visual otherwise --}}
    @php
        $heroStyle = collect([
            $media['hero'] ? "--dgx-hero-img: url('{$media['hero']}')" : null,
            $media['story'] ? "--dgx-hero-img-tall: url('{$media['story']}')" : null,
        ])->filter()->implode('; ');
    @endphp
    <section class="dgx-hero {{ $media['hero'] ? 'has-image' : '' }} {{ $media['story'] ? 'has-story' : '' }}"
             @if($heroStyle) style="{{ $heroStyle }}" @endif
             aria-labelledby="dgx-title">
        <div class="dgx-hero__bg" aria-hidden="true"></div>
        <div class="dgx-wrap dgx-hero__grid">
            <div class="dgx-hero__copy">
                <p class="dgx-eyebrow">
                    <span class="dgx-dot" aria-hidden="true"></span>
                    แคมเปญหลัก · จำกัด {{ $cap }} ชุด
                </p>
                <h1 id="dgx-title" class="dgx-hero__title">
                    ซูเปอร์คอมพิวเตอร์ AI บนโต๊ะคุณ
                    <span class="dgx-grad">พร้อม AI เขียนโค้ด<span class="dgx-nowrap">ที่เป็นของคุณตลอดไป</span></span>
                </h1>
                <p class="dgx-hero__lead">
                    <strong>NVIDIA DGX Spark</strong> (128 GB unified memory) + <strong>CluadeX</strong> ตลอดชีพ
                    + <strong>BrainX Cloud</strong> ตลอดชีพ — รันโมเดลเขียนโค้ดระดับ 120B ในเครื่องของคุณเอง
                    โค้ดไม่ออกนอกออฟฟิศ และไม่มีค่าใช้จ่ายต่อโทเคน
                </p>

                <div class="dgx-hero__price">
                    <div>
                        <span class="dgx-hero__amount">{{ $priceText }}</span>
                        <span class="dgx-hero__vat">{{ $vatIncluded ? 'รวม VAT แล้ว' : '+ VAT 7%' }}</span>
                    </div>
                    <p class="dgx-hero__ref">
                        ราคาอ้างอิงตัวเครื่องที่ {{ $reference['store'] }} {{ $referenceText }} ณ {{ $referenceDateText }}
                    </p>
                </div>

                <div class="dgx-meter dgx-meter--hero" role="img"
                     aria-label="เหลือ {{ $remaining }} จาก {{ $cap }} ชุด">
                    <div class="dgx-meter__head">
                        <span>เหลือ <b>{{ $remaining }}</b> จาก {{ $cap }} ชุด</span>
                        <a href="#stock" class="dgx-meter__more">นับอย่างไร?</a>
                    </div>
                    <div class="dgx-meter__bar" aria-hidden="true">
                        <span class="is-sold" style="width: {{ $cap ? round($sold / $cap * 100, 2) : 0 }}%"></span>
                        <span class="is-reserved" style="width: {{ $cap ? round($reserved / $cap * 100, 2) : 0 }}%"></span>
                    </div>
                </div>

                <div class="dgx-hero__cta">
                    <a href="#order" class="dgx-btn dgx-btn--primary">
                        {{ $closedReason ? 'ดูสถานะการสั่งจอง' : 'สั่งจองชุดนี้' }}
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <a href="#included" class="dgx-btn dgx-btn--ghost">ดูสิ่งที่ได้รับ</a>
                </div>
            </div>

            {{-- The 16:9 key visual fills the hero's right half by itself; without it, the square
                 artwork stands there, and without that, a drawn stand-in --}}
            <div class="dgx-hero__visual {{ $media['hero'] ? 'is-backdrop' : '' }}" aria-hidden="{{ ! $media['hero'] && $media['square'] ? 'false' : 'true' }}">
                @if($media['hero'])
                    {{-- nothing drawn on top of the key visual --}}
                @elseif($media['square'])
                    <img src="{{ $media['square'] }}" alt="NVIDIA DGX Spark พร้อม CluadeX และ BrainX" class="dgx-hero__img" width="640" height="640" fetchpriority="high">
                @else
                    {{-- No key visual yet: a drawn stand-in, not a product photo --}}
                    <div class="dgx-device">
                        <div class="dgx-device__box">
                            <div class="dgx-device__face"></div>
                            <div class="dgx-device__top"></div>
                            <div class="dgx-device__side"></div>
                        </div>
                        <div class="dgx-device__glow"></div>
                        <div class="dgx-device__chips">
                            <span>128 GB</span><span>4 TB</span><span>Blackwell</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════ VIDEO (only when the file exists) ═══════════════════════════ --}}
    @if($media['video'])
        <section class="dgx-section dgx-video" aria-label="วิดีโอแนะนำ">
            <div class="dgx-wrap">
                <video class="dgx-video__player" controls playsinline preload="none"
                       @if($media['poster']) poster="{{ $media['poster'] }}" @endif>
                    <source src="{{ $media['video'] }}" type="video/mp4">
                </video>
            </div>
        </section>
    @endif

    {{-- ═══════════════════════════ WHAT YOU GET ═══════════════════════════ --}}
    <section id="included" class="dgx-section" aria-labelledby="dgx-included-title">
        <div class="dgx-wrap">
            <p class="dgx-kicker">ในชุดมีอะไรบ้าง</p>
            <h2 id="dgx-included-title" class="dgx-h2">ฮาร์ดแวร์ 1 เครื่อง + ซอฟต์แวร์ 2 ตัว แบบไม่มีวันหมดอายุ</h2>

            <div class="dgx-cards">
                <article class="dgx-card dgx-card--hw">
                    <div class="dgx-card__tag">ฮาร์ดแวร์</div>
                    <h3 class="dgx-card__title">NVIDIA DGX Spark</h3>
                    <p class="dgx-card__sub">Leadtek · ประกันศูนย์ไทย 1 ปี ผ่านผู้จัดจำหน่าย</p>
                    <ul class="dgx-list">
                        <li>NVIDIA GB10 Grace Blackwell Superchip</li>
                        <li>128 GB LPDDR5X unified memory — CPU และ GPU ใช้หน่วยความจำก้อนเดียวกัน</li>
                        <li>4 TB NVMe M.2 (self-encryption)</li>
                        <li>ประสิทธิภาพ AI สูงสุด 1 PFLOP (FP4)</li>
                        <li>ขนาดเพียง 15 × 15 ซม. วางบนโต๊ะทำงานได้</li>
                    </ul>
                </article>

                <article class="dgx-card dgx-card--cx">
                    <div class="dgx-card__tag">License ตลอดชีพ</div>
                    <h3 class="dgx-card__title">CluadeX</h3>
                    <p class="dgx-card__sub">
                        AI ผู้ช่วยเขียนโค้ดบน Windows
                        @if($cluadexLifetimePrice)
                            · ปกติ {{ Dgx::baht($cluadexLifetimePrice) }}
                        @endif
                    </p>
                    <ul class="dgx-list">
                        <li>อ่าน แก้ และสร้างไฟล์ในโปรเจกต์ รันคำสั่ง build/test ให้เอง</li>
                        <li>ใช้กับโมเดลที่รันบน DGX Spark ของคุณผ่านเครือข่ายในออฟฟิศ (Ollama / llama.cpp / vLLM)</li>
                        <li>ยังสลับไปใช้ Claude, OpenAI หรือ Gemini ด้วย API key ของคุณเองได้เมื่อต้องการ</li>
                        <li>อัปเดตเวอร์ชันใหม่จาก xman4289.com ตลอดอายุ License</li>
                    </ul>
                </article>

                <article class="dgx-card dgx-card--bx">
                    <div class="dgx-card__tag">License ตลอดชีพ</div>
                    <h3 class="dgx-card__title">BrainX Cloud</h3>
                    <p class="dgx-card__sub">
                        ความจำระยะยาวให้ AI
                        @if($brainxMonthlyPrice)
                            · ปกติ {{ Dgx::baht($brainxMonthlyPrice) }}/เดือน
                        @endif
                    </p>
                    <ul class="dgx-list">
                        <li>เก็บโฟลเดอร์โน้ตที่คุณเลือกไว้ในพื้นที่ส่วนตัวบนคลาวด์ (1 GB)</li>
                        <li>ให้ Claude ใช้ความรู้ของคุณได้ทุกที่ผ่าน Remote MCP</li>
                        <li>ไม่มีค่ารายเดือนอีกเลย — ใช้ได้ตลอดชีพ</li>
                        <li>มีคีย์ BrainX Cloud รายเดือนอยู่แล้ว? ระบบอัปเกรดคีย์เดิมเป็นตลอดชีพ ข้อมูลอยู่ครบ</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════ WHY LOCAL ═══════════════════════════ --}}
    <section class="dgx-section dgx-section--alt" aria-labelledby="dgx-why-title">
        <div class="dgx-wrap">
            <p class="dgx-kicker">CluadeX × DGX Spark</p>
            <h2 id="dgx-why-title" class="dgx-h2">AI เขียนโค้ดที่รันอยู่ในออฟฟิศของคุณ ไม่ใช่บนเซิร์ฟเวอร์ของคนอื่น</h2>

            <div class="dgx-why">
                <div class="dgx-why__item">
                    <span class="dgx-why__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    </span>
                    <h3>ข้อมูลไม่ออกนอกเครือข่าย</h3>
                    <p>ซอร์สโค้ด สัญญา และข้อมูลลูกค้าอยู่บนเครื่องของคุณเอง เหมาะกับงานที่ส่งขึ้นคลาวด์ไม่ได้</p>
                </div>
                <div class="dgx-why__item">
                    <span class="dgx-why__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>
                    </span>
                    <h3>ไม่มีค่าโทเคน</h3>
                    <p>ใช้หนักแค่ไหนก็ไม่มีบิล API ต่อโทเคน — ค่าใช้จ่ายต่อเนื่องมีแค่ค่าไฟของเครื่อง</p>
                </div>
                <div class="dgx-why__item">
                    <span class="dgx-why__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                    </span>
                    <h3>โมเดลใหญ่ในเครื่องเดียว</h3>
                    <p>หน่วยความจำ 128 GB พอสำหรับโมเดลระดับ 120B อย่าง gpt-oss-120b — NVIDIA ระบุว่ารองรับโมเดลได้ถึงราว 200B พารามิเตอร์</p>
                </div>
            </div>

            <ol class="dgx-steps">
                <li><b>ติดตั้งเซิร์ฟเวอร์โมเดล</b> บน DGX Spark เช่น Ollama, llama.cpp หรือ vLLM แล้วโหลดโมเดลที่ต้องการ</li>
                <li><b>ชี้ CluadeX</b> บนพีซี Windows ของคุณไปที่ที่อยู่ของ DGX Spark ในเครือข่าย</li>
                <li><b>สั่งงานเป็นภาษาคน</b> — CluadeX อ่านโปรเจกต์ แก้ไฟล์ และรัน build/test ให้ โดยใช้โมเดลบนเครื่องของคุณ</li>
            </ol>
            <p class="dgx-note">CluadeX เป็นแอปบน Windows ทำงานคู่กับ DGX Spark ผ่านเครือข่าย (DGX Spark ใช้ระบบ NVIDIA DGX OS)</p>
        </div>
    </section>

    {{-- ═══════════════════════════ BENCHMARKS ═══════════════════════════ --}}
    <section id="performance" class="dgx-section" aria-labelledby="dgx-perf-title">
        <div class="dgx-wrap">
            <p class="dgx-kicker">ผลทดสอบจากแหล่งอ้างอิง</p>
            <h2 id="dgx-perf-title" class="dgx-h2">ความเร็วสร้างข้อความบน DGX Spark เครื่องเดียว</h2>
            <p class="dgx-lead">
                ตัวเลขด้านล่างคือผลที่ผู้อื่นวัดและเผยแพร่ไว้ (สร้างคำตอบทีละคำขอ, tokens ต่อวินาที)
                ไม่ใช่ตัวเลขที่เรารับประกัน — ความเร็วจริงขึ้นกับโมเดล การควอนไทซ์ ความยาวบริบท และซอฟต์แวร์ที่ใช้
            </p>

            <div class="dgx-bench" role="table" aria-label="ผลทดสอบความเร็ว">
                <div class="dgx-bench__row dgx-bench__row--head" role="row">
                    <span role="columnheader">โมเดล</span>
                    <span role="columnheader">ซอฟต์แวร์ · ควอนไทซ์</span>
                    <span role="columnheader">tokens/s</span>
                    <span role="columnheader">แหล่งที่มา</span>
                </div>
                @foreach($benchmarks as $row)
                    @php $src = $sources[$row['source']] ?? null; @endphp
                    <div class="dgx-bench__row" role="row">
                        <span role="cell" class="dgx-bench__model">{{ $row['model'] }}</span>
                        <span role="cell" class="dgx-bench__engine">{{ $row['engine'] }} · {{ $row['quant'] }}</span>
                        <span role="cell" class="dgx-bench__tps">
                            <span class="dgx-bench__bar" style="--w: {{ round($row['tps'] / $maxTps * 100, 1) }}%" aria-hidden="true"></span>
                            <b>{{ rtrim(rtrim(number_format($row['tps'], 1), '0'), '.') }}</b>
                        </span>
                        <span role="cell" class="dgx-bench__src">
                            @if($src)
                                <a href="#src-{{ $row['source'] }}">{{ $src['short'] ?? $src['publisher'] }}</a>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>

            <ul class="dgx-sources">
                @foreach($sources as $id => $src)
                    <li id="src-{{ $id }}">
                        <a href="{{ $src['url'] }}" target="_blank" rel="noopener nofollow">{{ $src['title'] }}</a>
                        — {{ $src['publisher'] }}, {{ Dgx::thaiDate(\Carbon\CarbonImmutable::parse($src['date'])) }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ═══════════════════════════ SPECS ═══════════════════════════ --}}
    <section class="dgx-section dgx-section--alt" aria-labelledby="dgx-spec-title">
        <div class="dgx-wrap">
            <p class="dgx-kicker">สเปกเครื่อง</p>
            <h2 id="dgx-spec-title" class="dgx-h2">NVIDIA DGX Spark (Leadtek)</h2>
            <dl class="dgx-spec">
                <div><dt>ชิป</dt><dd>NVIDIA GB10 Grace Blackwell Superchip</dd></div>
                <div><dt>CPU</dt><dd>20-core Arm (10× Cortex-X925 + 10× Cortex-A725)</dd></div>
                <div><dt>GPU</dt><dd>NVIDIA Blackwell architecture</dd></div>
                <div><dt>หน่วยความจำ</dt><dd>128 GB LPDDR5X unified memory</dd></div>
                <div><dt>ประสิทธิภาพ AI</dt><dd>สูงสุด 1 PFLOP (FP4, sparsity)</dd></div>
                <div><dt>พื้นที่เก็บข้อมูล</dt><dd>4 TB NVMe M.2 (self-encryption)</dd></div>
                <div><dt>เครือข่าย</dt><dd>10 GbE (RJ-45), ConnectX-7, Wi-Fi 7, Bluetooth</dd></div>
                <div><dt>พอร์ต</dt><dd>4× USB-C, 1× HDMI 2.1a</dd></div>
                <div><dt>ระบบปฏิบัติการ</dt><dd>NVIDIA DGX OS</dd></div>
                <div><dt>รหัสสินค้า</dt><dd>{{ $reference['item'] }}</dd></div>
                <div><dt>การรับประกัน</dt><dd>1 ปี ศูนย์ไทย ผ่านผู้จัดจำหน่ายในประเทศไทย</dd></div>
            </dl>
            <p class="dgx-note">สเปกตามข้อมูลผู้ผลิตและผู้จัดจำหน่าย อาจเปลี่ยนแปลงตามล็อตสินค้า</p>
        </div>
    </section>

    {{-- ═══════════════════════════ PRICE + STOCK ═══════════════════════════ --}}
    <section id="price" class="dgx-section" aria-labelledby="dgx-price-title">
        <div class="dgx-wrap dgx-price-grid">
            <div class="dgx-price">
                <p class="dgx-kicker">ราคา</p>
                <h2 id="dgx-price-title" class="dgx-h2">{{ $priceText }} <small>{{ $vatIncluded ? 'รวม VAT 7% แล้ว' : '+ VAT 7%' }}</small></h2>

                <dl class="dgx-price__rows">
                    <div>
                        <dt>ราคาอ้างอิงตัวเครื่อง ({{ $reference['store'] }}, ณ {{ $referenceDateText }})</dt>
                        <dd>{{ $referenceText }}</dd>
                    </div>
                    <div>
                        <dt>CluadeX + BrainX Cloud ตลอดชีพ และการจัดหา-จัดส่งถึงมือคุณ</dt>
                        <dd>+ {{ Dgx::baht($markup) }}</dd>
                    </div>
                    <div class="is-total">
                        <dt>ราคาชุดแคมเปญ</dt>
                        <dd>{{ $priceText }}</dd>
                    </div>
                </dl>

                <p class="dgx-note">
                    ตรวจสอบราคาอ้างอิงได้ที่
                    <a href="{{ $reference['url'] }}" target="_blank" rel="noopener nofollow noreferrer">หน้าสินค้าของ {{ $reference['store'] }}</a>
                    (รหัส {{ $reference['item'] }}) — ราคาของผู้จัดจำหน่ายเปลี่ยนได้ เราจึงปรับราคาชุดตามเมื่อราคาอ้างอิงเปลี่ยน
                    ราคาที่คุณจ่ายคือราคาที่แสดงตอนกดสั่งจอง
                </p>
                <ul class="dgx-checks">
                    <li>ชำระด้วยการโอนเงินหรือพร้อมเพย์ ไม่มีค่าธรรมเนียม</li>
                    <li>License ทั้งสองตัวส่งให้ทันทีที่ยืนยันยอดเงิน</li>
                    <li>จัดส่งเครื่องประมาณ {{ $delivery }} หลังยืนยันยอดเงิน</li>
                </ul>
            </div>

            <div id="stock" class="dgx-stock" aria-labelledby="dgx-stock-title">
                <p class="dgx-kicker">จำนวนชุด</p>
                <h3 id="dgx-stock-title" class="dgx-stock__title">เหลือ <b>{{ $remaining }}</b> จาก {{ $cap }} ชุด</h3>
                <div class="dgx-cells" aria-hidden="true">
                    @for($i = 0; $i < $cap; $i++)
                        <span class="{{ $i < $sold ? 'is-sold' : ($i < $sold + $reserved ? 'is-reserved' : '') }}"></span>
                    @endfor
                </div>
                <ul class="dgx-legend">
                    <li><i class="is-sold"></i> ชำระแล้ว {{ $sold }}</li>
                    <li><i class="is-reserved"></i> จองรอชำระ {{ $reserved }}</li>
                    <li><i></i> ว่าง {{ $remaining }}</li>
                </ul>
                <p class="dgx-note">
                    นับจากคำสั่งซื้อจริงในระบบ: ชุดที่ชำระแล้ว และการจองที่ยังอยู่ในเวลาชำระ {{ $holdHours }} ชั่วโมง
                    การจองที่ไม่ชำระภายในเวลา ระบบยกเลิกอัตโนมัติและคืนชุดนั้นให้คนถัดไป
                </p>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════ HOW TO ORDER ═══════════════════════════ --}}
    <section class="dgx-section dgx-section--alt" aria-labelledby="dgx-how-title">
        <div class="dgx-wrap">
            <p class="dgx-kicker">ขั้นตอนการสั่งซื้อ</p>
            <h2 id="dgx-how-title" class="dgx-h2">จ่ายก่อน ได้ License ทันที แล้วรอรับเครื่อง</h2>
            <ol class="dgx-flow">
                <li><span>1</span><b>สั่งจอง</b>กรอกที่อยู่จัดส่งและเลือกช่องทางชำระเงิน ระบบกันชุดไว้ให้คุณ {{ $holdHours }} ชั่วโมง</li>
                <li><span>2</span><b>โอนเงิน + แนบสลิป</b>โอนตามยอดในหน้าคำสั่งซื้อ แล้วแนบสลิปภายในเวลาที่กำหนด</li>
                <li><span>3</span><b>ได้ License ทันที</b>เมื่อเรายืนยันยอด License CluadeX และ BrainX Cloud แสดงในหน้าคำสั่งซื้อและส่งทางอีเมล</li>
                <li><span>4</span><b>รับเครื่อง</b>เราสั่งเครื่องจากผู้จัดจำหน่ายในไทยและจัดส่งให้ภายในประมาณ {{ $delivery }} พร้อมเลขพัสดุ</li>
            </ol>
        </div>
    </section>

    {{-- ═══════════════════════════ ORDER FORM ═══════════════════════════ --}}
    <section id="order" class="dgx-section" aria-labelledby="dgx-order-title">
        <div class="dgx-wrap dgx-order">
            <div class="dgx-order__side">
                <p class="dgx-kicker">สั่งจอง</p>
                <h2 id="dgx-order-title" class="dgx-h2">สั่งจองชุด DGX Spark</h2>
                @if($media['square'])
                    <img src="{{ $media['square'] }}" alt="ชุด NVIDIA DGX Spark พร้อม CluadeX และ BrainX" class="dgx-summary__img" width="480" height="480" loading="lazy">
                @endif
                <div class="dgx-summary">
                    <div class="dgx-summary__row"><span>NVIDIA DGX Spark (Leadtek) 128 GB / 4 TB</span><span>×1</span></div>
                    <div class="dgx-summary__row"><span>CluadeX License ตลอดชีพ</span><span>รวมในชุด</span></div>
                    <div class="dgx-summary__row"><span>BrainX Cloud License ตลอดชีพ</span><span>รวมในชุด</span></div>
                    @if($vatIncluded)
                        <div class="dgx-summary__row is-muted"><span>ราคาก่อน VAT</span><span>฿{{ number_format($totals['subtotal'], 2) }}</span></div>
                        <div class="dgx-summary__row is-muted"><span>VAT 7%</span><span>฿{{ number_format($totals['tax'], 2) }}</span></div>
                    @else
                        <div class="dgx-summary__row is-muted"><span>ราคาชุด</span><span>฿{{ number_format($totals['subtotal'], 2) }}</span></div>
                        <div class="dgx-summary__row is-muted"><span>VAT 7%</span><span>฿{{ number_format($totals['tax'], 2) }}</span></div>
                    @endif
                    <div class="dgx-summary__row is-total"><span>ยอดชำระ</span><span>฿{{ number_format($totals['total'], 2) }}</span></div>
                </div>
                <p class="dgx-note">1 คำสั่งซื้อ = 1 ชุด · ต้องการหลายชุด สั่งชุดถัดไปได้หลังยืนยันยอดชุดแรก หรือ <a href="{{ route('contact.show') }}">ติดต่อเรา</a></p>
            </div>

            <div class="dgx-order__main">
                @if(session('error'))
                    <div class="dgx-alert dgx-alert--error" role="alert">{{ session('error') }}</div>
                @endif

                @if($openOrder)
                    <div class="dgx-panel">
                        <h3 class="dgx-panel__title">คุณมีการจองชุดนี้อยู่แล้ว</h3>
                        <p>คำสั่งซื้อ <b>#{{ $openOrder->order_number }}</b> —
                            @if($openOrder->payment_status === 'pending')
                                รอโอนเงินและแนบสลิปภายใน <b>{{ Dgx::thaiDateTime(Dgx::holdExpiresAt($openOrder)) }}</b>
                            @else
                                เราได้รับสลิปแล้ว กำลังตรวจสอบยอดเงิน
                            @endif
                        </p>
                        <a href="{{ route('orders.show', $openOrder) }}" class="dgx-btn dgx-btn--primary">ไปที่คำสั่งซื้อ</a>
                    </div>
                @elseif($closedReason)
                    <div class="dgx-panel">
                        <h3 class="dgx-panel__title">ยังสั่งจองไม่ได้ในขณะนี้</h3>
                        <p>{{ $closedReason }}</p>
                        <a href="{{ route('contact.show') }}" class="dgx-btn dgx-btn--ghost">ติดต่อสอบถาม</a>
                    </div>
                @elseif(! $user)
                    <div class="dgx-panel">
                        <h3 class="dgx-panel__title">เข้าสู่ระบบเพื่อสั่งจอง</h3>
                        <p>License ของ CluadeX และ BrainX Cloud จะออกให้บัญชีของคุณ และคุณติดตามการชำระเงินและการจัดส่งได้จากหน้าคำสั่งซื้อ</p>
                        <div class="dgx-panel__actions">
                            <a href="{{ route('campaign.dgx-spark', ['signin' => 'login']) }}" class="dgx-btn dgx-btn--primary">เข้าสู่ระบบ</a>
                            <a href="{{ route('campaign.dgx-spark', ['signin' => 'register']) }}" class="dgx-btn dgx-btn--ghost">สมัครสมาชิก</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('campaign.dgx-spark.order') }}" class="dgx-form" novalidate>
                        @csrf
                        <input type="hidden" name="expected_price" value="{{ $price }}">

                        @if($errors->any())
                            <div class="dgx-alert dgx-alert--error" role="alert">
                                <b>กรุณาตรวจสอบข้อมูล</b>
                                <ul>
                                    @foreach($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <fieldset>
                            <legend>ผู้สั่งซื้อและผู้รับสินค้า</legend>
                            <div class="dgx-field">
                                <label for="dgx-name">ชื่อ-นามสกุล</label>
                                <input id="dgx-name" name="customer_name" type="text" autocomplete="name" required maxlength="255"
                                       value="{{ old('customer_name', $user->name) }}">
                            </div>
                            <div class="dgx-field-row">
                                <div class="dgx-field">
                                    <label for="dgx-email">อีเมล</label>
                                    <input id="dgx-email" name="customer_email" type="email" autocomplete="email" required maxlength="255"
                                           value="{{ old('customer_email', $user->email) }}">
                                </div>
                                <div class="dgx-field">
                                    <label for="dgx-phone">เบอร์โทรศัพท์</label>
                                    <input id="dgx-phone" name="customer_phone" type="tel" autocomplete="tel" required maxlength="20" inputmode="tel"
                                           value="{{ old('customer_phone', $user->phone ?? '') }}">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>ที่อยู่จัดส่ง</legend>
                            <div class="dgx-field">
                                <label for="dgx-address">บ้านเลขที่ ถนน แขวง/ตำบล เขต/อำเภอ</label>
                                <textarea id="dgx-address" name="shipping_address" rows="3" autocomplete="street-address" required maxlength="500">{{ old('shipping_address') }}</textarea>
                            </div>
                            <div class="dgx-field-row">
                                <div class="dgx-field">
                                    <label for="dgx-province">จังหวัด</label>
                                    <input id="dgx-province" name="shipping_province" type="text" autocomplete="address-level1" required maxlength="100"
                                           value="{{ old('shipping_province') }}">
                                </div>
                                <div class="dgx-field">
                                    <label for="dgx-postcode">รหัสไปรษณีย์</label>
                                    <input id="dgx-postcode" name="shipping_postcode" type="text" autocomplete="postal-code" required inputmode="numeric" maxlength="5" pattern="[0-9]{5}"
                                           value="{{ old('shipping_postcode') }}">
                                </div>
                            </div>
                        </fieldset>

                        <details class="dgx-company" @if(old('company_name')) open @endif>
                            <summary>ออกเอกสารในนามบริษัท (ไม่บังคับ)</summary>
                            <div class="dgx-field">
                                <label for="dgx-company">ชื่อบริษัท / นิติบุคคล</label>
                                <input id="dgx-company" name="company_name" type="text" maxlength="255" value="{{ old('company_name') }}">
                            </div>
                            <div class="dgx-field-row">
                                <div class="dgx-field">
                                    <label for="dgx-taxid">เลขประจำตัวผู้เสียภาษี (13 หลัก)</label>
                                    <input id="dgx-taxid" name="company_tax_id" type="text" inputmode="numeric" maxlength="17" value="{{ old('company_tax_id') }}">
                                </div>
                                <div class="dgx-field">
                                    <label for="dgx-branch">สาขา</label>
                                    <input id="dgx-branch" name="company_branch" type="text" maxlength="100" placeholder="สำนักงานใหญ่" value="{{ old('company_branch') }}">
                                </div>
                            </div>
                            <div class="dgx-field">
                                <label for="dgx-company-address">ที่อยู่บริษัท (ถ้าต่างจากที่อยู่จัดส่ง)</label>
                                <textarea id="dgx-company-address" name="company_address" rows="2" maxlength="500">{{ old('company_address') }}</textarea>
                            </div>
                        </details>

                        <fieldset>
                            <legend>ช่องทางชำระเงิน</legend>
                            <div class="dgx-pay">
                                @foreach($paymentMethods as $method)
                                    <label class="dgx-pay__opt">
                                        <input type="radio" name="payment_method" value="{{ $method['id'] }}" required
                                               @checked(old('payment_method', $paymentMethods[0]['id']) === $method['id'])>
                                        <span>
                                            <b>{{ $method['name'] }}</b>
                                            <small>{{ $method['id'] === 'promptpay' ? 'สแกน QR ยอดตรงในหน้าคำสั่งซื้อ' : 'โอนเข้าบัญชีที่แสดงในหน้าคำสั่งซื้อ' }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="dgx-note">ชุดแคมเปญรับเฉพาะการโอนเงินและพร้อมเพย์ — ยอดสูง อาจต้องปรับวงเงินโอนต่อวันในแอปธนาคารก่อน</p>
                        </fieldset>

                        <div class="dgx-field">
                            <label for="dgx-notes">หมายเหตุถึงเรา (ไม่บังคับ)</label>
                            <textarea id="dgx-notes" name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea>
                        </div>

                        <label class="dgx-terms">
                            <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms'))>
                            <span>
                                ฉันอ่านและยอมรับเงื่อนไขใน <a href="#faq">คำถามที่พบบ่อย</a> (การจัดส่ง การรับประกัน การยกเลิกและคืนเงิน)
                                และเข้าใจว่าต้องโอนเงินและแนบสลิปภายใน {{ $holdHours }} ชั่วโมง มิฉะนั้นการจองจะถูกยกเลิก
                            </span>
                        </label>

                        <x-turnstile section="checkout" />

                        <button type="submit" class="dgx-btn dgx-btn--primary dgx-btn--block">
                            สั่งจอง — ชำระ {{ $priceText }}
                        </button>
                        <p class="dgx-note dgx-note--center">ยังไม่ตัดเงินตอนนี้ ระบบจะพาไปหน้าคำสั่งซื้อพร้อมรายละเอียดการโอน</p>
                    </form>
                @endif
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════ FAQ ═══════════════════════════ --}}
    <section id="faq" class="dgx-section dgx-section--alt" aria-labelledby="dgx-faq-title">
        <div class="dgx-wrap dgx-faq">
            <p class="dgx-kicker">คำถามที่พบบ่อย</p>
            <h2 id="dgx-faq-title" class="dgx-h2">ก่อนสั่งจอง</h2>

            <details>
                <summary>การรับประกันเป็นอย่างไร</summary>
                <p>ตัวเครื่องรับประกัน 1 ปีโดยศูนย์ในประเทศไทยผ่านผู้จัดจำหน่าย นับจากวันที่ซื้อ หากเครื่องมีปัญหา แจ้งเราพร้อมเลขคำสั่งซื้อ เราช่วยประสานการเคลมให้ ส่วน License ของ CluadeX และ BrainX Cloud เป็นแบบตลอดชีพ ไม่มีวันหมดอายุ</p>
            </details>
            <details>
                <summary>ได้รับเครื่องเมื่อไร</summary>
                <p>หลังเรายืนยันยอดเงิน เราสั่งเครื่องจากผู้จัดจำหน่ายในไทยทันที และจัดส่งถึงที่อยู่ของคุณภายในประมาณ {{ $delivery }} (ขึ้นกับสต็อกของผู้จัดจำหน่าย) สถานะการจัดส่งและเลขพัสดุดูได้ในหน้าคำสั่งซื้อ หากผู้จัดจำหน่ายส่งของไม่ได้ภายใน 30 วัน คุณเลือกได้ว่าจะรอต่อหรือรับเงินคืนเต็มจำนวน</p>
            </details>
            <details>
                <summary>ได้รับ License อย่างไร และเปิดใช้งานอย่างไร</summary>
                <p>ทันทีที่ยืนยันยอดเงิน ระบบออก License Key ของ CluadeX และ BrainX Cloud ให้บัญชีของคุณ แสดงในหน้าคำสั่งซื้อ ในหน้า <a href="{{ route('customer.licenses') }}">License ของฉัน</a> และส่งทางอีเมล ดาวน์โหลด CluadeX ได้จาก <a href="{{ route('cluadex.detail') }}">หน้า CluadeX</a> บน xman4289.com แล้วใส่คีย์ในแอป ส่วน BrainX Cloud ใส่คีย์ในแอป BrainX เพื่อเชื่อมพื้นที่คลาวด์ของคุณ ถ้าบัญชีคุณมีคีย์ BrainX Cloud รายเดือนอยู่แล้ว ระบบจะอัปเกรดคีย์เดิมนั้นเป็นตลอดชีพ ข้อมูลบนคลาวด์อยู่ครบ</p>
            </details>
            <details>
                <summary>ชำระเงินช่องทางไหนได้บ้าง ผ่อนได้ไหม</summary>
                <p>ชุดแคมเปญรับเฉพาะการโอนเงินเข้าบัญชีและพร้อมเพย์ ยอดตามหน้าคำสั่งซื้อ ไม่มีค่าธรรมเนียม ยังไม่รองรับบัตรเครดิตและการผ่อนชำระ เพราะค่าธรรมเนียมบัตรสำหรับยอดนี้สูงเกินกว่าจะรวมไว้ในราคาได้ ราคาที่แสดง{{ $vatIncluded ? 'รวมภาษีมูลค่าเพิ่ม 7% แล้ว' : 'ยังไม่รวมภาษีมูลค่าเพิ่ม 7%' }}</p>
            </details>
            <details>
                <summary>ต้องโอนภายในเมื่อไร ถ้าไม่ทันจะเป็นอย่างไร</summary>
                <p>หลังกดสั่งจอง ระบบกันชุดไว้ให้คุณ {{ $holdHours }} ชั่วโมง โอนเงินและแนบสลิปในหน้าคำสั่งซื้อภายในเวลานั้น หากเลยเวลา การจองถูกยกเลิกอัตโนมัติและชุดนั้นกลับไปให้คนถัดไป ถ้าคุณโอนเงินไปแล้วแต่แนบสลิปไม่ทัน ติดต่อเราพร้อมเลขคำสั่งซื้อ — ถ้ายังมีชุดว่างเรายืนยันให้ ถ้าชุดเต็มแล้วเราคืนเงินเต็มจำนวน</p>
            </details>
            <details>
                <summary>ยกเลิกหรือขอคืนเงินได้ไหม</summary>
                <p>ก่อนโอนเงิน: ไม่ต้องทำอะไร การจองจะหมดเวลาเอง · หลังชำระแล้วแต่เรายังไม่ได้สั่งเครื่องจากผู้จัดจำหน่าย: ขอยกเลิกได้และรับเงินคืนเต็มจำนวน (License ในชุดจะถูกยกเลิก) · หลังสั่งเครื่องแล้ว: ยกเลิกไม่ได้ เว้นแต่สินค้ามีปัญหาซึ่งเป็นไปตามเงื่อนไขการรับประกัน · ได้รับเครื่องเสียหายจากการขนส่ง แจ้งเราภายใน 7 วันพร้อมรูปถ่าย ทั้งนี้ไม่ตัดสิทธิ์ของผู้บริโภคตามกฎหมาย</p>
            </details>
            <details>
                <summary>ซื้อเฉพาะเครื่อง หรือเฉพาะ License ได้ไหม</summary>
                <p>แคมเปญนี้ขายเป็นชุดเท่านั้น ส่วน CluadeX และ BrainX Cloud ยังซื้อแยกได้ตามปกติในหน้าสินค้าของแต่ละตัว</p>
            </details>
            <details>
                <summary>ใช้ CluadeX บน Mac หรือ Linux ได้ไหม</summary>
                <p>CluadeX เป็นแอปบน Windows ใช้คู่กับ DGX Spark ผ่านเครือข่าย (DGX Spark รันโมเดล ส่วน CluadeX ทำงานบนพีซีของคุณ)</p>
            </details>
            <details>
                <summary>ต้องการใบเสร็จหรือเอกสารในนามบริษัท</summary>
                <p>กรอกข้อมูลบริษัทในแบบฟอร์มสั่งจอง ใบเสร็จรับเงินดาวน์โหลดได้ในหน้าคำสั่งซื้อหลังยืนยันยอด หากต้องการเอกสารเพิ่มเติม ติดต่อเราได้</p>
            </details>
        </div>
    </section>

    <footer class="dgx-legal">
        <div class="dgx-wrap">
            <p>NVIDIA, DGX และ DGX Spark เป็นเครื่องหมายการค้าของ NVIDIA Corporation · XMAN Studio ไม่ได้เป็นตัวแทนหรือพันธมิตรของ NVIDIA — เครื่องในชุดนี้จัดหาจากผู้จัดจำหน่ายในประเทศไทยพร้อมประกันศูนย์</p>
            <p>ชื่อผลิตภัณฑ์และเครื่องหมายการค้าอื่นเป็นของเจ้าของแต่ละราย · ผลทดสอบความเร็วเป็นข้อมูลจากแหล่งอ้างอิงภายนอก</p>
        </div>
    </footer>
</div>
@endsection

@push('styles')
<style>
    .dgx {
        --dgx-bg: #05070c;
        --dgx-bg-2: #0a0e18;
        --dgx-panel: rgba(255, 255, 255, .035);
        --dgx-line: rgba(255, 255, 255, .09);
        --dgx-fg: #eef2ff;
        --dgx-fg-2: #b4bdd3;
        --dgx-fg-3: #7d88a3;
        --dgx-cyan: #22d3ee;
        --dgx-violet: #8b5cf6;
        --dgx-gold: #f5c86b;
        --dgx-green: #34d399;
        --dgx-red: #fb7185;
        --dgx-radius: 18px;
        background: var(--dgx-bg);
        color: var(--dgx-fg);
        overflow-x: clip;
        line-height: 1.65;
    }
    .dgx *, .dgx *::before, .dgx *::after { box-sizing: border-box; }
    .dgx a { color: var(--dgx-cyan); text-underline-offset: 3px; }
    .dgx-wrap { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
    .dgx-section { padding: clamp(56px, 9vw, 104px) 0; border-top: 1px solid var(--dgx-line); scroll-margin-top: 72px; }
    .dgx [id] { scroll-margin-top: 88px; }
    .dgx-nowrap { white-space: nowrap; }
    .dgx-section--alt { background: var(--dgx-bg-2); }
    .dgx-kicker { margin: 0 0 10px; color: var(--dgx-cyan); font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .dgx-h2 { margin: 0 0 22px; font-size: clamp(26px, 3.6vw, 40px); line-height: 1.22; font-weight: 700; letter-spacing: -.01em; color: #fff; max-width: 30ch; }
    .dgx-h2 small { font-size: .45em; font-weight: 600; color: var(--dgx-fg-2); letter-spacing: 0; }
    .dgx-lead { max-width: 70ch; color: var(--dgx-fg-2); margin: 0 0 28px; }
    .dgx-note { color: var(--dgx-fg-3); font-size: 13.5px; margin: 14px 0 0; }
    .dgx-note--center { text-align: center; }
    .dgx-grad { background: linear-gradient(95deg, var(--dgx-cyan), var(--dgx-violet) 55%, #e879f9); -webkit-background-clip: text; background-clip: text; color: transparent; }

    /* buttons */
    .dgx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 24px; border-radius: 999px; font-weight: 700; font-size: 15.5px; text-decoration: none !important; cursor: pointer; border: 1px solid transparent; transition: transform .2s ease, box-shadow .2s ease, background .2s ease; }
    .dgx-btn svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    .dgx-btn--primary { background: linear-gradient(95deg, #22d3ee, #6366f1); color: #05070c !important; box-shadow: 0 12px 34px -12px rgba(34, 211, 238, .7); }
    .dgx-btn--primary:hover, .dgx-btn--primary:focus-visible { transform: translateY(-1px); box-shadow: 0 16px 40px -10px rgba(99, 102, 241, .8); }
    .dgx-btn--ghost { background: rgba(255, 255, 255, .04); border-color: var(--dgx-line); color: var(--dgx-fg) !important; }
    .dgx-btn--ghost:hover, .dgx-btn--ghost:focus-visible { background: rgba(255, 255, 255, .09); }
    .dgx-btn--block { width: 100%; }
    .dgx-btn:focus-visible, .dgx a:focus-visible, .dgx summary:focus-visible { outline: 2px solid var(--dgx-cyan); outline-offset: 3px; }

    /* hero */
    .dgx-hero { position: relative; padding: clamp(48px, 8vw, 96px) 0 clamp(56px, 8vw, 100px); isolation: isolate; }
    .dgx-hero__bg { position: absolute; inset: 0; z-index: -1;
        background:
            radial-gradient(60% 70% at 85% 30%, rgba(139, 92, 246, .28), transparent 70%),
            radial-gradient(50% 60% at 10% 10%, rgba(34, 211, 238, .18), transparent 70%),
            radial-gradient(40% 50% at 70% 100%, rgba(245, 200, 107, .12), transparent 70%),
            linear-gradient(180deg, #070a14, #05070c); }
    .dgx-hero__bg::after { content: ""; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(70% 70% at 50% 40%, #000, transparent); -webkit-mask-image: radial-gradient(70% 70% at 50% 40%, #000, transparent); }
    .dgx-hero.has-image .dgx-hero__bg { background: linear-gradient(90deg, rgba(5, 7, 12, .94) 0%, rgba(5, 7, 12, .78) 45%, rgba(5, 7, 12, .35) 100%), var(--dgx-hero-img) center / cover no-repeat, #05070c; }
    .dgx-hero.has-image .dgx-hero__bg::after { display: none; }
    @media (max-width: 640px) {
        .dgx-hero.has-story .dgx-hero__bg { background: linear-gradient(180deg, rgba(5, 7, 12, .55) 0%, rgba(5, 7, 12, .88) 55%, #05070c 100%), var(--dgx-hero-img-tall) center top / cover no-repeat, #05070c; }
        .dgx-hero.has-story .dgx-hero__bg::after { display: none; }
    }
    .dgx-hero__grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: clamp(28px, 5vw, 64px); align-items: center; }
    .dgx-eyebrow { display: inline-flex; align-items: center; gap: 10px; margin: 0 0 18px; padding: 6px 14px; border-radius: 999px; border: 1px solid rgba(245, 200, 107, .35); background: rgba(245, 200, 107, .08); color: var(--dgx-gold); font-size: 13.5px; font-weight: 700; }
    .dgx-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--dgx-gold); box-shadow: 0 0 12px var(--dgx-gold); }
    .dgx-hero__title { margin: 0 0 18px; font-size: clamp(32px, 5.2vw, 60px); line-height: 1.14; font-weight: 800; letter-spacing: -.02em; color: #fff; }
    .dgx-hero__title .dgx-grad { display: block; font-size: .62em; line-height: 1.3; margin-top: 8px; }
    .dgx-hero__lead { margin: 0 0 26px; max-width: 58ch; color: var(--dgx-fg-2); font-size: clamp(15.5px, 1.5vw, 18px); }
    .dgx-hero__lead strong { color: #fff; }
    .dgx-hero__price { display: flex; flex-direction: column; gap: 2px; margin: 0 0 18px; }
    .dgx-hero__amount { font-size: clamp(34px, 4.6vw, 52px); font-weight: 800; letter-spacing: -.02em; color: var(--dgx-gold); line-height: 1.1; }
    .dgx-hero__vat { margin-left: 10px; font-size: 14px; color: var(--dgx-fg-2); font-weight: 600; }
    .dgx-hero__ref { margin: 0; color: var(--dgx-fg-3); font-size: 13.5px; }
    .dgx-hero__cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 22px; }
    .dgx-hero__visual { display: flex; justify-content: center; }
    .dgx-hero__visual.is-backdrop { min-height: 1px; }
    .dgx-hero__img { width: 100%; max-width: 520px; height: auto; aspect-ratio: 1; object-fit: cover; border-radius: 24px; border: 1px solid var(--dgx-line); box-shadow: 0 40px 90px -40px rgba(139, 92, 246, .7); }

    /* drawn stand-in for the device (no key visual yet) */
    .dgx-device { position: relative; width: min(100%, 380px); aspect-ratio: 1; display: grid; place-items: center; }
    .dgx-device__box { position: relative; width: 62%; aspect-ratio: 1 / .62; transform: perspective(900px) rotateX(18deg) rotateY(-24deg); transform-style: preserve-3d; }
    .dgx-device__face { position: absolute; inset: 0; border-radius: 14px; background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,.35) 0 1.4px, transparent 1.6px) 0 0 / 9px 9px,
        linear-gradient(135deg, #f7dfa6, #c9a35a 45%, #8a6a2e); box-shadow: inset 0 0 0 1px rgba(255,255,255,.25), 0 30px 60px -20px rgba(0,0,0,.8); }
    .dgx-device__top { position: absolute; left: 0; right: 0; top: -14%; height: 14%; border-radius: 10px 10px 0 0; background: linear-gradient(180deg, #3b3f4a, #1d2028); transform-origin: bottom; transform: rotateX(70deg); }
    .dgx-device__side { position: absolute; top: 0; bottom: 0; right: -9%; width: 9%; border-radius: 0 10px 10px 0; background: linear-gradient(90deg, #2a2d36, #15171d); transform-origin: left; transform: rotateY(70deg); }
    .dgx-device__glow { position: absolute; inset: 18% 10% 6%; z-index: -1; border-radius: 50%; background: radial-gradient(closest-side, rgba(139, 92, 246, .55), rgba(34, 211, 238, .2), transparent); filter: blur(18px); }
    .dgx-device__chips { position: absolute; bottom: 8%; display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
    .dgx-device__chips span { padding: 4px 12px; border-radius: 999px; background: rgba(5, 7, 12, .7); border: 1px solid var(--dgx-line); font-size: 12.5px; font-weight: 700; color: var(--dgx-fg-2); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    /* stock meter */
    .dgx-meter { max-width: 460px; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.04); border: 1px solid var(--dgx-line); }
    .dgx-meter__head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 14.5px; color: var(--dgx-fg-2); margin-bottom: 10px; }
    .dgx-meter__head b { color: #fff; font-size: 20px; }
    .dgx-meter__more { font-size: 13px; }
    .dgx-meter__bar { display: flex; height: 8px; border-radius: 999px; background: rgba(255,255,255,.08); overflow: hidden; }
    .dgx-meter__bar .is-sold { background: var(--dgx-gold); }
    .dgx-meter__bar .is-reserved { background: repeating-linear-gradient(45deg, rgba(245,200,107,.75) 0 4px, rgba(245,200,107,.35) 4px 8px); }

    /* video */
    .dgx-video__player { display: block; width: 100%; max-height: 80vh; border-radius: var(--dgx-radius); border: 1px solid var(--dgx-line); background: #000; }

    /* included cards */
    .dgx-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
    .dgx-card { position: relative; padding: 26px 24px; border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); overflow: hidden; }
    .dgx-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 3px; background: var(--dgx-accent, var(--dgx-cyan)); }
    .dgx-card--hw { --dgx-accent: linear-gradient(90deg, var(--dgx-gold), #e8a33d); }
    .dgx-card--cx { --dgx-accent: linear-gradient(90deg, var(--dgx-cyan), #6366f1); }
    .dgx-card--bx { --dgx-accent: linear-gradient(90deg, var(--dgx-violet), #e879f9); }
    .dgx-card__tag { display: inline-block; margin-bottom: 12px; padding: 3px 10px; border-radius: 999px; background: rgba(255,255,255,.06); color: var(--dgx-fg-2); font-size: 12px; font-weight: 700; }
    .dgx-card__title { margin: 0; font-size: 22px; color: #fff; }
    .dgx-card__sub { margin: 4px 0 16px; color: var(--dgx-fg-3); font-size: 14px; }
    .dgx-list { margin: 0; padding: 0; list-style: none; display: grid; gap: 10px; }
    .dgx-list li { position: relative; padding-left: 24px; color: var(--dgx-fg-2); font-size: 15px; }
    .dgx-list li::before { content: ""; position: absolute; left: 2px; top: .55em; width: 12px; height: 7px; border-left: 2px solid var(--dgx-green); border-bottom: 2px solid var(--dgx-green); transform: rotate(-45deg); }

    /* why local */
    .dgx-why { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-bottom: 32px; }
    .dgx-why__item { padding: 22px; border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); }
    .dgx-why__item h3 { margin: 12px 0 6px; font-size: 18px; color: #fff; }
    .dgx-why__item p { margin: 0; color: var(--dgx-fg-2); font-size: 15px; }
    .dgx-why__icon { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; background: rgba(34, 211, 238, .1); color: var(--dgx-cyan); }
    .dgx-why__icon svg { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .dgx-steps { margin: 0; padding: 0; list-style: none; counter-reset: dgx; display: grid; gap: 12px; max-width: 820px; }
    .dgx-steps li { counter-increment: dgx; position: relative; padding: 14px 16px 14px 58px; border-radius: 14px; background: rgba(255,255,255,.025); border: 1px dashed var(--dgx-line); color: var(--dgx-fg-2); }
    .dgx-steps li::before { content: counter(dgx); position: absolute; left: 16px; top: 13px; width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: rgba(139, 92, 246, .2); color: #c4b5fd; font-weight: 800; font-size: 14px; }
    .dgx-steps b { color: #fff; }

    /* benchmarks */
    .dgx-bench { border: 1px solid var(--dgx-line); border-radius: var(--dgx-radius); overflow: hidden; }
    .dgx-bench__row { display: grid; grid-template-columns: 1.2fr 1fr 1.6fr 1.2fr; gap: 14px; align-items: center; padding: 14px 18px; border-top: 1px solid var(--dgx-line); }
    .dgx-bench__row--head { border-top: 0; background: rgba(255,255,255,.04); color: var(--dgx-fg-3); font-size: 12.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .dgx-bench__model { font-weight: 700; color: #fff; }
    .dgx-bench__engine { color: var(--dgx-fg-2); font-size: 14.5px; }
    .dgx-bench__tps { display: flex; align-items: center; gap: 10px; }
    .dgx-bench__bar { flex: 1; height: 10px; border-radius: 999px; background: rgba(255,255,255,.06); position: relative; overflow: hidden; }
    .dgx-bench__bar::after { content: ""; position: absolute; inset: 0 auto 0 0; width: var(--w); border-radius: inherit; background: linear-gradient(90deg, var(--dgx-cyan), var(--dgx-violet)); }
    .dgx-bench__tps b { min-width: 3.2em; text-align: right; font-variant-numeric: tabular-nums; color: #fff; }
    .dgx-bench__src { font-size: 13.5px; }
    .dgx-sources { margin: 18px 0 0; padding-left: 18px; color: var(--dgx-fg-3); font-size: 13.5px; display: grid; gap: 6px; }

    /* specs */
    .dgx-spec { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 32px; margin: 0; }
    .dgx-spec > div { display: grid; grid-template-columns: 9.5em 1fr; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--dgx-line); }
    .dgx-spec dt { color: var(--dgx-fg-3); font-size: 14px; }
    .dgx-spec dd { margin: 0; color: var(--dgx-fg); font-size: 15px; }

    /* price + stock */
    .dgx-price-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: clamp(24px, 4vw, 48px); align-items: start; }
    .dgx-price__rows { margin: 0; border: 1px solid var(--dgx-line); border-radius: var(--dgx-radius); overflow: hidden; }
    .dgx-price__rows > div { display: flex; justify-content: space-between; gap: 16px; padding: 14px 18px; border-top: 1px solid var(--dgx-line); }
    .dgx-price__rows > div:first-child { border-top: 0; }
    .dgx-price__rows dt { color: var(--dgx-fg-2); font-size: 15px; }
    .dgx-price__rows dd { margin: 0; font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .dgx-price__rows .is-total { background: rgba(245, 200, 107, .08); }
    .dgx-price__rows .is-total dt { color: #fff; font-weight: 700; }
    .dgx-price__rows .is-total dd { color: var(--dgx-gold); font-size: 20px; }
    .dgx-checks { margin: 18px 0 0; padding: 0; list-style: none; display: grid; gap: 8px; }
    .dgx-checks li { position: relative; padding-left: 24px; color: var(--dgx-fg-2); }
    .dgx-checks li::before { content: ""; position: absolute; left: 2px; top: .55em; width: 12px; height: 7px; border-left: 2px solid var(--dgx-green); border-bottom: 2px solid var(--dgx-green); transform: rotate(-45deg); }
    .dgx-stock { padding: 26px; border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); }
    .dgx-stock__title { margin: 0 0 16px; font-size: 22px; color: var(--dgx-fg-2); font-weight: 600; }
    .dgx-stock__title b { color: #fff; font-size: 34px; }
    .dgx-cells { display: grid; grid-template-columns: repeat(10, 1fr); gap: 6px; }
    .dgx-cells span { aspect-ratio: 1; border-radius: 6px; border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.03); }
    .dgx-cells .is-sold { background: var(--dgx-gold); border-color: var(--dgx-gold); }
    .dgx-cells .is-reserved { background: repeating-linear-gradient(45deg, rgba(245,200,107,.7) 0 4px, rgba(245,200,107,.25) 4px 8px); border-color: rgba(245,200,107,.6); }
    .dgx-legend { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 14px 0 0; padding: 0; list-style: none; font-size: 13.5px; color: var(--dgx-fg-2); }
    .dgx-legend i { display: inline-block; width: 12px; height: 12px; margin-right: 6px; vertical-align: -1px; border-radius: 3px; border: 1px solid rgba(255,255,255,.25); }
    .dgx-legend i.is-sold { background: var(--dgx-gold); border-color: var(--dgx-gold); }
    .dgx-legend i.is-reserved { background: repeating-linear-gradient(45deg, rgba(245,200,107,.7) 0 3px, rgba(245,200,107,.25) 3px 6px); }

    /* how to order */
    .dgx-flow { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .dgx-flow li { position: relative; padding: 22px 20px; border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); color: var(--dgx-fg-2); font-size: 14.5px; }
    .dgx-flow li span { display: grid; place-items: center; width: 34px; height: 34px; margin-bottom: 12px; border-radius: 50%; background: linear-gradient(135deg, var(--dgx-cyan), var(--dgx-violet)); color: #05070c; font-weight: 800; }
    .dgx-flow li b { display: block; margin-bottom: 4px; color: #fff; font-size: 16.5px; }

    /* order */
    .dgx-order { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: clamp(24px, 4vw, 48px); align-items: start; }
    .dgx-summary { border: 1px solid var(--dgx-line); border-radius: var(--dgx-radius); overflow: hidden; }
    .dgx-summary__img { display: block; width: 100%; max-width: 360px; height: auto; aspect-ratio: 1; object-fit: cover; margin: 0 0 18px; border-radius: var(--dgx-radius); border: 1px solid var(--dgx-line); }
    .dgx-summary__row { display: flex; justify-content: space-between; gap: 14px; padding: 12px 16px; border-top: 1px solid var(--dgx-line); font-size: 14.5px; color: var(--dgx-fg-2); }
    .dgx-summary__row:first-child { border-top: 0; }
    .dgx-summary__row span:last-child { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .dgx-summary__row.is-muted { font-size: 13.5px; color: var(--dgx-fg-3); }
    .dgx-summary__row.is-total { background: rgba(245, 200, 107, .08); color: #fff; font-weight: 700; font-size: 17px; }
    .dgx-summary__row.is-total span:last-child { color: var(--dgx-gold); }
    .dgx-panel { padding: 26px; border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); }
    .dgx-panel__title { margin: 0 0 8px; font-size: 20px; color: #fff; }
    .dgx-panel p { color: var(--dgx-fg-2); margin: 0 0 18px; }
    .dgx-panel__actions { display: flex; flex-wrap: wrap; gap: 12px; }
    .dgx-alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; font-size: 14.5px; }
    .dgx-alert ul { margin: 6px 0 0; padding-left: 18px; }
    .dgx-alert--error { background: rgba(251, 113, 133, .1); border: 1px solid rgba(251, 113, 133, .45); color: #fecdd3; }
    .dgx-form { padding: clamp(20px, 3vw, 30px); border-radius: var(--dgx-radius); background: var(--dgx-panel); border: 1px solid var(--dgx-line); }
    .dgx-form fieldset { margin: 0 0 18px; padding: 0; border: 0; }
    .dgx-form legend { margin-bottom: 10px; font-weight: 700; color: #fff; font-size: 16px; }
    .dgx-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; min-width: 0; }
    .dgx-field label { font-size: 14px; color: var(--dgx-fg-2); }
    .dgx-field-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .dgx-form input[type=text], .dgx-form input[type=email], .dgx-form input[type=tel], .dgx-form textarea {
        width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,.16); background: rgba(5, 7, 12, .6); color: #fff; font: inherit; font-size: 16px; }
    .dgx-form input:focus, .dgx-form textarea:focus { outline: none; border-color: var(--dgx-cyan); box-shadow: 0 0 0 3px rgba(34, 211, 238, .2); }
    .dgx-company { margin: 0 0 18px; padding: 14px 16px; border-radius: 12px; border: 1px dashed var(--dgx-line); }
    .dgx-company summary { cursor: pointer; color: var(--dgx-fg-2); font-weight: 600; }
    .dgx-company[open] summary { margin-bottom: 14px; }
    .dgx-pay { display: grid; gap: 10px; }
    .dgx-pay__opt { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,.16); cursor: pointer; }
    .dgx-pay__opt:has(input:checked) { border-color: var(--dgx-cyan); background: rgba(34, 211, 238, .07); }
    .dgx-pay__opt input { margin-top: 5px; accent-color: #22d3ee; }
    .dgx-pay__opt b { display: block; color: #fff; }
    .dgx-pay__opt small { color: var(--dgx-fg-3); font-size: 13px; }
    .dgx-terms { display: flex; gap: 12px; align-items: flex-start; margin: 4px 0 18px; font-size: 14px; color: var(--dgx-fg-2); cursor: pointer; }
    .dgx-terms input { margin-top: 5px; width: 18px; height: 18px; flex: none; accent-color: #22d3ee; }

    /* faq */
    .dgx-faq details { border-bottom: 1px solid var(--dgx-line); }
    .dgx-faq summary { display: flex; justify-content: space-between; gap: 16px; padding: 18px 0; cursor: pointer; list-style: none; font-weight: 700; font-size: 17px; color: #fff; }
    .dgx-faq summary::-webkit-details-marker { display: none; }
    .dgx-faq summary::after { content: "+"; flex: none; color: var(--dgx-cyan); font-size: 22px; line-height: 1; transition: transform .2s ease; }
    .dgx-faq details[open] summary::after { transform: rotate(45deg); }
    .dgx-faq details p { margin: 0 0 18px; color: var(--dgx-fg-2); max-width: 80ch; }

    .dgx-legal { padding: 28px 0 40px; border-top: 1px solid var(--dgx-line); color: var(--dgx-fg-3); font-size: 12.5px; }
    .dgx-legal p { margin: 0 0 6px; }

    @media (max-width: 960px) {
        .dgx-hero__grid, .dgx-price-grid, .dgx-order { grid-template-columns: minmax(0, 1fr); }
        .dgx-device { width: min(100%, 260px); }
        .dgx-hero__visual.is-backdrop { display: none; }
        .dgx-hero__img { max-width: 420px; }
        .dgx-cards, .dgx-why { grid-template-columns: minmax(0, 1fr); }
        .dgx-flow { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dgx-spec { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 640px) {
        .dgx-wrap { padding: 0 16px; }
        .dgx-flow, .dgx-field-row { grid-template-columns: minmax(0, 1fr); }
        .dgx-bench__row--head { display: none; }
        .dgx-bench__row { grid-template-columns: minmax(0, 1fr) auto; gap: 4px 12px; }
        .dgx-bench__model { grid-column: 1; }
        .dgx-bench__engine { grid-column: 2; grid-row: 1; text-align: right; }
        .dgx-bench__tps { grid-column: 1 / -1; grid-row: 2; }
        .dgx-bench__src { grid-column: 1 / -1; grid-row: 3; font-size: 12.5px; }
        .dgx-spec > div { grid-template-columns: 7.5em 1fr; }
        .dgx-hero__cta .dgx-btn { flex: 1 1 100%; }
        .dgx-cells { gap: 4px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .dgx-btn, .dgx-faq summary::after { transition: none; }
    }
</style>
@endpush
