<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <x-bi th="นี่คือพื้นที่ปลอดภัยของระบบ กรุณายืนยันรหัสผ่านของคุณก่อนดำเนินการต่อ" en="This is a secure area of the application. Please confirm your password before continuing." />
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="bi('common.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                <x-bi k="common.confirm" />
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
