{{-- ══════════ ระบบ: รหัสผ่าน · ชื่อโฮสต์ · แผงควบคุม · กู้ระบบ · ติดตั้งใหม่ ══════════ --}}
<div class="grid lg:grid-cols-2 gap-6">
    {{-- ไม่เติมค่ารหัสผ่านเดิมกลับเข้าช่องเด็ดขาด แม้ validation ไม่ผ่าน --}}
    <div id="root-password" class="{{ $card }} p-6 scroll-mt-24" x-data="vpsPassword()">
        <h2 class="{{ $h2 }}"><x-bi th="ตั้งรหัสผ่าน root" en="Root password" /></h2>
        <p class="{{ $lead }}">
            <x-bi th="ลืมรหัสหรืออยากเปลี่ยน ตั้งใหม่ได้ทันทีโดยไม่ต้องติดตั้งเครื่องใหม่ — เราไม่เก็บรหัสผ่านของคุณ จึงแสดงให้ดูซ้ำไม่ได้"
                  en="Forgot it or want a new one? Set it here without reinstalling. We never keep your password, so we can't show it again." />
        </p>

        <form method="POST" action="{{ route('customer.vps.password', $server->id) }}" class="mt-4 space-y-3" data-once>
            @csrf
            <input type="hidden" name="section" value="password">
            <div>
                <label for="root_password" class="{{ $label }}"><x-bi th="รหัสผ่านใหม่" en="New password" /></label>
                <div class="flex gap-2">
                    <input type="password" :type="show ? 'text' : 'password'" id="root_password" name="root_password" x-ref="pw"
                           required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                           class="{{ $field }} font-mono">
                    <button type="button" @click="show = !show" class="{{ $btnGhost }} shrink-0">
                        <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                        <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                    </button>
                </div>
            </div>
            <div>
                <label for="root_password_confirmation" class="{{ $label }}"><x-bi th="ยืนยันรหัสผ่าน" en="Confirm password" /></label>
                <input type="password" :type="show ? 'text' : 'password'" id="root_password_confirmation" name="root_password_confirmation" x-ref="confirm"
                       required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                       class="{{ $field }} font-mono">
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <x-bi th="อย่างน้อย 12 ตัว มีทั้งตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข · ห้ามใช้รหัสที่เคยหลุดสู่สาธารณะ"
                      en="At least 12 characters with upper case, lower case and a number — and not one that has leaked before." />
            </p>
            <p x-show="generated" x-cloak class="rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                <x-bi th="คัดลอกหรือจดรหัสนี้ไว้ก่อนกดบันทึก — หลังจากนี้เราแสดงให้ดูอีกไม่ได้"
                      en="Copy or write it down before you save — we can't show it to you again afterwards." />
            </p>
            <div class="flex flex-wrap gap-2">
                <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}">
                    <x-bi th="สุ่มรหัสที่ปลอดภัย" en="Generate one" />
                </button>
                <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                    <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                    <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                </button>
                <button type="submit" class="{{ $btnPrimary }}"><x-bi th="บันทึกรหัสผ่าน" en="Save password" /></button>
            </div>
        </form>
    </div>

    <div class="space-y-6">
        <div class="{{ $card }} p-6">
            <h2 class="{{ $h2 }}"><x-bi th="ชื่อโฮสต์" en="Hostname" /></h2>
            <p class="{{ $lead }}">
                <x-bi th="ชื่อของเครื่อง เช่น server.example.com — เป็นแค่ป้ายชื่อ ไม่ได้ทำให้โดเมนชี้มาที่เครื่องนี้"
                      en="The machine's name, e.g. server.example.com. It's only a label — it doesn't point a domain here." />
            </p>
            <form method="POST" action="{{ route('customer.vps.hostname', $server->id) }}" class="mt-4 flex flex-col sm:flex-row gap-2" data-once>
                @csrf
                <input type="hidden" name="section" value="hostname">
                <label for="hostname" class="sr-only">ชื่อโฮสต์ / Hostname</label>
                <input type="text" id="hostname" name="hostname" value="{{ old('hostname', $server->hostname) }}"
                       required maxlength="253" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="url"
                       class="{{ $field }} font-mono">
                <button type="submit" class="{{ $btnDark }} shrink-0"><x-bi th="บันทึก" en="Save" /></button>
            </form>
        </div>

        @if($panel)
            {{-- รหัสผ่านของแผงควบคุมที่มากับ template (CloudPanel, CyberPanel …) — คนละตัวกับรหัส root --}}
            <div id="panel-password" class="{{ $card }} p-6 scroll-mt-24" x-data="vpsPassword()">
                <h2 class="{{ $h2 }}"><x-bi :th="'รหัสผ่านแผงควบคุม ' . $panel['name']" :en="$panel['name'] . ' password'" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="ลืมรหัสเข้าแผงควบคุม ตั้งใหม่ได้ที่นี่ — คนละตัวกับรหัสผ่าน root"
                          en="Forgot the control panel login? Set a new one here — it's separate from the root password." />
                </p>
                <form method="POST" action="{{ route('customer.vps.panel-password', $server->id) }}" class="mt-4 space-y-3" data-once>
                    @csrf
                    <div class="flex gap-2">
                        <label for="panel_password" class="sr-only">รหัสผ่านใหม่ / New password</label>
                        <input type="password" :type="show ? 'text' : 'password'" id="panel_password" name="panel_password" x-ref="pw"
                               required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                               placeholder="รหัสผ่านใหม่ / New password" class="{{ $field }} font-mono">
                        <button type="button" @click="show = !show" class="{{ $btnGhost }} shrink-0">
                            <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                            <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                        </button>
                    </div>
                    <label for="panel_password_confirmation" class="sr-only">ยืนยันรหัสผ่าน / Confirm password</label>
                    <input type="password" :type="show ? 'text' : 'password'" id="panel_password_confirmation" name="panel_password_confirmation" x-ref="confirm"
                           required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                           placeholder="ยืนยันรหัสผ่าน / Confirm" class="{{ $field }} font-mono">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}"><x-bi th="สุ่มรหัสที่ปลอดภัย" en="Generate one" /></button>
                        <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                            <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                            <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                        </button>
                        <button type="submit" class="{{ $btnDark }}"><x-bi th="บันทึก" en="Save" /></button>
                    </div>
                    @if($panel['url'])
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <x-bi th="หน้าเข้าแผงควบคุม" en="Panel login" />:
                            <a href="{{ $panel['url'] }}" target="_blank" rel="noopener noreferrer" class="font-mono text-indigo-600 dark:text-indigo-400 hover:underline break-all">{{ $panel['url'] }}</a>
                        </p>
                    @endif
                </form>
            </div>
        @endif
    </div>
