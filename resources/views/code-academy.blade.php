@extends($publicLayout ?? 'layouts.app')

@section('title', 'XMAN Code Academy — ศูนย์เรียนรู้โค้ดมืออาชีพ | Professional Code Reference')
@section('meta_description', 'ศูนย์เรียนรู้และแบ่งปันโค้ดระดับมืออาชีพ ครอบคลุม Laravel, PHP, JavaScript, Python, Flutter, SQL และอีกมากมาย พร้อมตัวอย่างการใช้งานจริง')

@section('content')
@push('styles')
<style>
    /* ===== Code Academy Theme: Light Blue + Cream ===== */
    .academy-gradient { background: linear-gradient(135deg, #e0f2fe 0%, #f5f0e8 30%, #dbeafe 60%, #fef9ef 100%); }
    .academy-hero { background: linear-gradient(135deg, #0369a1 0%, #0284c7 25%, #38bdf8 50%, #7dd3fc 75%, #bae6fd 100%); }
    .academy-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 50%, #fefce8 100%);
        border: 1px solid rgba(186, 230, 253, 0.6);
        box-shadow: 0 4px 24px rgba(14, 116, 144, 0.08), 0 1px 3px rgba(14, 116, 144, 0.04);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .academy-card:hover {
        box-shadow: 0 12px 40px rgba(14, 116, 144, 0.15), 0 4px 12px rgba(14, 116, 144, 0.08);
        transform: translateY(-4px);
        border-color: rgba(56, 189, 248, 0.5);
    }
    .academy-badge {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        color: white;
        padding: 4px 14px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .academy-section-title {
        background: linear-gradient(135deg, #0c4a6e, #0369a1, #0ea5e9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .code-block {
        background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
        border: 1px solid rgba(56, 189, 248, 0.2);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(56, 189, 248, 0.1);
    }
    .code-block-header {
        background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
        border-bottom: 1px solid rgba(56, 189, 248, 0.15);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .code-block-dots {
        display: flex;
        gap: 6px;
    }
    .code-block-dots span {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .code-block-dots span:nth-child(1) { background: #ef4444; }
    .code-block-dots span:nth-child(2) { background: #eab308; }
    .code-block-dots span:nth-child(3) { background: #22c55e; }
    .code-block-lang {
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .code-block pre {
        padding: 20px;
        overflow-x: auto;
        font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
        font-size: 0.85rem;
        line-height: 1.7;
        color: #e2e8f0;
        margin: 0;
    }
    /* Syntax Colors */
    .c-keyword { color: #c084fc; font-weight: 600; }   /* purple - keywords */
    .c-type { color: #67e8f9; }                          /* cyan - types/classes */
    .c-func { color: #fbbf24; }                          /* amber - functions */
    .c-string { color: #86efac; }                        /* green - strings */
    .c-number { color: #fb923c; }                        /* orange - numbers */
    .c-comment { color: #64748b; font-style: italic; }   /* gray - comments */
    .c-var { color: #f472b6; }                           /* pink - variables */
    .c-attr { color: #93c5fd; }                          /* blue - attributes */
    .c-tag { color: #f87171; }                           /* red - HTML tags */
    .c-op { color: #94a3b8; }                            /* gray - operators */
    .c-prop { color: #a5f3fc; }                          /* light cyan - properties */
    .c-decorator { color: #c4b5fd; }                     /* light purple - decorators */
    .c-builtin { color: #fca5a5; }                       /* light red - built-in */
    .c-const { color: #5eead4; }                         /* teal - constants */

    /* Category Navigation */
    .cat-nav-item {
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        cursor: pointer;
        white-space: nowrap;
        border: 2px solid transparent;
        background: white;
        color: #475569;
    }
    .cat-nav-item:hover {
        background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
        border-color: #7dd3fc;
        color: #0369a1;
    }
    .cat-nav-item.active {
        background: linear-gradient(135deg, #0ea5e9, #06b6d4);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 16px rgba(14, 165, 233, 0.3);
    }

    /* Scrollbar */
    .cat-scroll::-webkit-scrollbar { height: 4px; }
    .cat-scroll::-webkit-scrollbar-track { background: transparent; }
    .cat-scroll::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 2px; }

    /* Gloss effect */
    .gloss {
        position: relative;
        overflow: hidden;
    }
    .gloss::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.1) 45%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0.1) 55%, transparent 60%);
        transform: rotate(0deg);
        transition: transform 0.8s ease;
        pointer-events: none;
    }
    .gloss:hover::after {
        transform: rotate(180deg);
    }

    /* Copy button */
    .copy-btn {
        background: rgba(56, 189, 248, 0.15);
        border: 1px solid rgba(56, 189, 248, 0.3);
        color: #7dd3fc;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .copy-btn:hover {
        background: rgba(56, 189, 248, 0.3);
        color: #bae6fd;
    }

    /* Floating shapes */
    .float-shape {
        position: absolute;
        border-radius: 50%;
        opacity: 0.08;
        animation: float-drift 20s ease-in-out infinite;
    }
    @keyframes float-drift {
        0%, 100% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(30px, -20px) rotate(90deg); }
        50% { transform: translate(-20px, 30px) rotate(180deg); }
        75% { transform: translate(20px, 20px) rotate(270deg); }
    }

    /* Stats counter */
    .stat-card {
        background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(254,249,239,0.95));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(186, 230, 253, 0.5);
    }

    /* Smooth scroll behavior */
    html { scroll-behavior: smooth; }

    /* Print optimization */
    @media print {
        .code-block { break-inside: avoid; }
        .academy-hero, .cat-scroll { display: none; }
    }
</style>
@endpush

<!-- ===== HERO SECTION ===== -->
<section class="academy-hero relative overflow-hidden py-20 lg:py-28">
    <!-- Decorative shapes -->
    <div class="float-shape w-64 h-64 bg-white top-10 left-10" style="animation-delay: 0s;"></div>
    <div class="float-shape w-48 h-48 bg-yellow-200 top-40 right-20" style="animation-delay: -5s;"></div>
    <div class="float-shape w-32 h-32 bg-cyan-200 bottom-10 left-1/3" style="animation-delay: -10s;"></div>
    <div class="float-shape w-56 h-56 bg-white bottom-0 right-10" style="animation-delay: -15s;"></div>

    <!-- Grid pattern overlay -->
    <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
        <!-- Badge -->
        <div :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'" style="transition: all 0.5s ease 0.1s;">
            <span class="inline-flex items-center gap-2 px-5 py-2 bg-white/20 backdrop-blur-md border border-white/30 rounded-full text-white text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Professional Code Reference
            </span>
        </div>

        <!-- Title -->
        <h1 class="mt-8 text-4xl md:text-5xl lg:text-7xl font-black text-white leading-tight"
            :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.2s;">
            XMAN Code Academy
        </h1>
        <p class="mt-2 text-xl md:text-2xl font-semibold text-sky-100"
           :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.3s;">
            ศูนย์เรียนรู้โค้ดมืออาชีพ
        </p>

        <!-- Description -->
        <p class="mt-6 text-lg text-sky-100/80 max-w-2xl mx-auto leading-relaxed"
           :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.4s;">
            แหล่งรวมความรู้และตัวอย่างโค้ดคุณภาพสูง ครอบคลุมทุกภาษาและเฟรมเวิร์กยอดนิยม<br>
            <span class="text-white/60">Comprehensive code examples covering all popular languages & frameworks</span>
        </p>

        <!-- Stats -->
        <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto"
             :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.5s;">
            <div class="stat-card rounded-xl px-4 py-3">
                <div class="text-2xl font-black text-sky-700">10+</div>
                <div class="text-xs font-semibold text-slate-500">หมวดหมู่ / Categories</div>
            </div>
            <div class="stat-card rounded-xl px-4 py-3">
                <div class="text-2xl font-black text-sky-700">50+</div>
                <div class="text-xs font-semibold text-slate-500">ตัวอย่างโค้ด / Examples</div>
            </div>
            <div class="stat-card rounded-xl px-4 py-3">
                <div class="text-2xl font-black text-sky-700">8+</div>
                <div class="text-xs font-semibold text-slate-500">ภาษา / Languages</div>
            </div>
            <div class="stat-card rounded-xl px-4 py-3">
                <div class="text-2xl font-black text-sky-700">Free</div>
                <div class="text-xs font-semibold text-slate-500">ฟรีทั้งหมด / All Free</div>
            </div>
        </div>

        <!-- Scroll hint -->
        <div class="mt-12" :class="loaded ? 'opacity-100' : 'opacity-0'" style="transition: opacity 0.6s ease 0.7s;">
            <a href="#categories" class="inline-flex flex-col items-center text-white/60 hover:text-white transition-colors">
                <span class="text-xs font-medium mb-2">เลื่อนลงเพื่อดูทั้งหมด</span>
                <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- ===== CATEGORY NAVIGATION ===== -->
<section id="categories" class="academy-gradient sticky top-0 z-40 border-b border-sky-200/50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="cat-scroll flex gap-3 overflow-x-auto pb-2" x-data="{ active: 'all' }">
            <button class="cat-nav-item" :class="active === 'all' && 'active'" @click="active = 'all'; document.getElementById('sec-laravel').scrollIntoView({behavior:'smooth'})">
                📚 ทั้งหมด / All
            </button>
            <button class="cat-nav-item" :class="active === 'laravel' && 'active'" @click="active = 'laravel'; document.getElementById('sec-laravel').scrollIntoView({behavior:'smooth'})">
                🔴 Laravel
            </button>
            <button class="cat-nav-item" :class="active === 'php' && 'active'" @click="active = 'php'; document.getElementById('sec-php').scrollIntoView({behavior:'smooth'})">
                🐘 PHP
            </button>
            <button class="cat-nav-item" :class="active === 'js' && 'active'" @click="active = 'js'; document.getElementById('sec-js').scrollIntoView({behavior:'smooth'})">
                💛 JavaScript
            </button>
            <button class="cat-nav-item" :class="active === 'tailwind' && 'active'" @click="active = 'tailwind'; document.getElementById('sec-tailwind').scrollIntoView({behavior:'smooth'})">
                🎨 Tailwind CSS
            </button>
            <button class="cat-nav-item" :class="active === 'python' && 'active'" @click="active = 'python'; document.getElementById('sec-python').scrollIntoView({behavior:'smooth'})">
                🐍 Python
            </button>
            <button class="cat-nav-item" :class="active === 'flutter' && 'active'" @click="active = 'flutter'; document.getElementById('sec-flutter').scrollIntoView({behavior:'smooth'})">
                🦋 Flutter / Dart
            </button>
            <button class="cat-nav-item" :class="active === 'sql' && 'active'" @click="active = 'sql'; document.getElementById('sec-sql').scrollIntoView({behavior:'smooth'})">
                🗄️ SQL / Database
            </button>
            <button class="cat-nav-item" :class="active === 'git' && 'active'" @click="active = 'git'; document.getElementById('sec-git').scrollIntoView({behavior:'smooth'})">
                🔀 Git
            </button>
            <button class="cat-nav-item" :class="active === 'api' && 'active'" @click="active = 'api'; document.getElementById('sec-api').scrollIntoView({behavior:'smooth'})">
                🌐 REST API
            </button>
            <button class="cat-nav-item" :class="active === 'docker' && 'active'" @click="active = 'docker'; document.getElementById('sec-docker').scrollIntoView({behavior:'smooth'})">
                🐳 Docker
            </button>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<div class="academy-gradient min-h-screen">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-20">

{{-- ============================================================ --}}
{{-- 1. LARAVEL --}}
{{-- ============================================================ --}}
<section id="sec-laravel">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center shadow-lg shadow-red-500/20 gloss">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012a.26.26 0 01-.064-.023L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034h.001L5.044.05a.375.375 0 01.375 0L9.933 2.697h.001c.015.01.027.021.04.033.013.01.026.018.038.028l.032.045c.01.011.02.021.025.033a.253.253 0 01.022.058c.006.011.012.021.015.033.008.032.013.065.013.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.009-.021.013-.032a.487.487 0 01.024-.059c.007-.012.018-.021.025-.033.01-.015.021-.03.033-.043.012-.012.025-.02.037-.028.014-.013.028-.024.042-.034h.001l4.513-2.647a.375.375 0 01.375 0l4.513 2.647c.016.01.027.021.042.031.012.01.025.019.036.03.013.014.024.028.034.044.008.012.019.021.024.033a.42.42 0 01.024.06c.006.01.012.02.013.03zm-.74 5.032V5.862l-1.58.908-2.18 1.254v4.6zm-4.514 7.75v-4.6l-2.147 1.225-6.88 3.924v4.652zM1.093 3.624v14.588l8.273 4.761v-4.648l-4.322-2.445-.002-.003h-.002c-.015-.01-.027-.023-.04-.033-.013-.01-.027-.02-.037-.032l-.001-.002c-.013-.013-.022-.028-.033-.043-.01-.013-.021-.023-.028-.037v-.002c-.01-.015-.016-.032-.023-.048-.006-.013-.014-.025-.018-.038-.006-.02-.008-.042-.011-.063-.003-.014-.008-.025-.008-.039V6.085l-2.18-1.253zM5.23.81L1.47 3.02l3.76 2.21 3.758-2.21zm2.14 13.476l2.18-1.253V3.624l-1.58.91-2.178 1.253v9.409zM14.555 3.37l-3.758 2.21 3.758 2.21 3.759-2.21zm-.376 4.745l-2.18-1.254-1.58-.908v4.6l2.18 1.254 1.58.907zm-8.19 10.14l5.524-3.152 2.756-1.572-3.756-2.21-4.322 2.49-3.96 2.28z"/></svg>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Laravel</h2>
            <p class="text-slate-500 text-sm">PHP Framework ยอดนิยมอันดับ 1 / #1 PHP Framework</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Essential</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Laravel: Eloquent Model -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">Eloquent Model — การสร้างโมเดล</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">โมเดล Eloquent สำหรับจัดการฐานข้อมูลอย่างมืออาชีพ / Professional Eloquent model with relationships</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Models/Product.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Models</span>;

<span class="c-keyword">use</span> <span class="c-type">Illuminate\Database\Eloquent\Model</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Database\Eloquent\Relations\BelongsTo</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Database\Eloquent\Relations\HasMany</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Database\Eloquent\SoftDeletes</span>;

<span class="c-keyword">class</span> <span class="c-type">Product</span> <span class="c-keyword">extends</span> <span class="c-type">Model</span>
{
    <span class="c-keyword">use</span> <span class="c-type">SoftDeletes</span>;

    <span class="c-keyword">protected</span> <span class="c-var">$fillable</span> <span class="c-op">=</span> [
        <span class="c-string">'name'</span>, <span class="c-string">'slug'</span>, <span class="c-string">'description'</span>,
        <span class="c-string">'price'</span>, <span class="c-string">'category_id'</span>, <span class="c-string">'is_active'</span>,
    ];

    <span class="c-keyword">protected</span> <span class="c-var">$casts</span> <span class="c-op">=</span> [
        <span class="c-string">'price'</span>     <span class="c-op">=></span> <span class="c-string">'decimal:2'</span>,
        <span class="c-string">'is_active'</span> <span class="c-op">=></span> <span class="c-string">'boolean'</span>,
    ];

    <span class="c-comment">// ความสัมพันธ์: สินค้า → หมวดหมู่</span>
    <span class="c-keyword">public function</span> <span class="c-func">category</span>(): <span class="c-type">BelongsTo</span>
    {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">belongsTo</span>(<span class="c-type">Category</span>::<span class="c-keyword">class</span>);
    }

    <span class="c-comment">// ความสัมพันธ์: สินค้า → รีวิว (หลายรายการ)</span>
    <span class="c-keyword">public function</span> <span class="c-func">reviews</span>(): <span class="c-type">HasMany</span>
    {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">hasMany</span>(<span class="c-type">Review</span>::<span class="c-keyword">class</span>);
    }

    <span class="c-comment">// Scope: เฉพาะที่เปิดใช้งาน</span>
    <span class="c-keyword">public function</span> <span class="c-func">scopeActive</span>(<span class="c-var">$query</span>)
    {
        <span class="c-keyword">return</span> <span class="c-var">$query</span>-><span class="c-func">where</span>(<span class="c-string">'is_active'</span>, <span class="c-const">true</span>);
    }

    <span class="c-comment">// Accessor: ราคาพร้อมฟอร์แมต</span>
    <span class="c-keyword">public function</span> <span class="c-func">getFormattedPriceAttribute</span>(): <span class="c-type">string</span>
    {
        <span class="c-keyword">return</span> <span class="c-func">number_format</span>(<span class="c-var">$this</span>-><span class="c-prop">price</span>, <span class="c-number">2</span>) . <span class="c-string">' ฿'</span>;
    }
}</code></pre>
            </div>
        </div>

        <!-- Laravel: Controller -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">Controller — CRUD Operations</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Resource Controller พร้อม Validation / Full CRUD with validation & authorization</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — ProductController.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">ProductController</span> <span class="c-keyword">extends</span> <span class="c-type">Controller</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">index</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>)
    {
        <span class="c-var">$products</span> <span class="c-op">=</span> <span class="c-type">Product</span>::<span class="c-func">active</span>()
            -><span class="c-func">with</span>(<span class="c-string">'category'</span>)
            -><span class="c-func">when</span>(<span class="c-var">$request</span>-><span class="c-prop">search</span>, <span class="c-keyword">fn</span>(<span class="c-var">$q</span>, <span class="c-var">$s</span>) <span class="c-op">=></span>
                <span class="c-var">$q</span>-><span class="c-func">where</span>(<span class="c-string">'name'</span>, <span class="c-string">'like'</span>, <span class="c-string">"%{$s}%"</span>)
            )
            -><span class="c-func">latest</span>()
            -><span class="c-func">paginate</span>(<span class="c-number">12</span>);

        <span class="c-keyword">return</span> <span class="c-func">view</span>(<span class="c-string">'products.index'</span>, <span class="c-func">compact</span>(<span class="c-string">'products'</span>));
    }

    <span class="c-keyword">public function</span> <span class="c-func">store</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>)
    {
        <span class="c-var">$validated</span> <span class="c-op">=</span> <span class="c-var">$request</span>-><span class="c-func">validate</span>([
            <span class="c-string">'name'</span>        <span class="c-op">=></span> <span class="c-string">'required|string|max:255'</span>,
            <span class="c-string">'slug'</span>        <span class="c-op">=></span> <span class="c-string">'required|unique:products'</span>,
            <span class="c-string">'price'</span>       <span class="c-op">=></span> <span class="c-string">'required|numeric|min:0'</span>,
            <span class="c-string">'category_id'</span> <span class="c-op">=></span> <span class="c-string">'required|exists:categories,id'</span>,
            <span class="c-string">'image'</span>       <span class="c-op">=></span> <span class="c-string">'nullable|image|max:2048'</span>,
        ]);

        <span class="c-keyword">if</span> (<span class="c-var">$request</span>-><span class="c-func">hasFile</span>(<span class="c-string">'image'</span>)) {
            <span class="c-var">$validated</span>[<span class="c-string">'image'</span>] <span class="c-op">=</span> <span class="c-var">$request</span>
                -><span class="c-func">file</span>(<span class="c-string">'image'</span>)
                -><span class="c-func">store</span>(<span class="c-string">'products'</span>, <span class="c-string">'public'</span>);
        }

        <span class="c-var">$product</span> <span class="c-op">=</span> <span class="c-type">Product</span>::<span class="c-func">create</span>(<span class="c-var">$validated</span>);

        <span class="c-keyword">return</span> <span class="c-func">redirect</span>()
            -><span class="c-func">route</span>(<span class="c-string">'products.show'</span>, <span class="c-var">$product</span>)
            -><span class="c-func">with</span>(<span class="c-string">'success'</span>, <span class="c-string">'สร้างสินค้าเรียบร้อย!'</span>);
    }
}</code></pre>
            </div>
        </div>

        <!-- Laravel: Migration -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">Migration — การสร้างตาราง</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">สร้างและจัดการโครงสร้างฐานข้อมูล / Database schema management</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — migration</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">return new class extends</span> <span class="c-type">Migration</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">up</span>(): <span class="c-type">void</span>
    {
        <span class="c-type">Schema</span>::<span class="c-func">create</span>(<span class="c-string">'products'</span>, <span class="c-keyword">function</span> (<span class="c-type">Blueprint</span> <span class="c-var">$table</span>) {
            <span class="c-var">$table</span>-><span class="c-func">id</span>();
            <span class="c-var">$table</span>-><span class="c-func">string</span>(<span class="c-string">'name'</span>);
            <span class="c-var">$table</span>-><span class="c-func">string</span>(<span class="c-string">'slug'</span>)-><span class="c-func">unique</span>();
            <span class="c-var">$table</span>-><span class="c-func">text</span>(<span class="c-string">'description'</span>)-><span class="c-func">nullable</span>();
            <span class="c-var">$table</span>-><span class="c-func">decimal</span>(<span class="c-string">'price'</span>, <span class="c-number">10</span>, <span class="c-number">2</span>);
            <span class="c-var">$table</span>-><span class="c-func">foreignId</span>(<span class="c-string">'category_id'</span>)
                  -><span class="c-func">constrained</span>()
                  -><span class="c-func">cascadeOnDelete</span>();
            <span class="c-var">$table</span>-><span class="c-func">string</span>(<span class="c-string">'image'</span>)-><span class="c-func">nullable</span>();
            <span class="c-var">$table</span>-><span class="c-func">boolean</span>(<span class="c-string">'is_active'</span>)-><span class="c-func">default</span>(<span class="c-const">true</span>);
            <span class="c-var">$table</span>-><span class="c-func">softDeletes</span>();
            <span class="c-var">$table</span>-><span class="c-func">timestamps</span>();

            <span class="c-comment">// Index สำหรับเพิ่มความเร็ว</span>
            <span class="c-var">$table</span>-><span class="c-func">index</span>([<span class="c-string">'is_active'</span>, <span class="c-string">'created_at'</span>]);
        });
    }
};</code></pre>
            </div>
        </div>

        <!-- Laravel: Middleware -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">Middleware — ตรวจสอบสิทธิ์</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Custom Middleware สำหรับจัดการ Request / Request filtering & authentication</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — Middleware</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">EnsureIsAdmin</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">handle</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>, <span class="c-type">Closure</span> <span class="c-var">$next</span>)
    {
        <span class="c-keyword">if</span> (<span class="c-op">!</span> <span class="c-var">$request</span>-><span class="c-func">user</span>()?-><span class="c-prop">is_admin</span>) {
            <span class="c-func">abort</span>(<span class="c-number">403</span>, <span class="c-string">'ไม่มีสิทธิ์เข้าถึง'</span>);
        }

        <span class="c-keyword">return</span> <span class="c-var">$next</span>(<span class="c-var">$request</span>);
    }
}

<span class="c-comment">// การใช้งานใน routes/web.php</span>
<span class="c-type">Route</span>::<span class="c-func">middleware</span>([<span class="c-string">'auth'</span>, <span class="c-type">EnsureIsAdmin</span>::<span class="c-keyword">class</span>])
    -><span class="c-func">prefix</span>(<span class="c-string">'admin'</span>)
    -><span class="c-func">group</span>(<span class="c-keyword">function</span> () {
        <span class="c-type">Route</span>::<span class="c-func">resource</span>(<span class="c-string">'products'</span>, <span class="c-type">AdminProductController</span>::<span class="c-keyword">class</span>);
    });</code></pre>
            </div>
        </div>

        <!-- Laravel: Blade Template -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">Blade Template — เทมเพลตเอนจิน</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Blade templating พร้อม Components / Templating with reusable components</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Blade — products/index.blade.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-decorator">@@extends</span>(<span class="c-string">'layouts.app'</span>)
<span class="c-decorator">@@section</span>(<span class="c-string">'title'</span>, <span class="c-string">'สินค้าทั้งหมด'</span>)

<span class="c-decorator">@@section</span>(<span class="c-string">'content'</span>)
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"max-w-7xl mx-auto px-4 py-8"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;h1</span> <span class="c-attr">class</span>=<span class="c-string">"text-3xl font-bold mb-6"</span><span class="c-tag">&gt;</span>
        สินค้าทั้งหมด
    <span class="c-tag">&lt;/h1&gt;</span>

    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"grid md:grid-cols-3 gap-6"</span><span class="c-tag">&gt;</span>
        <span class="c-decorator">@@forelse</span>(<span class="c-var">$products</span> <span class="c-keyword">as</span> <span class="c-var">$product</span>)
            <span class="c-tag">&lt;x-product-card</span>
                <span class="c-attr">:product</span>=<span class="c-string">"$product"</span>
                <span class="c-attr">:show-price</span>=<span class="c-string">"true"</span>
            <span class="c-tag">/&gt;</span>
        <span class="c-decorator">@@empty</span>
            <span class="c-tag">&lt;p</span> <span class="c-attr">class</span>=<span class="c-string">"text-gray-500 col-span-3"</span><span class="c-tag">&gt;</span>
                ยังไม่มีสินค้า
            <span class="c-tag">&lt;/p&gt;</span>
        <span class="c-decorator">@@endforelse</span>
    <span class="c-tag">&lt;/div&gt;</span>

    <span class="c-comment">&lt;!-- Pagination --&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"mt-8"</span><span class="c-tag">&gt;</span>
        {{ <span class="c-var">$products</span>-><span class="c-func">links</span>() }}
    <span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>
<span class="c-decorator">@@endsection</span></code></pre>
            </div>
        </div>

        <!-- Laravel: API Resource -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h3 class="font-bold text-slate-800">API Resource — จัดรูปแบบ JSON</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">API Resource สำหรับส่งข้อมูล JSON / Transform models to JSON responses</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — ProductResource.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">ProductResource</span> <span class="c-keyword">extends</span> <span class="c-type">JsonResource</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">toArray</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>): <span class="c-type">array</span>
    {
        <span class="c-keyword">return</span> [
            <span class="c-string">'id'</span>          <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">id</span>,
            <span class="c-string">'name'</span>        <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">name</span>,
            <span class="c-string">'slug'</span>        <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">slug</span>,
            <span class="c-string">'price'</span>       <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">price</span>,
            <span class="c-string">'formatted'</span>   <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">formatted_price</span>,
            <span class="c-string">'category'</span>    <span class="c-op">=></span> <span class="c-keyword">new</span> <span class="c-type">CategoryResource</span>(
                <span class="c-var">$this</span>-><span class="c-func">whenLoaded</span>(<span class="c-string">'category'</span>)
            ),
            <span class="c-string">'reviews_count'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">whenCounted</span>(<span class="c-string">'reviews'</span>),
            <span class="c-string">'created_at'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-prop">created_at</span>-><span class="c-func">toISOString</span>(),
        ];
    }
}</code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 2. PHP --}}
{{-- ============================================================ --}}
<section id="sec-php">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 gloss">
            <span class="text-white text-2xl font-black">P</span>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">PHP</h2>
            <p class="text-slate-500 text-sm">ภาษาพื้นฐานสำหรับ Web Development / Server-side scripting language</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Fundamental</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- PHP: OOP -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h3 class="font-bold text-slate-800">OOP — Class & Interface</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">การเขียน Object-Oriented PHP อย่างมืออาชีพ / Professional OOP patterns</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">interface</span> <span class="c-type">PaymentGateway</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">charge</span>(<span class="c-type">float</span> <span class="c-var">$amount</span>): <span class="c-type">PaymentResult</span>;
    <span class="c-keyword">public function</span> <span class="c-func">refund</span>(<span class="c-type">string</span> <span class="c-var">$transactionId</span>): <span class="c-type">bool</span>;
}

<span class="c-keyword">readonly class</span> <span class="c-type">PaymentResult</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">public</span> <span class="c-type">bool</span>   <span class="c-var">$success</span>,
        <span class="c-keyword">public</span> <span class="c-type">string</span> <span class="c-var">$transactionId</span>,
        <span class="c-keyword">public</span> <span class="c-type">float</span>  <span class="c-var">$amount</span>,
        <span class="c-keyword">public</span> <span class="c-type">string</span> <span class="c-var">$message</span> <span class="c-op">=</span> <span class="c-string">''</span>,
    ) {}
}

<span class="c-keyword">class</span> <span class="c-type">StripeGateway</span> <span class="c-keyword">implements</span> <span class="c-type">PaymentGateway</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">private readonly</span> <span class="c-type">string</span> <span class="c-var">$apiKey</span>,
    ) {}

    <span class="c-keyword">public function</span> <span class="c-func">charge</span>(<span class="c-type">float</span> <span class="c-var">$amount</span>): <span class="c-type">PaymentResult</span>
    {
        <span class="c-comment">// เรียก Stripe API</span>
        <span class="c-keyword">return new</span> <span class="c-type">PaymentResult</span>(
            <span class="c-attr">success</span>: <span class="c-const">true</span>,
            <span class="c-attr">transactionId</span>: <span class="c-func">uniqid</span>(<span class="c-string">'txn_'</span>),
            <span class="c-attr">amount</span>: <span class="c-var">$amount</span>,
        );
    }
}</code></pre>
            </div>
        </div>

        <!-- PHP: Array Functions -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h3 class="font-bold text-slate-800">Array Functions — ฟังก์ชันอาเรย์</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">ฟังก์ชันอาเรย์ที่ใช้บ่อยที่สุด / Most common array operations</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-var">$users</span> <span class="c-op">=</span> [
    [<span class="c-string">'name'</span> <span class="c-op">=></span> <span class="c-string">'สมชาย'</span>, <span class="c-string">'age'</span> <span class="c-op">=></span> <span class="c-number">28</span>, <span class="c-string">'role'</span> <span class="c-op">=></span> <span class="c-string">'dev'</span>],
    [<span class="c-string">'name'</span> <span class="c-op">=></span> <span class="c-string">'สมหญิง'</span>, <span class="c-string">'age'</span> <span class="c-op">=></span> <span class="c-number">34</span>, <span class="c-string">'role'</span> <span class="c-op">=></span> <span class="c-string">'lead'</span>],
    [<span class="c-string">'name'</span> <span class="c-op">=></span> <span class="c-string">'สมศักดิ์'</span>, <span class="c-string">'age'</span> <span class="c-op">=></span> <span class="c-number">22</span>, <span class="c-string">'role'</span> <span class="c-op">=></span> <span class="c-string">'dev'</span>],
];

<span class="c-comment">// กรองเฉพาะ developer อายุ > 25</span>
<span class="c-var">$seniorDevs</span> <span class="c-op">=</span> <span class="c-func">array_filter</span>(<span class="c-var">$users</span>, <span class="c-keyword">fn</span>(<span class="c-var">$u</span>) <span class="c-op">=></span>
    <span class="c-var">$u</span>[<span class="c-string">'role'</span>] <span class="c-op">===</span> <span class="c-string">'dev'</span> <span class="c-op">&&</span> <span class="c-var">$u</span>[<span class="c-string">'age'</span>] <span class="c-op">></span> <span class="c-number">25</span>
);

<span class="c-comment">// ดึงเฉพาะชื่อ</span>
<span class="c-var">$names</span> <span class="c-op">=</span> <span class="c-func">array_map</span>(<span class="c-keyword">fn</span>(<span class="c-var">$u</span>) <span class="c-op">=></span> <span class="c-var">$u</span>[<span class="c-string">'name'</span>], <span class="c-var">$users</span>);
<span class="c-comment">// ['สมชาย', 'สมหญิง', 'สมศักดิ์']</span>

<span class="c-comment">// รวมอายุทั้งหมด</span>
<span class="c-var">$totalAge</span> <span class="c-op">=</span> <span class="c-func">array_reduce</span>(<span class="c-var">$users</span>,
    <span class="c-keyword">fn</span>(<span class="c-var">$sum</span>, <span class="c-var">$u</span>) <span class="c-op">=></span> <span class="c-var">$sum</span> <span class="c-op">+</span> <span class="c-var">$u</span>[<span class="c-string">'age'</span>], <span class="c-number">0</span>
); <span class="c-comment">// 84</span>

<span class="c-comment">// จัดเรียงตามอายุ</span>
<span class="c-func">usort</span>(<span class="c-var">$users</span>, <span class="c-keyword">fn</span>(<span class="c-var">$a</span>, <span class="c-var">$b</span>) <span class="c-op">=></span>
    <span class="c-var">$a</span>[<span class="c-string">'age'</span>] <span class="c-op"><=></span> <span class="c-var">$b</span>[<span class="c-string">'age'</span>]
);

<span class="c-comment">// Spread operator (PHP 8+)</span>
<span class="c-var">$merged</span> <span class="c-op">=</span> [<span class="c-op">...</span><span class="c-var">$names</span>, <span class="c-string">'สมปอง'</span>, <span class="c-string">'สมใจ'</span>];</code></pre>
            </div>
        </div>

        <!-- PHP: Enum -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h3 class="font-bold text-slate-800">Enum (PHP 8.1+) — ค่าคงที่แบบมีโครงสร้าง</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Backed Enum สำหรับสถานะต่างๆ / Type-safe enumerations</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">enum</span> <span class="c-type">OrderStatus</span>: <span class="c-type">string</span>
{
    <span class="c-keyword">case</span> <span class="c-const">Pending</span>    <span class="c-op">=</span> <span class="c-string">'pending'</span>;
    <span class="c-keyword">case</span> <span class="c-const">Processing</span> <span class="c-op">=</span> <span class="c-string">'processing'</span>;
    <span class="c-keyword">case</span> <span class="c-const">Shipped</span>    <span class="c-op">=</span> <span class="c-string">'shipped'</span>;
    <span class="c-keyword">case</span> <span class="c-const">Completed</span>  <span class="c-op">=</span> <span class="c-string">'completed'</span>;
    <span class="c-keyword">case</span> <span class="c-const">Cancelled</span>  <span class="c-op">=</span> <span class="c-string">'cancelled'</span>;

    <span class="c-comment">// ชื่อแสดงเป็นภาษาไทย</span>
    <span class="c-keyword">public function</span> <span class="c-func">label</span>(): <span class="c-type">string</span>
    {
        <span class="c-keyword">return match</span>(<span class="c-var">$this</span>) {
            <span class="c-keyword">self</span>::<span class="c-const">Pending</span>    <span class="c-op">=></span> <span class="c-string">'รอดำเนินการ'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Processing</span> <span class="c-op">=></span> <span class="c-string">'กำลังดำเนินการ'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Shipped</span>    <span class="c-op">=></span> <span class="c-string">'จัดส่งแล้ว'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Completed</span>  <span class="c-op">=></span> <span class="c-string">'สำเร็จ'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Cancelled</span>  <span class="c-op">=></span> <span class="c-string">'ยกเลิก'</span>,
        };
    }

    <span class="c-comment">// สีสำหรับแสดงใน Badge</span>
    <span class="c-keyword">public function</span> <span class="c-func">color</span>(): <span class="c-type">string</span>
    {
        <span class="c-keyword">return match</span>(<span class="c-var">$this</span>) {
            <span class="c-keyword">self</span>::<span class="c-const">Pending</span>    <span class="c-op">=></span> <span class="c-string">'yellow'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Processing</span> <span class="c-op">=></span> <span class="c-string">'blue'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Shipped</span>    <span class="c-op">=></span> <span class="c-string">'purple'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Completed</span>  <span class="c-op">=></span> <span class="c-string">'green'</span>,
            <span class="c-keyword">self</span>::<span class="c-const">Cancelled</span>  <span class="c-op">=></span> <span class="c-string">'red'</span>,
        };
    }
}</code></pre>
            </div>
        </div>

        <!-- PHP: Error Handling -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h3 class="font-bold text-slate-800">Error Handling — การจัดการข้อผิดพลาด</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Try-catch, Custom Exception / Robust error handling patterns</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">InsufficientBalanceException</span> <span class="c-keyword">extends</span> <span class="c-type">\RuntimeException</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">public readonly</span> <span class="c-type">float</span> <span class="c-var">$required</span>,
        <span class="c-keyword">public readonly</span> <span class="c-type">float</span> <span class="c-var">$available</span>,
    ) {
        <span class="c-keyword">parent</span>::<span class="c-func">__construct</span>(
            <span class="c-string">"ยอดเงินไม่เพียงพอ: ต้องการ {$required} มี {$available}"</span>
        );
    }
}

<span class="c-keyword">function</span> <span class="c-func">processPayment</span>(<span class="c-type">float</span> <span class="c-var">$amount</span>): <span class="c-type">void</span>
{
    <span class="c-keyword">try</span> {
        <span class="c-var">$balance</span> <span class="c-op">=</span> <span class="c-func">getWalletBalance</span>();

        <span class="c-keyword">if</span> (<span class="c-var">$balance</span> <span class="c-op"><</span> <span class="c-var">$amount</span>) {
            <span class="c-keyword">throw new</span> <span class="c-type">InsufficientBalanceException</span>(
                <span class="c-var">$amount</span>, <span class="c-var">$balance</span>
            );
        }

        <span class="c-func">deductBalance</span>(<span class="c-var">$amount</span>);
    } <span class="c-keyword">catch</span> (<span class="c-type">InsufficientBalanceException</span> <span class="c-var">$e</span>) {
        <span class="c-func">logger</span>()-><span class="c-func">warning</span>(<span class="c-string">'ยอดไม่พอ'</span>, [
            <span class="c-string">'required'</span>  <span class="c-op">=></span> <span class="c-var">$e</span>-><span class="c-prop">required</span>,
            <span class="c-string">'available'</span> <span class="c-op">=></span> <span class="c-var">$e</span>-><span class="c-prop">available</span>,
        ]);
        <span class="c-keyword">throw</span> <span class="c-var">$e</span>;
    } <span class="c-keyword">finally</span> {
        <span class="c-comment">// ทำงานเสมอ ไม่ว่าจะสำเร็จหรือล้มเหลว</span>
        <span class="c-func">logTransaction</span>(<span class="c-var">$amount</span>);
    }
}</code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 3. JAVASCRIPT --}}
{{-- ============================================================ --}}
<section id="sec-js">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center shadow-lg shadow-yellow-500/20 gloss">
            <span class="text-slate-900 text-2xl font-black">JS</span>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">JavaScript</h2>
            <p class="text-slate-500 text-sm">ภาษาสำหรับ Web ทั้ง Frontend & Backend / The language of the web</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Must Know</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- JS: Modern ES6+ -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                <h3 class="font-bold text-slate-800">ES6+ Modern Syntax</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">ไวยากรณ์สมัยใหม่ที่ต้องรู้ / Essential modern JavaScript features</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">JavaScript</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// Destructuring — แกะค่าจาก Object / Array</span>
<span class="c-keyword">const</span> { <span class="c-var">name</span>, <span class="c-var">age</span>, <span class="c-attr">role</span> <span class="c-op">=</span> <span class="c-string">'user'</span> } <span class="c-op">=</span> <span class="c-var">userData</span>;
<span class="c-keyword">const</span> [<span class="c-var">first</span>, <span class="c-op">...</span><span class="c-var">rest</span>] <span class="c-op">=</span> <span class="c-var">items</span>;

<span class="c-comment">// Template Literals — สตริงแบบมีตัวแปร</span>
<span class="c-keyword">const</span> <span class="c-var">greeting</span> <span class="c-op">=</span> <span class="c-string">`สวัสดี ${name}, คุณอายุ ${age} ปี`</span>;

<span class="c-comment">// Optional Chaining & Nullish Coalescing</span>
<span class="c-keyword">const</span> <span class="c-var">city</span> <span class="c-op">=</span> <span class="c-var">user</span><span class="c-op">?.</span><span class="c-prop">address</span><span class="c-op">?.</span><span class="c-prop">city</span> <span class="c-op">??</span> <span class="c-string">'ไม่ระบุ'</span>;

<span class="c-comment">// Arrow Functions</span>
<span class="c-keyword">const</span> <span class="c-func">double</span> <span class="c-op">=</span> (<span class="c-var">n</span>) <span class="c-op">=></span> <span class="c-var">n</span> <span class="c-op">*</span> <span class="c-number">2</span>;
<span class="c-keyword">const</span> <span class="c-func">greet</span> <span class="c-op">=</span> (<span class="c-var">name</span>) <span class="c-op">=></span> {
    <span class="c-keyword">const</span> <span class="c-var">msg</span> <span class="c-op">=</span> <span class="c-string">`สวัสดี ${name}!`</span>;
    <span class="c-keyword">return</span> <span class="c-var">msg</span>;
};

<span class="c-comment">// Spread & Rest</span>
<span class="c-keyword">const</span> <span class="c-var">merged</span> <span class="c-op">=</span> { <span class="c-op">...</span><span class="c-var">defaults</span>, <span class="c-op">...</span><span class="c-var">overrides</span> };
<span class="c-keyword">const</span> <span class="c-var">allItems</span> <span class="c-op">=</span> [<span class="c-op">...</span><span class="c-var">oldItems</span>, <span class="c-var">newItem</span>];

<span class="c-comment">// Object shorthand</span>
<span class="c-keyword">const</span> <span class="c-var">product</span> <span class="c-op">=</span> { <span class="c-var">name</span>, <span class="c-var">price</span>, <span class="c-func">getTotal</span>() {
    <span class="c-keyword">return</span> <span class="c-var">this</span>.<span class="c-prop">price</span> <span class="c-op">*</span> <span class="c-number">1.07</span>; <span class="c-comment">// รวม VAT 7%</span>
}};</code></pre>
            </div>
        </div>

        <!-- JS: Async/Await -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                <h3 class="font-bold text-slate-800">Async / Await — การทำงานแบบ Asynchronous</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Promise, async/await, error handling / Asynchronous programming patterns</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">JavaScript</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// Fetch API with async/await</span>
<span class="c-keyword">async function</span> <span class="c-func">fetchProducts</span>(<span class="c-var">category</span>) {
    <span class="c-keyword">try</span> {
        <span class="c-keyword">const</span> <span class="c-var">response</span> <span class="c-op">=</span> <span class="c-keyword">await</span> <span class="c-func">fetch</span>(
            <span class="c-string">`/api/products?category=${category}`</span>
        );

        <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">response</span>.<span class="c-prop">ok</span>) {
            <span class="c-keyword">throw new</span> <span class="c-type">Error</span>(<span class="c-string">`HTTP ${response.status}`</span>);
        }

        <span class="c-keyword">const</span> { <span class="c-var">data</span> } <span class="c-op">=</span> <span class="c-keyword">await</span> <span class="c-var">response</span>.<span class="c-func">json</span>();
        <span class="c-keyword">return</span> <span class="c-var">data</span>;

    } <span class="c-keyword">catch</span> (<span class="c-var">error</span>) {
        <span class="c-var">console</span>.<span class="c-func">error</span>(<span class="c-string">'โหลดข้อมูลไม่สำเร็จ:'</span>, <span class="c-var">error</span>);
        <span class="c-keyword">return</span> [];
    }
}

<span class="c-comment">// Promise.all — ทำงานหลายอย่างพร้อมกัน</span>
<span class="c-keyword">const</span> [<span class="c-var">products</span>, <span class="c-var">categories</span>, <span class="c-var">reviews</span>] <span class="c-op">=</span>
    <span class="c-keyword">await</span> <span class="c-type">Promise</span>.<span class="c-func">all</span>([
        <span class="c-func">fetchProducts</span>(<span class="c-string">'software'</span>),
        <span class="c-func">fetchCategories</span>(),
        <span class="c-func">fetchReviews</span>(),
    ]);

<span class="c-comment">// Promise.allSettled — รอทุกตัวจบ (ไม่สนใจ error)</span>
<span class="c-keyword">const</span> <span class="c-var">results</span> <span class="c-op">=</span> <span class="c-keyword">await</span> <span class="c-type">Promise</span>.<span class="c-func">allSettled</span>([
    <span class="c-func">apiCall1</span>(), <span class="c-func">apiCall2</span>(), <span class="c-func">apiCall3</span>()
]);

<span class="c-var">results</span>.<span class="c-func">forEach</span>((<span class="c-var">r</span>) <span class="c-op">=></span> {
    <span class="c-keyword">if</span> (<span class="c-var">r</span>.<span class="c-prop">status</span> <span class="c-op">===</span> <span class="c-string">'fulfilled'</span>) <span class="c-var">console</span>.<span class="c-func">log</span>(<span class="c-var">r</span>.<span class="c-prop">value</span>);
    <span class="c-keyword">else</span> <span class="c-var">console</span>.<span class="c-func">warn</span>(<span class="c-var">r</span>.<span class="c-prop">reason</span>);
});</code></pre>
            </div>
        </div>

        <!-- JS: DOM Manipulation -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                <h3 class="font-bold text-slate-800">DOM — จัดการ HTML ด้วย JS</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">สร้าง, แก้ไข, ลบ Element / Practical DOM manipulation</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">JavaScript</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// เลือก Element</span>
<span class="c-keyword">const</span> <span class="c-var">btn</span> <span class="c-op">=</span> <span class="c-var">document</span>.<span class="c-func">querySelector</span>(<span class="c-string">'#submit-btn'</span>);
<span class="c-keyword">const</span> <span class="c-var">cards</span> <span class="c-op">=</span> <span class="c-var">document</span>.<span class="c-func">querySelectorAll</span>(<span class="c-string">'.product-card'</span>);

<span class="c-comment">// สร้าง Element ใหม่</span>
<span class="c-keyword">const</span> <span class="c-var">card</span> <span class="c-op">=</span> <span class="c-var">document</span>.<span class="c-func">createElement</span>(<span class="c-string">'div'</span>);
<span class="c-var">card</span>.<span class="c-prop">className</span> <span class="c-op">=</span> <span class="c-string">'p-4 bg-white rounded-lg shadow'</span>;
<span class="c-var">card</span>.<span class="c-prop">innerHTML</span> <span class="c-op">=</span> <span class="c-string">`
    &lt;h3&gt;${product.name}&lt;/h3&gt;
    &lt;p&gt;${product.price} ฿&lt;/p&gt;
`</span>;
<span class="c-var">document</span>.<span class="c-func">querySelector</span>(<span class="c-string">'#grid'</span>).<span class="c-func">appendChild</span>(<span class="c-var">card</span>);

<span class="c-comment">// Event Listener พร้อม Debounce</span>
<span class="c-keyword">function</span> <span class="c-func">debounce</span>(<span class="c-var">fn</span>, <span class="c-var">ms</span> <span class="c-op">=</span> <span class="c-number">300</span>) {
    <span class="c-keyword">let</span> <span class="c-var">timer</span>;
    <span class="c-keyword">return</span> (<span class="c-op">...</span><span class="c-var">args</span>) <span class="c-op">=></span> {
        <span class="c-func">clearTimeout</span>(<span class="c-var">timer</span>);
        <span class="c-var">timer</span> <span class="c-op">=</span> <span class="c-func">setTimeout</span>(
            () <span class="c-op">=></span> <span class="c-var">fn</span>(<span class="c-op">...</span><span class="c-var">args</span>), <span class="c-var">ms</span>
        );
    };
}

<span class="c-var">searchInput</span>.<span class="c-func">addEventListener</span>(<span class="c-string">'input'</span>,
    <span class="c-func">debounce</span>((<span class="c-var">e</span>) <span class="c-op">=></span> <span class="c-func">searchProducts</span>(<span class="c-var">e</span>.<span class="c-prop">target</span>.<span class="c-prop">value</span>))
);</code></pre>
            </div>
        </div>

        <!-- JS: Alpine.js -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                <h3 class="font-bold text-slate-800">Alpine.js — Lightweight Interactivity</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">เฟรมเวิร์กเบาๆ คู่หู Tailwind / Perfect companion for Tailwind CSS</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">HTML — Alpine.js</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">&lt;!-- Dropdown Menu --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">x-data</span>=<span class="c-string">"{ open: false }"</span> <span class="c-attr">class</span>=<span class="c-string">"relative"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;button</span> <span class="c-attr">@click</span>=<span class="c-string">"open = !open"</span><span class="c-tag">&gt;</span>เมนู<span class="c-tag">&lt;/button&gt;</span>

    <span class="c-tag">&lt;div</span>
        <span class="c-attr">x-show</span>=<span class="c-string">"open"</span>
        <span class="c-attr">x-transition.opacity</span>
        <span class="c-attr">@click.outside</span>=<span class="c-string">"open = false"</span>
        <span class="c-attr">class</span>=<span class="c-string">"absolute mt-2 bg-white rounded-lg shadow-xl"</span>
    <span class="c-tag">&gt;</span>
        <span class="c-tag">&lt;a</span> <span class="c-attr">href</span>=<span class="c-string">"/profile"</span><span class="c-tag">&gt;</span>โปรไฟล์<span class="c-tag">&lt;/a&gt;</span>
        <span class="c-tag">&lt;a</span> <span class="c-attr">href</span>=<span class="c-string">"/settings"</span><span class="c-tag">&gt;</span>ตั้งค่า<span class="c-tag">&lt;/a&gt;</span>
    <span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>

<span class="c-comment">&lt;!-- Counter with Animation --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">x-data</span>=<span class="c-string">"{ count: 0 }"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;span</span> <span class="c-attr">x-text</span>=<span class="c-string">"count"</span> <span class="c-attr">class</span>=<span class="c-string">"text-4xl font-bold"</span><span class="c-tag">&gt;</span><span class="c-tag">&lt;/span&gt;</span>
    <span class="c-tag">&lt;button</span> <span class="c-attr">@click</span>=<span class="c-string">"count++"</span><span class="c-tag">&gt;</span>+1<span class="c-tag">&lt;/button&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>

<span class="c-comment">&lt;!-- Fetch + Loading State --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">x-data</span>=<span class="c-string">"{ items: [], loading: true }"</span>
     <span class="c-attr">x-init</span>=<span class="c-string">"
        items = await (await fetch('/api/items')).json();
        loading = false;
     "</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">x-show</span>=<span class="c-string">"loading"</span><span class="c-tag">&gt;</span>กำลังโหลด...<span class="c-tag">&lt;/div&gt;</span>
    <span class="c-tag">&lt;template</span> <span class="c-attr">x-for</span>=<span class="c-string">"item in items"</span><span class="c-tag">&gt;</span>
        <span class="c-tag">&lt;div</span> <span class="c-attr">x-text</span>=<span class="c-string">"item.name"</span><span class="c-tag">&gt;</span><span class="c-tag">&lt;/div&gt;</span>
    <span class="c-tag">&lt;/template&gt;</span>
<span class="c-tag">&lt;/div&gt;</span></code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 4. TAILWIND CSS --}}
{{-- ============================================================ --}}
<section id="sec-tailwind">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-500 to-sky-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 gloss">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.001 4.8c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624C13.666 10.618 15.027 12 18.001 12c3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C16.337 6.182 14.976 4.8 12.001 4.8zm-6 7.2c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624 1.177 1.194 2.538 2.576 5.512 2.576 3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C10.337 13.382 8.976 12 6.001 12z"/></svg>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Tailwind CSS</h2>
            <p class="text-slate-500 text-sm">Utility-First CSS Framework / เขียน CSS ด้วย Class</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Design</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Tailwind: Responsive Card -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                <h3 class="font-bold text-slate-800">Responsive Card — การ์ดแบบ Responsive</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">ออกแบบการ์ดที่สวยงามทุกหน้าจอ / Beautiful cards for all screen sizes</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">HTML — Tailwind</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"group relative bg-white rounded-2xl
     overflow-hidden shadow-lg hover:shadow-2xl
     transition-all duration-500 hover:-translate-y-2"</span><span class="c-tag">&gt;</span>

    <span class="c-comment">&lt;!-- รูปภาพ + Overlay --&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"aspect-video overflow-hidden"</span><span class="c-tag">&gt;</span>
        <span class="c-tag">&lt;img</span> <span class="c-attr">src</span>=<span class="c-string">"/images/product.jpg"</span>
             <span class="c-attr">alt</span>=<span class="c-string">"สินค้า"</span>
             <span class="c-attr">class</span>=<span class="c-string">"w-full h-full object-cover
                    transition-transform duration-700
                    group-hover:scale-110"</span><span class="c-tag">/&gt;</span>
        <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"absolute inset-0 bg-gradient-to-t
                    from-black/50 to-transparent
                    opacity-0 group-hover:opacity-100
                    transition-opacity"</span><span class="c-tag">&gt;</span><span class="c-tag">&lt;/div&gt;</span>
    <span class="c-tag">&lt;/div&gt;</span>

    <span class="c-comment">&lt;!-- Badge --&gt;</span>
    <span class="c-tag">&lt;span</span> <span class="c-attr">class</span>=<span class="c-string">"absolute top-3 right-3
                px-3 py-1 bg-sky-500 text-white
                text-xs font-bold rounded-full"</span><span class="c-tag">&gt;</span>
        ใหม่
    <span class="c-tag">&lt;/span&gt;</span>

    <span class="c-comment">&lt;!-- เนื้อหา --&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"p-5 sm:p-6"</span><span class="c-tag">&gt;</span>
        <span class="c-tag">&lt;h3</span> <span class="c-attr">class</span>=<span class="c-string">"text-lg font-bold text-slate-800
                    line-clamp-1"</span><span class="c-tag">&gt;</span>ชื่อสินค้า<span class="c-tag">&lt;/h3&gt;</span>
        <span class="c-tag">&lt;p</span> <span class="c-attr">class</span>=<span class="c-string">"mt-2 text-sm text-slate-500
                  line-clamp-2"</span><span class="c-tag">&gt;</span>
            รายละเอียดสินค้าที่น่าสนใจ...
        <span class="c-tag">&lt;/p&gt;</span>
        <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"mt-4 flex items-center
                    justify-between"</span><span class="c-tag">&gt;</span>
            <span class="c-tag">&lt;span</span> <span class="c-attr">class</span>=<span class="c-string">"text-xl font-black
                        text-sky-600"</span><span class="c-tag">&gt;</span>฿599<span class="c-tag">&lt;/span&gt;</span>
            <span class="c-tag">&lt;button</span> <span class="c-attr">class</span>=<span class="c-string">"px-4 py-2 bg-sky-500
                            text-white text-sm font-semibold
                            rounded-lg hover:bg-sky-600
                            transition-colors"</span><span class="c-tag">&gt;</span>
                ซื้อเลย
            <span class="c-tag">&lt;/button&gt;</span>
        <span class="c-tag">&lt;/div&gt;</span>
    <span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/div&gt;</span></code></pre>
            </div>
        </div>

        <!-- Tailwind: Flexbox & Grid -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                <h3 class="font-bold text-slate-800">Flexbox & Grid Layout</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">จัดวาง Layout แบบมืออาชีพ / Professional layout patterns</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">HTML — Tailwind</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">&lt;!-- Responsive Grid: 1 → 2 → 3 → 4 คอลัมน์ --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"grid grid-cols-1 sm:grid-cols-2
     lg:grid-cols-3 xl:grid-cols-4 gap-6"</span><span class="c-tag">&gt;</span>
    <span class="c-comment">&lt;!-- Card items go here --&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>

<span class="c-comment">&lt;!-- Centered Flex Container --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"flex items-center justify-center
     min-h-screen"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"w-full max-w-md"</span><span class="c-tag">&gt;</span>Login Form<span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>

<span class="c-comment">&lt;!-- Sidebar Layout --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"flex flex-col lg:flex-row gap-8"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;aside</span> <span class="c-attr">class</span>=<span class="c-string">"lg:w-64 shrink-0"</span><span class="c-tag">&gt;</span>
        Sidebar
    <span class="c-tag">&lt;/aside&gt;</span>
    <span class="c-tag">&lt;main</span> <span class="c-attr">class</span>=<span class="c-string">"flex-1 min-w-0"</span><span class="c-tag">&gt;</span>
        Main Content
    <span class="c-tag">&lt;/main&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>

<span class="c-comment">&lt;!-- Sticky Header + Scrollable Content --&gt;</span>
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"h-screen flex flex-col"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;header</span> <span class="c-attr">class</span>=<span class="c-string">"sticky top-0 z-50 bg-white/80
                       backdrop-blur-md border-b
                       px-6 py-3"</span><span class="c-tag">&gt;</span>
        Navigation
    <span class="c-tag">&lt;/header&gt;</span>
    <span class="c-tag">&lt;main</span> <span class="c-attr">class</span>=<span class="c-string">"flex-1 overflow-y-auto p-6"</span><span class="c-tag">&gt;</span>
        Scrollable Content
    <span class="c-tag">&lt;/main&gt;</span>
<span class="c-tag">&lt;/div&gt;</span></code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 5. PYTHON --}}
{{-- ============================================================ --}}
<section id="sec-python">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center shadow-lg shadow-blue-500/20 gloss">
            <span class="text-white text-2xl font-black">Py</span>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Python</h2>
            <p class="text-slate-500 text-sm">ภาษายอดนิยมสำหรับ AI, Data Science & Automation</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Popular</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Python: Class & Dataclass -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h3 class="font-bold text-slate-800">Dataclass & Type Hints</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">โค้ด Python สมัยใหม่ที่อ่านง่าย / Modern Python with type safety</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Python</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">from</span> <span class="c-type">dataclasses</span> <span class="c-keyword">import</span> <span class="c-func">dataclass</span>, <span class="c-func">field</span>
