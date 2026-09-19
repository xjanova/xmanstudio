{{--
    Cloudflare Turnstile widget.

    Whether it renders is decided by App\Support\Turnstile — the same class the
    VerifyTurnstile middleware asks. Never re-derive the condition here: if this
    view and the middleware disagree, the middleware demands a token this widget
    never produced and the form cannot be submitted at all.
--}}
@props(['section' => ''])

@if(\App\Support\Turnstile::enabledFor($section))
    <div class="mb-4">
        <div class="cf-turnstile" data-sitekey="{{ \App\Support\Turnstile::siteKey() }}" data-theme="auto"></div>
        @error('cf-turnstile-response')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
