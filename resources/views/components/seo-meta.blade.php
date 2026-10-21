@php
    $seoSetting = \App\Models\SeoSetting::getInstance();
    $pageTitle = $title ?? $seoSetting->site_title;
    $pageDescription = $description ?? $seoSetting->site_description;
    $pageKeywords = $keywords ?? $seoSetting->site_keywords;
    $ogType = $type ?? 'website';

    // OG Image: priority → passed $image → database og_image → dynamic generator
    // Uses route-based serving to avoid symlink dependency
    if (!empty($image)) {
        $pageImage = $image;
        $pageImageType = 'image/png'; // Default for custom images
    } elseif ($seoSetting->og_image) {
        // Serve uploaded image via route (no storage symlink needed). The version makes a new
        // upload a new URL: Facebook, LINE and X keep a shared image cached by its address.
        $pageImage = route('og-image.site', ['v' => substr(md5($seoSetting->og_image), 0, 8)]);
        // Detect actual image type from file extension
        $ext = strtolower(pathinfo($seoSetting->og_image, PATHINFO_EXTENSION));
        $pageImageType = match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    } else {
        $pageImage = route('og-image.default');
        $pageImageType = 'image/png';
    }
@endphp

<!-- Primary Meta Tags -->
<title>{{ $pageTitle }}</title>
<meta name="title" content="{{ $pageTitle }}">
<meta name="description" content="{{ $pageDescription }}">
@if($pageKeywords)
<meta name="keywords" content="{{ $pageKeywords }}">
@endif
@if($seoSetting->site_author)
<meta name="author" content="{{ $seoSetting->site_author }}">
@endif
<link rel="canonical" href="{{ url()->current() }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:image" content="{{ $pageImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="{{ $pageImageType }}">
<meta property="og:site_name" content="{{ $seoSetting->site_name ?? 'XMAN Studio' }}">
<meta property="og:locale" content="th_TH">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ url()->current() }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $pageImage }}">
@if($seoSetting->twitter_site)
<meta name="twitter:site" content="{{ $seoSetting->twitter_site }}">
@endif
@if($seoSetting->twitter_creator)
<meta name="twitter:creator" content="{{ $seoSetting->twitter_creator }}">
@endif

<!-- LINE / Messaging Apps -->
<meta property="og:image:alt" content="{{ $pageTitle }}">

<!-- Google Site Verification -->
@if($seoSetting->google_site_verification)
<meta name="google-site-verification" content="{{ $seoSetting->google_site_verification }}">
@endif

<!-- Google Analytics -->
@if($seoSetting->google_analytics_id)
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $seoSetting->google_analytics_id }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ $seoSetting->google_analytics_id }}');
</script>
@endif

<!-- Structured Data (JSON-LD) -->
@php
    // Google shows the Organization logo in results and wants it square: the favicon emblem,
    // never a stored URL that can go stale (it pointed at a 404 /images/logo.png).
    $orgLogo = \App\Support\BrandLogo::markUrl();
@endphp
@if($seoSetting->structured_data)
{!! $seoSetting->getStructuredData($orgLogo && array_key_exists('logo', $seoSetting->structured_data) ? ['logo' => $orgLogo] : null) !!}
@else
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ $seoSetting->site_name ?? 'XMAN Studio' }}",
    "url": "{{ url('/') }}",
    "logo": "{{ $orgLogo ?: $pageImage }}",
    "description": "{{ $seoSetting->site_description ?? '' }}",
    "sameAs": []
}
</script>
@endif