<span class="c-keyword">from</span> <span class="c-type">datetime</span> <span class="c-keyword">import</span> <span class="c-type">datetime</span>
<span class="c-keyword">from</span> <span class="c-type">enum</span> <span class="c-keyword">import</span> <span class="c-type">Enum</span>

<span class="c-keyword">class</span> <span class="c-type">Priority</span>(<span class="c-type">Enum</span>):
    <span class="c-const">LOW</span> <span class="c-op">=</span> <span class="c-string">"low"</span>
    <span class="c-const">MEDIUM</span> <span class="c-op">=</span> <span class="c-string">"medium"</span>
    <span class="c-const">HIGH</span> <span class="c-op">=</span> <span class="c-string">"high"</span>
    <span class="c-const">CRITICAL</span> <span class="c-op">=</span> <span class="c-string">"critical"</span>

<span class="c-decorator">@dataclass</span>
<span class="c-keyword">class</span> <span class="c-type">Task</span>:
    <span class="c-var">title</span>: <span class="c-type">str</span>
    <span class="c-var">description</span>: <span class="c-type">str</span>
    <span class="c-var">priority</span>: <span class="c-type">Priority</span> <span class="c-op">=</span> <span class="c-type">Priority</span>.<span class="c-const">MEDIUM</span>
    <span class="c-var">completed</span>: <span class="c-type">bool</span> <span class="c-op">=</span> <span class="c-const">False</span>
    <span class="c-var">tags</span>: <span class="c-type">list</span>[<span class="c-type">str</span>] <span class="c-op">=</span> <span class="c-func">field</span>(<span class="c-attr">default_factory</span><span class="c-op">=</span><span class="c-type">list</span>)
    <span class="c-var">created_at</span>: <span class="c-type">datetime</span> <span class="c-op">=</span> <span class="c-func">field</span>(
        <span class="c-attr">default_factory</span><span class="c-op">=</span><span class="c-type">datetime</span>.<span class="c-prop">now</span>
    )

    <span class="c-keyword">def</span> <span class="c-func">mark_done</span>(<span class="c-var">self</span>) <span class="c-op">-></span> <span class="c-const">None</span>:
        <span class="c-var">self</span>.<span class="c-prop">completed</span> <span class="c-op">=</span> <span class="c-const">True</span>

    <span class="c-keyword">def</span> <span class="c-func">is_urgent</span>(<span class="c-var">self</span>) <span class="c-op">-></span> <span class="c-type">bool</span>:
        <span class="c-keyword">return</span> <span class="c-var">self</span>.<span class="c-prop">priority</span> <span class="c-keyword">in</span> (
            <span class="c-type">Priority</span>.<span class="c-const">HIGH</span>, <span class="c-type">Priority</span>.<span class="c-const">CRITICAL</span>
        )

