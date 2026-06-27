<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            <x-bi th="ข้อมูลโปรไฟล์" en="Profile Information" />
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            <x-bi th="อัปเดตข้อมูลโปรไฟล์และอีเมลของบัญชีคุณ" en="Update your account's profile information and email address." />
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="bi('common.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="bi('common.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        <x-bi th="อีเมลของคุณยังไม่ได้รับการยืนยัน" en="Your email address is unverified." />

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <x-bi th="คลิกที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง" en="Click here to re-send the verification email." />
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            <x-bi th="ส่งลิงก์ยืนยันใหม่ไปยังอีเมลของคุณแล้ว" en="A new verification link has been sent to your email address." />
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button><x-bi k="common.save" /></x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                ><x-bi th="บันทึกแล้ว" en="Saved." /></p>
            @endif
        </div>
    </form>
</section>
