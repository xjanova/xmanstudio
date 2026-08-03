{{--
    NOVA HOME — the "3D star system" landing page.

    Rendered by HomeController when the active theme is `nova`. Extends
    layouts.nova directly (same pattern as home-retro / layouts.retro); every
    other page on the site keeps layouts.app.

    Receives from HomeController: $featuredProducts, $categories, $featuredReviews.
--}}
@extends('layouts.nova')

@section('title', 'XMAN Studio - IT Solutions & Software Development ครบวงจร')
@section('meta_description', 'XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร — Blockchain, AI, Web & Mobile, IoT, Network Security และซอฟต์แวร์เฉพาะทาง')

@section('content')
    {{-- Promotion strip (carried over from the previous home page). --}}
    <div class="nova-promo">
        <div class="nova-shell nova-promo__inner">
            <span class="nova-promo__tag">%</span>
            <span class="nova-promo__text">มหกรรมลดราคา / Mega Sale</span>
            <span class="nova-promo__off">50-70% OFF</span>
            <a href="{{ route('services.index') }}" class="nova-promo__cta">
                ดูบริการทั้งหมด
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>

    @include('partials.nova-hero')
    @include('partials.nova-stats')
    @include('partials.nova-services')
    @include('partials.nova-products')
    @include('partials.nova-ecosystem')
    @include('partials.nova-proof')
    @include('partials.nova-cta')
@endsection

@push('styles')
<style>
    .nova-promo {
        position: relative;
        z-index: 30;
        overflow: hidden;
        background: linear-gradient(100deg, #7c1d3f, #b91c5c 40%, #7c2d92);
        border-bottom: 1px solid rgba(255, 255, 255, .1);
    }
    .nova-promo__inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 11px 20px;
        font-size: 13px;
    }
    .nova-promo__tag {
        display: grid;
        place-items: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--nv-core);
        color: #7c1d3f;
        font-size: 11px;
        font-weight: 900;
    }
    .nova-promo__text { font-weight: 700; color: #fff; letter-spacing: .02em; }
    .nova-promo__off {
        padding: 3px 12px;
        border-radius: 8px;
        background: var(--nv-core);
        color: #7c1d3f;
        font-size: 15px;
        font-weight: 900;
        letter-spacing: .02em;
    }
    .nova-promo__cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 15px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .95);
        color: #7c1d3f;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        transition: background .25s var(--nv-ease), transform .25s var(--nv-ease);
    }
    .nova-promo__cta svg { width: 13px; height: 13px; }
    .nova-promo__cta:hover,
    .nova-promo__cta:focus-visible { background: #fff; transform: translateX(2px); }
</style>
@endpush
