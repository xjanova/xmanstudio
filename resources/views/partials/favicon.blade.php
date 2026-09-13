{{--
    Site icon for every layout, from the icon uploaded on the admin Branding page.
    Square sizes are cut by FaviconController; the version tag changes with each upload so
    browsers and Cloudflare drop whatever icon they cached before (it was WordPress's).
--}}
@php
    $faviconVersion = \App\Http\Controllers\FaviconController::version();
@endphp
@if ($faviconVersion !== '')
    <link rel="icon" href="{{ url('/favicon.ico') }}?v={{ $faviconVersion }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url('/favicon-192.png') }}?v={{ $faviconVersion }}">
    <link rel="apple-touch-icon" href="{{ url('/apple-touch-icon.png') }}?v={{ $faviconVersion }}">
@elseif (! empty($fallback))
    <link rel="icon" type="image/png" href="{{ $fallback }}">
@endif
