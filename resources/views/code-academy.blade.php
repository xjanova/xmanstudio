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
        background: linear-gradient(145deg, #f8fafc 0%, #f0f9ff 50%, #fefce8 100%);
        border: 1px solid rgba(148, 197, 253, 0.45);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(14, 116, 144, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }
    .code-block-header {
        background: linear-gradient(90deg, #e0f2fe 0%, #f0f9ff 50%, #fef9ef 100%);
        border-bottom: 1px solid rgba(148, 197, 253, 0.35);
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
    .code-block-dots span:nth-child(1) { background: #f87171; }
    .code-block-dots span:nth-child(2) { background: #fbbf24; }
    .code-block-dots span:nth-child(3) { background: #34d399; }
    .code-block-lang {
        color: #0369a1;
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
        color: #1e293b;
        margin: 0;
    }
    /* Syntax Colors — Light Theme */
    .c-keyword { color: #7c3aed; font-weight: 600; }   /* purple - keywords */
    .c-type { color: #0891b2; }                          /* teal - types/classes */
    .c-func { color: #b45309; }                          /* amber - functions */
    .c-string { color: #15803d; }                        /* green - strings */
    .c-number { color: #c2410c; }                        /* orange - numbers */
    .c-comment { color: #94a3b8; font-style: italic; }   /* gray - comments */
    .c-var { color: #be185d; }                           /* pink - variables */
    .c-attr { color: #1d4ed8; }                          /* blue - attributes */
    .c-tag { color: #dc2626; }                           /* red - HTML tags */
    .c-op { color: #64748b; }                            /* gray - operators */
    .c-prop { color: #0e7490; }                          /* cyan - properties */
    .c-decorator { color: #7c3aed; }                     /* purple - decorators */
    .c-builtin { color: #e11d48; }                       /* rose - built-in */
    .c-const { color: #0d9488; }                         /* teal - constants */

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
        background: rgba(14, 165, 233, 0.1);
        border: 1px solid rgba(14, 165, 233, 0.3);
        color: #0369a1;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .copy-btn:hover {
        background: rgba(14, 165, 233, 0.2);
        color: #0c4a6e;
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

    /* ===== Encyclopedia Sidebar ===== */
    .enc-sidebar {
        width: 280px;
        min-height: calc(100vh - 200px);
        background: linear-gradient(145deg, #ffffff, #fefce8);
        border: 1px solid rgba(186, 230, 253, 0.5);
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(14, 116, 144, 0.06);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #bae6fd transparent;
    }
    .enc-sidebar::-webkit-scrollbar { width: 4px; }
    .enc-sidebar::-webkit-scrollbar-track { background: transparent; }
    .enc-sidebar::-webkit-scrollbar-thumb { background: #bae6fd; border-radius: 2px; }
    .enc-sidebar-cat {
        padding: 10px 16px;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 10px;
        margin: 2px 8px;
        color: #334155;
    }
    .enc-sidebar-cat:hover { background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0369a1; }
    .enc-sidebar-cat.active { background: linear-gradient(135deg, #0ea5e9, #06b6d4); color: white; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.25); }
    .enc-sidebar-topic {
        padding: 6px 16px 6px 42px;
        font-size: 0.8rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s;
        border-radius: 6px;
        margin: 1px 8px;
    }
    .enc-sidebar-topic:hover { background: #e0f2fe; color: #0369a1; }
    .enc-accordion {
        border: 1px solid rgba(186, 230, 253, 0.6);
        border-radius: 16px;
        overflow: hidden;
        background: linear-gradient(145deg, #ffffff, #f8fafc, #fefce8);
        box-shadow: 0 4px 24px rgba(14, 116, 144, 0.06);
        transition: all 0.3s;
    }
    .enc-accordion:hover { box-shadow: 0 8px 32px rgba(14, 116, 144, 0.12); border-color: rgba(56, 189, 248, 0.4); }
    .enc-accordion-header {
        padding: 16px 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.2s;
    }
    .enc-accordion-header:hover { background: rgba(224, 242, 254, 0.3); }
    .enc-accordion-body { padding: 0 20px 20px; }
    @media (max-width: 1024px) {
        .enc-sidebar { position: fixed; top: 0; left: 0; z-index: 50; height: 100vh; border-radius: 0 16px 16px 0; transition: transform 0.3s ease; }
    }
    .enc-search {
        background: white;
        border: 1px solid rgba(186, 230, 253, 0.5);
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.85rem;
        width: 100%;
        outline: none;
        transition: border-color 0.2s;
    }
    .enc-search:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15); }
</style>
@endpush

<!-- ===== COMPACT HERO ===== -->
<section class="academy-hero relative overflow-hidden py-10 lg:py-12">
    <div class="float-shape w-48 h-48 bg-white top-5 left-10" style="animation-delay: 0s;"></div>
    <div class="float-shape w-32 h-32 bg-yellow-200 top-20 right-20" style="animation-delay: -5s;"></div>
    <div class="float-shape w-40 h-40 bg-cyan-200 bottom-0 right-10" style="animation-delay: -10s;"></div>
    <div class="absolute inset-0 opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%221%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
        <div :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'" style="transition: all 0.5s ease 0.1s;">
            <span class="inline-flex items-center gap-2 px-5 py-2 bg-white/20 backdrop-blur-md border border-white/30 rounded-full text-white text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Professional Code Reference
            </span>
        </div>
        <h1 class="mt-6 text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.2s;">XMAN Code Academy</h1>
        <p class="mt-2 text-lg md:text-xl font-semibold text-sky-100" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.3s;">ศูนย์เรียนรู้โค้ดมืออาชีพ</p>
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3 max-w-2xl mx-auto" :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'" style="transition: all 0.6s ease 0.4s;">
            <div class="stat-card rounded-xl px-3 py-2"><div class="text-xl font-black text-sky-700">10+</div><div class="text-xs font-semibold text-slate-500">หมวดหมู่</div></div>
            <div class="stat-card rounded-xl px-3 py-2"><div class="text-xl font-black text-sky-700">50+</div><div class="text-xs font-semibold text-slate-500">ตัวอย่างโค้ด</div></div>
            <div class="stat-card rounded-xl px-3 py-2"><div class="text-xl font-black text-sky-700">8+</div><div class="text-xs font-semibold text-slate-500">ภาษา</div></div>
            <div class="stat-card rounded-xl px-3 py-2"><div class="text-xl font-black text-sky-700">Free</div><div class="text-xs font-semibold text-slate-500">ฟรีทั้งหมด</div></div>
        </div>
    </div>
</section>

<!-- ===== MAIN ENCYCLOPEDIA LAYOUT ===== -->
<div x-data="academy()" class="academy-gradient min-h-screen">
<div class="max-w-[1400px] mx-auto px-4 flex gap-6 py-8 relative">

    <!-- Mobile sidebar toggle -->
    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden fixed bottom-6 left-6 z-[55] w-12 h-12 rounded-full bg-sky-500 text-white shadow-lg shadow-sky-500/30 flex items-center justify-center hover:bg-sky-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Overlay for mobile -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:leave="transition-opacity ease-in duration-150" @click="sidebarOpen = false" class="lg:hidden fixed inset-0 bg-black/30 z-40" style="display:none;"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside class="enc-sidebar shrink-0 p-4 sticky top-4 self-start hidden lg:block" :class="sidebarOpen ? 'translate-x-0 !block' : '-translate-x-full lg:translate-x-0'">
        <button @click="sidebarOpen = false" class="lg:hidden absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="mb-4">
            <input type="text" x-model="search" placeholder="&#x1F50D; ค้นหาหัวข้อ..." class="enc-search">
        </div>
        <nav class="space-y-1">
            <template x-for="cat in filteredCategories" :key="cat.id">
                <div>
                    <button @click="selectCat(cat.id)" class="enc-sidebar-cat" :class="activeCat === cat.id && 'active'">
                        <span class="flex items-center gap-2">
                            <span x-text="cat.icon"></span>
                            <span x-text="cat.name"></span>
                            <span class="text-xs opacity-60" x-text="'(' + cat.count + ')'"></span>
                        </span>
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-300" :class="activeCat === cat.id && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="activeCat === cat.id" x-collapse>
                        <template x-for="topic in cat.topics" :key="topic">
                            <div class="enc-sidebar-topic" x-text="topic"></div>
                        </template>
                    </div>
                </div>
            </template>
        </nav>
    </aside>

    <!-- ===== CONTENT AREA ===== -->
    <main class="flex-1 min-w-0">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2 text-sm text-slate-500">
            <a href="/" class="hover:text-sky-600 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span>Code Academy</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-sky-600 font-semibold" x-text="getCategoryName()"></span>
        </div>


{{-- LARAVEL --}}
<div x-show="activeCat === 'laravel'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center shadow-lg shadow-red-500/20 gloss">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012a.26.26 0 01-.064-.023L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034h.001L5.044.05a.375.375 0 01.375 0L9.933 2.697h.001c.015.01.027.021.04.033.013.01.026.018.038.028l.032.045c.01.011.02.021.025.033a.253.253 0 01.022.058c.006.011.012.021.015.033.008.032.013.065.013.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.009-.021.013-.032a.487.487 0 01.024-.059c.007-.012.018-.021.025-.033.01-.015.021-.03.033-.043.012-.012.025-.02.037-.028.014-.013.028-.024.042-.034h.001l4.513-2.647a.375.375 0 01.375 0l4.513 2.647c.016.01.027.021.042.031.012.01.025.019.036.03.013.014.024.028.034.044.008.012.019.021.024.033a.42.42 0 01.024.06c.006.01.012.02.013.03zm-.74 5.032V5.862l-1.58.908-2.18 1.254v4.6zm-4.514 7.75v-4.6l-2.147 1.225-6.88 3.924v4.652zM1.093 3.624v14.588l8.273 4.761v-4.648l-4.322-2.445-.002-.003h-.002c-.015-.01-.027-.023-.04-.033-.013-.01-.027-.02-.037-.032l-.001-.002c-.013-.013-.022-.028-.033-.043-.01-.013-.021-.023-.028-.037v-.002c-.01-.015-.016-.032-.023-.048-.006-.013-.014-.025-.018-.038-.006-.02-.008-.042-.011-.063-.003-.014-.008-.025-.008-.039V6.085l-2.18-1.253zM5.23.81L1.47 3.02l3.76 2.21 3.758-2.21zm2.14 13.476l2.18-1.253V3.624l-1.58.91-2.178 1.253v9.409zM14.555 3.37l-3.758 2.21 3.758 2.21 3.759-2.21zm-.376 4.745l-2.18-1.254-1.58-.908v4.6l2.18 1.254 1.58.907zm-8.19 10.14l5.524-3.152 2.756-1.572-3.756-2.21-4.322 2.49-3.96 2.28z"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Laravel</h2>
            <p class="text-slate-500 text-sm">PHP Framework ยอดนิยมอันดับ 1 / #1 PHP Framework</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Essential</span>
    </div>

    
    <div class="space-y-4">
        <!-- Eloquent Model — การสร้างโมเดล -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Eloquent Model — การสร้างโมเดล</h3>
                        <p class="text-sm text-slate-500">โมเดล Eloquent สำหรับจัดการฐานข้อมูลอย่างมืออาชีพ / Professional Eloquent model with relationships</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Controller — CRUD Operations -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Controller — CRUD Operations</h3>
                        <p class="text-sm text-slate-500">Resource Controller พร้อม Validation / Full CRUD with validation & authorization</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Migration — การสร้างตาราง -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Migration — การสร้างตาราง</h3>
                        <p class="text-sm text-slate-500">สร้างและจัดการโครงสร้างฐานข้อมูล / Database schema management</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP — migration</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Middleware — ตรวจสอบสิทธิ์ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Middleware — ตรวจสอบสิทธิ์</h3>
                        <p class="text-sm text-slate-500">Custom Middleware สำหรับจัดการ Request / Request filtering & authentication</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP — Middleware</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Blade Template — เทมเพลตเอนจิน -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Blade Template — เทมเพลตเอนจิน</h3>
                        <p class="text-sm text-slate-500">Blade templating พร้อม Components / Templating with reusable components</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Blade</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Blade — products/index.blade.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
@verbatim
<pre><code><span class="c-decorator">@extends</span>(<span class="c-string">'layouts.app'</span>)
<span class="c-decorator">@section</span>(<span class="c-string">'title'</span>, <span class="c-string">'สินค้าทั้งหมด'</span>)

<span class="c-decorator">@section</span>(<span class="c-string">'content'</span>)
<span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"max-w-7xl mx-auto px-4 py-8"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;h1</span> <span class="c-attr">class</span>=<span class="c-string">"text-3xl font-bold mb-6"</span><span class="c-tag">&gt;</span>
        สินค้าทั้งหมด
    <span class="c-tag">&lt;/h1&gt;</span>

    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"grid md:grid-cols-3 gap-6"</span><span class="c-tag">&gt;</span>
        <span class="c-decorator">@forelse</span>(<span class="c-var">$products</span> <span class="c-keyword">as</span> <span class="c-var">$product</span>)
            <span class="c-tag">&lt;x-product-card</span>
                <span class="c-attr">:product</span>=<span class="c-string">"$product"</span>
                <span class="c-attr">:show-price</span>=<span class="c-string">"true"</span>
            <span class="c-tag">/&gt;</span>
        <span class="c-decorator">@empty</span>
            <span class="c-tag">&lt;p</span> <span class="c-attr">class</span>=<span class="c-string">"text-gray-500 col-span-3"</span><span class="c-tag">&gt;</span>
                ยังไม่มีสินค้า
            <span class="c-tag">&lt;/p&gt;</span>
        <span class="c-decorator">@endforelse</span>
    <span class="c-tag">&lt;/div&gt;</span>

    <span class="c-comment">&lt;!-- Pagination --&gt;</span>
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-string">"mt-8"</span><span class="c-tag">&gt;</span>
        {{ <span class="c-var">$products</span>-><span class="c-func">links</span>() }}
    <span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/div&gt;</span>
<span class="c-decorator">@endsection</span></code></pre>
@endverbatim
            </div>
                </div>
            </div>
        </div>

        <!-- API Resource — จัดรูปแบบ JSON -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">API Resource — จัดรูปแบบ JSON</h3>
                        <p class="text-sm text-slate-500">API Resource สำหรับส่งข้อมูล JSON / Transform models to JSON responses</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- PHP --}}
<div x-show="activeCat === 'php'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 gloss">
            <span class="text-white text-2xl font-black">P</span>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">PHP</h2>
            <p class="text-slate-500 text-sm">ภาษาพื้นฐานสำหรับ Web Development / Server-side scripting language</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Fundamental</span>
    </div>

    
    <div class="space-y-4">
        <!-- OOP — Class & Interface -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">OOP — Class & Interface</h3>
                        <p class="text-sm text-slate-500">การเขียน Object-Oriented PHP อย่างมืออาชีพ / Professional OOP patterns</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Array Functions — ฟังก์ชันอาเรย์ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Array Functions — ฟังก์ชันอาเรย์</h3>
                        <p class="text-sm text-slate-500">ฟังก์ชันอาเรย์ที่ใช้บ่อยที่สุด / Most common array operations</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Enum (PHP 8.1+) — ค่าคงที่แบบมีโครงสร้าง -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Enum (PHP 8.1+) — ค่าคงที่แบบมีโครงสร้าง</h3>
                        <p class="text-sm text-slate-500">Backed Enum สำหรับสถานะต่างๆ / Type-safe enumerations</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Error Handling — การจัดการข้อผิดพลาด -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Error Handling — การจัดการข้อผิดพลาด</h3>
                        <p class="text-sm text-slate-500">Try-catch, Custom Exception / Robust error handling patterns</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- JS --}}
<div x-show="activeCat === 'js'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center shadow-lg shadow-yellow-500/20 gloss">
            <span class="text-slate-900 text-2xl font-black">JS</span>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">JavaScript</h2>
            <p class="text-slate-500 text-sm">ภาษาสำหรับ Web ทั้ง Frontend & Backend / The language of the web</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Must Know</span>
    </div>

    
    <div class="space-y-4">
        <!-- ES6+ Modern Syntax -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">ES6+ Modern Syntax</h3>
                        <p class="text-sm text-slate-500">ไวยากรณ์สมัยใหม่ที่ต้องรู้ / Essential modern JavaScript features</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">JavaScript</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Async / Await — การทำงานแบบ Asynchronous -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Async / Await — การทำงานแบบ Asynchronous</h3>
                        <p class="text-sm text-slate-500">Promise, async/await, error handling / Asynchronous programming patterns</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">JavaScript</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- DOM — จัดการ HTML ด้วย JS -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">DOM — จัดการ HTML ด้วย JS</h3>
                        <p class="text-sm text-slate-500">สร้าง, แก้ไข, ลบ Element / Practical DOM manipulation</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">JavaScript</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Alpine.js — Lightweight Interactivity -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Alpine.js — Lightweight Interactivity</h3>
                        <p class="text-sm text-slate-500">เฟรมเวิร์กเบาๆ คู่หู Tailwind / Perfect companion for Tailwind CSS</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">HTML — Alpine.js</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- TAILWIND --}}
<div x-show="activeCat === 'tailwind'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-500 to-sky-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 gloss">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.001 4.8c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624C13.666 10.618 15.027 12 18.001 12c3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C16.337 6.182 14.976 4.8 12.001 4.8zm-6 7.2c-3.2 0-5.2 1.6-6 4.8 1.2-1.6 2.6-2.2 4.2-1.8.913.228 1.565.89 2.288 1.624 1.177 1.194 2.538 2.576 5.512 2.576 3.2 0 5.2-1.6 6-4.8-1.2 1.6-2.6 2.2-4.2 1.8-.913-.228-1.565-.89-2.288-1.624C10.337 13.382 8.976 12 6.001 12z"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Tailwind CSS</h2>
            <p class="text-slate-500 text-sm">Utility-First CSS Framework / เขียน CSS ด้วย Class</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Design</span>
    </div>

    
    <div class="space-y-4">
        <!-- Responsive Card — การ์ดแบบ Responsive -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Responsive Card — การ์ดแบบ Responsive</h3>
                        <p class="text-sm text-slate-500">ออกแบบการ์ดที่สวยงามทุกหน้าจอ / Beautiful cards for all screen sizes</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">HTML — Tailwind</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Flexbox & Grid Layout -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Flexbox & Grid Layout</h3>
                        <p class="text-sm text-slate-500">จัดวาง Layout แบบมืออาชีพ / Professional layout patterns</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">HTML — Tailwind</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- PYTHON --}}
<div x-show="activeCat === 'python'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-green-500 flex items-center justify-center shadow-lg shadow-blue-500/20 gloss">
            <span class="text-white text-2xl font-black">Py</span>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Python</h2>
            <p class="text-slate-500 text-sm">ภาษายอดนิยมสำหรับ AI, Data Science & Automation</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Popular</span>
    </div>

    
    <div class="space-y-4">
        <!-- Dataclass & Type Hints -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Dataclass & Type Hints</h3>
                        <p class="text-sm text-slate-500">โค้ด Python สมัยใหม่ที่อ่านง่าย / Modern Python with type safety</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Python</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- FastAPI — สร้าง API สมัยใหม่ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">FastAPI — สร้าง API สมัยใหม่</h3>
                        <p class="text-sm text-slate-500">REST API ด้วย FastAPI + Pydantic / High-performance async API</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Python — FastAPI</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- FLUTTER --}}
<div x-show="activeCat === 'flutter'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/20 gloss">
            <span class="text-white text-xl font-black">FL</span>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Flutter / Dart</h2>
            <p class="text-slate-500 text-sm">สร้างแอป Cross-Platform จากโค้ดเดียว / One codebase, all platforms</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Mobile</span>
    </div>

    
    <div class="space-y-4">
        <!-- StatefulWidget — หน้าจอแบบมี State -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">StatefulWidget — หน้าจอแบบมี State</h3>
                        <p class="text-sm text-slate-500">Widget ที่มีสถานะเปลี่ยนแปลงได้ / Interactive widgets with state management</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Dart — Flutter</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- API Service — เชื่อมต่อ Backend -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">API Service — เชื่อมต่อ Backend</h3>
                        <p class="text-sm text-slate-500">Http client สำหรับเรียก REST API / Clean API service pattern</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Dart — Flutter</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- SQL --}}
<div x-show="activeCat === 'sql'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">SQL / Database</h2>
            <p class="text-slate-500 text-sm">ภาษาจัดการฐานข้อมูล / Database management & optimization</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Core</span>
    </div>

    
    <div class="space-y-4">
        <!-- CRUD Operations — คำสั่งพื้นฐาน -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">CRUD Operations — คำสั่งพื้นฐาน</h3>
                        <p class="text-sm text-slate-500">SELECT, INSERT, UPDATE, DELETE / Essential SQL commands</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">SQL</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Index & Optimization — เพิ่มความเร็ว -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Index & Optimization — เพิ่มความเร็ว</h3>
                        <p class="text-sm text-slate-500">สร้าง Index, Subquery, CTE / Performance optimization techniques</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">SQL</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- GIT --}}
<div x-show="activeCat === 'git'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M23.546 10.93L13.067.452c-.604-.603-1.582-.603-2.188 0L8.708 2.627l2.76 2.76c.645-.215 1.379-.07 1.889.441.516.515.658 1.258.438 1.9l2.66 2.66c.643-.222 1.387-.078 1.9.435.721.72.721 1.884 0 2.604-.72.719-1.886.719-2.605 0-.538-.536-.674-1.337-.404-1.996L12.86 8.955v6.525c.176.086.342.203.488.348.713.721.713 1.883 0 2.6-.719.721-1.889.721-2.609 0-.719-.719-.719-1.879 0-2.598.182-.18.387-.316.605-.406V8.835c-.217-.091-.424-.222-.6-.401-.545-.545-.676-1.342-.396-2.009L7.636 3.7.45 10.881c-.6.605-.6 1.584 0 2.189l10.48 10.477c.604.604 1.582.604 2.186 0l10.43-10.43c.605-.603.605-1.582 0-2.187"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Git</h2>
            <p class="text-slate-500 text-sm">ระบบจัดการเวอร์ชัน / Version control system</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Essential</span>
    </div>

    
    <div class="space-y-4">
        <!-- Essential Commands — คำสั่งที่ใช้ทุกวัน -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Essential Commands — คำสั่งที่ใช้ทุกวัน</h3>
                        <p class="text-sm text-slate-500">คำสั่ง Git ที่ต้องรู้ / Daily Git workflow commands</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Bash — Git</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Advanced Git — เทคนิคขั้นสูง -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Advanced Git — เทคนิคขั้นสูง</h3>
                        <p class="text-sm text-slate-500">Cherry-pick, Bisect, Reflog / Pro-level Git techniques</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Bash — Git</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- API --}}
<div x-show="activeCat === 'api'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20 gloss">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">REST API Design</h2>
            <p class="text-slate-500 text-sm">ออกแบบ API ที่ดี / Professional API design patterns</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Backend</span>
    </div>

    
    <div class="space-y-4">
        <!-- API Endpoints — ออกแบบ URL -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-violet-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">API Endpoints — ออกแบบ URL</h3>
                        <p class="text-sm text-slate-500">RESTful URL patterns & HTTP methods / Standard API design</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">REST API Design</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Error Handling — จัดการ Error -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-violet-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Error Handling — จัดการ Error</h3>
                        <p class="text-sm text-slate-500">HTTP Status Codes & Error Response / Proper error responses</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">JSON — API Errors</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- DOCKER --}}
<div x-show="activeCat === 'docker'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-700 flex items-center justify-center shadow-lg shadow-sky-500/20 gloss">
            <span class="text-white text-xl font-black">🐳</span>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Docker</h2>
            <p class="text-slate-500 text-sm">Containerization & Deployment / คอนเทนเนอร์สำหรับ Deploy</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">DevOps</span>
    </div>

    
    <div class="space-y-4">
        <!-- Dockerfile — สร้าง Image -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-sky-600 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Dockerfile — สร้าง Image</h3>
                        <p class="text-sm text-slate-500">Multi-stage build สำหรับ Laravel / Optimized production image</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Dockerfile</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
            </div>
        </div>

        <!-- Docker Compose — จัดการหลาย Container -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-sky-600 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Docker Compose — จัดการหลาย Container</h3>
                        <p class="text-sm text-slate-500">App + DB + Redis + Nginx / Full stack orchestration</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">YAML</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
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
        </div>

    </div>
</div>


{{-- LINEOA --}}
<div x-show="activeCat === 'lineoa'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/20 gloss">
            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.271.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">LINE OA — Messaging API</h2>
            <p class="text-slate-500 text-sm">เชื่อมต่อ LINE Official Account กับ Laravel / LINE Notification & Chatbot Integration</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Thailand Essential</span>
    </div>


    <div class="space-y-4">

<!-- Intro Box -->
    <div class="academy-card rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0 mt-1">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 mb-2">ขั้นตอนภาพรวม — Overview Steps</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                        <span class="text-sm font-medium text-slate-700">สร้าง LINE OA + Messaging API Channel</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                        <span class="text-sm font-medium text-slate-700">ตั้งค่า .env + สร้าง Service Class</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                        <span class="text-sm font-medium text-slate-700">สร้าง Webhook รับข้อความ</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center">4</span>
                        <span class="text-sm font-medium text-slate-700">ส่งแจ้งเตือนอัตโนมัติ (Push / Flex)</span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-500">
                    <strong>ต้องมี:</strong> LINE Official Account (ฟรี), LINE Developers Console, Laravel 10+, PHP 8.1+, HTTPS domain (สำหรับ Webhook)
                </p>
            </div>
        </div>
    </div>

        <!-- Step 1 — ตั้งค่า Environment -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Step 1 — ตั้งค่า Environment</h3>
                        <p class="text-sm text-slate-500">ตั้งค่า Channel Access Token & Channel Secret จาก LINE Developers Console</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">.env</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">.env</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment"># =============================================</span>
<span class="c-comment"># LINE OA — Messaging API Configuration</span>
<span class="c-comment"># =============================================</span>
<span class="c-comment"># ได้จาก https://developers.line.biz/console/</span>
<span class="c-comment"># → เลือก Provider → เลือก Channel</span>
<span class="c-comment"># =============================================</span>

<span class="c-comment"># Channel Secret (Basic settings tab)</span>
<span class="c-attr">LINE_CHANNEL_SECRET</span><span class="c-op">=</span><span class="c-string">your_channel_secret_here</span>

<span class="c-comment"># Channel Access Token (Messaging API tab)</span>
<span class="c-comment"># กด "Issue" เพื่อสร้าง Long-lived token</span>
<span class="c-attr">LINE_CHANNEL_ACCESS_TOKEN</span><span class="c-op">=</span><span class="c-string">your_channel_access_token_here</span>

<span class="c-comment"># (Optional) สำหรับ LINE Login</span>
<span class="c-attr">LINE_LOGIN_CHANNEL_ID</span><span class="c-op">=</span><span class="c-string">your_login_channel_id</span>
<span class="c-attr">LINE_LOGIN_CHANNEL_SECRET</span><span class="c-op">=</span><span class="c-string">your_login_channel_secret</span>
<span class="c-attr">LINE_LOGIN_CALLBACK_URL</span><span class="c-op">=</span><span class="c-string">https://yoursite.com/auth/line/callback</span></code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — config/services.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// เพิ่มใน config/services.php</span>
<span class="c-string">'line'</span> <span class="c-op">=></span> [
    <span class="c-string">'channel_secret'</span>       <span class="c-op">=></span> <span class="c-func">env</span>(<span class="c-string">'LINE_CHANNEL_SECRET'</span>),
    <span class="c-string">'channel_access_token'</span> <span class="c-op">=></span> <span class="c-func">env</span>(<span class="c-string">'LINE_CHANNEL_ACCESS_TOKEN'</span>),
    <span class="c-string">'api_endpoint'</span>         <span class="c-op">=></span> <span class="c-string">'https://api.line.me/v2/bot'</span>,
],</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Step 2 — LineService Class (หลัก) -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Step 2 — LineService Class (หลัก)</h3>
                        <p class="text-sm text-slate-500">Service หลักสำหรับส่งข้อความทุกประเภท / Core LINE messaging service</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Services/LineService.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Services</span>;

<span class="c-keyword">use</span> <span class="c-type">Illuminate\Support\Facades\Http</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Support\Facades\Log</span>;

<span class="c-keyword">class</span> <span class="c-type">LineService</span>
{
    <span class="c-keyword">private</span> <span class="c-type">string</span> <span class="c-var">$accessToken</span>;
    <span class="c-keyword">private</span> <span class="c-type">string</span> <span class="c-var">$channelSecret</span>;
    <span class="c-keyword">private</span> <span class="c-type">string</span> <span class="c-var">$apiBase</span>;

    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>()
    {
        <span class="c-var">$this</span>-><span class="c-prop">accessToken</span>   <span class="c-op">=</span> <span class="c-func">config</span>(<span class="c-string">'services.line.channel_access_token'</span>);
        <span class="c-var">$this</span>-><span class="c-prop">channelSecret</span> <span class="c-op">=</span> <span class="c-func">config</span>(<span class="c-string">'services.line.channel_secret'</span>);
        <span class="c-var">$this</span>-><span class="c-prop">apiBase</span>       <span class="c-op">=</span> <span class="c-func">config</span>(<span class="c-string">'services.line.api_endpoint'</span>);
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// Push Message — ส่งข้อความหาคนเฉพาะเจาะจง</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">pushMessage</span>(
        <span class="c-type">string</span> <span class="c-var">$userId</span>,
        <span class="c-type">array</span>  <span class="c-var">$messages</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">sendRequest</span>(<span class="c-string">'/message/push'</span>, [
            <span class="c-string">'to'</span>       <span class="c-op">=></span> <span class="c-var">$userId</span>,
            <span class="c-string">'messages'</span> <span class="c-op">=></span> <span class="c-var">$messages</span>,
        ]);
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// Reply Message — ตอบกลับจาก Webhook</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">replyMessage</span>(
        <span class="c-type">string</span> <span class="c-var">$replyToken</span>,
        <span class="c-type">array</span>  <span class="c-var">$messages</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">sendRequest</span>(<span class="c-string">'/message/reply'</span>, [
            <span class="c-string">'replyToken'</span> <span class="c-op">=></span> <span class="c-var">$replyToken</span>,
            <span class="c-string">'messages'</span>   <span class="c-op">=></span> <span class="c-var">$messages</span>,
        ]);
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// Multicast — ส่งหาหลายคนพร้อมกัน</span>
    <span class="c-comment">// (สูงสุด 500 userId ต่อครั้ง)</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">multicast</span>(
        <span class="c-type">array</span> <span class="c-var">$userIds</span>,
        <span class="c-type">array</span> <span class="c-var">$messages</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">sendRequest</span>(<span class="c-string">'/message/multicast'</span>, [
            <span class="c-string">'to'</span>       <span class="c-op">=></span> <span class="c-var">$userIds</span>,
            <span class="c-string">'messages'</span> <span class="c-op">=></span> <span class="c-var">$messages</span>,
        ]);
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// Broadcast — ส่งหาทุกคนที่ add เพื่อน</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">broadcast</span>(<span class="c-type">array</span> <span class="c-var">$messages</span>): <span class="c-type">bool</span>
    {
        <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-func">sendRequest</span>(<span class="c-string">'/message/broadcast'</span>, [
            <span class="c-string">'messages'</span> <span class="c-op">=></span> <span class="c-var">$messages</span>,
        ]);
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// ดึงข้อมูลโปรไฟล์ผู้ใช้</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">getProfile</span>(<span class="c-type">string</span> <span class="c-var">$userId</span>): ?<span class="c-type">array</span>
    {
        <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">withToken</span>(<span class="c-var">$this</span>-><span class="c-prop">accessToken</span>)
            -><span class="c-func">get</span>(<span class="c-string">"{$this->apiBase}/profile/{$userId}"</span>);

        <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">successful</span>()
            ? <span class="c-var">$response</span>-><span class="c-func">json</span>()
            : <span class="c-const">null</span>;
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// ตรวจสอบ Signature จาก Webhook</span>
    <span class="c-comment">// (สำคัญมาก! ป้องกัน Spoofing)</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">public function</span> <span class="c-func">validateSignature</span>(
        <span class="c-type">string</span> <span class="c-var">$body</span>,
        <span class="c-type">string</span> <span class="c-var">$signature</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-var">$hash</span> <span class="c-op">=</span> <span class="c-func">hash_hmac</span>(
            <span class="c-string">'sha256'</span>,
            <span class="c-var">$body</span>,
            <span class="c-var">$this</span>-><span class="c-prop">channelSecret</span>,
            <span class="c-const">true</span>
        );

        <span class="c-keyword">return</span> <span class="c-func">hash_equals</span>(
            <span class="c-var">$signature</span>,
            <span class="c-func">base64_encode</span>(<span class="c-var">$hash</span>)
        );
    }

    <span class="c-comment">// ======================================</span>
    <span class="c-comment">// Private: ส่ง HTTP Request ไป LINE API</span>
    <span class="c-comment">// ======================================</span>
    <span class="c-keyword">private function</span> <span class="c-func">sendRequest</span>(
        <span class="c-type">string</span> <span class="c-var">$endpoint</span>,
        <span class="c-type">array</span>  <span class="c-var">$data</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">withToken</span>(<span class="c-var">$this</span>-><span class="c-prop">accessToken</span>)
            -><span class="c-func">post</span>(<span class="c-var">$this</span>-><span class="c-prop">apiBase</span> . <span class="c-var">$endpoint</span>, <span class="c-var">$data</span>);

        <span class="c-keyword">if</span> (<span class="c-var">$response</span>-><span class="c-func">failed</span>()) {
            <span class="c-type">Log</span>::<span class="c-func">error</span>(<span class="c-string">'LINE API Error'</span>, [
                <span class="c-string">'endpoint'</span> <span class="c-op">=></span> <span class="c-var">$endpoint</span>,
                <span class="c-string">'status'</span>   <span class="c-op">=></span> <span class="c-var">$response</span>-><span class="c-func">status</span>(),
                <span class="c-string">'body'</span>     <span class="c-op">=></span> <span class="c-var">$response</span>-><span class="c-func">json</span>(),
            ]);
        }

        <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">successful</span>();
    }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Step 3 — Webhook Controller (รับข้อความ) -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Step 3 — Webhook Controller (รับข้อความ)</h3>
                        <p class="text-sm text-slate-500">รับ Event จาก LINE (ข้อความ, follow, unfollow, postback) / Handle LINE webhook events</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Http/Controllers/LineWebhookController.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Http\Controllers</span>;

<span class="c-keyword">use</span> <span class="c-type">App\Services\LineService</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Http\Request</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Http\JsonResponse</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Support\Facades\Log</span>;

<span class="c-keyword">class</span> <span class="c-type">LineWebhookController</span> <span class="c-keyword">extends</span> <span class="c-type">Controller</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">private readonly</span> <span class="c-type">LineService</span> <span class="c-var">$line</span>,
    ) {}

    <span class="c-keyword">public function</span> <span class="c-func">handle</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>): <span class="c-type">JsonResponse</span>
    {
        <span class="c-comment">// 1) ตรวจสอบ Signature (ป้องกัน Spoofing)</span>
        <span class="c-var">$signature</span> <span class="c-op">=</span> <span class="c-var">$request</span>-><span class="c-func">header</span>(<span class="c-string">'X-Line-Signature'</span>, <span class="c-string">''</span>);
        <span class="c-var">$body</span>      <span class="c-op">=</span> <span class="c-var">$request</span>-><span class="c-func">getContent</span>();

        <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">validateSignature</span>(<span class="c-var">$body</span>, <span class="c-var">$signature</span>)) {
            <span class="c-type">Log</span>::<span class="c-func">warning</span>(<span class="c-string">'LINE Webhook: Invalid signature'</span>);
            <span class="c-keyword">return</span> <span class="c-func">response</span>()-><span class="c-func">json</span>([<span class="c-string">'error'</span> <span class="c-op">=></span> <span class="c-string">'Invalid'</span>], <span class="c-number">403</span>);
        }

        <span class="c-comment">// 2) วนลูปรับ Event ทั้งหมด</span>
        <span class="c-var">$events</span> <span class="c-op">=</span> <span class="c-var">$request</span>-><span class="c-func">input</span>(<span class="c-string">'events'</span>, []);

        <span class="c-keyword">foreach</span> (<span class="c-var">$events</span> <span class="c-keyword">as</span> <span class="c-var">$event</span>) {
            <span class="c-keyword">match</span> (<span class="c-var">$event</span>[<span class="c-string">'type'</span>]) {
                <span class="c-string">'message'</span>  <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">handleMessage</span>(<span class="c-var">$event</span>),
                <span class="c-string">'follow'</span>   <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">handleFollow</span>(<span class="c-var">$event</span>),
                <span class="c-string">'unfollow'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">handleUnfollow</span>(<span class="c-var">$event</span>),
                <span class="c-string">'postback'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">handlePostback</span>(<span class="c-var">$event</span>),
                <span class="c-keyword">default</span>    <span class="c-op">=></span> <span class="c-type">Log</span>::<span class="c-func">info</span>(<span class="c-string">'LINE event: '</span> . <span class="c-var">$event</span>[<span class="c-string">'type'</span>]),
            };
        }

        <span class="c-keyword">return</span> <span class="c-func">response</span>()-><span class="c-func">json</span>([<span class="c-string">'status'</span> <span class="c-op">=></span> <span class="c-string">'ok'</span>]);
    }

    <span class="c-comment">// =============================</span>
    <span class="c-comment">// จัดการข้อความที่ส่งเข้ามา</span>
    <span class="c-comment">// =============================</span>
    <span class="c-keyword">private function</span> <span class="c-func">handleMessage</span>(<span class="c-type">array</span> <span class="c-var">$event</span>): <span class="c-type">void</span>
    {
        <span class="c-var">$replyToken</span> <span class="c-op">=</span> <span class="c-var">$event</span>[<span class="c-string">'replyToken'</span>];
        <span class="c-var">$userId</span>     <span class="c-op">=</span> <span class="c-var">$event</span>[<span class="c-string">'source'</span>][<span class="c-string">'userId'</span>];
        <span class="c-var">$msgType</span>    <span class="c-op">=</span> <span class="c-var">$event</span>[<span class="c-string">'message'</span>][<span class="c-string">'type'</span>];
        <span class="c-var">$text</span>       <span class="c-op">=</span> <span class="c-var">$event</span>[<span class="c-string">'message'</span>][<span class="c-string">'text'</span>] <span class="c-op">??</span> <span class="c-string">''</span>;

        <span class="c-comment">// ตอบตามคำสั่ง</span>
        <span class="c-var">$reply</span> <span class="c-op">=</span> <span class="c-keyword">match</span> (<span class="c-func">mb_strtolower</span>(<span class="c-func">trim</span>(<span class="c-var">$text</span>))) {
            <span class="c-string">'สวัสดี'</span>, <span class="c-string">'hello'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">textMsg</span>(
                <span class="c-string">'สวัสดีครับ! 👋 ยินดีต้อนรับสู่ XMAN Studio'</span>
            ),
            <span class="c-string">'เมนู'</span>, <span class="c-string">'menu'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">menuMessage</span>(),
            <span class="c-string">'ราคา'</span>, <span class="c-string">'price'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">textMsg</span>(
                <span class="c-string">"💰 ราคาบริการ\n\n"</span>
                . <span class="c-string">"🔹 เว็บไซต์ เริ่มต้น 15,000฿\n"</span>
                . <span class="c-string">"🔹 แอปมือถือ เริ่มต้น 30,000฿\n"</span>
                . <span class="c-string">"🔹 ระบบ AI เริ่มต้น 50,000฿\n\n"</span>
                . <span class="c-string">"📞 ติดต่อเพิ่มเติม: พิมพ์ 'ติดต่อ'"</span>
            ),
            <span class="c-string">'ติดต่อ'</span>, <span class="c-string">'contact'</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">textMsg</span>(
                <span class="c-string">"📞 ช่องทางติดต่อ\n\n"</span>
                . <span class="c-string">"🌐 เว็บ: xman.studio\n"</span>
                . <span class="c-string">"📧 อีเมล: hello@xman.studio"</span>
            ),
            <span class="c-keyword">default</span> <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">textMsg</span>(
                <span class="c-string">"ขอบคุณสำหรับข้อความครับ 🙏\n"</span>
                . <span class="c-string">"พิมพ์ 'เมนู' เพื่อดูรายการคำสั่ง"</span>
            ),
        };

        <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">replyMessage</span>(<span class="c-var">$replyToken</span>, [<span class="c-var">$reply</span>]);
    }

    <span class="c-comment">// มีคนเพิ่มเพื่อน (follow)</span>
    <span class="c-keyword">private function</span> <span class="c-func">handleFollow</span>(<span class="c-type">array</span> <span class="c-var">$event</span>): <span class="c-type">void</span>
    {
        <span class="c-var">$userId</span> <span class="c-op">=</span> <span class="c-var">$event</span>[<span class="c-string">'source'</span>][<span class="c-string">'userId'</span>];
        <span class="c-var">$profile</span> <span class="c-op">=</span> <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">getProfile</span>(<span class="c-var">$userId</span>);

        <span class="c-comment">// บันทึกลง Database</span>
        <span class="c-type">LineFollower</span>::<span class="c-func">updateOrCreate</span>(
            [<span class="c-string">'line_user_id'</span> <span class="c-op">=></span> <span class="c-var">$userId</span>],
            [
                <span class="c-string">'display_name'</span> <span class="c-op">=></span> <span class="c-var">$profile</span>[<span class="c-string">'displayName'</span>] <span class="c-op">??</span> <span class="c-const">null</span>,
                <span class="c-string">'picture_url'</span>  <span class="c-op">=></span> <span class="c-var">$profile</span>[<span class="c-string">'pictureUrl'</span>] <span class="c-op">??</span> <span class="c-const">null</span>,
                <span class="c-string">'status'</span>       <span class="c-op">=></span> <span class="c-string">'active'</span>,
            ],
        );

        <span class="c-comment">// ส่งข้อความต้อนรับ</span>
        <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">replyMessage</span>(<span class="c-var">$event</span>[<span class="c-string">'replyToken'</span>], [
            <span class="c-var">$this</span>-><span class="c-func">textMsg</span>(
                <span class="c-string">"🎉 ยินดีต้อนรับสู่ XMAN Studio!\n\n"</span>
                . <span class="c-string">"พิมพ์ 'เมนู' เพื่อดูสิ่งที่เราทำได้"</span>
            ),
        ]);
    }

    <span class="c-comment">// มีคน unfollow</span>
    <span class="c-keyword">private function</span> <span class="c-func">handleUnfollow</span>(<span class="c-type">array</span> <span class="c-var">$event</span>): <span class="c-type">void</span>
    {
        <span class="c-type">LineFollower</span>::<span class="c-func">where</span>(<span class="c-string">'line_user_id'</span>, <span class="c-var">$event</span>[<span class="c-string">'source'</span>][<span class="c-string">'userId'</span>])
            -><span class="c-func">update</span>([<span class="c-string">'status'</span> <span class="c-op">=></span> <span class="c-string">'inactive'</span>]);
    }

    <span class="c-comment">// Postback จาก Flex Message / Rich Menu</span>
    <span class="c-keyword">private function</span> <span class="c-func">handlePostback</span>(<span class="c-type">array</span> <span class="c-var">$event</span>): <span class="c-type">void</span>
    {
        <span class="c-func">parse_str</span>(<span class="c-var">$event</span>[<span class="c-string">'postback'</span>][<span class="c-string">'data'</span>], <span class="c-var">$params</span>);

        <span class="c-keyword">match</span> (<span class="c-var">$params</span>[<span class="c-string">'action'</span>] <span class="c-op">??</span> <span class="c-string">''</span>) {
            <span class="c-string">'view_order'</span>  <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">sendOrderDetail</span>(<span class="c-var">$event</span>, <span class="c-var">$params</span>),
            <span class="c-string">'track'</span>       <span class="c-op">=></span> <span class="c-var">$this</span>-><span class="c-func">sendTrackingInfo</span>(<span class="c-var">$event</span>, <span class="c-var">$params</span>),
            <span class="c-keyword">default</span>       <span class="c-op">=></span> <span class="c-const">null</span>,
        };
    }

    <span class="c-comment">// Helper: สร้าง Text Message Object</span>
    <span class="c-keyword">private function</span> <span class="c-func">textMsg</span>(<span class="c-type">string</span> <span class="c-var">$text</span>): <span class="c-type">array</span>
    {
        <span class="c-keyword">return</span> [<span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>, <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-var">$text</span>];
    }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Step 4 — Route & CSRF Exemption -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Step 4 — Route & CSRF Exemption</h3>
                        <p class="text-sm text-slate-500">ลงทะเบียน Route และยกเว้น CSRF สำหรับ Webhook / Register webhook route</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — routes/api.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// routes/api.php</span>
<span class="c-comment">// API routes จะไม่มี CSRF token อยู่แล้ว</span>
<span class="c-keyword">use</span> <span class="c-type">App\Http\Controllers\LineWebhookController</span>;

<span class="c-type">Route</span>::<span class="c-func">post</span>(
    <span class="c-string">'/webhook/line'</span>,
    [<span class="c-type">LineWebhookController</span>::<span class="c-keyword">class</span>, <span class="c-string">'handle'</span>]
)-><span class="c-func">name</span>(<span class="c-string">'webhook.line'</span>);</code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — bootstrap/app.php (Laravel 11)</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ถ้าใช้ routes/web.php แทน api.php</span>
<span class="c-comment">// ต้องยกเว้น CSRF สำหรับ Webhook URL</span>
<span class="c-comment">// ======================================</span>

<span class="c-comment">// Laravel 11 — bootstrap/app.php</span>
-><span class="c-func">withMiddleware</span>(<span class="c-keyword">function</span> (<span class="c-type">Middleware</span> <span class="c-var">$middleware</span>) {
    <span class="c-var">$middleware</span>-><span class="c-func">validateCsrfTokens</span>(
        <span class="c-attr">except</span>: [
            <span class="c-string">'webhook/line'</span>,
            <span class="c-string">'webhook/*'</span>,  <span class="c-comment">// หรือยกเว้นทั้งหมด</span>
        ]
    );
})

<span class="c-comment">// ======================================</span>
<span class="c-comment">// Laravel 10 — app/Http/Middleware/</span>
<span class="c-comment">//               VerifyCsrfToken.php</span>
<span class="c-comment">// ======================================</span>
<span class="c-keyword">protected</span> <span class="c-var">$except</span> <span class="c-op">=</span> [
    <span class="c-string">'webhook/line'</span>,
];</code></pre>
            </div>
                    <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
                <div class="flex items-start gap-2">
                    <span class="text-amber-500 text-lg">⚠️</span>
                    <div class="text-sm text-amber-800">
                        <strong>สำคัญ!</strong> Webhook URL ที่ตั้งใน LINE Developers Console ต้องเป็น HTTPS<br>
                        ตัวอย่าง: <code class="bg-amber-100 px-1 rounded">https://yoursite.com/api/webhook/line</code><br>
                        สำหรับทดสอบในเครื่อง ใช้ <strong>ngrok</strong>: <code class="bg-amber-100 px-1 rounded">ngrok http 8000</code>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Message Types — ประเภทข้อความ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Message Types — ประเภทข้อความ</h3>
                        <p class="text-sm text-slate-500">Text, Image, Sticker, Quick Reply / All message types you can send</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — Message Objects</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ======================================</span>
<span class="c-comment">// 1) Text Message — ข้อความธรรมดา</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$textMsg</span> <span class="c-op">=</span> [
    <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
    <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">'สวัสดีครับ! 🎉'</span>,
];

