@if($banner && $banner->isActive())
@php
    $displayWidth = $banner->display_width ?? 1200;
    $displayHeight = $banner->display_height ?? 630;
    $cropData = $banner->crop_data;

    // Calculate object-position from crop data
    $style = '';
    if ($cropData && isset($cropData['x']) && isset($cropData['y'])) {
        $offsetX = -($cropData['x'] * $cropData['scale']);
        $offsetY = -($cropData['y'] * $cropData['scale']);
        $style = sprintf('object-position: %dpx %dpx;', $offsetX, $offsetY);
    }
@endphp

<div class="banner-placement banner-{{ $position }}"
     data-position="{{ $position }}"
     data-page="{{ $page }}"
     data-banner-id="{{ $banner->id }}"
     style="position: relative; overflow: hidden; width: 100%; max-width: {{ $displayWidth }}px; aspect-ratio: {{ $displayWidth }}/{{ $displayHeight }};">

    @if($banner->link_url)
        <a href="{{ $banner->link_url }}"
           {{ $banner->target_blank ? 'target="_blank" rel="noopener noreferrer"' : '' }}
           class="banner-link block"
           data-banner-id="{{ $banner->id }}"
           onclick="trackBannerClick({{ $banner->id }})"
           style="display: block; width: 100%; height: 100%;">
            <img src="{{ $banner->image_url }}"
                 alt="{{ $banner->title }}"
                 style="width: 100%; height: 100%; object-fit: cover; {{ $style }}"
                 loading="lazy">
        </a>
    @else
        <img src="{{ $banner->image_url }}"
             alt="{{ $banner->title }}"
             style="width: 100%; height: 100%; object-fit: cover; {{ $style }}"
             loading="lazy">
    @endif
</div>

<script>
// Track banner view on load
(function() {
    if (typeof window.trackedBanners === 'undefined') {
        window.trackedBanners = new Set();
    }

    const bannerId = {{ $banner->id }};

    // Track view only once per page load
    if (!window.trackedBanners.has(bannerId)) {
        window.trackedBanners.add(bannerId);

        fetch('{{ route('banners.track-view', $banner->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        }).catch(err => console.error('Failed to track banner view:', err));
    }
})();

// Track banner click
function trackBannerClick(bannerId) {
    fetch('/banners/' + bannerId + '/track-click', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    }).catch(err => console.error('Failed to track banner click:', err));
}
</script>
@endif