</div>

{{-- ══════════ โหมดกู้ระบบ ══════════ --}}
<div id="recovery" class="{{ $card }} p-6 scroll-mt-24" x-data="vpsPassword()">
    <div class="flex flex-wrap items-center gap-2">
        <h2 class="{{ $h2 }}"><x-bi th="โหมดกู้ระบบ" en="Recovery mode" /></h2>
        @if($inRecovery)
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-500/20 text-violet-800 dark:text-violet-300"><x-bi th="เปิดอยู่" en="Active" /></span>
        @endif
    </div>
    <p class="{{ $lead }} max-w-3xl">
        <x-bi th="บูตเครื่องด้วยระบบกู้ภัยชั่วคราว ดิสก์เดิมถูกต่อไว้ที่ /mnt — ใช้เมื่อเครื่องบูตไม่ขึ้น แก้ไฟล์ตั้งค่าที่ทำพัง หรือดึงข้อมูลออกก่อนติดตั้งใหม่"
              en="Boots a temporary rescue system with your disk mounted at /mnt — for a server that won't boot, a config you broke, or pulling data off before a reinstall." />
    </p>

    @if($inRecovery)
        <div class="mt-4 rounded-lg bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/30 px-4 py-3 text-sm text-violet-900 dark:text-violet-200">
            <p class="font-semibold"><x-bi th="เครื่องอยู่ในโหมดกู้ระบบ" en="The server is in recovery mode" /></p>
            <p class="mt-1">
                <x-bi :th="'SSH เข้า root@' . ($server->ipv4 ?: 'IP ของเครื่อง') . ' ด้วยรหัสชั่วคราวที่ตั้งไว้ · ไฟล์ของเครื่องอยู่ที่ /mnt · เว็บและบริการปกติหยุดอยู่จนกว่าจะออกจากโหมดนี้'"
                      :en="'SSH to root@' . ($server->ipv4 ?: 'the server') . ' with the temporary password · your files are under /mnt · normal services are down until you leave this mode'" />
            </p>
        </div>
        <form method="POST" action="{{ route('customer.vps.recovery', $server->id) }}" class="mt-4" data-once>
            @csrf
            <input type="hidden" name="action" value="stop">
            <button type="submit" class="{{ $btnPrimary }}"><x-bi th="ออกจากโหมดกู้ระบบ และบูตระบบปกติ" en="Leave recovery and boot normally" /></button>
        </form>
    @else
        <form method="POST" action="{{ route('customer.vps.recovery', $server->id) }}" class="mt-4 space-y-3" data-once
              onsubmit="return confirm(@js($confirm['recovery']))">
            @csrf
            <input type="hidden" name="action" value="start">
            <div class="grid sm:grid-cols-2 gap-2">
                <label class="block">
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><x-bi th="รหัส root ชั่วคราว" en="Temporary root password" /></span>
                    <input type="password" :type="show ? 'text' : 'password'" name="recovery_password" x-ref="pw"
                           required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false" class="{{ $field }} font-mono">
                </label>
                <label class="block">
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><x-bi th="ยืนยันรหัส" en="Confirm" /></span>
                    <input type="password" :type="show ? 'text' : 'password'" name="recovery_password_confirmation" x-ref="confirm"
                           required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false" class="{{ $field }} font-mono">
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}"><x-bi th="สุ่มรหัส" en="Generate" /></button>
                <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                    <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                    <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                </button>
                <button type="button" @click="show = !show" class="{{ $btnGhost }}">
                    <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                    <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                </button>
                <button type="submit" class="{{ $btnDark }}" @disabled($busy)><x-bi th="เข้าโหมดกู้ระบบ" en="Start recovery mode" /></button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <x-bi th="รหัสนี้ใช้เฉพาะระบบกู้ภัย ไม่เปลี่ยนรหัส root ของเครื่องจริง · อย่างน้อย 12 ตัว มีพิมพ์ใหญ่ พิมพ์เล็ก และตัวเลข"
                      en="Only for the rescue system — your real root password doesn't change. 12+ characters with upper, lower and a number." />
            </p>
        </form>
    @endif