<span class="c-comment">// ======================================</span>
<span class="c-comment">// 2) Text + Emoji</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$emojiMsg</span> <span class="c-op">=</span> [
    <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
    <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-string">'$ สั่งซื้อสำเร็จ $ ขอบคุณครับ'</span>,
    <span class="c-string">'emojis'</span> <span class="c-op">=></span> [
        [<span class="c-string">'index'</span> <span class="c-op">=></span> <span class="c-number">0</span>, <span class="c-string">'productId'</span> <span class="c-op">=></span> <span class="c-string">'5ac1bfd5040ab15980c9b435'</span>,
         <span class="c-string">'emojiId'</span> <span class="c-op">=></span> <span class="c-string">'001'</span>],
        [<span class="c-string">'index'</span> <span class="c-op">=></span> <span class="c-number">12</span>, <span class="c-string">'productId'</span> <span class="c-op">=></span> <span class="c-string">'5ac1bfd5040ab15980c9b435'</span>,
         <span class="c-string">'emojiId'</span> <span class="c-op">=></span> <span class="c-string">'002'</span>],
    ],
];

<span class="c-comment">// ======================================</span>
<span class="c-comment">// 3) Image Message — ส่งรูปภาพ</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$imageMsg</span> <span class="c-op">=</span> [
    <span class="c-string">'type'</span>               <span class="c-op">=></span> <span class="c-string">'image'</span>,
    <span class="c-string">'originalContentUrl'</span> <span class="c-op">=></span> <span class="c-string">'https://yoursite.com/img/promo.jpg'</span>,
    <span class="c-string">'previewImageUrl'</span>    <span class="c-op">=></span> <span class="c-string">'https://yoursite.com/img/promo-sm.jpg'</span>,
];

