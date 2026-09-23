<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <x-bi th="ยืนยันตัวตนขั้นที่ 2 — กรอกรหัส 6 หลักจากแอป Authenticator ในมือถือ หรือรหัสสำรองชุดใดชุดหนึ่งที่เก็บไว้" en="Second step — enter the 6-digit code from your authenticator app, or one of your recovery codes." />
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code">
                <x-bi th="รหัสยืนยัน" en="Verification code" />
            </x-input-label>

            <x-text-input id="code" class="block mt-1 w-full text-center text-lg tracking-widest"
                          type="text"
                          name="code"
                          maxlength="64"
                          autocomplete="one-time-code"
                          required autofocus />

            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <button type="submit" form="two-factor-logout" class="text-sm text-gray-600 underline hover:text-gray-900">
                <x-bi th="ออกจากระบบ" en="Log out" />
            </button>

            <x-primary-button>
                <x-bi th="ยืนยัน" en="Verify" />
            </x-primary-button>
        </div>
    </form>

    <form id="two-factor-logout" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
</x-guest-layout>
