<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- SEO & Social Media Preview -->
        <x-seo-meta :title="config('app.name', 'XMAN Studio')" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Custom Head Code (Tracking & Verification) -->
        @php
            $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
        @endphp
        @if($customHeadCode)
            {!! $customHeadCode !!}
        @endif

        <!-- Google AdSense (site verification + ads) -->
        <x-adsense-head />
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- Custom Body Start Code (Tracking noscript) -->
        @php
            $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
        @endphp
        @if($customBodyStartCode)
            {!! $customBodyStartCode !!}
        @endif

        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-950 overflow-hidden">
            {{-- Backdrop artwork, matching the login/register space theme --}}
            <x-page-art art="hero-home" :opacity="40" />

            <div class="relative">
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-300" />
                </a>
            </div>

            <div class="relative w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-2xl overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

        {{-- Mobile Bottom Navigation Bar --}}
        @include('components.mobile-bottom-nav')

        {{-- AI Chat Floating Widget --}}
        @include('components.ai-chat-widget')

        <!-- Custom Body End Code (Chat widgets, Tracking pixels) -->
        @php
            $customBodyEndCode = \App\Models\Setting::getValue('custom_code_body_end', '');
        @endphp
        @if($customBodyEndCode)
            {!! $customBodyEndCode !!}
        @endif
    </body>
</html>
