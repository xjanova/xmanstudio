@extends($publicLayout ?? 'layouts.app')

@section('title', 'คู่มือการใช้งาน Chanthra Studio | XMAN Studio')

@push('styles')
<style>
    :root {
        --void: #060409;
        --bg-base: #0c0814;
        --bg-elev: #1d1530;
        --bg-inset: #0a0612;
        --bg-card: #160f22;
        --gold: #d4a76a;
        --gold-hi: #f0cb88;
        --gold-lo: #8b6938;
        --crimson: #b03346;
        --crimson-deep: #6e1f2c;
        --moon: #d8d0e6;
        --plum: #2a1b3d;
        --text-1: #f0e7d4;
        --text-2: #b8aec0;
        --text-3: #7d7388;
        --hairline: rgba(232, 213, 179, 0.08);
    }

    .manual-page {
        background:
            radial-gradient(ellipse at top right, rgba(176, 51, 70, 0.12), transparent 50%),
            radial-gradient(ellipse at bottom left, rgba(212, 167, 106, 0.08), transparent 55%),
            #0c0814;
        color: var(--text-1);
        min-height: 100vh;
        font-family: 'IBM Plex Sans', 'IBM Plex Sans Thai', 'Sarabun', sans-serif;
    }

    .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }
    .font-mono { font-family: 'IBM Plex Mono', Consolas, monospace; }

    .toc-link {
        display: block;
        padding: 8px 12px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 11.5px;
        color: var(--text-3);
        border-left: 2px solid transparent;
        transition: all 0.15s ease;
    }
    .toc-link:hover {
        color: var(--gold-hi);
        border-left-color: var(--gold);
        background: rgba(212, 167, 106, 0.06);
    }
    .toc-link.active {
        color: var(--gold-hi);
        border-left-color: var(--gold-hi);
        background: rgba(212, 167, 106, 0.08);
    }

    .doc-section {
        scroll-margin-top: 100px;
        padding-bottom: 64px;
    }
    .doc-section h2 {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-style: italic;
        font-size: 2.5rem;
        color: var(--gold-hi);
        margin-bottom: 8px;
    }
    .doc-section .section-label {
        display: inline-block;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 10px;
        letter-spacing: 0.2em;
        color: var(--gold);
        margin-bottom: 8px;
    }
    .doc-section h3 {
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-style: italic;
        font-size: 1.5rem;
        color: var(--text-1);
        margin: 28px 0 12px;
    }
    .doc-section p {
        line-height: 1.75;
        color: var(--text-2);
        margin-bottom: 12px;
    }

    .doc-section ul, .doc-section ol {
        line-height: 1.85;
        color: var(--text-2);
        margin: 12px 0 16px 24px;
    }
    .doc-section li { margin-bottom: 6px; }
    .doc-section li::marker { color: var(--gold); }

    .doc-section code {
        background: var(--bg-inset);
        border: 1px solid var(--hairline);
        border-radius: 4px;
        padding: 2px 6px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 12px;
        color: var(--gold-hi);
    }
    .doc-section pre {
        background: var(--bg-inset);
        border: 1px solid var(--hairline);
        border-radius: 6px;
        padding: 14px 18px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 12px;
        color: var(--text-1);
        overflow-x: auto;
        margin: 16px 0;
    }
    .doc-section pre code {
        background: transparent;
        border: 0;
        padding: 0;
    }

    .doc-card {
        background: linear-gradient(180deg, #1a1230 0%, #12091e 100%);
        border: 1px solid var(--hairline);
        border-radius: 10px;
        padding: 18px 20px;
        margin: 16px 0;
    }
    .doc-callout {
        border-left: 3px solid var(--gold);
        background: rgba(212, 167, 106, 0.05);
        padding: 14px 18px;
        margin: 16px 0;
        border-radius: 0 6px 6px 0;
    }
    .doc-callout strong { color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.1em; }

    .step-row {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        padding: 14px 0;
        border-bottom: 1px solid var(--hairline);
    }
    .step-num {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--gold-lo), var(--gold-hi));
        color: #1a0d05;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        font-weight: 600;
        font-size: 14px;
    }

    .gold-link {
        color: var(--gold-hi);
        text-decoration: underline dotted;
        text-underline-offset: 4px;
    }
    .gold-link:hover { color: var(--gold); }

    .key-input-mock {
        background: var(--bg-inset);
        border: 1px solid var(--hairline);
        border-radius: 6px;
        padding: 12px 14px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 13px;
        color: var(--gold-hi);
        letter-spacing: 0.1em;
    }
