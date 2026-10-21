{{--
    The "or continue with" block on the login and register pages.

    Which buttons exist is decided by App\Support\Auth\SocialProviders — the
    same class each controller asks. The old LINE button read
    Setting::getValue('line_login_enabled') on its own, so a half-configured
    channel drew a button that led straight to an error page. Never re-derive
    the condition here.

    Props:
      mode  — 'login' or 'register', wording only
      dark  — light text on a dark card (the X-DREAMER auth pages)
--}}
@props(['mode' => 'login', 'dark' => false])

@php
    use App\Support\Auth\SocialProviders;

    $providers = SocialProviders::enabledList();
@endphp

@if($providers !== [])
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t {{ $dark ? 'border-white/15' : 'border-gray-300' }}"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-3 {{ $dark ? 'bg-transparent text-gray-400' : 'bg-white text-gray-500' }}">
                    @if($mode === 'register')
                        <x-bi th="หรือสมัครด้วย" en="Or sign up with" />
                    @else
                        <x-bi th="หรือเข้าสู่ระบบด้วย" en="Or sign in with" />
                    @endif
                </span>
            </div>
        </div>

        <div class="mt-4 space-y-3">

            @if(in_array('google', $providers, true))
                <a href="{{ route('google.redirect') }}"
                   class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-xl border font-medium transition
                          {{ $dark
                              ? 'border-white/20 bg-white/5 text-white hover:bg-white/10'
                              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                    {{-- Google's four-colour mark: fixed brand colours, never currentColor. --}}
                    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M23.52 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.54 5.54 0 01-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.87z"/>
                        <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.95-2.91l-3.88-3.01c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.26v3.09A12 12 0 0012 24z"/>
                        <path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 010-4.56V6.63H1.26a12 12 0 000 10.74l4.01-3.09z"/>
                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 001.26 6.63l4.01 3.09C6.22 6.86 8.87 4.75 12 4.75z"/>
                    </svg>
                    <x-bi th="ดำเนินการต่อด้วย Google" en="Continue with Google" />
                </a>
            @endif

            @if(in_array('line', $providers, true))
                <a href="{{ route('line.redirect') }}"
                   class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-white font-medium transition hover:brightness-110"
                   style="background-color: #06C755;">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    <x-bi th="ดำเนินการต่อด้วย LINE" en="Continue with LINE" />
                </a>
            @endif

            @if(in_array('telegram', $providers, true))
                {{--
                    Telegram has no OAuth redirect — its script renders an iframe
                    button that sends the signed profile straight to data-auth-url.
                    The look is Telegram's, so it cannot be styled to match the
                    others; it is centred instead.

                    A wrong bot username or a domain not registered with
                    @BotFather (/setdomain) renders nothing at all, with no error
                    anywhere, so the note below is what tells an operator why the
                    button they enabled is missing.
                --}}
                <div class="flex justify-center min-h-[40px]">
                    <script async src="https://telegram.org/js/telegram-widget.js?22"
                            data-telegram-login="{{ \App\Support\Auth\SocialProviders::telegramBotUsername() }}"
                            data-size="large"
                            data-radius="12"
                            data-auth-url="{{ route('telegram.callback') }}"
                            data-request-access="write"></script>
                </div>
            @endif
        </div>
    </div>
@endif
