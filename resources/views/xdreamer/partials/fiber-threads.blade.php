{{-- Animated fiber threads canvas — bezier curves with cursor reactivity --}}
{{-- Props: id, density, speed, hueShift, opacity, interactive --}}
@php
    $id = $id ?? 'fiber-canvas';
    $density = $density ?? 70;
    $speed = $speed ?? 1;
    $hueShift = $hueShift ?? 70;
    $opacity = $opacity ?? 0.55;
    $interactive = $interactive ?? true;
@endphp
<canvas id="{{ $id }}" data-fiber-threads
        data-density="{{ $density }}"
        data-speed="{{ $speed }}"
        data-hue-shift="{{ $hueShift }}"
        data-opacity="{{ $opacity }}"
        data-interactive="{{ $interactive ? '1' : '0' }}"
        style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"></canvas>
