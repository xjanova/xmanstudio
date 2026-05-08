@extends('layouts.app')

@section('title', 'แพ็กเกจราคา · Chanthra Studio | XMAN Studio')

@push('styles')
<style>
    :root {
        --gold: #d4a76a;
        --gold-hi: #f0cb88;
        --gold-lo: #8b6938;
        --crimson: #b03346;
        --plum: #2a1b3d;
        --text-1: #f0e7d4;
        --text-2: #b8aec0;
        --text-3: #7d7388;
    }

    .pricing-page {
        background:
            radial-gradient(ellipse at top right, rgba(176, 51, 70, 0.15), transparent 55%),
            radial-gradient(ellipse at bottom left, rgba(212, 167, 106, 0.10), transparent 55%),
            #0c0814;
        color: var(--text-1);
        min-height: 100vh;
    }

    .font-display { font-family: 'Cormorant Garamond', Georgia, serif; }
    .font-mono { font-family: 'IBM Plex Mono', Consolas, monospace; }

    .glass-card {
        background: linear-gradient(180deg, rgba(26, 18, 48, 0.72) 0%, rgba(18, 9, 30, 0.85) 100%);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(232, 213, 179, 0.06);
        border-radius: 12px;
    }

    .gold-cta {
        background: linear-gradient(135deg, var(--gold-lo), var(--gold), var(--gold-hi));
        color: #1a0d05;
        box-shadow: 0 6px 24px rgba(212, 167, 106, 0.35);
    }
    .gold-cta:hover { transform: translateY(-2px); }
</style>
@endpush

@section('content')
<div class="pricing-page py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
            <a href="{{ route('chanthra-studio.detail') }}" class="font-mono text-xs" style="color: var(--text-3);">← chanthra studio</a>
            <h1 class="font-display italic text-6xl mt-4" style="color: var(--text-1);">เลือกแพ็กเกจ</h1>
            <p class="text-lg mt-3" style="color: var(--text-2);">1 license = 1 เครื่อง · ย้ายเครื่องเองได้ผ่าน Deactivate · auto-update รวมในทุกแพ็กเกจ</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($pricing as $key => $p)
                <div class="glass-card p-8 relative" style="{{ $key === 'yearly' ? 'border-color: rgba(212, 167, 106, 0.5);' : '' }}">
                    @if ($key === 'yearly')
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full gold-cta font-mono text-xs">
                            MOST POPULAR
                        </div>
                    @endif

                    <div class="font-mono text-xs tracking-widest mb-2" style="color: var(--gold);">{{ strtoupper($p['name']) }}</div>
                    <div class="font-display italic text-6xl mb-1" style="color: var(--text-1);">฿{{ number_format($p['price']) }}</div>
                    <div class="font-mono text-xs mb-6" style="color: var(--text-3);">
                        {{ $p['duration_days'] ? $p['duration_days'] . ' days' : 'one-time · perpetual' }}
                    </div>

                    <ul class="space-y-2 mb-8 text-sm" style="color: var(--text-2);">
                        @foreach ($p['features'] as $feat)
                            <li class="flex items-start gap-2">
                                <span class="font-display italic text-base" style="color: var(--gold);">·</span>
                                {{ $feat }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="/cart/add/chanthra-studio?plan={{ $key }}" class="block text-center {{ $key === 'yearly' ? 'gold-cta font-display italic text-lg' : '' }} px-6 py-4 rounded-lg" style="{{ $key === 'yearly' ? '' : 'color: var(--text-1); background: rgba(255,255,255,0.04); border: 1px solid rgba(232,213,179,0.08);' }}">
                        ซื้อ {{ $p['name_th'] }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-16 max-w-2xl mx-auto">
            <p class="font-display italic text-2xl mb-2" style="color: var(--gold-hi);">ยังไม่แน่ใจ?</p>
            <p class="text-sm" style="color: var(--text-2);">
                <a href="{{ route('chanthra-studio.manual') }}" class="underline" style="color: var(--gold);">อ่านคู่มือ</a> ก่อนตัดสินใจ · ทุกแพ็กเกจรวม auto-update และ priority bug fix
            </p>
        </div>
    </div>
</div>
@endsection
