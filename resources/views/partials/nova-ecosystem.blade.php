{{-- Nova ecosystem — the four platforms that sit alongside the service
     business. Metal-X and Code Academy carry over from the previous home
     page (including the direct YouTube channel link); XDreamer and BrainX
     are new entries. --}}
<section id="nova-ecosystem" class="nova-section">
    <div class="nova-section__glow" aria-hidden="true"></div>
    <div class="nova-shell">
        <header class="nova-head nova-reveal">
            <span class="nova-eyebrow">
                <span class="nova-eyebrow__dot"></span>
                แพลตฟอร์มของเรา / Our Platforms
            </span>
            <h2 class="nova-h2">
                ระบบนิเวศ <span class="nova-grad">XMAN</span>
            </h2>
            <p class="nova-lede">
                นอกจากงานรับพัฒนา เรายังสร้างแพลตฟอร์มของเราเองที่เปิดให้ใช้งานจริง<br>
                <span style="color:var(--nv-fg-3);">Products we build, run, and use ourselves.</span>
            </p>
        </header>

        <div class="nova-grid nova-grid--2">

            {{-- XDreamer --}}
            <article class="nova-card nova-eco nova-reveal" style="--nv-accent:#f472b6;">
                <span class="nova-badge">AI</span>
                <span class="nova-card__icon" aria-hidden="true">
                    @include('partials.nova-icon', ['name' => 'spark'])
                </span>
                <h3 class="nova-card__title">XDreamer AI Studio</h3>
                <span class="nova-card__en">Generative image &amp; video studio</span>
                <p class="nova-card__body">
                    สร้างภาพและวิดีโอด้วย AI ผ่านสตูดิโอออนไลน์ของเรา
                    เลือกสไตล์ ปรับแต่ง และดาวน์โหลดได้ทันที พร้อมแกลเลอรีผลงานจากผู้ใช้จริง
                </p>
                <div class="nova-eco__links">
                    <a href="{{ route('xdreamer.home') }}" class="nova-btn nova-btn--primary nova-btn--sm">
                        เข้าใช้งาน / Launch
                        @include('partials.nova-icon', ['name' => 'arrow'])
                    </a>
                    <a href="{{ route('xdreamer.studio') }}" class="nova-eco__sub">สตูดิโอ / Studio</a>
                    <a href="{{ route('xdreamer.gallery') }}" class="nova-eco__sub">แกลเลอรี / Gallery</a>
                    <a href="{{ route('xdreamer.docs') }}" class="nova-eco__sub">คู่มือ / Docs</a>
                </div>
            </article>

            {{-- BrainX --}}
            <article class="nova-card nova-eco nova-reveal" style="--nv-accent:#8b5cf6;">
                <span class="nova-badge">ใหม่</span>
                <span class="nova-card__icon" aria-hidden="true">
                    @include('partials.nova-icon', ['name' => 'brain'])
                </span>
                <h3 class="nova-card__title">BrainX</h3>
                <span class="nova-card__en">Personal knowledge engine</span>
                <p class="nova-card__body">
                    ระบบความจำถาวรสำหรับ AI agent — เก็บโน้ต เชื่อมโยงความรู้เป็นกราฟ
                    และค้นหาแบบ semantic ให้ผู้ช่วย AI ของคุณจำบริบทได้ข้ามเซสชัน
                </p>
                <div class="nova-eco__links">
                    <a href="https://product.xman4289.com" target="_blank" rel="noopener noreferrer"
                       class="nova-btn nova-btn--primary nova-btn--sm">
                        ดูรายละเอียด / Learn more
                        @include('partials.nova-icon', ['name' => 'external'])
                    </a>
                    <span class="nova-eco__sub" style="color:var(--nv-fg-3);">product.xman4289.com</span>
                </div>
            </article>

            {{-- Metal-X --}}
            <article class="nova-card nova-eco nova-reveal" style="--nv-accent:#fb7185;">
                <span class="nova-card__icon" aria-hidden="true">
                    @include('partials.nova-icon', ['name' => 'play'])
                </span>
                <h3 class="nova-card__title">Metal-X Project</h3>
                <span class="nova-card__en">Music channel &amp; AI production</span>
                <p class="nova-card__body">
                    ช่องเพลงและ Music Video ที่เราผลิตเอง ตั้งแต่แต่งเพลงด้วย AI
                    ไปจนถึงเรนเดอร์ภาพและปล่อยขึ้น YouTube — สำรวจผลงานและทีมงานทั้งหมดได้
                </p>
                <div class="nova-eco__links">
                    <a href="{{ route('metal-x.index') }}" class="nova-btn nova-btn--primary nova-btn--sm">
                        ทีมงาน &amp; ผลงาน / Explore
                        @include('partials.nova-icon', ['name' => 'arrow'])
                    </a>
                    <a href="https://www.youtube.com/@Metal-XProject" target="_blank" rel="noopener noreferrer"
                       class="nova-eco__sub">ช่อง YouTube / Channel ↗</a>
                </div>
            </article>

            {{-- Code Academy --}}
            <article class="nova-card nova-eco nova-reveal" style="--nv-accent:#38bdf8;">
                <span class="nova-badge">ฟรี</span>
                <span class="nova-card__icon" aria-hidden="true">
                    @include('partials.nova-icon', ['name' => 'book'])
                </span>
                <h3 class="nova-card__title">XMAN Code Academy</h3>
                <span class="nova-card__en">Free resource · 50+ examples</span>
                <p class="nova-card__body">
                    ศูนย์เรียนรู้โค้ดมืออาชีพ รวมตัวอย่างคุณภาพสูงกว่า 50 ตัวอย่าง ครอบคลุม
                    Laravel, PHP, JavaScript, Python, Flutter, SQL, Git และ Docker — ฟรี ไม่ต้องสมัครสมาชิก
                </p>
                <div class="nova-eco__links">
                    <a href="{{ route('code-academy') }}" class="nova-btn nova-btn--primary nova-btn--sm">
                        เข้าสู่ Code Academy
                        @include('partials.nova-icon', ['name' => 'arrow'])
                    </a>
                    <span class="nova-eco__sub" style="color:var(--nv-mint);">ฟรี 100% / No signup</span>
                </div>
            </article>
        </div>
    </div>
</section>

<style>
    .nova-eco { padding: 30px; }
    .nova-eco .nova-card__body { margin-bottom: 22px; }
    .nova-eco__links {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 18px;
    }
    .nova-btn--sm { padding: 11px 20px; font-size: 13px; }
    .nova-eco__sub {
        font-size: 13px;
        font-weight: 600;
        color: var(--nv-fg-2);
        text-decoration: none;
        border-bottom: 1px solid transparent;
        transition: color .25s var(--nv-ease), border-color .25s var(--nv-ease);
    }
    a.nova-eco__sub:hover,
    a.nova-eco__sub:focus-visible {
        color: var(--nv-accent);
        border-bottom-color: var(--nv-accent);
    }
    /* These cards are <article>, not <a> — no whole-card hover lift. */
    .nova-eco:hover { transform: none; }
</style>
