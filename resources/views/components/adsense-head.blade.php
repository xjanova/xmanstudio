{{--
    Google AdSense — site verification + ad serving loader.

    Renders the official AdSense <script> loader and the
    <meta name="google-adsense-account"> verification tag on every public page.

    Controlled from Admin → Ads.txt:
      - Setting('adsense_client_id')  e.g. "ca-pub-1012362923849759"
      - Setting('adsense_enabled')    boolean toggle

    SECURITY: the client ID reaches an HTML attribute + meta content, so it is
    strictly format-validated here (ca-pub + 10-20 digits) before output, on top
    of Blade's {{ }} escaping. An invalid/empty ID renders nothing.
--}}
@php
    $adsenseClient = (string) \App\Models\Setting::getValue('adsense_client_id', '');
    $adsenseEnabled = (bool) \App\Models\Setting::getValue('adsense_enabled', false);
    $adsenseValid = $adsenseClient !== '' && preg_match('/^ca-pub-\d{10,20}$/', $adsenseClient) === 1;
@endphp
@if($adsenseEnabled && $adsenseValid)
    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="{{ $adsenseClient }}">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsenseClient }}" crossorigin="anonymous"></script>
@endif