<span class="c-comment"># การใช้งาน</span>
<span class="c-var">task</span> <span class="c-op">=</span> <span class="c-type">Task</span>(
    <span class="c-attr">title</span><span class="c-op">=</span><span class="c-string">"Deploy v2.0"</span>,
    <span class="c-attr">description</span><span class="c-op">=</span><span class="c-string">"อัพเดทเวอร์ชันใหม่"</span>,
    <span class="c-attr">priority</span><span class="c-op">=</span><span class="c-type">Priority</span>.<span class="c-const">HIGH</span>,
    <span class="c-attr">tags</span><span class="c-op">=</span>[<span class="c-string">"deploy"</span>, <span class="c-string">"production"</span>],
)</code></pre>
            </div>
        </div>

        <!-- Python: FastAPI -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h3 class="font-bold text-slate-800">FastAPI — สร้าง API สมัยใหม่</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">REST API ด้วย FastAPI + Pydantic / High-performance async API</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Python — FastAPI</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">from</span> <span class="c-type">fastapi</span> <span class="c-keyword">import</span> <span class="c-type">FastAPI</span>, <span class="c-type">HTTPException</span>
<span class="c-keyword">from</span> <span class="c-type">pydantic</span> <span class="c-keyword">import</span> <span class="c-type">BaseModel</span>

