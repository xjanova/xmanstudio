{{--
    Photo gallery of the studio — building, workspaces and the team at work.

    The grid is plain <img> markup, so every photo is still visible and still
    has its caption when Alpine fails to load. Only the click-to-enlarge
    lightbox depends on JS, and that is an enhancement, never the only way to
    see a picture.

    Two files per shot: `<name>.webp` feeds the grid (640px square or 800x450
    wide, lazy-loaded — ~876KB if every one of the 17 ends up on screen) and
    `<name>-full.webp` is fetched only when someone actually opens the lightbox.
--}}
@php
    // `wide` = ภาพ 16:9 กินสองคอลัมน์ ที่เหลือเป็นจัตุรัส ที่ต้องมีสองแบบเพราะ
    // ภาพชุดหลังเจนออกมาเป็น 16:9 การครอบให้เป็นจัตุรัสจะตัดคนที่ยืนอยู่ริมเฟรม
    // ออกไป (เช่นใบประชุมลูกค้าที่คนนั่งกระจายเต็มโต๊ะ)
    $studioShots = [
        ['f' => 'building', 'wide' => false, 'th' => 'ที่ทำงานของเรา',      'en' => 'The studio',       'desc' => 'อาคารสำนักงานย่านกรุงเทพฯ ที่ทีมงานทั้งหมดทำงานอยู่'],
        ['f' => 'lobby',    'wide' => false, 'th' => 'ล็อบบี้ต้อนรับ',       'en' => 'Reception',        'desc' => 'จุดแรกที่ลูกค้าเดินเข้ามาคุยงานกับเรา'],
        ['f' => 'floor',    'wide' => false, 'th' => 'ฟลอร์ทำงานหลัก',      'en' => 'The main floor',   'desc' => 'โต๊ะทำงานเรียงยาว จอคู่ทุกที่นั่ง และวิวเมืองนอกหน้าต่าง'],
        ['f' => 'window',   'wide' => true,  'th' => 'มุมสงบริมหน้าต่าง',    'en' => 'Quiet corner',     'desc' => 'งานที่ต้องคิดยาวๆ หลายคนย้ายมานั่งตรงนี้'],
        ['f' => 'pair',     'wide' => false, 'th' => 'จับคู่เขียนโค้ด',       'en' => 'Pair programming', 'desc' => 'งานยากๆ เรานั่งดูจอเดียวกัน แก้ไปด้วยกัน เร็วกว่าต่างคนต่างทำ'],
        ['f' => 'desk',     'wide' => true,  'th' => 'โต๊ะทำงานจริง',        'en' => 'At the keyboard',  'desc' => 'ที่ที่งานส่วนใหญ่เกิดขึ้นจริง ไม่ใช่ในห้องประชุม'],
        ['f' => 'designer', 'wide' => false, 'th' => 'ออกแบบหน้าจอ',        'en' => 'Design',           'desc' => 'วาง wireframe และชุดสีให้จบก่อน แล้วค่อยส่งต่อให้ทีมพัฒนา'],
        ['f' => 'qa',       'wide' => false, 'th' => 'ทดสอบก่อนส่ง',         'en' => 'QA',               'desc' => 'ไล่เทสต์ทีละเครื่องทีละรุ่น ก่อนปล่อยให้ผู้ใช้จริงเจอ'],
        ['f' => 'mobile',   'wide' => true,  'th' => 'เทสต์บนเครื่องจริง',    'en' => 'Device testing',   'desc' => 'ต่อสายเทสต์บนมือถือจริง ไม่เชื่อแค่ emulator'],
        ['f' => 'standup',  'wide' => false, 'th' => 'ประชุมเช้า',           'en' => 'Morning stand-up', 'desc' => 'ทุกเช้าคุยกันสั้นๆ ว่าใครติดอะไร จะได้ไม่มีใครค้างอยู่คนเดียว'],
        ['f' => 'workshop', 'wide' => true,  'th' => 'อบรมภายใน',           'en' => 'Workshop',         'desc' => 'แบ่งความรู้กันในทีมสม่ำเสมอ ของใหม่มาเร็ว ต้องตามให้ทัน'],
        ['f' => 'pantry',   'wide' => true,  'th' => 'พักกินกาแฟ',           'en' => 'Coffee break',     'desc' => 'ไอเดียดีๆ หลายอันเกิดตรงนี้มากกว่าในห้องประชุม'],
        ['f' => 'client',   'wide' => true,  'th' => 'คุยงานกับลูกค้า',       'en' => 'Client review',    'desc' => 'นำเสนอความคืบหน้าเป็นระยะ ไม่ใช่หายไปแล้วโผล่ตอนจบ'],
        ['f' => 'call',     'wide' => true,  'th' => 'ประชุมออนไลน์',        'en' => 'Remote call',      'desc' => 'ลูกค้าต่างจังหวัดหรือต่างประเทศก็คุยกันได้ทุกสัปดาห์'],
        ['f' => 'handover', 'wide' => true,  'th' => 'ส่งมอบงาน',            'en' => 'Handover',         'desc' => 'จบโปรเจคแล้วยังดูแลต่อ ไม่ใช่ส่งของเสร็จแล้วหายไป'],
        ['f' => 'server',   'wide' => false, 'th' => 'ห้องเซิร์ฟเวอร์',       'en' => 'Server room',      'desc' => 'เครื่องและเครือข่ายที่เราดูแลเอง ไม่ได้ฝากใครทั้งหมด'],
        ['f' => 'tech',     'wide' => true,  'th' => 'ดูแลระบบหลังบ้าน',     'en' => 'Operations',       'desc' => 'เดินสาย ตั้งเครื่อง และเฝ้าระบบเองทั้งหมด'],
    ];

    // Cloudflare holds these for 4 hours, so swapping a photo for a different
    // one under the same filename left the old picture live long after the
    // deploy finished — that is exactly what happened when the daytime shot of
    // the building was replaced with the night one. mtime only moves when the
    // file actually changes, so unchanged photos stay cached as before.
    $studioShots = array_map(function ($shot) {
        foreach (['thumb' => '', 'full' => '-full'] as $key => $suffix) {
            $file = 'artwork/studio/' . $shot['f'] . $suffix . '.webp';
            $path = public_path($file);
            $shot[$key] = asset($file) . (is_file($path) ? '?v=' . filemtime($path) : '');
        }

        return $shot;
    }, $studioShots);