<span class="c-comment">// ======================================</span>
<span class="c-comment">// 4) Sticker Message — สติกเกอร์</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$stickerMsg</span> <span class="c-op">=</span> [
    <span class="c-string">'type'</span>      <span class="c-op">=></span> <span class="c-string">'sticker'</span>,
    <span class="c-string">'packageId'</span> <span class="c-op">=></span> <span class="c-string">'446'</span>,
    <span class="c-string">'stickerId'</span> <span class="c-op">=></span> <span class="c-string">'1988'</span>,
];

<span class="c-comment">// ======================================</span>
<span class="c-comment">// 5) Quick Reply — ปุ่มตอบด่วน</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$quickReply</span> <span class="c-op">=</span> [
    <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
    <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">'เลือกสิ่งที่ต้องการ:'</span>,
    <span class="c-string">'quickReply'</span> <span class="c-op">=></span> [
        <span class="c-string">'items'</span> <span class="c-op">=></span> [
            [
                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'action'</span>,
                <span class="c-string">'action'</span> <span class="c-op">=></span> [
                    <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'message'</span>,
                    <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'💰 ดูราคา'</span>,
                    <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">'ราคา'</span>,
                ],
            ],
            [
                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'action'</span>,
                <span class="c-string">'action'</span> <span class="c-op">=></span> [
                    <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'message'</span>,
                    <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'📞 ติดต่อ'</span>,
                    <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">'ติดต่อ'</span>,
                ],
            ],
            [
                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'action'</span>,
                <span class="c-string">'action'</span> <span class="c-op">=></span> [
                    <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'uri'</span>,
                    <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'🌐 เว็บไซต์'</span>,
                    <span class="c-string">'uri'</span>   <span class="c-op">=></span> <span class="c-string">'https://xman.studio'</span>,
                ],
            ],
        ],
    ],
];</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Flex Message — ข้อความสวยงาม (ใบเสร็จ) -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Flex Message — ข้อความสวยงาม (ใบเสร็จ)</h3>
                        <p class="text-sm text-slate-500">สร้าง Flex Message สำหรับแจ้งเตือนคำสั่งซื้อ / Beautiful order notification</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — Flex Message Builder</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// สร้าง Flex Message ใบเสร็จคำสั่งซื้อ</span>
