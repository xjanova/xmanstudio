{{--
    The main campaign on the home page (classic / premium / nova / retro homes): one banner that
    links to /dgx-spark. Self-contained styles (dgxh-*), so it looks the same under every theme and
    needs no Tailwind rebuild. Shows nothing when the campaign is switched off in the admin or its
    product row is missing. The count is the real one (App\Support\DgxSparkCampaign::availability).
--}}
@php
    $dgxhShow = \App\Support\DgxSparkCampaign::enabled() && \App\Support\DgxSparkCampaign::product();
    if ($dgxhShow) {
        $dgxhAvailability = \App\Support\DgxSparkCampaign::availability();
        $dgxhPrice = \App\Support\DgxSparkCampaign::baht(\App\Support\DgxSparkCampaign::price());
        $dgxhVat = \App\Support\DgxSparkCampaign::priceIncludesVat();
        $dgxhImage = \App\Support\DgxSparkCampaign::mediaUrl('hero');
    }
@endphp
@if($dgxhShow)
<section class="dgxh {{ $dgxhImage ? 'has-image' : '' }}" aria-label="แคมเปญหลัก: NVIDIA DGX Spark พร้อม CluadeX และ BrainX ตลอดชีพ"
         @if($dgxhImage) style="--dgxh-img: url('{{ $dgxhImage }}');" @endif>
    <a href="{{ route('campaign.dgx-spark') }}" class="dgxh__link">
        <span class="dgxh__inner">
            <span class="dgxh__copy">
                <span class="dgxh__eyebrow"><i aria-hidden="true"></i> แคมเปญหลัก · จำกัด {{ $dgxhAvailability['cap'] }} ชุด</span>
                <span class="dgxh__title">NVIDIA DGX Spark <span>+ CluadeX &amp; BrainX ตลอดชีพ</span></span>
                <span class="dgxh__sub">ซูเปอร์คอมพิวเตอร์ AI 128 GB บนโต๊ะคุณ รันโมเดลเขียนโค้ดระดับ 120B ในเครื่อง โค้ดไม่ออกนอกออฟฟิศ</span>
            </span>
            <span class="dgxh__side">
                <span class="dgxh__price">{{ $dgxhPrice }} <small>{{ $dgxhVat ? 'รวม VAT' : '+ VAT' }}</small></span>
                <span class="dgxh__left">
                    @if($dgxhAvailability['remaining'] > 0)
                        เหลือ <b>{{ $dgxhAvailability['remaining'] }}</b> จาก {{ $dgxhAvailability['cap'] }} ชุด
                    @else
                        จองครบ {{ $dgxhAvailability['cap'] }} ชุดแล้ว
                    @endif
                </span>
                <span class="dgxh__cta">ดูรายละเอียด <span aria-hidden="true">→</span></span>
            </span>
        </span>
    </a>
</section>

@once
<style>
    .dgxh { position: relative; z-index: 20; isolation: isolate; overflow: hidden; color: #eef2ff;
        background:
            radial-gradient(50% 120% at 90% 20%, rgba(139, 92, 246, .35), transparent 70%),
            radial-gradient(40% 120% at 0% 100%, rgba(34, 211, 238, .22), transparent 70%),
            linear-gradient(100deg, #070a14, #0b1020 60%, #120a24);
        border-bottom: 1px solid rgba(255, 255, 255, .08); }
    .dgxh.has-image { background: linear-gradient(90deg, rgba(5, 7, 12, .92), rgba(5, 7, 12, .72) 55%, rgba(5, 7, 12, .45)), var(--dgxh-img) center / cover no-repeat, #05070c; }
    .dgxh__link { display: block; color: inherit !important; text-decoration: none !important; }
    .dgxh__inner { display: flex; align-items: center; justify-content: space-between; gap: 20px 40px; max-width: 1180px; margin: 0 auto; padding: clamp(22px, 3.4vw, 40px) 20px; }
    .dgxh__copy { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
    .dgxh__eyebrow { display: inline-flex; align-items: center; gap: 8px; align-self: flex-start; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(245, 200, 107, .4); background: rgba(245, 200, 107, .08); color: #f5c86b; font-size: 12.5px; font-weight: 700; }
    .dgxh__eyebrow i { width: 7px; height: 7px; border-radius: 50%; background: #f5c86b; box-shadow: 0 0 10px #f5c86b; }
    .dgxh__title { font-size: clamp(22px, 3.2vw, 36px); line-height: 1.2; font-weight: 800; letter-spacing: -.01em; color: #fff; }
    .dgxh__title span { background: linear-gradient(95deg, #22d3ee, #8b5cf6 60%, #e879f9); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .dgxh__sub { max-width: 60ch; color: #b4bdd3; font-size: clamp(14px, 1.3vw, 16px); line-height: 1.6; }
    .dgxh__side { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex: none; text-align: right; }
    .dgxh__price { font-size: clamp(26px, 3vw, 36px); font-weight: 800; color: #f5c86b; line-height: 1.1; }
    .dgxh__price small { font-size: 13px; font-weight: 600; color: #b4bdd3; }
    .dgxh__left { color: #b4bdd3; font-size: 14px; }
    .dgxh__left b { color: #fff; font-size: 17px; }
    .dgxh__cta { display: inline-flex; align-items: center; gap: 8px; margin-top: 6px; padding: 10px 20px; border-radius: 999px; background: linear-gradient(95deg, #22d3ee, #6366f1); color: #05070c; font-weight: 800; font-size: 15px; box-shadow: 0 10px 30px -10px rgba(34, 211, 238, .7); transition: transform .2s ease; }
    .dgxh__link:hover .dgxh__cta, .dgxh__link:focus-visible .dgxh__cta { transform: translateX(3px); }
    .dgxh__link:focus-visible { outline: 2px solid #22d3ee; outline-offset: -4px; }
    @media (max-width: 760px) {
        .dgxh__inner { flex-direction: column; align-items: stretch; padding: 22px 16px; }
        .dgxh__side { align-items: flex-start; text-align: left; }
        .dgxh__cta { align-self: stretch; justify-content: center; }
    }
    @media (prefers-reduced-motion: reduce) { .dgxh__cta { transition: none; } }
</style>
@endonce
@endif
