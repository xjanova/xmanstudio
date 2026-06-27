<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <x-bi th="ขอบคุณที่สมัครสมาชิก! ก่อนเริ่มต้นใช้งาน กรุณายืนยันอีเมลของคุณโดยคลิกลิงก์ที่เราเพิ่งส่งไปให้คุณ หากคุณไม่ได้รับอีเมล เรายินดีส่งให้ใหม่อีกครั้ง" en="Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another." layout="stack" />
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            <x-bi th="ลิงก์ยืนยันใหม่ได้ถูกส่งไปยังอีเมลที่คุณให้ไว้ตอนสมัครสมาชิกแล้ว" en="A new verification link has been sent to the email address you provided during registration." layout="stack" />
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    <x-bi th="ส่งอีเมลยืนยันอีกครั้ง" en="Resend Verification Email" />
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <x-bi k="common.logout" />
            </button>
        </form>
    </div>
</x-guest-layout>