<span class="c-keyword">public function</span> <span class="c-func">orderReceiptFlex</span>(<span class="c-type">Order</span> <span class="c-var">$order</span>): <span class="c-type">array</span>
{
    <span class="c-comment">// รายการสินค้า</span>
    <span class="c-var">$items</span> <span class="c-op">=</span> <span class="c-var">$order</span>-><span class="c-prop">items</span>-><span class="c-func">map</span>(<span class="c-keyword">fn</span> (<span class="c-var">$item</span>) <span class="c-op">=></span> [
        <span class="c-string">'type'</span>     <span class="c-op">=></span> <span class="c-string">'box'</span>,
        <span class="c-string">'layout'</span>   <span class="c-op">=></span> <span class="c-string">'horizontal'</span>,
        <span class="c-string">'contents'</span> <span class="c-op">=></span> [
            [
                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
                <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-var">$item</span>-><span class="c-prop">name</span>,
                <span class="c-string">'size'</span>   <span class="c-op">=></span> <span class="c-string">'sm'</span>,
                <span class="c-string">'color'</span>  <span class="c-op">=></span> <span class="c-string">'#555555'</span>,
                <span class="c-string">'flex'</span>   <span class="c-op">=></span> <span class="c-number">0</span>,
            ],
            [
                <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'text'</span>,
                <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">"฿"</span> . <span class="c-func">number_format</span>(<span class="c-var">$item</span>-><span class="c-prop">price</span>, <span class="c-number">2</span>),
                <span class="c-string">'size'</span>  <span class="c-op">=></span> <span class="c-string">'sm'</span>,
                <span class="c-string">'color'</span> <span class="c-op">=></span> <span class="c-string">'#111111'</span>,
                <span class="c-string">'align'</span> <span class="c-op">=></span> <span class="c-string">'end'</span>,
            ],
        ],
    ])-><span class="c-func">toArray</span>();

    <span class="c-keyword">return</span> [
        <span class="c-string">'type'</span>    <span class="c-op">=></span> <span class="c-string">'flex'</span>,
        <span class="c-string">'altText'</span> <span class="c-op">=></span> <span class="c-string">"ใบเสร็จ #{$order->order_number}"</span>,
        <span class="c-string">'contents'</span> <span class="c-op">=></span> [
            <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'bubble'</span>,
            <span class="c-string">'size'</span> <span class="c-op">=></span> <span class="c-string">'mega'</span>,

            <span class="c-comment">// === Header ===</span>
            <span class="c-string">'header'</span> <span class="c-op">=></span> [
                <span class="c-string">'type'</span>            <span class="c-op">=></span> <span class="c-string">'box'</span>,
                <span class="c-string">'layout'</span>          <span class="c-op">=></span> <span class="c-string">'vertical'</span>,
                <span class="c-string">'backgroundColor'</span> <span class="c-op">=></span> <span class="c-string">'#0ea5e9'</span>,
                <span class="c-string">'paddingAll'</span>      <span class="c-op">=></span> <span class="c-string">'20px'</span>,
                <span class="c-string">'contents'</span>        <span class="c-op">=></span> [
                    [
                        <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
                        <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-string">'XMAN STUDIO'</span>,
                        <span class="c-string">'color'</span>  <span class="c-op">=></span> <span class="c-string">'#ffffff'</span>,
                        <span class="c-string">'size'</span>   <span class="c-op">=></span> <span class="c-string">'lg'</span>,
                        <span class="c-string">'weight'</span> <span class="c-op">=></span> <span class="c-string">'bold'</span>,
                    ],
                    [
                        <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'text'</span>,
                        <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">'ใบเสร็จรับเงิน'</span>,
                        <span class="c-string">'color'</span> <span class="c-op">=></span> <span class="c-string">'#ffffffcc'</span>,
                        <span class="c-string">'size'</span>  <span class="c-op">=></span> <span class="c-string">'xs'</span>,
                    ],
                ],
            ],

            <span class="c-comment">// === Body (รายการสินค้า) ===</span>
            <span class="c-string">'body'</span> <span class="c-op">=></span> [
                <span class="c-string">'type'</span>     <span class="c-op">=></span> <span class="c-string">'box'</span>,
                <span class="c-string">'layout'</span>   <span class="c-op">=></span> <span class="c-string">'vertical'</span>,
                <span class="c-string">'contents'</span> <span class="c-op">=></span> [
                    [
                        <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
                        <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-string">"Order #{$order->order_number}"</span>,
                        <span class="c-string">'weight'</span> <span class="c-op">=></span> <span class="c-string">'bold'</span>,
                        <span class="c-string">'size'</span>   <span class="c-op">=></span> <span class="c-string">'xl'</span>,
                    ],
                    [<span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'separator'</span>, <span class="c-string">'margin'</span> <span class="c-op">=></span> <span class="c-string">'lg'</span>],

                    <span class="c-comment">// รายการสินค้า</span>
                    [
                        <span class="c-string">'type'</span>     <span class="c-op">=></span> <span class="c-string">'box'</span>,
                        <span class="c-string">'layout'</span>   <span class="c-op">=></span> <span class="c-string">'vertical'</span>,
                        <span class="c-string">'margin'</span>   <span class="c-op">=></span> <span class="c-string">'lg'</span>,
                        <span class="c-string">'spacing'</span>  <span class="c-op">=></span> <span class="c-string">'sm'</span>,
                        <span class="c-string">'contents'</span> <span class="c-op">=></span> <span class="c-var">$items</span>,
                    ],

                    [<span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'separator'</span>, <span class="c-string">'margin'</span> <span class="c-op">=></span> <span class="c-string">'lg'</span>],

                    <span class="c-comment">// ยอมรวม</span>
                    [
                        <span class="c-string">'type'</span>     <span class="c-op">=></span> <span class="c-string">'box'</span>,
                        <span class="c-string">'layout'</span>   <span class="c-op">=></span> <span class="c-string">'horizontal'</span>,
                        <span class="c-string">'margin'</span>   <span class="c-op">=></span> <span class="c-string">'lg'</span>,
                        <span class="c-string">'contents'</span> <span class="c-op">=></span> [
                            [
                                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
                                <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-string">'รวมทั้งหมด'</span>,
                                <span class="c-string">'size'</span>   <span class="c-op">=></span> <span class="c-string">'md'</span>,
                                <span class="c-string">'weight'</span> <span class="c-op">=></span> <span class="c-string">'bold'</span>,
                            ],
                            [
                                <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'text'</span>,
                                <span class="c-string">'text'</span>   <span class="c-op">=></span> <span class="c-string">"฿"</span> . <span class="c-func">number_format</span>(
                                    <span class="c-var">$order</span>-><span class="c-prop">total</span>, <span class="c-number">2</span>
                                ),
                                <span class="c-string">'size'</span>   <span class="c-op">=></span> <span class="c-string">'lg'</span>,
                                <span class="c-string">'weight'</span> <span class="c-op">=></span> <span class="c-string">'bold'</span>,
                                <span class="c-string">'color'</span>  <span class="c-op">=></span> <span class="c-string">'#0ea5e9'</span>,
                                <span class="c-string">'align'</span>  <span class="c-op">=></span> <span class="c-string">'end'</span>,
                            ],
                        ],
                    ],
                ],
            ],

            <span class="c-comment">// === Footer (ปุ่มกด) ===</span>
            <span class="c-string">'footer'</span> <span class="c-op">=></span> [
                <span class="c-string">'type'</span>     <span class="c-op">=></span> <span class="c-string">'box'</span>,
                <span class="c-string">'layout'</span>   <span class="c-op">=></span> <span class="c-string">'vertical'</span>,
                <span class="c-string">'spacing'</span>  <span class="c-op">=></span> <span class="c-string">'sm'</span>,
                <span class="c-string">'contents'</span> <span class="c-op">=></span> [
                    [
                        <span class="c-string">'type'</span>   <span class="c-op">=></span> <span class="c-string">'button'</span>,
                        <span class="c-string">'style'</span>  <span class="c-op">=></span> <span class="c-string">'primary'</span>,
                        <span class="c-string">'color'</span>  <span class="c-op">=></span> <span class="c-string">'#0ea5e9'</span>,
                        <span class="c-string">'action'</span> <span class="c-op">=></span> [
                            <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'uri'</span>,
                            <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'ดูรายละเอียดคำสั่งซื้อ'</span>,
                            <span class="c-string">'uri'</span>   <span class="c-op">=></span> <span class="c-func">route</span>(
                                <span class="c-string">'orders.show'</span>,
                                <span class="c-var">$order</span>
                            ),
                        ],
                    ],
                ],
            ],
        ],
    ];
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Laravel Notification Channel -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Laravel Notification Channel</h3>
                        <p class="text-sm text-slate-500">ใช้ระบบ Notification ของ Laravel ส่งผ่าน LINE / Integrate with Laravel's notification system</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Notifications/OrderPaidNotification.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Notifications</span>;

<span class="c-keyword">use</span> <span class="c-type">App\Channels\LineChannel</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Notifications\Notification</span>;

<span class="c-keyword">class</span> <span class="c-type">OrderPaidNotification</span> <span class="c-keyword">extends</span> <span class="c-type">Notification</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">public readonly</span> <span class="c-type">Order</span> <span class="c-var">$order</span>,
    ) {}

    <span class="c-comment">// กำหนดช่องทางส่ง</span>
    <span class="c-keyword">public function</span> <span class="c-func">via</span>(<span class="c-var">$notifiable</span>): <span class="c-type">array</span>
    {
        <span class="c-keyword">return</span> [<span class="c-string">'mail'</span>, <span class="c-type">LineChannel</span>::<span class="c-keyword">class</span>];
    }

    <span class="c-comment">// ข้อความสำหรับ LINE</span>
    <span class="c-keyword">public function</span> <span class="c-func">toLine</span>(<span class="c-var">$notifiable</span>): <span class="c-type">array</span>
    {
        <span class="c-keyword">return</span> [
            [
                <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
                <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">"✅ ชำระเงินสำเร็จ!\n\n"</span>
                    . <span class="c-string">"📦 คำสั่งซื้อ: #{$this->order->order_number}\n"</span>
                    . <span class="c-string">"💰 ยอดชำระ: ฿"</span> . <span class="c-func">number_format</span>(
                        <span class="c-var">$this</span>-><span class="c-prop">order</span>-><span class="c-prop">total</span>, <span class="c-number">2</span>
                    ) . <span class="c-string">"\n"</span>
                    . <span class="c-string">"📅 วันที่: "</span> . <span class="c-func">now</span>()-><span class="c-func">format</span>(<span class="c-string">'d/m/Y H:i'</span>)
                    . <span class="c-string">"\n\nขอบคุณที่ใช้บริการครับ 🙏"</span>,
            ],
        ];
    }
}</code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Channels/LineChannel.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Channels</span>;

<span class="c-keyword">use</span> <span class="c-type">App\Services\LineService</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Notifications\Notification</span>;

<span class="c-keyword">class</span> <span class="c-type">LineChannel</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">private readonly</span> <span class="c-type">LineService</span> <span class="c-var">$line</span>,
    ) {}

    <span class="c-keyword">public function</span> <span class="c-func">send</span>(
        <span class="c-var">$notifiable</span>,
        <span class="c-type">Notification</span> <span class="c-var">$notification</span>,
    ): <span class="c-type">void</span> {
        <span class="c-comment">// ดึง LINE User ID จาก Model</span>
        <span class="c-var">$lineUserId</span> <span class="c-op">=</span> <span class="c-var">$notifiable</span>-><span class="c-func">routeNotificationFor</span>(
            <span class="c-string">'line'</span>
        );

        <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">$lineUserId</span>) {
            <span class="c-keyword">return</span>;
        }

        <span class="c-var">$messages</span> <span class="c-op">=</span> <span class="c-var">$notification</span>-><span class="c-func">toLine</span>(<span class="c-var">$notifiable</span>);
        <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">pushMessage</span>(<span class="c-var">$lineUserId</span>, <span class="c-var">$messages</span>);
    }
}</code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Models/User.php (เพิ่ม method)</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// เพิ่มใน User Model</span>
<span class="c-keyword">public function</span> <span class="c-func">routeNotificationForLine</span>(): ?<span class="c-type">string</span>
{
    <span class="c-keyword">return</span> <span class="c-var">$this</span>-><span class="c-prop">line_user_id</span>; <span class="c-comment">// คอลัมน์ในตาราง users</span>
}

<span class="c-comment">// การใช้งาน</span>
<span class="c-var">$user</span>-><span class="c-func">notify</span>(<span class="c-keyword">new</span> <span class="c-type">OrderPaidNotification</span>(<span class="c-var">$order</span>));</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Rich Menu — เมนูด้านล่าง -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Rich Menu — เมนูด้านล่าง</h3>
                        <p class="text-sm text-slate-500">สร้างเมนูแบบกดได้ด้านล่างแชท / Create interactive bottom menu</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — Rich Menu API</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ======================================</span>
<span class="c-comment">// สร้าง Rich Menu (ทำครั้งเดียว)</span>
<span class="c-comment">// ======================================</span>
<span class="c-keyword">public function</span> <span class="c-func">createRichMenu</span>(): ?<span class="c-type">string</span>
{
    <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">withToken</span>(<span class="c-var">$this</span>-><span class="c-prop">accessToken</span>)
        -><span class="c-func">post</span>(<span class="c-string">'{$this->apiBase}/richmenu'</span>, [
            <span class="c-string">'size'</span> <span class="c-op">=></span> [
                <span class="c-string">'width'</span>  <span class="c-op">=></span> <span class="c-number">2500</span>,
                <span class="c-string">'height'</span> <span class="c-op">=></span> <span class="c-number">1686</span>, <span class="c-comment">// หรือ 843</span>
            ],
            <span class="c-string">'selected'</span>    <span class="c-op">=></span> <span class="c-const">true</span>,
            <span class="c-string">'name'</span>        <span class="c-op">=></span> <span class="c-string">'Main Menu'</span>,
            <span class="c-string">'chatBarText'</span> <span class="c-op">=></span> <span class="c-string">'เมนูหลัก'</span>,
            <span class="c-string">'areas'</span> <span class="c-op">=></span> [
                <span class="c-comment">// ปุ่มซ้ายบน — ดูสินค้า</span>
                [
                    <span class="c-string">'bounds'</span> <span class="c-op">=></span> [
                        <span class="c-string">'x'</span> <span class="c-op">=></span> <span class="c-number">0</span>, <span class="c-string">'y'</span> <span class="c-op">=></span> <span class="c-number">0</span>,
                        <span class="c-string">'width'</span> <span class="c-op">=></span> <span class="c-number">833</span>, <span class="c-string">'height'</span> <span class="c-op">=></span> <span class="c-number">843</span>,
                    ],
                    <span class="c-string">'action'</span> <span class="c-op">=></span> [
                        <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'uri'</span>,
                        <span class="c-string">'uri'</span>   <span class="c-op">=></span> <span class="c-string">'https://yoursite.com/products'</span>,
                        <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'ดูสินค้า'</span>,
                    ],
                ],
                <span class="c-comment">// ปุ่มกลางบน — ดูราคา</span>
                [
                    <span class="c-string">'bounds'</span> <span class="c-op">=></span> [
                        <span class="c-string">'x'</span> <span class="c-op">=></span> <span class="c-number">833</span>, <span class="c-string">'y'</span> <span class="c-op">=></span> <span class="c-number">0</span>,
                        <span class="c-string">'width'</span> <span class="c-op">=></span> <span class="c-number">834</span>, <span class="c-string">'height'</span> <span class="c-op">=></span> <span class="c-number">843</span>,
                    ],
                    <span class="c-string">'action'</span> <span class="c-op">=></span> [
                        <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'message'</span>,
                        <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">'ราคา'</span>,
                        <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'ดูราคา'</span>,
                    ],
                ],
                <span class="c-comment">// ปุ่มขวาบน — ติดต่อเรา</span>
                [
                    <span class="c-string">'bounds'</span> <span class="c-op">=></span> [
                        <span class="c-string">'x'</span> <span class="c-op">=></span> <span class="c-number">1667</span>, <span class="c-string">'y'</span> <span class="c-op">=></span> <span class="c-number">0</span>,
                        <span class="c-string">'width'</span> <span class="c-op">=></span> <span class="c-number">833</span>, <span class="c-string">'height'</span> <span class="c-op">=></span> <span class="c-number">843</span>,
                    ],
                    <span class="c-string">'action'</span> <span class="c-op">=></span> [
                        <span class="c-string">'type'</span>  <span class="c-op">=></span> <span class="c-string">'message'</span>,
                        <span class="c-string">'text'</span>  <span class="c-op">=></span> <span class="c-string">'ติดต่อ'</span>,
                        <span class="c-string">'label'</span> <span class="c-op">=></span> <span class="c-string">'ติดต่อเรา'</span>,
                    ],
                ],
            ],
        ]);

    <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">json</span>(<span class="c-string">'richMenuId'</span>);
}

<span class="c-comment">// อัพโหลดรูป Rich Menu (2500x1686 px)</span>
<span class="c-keyword">public function</span> <span class="c-func">uploadRichMenuImage</span>(
    <span class="c-type">string</span> <span class="c-var">$richMenuId</span>,
    <span class="c-type">string</span> <span class="c-var">$imagePath</span>,
): <span class="c-type">bool</span> {
    <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">withToken</span>(<span class="c-var">$this</span>-><span class="c-prop">accessToken</span>)
        -><span class="c-func">withHeaders</span>([
            <span class="c-string">'Content-Type'</span> <span class="c-op">=></span> <span class="c-string">'image/png'</span>,
        ])
        -><span class="c-func">withBody</span>(
            <span class="c-func">file_get_contents</span>(<span class="c-var">$imagePath</span>), <span class="c-string">'image/png'</span>
        )
        -><span class="c-func">post</span>(
            <span class="c-string">"https://api-data.line.me/v2/bot/richmenu"</span>
            . <span class="c-string">"/{$richMenuId}/content"</span>
        );

    <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">successful</span>();
}

