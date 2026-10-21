{{-- Stop 8 — the launch gate: a wormhole (world/Wormhole.js) and the closing call to action from nova-cta. --}}
<section id="xu-launch" class="xu-st" data-station="launch" data-len="1.5"
         data-label-th="ประตูมิติ" data-label-en="Launch" aria-labelledby="xu-launch-title">
    <div class="xu-panel xu-launch">
        <p class="xu-eyebrow xu-r" style="--i: 0;">
            <span class="xu-eyebrow__dot" aria-hidden="true"></span>
            จุดหมายต่อไปคือโปรเจคของคุณ / Your project is next
        </p>
        <h2 id="xu-launch-title" class="xu-launch__title xu-r" style="--i: 0.6;">
            พร้อมที่จะเริ่ม<span class="xu-grad">โปรเจคของคุณ</span>?
        </h2>
        <p class="xu-lede xu-r" style="--i: 1.2;">
            ปรึกษาเราฟรี! ทีมผู้เชี่ยวชาญพร้อมให้คำแนะนำและวางแผนโปรเจคให้คุณ
            <small>Free consultation — we scope it with you before anything is billed.</small>
        </p>
        <div class="xu-launch__cta xu-r" style="--i: 1.8;">
            <a href="{{ route('contact.show') }}" class="xu-btn xu-btn--primary xu-btn--lg" data-xu-launch>
                @include('partials.nova-icon', ['name' => 'chat'])
                <span>ติดต่อเรา <small>Contact us</small></span>
            </a>
            <a href="{{ route('quote.index') }}" class="xu-btn xu-btn--ghost xu-btn--lg" data-xu-launch>
                <span>ขอใบเสนอราคา <small>Get a quote</small></span>
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
