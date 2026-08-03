{{-- Nova closing CTA. --}}
<section class="nova-section nova-cta">
    <div class="nova-cta__aura" aria-hidden="true"></div>
    <div class="nova-shell" style="text-align:center;position:relative;">
        <h2 class="nova-h2 nova-reveal" style="font-size:clamp(28px,5vw,52px);">
            พร้อมที่จะเริ่ม<span class="nova-grad">โปรเจคของคุณ</span>?
        </h2>
        <p class="nova-lede nova-reveal" style="margin-bottom:34px;">
            ปรึกษาเราฟรี! ทีมผู้เชี่ยวชาญพร้อมให้คำแนะนำและวางแผนโปรเจคให้คุณ<br>
            <span style="color:var(--nv-fg-3);">Free consultation — we scope it with you before anything is billed.</span>
        </p>
        <div class="nova-reveal" style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center;">
            <a href="{{ route('support.index') }}" class="nova-btn nova-btn--primary">
                @include('partials.nova-icon', ['name' => 'chat'])
                ติดต่อเรา / Contact us
            </a>
            <a href="{{ route('quotation.services') }}" class="nova-btn nova-btn--ghost">
                ขอใบเสนอราคา / Get a quote
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>

<style>
    .nova-cta { padding: 110px 0 120px; }
    .nova-cta__aura {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            radial-gradient(ellipse 60% 70% at 50% 50%, rgba(139, 92, 246, .22), transparent 70%),
            radial-gradient(ellipse 40% 50% at 20% 30%, rgba(34, 211, 238, .14), transparent 70%),
            radial-gradient(ellipse 40% 50% at 80% 70%, rgba(232, 121, 249, .14), transparent 70%);
    }
</style>