<span class="c-comment">// ตั้งเป็น Default สำหรับทุกคน</span>
<span class="c-keyword">public function</span> <span class="c-func">setDefaultRichMenu</span>(
    <span class="c-type">string</span> <span class="c-var">$richMenuId</span>,
): <span class="c-type">bool</span> {
    <span class="c-keyword">return</span> <span class="c-type">Http</span>::<span class="c-func">withToken</span>(<span class="c-var">$this</span>-><span class="c-prop">accessToken</span>)
        -><span class="c-func">post</span>(
            <span class="c-string">"{$this->apiBase}/user/all/richmenu/{$richMenuId}"</span>
        )-><span class="c-func">successful</span>();
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Event Listener — แจ้งเตือนอัตโนมัติ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Event Listener — แจ้งเตือนอัตโนมัติ</h3>
                        <p class="text-sm text-slate-500">ส่งแจ้งเตือนอัตโนมัติเมื่อมี Event เกิดขึ้น / Auto-notify on business events</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Listeners/SendLineOrderNotification.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Listeners</span>;

<span class="c-keyword">use</span> <span class="c-type">App\Events\OrderPaid</span>;
<span class="c-keyword">use</span> <span class="c-type">App\Services\LineService</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Contracts\Queue\ShouldQueue</span>;

<span class="c-comment">// ใช้ Queue เพื่อไม่ให้ block ผู้ใช้</span>
<span class="c-keyword">class</span> <span class="c-type">SendLineOrderNotification</span>
    <span class="c-keyword">implements</span> <span class="c-type">ShouldQueue</span>
{
    <span class="c-keyword">public</span> <span class="c-type">int</span> <span class="c-var">$tries</span> <span class="c-op">=</span> <span class="c-number">3</span>;
    <span class="c-keyword">public</span> <span class="c-type">int</span> <span class="c-var">$backoff</span> <span class="c-op">=</span> <span class="c-number">10</span>; <span class="c-comment">// วินาที</span>

    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">private readonly</span> <span class="c-type">LineService</span> <span class="c-var">$line</span>,
    ) {}

    <span class="c-keyword">public function</span> <span class="c-func">handle</span>(<span class="c-type">OrderPaid</span> <span class="c-var">$event</span>): <span class="c-type">void</span>
    {
        <span class="c-var">$order</span> <span class="c-op">=</span> <span class="c-var">$event</span>-><span class="c-prop">order</span>;
        <span class="c-var">$user</span>  <span class="c-op">=</span> <span class="c-var">$order</span>-><span class="c-prop">user</span>;

        <span class="c-comment">// ส่งแจ้งลูกค้า</span>
        <span class="c-keyword">if</span> (<span class="c-var">$user</span>-><span class="c-prop">line_user_id</span>) {
            <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">pushMessage</span>(
                <span class="c-var">$user</span>-><span class="c-prop">line_user_id</span>,
                [<span class="c-var">$this</span>-><span class="c-func">orderReceiptFlex</span>(<span class="c-var">$order</span>)]
            );
        }

        <span class="c-comment">// ส่งแจ้ง Admin (Broadcast กลุ่ม admin)</span>
        <span class="c-var">$adminLineId</span> <span class="c-op">=</span> <span class="c-func">config</span>(<span class="c-string">'services.line.admin_user_id'</span>);
        <span class="c-keyword">if</span> (<span class="c-var">$adminLineId</span>) {
            <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">pushMessage</span>(<span class="c-var">$adminLineId</span>, [
                [
                    <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
                    <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">"🔔 คำสั่งซื้อใหม่!\n\n"</span>
                        . <span class="c-string">"📦 #{$order->order_number}\n"</span>
                        . <span class="c-string">"👤 {$user->name}\n"</span>
                        . <span class="c-string">"💰 ฿"</span> . <span class="c-func">number_format</span>(
                            <span class="c-var">$order</span>-><span class="c-prop">total</span>, <span class="c-number">2</span>
                        ),
                ],
            ]);
        }
    }
}</code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Providers/EventServiceProvider.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ลงทะเบียน Event → Listener</span>
<span class="c-keyword">protected</span> <span class="c-var">$listen</span> <span class="c-op">=</span> [
    <span class="c-type">OrderPaid</span>::<span class="c-keyword">class</span> <span class="c-op">=></span> [
        <span class="c-type">SendLineOrderNotification</span>::<span class="c-keyword">class</span>,
        <span class="c-type">SendEmailReceipt</span>::<span class="c-keyword">class</span>,
        <span class="c-type">UpdateInventory</span>::<span class="c-keyword">class</span>,
    ],
    <span class="c-type">OrderShipped</span>::<span class="c-keyword">class</span> <span class="c-op">=></span> [
        <span class="c-type">SendLineShippingNotification</span>::<span class="c-keyword">class</span>,
    ],
    <span class="c-type">LicenseActivated</span>::<span class="c-keyword">class</span> <span class="c-op">=></span> [
        <span class="c-type">SendLineLicenseInfo</span>::<span class="c-keyword">class</span>,
    ],
];</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- LINE Notify — ทางเลือกง่ายๆ -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">LINE Notify — ทางเลือกง่ายๆ</h3>
                        <p class="text-sm text-slate-500">ส่งแจ้งเตือนง่ายๆ ด้วย LINE Notify (ไม่ต้องมี OA) / Simple notification alternative</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — LINE Notify (ง่ายมาก!)</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ======================================</span>
<span class="c-comment">// LINE Notify — ส่งแจ้งเตือนแบบง่ายที่สุด</span>
<span class="c-comment">// ขอ Token: https://notify-bot.line.me/</span>
<span class="c-comment">// ======================================</span>

<span class="c-comment">// .env</span>
<span class="c-comment">// LINE_NOTIFY_TOKEN=your_token_here</span>

<span class="c-keyword">class</span> <span class="c-type">LineNotifyService</span>
{
    <span class="c-keyword">private const</span> <span class="c-const">API_URL</span> <span class="c-op">=</span>
        <span class="c-string">'https://notify-api.line.me/api/notify'</span>;

    <span class="c-keyword">public function</span> <span class="c-func">send</span>(
        <span class="c-type">string</span>  <span class="c-var">$message</span>,
        ?<span class="c-type">string</span> <span class="c-var">$imageUrl</span> <span class="c-op">=</span> <span class="c-const">null</span>,
        ?<span class="c-type">string</span> <span class="c-var">$stickerPackageId</span> <span class="c-op">=</span> <span class="c-const">null</span>,
        ?<span class="c-type">string</span> <span class="c-var">$stickerId</span> <span class="c-op">=</span> <span class="c-const">null</span>,
    ): <span class="c-type">bool</span> {
        <span class="c-var">$data</span> <span class="c-op">=</span> [<span class="c-string">'message'</span> <span class="c-op">=></span> <span class="c-var">$message</span>];

        <span class="c-keyword">if</span> (<span class="c-var">$imageUrl</span>) {
            <span class="c-var">$data</span>[<span class="c-string">'imageThumbnail'</span>] <span class="c-op">=</span> <span class="c-var">$imageUrl</span>;
            <span class="c-var">$data</span>[<span class="c-string">'imageFullsize'</span>]  <span class="c-op">=</span> <span class="c-var">$imageUrl</span>;
        }

        <span class="c-keyword">if</span> (<span class="c-var">$stickerPackageId</span> <span class="c-op">&&</span> <span class="c-var">$stickerId</span>) {
            <span class="c-var">$data</span>[<span class="c-string">'stickerPackageId'</span>] <span class="c-op">=</span> <span class="c-var">$stickerPackageId</span>;
            <span class="c-var">$data</span>[<span class="c-string">'stickerId'</span>]        <span class="c-op">=</span> <span class="c-var">$stickerId</span>;
        }

        <span class="c-keyword">return</span> <span class="c-type">Http</span>::<span class="c-func">asForm</span>()
            -><span class="c-func">withToken</span>(<span class="c-func">config</span>(<span class="c-string">'services.line.notify_token'</span>))
            -><span class="c-func">post</span>(<span class="c-keyword">self</span>::<span class="c-const">API_URL</span>, <span class="c-var">$data</span>)
            -><span class="c-func">successful</span>();
    }
}

<span class="c-comment">// ======================================</span>
<span class="c-comment">// ตัวอย่างการใช้งาน</span>
<span class="c-comment">// ======================================</span>
<span class="c-var">$notify</span> <span class="c-op">=</span> <span class="c-func">app</span>(<span class="c-type">LineNotifyService</span>::<span class="c-keyword">class</span>);

<span class="c-comment">// แจ้งข้อความธรรมดา</span>
<span class="c-var">$notify</span>-><span class="c-func">send</span>(<span class="c-string">"\n🛒 คำสั่งซื้อใหม่ #1234\n💰 ฿5,990"</span>);

<span class="c-comment">// แจ้งพร้อมรูปภาพ</span>
<span class="c-var">$notify</span>-><span class="c-func">send</span>(
    <span class="c-string">"\n📦 สินค้าจัดส่งแล้ว"</span>,
    <span class="c-attr">imageUrl</span>: <span class="c-string">'https://yoursite.com/tracking.jpg'</span>,
);

<span class="c-comment">// แจ้งพร้อมสติกเกอร์</span>
<span class="c-var">$notify</span>-><span class="c-func">send</span>(
    <span class="c-string">"\n✅ ชำระเงินเรียบร้อย"</span>,
    <span class="c-attr">stickerPackageId</span>: <span class="c-string">'446'</span>,
    <span class="c-attr">stickerId</span>: <span class="c-string">'1988'</span>,
);</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Artisan Command — ส่งแจ้งเตือนผ่าน CLI -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Artisan Command — ส่งแจ้งเตือนผ่าน CLI</h3>
                        <p class="text-sm text-slate-500">สร้างคำสั่ง Artisan สำหรับส่ง LINE จาก Terminal / CLI command for LINE notifications</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Console/Commands/LineBroadcast.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Console\Commands</span>;

<span class="c-keyword">use</span> <span class="c-type">App\Services\LineService</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Console\Command</span>;

<span class="c-keyword">class</span> <span class="c-type">LineBroadcast</span> <span class="c-keyword">extends</span> <span class="c-type">Command</span>
{
    <span class="c-keyword">protected</span> <span class="c-var">$signature</span> <span class="c-op">=</span>
        <span class="c-string">'line:broadcast {message : ข้อความที่จะส่ง}'</span>;

    <span class="c-keyword">protected</span> <span class="c-var">$description</span> <span class="c-op">=</span>
        <span class="c-string">'ส่งข้อความ LINE ถึงผู้ติดตามทุกคน'</span>;

    <span class="c-keyword">public function</span> <span class="c-func">handle</span>(<span class="c-type">LineService</span> <span class="c-var">$line</span>): <span class="c-type">int</span>
    {
        <span class="c-var">$message</span> <span class="c-op">=</span> <span class="c-var">$this</span>-><span class="c-func">argument</span>(<span class="c-string">'message'</span>);

        <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">$this</span>-><span class="c-func">confirm</span>(<span class="c-string">"ส่ง: \"{$message}\" ?"</span>)) {
            <span class="c-var">$this</span>-><span class="c-func">info</span>(<span class="c-string">'ยกเลิก'</span>);
            <span class="c-keyword">return</span> <span class="c-number">0</span>;
        }

        <span class="c-var">$success</span> <span class="c-op">=</span> <span class="c-var">$line</span>-><span class="c-func">broadcast</span>([
            [<span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>, <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-var">$message</span>],
        ]);

        <span class="c-var">$success</span>
            ? <span class="c-var">$this</span>-><span class="c-func">info</span>(<span class="c-string">'✅ ส่งเรียบร้อย!'</span>)
            : <span class="c-var">$this</span>-><span class="c-func">error</span>(<span class="c-string">'❌ ส่งไม่สำเร็จ'</span>);

        <span class="c-keyword">return</span> <span class="c-var">$success</span> ? <span class="c-number">0</span> : <span class="c-number">1</span>;
    }
}

<span class="c-comment">// ======================================</span>
<span class="c-comment">// ใช้งาน:</span>
<span class="c-comment">// php artisan line:broadcast "สวัสดีปีใหม่!"</span>
<span class="c-comment">// ======================================</span></code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Scheduled — แจ้งเตือนตามเวลา -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Scheduled — แจ้งเตือนตามเวลา</h3>
                        <p class="text-sm text-slate-500">ตั้งเวลาส่งแจ้งเตือนอัตโนมัติ (License หมดอายุ, รายงานรายวัน) / Scheduled auto-notifications</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Console/Commands/LineExpiryReminder.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">LineExpiryReminder</span> <span class="c-keyword">extends</span> <span class="c-type">Command</span>
{
    <span class="c-keyword">protected</span> <span class="c-var">$signature</span> <span class="c-op">=</span> <span class="c-string">'line:expiry-reminder'</span>;
    <span class="c-keyword">protected</span> <span class="c-var">$description</span> <span class="c-op">=</span> <span class="c-string">'แจ้ง License ใกล้หมดอายุ'</span>;

    <span class="c-keyword">public function</span> <span class="c-func">handle</span>(<span class="c-type">LineService</span> <span class="c-var">$line</span>): <span class="c-type">int</span>
    {
        <span class="c-comment">// หา License ที่จะหมดอายุใน 7 วัน</span>
        <span class="c-var">$expiring</span> <span class="c-op">=</span> <span class="c-type">License</span>::<span class="c-func">with</span>(<span class="c-string">'user'</span>)
            -><span class="c-func">where</span>(<span class="c-string">'status'</span>, <span class="c-string">'active'</span>)
            -><span class="c-func">whereBetween</span>(<span class="c-string">'expires_at'</span>, [
                <span class="c-func">now</span>(),
                <span class="c-func">now</span>()-><span class="c-func">addDays</span>(<span class="c-number">7</span>),
            ])
            -><span class="c-func">get</span>();

        <span class="c-keyword">foreach</span> (<span class="c-var">$expiring</span> <span class="c-keyword">as</span> <span class="c-var">$license</span>) {
            <span class="c-var">$user</span> <span class="c-op">=</span> <span class="c-var">$license</span>-><span class="c-prop">user</span>;
            <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">$user</span>-><span class="c-prop">line_user_id</span>) <span class="c-keyword">continue</span>;

            <span class="c-var">$days</span> <span class="c-op">=</span> <span class="c-func">now</span>()-><span class="c-func">diffInDays</span>(<span class="c-var">$license</span>-><span class="c-prop">expires_at</span>);

            <span class="c-var">$line</span>-><span class="c-func">pushMessage</span>(<span class="c-var">$user</span>-><span class="c-prop">line_user_id</span>, [
                [
                    <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
                    <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">"⏰ แจ้งเตือน License ใกล้หมดอายุ\n\n"</span>
                        . <span class="c-string">"🔑 {$license->product->name}\n"</span>
                        . <span class="c-string">"📅 หมดอายุอีก {$days} วัน\n"</span>
                        . <span class="c-string">"({$license->expires_at->format('d/m/Y')})\n\n"</span>
                        . <span class="c-string">"ต่ออายุได้ที่ xman.studio/licenses"</span>,
                ],
            ]);

            <span class="c-var">$this</span>-><span class="c-func">info</span>(<span class="c-string">"ส่งถึง {$user->name}"</span>);
        }

        <span class="c-var">$this</span>-><span class="c-func">info</span>(<span class="c-string">"ส่งทั้งหมด {$expiring->count()} คน"</span>);
        <span class="c-keyword">return</span> <span class="c-number">0</span>;
    }
}</code></pre>
            </div>
                    <div class="mt-4 code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — routes/console.php (Laravel 11)</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// ตั้งเวลาให้ทำงานทุกวันตอน 9 โมงเช้า</span>
<span class="c-type">Schedule</span>::<span class="c-func">command</span>(<span class="c-string">'line:expiry-reminder'</span>)
    -><span class="c-func">dailyAt</span>(<span class="c-string">'09:00'</span>)
    -><span class="c-func">timezone</span>(<span class="c-string">'Asia/Bangkok'</span>);

<span class="c-comment">// รายงานยอดขายรายวันให้ Admin</span>
<span class="c-type">Schedule</span>::<span class="c-func">command</span>(<span class="c-string">'line:daily-report'</span>)
    -><span class="c-func">dailyAt</span>(<span class="c-string">'20:00'</span>)
    -><span class="c-func">timezone</span>(<span class="c-string">'Asia/Bangkok'</span>);

