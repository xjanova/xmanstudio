{{--
    Bilingual label — shows Thai AND English together (no language switcher).

    NOTE: never write a literal x-bi tag inside this comment — Blade's component
    compiler processes component tags even inside comments and that would make
    this component render itself recursively.

    Props:
      th       Thai text (inline literal)               e.g. th="หน้าหลัก"
      en       English text (inline literal)             e.g. en="Home"
      k        shared key from lang/bi/*.php             e.g. k="common.save"
      layout   "inline" (default) | "stack" (TH over smaller EN)
      sep      inline separator (default " / ")
      class    extra classes pass through

    For attributes / title / option / JS (no markup allowed) use the
    bi('common.save') helper or a plain "ไทย / English" literal instead.
--}}
@props([
    'k' => null,
    'th' => null,
    'en' => null,
    'layout' => 'inline',
    'sep' => ' / ',
])
@php
    if ($k) {
        $pair = \App\Support\Bi::get($k);
        $thText = $pair['th'] ?? $k;
        $enText = $pair['en'] ?? '';
    } else {
        $thText = $th ?? '';
        $enText = $en ?? '';
    }
    $isStack = $layout === 'stack' || $layout === 'block';
    $wrapClass = $isStack ? 'bi bi-stack' : 'bi bi-inline';
@endphp
@if($isStack)
<span {{ $attributes->merge(['class' => $wrapClass]) }}><span class="bi-th">{{ $thText }}</span>@if($enText !== '')<span class="bi-en">{{ $enText }}</span>@endif</span>
@else
<span {{ $attributes->merge(['class' => $wrapClass]) }}><span class="bi-th">{{ $thText }}</span>@if($enText !== '')<span class="bi-sep" aria-hidden="true">{{ $sep }}</span><span class="bi-en">{{ $enText }}</span>@endif</span>
@endif
