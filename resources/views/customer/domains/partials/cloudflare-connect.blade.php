{{--
    เชื่อม Cloudflare ของลูกค้าด้วย API token — ใช้ทั้งในแผง "ชี้ไป Cloudflare"
    และในการ์ดตั้งค่าด่วนของโดเมนที่อยู่บน Cloudflare แล้ว
    คู่มือทีละขั้นมีภาพจริงของหน้า Cloudflare (public_html/images/guides/cloudflare-token-*.jpg)
    ตัวแปร: $cfTokenUrl, $intro (หัวข้อ, ไม่บังคับ)
--}}
<form method="POST" action="{{ route('customer.domains.cloudflare-connect') }}"
      x-data="{ sending: false }" @submit="sending = true"
      class="rounded-xl border border-orange-200 dark:border-orange-500/30 bg-orange-50 dark:bg-orange-500/10 p-4 space-y-3">
    @csrf
    <p class="text-sm font-semibold text-slate-900 dark:text-white">
        @isset($intro)
            {{ $intro }}
        @else
            <x-bi th="แบบอัตโนมัติ (แนะนำ) — เชื่อม Cloudflare ครั้งเดียว ใช้ได้ทุกโดเมน"
                  en="Automatic (recommended) — connect Cloudflare once, use it for every domain" />
        @endisset
    </p>
    <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-300 list-decimal pl-5">
        <li>
            <a href="{{ $cfTokenUrl }}" target="_blank" rel="noopener noreferrer" class="text-orange-600 dark:text-orange-400 font-semibold hover:underline">
                <x-bi th="เปิดหน้าสร้าง API token ที่ Cloudflare" en="Open Cloudflare's create-token page" />
            </a>
            — <x-bi th="สิทธิ์ถูกติ๊กไว้ให้แล้ว เลื่อนลงกด Continue to summary → Create Token"
                    en="the permissions are already ticked: scroll down, Continue to summary → Create Token" />
        </li>
        <li><x-bi th="คัดลอก token ที่ได้มาวางที่นี่ (Cloudflare แสดงแค่ครั้งเดียว)" en="Copy the token and paste it here (Cloudflare shows it once)" /></li>
    </ol>

    {{-- คู่มือสำหรับคนที่ไม่เคยสร้าง token — ภาพจริงจากหน้า Cloudflare --}}
    <details class="group rounded-lg bg-white/70 dark:bg-slate-900/40 border border-orange-100 dark:border-orange-500/20">
        <summary class="cursor-pointer px-3 py-2 text-sm font-semibold text-orange-700 dark:text-orange-300">
            <x-bi th="ไม่เคยทำ? ดูวิธีเอา token ทีละขั้น (มีภาพ)" en="Never done this? Step-by-step with pictures" />
        </summary>
        <ol class="px-3 pb-4 pt-1 space-y-4 text-sm text-slate-700 dark:text-slate-300">
            <li class="space-y-1">
                <p class="font-semibold">1. <x-bi th="มีบัญชี Cloudflare และล็อกอินไว้" en="Have a Cloudflare account, signed in" /></p>
                <p>
                    <x-bi th="ยังไม่มี? สมัครฟรีที่" en="None yet? Sign up free at" />
                    <a href="https://dash.cloudflare.com/sign-up" target="_blank" rel="noopener noreferrer" class="text-orange-600 dark:text-orange-400 hover:underline">dash.cloudflare.com/sign-up</a>
                    <x-bi th="(ใช้อีเมล แล้วกดยืนยันในอีเมล) — ไม่ต้องผูกบัตร" en="(an e-mail address, then confirm it) — no card needed" />
                </p>
            </li>
            <li class="space-y-2">
                <p class="font-semibold">2. <x-bi th="กดลิงก์ “เปิดหน้าสร้าง API token ที่ Cloudflare” ด้านบน" en="Click “Open Cloudflare's create-token page” above" /></p>
                <p><x-bi th="จะเห็นหน้า Create Token ชื่อ “XMAN Studio” พร้อมสิทธิ์ 4 แถวแบบในภาพ — ไม่ต้องแก้อะไร (ถ้าเด้งไปหน้าล็อกอิน ให้ล็อกอินแล้วกดลิงก์อีกครั้ง)"
                          en="You'll see Create Token named “XMAN Studio” with four permission rows like this — change nothing. (Sent to sign in? Sign in and click the link again.)" /></p>
                <img src="{{ asset('images/guides/cloudflare-token-1.jpg') }}" alt="Cloudflare Create Token: XMAN Studio, Account Settings Read, Zone Edit, DNS Edit, Zone Settings Edit"
                     loading="lazy" width="650" height="400" class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700">
            </li>
            <li class="space-y-2">
                <p class="font-semibold">3. <x-bi th="เลื่อนลงล่างสุด กด “Continue to summary”" en="Scroll to the bottom, click “Continue to summary”" /></p>
                <img src="{{ asset('images/guides/cloudflare-token-2.jpg') }}" alt="Continue to summary button"
                     loading="lazy" width="650" height="182" class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700">
            </li>
            <li class="space-y-2">
                <p class="font-semibold">4. <x-bi th="ตรวจหน้าสรุปให้ตรงกับภาพ แล้วกด “Create Token”" en="Check the summary matches, then click “Create Token”" /></p>
                <img src="{{ asset('images/guides/cloudflare-token-3.jpg') }}" alt="Token summary with the Create Token button"
                     loading="lazy" width="660" height="262" class="w-full max-w-xl rounded-lg border border-slate-200 dark:border-slate-700">
            </li>
            <li class="space-y-1">
                <p class="font-semibold">5. <x-bi th="กดปุ่ม “Copy” ข้างกล่อง token" en="Click “Copy” next to the token" /></p>
                <p><x-bi th="token เป็นตัวอักษรยาวราว 40 ตัว Cloudflare แสดงให้ครั้งเดียว — ถ้าปิดไปก่อนคัดลอก ที่หน้า API Tokens กด ⋯ ข้าง XMAN Studio → Roll เพื่อออกค่าใหม่"
                          en="About 40 characters, shown once — closed it too soon? On API Tokens, ⋯ next to XMAN Studio → Roll for a new one." /></p>
            </li>
            <li class="space-y-1">
                <p class="font-semibold">6. <x-bi th="กลับมาหน้านี้ วางในช่องด้านล่าง แล้วกด “เชื่อมต่อ”" en="Back here: paste it below and click “Connect”" /></p>
            </li>
            <li class="rounded-lg bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs text-slate-600 dark:text-slate-400 list-none">
                <x-bi th="token ใช้แทนรหัสผ่านเฉพาะสิทธิ์ที่ติ๊กไว้ อย่าส่งให้ใคร ทีมงานจะไม่ขอ token ทางแชตหรืออีเมล · เลิกใช้เมื่อไหร่ก็ได้: กด “ยกเลิกการเชื่อม” ที่หน้านี้ แล้วลบ token ที่ Cloudflare (My Profile → API Tokens → ⋯ → Delete)"
                      en="The token works like a password for the ticked permissions — never share it; our team will never ask for it by chat or e-mail. Stop any time: “Disconnect” here, then delete the token at Cloudflare (My Profile → API Tokens → ⋯ → Delete)." />
            </li>
        </ol>
    </details>

    <div class="flex flex-col sm:flex-row gap-2">
        <input type="password" name="cloudflare_token" required minlength="20" maxlength="200" autocomplete="off" spellcheck="false"
               placeholder="Cloudflare API token"
               class="w-full sm:flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white text-sm font-mono focus:border-orange-500 focus:ring-orange-500">
        <button type="submit" :disabled="sending"
                class="shrink-0 px-5 py-2.5 rounded-lg bg-orange-500 hover:bg-orange-400 text-white text-sm font-semibold transition disabled:opacity-50">
            <span x-show="!sending"><x-bi th="เชื่อมต่อ" en="Connect" /></span>
            <span x-show="sending" x-cloak><x-bi th="กำลังตรวจ token…" en="Checking token…" /></span>
        </button>
    </div>
    <p class="text-xs text-slate-500 dark:text-slate-400">
        <x-bi th="token เก็บแบบเข้ารหัส ไม่แสดงกลับ ใช้ทำแค่สิ่งที่คุณกดเท่านั้น และยกเลิกได้ทุกเมื่อ"
              en="The token is stored encrypted, never shown again, used only for what you click, and can be removed any time." />
    </p>
</form>
