{{--
    A screen of the app in a glowing window (or a phone frame). `mock` = a mock-up drawn to show the
    layout until the real capture arrives: the picture says so itself, it is never passed off as real.
--}}
@props(['src', 'alt', 'width' => 1600, 'height' => 1000, 'mock' => false, 'phone' => false, 'eager' => false])
<div {{ $attributes->merge(['class' => $phone ? 'xp-phone' : 'xp-window']) }}>
    <div>
        @if($mock)<span class="xp-mock-tag" title="ภาพจำลองหน้าจอ รอแทนด้วยภาพจริงจากโปรแกรม">ภาพตัวอย่าง · MOCKUP</span>@endif
        <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" @unless($eager) loading="lazy" @endunless>
    </div>
</div>