<span class="c-var">app</span> <span class="c-op">=</span> <span class="c-type">FastAPI</span>(<span class="c-attr">title</span><span class="c-op">=</span><span class="c-string">"XMAN API"</span>)

<span class="c-keyword">class</span> <span class="c-type">ProductCreate</span>(<span class="c-type">BaseModel</span>):
    <span class="c-var">name</span>: <span class="c-type">str</span>
    <span class="c-var">price</span>: <span class="c-type">float</span>
    <span class="c-var">category</span>: <span class="c-type">str</span> <span class="c-op">=</span> <span class="c-string">"general"</span>

<span class="c-keyword">class</span> <span class="c-type">ProductResponse</span>(<span class="c-type">ProductCreate</span>):
    <span class="c-var">id</span>: <span class="c-type">int</span>

<span class="c-decorator">@app.get</span>(<span class="c-string">"/products"</span>)
<span class="c-keyword">async def</span> <span class="c-func">list_products</span>(
    <span class="c-var">category</span>: <span class="c-type">str</span> <span class="c-op">|</span> <span class="c-const">None</span> <span class="c-op">=</span> <span class="c-const">None</span>,
    <span class="c-var">limit</span>: <span class="c-type">int</span> <span class="c-op">=</span> <span class="c-number">20</span>,
) <span class="c-op">-></span> <span class="c-type">list</span>[<span class="c-type">ProductResponse</span>]:
    <span class="c-var">query</span> <span class="c-op">=</span> <span class="c-func">select</span>(<span class="c-type">Product</span>)
    <span class="c-keyword">if</span> <span class="c-var">category</span>:
        <span class="c-var">query</span> <span class="c-op">=</span> <span class="c-var">query</span>.<span class="c-func">where</span>(
            <span class="c-type">Product</span>.<span class="c-prop">category</span> <span class="c-op">==</span> <span class="c-var">category</span>
        )
    <span class="c-keyword">return</span> <span class="c-keyword">await</span> <span class="c-var">db</span>.<span class="c-func">execute</span>(
        <span class="c-var">query</span>.<span class="c-func">limit</span>(<span class="c-var">limit</span>)
    )

