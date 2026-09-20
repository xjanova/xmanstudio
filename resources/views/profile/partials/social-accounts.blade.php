{{--
    "Connected accounts" on the profile page.

    Replaces the LINE-only card. Which rows appear is App\Support\Auth\SocialProviders'
    decision, the same one the login page and the controllers use.

    The disconnect button is hidden — not just disabled — when it would leave
    the owner with no way back in (no password, no other provider). The route
    refuses that case too; this is so the button never looks available and then
    fails.
--}}
@php
    use App\Support\Auth\SocialAuth;
    use App\Support\Auth\SocialProviders;

    $rows = [
        'google' => [
            'label' => 'Google',
            'connected' => (bool) $user->google_id,
            'detail' => $user->google_id ? $user->email : null,
            'avatar' => $user->google_avatar,
            'tone' => 'blue',
            'connect' => SocialProviders::googleEnabled() ? route('google.redirect', ['link' => 1]) : null,
            'unlink' => 'google.unlink',
        ],
        'line' => [
            'label' => 'LINE',
            'connected' => (bool) $user->line_uid,
            'detail' => $user->line_display_name,
            'avatar' => $user->line_picture_url,
            'tone' => 'green',
            'connect' => SocialProviders::lineEnabled() ? route('line.redirect', ['link' => 1]) : null,
            'unlink' => 'line.unlink',
        ],
        'telegram' => [
            'label' => 'Telegram',
            'connected' => (bool) $user->telegram_id,
            'detail' => $user->telegram_username ? '@' . $user->telegram_username : null,
            'avatar' => $user->telegram_avatar,
            'tone' => 'sky',
            // Telegram's widget posts straight to the callback, so "connect"
            // is a POST that parks the intent in the session first.
            'connect' => null,
            'unlink' => 'telegram.unlink',
        ],
    ];

    // A provider that is switched off but still attached must stay visible —
    // otherwise the only way to disconnect it disappears with the setting.
    $rows = array_filter(
        $rows,
        fn (array $row, string $key) => $row['connected'] || SocialProviders::enabled($key),
        ARRAY_FILTER_USE_BOTH
    );
@endphp

@if($rows !== [])
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white">
        <h2 class="text-lg font-semibold text-gray-900">
            <x-bi th="บัญชีที่เชื่อมต่อ" en="Connected accounts" />
        </h2>
        <p class="text-sm text-gray-500 mt-1">
            <x-bi th="เชื่อมบัญชีโซเชียลเพื่อเข้าสู่ระบบได้เร็วขึ้น โดยไม่ต้องกรอกรหัสผ่าน"
                  en="Link a social account to sign in without typing your password" />
        </p>
    </div>

    <div class="divide-y divide-gray-100">
        @foreach($rows as $key => $row)
            <div class="flex items-center justify-between gap-4 px-6 py-4">
                <div class="flex items-center gap-4 min-w-0">
                    @if($row['connected'] && $row['avatar'])
                        <img src="{{ $row['avatar'] }}" alt="" class="w-11 h-11 rounded-full border-2 border-{{ $row['tone'] }}-300 object-cover">
                    @else
                        <div class="w-11 h-11 rounded-full flex items-center justify-center shrink-0
                                    {{ $row['connected'] ? 'bg-' . $row['tone'] . '-500' : 'bg-gray-100' }}">
                            <span class="text-sm font-bold {{ $row['connected'] ? 'text-white' : 'text-gray-400' }}">
                                {{ mb_substr($row['label'], 0, 1) }}
                            </span>
                        </div>
                    @endif

                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">{{ $row['label'] }}</p>
                        @if($row['connected'])
                            <p class="text-sm text-{{ $row['tone'] }}-600 truncate">
                                <x-bi th="เชื่อมต่อแล้ว" en="Connected" />
                                @if($row['detail']) · <span class="text-gray-500">{{ $row['detail'] }}</span> @endif
                            </p>
                        @else
                            <p class="text-sm text-gray-400"><x-bi th="ยังไม่ได้เชื่อมต่อ" en="Not connected" /></p>
                        @endif
                    </div>
                </div>

                <div class="shrink-0">
                    @if($row['connected'])
                        @if(SocialAuth::canUnlink($user, $key))
                            <form method="POST" action="{{ route($row['unlink']) }}"
                                  onsubmit="return confirm('ต้องการยกเลิกการเชื่อมต่อ {{ $row['label'] }} หรือไม่?')">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                    <x-bi th="ยกเลิกการเชื่อมต่อ" en="Disconnect" />
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400 max-w-[190px] block text-right leading-snug">
                                <x-bi th="ตั้งรหัสผ่านก่อน จึงจะยกเลิกได้ — ตอนนี้นี่คือทางเดียวที่คุณเข้าบัญชีได้"
                                      en="Set a password first — this is currently your only way to sign in" />
                            </span>
                        @endif
                    @elseif($key === 'telegram')
                        @if(SocialProviders::telegramEnabled())
                            <form method="POST" action="{{ route('telegram.link') }}">
                                @csrf
                                <button type="submit" class="px-5 py-2 rounded-lg text-white text-sm font-medium" style="background-color:#229ED9;">
                                    <x-bi th="เชื่อมต่อ" en="Connect" />
                                </button>
                            </form>
                        @endif
                    @elseif($row['connect'])
                        <a href="{{ $row['connect'] }}"
                           class="px-5 py-2 rounded-lg text-white text-sm font-medium inline-block
                                  {{ $key === 'line' ? '' : 'bg-blue-600 hover:bg-blue-700' }}"
                           @if($key === 'line') style="background-color:#06C755;" @endif>
                            <x-bi th="เชื่อมต่อ" en="Connect" />
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($user->password_set_at === null)
        <div class="px-6 py-4 bg-amber-50 border-t border-amber-200">
            <p class="text-sm text-amber-800">
                <x-bi th="บัญชีนี้ยังไม่มีรหัสผ่าน — ถ้าตั้งรหัสผ่านไว้ คุณจะเข้าระบบได้แม้ช่องทางโซเชียลมีปัญหา"
                      en="This account has no password yet. Setting one means you can still sign in if a provider stops working." />
            </p>
        </div>
    @endif
</div>
@endif