</style>
@endpush

@section('content')
<div class="manual-page">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16 pb-24">
        <div class="grid lg:grid-cols-[260px,1fr] gap-12">

            {{-- ═══════════ TABLE OF CONTENTS ═══════════ --}}
            <aside class="lg:sticky lg:top-8 lg:self-start">
                <div class="mb-8">
                    <a href="{{ route('chanthra-studio.detail') }}" class="font-mono text-xs" style="color: var(--text-3);">← chanthra studio</a>
                    <h1 class="font-display italic text-3xl mt-2" style="color: var(--gold-hi);">คู่มือ</h1>
                    <p class="font-mono text-xs mt-1" style="color: var(--text-3);">USER MANUAL · v0.2.0</p>
                </div>
                <nav class="space-y-1 border-l" style="border-color: var(--hairline);">
                    <a href="#overview" class="toc-link">1. Overview</a>
                    <a href="#requirements" class="toc-link">2. ความต้องการระบบ</a>
                    <a href="#install" class="toc-link">3. การติดตั้ง</a>
                    <a href="#license" class="toc-link">4. License activation</a>
                    <a href="#auto-update" class="toc-link">5. Auto-update</a>
                    <a href="#views" class="toc-link">6. หน้าต่างหลัก</a>
                    <a href="#generate" class="toc-link">&nbsp;&nbsp;6.1 Generate</a>
                    <a href="#voice" class="toc-link">&nbsp;&nbsp;6.2 Sound atelier</a>
                    <a href="#flow" class="toc-link">&nbsp;&nbsp;6.3 Node Flow</a>
                    <a href="#library" class="toc-link">&nbsp;&nbsp;6.4 Library + Queue</a>
                    <a href="#settings" class="toc-link">&nbsp;&nbsp;6.5 Settings</a>
                    <a href="#render" class="toc-link">7. Render film</a>
                    <a href="#troubleshoot" class="toc-link">8. แก้ไขปัญหา</a>
                    <a href="#changelog" class="toc-link">9. Changelog</a>
                </nav>
            </aside>

            {{-- ═══════════ MAIN CONTENT ═══════════ --}}
            <article>

                <section id="overview" class="doc-section">
                    <span class="section-label">SECTION 01</span>
                    <h2>Overview</h2>
                    <p>
                        <span class="font-display italic" style="color: var(--gold-hi); font-size: 1.15em;">Chanthra Studio</span>
                        คือเดสก์ท็อปแอป AI video atelier บน Windows ที่รวมขั้นตอนสร้างคลิปทั้งหมดไว้ในแอปเดียว — เขียนสคริปต์ด้วย LLM, สร้างภาพ/วีดีโอด้วย ComfyUI หรือ provider บนคลาวด์, พากย์เสียง, ต่อคลิปเป็น MP4, แล้วโพสต์ขึ้น Facebook อัตโนมัติ
                    </p>
                    <p>
                        แอปเก็บไฟล์ทุกอย่างไว้ในเครื่องตัวเอง (SQLite ใกล้ ๆ exe), API keys ทุก provider เข้ารหัสด้วย Windows DPAPI, ไม่มีการส่ง telemetry กลับเซิร์ฟเวอร์
                    </p>

                    <div class="doc-callout">
                        <strong>Lunar atelier</strong> — ดีไซน์ของแอปอ้างอิงจาก "จันทรา" (moon goddess) — ทอง/ครามแดง/ม่วงพระจันทร์ บน Mica gradient หลายชั้น · Cormorant Garamond + IBM Plex font stack · 1640×1000 frameless window
                    </div>
                </section>

                <section id="requirements" class="doc-section">
                    <span class="section-label">SECTION 02</span>
                    <h2>ความต้องการระบบ</h2>
                    <ul>
                        <li><strong style="color: var(--text-1);">Windows 10/11 (x64)</strong> — รองรับเฉพาะ Windows desktop ในตอนนี้</li>
                        <li><strong style="color: var(--text-1);">RAM 8 GB+</strong> — แนะนำ 16 GB ถ้ารัน ComfyUI ในเครื่องด้วย</li>
                        <li><strong style="color: var(--text-1);">GPU NVIDIA</strong> (optional) — ถ้าใช้ ComfyUI ในเครื่อง แนะนำ VRAM 8 GB+</li>
                        <li><strong style="color: var(--text-1);">ffmpeg</strong> ในระบบ — สำหรับฟีเจอร์ Render film
                            <pre><code>winget install Gyan.FFmpeg</code></pre>
                        </li>
                        <li><strong style="color: var(--text-1);">Internet</strong> — สำหรับ License activation, auto-update, และ provider บนคลาวด์</li>
                    </ul>

                    <h3>Provider ที่รองรับ</h3>
                    <ul>
                        <li><strong>LLM:</strong> OpenAI · Anthropic Claude · Google Gemini · OpenRouter</li>
                        <li><strong>TTS:</strong> OpenAI TTS · ElevenLabs</li>
                        <li><strong>Video:</strong> ComfyUI (local) · Replicate · Runway · Pika · fal.ai</li>
                        <li><strong>Posting:</strong> Facebook Graph API · generic webhook</li>
                    </ul>
                </section>

                <section id="install" class="doc-section">
                    <span class="section-label">SECTION 03</span>
                    <h2>การติดตั้ง</h2>

                    <div class="step-row">
                        <div class="step-num">1</div>
                        <div>
                            <h3 style="margin-top: 0;">ดาวน์โหลด</h3>
                            <p>ไปที่ <a class="gold-link" href="{{ $githubRepo }}/releases/latest">GitHub Releases ล่าสุด</a> แล้วโหลดไฟล์ <code>ChanthraStudio-vX.Y.Z-win-x64.zip</code></p>
                        </div>
                    </div>

                    <div class="step-row">
                        <div class="step-num">2</div>
                        <div>
                            <h3 style="margin-top: 0;">แตกไฟล์</h3>
                            <p>แตก zip ลงในโฟลเดอร์ที่จะใช้รัน เช่น <code>C:\Apps\ChanthraStudio</code> — ไม่ต้องติดตั้งเป็น service ใด ๆ portable เต็มตัว</p>
                        </div>
                    </div>

                    <div class="step-row">
                        <div class="step-num">3</div>
                        <div>
                            <h3 style="margin-top: 0;">รันครั้งแรก</h3>
                            <p>เปิด <code>ChanthraStudio.exe</code> หน้าจอจะเปิดที่ Generate workspace แอปจะเช็ค License + Update โดยอัตโนมัติ ที่ background</p>
                        </div>
                    </div>

                    <div class="step-row" style="border-bottom: 0;">
                        <div class="step-num">4</div>
                        <div>
                            <h3 style="margin-top: 0;">เปิดใช้งาน License</h3>
                            <p>ไปที่ <code>Settings → License &amp; Updates → Manage license</code> วาง license key แล้วกด Activate (ดูรายละเอียดในหัวข้อถัดไป)</p>
                        </div>
                    </div>
                </section>

                <section id="license" class="doc-section">
                    <span class="section-label">SECTION 04</span>
                    <h2>License activation</h2>
                    <p>
                        Chanthra Studio ใช้ License key รูปแบบ <code>CHS-XXXX-XXXX-XXXX</code> ซื้อได้ที่ <a class="gold-link" href="{{ route('chanthra-studio.pricing') }}">หน้าราคา</a> · 1 key = 1 เครื่อง bound ด้วย HWID (CPU + Motherboard + Disk)
                    </p>

                    <h3>วิธี Activate</h3>
                    <ol>
                        <li>เปิดแอป → Sidebar → <code>Settings (⚙)</code></li>
                        <li>เลื่อนไปที่ section <strong style="color: var(--gold-hi);">LICENSE &amp; UPDATES</strong></li>
                        <li>กดปุ่ม <strong>Manage license</strong> → จะเด้ง dialog</li>
                        <li>วาง License key ในช่อง <span class="key-input-mock">CHS-XXXX-XXXX-XXXX</span></li>
                        <li>กด <strong style="color: var(--gold-hi);">✦ ACTIVATE</strong></li>
                    </ol>
                    <p>หลัง activate สำเร็จ บัตรสถานะใน Settings และ status bar ด้านล่างขวาจะเปลี่ยนเป็นสีเขียวพร้อม license type (LIFETIME / YEARLY / MONTHLY)</p>

                    <h3>ย้ายเครื่อง (Deactivate)</h3>
                    <p>ก่อนติดตั้งบนเครื่องใหม่ ให้ Deactivate ที่เครื่องเดิมก่อน:</p>
                    <ol>
                        <li><code>Settings → Manage license → Deactivate</code></li>
                        <li>เครื่องใหม่: ติดตั้งแล้ว Activate ด้วย key เดิม</li>
                    </ol>

                    <div class="doc-callout">
                        <strong>HWID rebind</strong> — ถ้าเปลี่ยนฮาร์ดดิสก์/อัปเกรดเครื่องจน fingerprint เปลี่ยน แอปจะตรวจจับและขอ rebind อัตโนมัติ ไม่ต้อง deactivate ก่อน
                    </div>

                    <h3>Machine ID</h3>
                    <p>ใน Activation dialog มีปุ่ม <code>copy</code> เพื่อก๊อป Machine ID — ถ้าติดต่อ support ให้แนบ Machine ID มาด้วย</p>
                </section>

                <section id="auto-update" class="doc-section">
                    <span class="section-label">SECTION 05</span>
                    <h2>Auto-update</h2>
                    <p>
                        เมื่อ activate license แล้ว แอปจะเช็ค GitHub Releases ตอนเปิดทุกครั้ง ถ้ามีเวอร์ชันใหม่จะเด้ง <strong>Update available</strong> dialog แสดง:
                    </p>
                    <ul>
                        <li>เวอร์ชันปัจจุบัน → เวอร์ชันใหม่</li>
                        <li>ขนาดไฟล์</li>
                        <li><strong>Release notes</strong> ทั้งหมด — อ่านได้เลยว่ามีอะไรเปลี่ยน</li>
                        <li>ปุ่ม <strong>✦ INSTALL UPDATE</strong> — ดาวน์โหลด → เห็น progress bar เป็นเปอร์เซ็นต์ → ติดตั้งทับ → restart อัตโนมัติ</li>
                    </ul>

                    <h3>เช็คเองด้วย</h3>
                    <p>กด <code>Settings → License &amp; Updates → Check for updates</code> เพื่อตรวจสอบทันที</p>

                    <h3>ถ้ายังไม่ได้ activate</h3>
                    <p>ปุ่ม Install จะ disabled ให้กด <strong>เปิดหน้า GitHub Releases</strong> เพื่อดาวน์โหลด zip มาแตกเองแบบ manual</p>

                    <div class="doc-callout">
                        <strong>เบื้องหลัง</strong> — เมื่อกด Install แอปจะดาวน์โหลด zip ลง <code>%TEMP%</code>, สร้างสคริปต์ <code>chanthra-studio-update.cmd</code> ที่รอให้แอปปิด แล้วแตก zip ทับ install dir แล้วเปิดแอปใหม่
                    </div>
                </section>

                <section id="views" class="doc-section">
                    <span class="section-label">SECTION 06</span>
                    <h2>หน้าต่างหลัก</h2>
                    <p>หน้าต่างหลักของ Chanthra Studio ขนาด 1640×1000 px แบ่งเป็นโซน:</p>
                    <ul>
                        <li><strong>Title bar</strong> — ชื่อแอป + ปุ่ม min/max/close</li>
                        <li><strong>Sidebar</strong> (64px ซ้ายสุด) — switch ระหว่างหน้า: Generate · Edit · Voice · Flow · Library · Models · Queue · Settings</li>
                        <li><strong>Workspace</strong> (กลาง) — เนื้อหาของหน้าที่เลือก</li>
                        <li><strong>Status bar</strong> (28px ล่างสุด) — Connection · GPU · VRAM · Queue · License badge · Version · Auto-save</li>
                    </ul>
                </section>

                <section id="generate" class="doc-section">
                    <h3>6.1 Generate workspace</h3>
                    <p>หน้าหลักสำหรับสร้างคลิปแต่ละชอต:</p>
                    <ul>
                        <li><strong>Storyboard</strong> ด้านซ้าย — เพิ่ม/ลบ/เลือกชอต</li>
                        <li><strong>Prompt panel</strong> — เขียน prompt + เลือก model + style + seed + aspect (9:16, 1:1, 16:9, 21:9) + camera mode + duration + motion intensity</li>
                        <li><strong>Preview canvas</strong> — แสดง thumbnail หรือคลิปที่ render แล้ว</li>
                        <li>ปุ่ม <strong style="color: var(--gold-hi);">✦ Summon scene</strong> — ส่งเข้า ComfyUI หรือ provider บนคลาวด์</li>
                    </ul>
                </section>

                <section id="voice" class="doc-section">
                    <h3>6.2 Sound atelier</h3>
                    <p>หน้าจัดการเสียง:</p>
                    <ul>
                        <li><strong>Script editor</strong> — กล่องเขียนสคริปต์ มีปุ่ม <strong>✦ Write</strong> เรียก LLM ขัดเกลา/แต่งใหม่ได้</li>
                        <li><strong>Voice picker</strong> — เลือก provider (OpenAI / ElevenLabs) + voice + speed</li>
                        <li>ปุ่ม <strong>Generate take</strong> — สร้างไฟล์เสียงใหม่ ดูได้ใน <code>media/voice/</code></li>
                        <li>เลือก take ก่อนหน้าจาก dropdown มาใช้ใน Render film ได้โดยตรง</li>
                    </ul>
                </section>

                <section id="flow" class="doc-section">
                    <h3>6.3 Node Flow</h3>
                    <p>Visual graph editor สำหรับ ComfyUI workflow ในแอป:</p>
                    <ul>
                        <li><strong>Palette</strong> ด้านซ้าย — เพิ่ม node ตามหมวด (Loaders / Conditioning / Latent / Output / Video)</li>
                        <li><strong>Canvas</strong> กลาง — node บน dot grid พร้อม pan/zoom
                            <ul>
                                <li>ลาก <strong>หัว node</strong> (แถบทอง) เพื่อย้ายตำแหน่ง</li>
                                <li><strong>Right-click + drag</strong> ที่พื้นที่ว่าง = pan</li>
                                <li><strong>Ctrl + scroll</strong> = zoom</li>
                                <li>คลิก node = เลือก เห็น parameter ในแถบขวา</li>
                            </ul>
                        </li>
                        <li><strong>Mini-map</strong> ด้านขวาบน — เห็น layout overview</li>
                        <li><strong>Inspector</strong> ด้านขวาล่าง — แก้ parameter ของ node ที่เลือก</li>
                        <li>ปุ่ม <strong>✦ Auto-arrange</strong> ใน toolbar — จัด layout L→R ตาม topological order</li>
                    </ul>
                </section>

                <section id="library" class="doc-section">
                    <h3>6.4 Library + Queue</h3>
                    <p><strong>Library</strong> — คลิปทั้งหมดที่ render สำเร็จเก็บใน SQLite ใกล้ ๆ exe มี:</p>
                    <ul>
                        <li>Search box ด้านบน · grid ของ thumbnail</li>
                        <li>Tags · Date · Duration metadata</li>
                        <li>คลิกคลิปเพื่อเปิด preview · ส่งเข้า Render film · โพสต์ขึ้น Facebook · ลบ</li>
                    </ul>
                    <p><strong>Queue</strong> — ดูชอตที่กำลังประมวลผลพร้อม progress bar real-time</p>
                </section>

                <section id="settings" class="doc-section">
                    <h3>6.5 Settings</h3>
                    <p>หน้ารวม config ทั้งหมด แบ่งเป็น sections:</p>
                    <ul>
                        <li><strong>API · runtime</strong> — โฟลเดอร์ data, workflows ของ ComfyUI</li>
                        <li><strong>LLM Providers</strong> — กรอก API key สำหรับ OpenAI / Claude / Gemini / OpenRouter (Probe ก่อนเพื่อยืนยัน)</li>
                        <li><strong>Video Providers</strong> — ComfyUI URL · Replicate / Runway / Pika / fal.ai keys</li>
                        <li><strong>Voice Providers</strong> — OpenAI TTS · ElevenLabs keys</li>
                        <li><strong>Posting Providers</strong> — Facebook Page ID + token · Webhook URL</li>
                        <li><strong>License &amp; Updates</strong> — Manage license · Check for updates</li>
                    </ul>

                    <div class="doc-callout">
                        <strong>DPAPI encryption</strong> — ทุก API key ที่กรอกในหน้านี้ เข้ารหัสด้วย Windows Data Protection API ก่อนเก็บลง SQLite — ดึงไฟล์ DB ออกมาแล้วเปิดเครื่องอื่นจะอ่านไม่ได้
                    </div>
                </section>

                <section id="render" class="doc-section">
                    <span class="section-label">SECTION 07</span>
                    <h2>Render film</h2>
                    <p>หลัง render คลิปแต่ละชอตเสร็จ คุณสามารถต่อทั้ง project เป็น MP4 ผ่าน ffmpeg:</p>
                    <ol>
                        <li>ไปที่หน้า Library หรือ Generate</li>
                        <li>กดปุ่ม <strong style="color: var(--gold-hi);">✦ Render film</strong></li>
                        <li>Dialog จะเปิด ให้ตั้ง:
                            <ul>
                                <li>ชื่อไฟล์ output</li>
                                <li>วินาทีต่อชอต (default 8 วินาที)</li>
                                <li>fps (24/30/60)</li>
                                <li><strong>Audio track</strong> — เลือก voice take จาก dropdown หรือ Browse… ไฟล์เสียง</li>
                                <li>Volume</li>
                            </ul>
                        </li>
                        <li>กด <strong>Render</strong> → ffmpeg จะรันเบื้องหลัง เห็น progress ใน status bar</li>
                    </ol>

                    <p>ถ้าไม่มี ffmpeg ในระบบ Dialog จะเตือนสีแดงพร้อมคำแนะนำให้ติดตั้งผ่าน <code>winget</code></p>
                </section>

                <section id="troubleshoot" class="doc-section">
                    <span class="section-label">SECTION 08</span>
                    <h2>แก้ไขปัญหา</h2>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">License activation ไม่ผ่าน</strong>
                        <ul>
                            <li>ตรวจสอบ key ที่วาง — รูปแบบต้อง <code>CHS-XXXX-XXXX-XXXX</code></li>
                            <li>ถ้า error <strong>ALREADY_ACTIVATED_OTHER_DEVICE</strong> — เครื่องเดิมยังจองอยู่ ให้ deactivate เครื่องเดิม หรือใช้ Force rebind ผ่าน support</li>
                            <li>ถ้า error <strong>network error</strong> — เช็ค firewall + DNS ว่าออก <code>xman4289.com</code> ได้</li>
                        </ul>
                    </div>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">ComfyUI ไม่ตอบสนอง</strong>
                        <ul>
                            <li>ตรวจสอบว่า ComfyUI server รันอยู่ (default <code>http://127.0.0.1:8188</code>)</li>
                            <li>ใน Settings → Video Providers → ComfyUI ลอง <strong>Probe</strong></li>
                            <li>เช็คว่า workflow JSON ใน <code>Assets/Workflows/</code> มี checkpoint ที่ติดตั้งจริง</li>
                        </ul>
                    </div>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">ffmpeg not found</strong>
                        <pre><code>winget install Gyan.FFmpeg
# restart terminal/แอป หลังลงเสร็จ</code></pre>
                        <p>หรือกรอก path ตรง ๆ ใน Settings ถ้าติดตั้งใน custom path</p>
                    </div>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">Auto-update ไม่เด้ง</strong>
                        <ul>
                            <li>ตรวจ License — ต้อง valid ก่อนระบบ update ทำงาน</li>
                            <li>กด <code>Settings → Check for updates</code> เพื่อ trigger เอง</li>
                            <li>ถ้ายังไม่เจอ ให้ดาวน์โหลด zip ตรงจาก <a class="gold-link" href="{{ $githubRepo }}/releases/latest">GitHub Releases</a></li>
                        </ul>
                    </div>
                </section>

                <section id="changelog" class="doc-section" style="border-bottom: 0;">
                    <span class="section-label">SECTION 09</span>
                    <h2>Changelog</h2>
                    <p>ดู release notes ทั้งหมดที่ <a class="gold-link" href="{{ $githubRepo }}/releases">{{ $githubRepo }}/releases</a></p>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">v0.2.0 — Node Flow + License + Auto-update</strong>
                        <ul>
                            <li>Phase 6: visual graph editor (palette · bezier wires · mini-map · auto-arrange)</li>
                            <li>License system: Activation dialog + xman4289.com integration</li>
                            <li>Auto-update: GitHub Releases checker + progress bar + release notes display</li>
                            <li>Status bar license badge + dynamic version label</li>
                            <li>GitHub Actions workflow สำหรับสร้าง release อัตโนมัติเมื่อ push tag</li>
                        </ul>
                    </div>

                    <div class="doc-card">
                        <strong style="color: var(--gold-hi); font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.2em;">v0.1.0 — Foundation through Sound Atelier</strong>
                        <ul>
                            <li>Phase 1: lunar shell + Generate workspace</li>
                            <li>Phase 2: SQLite data layer + Provider abstractions + Settings</li>
                            <li>Phase 3: ComfyUI WebSocket integration + workflow picker</li>
                            <li>Phase 4: Render film via ffmpeg + audio track</li>
                            <li>Phase 5: Sound Atelier — TTS + LLM script writing</li>
                            <li>Phase 7: Posting — Facebook Graph + webhook</li>
                        </ul>
                    </div>
                </section>

            </article>
        </div>
    </div>
</div>

<script>
    // Highlight active section in TOC as user scrolls
    const sections = document.querySelectorAll('.doc-section[id]');
    const links = document.querySelectorAll('.toc-link');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                links.forEach(l => l.classList.remove('active'));
                const link = document.querySelector(`.toc-link[href="#${entry.target.id}"]`);
                if (link) link.classList.add('active');
            }
        });
    }, { rootMargin: '-100px 0px -60% 0px' });
    sections.forEach(s => observer.observe(s));
</script>
@endsection