<span class="c-decorator">@app.post</span>(<span class="c-string">"/products"</span>, <span class="c-attr">status_code</span><span class="c-op">=</span><span class="c-number">201</span>)
<span class="c-keyword">async def</span> <span class="c-func">create_product</span>(
    <span class="c-var">data</span>: <span class="c-type">ProductCreate</span>,
) <span class="c-op">-></span> <span class="c-type">ProductResponse</span>:
    <span class="c-var">product</span> <span class="c-op">=</span> <span class="c-type">Product</span>(<span class="c-op">**</span><span class="c-var">data</span>.<span class="c-func">model_dump</span>())
    <span class="c-var">db</span>.<span class="c-func">add</span>(<span class="c-var">product</span>)
    <span class="c-keyword">await</span> <span class="c-var">db</span>.<span class="c-func">commit</span>()
    <span class="c-keyword">return</span> <span class="c-var">product</span></code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 6. FLUTTER / DART --}}
{{-- ============================================================ --}}
<section id="sec-flutter">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/20 gloss">
            <span class="text-white text-xl font-black">FL</span>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Flutter / Dart</h2>
            <p class="text-slate-500 text-sm">สร้างแอป Cross-Platform จากโค้ดเดียว / One codebase, all platforms</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Mobile</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Flutter: StatefulWidget -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <h3 class="font-bold text-slate-800">StatefulWidget — หน้าจอแบบมี State</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Widget ที่มีสถานะเปลี่ยนแปลงได้ / Interactive widgets with state management</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Dart — Flutter</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">ProductListScreen</span> <span class="c-keyword">extends</span> <span class="c-type">StatefulWidget</span> {
  <span class="c-keyword">const</span> <span class="c-type">ProductListScreen</span>({<span class="c-keyword">super</span>.<span class="c-prop">key</span>});

  <span class="c-decorator">@override</span>
  <span class="c-type">State</span>&lt;<span class="c-type">ProductListScreen</span>&gt; <span class="c-func">createState</span>() <span class="c-op">=></span>
      <span class="c-type">_ProductListScreenState</span>();
}

