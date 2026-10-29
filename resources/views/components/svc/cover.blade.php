{{--
    A service category's picture: the image the admin set on the category, otherwise the in-house
    artwork (public_html/artwork, painted on black) that fits its key. The list of keys lives here
    only, so the services list and a service's own page always show a category the same way.
    Purely decorative — the name is always printed next to it.
--}}
@props(['category' => null, 'key' => null])
@php
    $key = (string) ($category?->key ?? $key);
    $exact = [
        'blockchain' => 'card-blockchain', 'web' => 'card-web', 'mobile' => 'card-mobile', 'ai' => 'card-ai',
        'iot' => 'card-iot', 'security' => 'card-security', 'software' => 'card-software', 'flutter' => 'card-flutter',
        'ai_chatbot' => 'card-ai', 'ai_image' => 'card-xdreamer', 'music_ai' => 'card-design',
        'web_development' => 'card-web', 'app_development' => 'card-mobile',
    ];
    // keys the admin adds later still get a fitting picture: the first word that matches wins
    $words = ['chatbot' => 'card-ai', 'image' => 'card-xdreamer', 'music' => 'card-design', 'blockchain' => 'card-blockchain',
        'web' => 'card-web', 'app' => 'card-mobile', 'mobile' => 'card-mobile', 'flutter' => 'card-flutter', 'iot' => 'card-iot',
        'security' => 'card-security', 'software' => 'card-software', 'ai' => 'card-ai'];
    $art = $exact[$key] ?? collect($words)->first(fn ($file, $word) => str_contains($key, $word)) ?? 'hero-network';
    $src = $category?->image ?: asset('artwork/' . $art . '.webp');
@endphp
<img src="{{ $src }}" alt="" aria-hidden="true" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'svc-cover']) }}>
