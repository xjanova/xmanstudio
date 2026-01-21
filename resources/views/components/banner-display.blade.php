@if($banner && $banner->isActive())
<div class="banner-placement banner-{{ $position }}" data-position="{{ $position }}" data-page="{{ $page }}" data-banner-id="{{ $banner->id }}">
    @if($banner->link_url)
        <a href="{{ $banner->link_url }}"
           {{ $banner->target_blank ? 'target="_blank" rel="noopener noreferrer"' : '' }}
           class="banner-link block"
           data-banner-id="{{ $banner->id }}"
           onclick="trackBannerClick({{ $banner->id }})">
            <img src="{{ $banner->image_url }}"
                 alt="{{ $banner->title }}"
                 class="w-full h-auto"
                 loading="lazy">
        </a>
    @else
        <img src="{{ $banner->image_url }}"
             alt="{{ $banner->title }}"
             class="w-full h-auto"
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
