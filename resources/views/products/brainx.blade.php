@extends($publicLayout ?? 'layouts.app')

@section('title', 'BrainX Cloud — สมองที่สองบนคลาวด์ ฿' . \App\Support\LicensePlans::price('brainx', 'monthly') . '/เดือน | XMAN Studio')
@section('meta_description', 'BrainX Cloud: อัปโหลดโน้ตที่คุณเลือกขึ้นพื้นที่ส่วนตัว แล้วให้ Claude ใช้ได้ทุกที่ผ่าน Remote MCP ฿' . \App\Support\LicensePlans::price('brainx', 'monthly') . ' ต่อเดือน · Your second brain in the cloud for any Claude, ' . \App\Support\LicensePlans::price('brainx', 'monthly') . ' THB a month.')

@section('content')
@php
    // ราคาเดียวกับที่ตะกร้าและ pricing API ใช้ — แก้ราคาที่ config/licenses.php 'plans' ที่เดียว
    $monthlyPrice = \App\Support\LicensePlans::price('brainx', 'monthly');

    // คีย์ที่การซื้อครั้งนี้จะต่ออายุให้ (null = ยังไม่มี / ยังไม่ล็อกอิน / คีย์ทุกใบถูกยกเลิก → ได้คีย์ใหม่)
    $key = $renewalKey ?? null;
    $keyLapsed = $key && $key->isExpired();
    $maskedKey = $key ? substr($key->license_key, 0, 4) . '-••••-••••-' . substr($key->license_key, -4) : null;
    $mcpCommand = 'claude mcp add --transport http brainx https://serverbrain.xman4289.com/mcp --header "Authorization: Bearer bxc_..."';
