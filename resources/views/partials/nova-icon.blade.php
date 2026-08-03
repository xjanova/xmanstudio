{{--
    Nova inline icon set.
    Usage: @include('partials.nova-icon', ['name' => 'home'])
    Stroke-based, inherits currentColor, sized by the parent (.nova-nav__icon
    / .nova-card__icon svg).
--}}
@php
    $novaIconPaths = [
        'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'grid'      => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
        'cube'      => '<path d="M12 2.5 21 7v10l-9 4.5L3 17V7z"/><path d="M3 7l9 4.5L21 7"/><path d="M12 11.5V21.5"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.5l3.5 2"/>',
        'spark'     => '<path d="M12 2.5 14.2 9 21 11.2 14.2 13.4 12 20 9.8 13.4 3 11.2 9.8 9z"/><path d="M18.5 3v3.5M20.25 4.75h-3.5"/>',
        'book'      => '<path d="M4 4.5A1.5 1.5 0 0 1 5.5 3H19v15.5H5.5A1.5 1.5 0 0 0 4 20z"/><path d="M4 18.5A1.5 1.5 0 0 1 5.5 17H19v4H5.5A1.5 1.5 0 0 1 4 19.5z"/>',
        'play'      => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5l6 3.5-6 3.5z"/>',
        'chat'      => '<path d="M21 11.5a8 8 0 0 1-11.6 7.1L3.5 20.5l1.9-5.4A8 8 0 1 1 21 11.5z"/>',
        'chain'     => '<path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2"/><rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6" rx="1"/>',
        'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/>',
        'mobile'    => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        'bolt'      => '<path d="M13 2.5 4 13.5h7L11 21.5l9-11h-7z"/>',
        'shield'    => '<path d="M12 2.5 20 6v6c0 4.5-3.2 8.3-8 9.5-4.8-1.2-8-5-8-9.5V6z"/><path d="M9 12l2 2 4-4"/>',
        'code'      => '<path d="M9.5 20.5 14.5 3.5"/><path d="M18 7.5 22 12l-4 4.5"/><path d="M6 7.5 2 12l4 4.5"/>',
        'layers'    => '<path d="M12 2.5 22 7.5 12 12.5 2 7.5z"/><path d="M2 12.5 12 17.5 22 12.5"/><path d="M2 17 12 22 22 17"/>',
        'brain'     => '<path d="M9.5 3.5A3 3 0 0 0 6.5 6.5a3 3 0 0 0-2 5.2A3 3 0 0 0 6 17a3 3 0 0 0 3.5 3.5V3.5z"/><path d="M14.5 3.5a3 3 0 0 1 3 3 3 3 0 0 1 2 5.2A3 3 0 0 1 18 17a3 3 0 0 1-3.5 3.5V3.5z"/>',
        'wrench'    => '<path d="M20 5.5a5 5 0 0 1-6.6 6.6L5.5 20a2.1 2.1 0 0 1-3-3l7.9-7.9A5 5 0 0 1 17 2.5z"/>',
        'arrow'     => '<path d="M4 12h15"/><path d="M13.5 6.5 19.5 12l-6 5.5"/>',
        'external'  => '<path d="M14 4.5h5.5V10"/><path d="M19.5 4.5 11 13"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
    ];
    $novaIconName = $name ?? 'arrow';
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $novaIconPaths[$novaIconName] ?? $novaIconPaths['arrow'] !!}
</svg>