@endphp

<section class="relative overflow-hidden py-20 bg-white dark:bg-gray-800">
    <x-page-art art="team-journey" :opacity="10" :scrim="false" />

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{
             open: false,
             i: 0,
             shots: @js($studioShots),
             show(n) { this.i = n; this.open = true },
             next()  { this.i = (this.i + 1) % this.shots.length },
             prev()  { this.i = (this.i - 1 + this.shots.length) % this.shots.length },
         }"
         @keydown.escape.window="open = false"
         @keydown.arrow-right.window="open && next()"
         @keydown.arrow-left.window="open && prev()">

        <div class="text-center mb-14 tm-reveal">
            <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold uppercase tracking-wider rounded-full mb-4">Inside XMAN Studio</span>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">พาชมที่ทำงานของเรา</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                ตั้งแต่หน้าตึก โต๊ะทำงาน ห้องประชุม ไปจนถึงห้องเซิร์ฟเวอร์ — กดที่รูปเพื่อดูขนาดเต็ม
                <span class="block text-base text-gray-500 dark:text-gray-400 mt-1">Tap any photo to view it larger</span>
            </p>
        </div>

        {{-- ตึกกินพื้นที่ 2x2 เป็นใบเด่น ภาพ 16:9 กินสองคอลัมน์ ที่เหลือจัตุรัส
             ใช้ grid-flow-dense ให้เบราว์เซอร์ถมช่องว่างที่เหลือเอง --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 grid-flow-row-dense">
            @foreach($studioShots as $shot)
                <button type="button"
                        @click="show({{ $loop->index }})"
                        class="group relative block overflow-hidden rounded-2xl ring-1 ring-gray-200 dark:ring-white/10 text-left
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 tm-reveal
                               {{ $loop->first ? 'col-span-2 row-span-2' : ($shot['wide'] ? 'col-span-2' : '') }}"
                        style="transition-delay: {{ min($loop->index, 6) * 0.07 }}s;"
                        aria-label="ดูรูป {{ $shot['th'] }} ขนาดเต็ม">
                    <img src="{{ $shot['thumb'] }}"
                         alt="{{ $shot['th'] }} — {{ $shot['en'] }}"
                         width="{{ $shot['wide'] ? 800 : 640 }}" height="{{ $shot['wide'] ? 450 : 640 }}"
                         loading="lazy" decoding="async"
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105
                                {{ $shot['wide'] ? 'aspect-video' : 'aspect-square' }}">
                    <span class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></span>
                    <span class="absolute bottom-0 left-0 right-0 p-3 sm:p-4">
                        <span class="block font-bold text-white {{ $loop->first ? 'text-lg sm:text-xl' : 'text-sm sm:text-base' }}">{{ $shot['th'] }}</span>
                        <span class="block text-xs sm:text-sm text-gray-300">{{ $shot['en'] }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Lightbox --}}
        <div x-cloak x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/90 backdrop-blur-sm p-4"
             role="dialog" aria-modal="true" @click.self="open = false">

            <button type="button" @click="open = false"
                    class="absolute top-4 right-4 p-2 text-gray-300 hover:text-white rounded-lg hover:bg-white/10"
                    aria-label="ปิด">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <button type="button" @click="prev()"
                    class="absolute left-2 sm:left-6 p-2 text-gray-300 hover:text-white rounded-lg hover:bg-white/10"
                    aria-label="รูปก่อนหน้า">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button type="button" @click="next()"
                    class="absolute right-2 sm:right-6 p-2 text-gray-300 hover:text-white rounded-lg hover:bg-white/10"
                    aria-label="รูปถัดไป">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <figure class="max-w-4xl w-full">
                {{-- ผูก src กับ i ตรงๆ ภาพใหญ่จึงถูกโหลดตอนเปิดเท่านั้น --}}
                <img :src="shots[i].full"
                     :alt="shots[i].th + ' — ' + shots[i].en"
                     class="w-full rounded-2xl shadow-2xl">
                <figcaption class="mt-4 text-center">
                    <span class="block text-lg font-bold text-white" x-text="shots[i].th"></span>
                    <span class="block text-sm text-gray-400 mb-1" x-text="shots[i].en"></span>
                    <span class="block text-sm text-gray-300 max-w-xl mx-auto" x-text="shots[i].desc"></span>
                    <span class="block text-xs text-gray-500 mt-3" x-text="(i + 1) + ' / ' + shots.length"></span>
                </figcaption>
            </figure>
        </div>
    </div>
</section>