<span class="c-keyword">class</span> <span class="c-type">_ProductListScreenState</span>
    <span class="c-keyword">extends</span> <span class="c-type">State</span>&lt;<span class="c-type">ProductListScreen</span>&gt; {
  <span class="c-type">List</span>&lt;<span class="c-type">Product</span>&gt; <span class="c-var">_products</span> <span class="c-op">=</span> [];
  <span class="c-type">bool</span> <span class="c-var">_loading</span> <span class="c-op">=</span> <span class="c-const">true</span>;

  <span class="c-decorator">@override</span>
  <span class="c-keyword">void</span> <span class="c-func">initState</span>() {
    <span class="c-keyword">super</span>.<span class="c-func">initState</span>();
    <span class="c-func">_loadProducts</span>();
  }

  <span class="c-type">Future</span>&lt;<span class="c-keyword">void</span>&gt; <span class="c-func">_loadProducts</span>() <span class="c-keyword">async</span> {
    <span class="c-keyword">final</span> <span class="c-var">data</span> <span class="c-op">=</span> <span class="c-keyword">await</span> <span class="c-type">ApiService</span>.<span class="c-func">getProducts</span>();
    <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">mounted</span>) <span class="c-keyword">return</span>; <span class="c-comment">// สำคัญมาก!</span>
    <span class="c-func">setState</span>(() {
      <span class="c-var">_products</span> <span class="c-op">=</span> <span class="c-var">data</span>;
      <span class="c-var">_loading</span> <span class="c-op">=</span> <span class="c-const">false</span>;
    });
  }

  <span class="c-decorator">@override</span>
  <span class="c-type">Widget</span> <span class="c-func">build</span>(<span class="c-type">BuildContext</span> <span class="c-var">context</span>) {
    <span class="c-keyword">if</span> (<span class="c-var">_loading</span>) {
      <span class="c-keyword">return</span> <span class="c-keyword">const</span> <span class="c-type">Center</span>(
        <span class="c-attr">child</span>: <span class="c-type">CircularProgressIndicator</span>(),
      );
    }

    <span class="c-keyword">return</span> <span class="c-type">ListView</span>.<span class="c-func">builder</span>(
      <span class="c-attr">itemCount</span>: <span class="c-var">_products</span>.<span class="c-prop">length</span>,
      <span class="c-attr">itemBuilder</span>: (<span class="c-var">ctx</span>, <span class="c-var">i</span>) <span class="c-op">=></span> <span class="c-type">ProductCard</span>(
        <span class="c-attr">product</span>: <span class="c-var">_products</span>[<span class="c-var">i</span>],
        <span class="c-attr">onTap</span>: () <span class="c-op">=></span> <span class="c-func">_navigateToDetail</span>(<span class="c-var">_products</span>[<span class="c-var">i</span>]),
      ),
    );
  }
}</code></pre>
            </div>
        </div>

        <!-- Flutter: API Service -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <h3 class="font-bold text-slate-800">API Service — เชื่อมต่อ Backend</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Http client สำหรับเรียก REST API / Clean API service pattern</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Dart — Flutter</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">import</span> <span class="c-string">'package:http/http.dart'</span> <span class="c-keyword">as</span> <span class="c-var">http</span>;
<span class="c-keyword">import</span> <span class="c-string">'dart:convert'</span>;

<span class="c-keyword">class</span> <span class="c-type">ApiService</span> {
  <span class="c-keyword">static const</span> <span class="c-var">_baseUrl</span> <span class="c-op">=</span> <span class="c-string">'https://api.xman.studio'</span>;

  <span class="c-keyword">static</span> <span class="c-type">Future</span>&lt;<span class="c-type">List</span>&lt;<span class="c-type">Product</span>&gt;&gt; <span class="c-func">getProducts</span>({
    <span class="c-type">String</span>? <span class="c-var">category</span>,
    <span class="c-type">int</span> <span class="c-var">page</span> <span class="c-op">=</span> <span class="c-number">1</span>,
  }) <span class="c-keyword">async</span> {
    <span class="c-keyword">final</span> <span class="c-var">uri</span> <span class="c-op">=</span> <span class="c-type">Uri</span>.<span class="c-func">parse</span>(<span class="c-string">'$_baseUrl/products'</span>)
        .<span class="c-func">replace</span>(<span class="c-attr">queryParameters</span>: {
      <span class="c-keyword">if</span> (<span class="c-var">category</span> <span class="c-op">!=</span> <span class="c-const">null</span>) <span class="c-string">'category'</span>: <span class="c-var">category</span>,
      <span class="c-string">'page'</span>: <span class="c-var">page</span>.<span class="c-func">toString</span>(),
    });

    <span class="c-keyword">final</span> <span class="c-var">response</span> <span class="c-op">=</span> <span class="c-keyword">await</span> <span class="c-var">http</span>.<span class="c-func">get</span>(
      <span class="c-var">uri</span>,
      <span class="c-attr">headers</span>: {<span class="c-string">'Accept'</span>: <span class="c-string">'application/json'</span>},
    );

    <span class="c-keyword">if</span> (<span class="c-var">response</span>.<span class="c-prop">statusCode</span> <span class="c-op">!=</span> <span class="c-number">200</span>) {
      <span class="c-keyword">throw</span> <span class="c-type">ApiException</span>(
        <span class="c-string">'โหลดสินค้าไม่สำเร็จ'</span>,
        <span class="c-var">response</span>.<span class="c-prop">statusCode</span>,
      );
    }

    <span class="c-keyword">final</span> <span class="c-type">List</span> <span class="c-var">data</span> <span class="c-op">=</span> <span class="c-func">jsonDecode</span>(
      <span class="c-var">response</span>.<span class="c-prop">body</span>
    )[<span class="c-string">'data'</span>];

    <span class="c-keyword">return</span> <span class="c-var">data</span>
        .<span class="c-func">map</span>((<span class="c-var">json</span>) <span class="c-op">=></span> <span class="c-type">Product</span>.<span class="c-func">fromJson</span>(<span class="c-var">json</span>))
        .<span class="c-func">toList</span>();
  }
}</code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 7. SQL / DATABASE --}}
{{-- ============================================================ --}}
<section id="sec-sql">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">SQL / Database</h2>
            <p class="text-slate-500 text-sm">ภาษาจัดการฐานข้อมูล / Database management & optimization</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Core</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- SQL: CRUD -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h3 class="font-bold text-slate-800">CRUD Operations — คำสั่งพื้นฐาน</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">SELECT, INSERT, UPDATE, DELETE / Essential SQL commands</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">SQL</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">-- ดึงข้อมูลสินค้าพร้อมหมวดหมู่ (JOIN)</span>
