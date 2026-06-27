<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <x-bi th="ลืมรหัสผ่าน? ไม่ต้องกังวล เพียงแจ้งอีเมลของคุณ แล้วเราจะส่งลิงก์รีเซ็ตรหัสผ่านไปให้ เพื่อให้คุณตั้งรหัสผ่านใหม่ได้" en="Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one." layout="stack" />
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="bi('common.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                <x-bi th="ส่งลิงก์รีเซ็ตรหัสผ่านทางอีเมล" en="Email Password Reset Link" />
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
