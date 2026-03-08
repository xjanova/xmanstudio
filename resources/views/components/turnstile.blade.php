@props(['section' => ''])

@php
    $enabled = \App\Models\Setting::getValue('turnstile_enabled', false);
    $sectionEnabled = $section ? \App\Models\Setting::getValue("turnstile_{$section}", false) : true;
    $siteKey = \App\Models\Setting::getValue('turnstile_site_key', '');
@endphp

@if($enabled && $sectionEnabled && $siteKey)
    <div class="mb-4">
        <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-theme="auto"></div>
        @error('cf-turnstile-response')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