<span class="c-keyword">SELECT</span>
    <span class="c-var">p</span>.<span class="c-prop">id</span>,
    <span class="c-var">p</span>.<span class="c-prop">name</span>,
    <span class="c-var">p</span>.<span class="c-prop">price</span>,
    <span class="c-var">c</span>.<span class="c-prop">name</span> <span class="c-keyword">AS</span> <span class="c-attr">category_name</span>,
    <span class="c-func">COUNT</span>(<span class="c-var">r</span>.<span class="c-prop">id</span>) <span class="c-keyword">AS</span> <span class="c-attr">review_count</span>,
    <span class="c-func">AVG</span>(<span class="c-var">r</span>.<span class="c-prop">rating</span>) <span class="c-keyword">AS</span> <span class="c-attr">avg_rating</span>
<span class="c-keyword">FROM</span> <span class="c-type">products</span> <span class="c-var">p</span>
<span class="c-keyword">LEFT JOIN</span> <span class="c-type">categories</span> <span class="c-var">c</span>
    <span class="c-keyword">ON</span> <span class="c-var">p</span>.<span class="c-prop">category_id</span> <span class="c-op">=</span> <span class="c-var">c</span>.<span class="c-prop">id</span>
<span class="c-keyword">LEFT JOIN</span> <span class="c-type">reviews</span> <span class="c-var">r</span>
    <span class="c-keyword">ON</span> <span class="c-var">r</span>.<span class="c-prop">product_id</span> <span class="c-op">=</span> <span class="c-var">p</span>.<span class="c-prop">id</span>
<span class="c-keyword">WHERE</span> <span class="c-var">p</span>.<span class="c-prop">is_active</span> <span class="c-op">=</span> <span class="c-number">1</span>
<span class="c-keyword">GROUP BY</span> <span class="c-var">p</span>.<span class="c-prop">id</span>, <span class="c-var">p</span>.<span class="c-prop">name</span>, <span class="c-var">p</span>.<span class="c-prop">price</span>, <span class="c-var">c</span>.<span class="c-prop">name</span>
<span class="c-keyword">HAVING</span> <span class="c-func">AVG</span>(<span class="c-var">r</span>.<span class="c-prop">rating</span>) <span class="c-op">>=</span> <span class="c-number">4.0</span>
<span class="c-keyword">ORDER BY</span> <span class="c-attr">avg_rating</span> <span class="c-keyword">DESC</span>
<span class="c-keyword">LIMIT</span> <span class="c-number">20</span>;

<span class="c-comment">-- INSERT พร้อมป้องกัน duplicate</span>
<span class="c-keyword">INSERT INTO</span> <span class="c-type">products</span> (<span class="c-prop">name</span>, <span class="c-prop">slug</span>, <span class="c-prop">price</span>)
<span class="c-keyword">VALUES</span> (<span class="c-string">'สินค้าใหม่'</span>, <span class="c-string">'new-product'</span>, <span class="c-number">599.00</span>)
<span class="c-keyword">ON DUPLICATE KEY UPDATE</span>
    <span class="c-prop">price</span> <span class="c-op">=</span> <span class="c-keyword">VALUES</span>(<span class="c-prop">price</span>);

<span class="c-comment">-- UPDATE พร้อมเงื่อนไขหลายตัว</span>
<span class="c-keyword">UPDATE</span> <span class="c-type">products</span>
<span class="c-keyword">SET</span> <span class="c-prop">price</span> <span class="c-op">=</span> <span class="c-prop">price</span> <span class="c-op">*</span> <span class="c-number">0.9</span>  <span class="c-comment">-- ลด 10%</span>
<span class="c-keyword">WHERE</span> <span class="c-prop">category_id</span> <span class="c-op">=</span> <span class="c-number">5</span>
  <span class="c-keyword">AND</span> <span class="c-prop">is_active</span> <span class="c-op">=</span> <span class="c-number">1</span>;</code></pre>
            </div>
        </div>

        <!-- SQL: Optimization -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <h3 class="font-bold text-slate-800">Index & Optimization — เพิ่มความเร็ว</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">สร้าง Index, Subquery, CTE / Performance optimization techniques</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">SQL</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">-- สร้าง Index ที่เหมาะสม</span>
<span class="c-keyword">CREATE INDEX</span> <span class="c-attr">idx_products_active_created</span>
<span class="c-keyword">ON</span> <span class="c-type">products</span> (<span class="c-prop">is_active</span>, <span class="c-prop">created_at</span> <span class="c-keyword">DESC</span>);

<span class="c-comment">-- CTE (Common Table Expression) — อ่านง่าย</span>
<span class="c-keyword">WITH</span> <span class="c-attr">monthly_sales</span> <span class="c-keyword">AS</span> (
    <span class="c-keyword">SELECT</span>
        <span class="c-prop">product_id</span>,
        <span class="c-func">SUM</span>(<span class="c-prop">amount</span>) <span class="c-keyword">AS</span> <span class="c-attr">total</span>,
        <span class="c-func">COUNT</span>(<span class="c-op">*</span>) <span class="c-keyword">AS</span> <span class="c-attr">orders</span>
    <span class="c-keyword">FROM</span> <span class="c-type">order_items</span>
    <span class="c-keyword">WHERE</span> <span class="c-prop">created_at</span> <span class="c-op">>=</span> <span class="c-func">DATE_SUB</span>(
        <span class="c-func">NOW</span>(), <span class="c-keyword">INTERVAL</span> <span class="c-number">30</span> <span class="c-keyword">DAY</span>
    )
    <span class="c-keyword">GROUP BY</span> <span class="c-prop">product_id</span>
)
<span class="c-keyword">SELECT</span>
    <span class="c-var">p</span>.<span class="c-prop">name</span>,
    <span class="c-var">ms</span>.<span class="c-prop">total</span>,
    <span class="c-var">ms</span>.<span class="c-prop">orders</span>,
    <span class="c-func">RANK</span>() <span class="c-keyword">OVER</span>(
        <span class="c-keyword">ORDER BY</span> <span class="c-var">ms</span>.<span class="c-prop">total</span> <span class="c-keyword">DESC</span>
    ) <span class="c-keyword">AS</span> <span class="c-attr">sales_rank</span>
<span class="c-keyword">FROM</span> <span class="c-attr">monthly_sales</span> <span class="c-var">ms</span>
<span class="c-keyword">JOIN</span> <span class="c-type">products</span> <span class="c-var">p</span>
    <span class="c-keyword">ON</span> <span class="c-var">p</span>.<span class="c-prop">id</span> <span class="c-op">=</span> <span class="c-var">ms</span>.<span class="c-prop">product_id</span>
<span class="c-keyword">ORDER BY</span> <span class="c-attr">sales_rank</span>
<span class="c-keyword">LIMIT</span> <span class="c-number">10</span>;</code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 8. GIT --}}
{{-- ============================================================ --}}
<section id="sec-git">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M23.546 10.93L13.067.452c-.604-.603-1.582-.603-2.188 0L8.708 2.627l2.76 2.76c.645-.215 1.379-.07 1.889.441.516.515.658 1.258.438 1.9l2.66 2.66c.643-.222 1.387-.078 1.9.435.721.72.721 1.884 0 2.604-.72.719-1.886.719-2.605 0-.538-.536-.674-1.337-.404-1.996L12.86 8.955v6.525c.176.086.342.203.488.348.713.721.713 1.883 0 2.6-.719.721-1.889.721-2.609 0-.719-.719-.719-1.879 0-2.598.182-.18.387-.316.605-.406V8.835c-.217-.091-.424-.222-.6-.401-.545-.545-.676-1.342-.396-2.009L7.636 3.7.45 10.881c-.6.605-.6 1.584 0 2.189l10.48 10.477c.604.604 1.582.604 2.186 0l10.43-10.43c.605-.603.605-1.582 0-2.187"/></svg>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Git</h2>
            <p class="text-slate-500 text-sm">ระบบจัดการเวอร์ชัน / Version control system</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Essential</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Git: Essential Commands -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <h3 class="font-bold text-slate-800">Essential Commands — คำสั่งที่ใช้ทุกวัน</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">คำสั่ง Git ที่ต้องรู้ / Daily Git workflow commands</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Bash — Git</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment"># สร้าง branch ใหม่จาก main</span>
<span class="c-func">git</span> checkout -b <span class="c-string">feature/payment-gateway</span>

<span class="c-comment"># ดูสถานะไฟล์ที่เปลี่ยนแปลง</span>
<span class="c-func">git</span> status
<span class="c-func">git</span> diff --staged  <span class="c-comment"># ดูเฉพาะที่ staged</span>

<span class="c-comment"># เพิ่มไฟล์ทีละไฟล์ (ปลอดภัยกว่า git add .)</span>
<span class="c-func">git</span> add <span class="c-string">app/Services/PaymentService.php</span>
<span class="c-func">git</span> add <span class="c-string">tests/PaymentTest.php</span>

<span class="c-comment"># Commit พร้อมข้อความที่ชัดเจน</span>
<span class="c-func">git</span> commit -m <span class="c-string">"feat: add Stripe payment gateway

- Add PaymentService with charge/refund
- Add webhook handler for payment events
- Add unit tests for payment flows"</span>

<span class="c-comment"># Push และสร้าง Pull Request</span>
<span class="c-func">git</span> push -u origin <span class="c-string">feature/payment-gateway</span>

<span class="c-comment"># อัพเดท branch กับ main (rebase)</span>
<span class="c-func">git</span> fetch origin
<span class="c-func">git</span> rebase origin/main

<span class="c-comment"># Stash — เก็บงานชั่วคราว</span>
<span class="c-func">git</span> stash push -m <span class="c-string">"WIP: payment form"</span>
<span class="c-func">git</span> stash pop  <span class="c-comment"># เอากลับมา</span>

<span class="c-comment"># ดูประวัติแบบสวยงาม</span>
<span class="c-func">git</span> log --oneline --graph --all -<span class="c-number">20</span></code></pre>
            </div>
        </div>

        <!-- Git: Advanced -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <h3 class="font-bold text-slate-800">Advanced Git — เทคนิคขั้นสูง</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Cherry-pick, Bisect, Reflog / Pro-level Git techniques</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Bash — Git</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment"># Cherry-pick — เลือก commit เฉพาะ</span>
<span class="c-func">git</span> cherry-pick <span class="c-string">abc1234</span>

<span class="c-comment"># Bisect — หา commit ที่ทำให้พัง</span>
<span class="c-func">git</span> bisect start
<span class="c-func">git</span> bisect bad           <span class="c-comment"># ตอนนี้พัง</span>
<span class="c-func">git</span> bisect good <span class="c-string">v1.0.0</span>  <span class="c-comment"># ตรงนี้ยังดี</span>
<span class="c-comment"># Git จะหา commit ต้นเหตุให้อัตโนมัติ</span>
<span class="c-func">git</span> bisect reset         <span class="c-comment"># เสร็จแล้วรีเซ็ต</span>

<span class="c-comment"># Reflog — กู้ commit ที่หายไป</span>
<span class="c-func">git</span> reflog
<span class="c-func">git</span> checkout <span class="c-string">HEAD@{3}</span>  <span class="c-comment"># กลับไปจุดนั้น</span>