</div>

{{-- ══════════ DANGER ZONE — ติดตั้ง OS ใหม่ ══════════
     ปุ่มที่ทำลายที่สุดในหน้า: พับไว้ก่อน และต้องพิมพ์ชื่อโฮสต์ยืนยัน
     เพราะ confirm() เฉยๆ คนกดผ่านโดยไม่อ่าน (controller ตรวจซ้ำอีกชั้น)
     ห้ามใช้ bg-white ที่กล่องนี้: customer-premium เขียน .bg-white ทับ
     border เป็นเส้นม่วงบาง 1px (สไตล์นอก layer ชนะ utility เสมอ) ขอบแดงจะหายไป --}}
<div class="rounded-2xl border-2 border-red-300 dark:border-red-500/40 bg-red-50/40 dark:bg-slate-800 p-6"
     x-data="vpsReinstall(@js(['hostname' => $server->hostname, 'open' => $failedSection === 'reinstall', 'busy' => $busy, 'confirmText' => $confirm['reinstall']]))">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Danger zone</p>
            <h2 class="{{ $h2 }} mt-1"><x-bi th="ติดตั้งระบบปฏิบัติการใหม่" en="Reinstall the operating system" /></h2>
            <p class="{{ $lead }}">
                <x-bi th="ล้างดิสก์ทั้งหมดแล้วติดตั้งใหม่ — ใช้เมื่อเครื่องพังจนแก้ไม่ได้ หรืออยากเปลี่ยนระบบปฏิบัติการ"
                      en="Wipe the disk and start again — for a server broken beyond repair, or to switch operating system." />
            </p>
        </div>
        <button type="button" @click="open = !open" class="{{ $btnGhostDanger }} shrink-0" aria-controls="reinstall-panel" :aria-expanded="open ? 'true' : 'false'">
            <span x-show="!open"><x-bi th="เปิด" en="Show" /></span>
            <span x-show="open" x-cloak><x-bi th="ปิด" en="Hide" /></span>
        </button>
    </div>

    <div id="reinstall-panel" x-show="open" x-cloak class="mt-5 pt-5 border-t border-red-200 dark:border-red-500/30">
        <div class="rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 mb-4 text-sm text-red-900 dark:text-red-200">
            <p class="font-semibold"><x-bi th="ลบข้อมูลทั้งหมดและสแนปช็อต กู้คืนไม่ได้" en="Deletes all data and the snapshot — this cannot be undone" /></p>
            <p class="mt-1"><x-bi th="ไฟล์ ฐานข้อมูล และการตั้งค่าทุกอย่างในเครื่องจะหายไป — สำรองสิ่งที่ต้องการออกไปก่อน"
                                  en="Every file, database and setting on the server goes. Copy off anything you need first." /></p>
        </div>

        @if(empty($templates))
            <p class="text-sm text-slate-600 dark:text-slate-400">
                <x-bi th="โหลดรายการระบบปฏิบัติการไม่ได้ในขณะนี้ กรุณารีเฟรชหน้าอีกครั้ง" en="Couldn't load the list of operating systems — please refresh the page." />
            </p>
        @else
            <form method="POST" action="{{ route('customer.vps.reinstall', $server->id) }}" class="space-y-4" data-once
                  @submit="confirmSubmit($event)">
                @csrf
                <input type="hidden" name="section" value="reinstall">

                <div>
                    <label for="template_id" class="{{ $label }}"><x-bi th="ระบบปฏิบัติการ" en="Operating system" /></label>
                    {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for — x-model อ่านค่าตั้งแต่ก่อน
                         ตัวเลือกจะมีจริง แล้ว select ตกไปที่ตัวแรก --}}
                    <select id="template_id" name="template_id" required class="{{ $field }}">
                        @foreach($templates as $group => $list)
                            <optgroup label="{{ (\App\Support\VpsCatalog::GROUPS[$group]['th'] ?? $group) . ' / ' . (\App\Support\VpsCatalog::GROUPS[$group]['en'] ?? $group) }}">
                                @foreach($list as $t)
                                    <option value="{{ $t['id'] }}" @selected($selectedTemplate === (int) $t['id'])>{{ $t['name'] }}{{ ! empty($t['licensed']) ? ' (ต้องซื้อไลเซนส์แยก)' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div x-data="vpsPassword()">
                    <label for="reinstall_root_password" class="{{ $label }}"><x-bi th="รหัสผ่าน root ของระบบใหม่" en="Root password for the new system" /></label>
                    <div class="flex gap-2">
                        <input type="password" :type="show ? 'text' : 'password'" id="reinstall_root_password" name="root_password" x-ref="pw"
                               required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                               class="{{ $field }} font-mono">
                        <button type="button" @click="show = !show" class="{{ $btnGhost }} shrink-0">
                            <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                            <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <x-bi th="อย่างน้อย 12 ตัว มีทั้งตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข"
                              en="At least 12 characters with upper case, lower case and a number." />
                    </p>
                    <p x-show="generated" x-cloak class="mt-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                        <x-bi th="คัดลอกหรือจดรหัสนี้ไว้ก่อนกดติดตั้ง — หลังจากนี้เราแสดงให้ดูอีกไม่ได้"
                              en="Copy or write it down before you reinstall — we can't show it to you again afterwards." />
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}">
                            <x-bi th="สุ่มรหัสที่ปลอดภัย" en="Generate one" />
                        </button>
                        <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                            <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                            <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirm_hostname" class="{{ $label }}">
                        <x-bi th="พิมพ์ชื่อโฮสต์เพื่อยืนยัน" en="Type the hostname to confirm" />:
                        <span class="font-mono break-all">{{ $server->hostname }}</span>
                    </label>
                    <input type="text" id="confirm_hostname" name="confirm_hostname" x-model="typed"
                           required autocomplete="off" autocapitalize="off" spellcheck="false"
                           class="{{ $field }} font-mono">
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="{{ $btnDanger }}" @disabled($busy) :disabled="busy || !matches">
                        <x-bi th="ลบทุกอย่างและติดตั้งใหม่" en="Wipe and reinstall" />
                    </button>
                    @if($busy)
                        <p class="text-xs text-amber-700 dark:text-amber-300"><x-bi th="เครื่องกำลังทำงานอื่นอยู่ — รอให้เสร็จก่อน" en="The server is busy — wait for it to finish first" /></p>
                    @else
                        <p x-show="typed.length > 0 && !matches" x-cloak class="text-xs text-red-600 dark:text-red-400"><x-bi th="ชื่อโฮสต์ยังไม่ตรง" en="The hostname doesn't match yet" /></p>
                    @endif
                </div>
            </form>
        @endif
    </div>
</div>