<span class="c-comment">// ======================================</span>
<span class="c-comment">// อย่าลืม! ตั้ง cron บน server:</span>
<span class="c-comment">// * * * * * cd /path && php artisan schedule:run</span>
<span class="c-comment">// ======================================</span></code></pre>
            </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Summary Reference Box -->
    <div class="mt-6 academy-card rounded-2xl p-6">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            สรุป: เลือกใช้วิธีไหนดี? / Which approach to use?
        </h3>
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                <div class="font-bold text-green-800 mb-1">LINE Notify</div>
                <div class="text-sm text-green-700 mb-2">ง่ายที่สุด, ใช้ได้เลย</div>
                <ul class="text-xs text-green-600 space-y-1">
                    <li>- แจ้งเตือนทีมงาน</li>
                    <li>- Server monitoring</li>
                    <li>- ไม่ต้องมี OA</li>
                    <li>- ส่งได้ 1,000 ครั้ง/ชม.</li>
                </ul>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                <div class="font-bold text-blue-800 mb-1">Messaging API (Push)</div>
                <div class="text-sm text-blue-700 mb-2">ยืดหยุ่น, หลายฟีเจอร์</div>
                <ul class="text-xs text-blue-600 space-y-1">
                    <li>- แจ้งลูกค้ารายคน</li>
                    <li>- Flex Message สวยๆ</li>
                    <li>- Rich Menu</li>
                    <li>- Push 500 msg/req</li>
                </ul>
            </div>
            <div class="p-4 bg-purple-50 rounded-xl border border-purple-200">
                <div class="font-bold text-purple-800 mb-1">Webhook (Reply)</div>
                <div class="text-sm text-purple-700 mb-2">Chatbot, Auto-reply</div>
                <ul class="text-xs text-purple-600 space-y-1">
                    <li>- ตอบกลับอัตโนมัติ</li>
                    <li>- Chatbot สั่งงานได้</li>
                    <li>- ฟรี! ไม่จำกัดจำนวน</li>
                    <li>- ต้องมี HTTPS domain</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>


{{-- APPSCRIPT --}}
<div x-show="activeCat === 'appscript'" style="display:none;" x-cloak>

    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/20 gloss">
            <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M16.148.012h-8.26c-.392 0-.71.318-.71.71v5.39l4.762 1.985 4.92-1.985V.722c0-.392-.318-.71-.71-.71zm.712 6.81l-4.92 2.77-4.762-2.77v5.39l4.762 2.77 4.92-2.77zm-10.394 6.1l4.762 2.77 4.92-2.77v5.39c0 .392-.318.71-.71.71H7.176c-.392 0-.71-.318-.71-.71z"/></svg>
        </div>
        <div>
            <h2 class="text-2xl font-black academy-section-title">Apps Script + ระบบจองคิว + LINE</h2>
            <p class="text-slate-500 text-sm">ระบบจองคิวออนไลน์ผ่าน Google Forms/Sheets + แจ้งเตือน LINE อัตโนมัติ</p>
        </div>
        <span class="academy-badge ml-auto hidden sm:inline-block">Special Case</span>
    </div>


    <div class="space-y-4">

<!-- Intro Box -->
    <div class="academy-card rounded-2xl p-6 mb-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0 mt-1">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 mb-2">Flow ทั้งระบบ — End-to-End Architecture</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mt-3">
                    <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-lg border border-blue-200">
                        <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                        <span class="text-xs font-medium text-slate-700">ลูกค้ากรอกฟอร์มจอง<br>(เว็บ / Google Forms)</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-lg border border-blue-200">
                        <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                        <span class="text-xs font-medium text-slate-700">บันทึกลง Google Sheets<br>(หรือ Database)</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-lg border border-blue-200">
                        <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                        <span class="text-xs font-medium text-slate-700">Apps Script Trigger<br>ดักจับข้อมูลใหม่</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg border border-green-200">
                        <span class="w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold flex items-center justify-center shrink-0">4</span>
                        <span class="text-xs font-medium text-slate-700">ส่งแจ้งเตือน LINE<br>(ลูกค้า + แอดมิน)</span>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2 bg-purple-50 rounded-lg border border-purple-200">
                        <span class="w-6 h-6 rounded-full bg-purple-500 text-white text-xs font-bold flex items-center justify-center shrink-0">5</span>
                        <span class="text-xs font-medium text-slate-700">ส่งเตือนก่อนถึงคิว<br>(Cron / Time Trigger)</span>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-500">
                    <strong>เหมาะสำหรับ:</strong> คลินิก, ร้านเสริมสวย, ร้านซ่อม, ร้านอาหาร, สำนักงาน — ทุกธุรกิจที่มีระบบจองคิว
                </p>
            </div>
        </div>
    </div>

        <!-- Apps Script: แจ้งเตือน LINE เมื่อมีการจอง -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Apps Script: แจ้งเตือน LINE เมื่อมีการจอง</h3>
                        <p class="text-sm text-slate-500">เมื่อมีข้อมูลใหม่เข้า Google Sheets → ส่ง LINE Notify ทันที / Auto-notify on new booking</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Google Apps Script</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Google Apps Script — Code.gs</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">// =============================================</span>
<span class="c-comment">// Google Apps Script — ส่งแจ้งเตือน LINE</span>
<span class="c-comment">// เมื่อมีการจองคิวใหม่จาก Google Forms</span>
<span class="c-comment">// =============================================</span>

<span class="c-comment">// ⚙️ ตั้งค่า — แก้ไขตรงนี้</span>
<span class="c-keyword">const</span> <span class="c-const">LINE_NOTIFY_TOKEN</span> <span class="c-op">=</span> <span class="c-string">'YOUR_LINE_NOTIFY_TOKEN'</span>;
<span class="c-keyword">const</span> <span class="c-const">LINE_NOTIFY_URL</span>   <span class="c-op">=</span> <span class="c-string">'https://notify-api.line.me/api/notify'</span>;

<span class="c-comment">/**
 * Trigger: ทำงานเมื่อมีการส่ง Google Form
 * ตั้งค่า: Extensions → Apps Script → Triggers
 *         → เลือก onFormSubmit → From spreadsheet
 *         → On form submit
 */</span>