@endphp
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-violet-900 to-gray-900">

    {{-- ============================ HERO ============================ --}}
    <section class="relative py-20 overflow-hidden">
        <x-page-art art="card-brainx" :opacity="30" />
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-violet-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-violet-300 hover:text-violet-200 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-violet-500/20 rounded-full text-violet-200 text-sm mb-6 backdrop-blur-sm border border-violet-500/30">
                        <span class="relative flex h-2.5 w-2.5 mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-violet-400"></span>
                        </span>
                        BrainX Cloud · Remote MCP
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        BrainX <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-fuchsia-400">Cloud</span>
                    </h1>

                    <p class="text-xl text-gray-200 mb-2">
                        สมองที่สองของคุณบนคลาวด์ — เลือกโฟลเดอร์โน้ตที่จะอัปโหลด แล้วให้ Claude ทุกเครื่องใช้ความรู้ก้อนเดียวกันได้ทุกที่
                    </p>
                    <p class="text-base text-gray-400 mb-8">
                        Your second brain in the cloud. Pick the note folders to upload, and every Claude you use can reach the same knowledge, anywhere.
                    </p>

                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-violet-500/20 text-violet-200 rounded-full text-sm border border-violet-500/30">Remote MCP</span>
                        <span class="px-3 py-1 bg-purple-500/20 text-purple-200 rounded-full text-sm border border-purple-500/30"><x-bi th="พื้นที่ 1 GB" en="1 GB space" /></span>
                        <span class="px-3 py-1 bg-violet-500/20 text-violet-200 rounded-full text-sm border border-violet-500/30"><x-bi th="ใช้ออฟไลน์ได้" en="Works offline" /></span>
                        <span class="px-3 py-1 bg-purple-500/20 text-purple-200 rounded-full text-sm border border-purple-500/30"><x-bi th="คีย์เดียวตลอดไป" en="One key for good" /></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-6">
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-white">฿{{ $monthlyPrice }}</span>
                                <span class="text-gray-300 text-sm"><x-bi th="/ เดือน" en="month" /></span>
                            </div>
                            <p class="text-violet-200/80 text-sm mt-1"><x-bi th="ต่ออายุแล้วใช้คีย์เดิม" en="Renewing keeps the same key" /></p>
                        </div>

                        @include('products.partials.brainx-buy-button', ['key' => $key, 'monthlyPrice' => $monthlyPrice])
                    </div>

                    @if($key)
                        <div class="mt-8 max-w-md bg-gray-900/60 rounded-2xl p-5 border {{ $keyLapsed ? 'border-amber-500/50' : 'border-violet-500/30' }} backdrop-blur-sm">
                            <p class="text-sm text-gray-400 mb-1"><x-bi th="คีย์ของคุณ" en="Your key" /></p>
                            <p class="font-mono text-lg text-white tracking-wider">{{ $maskedKey }}</p>
                            <p class="text-sm mt-2 {{ $keyLapsed ? 'text-amber-300' : 'text-emerald-300' }}">
                                @if(! $key->expires_at)
                                    <x-bi th="ยังไม่มีวันหมดอายุ" en="No expiry set" />
                                @elseif($keyLapsed)
                                    <x-bi th="หมดอายุเมื่อ" en="Expired on" /> {{ $key->expires_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                                    · <x-bi th="ต่ออายุแล้วได้ 30 วันนับจากวันที่ชำระ" en="renewing gives 30 days from the day you pay" />
                                @else
                                    <x-bi th="ใช้ได้ถึง" en="Valid until" /> {{ $key->expires_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                                    · <x-bi th="ต่ออายุแล้วบวกต่อจากวันนี้" en="renewing adds on from this date" />
                                @endif
                            </p>
                            <a href="{{ route('customer.licenses.show', $key) }}" class="inline-block mt-3 text-sm font-semibold text-violet-300 hover:text-violet-200">
                                <x-bi th="ดูคีย์เต็มและรายละเอียด" en="Full key and details" /> →
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Right: connect in one command --}}
                <div class="relative">
                    <div class="bg-gradient-to-br from-violet-500/20 to-purple-500/20 rounded-2xl p-3 sm:p-4 backdrop-blur-sm border border-violet-500/30 shadow-2xl">
                        <div class="rounded-xl overflow-hidden bg-gray-950 border border-white/10">
                            <div class="flex items-center gap-1.5 px-3 py-2 bg-gray-900/80 border-b border-white/5">
                                <span class="w-3 h-3 rounded-full bg-red-400/80"></span>
                                <span class="w-3 h-3 rounded-full bg-yellow-400/80"></span>
                                <span class="w-3 h-3 rounded-full bg-green-400/80"></span>
                                <span class="ml-3 text-xs text-gray-400"><x-bi th="ต่อ Claude ในคำสั่งเดียว" en="Connect Claude in one command" /></span>
                            </div>
                            <div class="p-5">
                                <p class="text-xs text-gray-500 mb-2">$ Claude Code</p>
                                <pre class="text-sm text-emerald-300 whitespace-pre-wrap break-all font-mono leading-relaxed">{{ $mcpCommand }}</pre>
                                <p class="text-xs text-gray-400 mt-4">
                                    <x-bi th="สร้างโทเค็น bxc_ ในแอป BrainX หลังล็อกอินด้วย License key" en="Create a bxc_ token in the BrainX app after signing in with your licence key" />
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ WHAT YOU GET ============================ --}}
    <section class="py-20 border-y border-white/5 bg-gray-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-3"><x-bi th="สิ่งที่คุณได้" en="What you get" layout="stack" /></h2>
            </div>

            @php
                $features = [
                    ['th' => 'Remote MCP ไม่ต้องติดตั้ง', 'en' => 'Remote MCP, nothing to install',
                     'dth' => 'Claude Code ทุกเครื่องต่อเข้าสมองบนคลาวด์ของคุณได้ด้วยคำสั่งเดียว',
                     'den' => 'Any Claude Code connects to your cloud brain with one command.'],
                    ['th' => 'คุณเลือกเองว่าอะไรขึ้นคลาวด์', 'en' => 'You choose what goes up',
                     'dth' => 'เลือกเป็นโฟลเดอร์ เฉพาะโน้ต .md ในโฟลเดอร์ที่คุณเลือกเท่านั้นที่ถูกอัปโหลด',
                     'den' => 'Pick folders. Only the .md notes in the folders you choose are uploaded.'],
                    ['th' => 'พื้นที่ของคุณคนเดียว', 'en' => 'A space that is yours alone',
                     'dth' => 'แต่ละบัญชีมีพื้นที่แยก 1 GB เข้าถึงได้เฉพาะด้วยโทเค็นของคุณ',
                     'den' => 'Each account gets its own 1 GB space, reachable only with your tokens.'],
                    ['th' => 'ใช้ออฟไลน์ได้', 'en' => 'Keeps working offline',
                     'dth' => 'brainx-mcp โหมดคลาวด์ดึงโน้ตมาเก็บไว้ในเครื่อง เน็ตหลุดก็ใช้ต่อได้ แล้วส่งสิ่งที่เขียนกลับขึ้นไปให้',
                     'den' => 'brainx-mcp in cloud mode caches your notes locally, works through a dropped connection and pushes your writes back.'],
                    ['th' => 'สมองหลักยังอยู่ที่เครื่องคุณ', 'en' => 'Your machine stays the master',
                     'dth' => 'คลาวด์เก็บเฉพาะสิ่งที่คุณอัปโหลด โน้ตทั้งหมดยังอยู่บนเครื่องของคุณเหมือนเดิม',
                     'den' => 'The cloud holds only what you upload. Your whole vault stays on your own machine.'],
                    ['th' => 'คีย์เดียวตลอดไป', 'en' => 'One key for good',
                     'dth' => 'ต่ออายุแล้วคีย์เดิมถูกยืดออกไป บัญชีคลาวด์และการตั้งค่าไม่ต้องทำใหม่',
                     'den' => 'Renewing extends the key you already have, so your cloud account and settings stay as they are.'],
                ];
            @endphp

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($features as $feature)
                    <div class="bg-gray-800/50 rounded-2xl p-6 border border-gray-700 hover:border-violet-500/50 transition-colors">
                        <div class="w-11 h-11 bg-violet-500/20 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-white font-bold mb-1">{{ $feature['th'] }}</h3>
                        <p class="text-violet-200/70 text-sm mb-3">{{ $feature['en'] }}</p>
                        <p class="text-gray-300 text-sm">{{ $feature['dth'] }}</p>
                        <p class="text-gray-500 text-sm mt-1">{{ $feature['den'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ HOW IT WORKS ============================ --}}
    <section class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-3"><x-bi th="เริ่มใช้งานใน 4 ขั้น" en="Up and running in four steps" layout="stack" /></h2>
            </div>

            @php
                $steps = [
                    ['th' => 'สมัครที่หน้านี้', 'en' => 'Subscribe here',
                     'dth' => 'ชำระ ฿' . $monthlyPrice . ' แล้วรับ License key ทางอีเมลและในหน้าบัญชีของคุณ',
                     'den' => 'Pay ฿' . $monthlyPrice . '; your licence key arrives by e-mail and in My Account.'],
                    ['th' => 'ใส่คีย์ในแอป BrainX', 'en' => 'Enter the key in BrainX',
                     'dth' => 'แล้วเลือกโฟลเดอร์ที่จะอัปโหลดขึ้นพื้นที่ของคุณ',
                     'den' => 'Then choose the folders to upload to your space.'],
                    ['th' => 'ต่อ Claude', 'en' => 'Connect Claude',
                     'dth' => 'ผ่าน Remote MCP ได้ทันที หรือใช้ brainx-mcp โหมดคลาวด์บนเครื่อง',
                     'den' => 'Over Remote MCP straight away, or with brainx-mcp in cloud mode on your machine.'],
                    ['th' => 'ต่ออายุก่อนหมด', 'en' => 'Renew before it runs out',
                     'dth' => 'ซื้อซ้ำที่หน้านี้ เวลาจะบวกต่อจากวันหมดอายุเดิมของคีย์เดิม',
                     'den' => 'Buy again here; the time adds on from your key\'s current expiry.'],
                ];
            @endphp

            <ol class="grid md:grid-cols-2 gap-6">
                @foreach($steps as $i => $step)
                    <li class="flex items-start gap-4 bg-gray-800/40 rounded-2xl p-6 border border-gray-700/60">
                        <span class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-white font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="text-white font-bold">{{ $step['th'] }} <span class="text-violet-200/70 font-normal text-sm">/ {{ $step['en'] }}</span></h3>
                            <p class="text-gray-300 text-sm mt-1">{{ $step['dth'] }}</p>
                            <p class="text-gray-500 text-sm mt-1">{{ $step['den'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============================ FAQ ============================ --}}
    <section class="py-20 bg-gray-900/40 border-y border-white/5">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-white"><x-bi th="คำถามที่พบบ่อย" en="Questions" layout="stack" /></h2>
            </div>

            @php
                $faqs = [
                    ['qth' => 'ต่ออายุแล้วได้คีย์ใหม่ไหม?', 'qen' => 'Do I get a new key when I renew?',
                     'ath' => 'ไม่ได้ และไม่ต้อง — คีย์เดิมของคุณถูกยืดออกไป 30 วันต่อหนึ่งเดือนที่ซื้อ นับจากวันหมดอายุเดิม หรือนับจากวันที่ชำระถ้าหมดอายุไปแล้ว',
                     'aen' => 'No, and you do not need one. Your existing key is extended by 30 days for each month bought, counted from its current expiry, or from the day you pay if it has already lapsed.'],
                    ['qth' => 'ซื้อหลายเดือนทีเดียวได้ไหม?', 'qen' => 'Can I buy several months at once?',
                     'ath' => 'ได้ ปรับจำนวนในตะกร้า จำนวนคือจำนวนเดือนที่ต่อให้คีย์เดียวกัน',
                     'aen' => 'Yes. Set the quantity in the cart; the quantity is the number of months added to the same key.'],
                    ['qth' => 'ถ้าหมดอายุ โน้ตบนคลาวด์เป็นอย่างไร?', 'qen' => 'What happens to my cloud notes if it lapses?',
                     'ath' => 'อัปโหลดและการใช้ผ่าน MCP จะหยุดจนกว่าจะต่ออายุ แต่คุณยังดาวน์โหลดโน้ตของตัวเองกลับได้',
                     'aen' => 'Uploads and MCP access pause until you renew, but you can still download your own notes.'],
                    ['qth' => 'ใช้ได้กี่เครื่อง?', 'qen' => 'How many machines can use it?',
                     'ath' => 'หลายเครื่อง — สร้างโทเค็นแยกให้แต่ละเครื่องได้ในแอป BrainX และยกเลิกทีละใบได้',
                     'aen' => 'Several. Create a separate token for each machine in the BrainX app, and revoke any one of them on its own.'],
                    ['qth' => 'ชำระเงินช่องทางไหนได้บ้าง?', 'qen' => 'How can I pay?',
                     'ath' => 'ทุกช่องทางที่หน้าชำระเงินของ XMAN Studio รองรับ',
                     'aen' => 'Any method the XMAN Studio checkout supports.'],
                ];
            @endphp

            <div class="space-y-4">
                @foreach($faqs as $faq)
                    <details class="group bg-gray-800/50 rounded-xl border border-gray-700 p-5">
                        <summary class="cursor-pointer list-none flex items-start justify-between gap-4">
                            <span>
                                <span class="block text-white font-semibold">{{ $faq['qth'] }}</span>
                                <span class="block text-violet-200/70 text-sm">{{ $faq['qen'] }}</span>
                            </span>
                            <span class="text-violet-300 group-open:rotate-45 transition-transform text-xl leading-none">+</span>
                        </summary>
                        <p class="text-gray-300 text-sm mt-4">{{ $faq['ath'] }}</p>
                        <p class="text-gray-500 text-sm mt-1">{{ $faq['aen'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================ FINAL CTA ============================ --}}
    <section class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600/20 via-purple-600/10 to-gray-900 border border-violet-500/30 p-10 md:p-14 text-center">
                <div class="absolute -top-16 -right-16 w-64 h-64 bg-violet-500/20 rounded-full blur-3xl"></div>
                <div class="relative">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-2">
                        {{ $key ? 'ต่ออายุ BrainX Cloud' : 'ให้ Claude ทุกเครื่องใช้สมองเดียวกัน' }}
                    </h2>
                    <p class="text-violet-200/80 mb-8">
                        {{ $key ? 'Renew BrainX Cloud — the same key, 30 more days.' : 'Give every Claude you use the same brain.' }}
                    </p>

                    <div class="flex flex-wrap justify-center gap-4">
                        @include('products.partials.brainx-buy-button', ['key' => $key, 'monthlyPrice' => $monthlyPrice])
                        <a href="{{ rtrim(config('app.product_site_url'), '/') }}/brainx.html" target="_blank" rel="noopener"
                           class="inline-flex items-center px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            <x-bi th="เกี่ยวกับแอป BrainX" en="About the BrainX app" />
                        </a>
                    </div>

                    <p class="text-gray-400 text-sm mt-6">
                        <x-bi th="ต้องล็อกอินตอนชำระเงิน คีย์จะผูกกับบัญชีของคุณ" en="You sign in at checkout; the key is tied to your account" />
                    </p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