<span class="c-comment"># Squash commits (รวม commit ก่อน merge)</span>
<span class="c-func">git</span> merge --squash <span class="c-string">feature/xyz</span>
<span class="c-func">git</span> commit -m <span class="c-string">"feat: add XYZ feature"</span>

<span class="c-comment"># Blame — ดูว่าใครแก้บรรทัดไหน</span>
<span class="c-func">git</span> blame <span class="c-string">app/Models/User.php</span>

<span class="c-comment"># Clean — ลบไฟล์ที่ไม่ได้ track</span>
<span class="c-func">git</span> clean -fd --dry-run  <span class="c-comment"># ดูก่อนลบ</span>
<span class="c-func">git</span> clean -fd            <span class="c-comment"># ลบจริง</span></code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 9. REST API --}}
{{-- ============================================================ --}}
<section id="sec-api">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">REST API Design</h2>
            <p class="text-slate-500 text-sm">ออกแบบ API ที่ดี / Professional API design patterns</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Backend</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- API: Design Patterns -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                <h3 class="font-bold text-slate-800">API Endpoints — ออกแบบ URL</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">RESTful URL patterns & HTTP methods / Standard API design</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">REST API Design</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment"># ===== RESTful API Convention =====</span>

<span class="c-keyword">GET</span>    <span class="c-string">/api/v1/products</span>          <span class="c-comment"># รายการสินค้า</span>
<span class="c-keyword">GET</span>    <span class="c-string">/api/v1/products/42</span>       <span class="c-comment"># สินค้า ID 42</span>
<span class="c-keyword">POST</span>   <span class="c-string">/api/v1/products</span>          <span class="c-comment"># สร้างสินค้าใหม่</span>
<span class="c-keyword">PUT</span>    <span class="c-string">/api/v1/products/42</span>       <span class="c-comment"># แก้ไขทั้งหมด</span>
<span class="c-keyword">PATCH</span>  <span class="c-string">/api/v1/products/42</span>       <span class="c-comment"># แก้ไขบางส่วน</span>
<span class="c-keyword">DELETE</span> <span class="c-string">/api/v1/products/42</span>       <span class="c-comment"># ลบสินค้า</span>

<span class="c-comment"># Nested Resources</span>
<span class="c-keyword">GET</span>    <span class="c-string">/api/v1/products/42/reviews</span>
<span class="c-keyword">POST</span>   <span class="c-string">/api/v1/products/42/reviews</span>

<span class="c-comment"># Filtering, Sorting, Pagination</span>
<span class="c-keyword">GET</span>    <span class="c-string">/api/v1/products</span><span class="c-attr">?category=software</span>
         <span class="c-attr">&amp;sort=-price</span>
         <span class="c-attr">&amp;page=2</span>
         <span class="c-attr">&amp;per_page=20</span>

<span class="c-comment"># Response Format (JSON)</span>
{
  <span class="c-string">"data"</span>: [...],
  <span class="c-string">"meta"</span>: {
    <span class="c-string">"current_page"</span>: <span class="c-number">2</span>,
    <span class="c-string">"total"</span>: <span class="c-number">150</span>,
    <span class="c-string">"per_page"</span>: <span class="c-number">20</span>
  },
  <span class="c-string">"links"</span>: {
    <span class="c-string">"next"</span>: <span class="c-string">"/api/v1/products?page=3"</span>,
    <span class="c-string">"prev"</span>: <span class="c-string">"/api/v1/products?page=1"</span>
  }
}</code></pre>
            </div>
        </div>

        <!-- API: Error Handling -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                <h3 class="font-bold text-slate-800">Error Handling — จัดการ Error</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">HTTP Status Codes & Error Response / Proper error responses</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">JSON — API Errors</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// 200 OK — สำเร็จ</span>
{ <span class="c-string">"data"</span>: { <span class="c-string">"id"</span>: <span class="c-number">42</span>, <span class="c-string">"name"</span>: <span class="c-string">"Product"</span> } }

<span class="c-comment">// 201 Created — สร้างสำเร็จ</span>
{ <span class="c-string">"data"</span>: { ... }, <span class="c-string">"message"</span>: <span class="c-string">"สร้างเรียบร้อย"</span> }

<span class="c-comment">// 400 Bad Request — ข้อมูลไม่ถูกต้อง</span>
{
  <span class="c-string">"error"</span>: {
    <span class="c-string">"code"</span>: <span class="c-string">"VALIDATION_ERROR"</span>,
    <span class="c-string">"message"</span>: <span class="c-string">"ข้อมูลไม่ถูกต้อง"</span>,
    <span class="c-string">"details"</span>: {
      <span class="c-string">"name"</span>: [<span class="c-string">"กรุณากรอกชื่อสินค้า"</span>],
      <span class="c-string">"price"</span>: [<span class="c-string">"ราคาต้องมากกว่า 0"</span>]
    }
  }
}

<span class="c-comment">// 401 Unauthorized — ไม่ได้ล็อกอิน</span>
{
  <span class="c-string">"error"</span>: {
    <span class="c-string">"code"</span>: <span class="c-string">"UNAUTHENTICATED"</span>,
    <span class="c-string">"message"</span>: <span class="c-string">"กรุณาเข้าสู่ระบบ"</span>
  }
}

<span class="c-comment">// 404 Not Found</span>
{
  <span class="c-string">"error"</span>: {
    <span class="c-string">"code"</span>: <span class="c-string">"NOT_FOUND"</span>,
    <span class="c-string">"message"</span>: <span class="c-string">"ไม่พบสินค้าที่ต้องการ"</span>
  }
}

<span class="c-comment">// 429 Too Many Requests</span>
{
  <span class="c-string">"error"</span>: {
    <span class="c-string">"code"</span>: <span class="c-string">"RATE_LIMITED"</span>,
    <span class="c-string">"message"</span>: <span class="c-string">"คำขอมากเกินไป กรุณารอสักครู่"</span>,
    <span class="c-string">"retry_after"</span>: <span class="c-number">60</span>
  }
}</code></pre>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- 10. DOCKER --}}
{{-- ============================================================ --}}
<section id="sec-docker">
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-700 flex items-center justify-center shadow-lg shadow-sky-500/20 gloss">
            <span class="text-white text-xl font-black">🐳</span>
        </div>
        <div>
            <h2 class="text-3xl font-black academy-section-title">Docker</h2>
            <p class="text-slate-500 text-sm">Containerization & Deployment / คอนเทนเนอร์สำหรับ Deploy</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">DevOps</span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        <!-- Docker: Dockerfile -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                <h3 class="font-bold text-slate-800">Dockerfile — สร้าง Image</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">Multi-stage build สำหรับ Laravel / Optimized production image</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Dockerfile</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment"># Stage 1: Build assets</span>
<span class="c-keyword">FROM</span> <span class="c-type">node:20-alpine</span> <span class="c-keyword">AS</span> <span class="c-attr">assets</span>
<span class="c-keyword">WORKDIR</span> <span class="c-string">/app</span>
<span class="c-keyword">COPY</span> package*.json ./
<span class="c-keyword">RUN</span> npm ci
<span class="c-keyword">COPY</span> vite.config.js tailwind.config.js ./
<span class="c-keyword">COPY</span> resources/ resources/
<span class="c-keyword">RUN</span> npm run build

<span class="c-comment"># Stage 2: PHP production</span>
<span class="c-keyword">FROM</span> <span class="c-type">php:8.3-fpm-alpine</span>
<span class="c-keyword">WORKDIR</span> <span class="c-string">/var/www</span>

<span class="c-comment"># Install extensions</span>
<span class="c-keyword">RUN</span> docker-php-ext-install \
    pdo_mysql bcmath opcache

<span class="c-comment"># Install Composer</span>
<span class="c-keyword">COPY</span> --from=<span class="c-type">composer:latest</span> \
    /usr/bin/composer /usr/bin/composer

<span class="c-comment"># Install dependencies (no dev)</span>
<span class="c-keyword">COPY</span> composer.* ./
<span class="c-keyword">RUN</span> composer install --no-dev \
    --optimize-autoloader --no-scripts

<span class="c-comment"># Copy application code</span>
<span class="c-keyword">COPY</span> . .
<span class="c-keyword">COPY</span> --from=<span class="c-attr">assets</span> /app/public_html/build \
    public_html/build

<span class="c-comment"># Optimize Laravel</span>
<span class="c-keyword">RUN</span> php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

<span class="c-keyword">EXPOSE</span> <span class="c-number">9000</span>
<span class="c-keyword">CMD</span> [<span class="c-string">"php-fpm"</span>]</code></pre>
            </div>
        </div>

        <!-- Docker: docker-compose -->
        <div class="academy-card rounded-2xl p-6 gloss">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                <h3 class="font-bold text-slate-800">Docker Compose — จัดการหลาย Container</h3>
            </div>
            <p class="text-sm text-slate-500 mb-4">App + DB + Redis + Nginx / Full stack orchestration</p>
            <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">YAML — docker-compose.yml</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-attr">services</span>:
  <span class="c-attr">app</span>:
    <span class="c-attr">build</span>: <span class="c-string">.</span>
    <span class="c-attr">volumes</span>:
      - <span class="c-string">./storage:/var/www/storage</span>
    <span class="c-attr">depends_on</span>:
      - <span class="c-string">mysql</span>
      - <span class="c-string">redis</span>
    <span class="c-attr">environment</span>:
      <span class="c-attr">DB_HOST</span>: <span class="c-string">mysql</span>
      <span class="c-attr">REDIS_HOST</span>: <span class="c-string">redis</span>

  <span class="c-attr">nginx</span>:
    <span class="c-attr">image</span>: <span class="c-string">nginx:alpine</span>
    <span class="c-attr">ports</span>:
      - <span class="c-string">"80:80"</span>
      - <span class="c-string">"443:443"</span>
    <span class="c-attr">volumes</span>:
      - <span class="c-string">./nginx.conf:/etc/nginx/conf.d/default.conf</span>
    <span class="c-attr">depends_on</span>:
      - <span class="c-string">app</span>

  <span class="c-attr">mysql</span>:
    <span class="c-attr">image</span>: <span class="c-string">mysql:8.0</span>
    <span class="c-attr">environment</span>:
      <span class="c-attr">MYSQL_DATABASE</span>: <span class="c-string">xmanstudio</span>
      <span class="c-attr">MYSQL_ROOT_PASSWORD</span>: <span class="c-string">${DB_PASSWORD}</span>
    <span class="c-attr">volumes</span>:
      - <span class="c-string">mysql_data:/var/lib/mysql</span>
    <span class="c-attr">ports</span>:
      - <span class="c-string">"3306:3306"</span>

  <span class="c-attr">redis</span>:
    <span class="c-attr">image</span>: <span class="c-string">redis:7-alpine</span>
    <span class="c-attr">ports</span>:
      - <span class="c-string">"6379:6379"</span>

<span class="c-attr">volumes</span>:
  <span class="c-attr">mysql_data</span>:</code></pre>
            </div>
        </div>
    </div>
</section>

</div>{{-- end max-w-7xl --}}

<!-- ===== BACK TO TOP ===== -->
<div class="text-center py-12 academy-gradient">
    <a href="#categories" class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 text-sky-700 font-semibold border border-sky-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        กลับด้านบน / Back to Top
    </a>
    <p class="mt-6 text-slate-400 text-sm">XMAN Code Academy &copy; {{ date('Y') }} — แหล่งเรียนรู้โค้ดมืออาชีพ</p>
</div>

</div>{{-- end academy-gradient --}}

@push('scripts')
<script>
function copyCode(btn) {
    const codeBlock = btn.closest('.code-block').querySelector('code');
    const text = codeBlock.innerText;
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = 'rgba(34, 197, 94, 0.2)';
        btn.style.borderColor = 'rgba(34, 197, 94, 0.4)';
        btn.style.color = '#86efac';
        setTimeout(() => {
            btn.textContent = original;
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2000);
    });
}
</script>
@endpush
@endsection