<span class="c-keyword">function</span> <span class="c-func">onFormSubmit</span>(<span class="c-var">e</span>) {
  <span class="c-keyword">const</span> <span class="c-var">values</span>    <span class="c-op">=</span> <span class="c-var">e</span>.<span class="c-prop">values</span>;
  <span class="c-keyword">const</span> <span class="c-var">timestamp</span> <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">0</span>]; <span class="c-comment">// Timestamp</span>
  <span class="c-keyword">const</span> <span class="c-var">name</span>      <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">1</span>]; <span class="c-comment">// ชื่อผู้จอง</span>
  <span class="c-keyword">const</span> <span class="c-var">phone</span>     <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">2</span>]; <span class="c-comment">// เบอร์โทร</span>
  <span class="c-keyword">const</span> <span class="c-var">service</span>   <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">3</span>]; <span class="c-comment">// บริการที่เลือก</span>
  <span class="c-keyword">const</span> <span class="c-var">date</span>      <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">4</span>]; <span class="c-comment">// วันที่จอง</span>
  <span class="c-keyword">const</span> <span class="c-var">time</span>      <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">5</span>]; <span class="c-comment">// เวลาที่จอง</span>
  <span class="c-keyword">const</span> <span class="c-var">note</span>      <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">6</span>] <span class="c-op">||</span> <span class="c-string">'-'</span>; <span class="c-comment">// หมายเหตุ</span>

  <span class="c-comment">// สร้างหมายเลขคิว</span>
  <span class="c-keyword">const</span> <span class="c-var">sheet</span>   <span class="c-op">=</span> <span class="c-type">SpreadsheetApp</span>.<span class="c-func">getActiveSheet</span>();
  <span class="c-keyword">const</span> <span class="c-var">queueNo</span> <span class="c-op">=</span> <span class="c-string">'Q-'</span> <span class="c-op">+</span> <span class="c-type">String</span>(
    <span class="c-var">sheet</span>.<span class="c-func">getLastRow</span>()
  ).<span class="c-func">padStart</span>(<span class="c-number">4</span>, <span class="c-string">'0'</span>);

  <span class="c-comment">// เขียนหมายเลขคิวกลับลง Sheet (คอลัมน์ H)</span>
  <span class="c-var">sheet</span>.<span class="c-func">getRange</span>(
    <span class="c-var">sheet</span>.<span class="c-func">getLastRow</span>(), <span class="c-number">8</span>
  ).<span class="c-func">setValue</span>(<span class="c-var">queueNo</span>);

  <span class="c-comment">// สร้างข้อความ LINE</span>
  <span class="c-keyword">const</span> <span class="c-var">message</span> <span class="c-op">=</span> <span class="c-string">`
🔔 การจองคิวใหม่!

📋 หมายเลขคิว: ${queueNo}
👤 ชื่อ: ${name}
📱 โทร: ${phone}
💼 บริการ: ${service}
📅 วันที่: ${date}
⏰ เวลา: ${time}
📝 หมายเหตุ: ${note}

⏱️ จองเมื่อ: ${timestamp}`</span>;

  <span class="c-comment">// ส่ง LINE Notify</span>
  <span class="c-func">sendLineNotify</span>(<span class="c-var">message</span>);

  <span class="c-comment">// (Optional) ส่ง Email ยืนยันให้ลูกค้า</span>
  <span class="c-keyword">const</span> <span class="c-var">email</span> <span class="c-op">=</span> <span class="c-var">values</span>[<span class="c-number">7</span>]; <span class="c-comment">// ถ้ามีช่อง email</span>
  <span class="c-keyword">if</span> (<span class="c-var">email</span>) {
    <span class="c-type">MailApp</span>.<span class="c-func">sendEmail</span>({
      <span class="c-attr">to</span>: <span class="c-var">email</span>,
      <span class="c-attr">subject</span>: <span class="c-string">`ยืนยันการจองคิว ${queueNo}`</span>,
      <span class="c-attr">body</span>: <span class="c-string">`สวัสดีคุณ ${name}\n\n`</span>
        <span class="c-op">+</span> <span class="c-string">`หมายเลขคิวของคุณคือ: ${queueNo}\n`</span>
        <span class="c-op">+</span> <span class="c-string">`บริการ: ${service}\n`</span>
        <span class="c-op">+</span> <span class="c-string">`วันที่: ${date} เวลา ${time}\n\n`</span>
        <span class="c-op">+</span> <span class="c-string">`ขอบคุณที่ใช้บริการครับ`</span>,
    });
  }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Apps Script: ฟังก์ชัน sendLineNotify -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Apps Script: ฟังก์ชัน sendLineNotify</h3>
                        <p class="text-sm text-slate-500">ฟังก์ชันหลักสำหรับเรียก LINE Notify API / Core function to call LINE Notify</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Google Apps Script</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Google Apps Script — LINE Notify</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">/**
 * ส่งข้อความผ่าน LINE Notify
 * @param {string} message ข้อความที่จะส่ง
 * @param {string} [imageUrl] URL รูปภาพ (optional)
 * @returns {boolean} สำเร็จหรือไม่
 */</span>
<span class="c-keyword">function</span> <span class="c-func">sendLineNotify</span>(<span class="c-var">message</span>, <span class="c-var">imageUrl</span>) {
  <span class="c-keyword">const</span> <span class="c-var">payload</span> <span class="c-op">=</span> { <span class="c-attr">message</span>: <span class="c-var">message</span> };

  <span class="c-keyword">if</span> (<span class="c-var">imageUrl</span>) {
    <span class="c-var">payload</span>.<span class="c-prop">imageThumbnail</span> <span class="c-op">=</span> <span class="c-var">imageUrl</span>;
    <span class="c-var">payload</span>.<span class="c-prop">imageFullsize</span>  <span class="c-op">=</span> <span class="c-var">imageUrl</span>;
  }

  <span class="c-keyword">const</span> <span class="c-var">options</span> <span class="c-op">=</span> {
    <span class="c-attr">method</span>:  <span class="c-string">'post'</span>,
    <span class="c-attr">headers</span>: {
      <span class="c-string">'Authorization'</span>: <span class="c-string">`Bearer ${LINE_NOTIFY_TOKEN}`</span>,
    },
    <span class="c-attr">payload</span>: <span class="c-var">payload</span>,
    <span class="c-attr">muteHttpExceptions</span>: <span class="c-const">true</span>,
  };

  <span class="c-keyword">try</span> {
    <span class="c-keyword">const</span> <span class="c-var">response</span> <span class="c-op">=</span> <span class="c-type">UrlFetchApp</span>.<span class="c-func">fetch</span>(
      <span class="c-const">LINE_NOTIFY_URL</span>, <span class="c-var">options</span>
    );
    <span class="c-keyword">const</span> <span class="c-var">code</span> <span class="c-op">=</span> <span class="c-var">response</span>.<span class="c-func">getResponseCode</span>();

    <span class="c-keyword">if</span> (<span class="c-var">code</span> <span class="c-op">!==</span> <span class="c-number">200</span>) {
      <span class="c-type">Logger</span>.<span class="c-func">log</span>(
        <span class="c-string">`LINE Error: ${code} - ${response.getContentText()}`</span>
      );
      <span class="c-keyword">return</span> <span class="c-const">false</span>;
    }

    <span class="c-type">Logger</span>.<span class="c-func">log</span>(<span class="c-string">'LINE Notify ส่งสำเร็จ'</span>);
    <span class="c-keyword">return</span> <span class="c-const">true</span>;
  } <span class="c-keyword">catch</span> (<span class="c-var">error</span>) {
    <span class="c-type">Logger</span>.<span class="c-func">log</span>(<span class="c-string">`LINE Error: ${error.message}`</span>);
    <span class="c-keyword">return</span> <span class="c-const">false</span>;
  }
}

<span class="c-comment">/**
 * ส่งข้อความผ่าน LINE Messaging API (Push)
 * สำหรับส่งหาลูกค้ารายคนที่รู้ userId
 */</span>
<span class="c-keyword">function</span> <span class="c-func">sendLinePush</span>(<span class="c-var">userId</span>, <span class="c-var">message</span>) {
  <span class="c-keyword">const</span> <span class="c-const">ACCESS_TOKEN</span> <span class="c-op">=</span>
    <span class="c-string">'YOUR_CHANNEL_ACCESS_TOKEN'</span>;

  <span class="c-keyword">const</span> <span class="c-var">payload</span> <span class="c-op">=</span> {
    <span class="c-attr">to</span>: <span class="c-var">userId</span>,
    <span class="c-attr">messages</span>: [
      { <span class="c-attr">type</span>: <span class="c-string">'text'</span>, <span class="c-attr">text</span>: <span class="c-var">message</span> }
    ],
  };

  <span class="c-type">UrlFetchApp</span>.<span class="c-func">fetch</span>(
    <span class="c-string">'https://api.line.me/v2/bot/message/push'</span>,
    {
      <span class="c-attr">method</span>:      <span class="c-string">'post'</span>,
      <span class="c-attr">contentType</span>: <span class="c-string">'application/json'</span>,
      <span class="c-attr">headers</span>: {
        <span class="c-string">'Authorization'</span>: <span class="c-string">`Bearer ${ACCESS_TOKEN}`</span>
      },
      <span class="c-attr">payload</span>: <span class="c-type">JSON</span>.<span class="c-func">stringify</span>(<span class="c-var">payload</span>),
    }
  );
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Apps Script: เตือนก่อนถึงคิว -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Apps Script: เตือนก่อนถึงคิว</h3>
                        <p class="text-sm text-slate-500">Time Trigger ส่งเตือนลูกค้าก่อนถึงเวลานัด / Auto-reminder before appointment</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Google Apps Script</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Google Apps Script — Reminder</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">/**
 * ส่งเตือนคิวที่จะมาถึงใน 1 ชั่วโมง
 * ตั้ง Time-driven trigger: ทุก 30 นาที
 *
 * Triggers → Add Trigger:
 *   Function: checkUpcomingQueues
 *   Event: Time-driven → Minutes timer → 30 min
 */</span>
<span class="c-keyword">function</span> <span class="c-func">checkUpcomingQueues</span>() {
  <span class="c-keyword">const</span> <span class="c-var">sheet</span> <span class="c-op">=</span> <span class="c-type">SpreadsheetApp</span>
    .<span class="c-func">getActiveSpreadsheet</span>()
    .<span class="c-func">getSheetByName</span>(<span class="c-string">'การจอง'</span>);
  <span class="c-keyword">const</span> <span class="c-var">data</span>  <span class="c-op">=</span> <span class="c-var">sheet</span>.<span class="c-func">getDataRange</span>().<span class="c-func">getValues</span>();
  <span class="c-keyword">const</span> <span class="c-var">now</span>   <span class="c-op">=</span> <span class="c-keyword">new</span> <span class="c-type">Date</span>();

  <span class="c-comment">// เริ่มจากแถว 2 (ข้ามหัวตาราง)</span>
  <span class="c-keyword">for</span> (<span class="c-keyword">let</span> <span class="c-var">i</span> <span class="c-op">=</span> <span class="c-number">1</span>; <span class="c-var">i</span> <span class="c-op"><</span> <span class="c-var">data</span>.<span class="c-prop">length</span>; <span class="c-var">i</span><span class="c-op">++</span>) {
    <span class="c-keyword">const</span> <span class="c-var">row</span>        <span class="c-op">=</span> <span class="c-var">data</span>[<span class="c-var">i</span>];
    <span class="c-keyword">const</span> <span class="c-var">name</span>       <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">1</span>]; <span class="c-comment">// ชื่อ</span>
    <span class="c-keyword">const</span> <span class="c-var">phone</span>      <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">2</span>]; <span class="c-comment">// เบอร์</span>
    <span class="c-keyword">const</span> <span class="c-var">service</span>    <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">3</span>]; <span class="c-comment">// บริการ</span>
    <span class="c-keyword">const</span> <span class="c-var">dateStr</span>    <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">4</span>]; <span class="c-comment">// วันที่</span>
    <span class="c-keyword">const</span> <span class="c-var">timeStr</span>    <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">5</span>]; <span class="c-comment">// เวลา</span>
    <span class="c-keyword">const</span> <span class="c-var">queueNo</span>   <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">7</span>]; <span class="c-comment">// หมายเลขคิว</span>
    <span class="c-keyword">const</span> <span class="c-var">reminded</span>  <span class="c-op">=</span> <span class="c-var">row</span>[<span class="c-number">8</span>]; <span class="c-comment">// เคยเตือนแล้ว?</span>

    <span class="c-keyword">if</span> (<span class="c-var">reminded</span> <span class="c-op">===</span> <span class="c-string">'✅'</span>) <span class="c-keyword">continue</span>; <span class="c-comment">// ข้าม</span>

    <span class="c-comment">// รวมวันที่+เวลา</span>
    <span class="c-keyword">const</span> <span class="c-var">appointmentDate</span> <span class="c-op">=</span> <span class="c-func">parseDateTime</span>(
      <span class="c-var">dateStr</span>, <span class="c-var">timeStr</span>
    );
    <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">appointmentDate</span>) <span class="c-keyword">continue</span>;

    <span class="c-comment">// คำนวณเวลาที่เหลือ (นาที)</span>
    <span class="c-keyword">const</span> <span class="c-var">diffMs</span>  <span class="c-op">=</span> <span class="c-var">appointmentDate</span> <span class="c-op">-</span> <span class="c-var">now</span>;
    <span class="c-keyword">const</span> <span class="c-var">diffMin</span> <span class="c-op">=</span> <span class="c-var">diffMs</span> <span class="c-op">/</span> <span class="c-number">60000</span>;

    <span class="c-comment">// เตือนเมื่อเหลืออีก 30-90 นาที</span>
    <span class="c-keyword">if</span> (<span class="c-var">diffMin</span> <span class="c-op">></span> <span class="c-number">30</span> <span class="c-op">&&</span> <span class="c-var">diffMin</span> <span class="c-op"><=</span> <span class="c-number">90</span>) {
      <span class="c-keyword">const</span> <span class="c-var">msg</span> <span class="c-op">=</span> <span class="c-string">`
⏰ แจ้งเตือนคิวของคุณ!

📋 คิว: ${queueNo}
👤 คุณ ${name}
💼 บริการ: ${service}
📅 ${dateStr} เวลา ${timeStr}

🕐 อีกประมาณ ${Math.round(diffMin)} นาที
กรุณามาถึงก่อนเวลา 10 นาทีครับ 🙏`</span>;

      <span class="c-func">sendLineNotify</span>(<span class="c-var">msg</span>);

      <span class="c-comment">// บันทึกว่าเตือนแล้ว (คอลัมน์ I)</span>
      <span class="c-var">sheet</span>.<span class="c-func">getRange</span>(<span class="c-var">i</span> <span class="c-op">+</span> <span class="c-number">1</span>, <span class="c-number">9</span>).<span class="c-func">setValue</span>(<span class="c-string">'✅'</span>);
    }
  }
}

<span class="c-comment">/**
 * Parse วันที่ + เวลา → Date object
 * รองรับ: "31/03/2026", "14:30"
 */</span>
<span class="c-keyword">function</span> <span class="c-func">parseDateTime</span>(<span class="c-var">dateStr</span>, <span class="c-var">timeStr</span>) {
  <span class="c-keyword">try</span> {
    <span class="c-keyword">const</span> [<span class="c-var">d</span>, <span class="c-var">m</span>, <span class="c-var">y</span>] <span class="c-op">=</span> <span class="c-type">String</span>(<span class="c-var">dateStr</span>).<span class="c-func">split</span>(<span class="c-string">'/'</span>);
    <span class="c-keyword">const</span> [<span class="c-var">h</span>, <span class="c-var">min</span>] <span class="c-op">=</span> <span class="c-type">String</span>(<span class="c-var">timeStr</span>).<span class="c-func">split</span>(<span class="c-string">':'</span>);
    <span class="c-keyword">return new</span> <span class="c-type">Date</span>(<span class="c-var">y</span>, <span class="c-var">m</span> <span class="c-op">-</span> <span class="c-number">1</span>, <span class="c-var">d</span>, <span class="c-var">h</span>, <span class="c-var">min</span>);
  } <span class="c-keyword">catch</span> (<span class="c-var">e</span>) {
    <span class="c-keyword">return</span> <span class="c-const">null</span>;
  }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Apps Script: รายงานสรุปรายวัน -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Apps Script: รายงานสรุปรายวัน</h3>
                        <p class="text-sm text-slate-500">ส่งสรุปการจองวันถัดไปให้ Admin ทุกเช้า / Daily booking summary for admin</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Google Apps Script</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Google Apps Script — Daily Report</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">/**
 * สรุปการจองของวันถัดไป
 * ตั้ง Trigger: Time-driven → Day timer → 8am-9am
 */</span>
<span class="c-keyword">function</span> <span class="c-func">sendDailySummary</span>() {
  <span class="c-keyword">const</span> <span class="c-var">sheet</span>    <span class="c-op">=</span> <span class="c-type">SpreadsheetApp</span>
    .<span class="c-func">getActiveSpreadsheet</span>()
    .<span class="c-func">getSheetByName</span>(<span class="c-string">'การจอง'</span>);
  <span class="c-keyword">const</span> <span class="c-var">data</span>     <span class="c-op">=</span> <span class="c-var">sheet</span>.<span class="c-func">getDataRange</span>().<span class="c-func">getValues</span>();
  <span class="c-keyword">const</span> <span class="c-var">tomorrow</span> <span class="c-op">=</span> <span class="c-keyword">new</span> <span class="c-type">Date</span>();
  <span class="c-var">tomorrow</span>.<span class="c-func">setDate</span>(<span class="c-var">tomorrow</span>.<span class="c-func">getDate</span>() <span class="c-op">+</span> <span class="c-number">1</span>);

  <span class="c-keyword">const</span> <span class="c-var">tmrStr</span> <span class="c-op">=</span> <span class="c-type">Utilities</span>.<span class="c-func">formatDate</span>(
    <span class="c-var">tomorrow</span>,
    <span class="c-string">'Asia/Bangkok'</span>,
    <span class="c-string">'dd/MM/yyyy'</span>
  );

  <span class="c-comment">// กรองเฉพาะจองของพรุ่งนี้</span>
  <span class="c-keyword">const</span> <span class="c-var">bookings</span> <span class="c-op">=</span> <span class="c-var">data</span>.<span class="c-func">filter</span>((<span class="c-var">row</span>, <span class="c-var">i</span>) <span class="c-op">=></span>
    <span class="c-var">i</span> <span class="c-op">></span> <span class="c-number">0</span> <span class="c-op">&&</span> <span class="c-func">formatDate</span>(<span class="c-var">row</span>[<span class="c-number">4</span>]) <span class="c-op">===</span> <span class="c-var">tmrStr</span>
  );

  <span class="c-keyword">if</span> (<span class="c-var">bookings</span>.<span class="c-prop">length</span> <span class="c-op">===</span> <span class="c-number">0</span>) {
    <span class="c-func">sendLineNotify</span>(
      <span class="c-string">`\n📅 สรุปวันพรุ่งนี้ (${tmrStr})\n\n`</span>
      <span class="c-op">+</span> <span class="c-string">`✨ ไม่มีการจอง — วันว่าง!`</span>
    );
    <span class="c-keyword">return</span>;
  }

  <span class="c-comment">// จัดเรียงตามเวลา</span>
  <span class="c-var">bookings</span>.<span class="c-func">sort</span>((<span class="c-var">a</span>, <span class="c-var">b</span>) <span class="c-op">=></span>
    <span class="c-type">String</span>(<span class="c-var">a</span>[<span class="c-number">5</span>]).<span class="c-func">localeCompare</span>(<span class="c-type">String</span>(<span class="c-var">b</span>[<span class="c-number">5</span>]))
  );

  <span class="c-comment">// สร้างข้อความ</span>
  <span class="c-keyword">let</span> <span class="c-var">msg</span> <span class="c-op">=</span> <span class="c-string">`\n📅 สรุปวันพรุ่งนี้ (${tmrStr})`</span>
    <span class="c-op">+</span> <span class="c-string">`\n📊 ทั้งหมด ${bookings.length} คิว\n`</span>;

  <span class="c-var">bookings</span>.<span class="c-func">forEach</span>((<span class="c-var">row</span>, <span class="c-var">idx</span>) <span class="c-op">=></span> {
    <span class="c-var">msg</span> <span class="c-op">+=</span> <span class="c-string">`\n${idx + 1}. ⏰ ${row[5]}`</span>
      <span class="c-op">+</span> <span class="c-string">` | 👤 ${row[1]}`</span>
      <span class="c-op">+</span> <span class="c-string">` | 💼 ${row[3]}`</span>;
  });

  <span class="c-func">sendLineNotify</span>(<span class="c-var">msg</span>);
}

<span class="c-comment">// Helper: format Date object เป็น dd/MM/yyyy</span>
<span class="c-keyword">function</span> <span class="c-func">formatDate</span>(<span class="c-var">date</span>) {
  <span class="c-keyword">if</span> (<span class="c-var">date</span> <span class="c-keyword">instanceof</span> <span class="c-type">Date</span>) {
    <span class="c-keyword">return</span> <span class="c-type">Utilities</span>.<span class="c-func">formatDate</span>(
      <span class="c-var">date</span>, <span class="c-string">'Asia/Bangkok'</span>, <span class="c-string">'dd/MM/yyyy'</span>
    );
  }
  <span class="c-keyword">return</span> <span class="c-type">String</span>(<span class="c-var">date</span>);
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Apps Script: Web App สำหรับจัดการคิว -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Apps Script: Web App สำหรับจัดการคิว</h3>
                        <p class="text-sm text-slate-500">Deploy เป็น Web App ให้เว็บไซต์เรียกได้ / Web endpoint for queue management</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Google Apps Script</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Google Apps Script — Web App API</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">/**
 * Deploy → Manage deployments → New → Web app
 * Execute as: Me
 * Who has access: Anyone
 *
 * เว็บไซต์สามารถเรียก POST ไปที่ URL นี้
 * เพื่อจองคิวโดยไม่ต้องผ่าน Google Forms
 */</span>
<span class="c-keyword">function</span> <span class="c-func">doPost</span>(<span class="c-var">e</span>) {
  <span class="c-keyword">try</span> {
    <span class="c-keyword">const</span> <span class="c-var">data</span> <span class="c-op">=</span> <span class="c-type">JSON</span>.<span class="c-func">parse</span>(<span class="c-var">e</span>.<span class="c-prop">postData</span>.<span class="c-prop">contents</span>);

    <span class="c-keyword">const</span> <span class="c-var">sheet</span> <span class="c-op">=</span> <span class="c-type">SpreadsheetApp</span>
      .<span class="c-func">getActiveSpreadsheet</span>()
      .<span class="c-func">getSheetByName</span>(<span class="c-string">'การจอง'</span>);

    <span class="c-comment">// สร้างหมายเลขคิว</span>
    <span class="c-keyword">const</span> <span class="c-var">queueNo</span> <span class="c-op">=</span> <span class="c-string">'Q-'</span> <span class="c-op">+</span> <span class="c-type">String</span>(
      <span class="c-var">sheet</span>.<span class="c-func">getLastRow</span>() <span class="c-op">+</span> <span class="c-number">1</span>
    ).<span class="c-func">padStart</span>(<span class="c-number">4</span>, <span class="c-string">'0'</span>);

    <span class="c-comment">// เขียนลง Sheet</span>
    <span class="c-var">sheet</span>.<span class="c-func">appendRow</span>([
      <span class="c-keyword">new</span> <span class="c-type">Date</span>(),      <span class="c-comment">// Timestamp</span>
      <span class="c-var">data</span>.<span class="c-prop">name</span>,       <span class="c-comment">// ชื่อ</span>
      <span class="c-var">data</span>.<span class="c-prop">phone</span>,      <span class="c-comment">// เบอร์โทร</span>
      <span class="c-var">data</span>.<span class="c-prop">service</span>,    <span class="c-comment">// บริการ</span>
      <span class="c-var">data</span>.<span class="c-prop">date</span>,       <span class="c-comment">// วันที่</span>
      <span class="c-var">data</span>.<span class="c-prop">time</span>,       <span class="c-comment">// เวลา</span>
      <span class="c-var">data</span>.<span class="c-prop">note</span> <span class="c-op">||</span> <span class="c-string">''</span>, <span class="c-comment">// หมายเหตุ</span>
      <span class="c-var">queueNo</span>,        <span class="c-comment">// หมายเลขคิว</span>
    ]);

    <span class="c-comment">// แจ้งเตือน LINE</span>
    <span class="c-func">sendLineNotify</span>(<span class="c-string">`
🔔 จองคิวใหม่จากเว็บไซต์!

📋 คิว: ${queueNo}
👤 ${data.name}
📱 ${data.phone}
💼 ${data.service}
📅 ${data.date} ⏰ ${data.time}`</span>);

    <span class="c-comment">// ส่งกลับ JSON</span>
    <span class="c-keyword">return</span> <span class="c-type">ContentService</span>
      .<span class="c-func">createTextOutput</span>(<span class="c-type">JSON</span>.<span class="c-func">stringify</span>({
        <span class="c-attr">success</span>: <span class="c-const">true</span>,
        <span class="c-attr">queueNo</span>: <span class="c-var">queueNo</span>,
        <span class="c-attr">message</span>: <span class="c-string">'จองคิวสำเร็จ'</span>,
      }))
      .<span class="c-func">setMimeType</span>(
        <span class="c-type">ContentService</span>.<span class="c-prop">MimeType</span>.<span class="c-const">JSON</span>
      );

  } <span class="c-keyword">catch</span> (<span class="c-var">error</span>) {
    <span class="c-keyword">return</span> <span class="c-type">ContentService</span>
      .<span class="c-func">createTextOutput</span>(<span class="c-type">JSON</span>.<span class="c-func">stringify</span>({
        <span class="c-attr">success</span>: <span class="c-const">false</span>,
        <span class="c-attr">message</span>: <span class="c-var">error</span>.<span class="c-prop">message</span>,
      }))
      .<span class="c-func">setMimeType</span>(
        <span class="c-type">ContentService</span>.<span class="c-prop">MimeType</span>.<span class="c-const">JSON</span>
      );
  }
}

<span class="c-comment">// GET — ดูคิวของวันนี้ (JSON)</span>
<span class="c-keyword">function</span> <span class="c-func">doGet</span>(<span class="c-var">e</span>) {
  <span class="c-keyword">const</span> <span class="c-var">date</span> <span class="c-op">=</span> <span class="c-var">e</span>.<span class="c-prop">parameter</span>.<span class="c-prop">date</span>
    <span class="c-op">||</span> <span class="c-type">Utilities</span>.<span class="c-func">formatDate</span>(
      <span class="c-keyword">new</span> <span class="c-type">Date</span>(), <span class="c-string">'Asia/Bangkok'</span>, <span class="c-string">'dd/MM/yyyy'</span>
    );

  <span class="c-keyword">const</span> <span class="c-var">sheet</span> <span class="c-op">=</span> <span class="c-type">SpreadsheetApp</span>
    .<span class="c-func">getActiveSpreadsheet</span>()
    .<span class="c-func">getSheetByName</span>(<span class="c-string">'การจอง'</span>);
  <span class="c-keyword">const</span> <span class="c-var">data</span> <span class="c-op">=</span> <span class="c-var">sheet</span>.<span class="c-func">getDataRange</span>().<span class="c-func">getValues</span>();

  <span class="c-keyword">const</span> <span class="c-var">queues</span> <span class="c-op">=</span> <span class="c-var">data</span>
    .<span class="c-func">filter</span>((<span class="c-var">r</span>, <span class="c-var">i</span>) <span class="c-op">=></span>
      <span class="c-var">i</span> <span class="c-op">></span> <span class="c-number">0</span> <span class="c-op">&&</span> <span class="c-func">formatDate</span>(<span class="c-var">r</span>[<span class="c-number">4</span>]) <span class="c-op">===</span> <span class="c-var">date</span>
    )
    .<span class="c-func">map</span>(<span class="c-var">r</span> <span class="c-op">=></span> ({
      <span class="c-attr">queue</span>:   <span class="c-var">r</span>[<span class="c-number">7</span>],
      <span class="c-attr">name</span>:    <span class="c-var">r</span>[<span class="c-number">1</span>],
      <span class="c-attr">service</span>: <span class="c-var">r</span>[<span class="c-number">3</span>],
      <span class="c-attr">time</span>:    <span class="c-var">r</span>[<span class="c-number">5</span>],
    }));

  <span class="c-keyword">return</span> <span class="c-type">ContentService</span>
    .<span class="c-func">createTextOutput</span>(<span class="c-type">JSON</span>.<span class="c-func">stringify</span>({
      <span class="c-attr">date</span>, <span class="c-attr">total</span>: <span class="c-var">queues</span>.<span class="c-prop">length</span>, <span class="c-attr">queues</span>,
    }))
    .<span class="c-func">setMimeType</span>(
      <span class="c-type">ContentService</span>.<span class="c-prop">MimeType</span>.<span class="c-const">JSON</span>
    );
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Laravel: เรียก Apps Script Web App -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Laravel: เรียก Apps Script Web App</h3>
                        <p class="text-sm text-slate-500">ส่งข้อมูลจองจาก Laravel ไปยัง Google Sheets ผ่าน Apps Script / Connect Laravel to Sheets</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — app/Services/QueueBookingService.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-tag">&lt;?php</span>

<span class="c-keyword">namespace</span> <span class="c-type">App\Services</span>;

<span class="c-keyword">use</span> <span class="c-type">Illuminate\Support\Facades\Http</span>;
<span class="c-keyword">use</span> <span class="c-type">Illuminate\Support\Facades\Log</span>;

<span class="c-keyword">class</span> <span class="c-type">QueueBookingService</span>
{
    <span class="c-keyword">private</span> <span class="c-type">string</span> <span class="c-var">$appsScriptUrl</span>;

    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>()
    {
        <span class="c-comment">// URL ของ Apps Script Web App</span>
        <span class="c-var">$this</span>-><span class="c-prop">appsScriptUrl</span> <span class="c-op">=</span> <span class="c-func">config</span>(
            <span class="c-string">'services.google.apps_script_url'</span>
        );
    }

    <span class="c-comment">/**
     * จองคิวจากเว็บไซต์
     * → ส่งไป Google Sheets + แจ้ง LINE อัตโนมัติ
     */</span>
    <span class="c-keyword">public function</span> <span class="c-func">createBooking</span>(<span class="c-type">array</span> <span class="c-var">$data</span>): <span class="c-type">array</span>
    {
        <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">timeout</span>(<span class="c-number">15</span>)
            -><span class="c-func">post</span>(<span class="c-var">$this</span>-><span class="c-prop">appsScriptUrl</span>, [
                <span class="c-string">'name'</span>    <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'name'</span>],
                <span class="c-string">'phone'</span>   <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'phone'</span>],
                <span class="c-string">'service'</span> <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'service'</span>],
                <span class="c-string">'date'</span>    <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'date'</span>],
                <span class="c-string">'time'</span>    <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'time'</span>],
                <span class="c-string">'note'</span>    <span class="c-op">=></span> <span class="c-var">$data</span>[<span class="c-string">'note'</span>] <span class="c-op">??</span> <span class="c-string">''</span>,
            ]);

        <span class="c-keyword">if</span> (<span class="c-var">$response</span>-><span class="c-func">failed</span>()) {
            <span class="c-type">Log</span>::<span class="c-func">error</span>(<span class="c-string">'Queue booking failed'</span>, [
                <span class="c-string">'status'</span> <span class="c-op">=></span> <span class="c-var">$response</span>-><span class="c-func">status</span>(),
            ]);

            <span class="c-keyword">return</span> [
                <span class="c-string">'success'</span> <span class="c-op">=></span> <span class="c-const">false</span>,
                <span class="c-string">'message'</span> <span class="c-op">=></span> <span class="c-string">'จองคิวไม่สำเร็จ กรุณาลองใหม่'</span>,
            ];
        }

        <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">json</span>();
    }

    <span class="c-comment">/**
     * ดึงคิววันนี้ (สำหรับแสดงผลบนเว็บ)
     */</span>
    <span class="c-keyword">public function</span> <span class="c-func">getTodayQueues</span>(): <span class="c-type">array</span>
    {
        <span class="c-var">$today</span> <span class="c-op">=</span> <span class="c-func">now</span>()-><span class="c-func">format</span>(<span class="c-string">'d/m/Y'</span>);

        <span class="c-var">$response</span> <span class="c-op">=</span> <span class="c-type">Http</span>::<span class="c-func">timeout</span>(<span class="c-number">10</span>)
            -><span class="c-func">get</span>(<span class="c-var">$this</span>-><span class="c-prop">appsScriptUrl</span>, [
                <span class="c-string">'date'</span> <span class="c-op">=></span> <span class="c-var">$today</span>,
            ]);

        <span class="c-keyword">return</span> <span class="c-var">$response</span>-><span class="c-func">successful</span>()
            ? <span class="c-var">$response</span>-><span class="c-func">json</span>()
            : [<span class="c-string">'total'</span> <span class="c-op">=></span> <span class="c-number">0</span>, <span class="c-string">'queues'</span> <span class="c-op">=></span> []];
    }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- Laravel: Controller + Blade ฟอร์มจองคิว -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">Laravel: Controller + Blade ฟอร์มจองคิว</h3>
                        <p class="text-sm text-slate-500">หน้าเว็บจองคิวที่เชื่อมกับ Apps Script / Web booking form connected to Sheets</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">PHP</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">PHP — QueueBookingController.php</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-keyword">class</span> <span class="c-type">QueueBookingController</span> <span class="c-keyword">extends</span> <span class="c-type">Controller</span>
{
    <span class="c-keyword">public function</span> <span class="c-func">__construct</span>(
        <span class="c-keyword">private readonly</span> <span class="c-type">QueueBookingService</span> <span class="c-var">$booking</span>,
        <span class="c-keyword">private readonly</span> <span class="c-type">LineService</span> <span class="c-var">$line</span>,
    ) {}

    <span class="c-comment">// หน้าฟอร์มจองคิว</span>
    <span class="c-keyword">public function</span> <span class="c-func">create</span>()
    {
        <span class="c-var">$todayQueues</span> <span class="c-op">=</span> <span class="c-var">$this</span>-><span class="c-prop">booking</span>-><span class="c-func">getTodayQueues</span>();

        <span class="c-keyword">return</span> <span class="c-func">view</span>(<span class="c-string">'queue.create'</span>, [
            <span class="c-string">'todayTotal'</span> <span class="c-op">=></span> <span class="c-var">$todayQueues</span>[<span class="c-string">'total'</span>],
        ]);
    }

    <span class="c-comment">// บันทึกการจอง</span>
    <span class="c-keyword">public function</span> <span class="c-func">store</span>(<span class="c-type">Request</span> <span class="c-var">$request</span>)
    {
        <span class="c-var">$validated</span> <span class="c-op">=</span> <span class="c-var">$request</span>-><span class="c-func">validate</span>([
            <span class="c-string">'name'</span>    <span class="c-op">=></span> <span class="c-string">'required|string|max:100'</span>,
            <span class="c-string">'phone'</span>   <span class="c-op">=></span> <span class="c-string">'required|string|max:20'</span>,
            <span class="c-string">'service'</span> <span class="c-op">=></span> <span class="c-string">'required|string'</span>,
            <span class="c-string">'date'</span>    <span class="c-op">=></span> <span class="c-string">'required|date|after:today'</span>,
            <span class="c-string">'time'</span>    <span class="c-op">=></span> <span class="c-string">'required|string'</span>,
            <span class="c-string">'note'</span>    <span class="c-op">=></span> <span class="c-string">'nullable|string|max:500'</span>,
        ]);

        <span class="c-comment">// ส่งไป Google Sheets (ผ่าน Apps Script)</span>
        <span class="c-var">$result</span> <span class="c-op">=</span> <span class="c-var">$this</span>-><span class="c-prop">booking</span>-><span class="c-func">createBooking</span>(<span class="c-var">$validated</span>);

        <span class="c-keyword">if</span> (<span class="c-op">!</span><span class="c-var">$result</span>[<span class="c-string">'success'</span>]) {
            <span class="c-keyword">return</span> <span class="c-func">back</span>()
                -><span class="c-func">withErrors</span>([<span class="c-string">'error'</span> <span class="c-op">=></span> <span class="c-var">$result</span>[<span class="c-string">'message'</span>]])
                -><span class="c-func">withInput</span>();
        }

        <span class="c-comment">// (Optional) ส่ง LINE Push ตรงถึงลูกค้า</span>
        <span class="c-comment">// ถ้าลูกค้าเชื่อม LINE กับ Account</span>
        <span class="c-keyword">if</span> (<span class="c-func">auth</span>()-><span class="c-func">user</span>()?-><span class="c-prop">line_user_id</span>) {
            <span class="c-var">$this</span>-><span class="c-prop">line</span>-><span class="c-func">pushMessage</span>(
                <span class="c-func">auth</span>()-><span class="c-func">user</span>()-><span class="c-prop">line_user_id</span>,
                [[
                    <span class="c-string">'type'</span> <span class="c-op">=></span> <span class="c-string">'text'</span>,
                    <span class="c-string">'text'</span> <span class="c-op">=></span> <span class="c-string">"✅ จองคิวสำเร็จ!\n\n"</span>
                        . <span class="c-string">"📋 คิว: {$result['queueNo']}\n"</span>
                        . <span class="c-string">"💼 {$validated['service']}\n"</span>
                        . <span class="c-string">"📅 {$validated['date']}\n"</span>
                        . <span class="c-string">"⏰ {$validated['time']}\n\n"</span>
                        . <span class="c-string">"เราจะแจ้งเตือนก่อนถึงคิวครับ 🔔"</span>,
                ]],
            );
        }

        <span class="c-keyword">return</span> <span class="c-func">redirect</span>()
            -><span class="c-func">route</span>(<span class="c-string">'queue.success'</span>)
            -><span class="c-func">with</span>(<span class="c-string">'queueNo'</span>, <span class="c-var">$result</span>[<span class="c-string">'queueNo'</span>]);
    }
}</code></pre>
            </div>
                </div>
            </div>
        </div>

        <!-- วิธีตั้งค่า — Step-by-Step Setup -->
        <div class="enc-accordion" x-data="{open: false}">
            <button @click="open = !open" class="enc-accordion-header w-full text-left">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <div>
                        <h3 class="font-bold text-slate-800">วิธีตั้งค่า — Step-by-Step Setup</h3>
                        <p class="text-sm text-slate-500">ตั้งค่าระบบทั้งหมดจากศูนย์ ทำตามได้เลย / Complete setup guide from scratch</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-mono text-sky-600 bg-sky-50 px-2 py-1 rounded">Setup Guide</span>
                    <span x-show="!open" class="text-slate-400 text-sm">&#x1F512;</span>
                    <svg :class="open && 'rotate-180'" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>
            <div x-show="open" x-collapse style="display:none;">
                <div class="enc-accordion-body">
                    <div class="code-block">
                <div class="code-block-header">
                    <div class="code-block-dots"><span></span><span></span><span></span></div>
                    <span class="code-block-lang">Setup Guide — Step by Step</span>
                    <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                </div>
<pre><code><span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ขั้นตอนที่ 1: ขอ LINE Notify Token      ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span>

<span class="c-number">1.</span> ไปที่ <span class="c-string">https://notify-bot.line.me/</span>
<span class="c-number">2.</span> Login ด้วย LINE Account
<span class="c-number">3.</span> My Page → Generate Token
<span class="c-number">4.</span> เลือกห้องแชทที่จะรับแจ้งเตือน
   <span class="c-comment">   - "1-on-1 chat" = ส่งมาหาตัวเอง</span>
   <span class="c-comment">   - เลือก Group = ส่งเข้ากลุ่ม</span>
<span class="c-number">5.</span> Copy Token เก็บไว้

<span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ขั้นตอนที่ 2: สร้าง Google Sheets        ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span>

<span class="c-number">1.</span> สร้าง Google Sheets ใหม่
<span class="c-number">2.</span> ตั้งชื่อ Sheet = <span class="c-string">"การจอง"</span>
<span class="c-number">3.</span> สร้างหัวตาราง Row 1:
   <span class="c-const">A: Timestamp</span>
   <span class="c-const">B: ชื่อ</span>
   <span class="c-const">C: เบอร์โทร</span>
   <span class="c-const">D: บริการ</span>
   <span class="c-const">E: วันที่</span>
   <span class="c-const">F: เวลา</span>
   <span class="c-const">G: หมายเหตุ</span>
   <span class="c-const">H: หมายเลขคิว</span>
   <span class="c-const">I: แจ้งเตือน</span>

<span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ขั้นตอนที่ 3: เพิ่ม Apps Script          ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span>

<span class="c-number">1.</span> Extensions → Apps Script
<span class="c-number">2.</span> ลบโค้ดเดิม → วาง Code.gs จากด้านบน
<span class="c-number">3.</span> แก้ <span class="c-var">LINE_NOTIFY_TOKEN</span> = Token ของคุณ
<span class="c-number">4.</span> กด Save (Ctrl+S)

<span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ขั้นตอนที่ 4: ตั้ง Trigger               ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span>

<span class="c-number">1.</span> คลิก ⏰ (Triggers) ที่เมนูซ้าย
<span class="c-number">2.</span> + Add Trigger:
   <span class="c-const">Function</span>: onFormSubmit
   <span class="c-const">Event source</span>: From spreadsheet
   <span class="c-const">Event type</span>: On form submit
<span class="c-number">3.</span> + Add Trigger อีกตัว:
   <span class="c-const">Function</span>: checkUpcomingQueues
   <span class="c-const">Event source</span>: Time-driven
   <span class="c-const">Type</span>: Minutes timer → 30 minutes
<span class="c-number">4.</span> + Add Trigger อีกตัว:
   <span class="c-const">Function</span>: sendDailySummary
   <span class="c-const">Event source</span>: Time-driven
   <span class="c-const">Type</span>: Day timer → 8am to 9am

<span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ขั้นตอนที่ 5: (Optional) Deploy Web App ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span>

<span class="c-number">1.</span> Deploy → New deployment
<span class="c-number">2.</span> Type: Web app
<span class="c-number">3.</span> Execute as: Me
<span class="c-number">4.</span> Who has access: Anyone
<span class="c-number">5.</span> Copy URL → ใส่ใน Laravel .env:
   <span class="c-attr">GOOGLE_APPS_SCRIPT_URL</span>=<span class="c-string">https://script.google.com/...</span>

<span class="c-comment">╔══════════════════════════════════════════╗</span>
<span class="c-comment">║  ทดสอบ: ส่ง Google Form → ดู LINE! 🎉     ║</span>
<span class="c-comment">╚══════════════════════════════════════════╝</span></code></pre>
            </div>
                </div>
            </div>
        </div>

    </div>
</div>

    </main>
</div>

<!-- ===== BACK TO TOP + FOOTER ===== -->
<div class="text-center py-8 academy-gradient">
    <button @click="window.scrollTo({top: 0, behavior: 'smooth'})" class="inline-flex items-center gap-2 px-6 py-3 bg-white rounded-xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 text-sky-700 font-semibold border border-sky-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
        กลับด้านบน / Back to Top
    </button>
    <p class="mt-6 text-slate-400 text-sm">XMAN Code Academy &copy; {{ date('Y') }} — แหล่งเรียนรู้โค้ดมืออาชีพ</p>
</div>

</div>

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

function academy() {
    return {
        activeCat: 'laravel',
        sidebarOpen: false,
        search: '',
        categories: [
            { id: 'laravel', name: 'Laravel', icon: '\uD83D\uDD34', count: 6, topics: ['Eloquent Model', 'Controller CRUD', 'Migration', 'Middleware', 'Blade Template', 'API Resource'] },
            { id: 'php', name: 'PHP', icon: '\uD83D\uDC18', count: 4, topics: ['OOP \u2014 Class & Interface', 'Array Functions', 'Enum (PHP 8.1+)', 'Error Handling'] },
            { id: 'js', name: 'JavaScript', icon: '\uD83D\uDC9B', count: 4, topics: ['ES6+ Modern Syntax', 'Async / Await', 'DOM Manipulation', 'Alpine.js'] },
            { id: 'tailwind', name: 'Tailwind CSS', icon: '\uD83C\uDFA8', count: 2, topics: ['Responsive Card', 'Flexbox & Grid Layout'] },
            { id: 'python', name: 'Python', icon: '\uD83D\uDC0D', count: 2, topics: ['Dataclass & Type Hints', 'FastAPI'] },
            { id: 'flutter', name: 'Flutter / Dart', icon: '\uD83E\uDD8B', count: 2, topics: ['StatefulWidget', 'API Service'] },
            { id: 'sql', name: 'SQL / Database', icon: '\uD83D\uDDC4\uFE0F', count: 2, topics: ['CRUD & Joins', 'Index & Optimization'] },
            { id: 'git', name: 'Git', icon: '\uD83D\uDD00', count: 2, topics: ['Essential Commands', 'Advanced Git'] },
            { id: 'api', name: 'REST API', icon: '\uD83C\uDF10', count: 2, topics: ['Endpoint Design', 'Error Handling'] },
            { id: 'docker', name: 'Docker', icon: '\uD83D\uDC33', count: 2, topics: ['Dockerfile', 'Docker Compose'] },
            { id: 'lineoa', name: 'LINE OA', icon: '\uD83D\uDC9A', count: 13, topics: ['Overview Steps', 'Environment Setup', 'LineService Class', 'Webhook Controller', 'Routes & CSRF', 'Message Types', 'Flex Message', 'Notification Channel', 'Rich Menu', 'Event Listener', 'LINE Notify', 'Artisan Command', 'Scheduled Notifications'] },
            { id: 'appscript', name: 'Apps Script + Queue', icon: '\uD83D\uDCCB', count: 9, topics: ['System Architecture', 'Form Submit Trigger', 'LINE Notify Function', 'Queue Reminder', 'Daily Summary', 'Web App API', 'Laravel Service', 'Laravel Controller', 'Setup Guide'] },
        ],
        get filteredCategories() {
            if (!this.search) return this.categories;
            const s = this.search.toLowerCase();
            return this.categories.filter(c =>
                c.name.toLowerCase().includes(s) ||
                c.topics.some(t => t.toLowerCase().includes(s))
            );
        },
        selectCat(cat) {
            this.activeCat = cat;
            this.sidebarOpen = false;
            window.scrollTo({ top: document.querySelector('.enc-sidebar')?.offsetTop - 20 || 0, behavior: 'smooth' });
        },
        getCategoryName() {
            const cat = this.categories.find(c => c.id === this.activeCat);
            return cat ? cat.name : '';
        }
    }
}
</script>
@endpush
@endsection
