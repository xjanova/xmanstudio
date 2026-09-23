{{-- ══════════ เครือข่ายและความปลอดภัย ══════════ --}}
@php
    $fw = $firewall ?? ['exists' => false, 'loaded' => false, 'active' => false, 'foreign' => false, 'synced' => true, 'rows' => []];
    // กลับมาจาก validation ไม่ผ่าน: แถวที่ลูกค้าพิมพ์ไว้ต้องอยู่ครบ
    $fwRows = is_array(old('rules')) ? array_values(array_filter(old('rules'), 'is_array')) : $fw['rows'];
    $fwProtocols = collect(\App\Support\VpsFirewall::PROTOCOLS)->map(fn ($p, $k) => ['label' => $p['th'] . ' / ' . $p['en'], 'port' => $p['port']])->all();
    // ปุ่ม "เปิด" ใช้กฎที่บันทึกไว้จริง ไม่ใช่แถวที่กำลังแก้ค้างในหน้า
    $fwSaved = $fw['saved_rows'] ?? [];
    $fwSavedAllowsSsh = \App\Support\VpsFirewall::allowsSsh($fwSaved);
    $fwEnableConfirm = 'เปิดไฟร์วอลล์ของ ' . $server->hostname . '?'
        . "\n\nเข้าถึงเครื่องได้เฉพาะ: " . \App\Support\VpsFirewall::describe($fwSaved)
        . "\nการเชื่อมต่ออื่นทั้งหมดจะถูกบล็อก";
@endphp

<div id="firewall" class="{{ $card }} p-6 scroll-mt-24"
     x-data="vpsFirewallRules(@js(['rows' => $fwRows, 'saved' => $fwSaved, 'protocols' => $fwProtocols, 'confirmNoSsh' => (bool) old('confirm_no_ssh')]))">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="{{ $h2 }}"><x-bi th="ไฟร์วอลล์" en="Firewall" /></h2>
                @if($fw['active'])
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        <x-bi th="เปิดอยู่" en="On" />
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                        <x-bi th="ปิดอยู่" en="Off" />
                    </span>
                @endif
            </div>
            <p class="{{ $lead }} max-w-2xl">
                <x-bi th="เมื่อเปิด ทุกการเชื่อมต่อจากภายนอกจะถูกบล็อก ยกเว้นพอร์ตที่อยู่ในกฎด้านล่าง — กันการเดารหัสผ่านและบริการที่เผลอเปิดทิ้งไว้"
                      en="When on, every incoming connection is blocked except the ports in the rules below — it stops password guessing and services left open by mistake." />
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($fw['active'])
                <form method="POST" action="{{ route('customer.vps.firewall', $server->id) }}" data-once
                      onsubmit="return confirm(@js('ปิดไฟร์วอลล์ของ ' . $server->hostname . '?' . "\n\nทุกพอร์ตที่เปิดในเครื่องจะเข้าถึงได้จากภายนอกอีกครั้ง"))">
                    @csrf
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="{{ $btnGhostDanger }}" @disabled($busy)><x-bi th="ปิดไฟร์วอลล์" en="Turn off" /></button>
                </form>
            @else
                <form method="POST" action="{{ route('customer.vps.firewall', $server->id) }}" class="flex flex-col items-end gap-2" data-once
                      onsubmit="return confirm(@js($fwEnableConfirm))">
                    @csrf
                    <input type="hidden" name="action" value="enable">
                    <button type="submit" class="{{ $btnPrimary }}" @disabled($busy || ! $fw['loaded'])
                            :disabled="{{ ($busy || ! $fw['loaded']) ? 'true' : 'dirty' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3z"/></svg>
                        <x-bi th="เปิดไฟร์วอลล์" en="Turn on" />
                    </button>
                    @if($fw['loaded'] && ! $fwSavedAllowsSsh)
                        {{-- ยืนยันของปุ่มนี้เอง ผูกกับกฎที่บันทึกไว้ ไม่ใช่กับตัวแก้ด้านล่าง --}}
                        <label class="inline-flex items-center gap-2 text-xs text-red-700 dark:text-red-300">
                            <input type="checkbox" name="confirm_no_ssh" value="1" required class="rounded border-red-300 text-red-600 focus:ring-red-500">
                            <x-bi th="กฎที่บันทึกไว้ไม่เปิด SSH — ยืนยันว่าตั้งใจ" en="Saved rules block SSH — I mean it" />
                        </label>
                    @endif
                    <p x-show="dirty" x-cloak class="text-xs text-amber-700 dark:text-amber-300">
                        <x-bi th="บันทึกกฎที่แก้ไว้ก่อน แล้วค่อยเปิด" en="Save your rule changes first" />
                    </p>
                </form>
            @endif
        </div>
    </div>

    @if($fw['foreign'])
        <p class="mt-4 rounded-lg bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 px-4 py-3 text-sm text-sky-900 dark:text-sky-200">
            <x-bi th="ตอนนี้เครื่องใช้ไฟร์วอลล์ที่ทีมงานตั้งไว้ให้ — ถ้าเปิดไฟร์วอลล์ของคุณเอง ชุดกฎด้านล่างจะใช้แทน"
                  en="The server is using a firewall our team set up. Turning yours on replaces it with the rules below." />
        </p>
    @endif
    @if(! empty($fw['applying']))
        <p class="mt-4 rounded-lg bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 px-4 py-3 text-sm text-sky-900 dark:text-sky-200">
            <x-bi th="กำลังนำการเปลี่ยนแปลงไปใช้กับเครื่อง — ใช้เวลาไม่เกิน 1–2 นาที" en="Applying the change to the server — takes a minute or two." />
        </p>
    @elseif($fw['active'] && ! $fw['synced'])
        <p class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
            <x-bi th="กำลังนำกฎล่าสุดไปใช้กับเครื่อง — ใช้เวลาไม่เกิน 1–2 นาที" en="Applying the latest rules to the server — takes a minute or two." />
        </p>
    @endif

    @if(! $fw['loaded'])
        <p class="mt-5 rounded-lg bg-slate-100 dark:bg-slate-900/60 px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
            <x-bi th="อ่านกฎไฟร์วอลล์ไม่ได้ในขณะนี้ — รีเฟรชหน้าอีกครั้งในอีกสักครู่" en="Couldn't read the firewall rules just now — refresh in a moment." />
        </p>
    @else
        <form method="POST" action="{{ route('customer.vps.firewall', $server->id) }}" class="mt-5" data-once>
            @csrf
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="confirm_no_ssh" :value="confirmNoSsh ? 1 : 0">

            <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="hidden sm:grid grid-cols-12 gap-3 bg-slate-50 dark:bg-slate-900/50 px-4 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <span class="col-span-4"><x-bi th="อนุญาต" en="Allow" /></span>
                    <span class="col-span-3"><x-bi th="พอร์ต" en="Port" /></span>
                    <span class="col-span-4"><x-bi th="จากที่ไหน" en="From" /></span>
                    <span class="col-span-1"></span>
                </div>
                <template x-for="(row, i) in rows" :key="row._k">
                    <div class="grid grid-cols-12 gap-3 items-center px-4 py-3 border-t border-slate-200 dark:border-slate-700 first:border-t-0 sm:first:border-t">
                        <label class="col-span-12 sm:col-span-4">
                            <span class="sr-only">ชนิด / Protocol</span>
                            {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for — x-model อ่านค่าก่อนตัวเลือกจะมีจริง --}}
                            <select :name="'rules[' + i + '][protocol]'" x-model="row.protocol" @change="fixPort(row)" class="{{ $field }}">
                                @foreach($fwProtocols as $key => $p)
                                    <option value="{{ $key }}">{{ $p['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="col-span-5 sm:col-span-3">
                            <span class="sr-only">พอร์ต / Port</span>
                            <input type="text" :name="'rules[' + i + '][port]'" x-model="row.port" :readonly="portFixed(row)"
                                   placeholder="เช่น 8080 หรือ 8000:8100" inputmode="numeric" autocomplete="off"
                                   class="{{ $field }} font-mono" :class="portFixed(row) ? 'opacity-60 cursor-not-allowed' : ''">
                        </label>
                        <label class="col-span-6 sm:col-span-4">
                            <span class="sr-only">ต้นทาง / Source</span>
                            <input type="text" :name="'rules[' + i + '][source_detail]'" x-model="row.source_detail"
                                   placeholder="ทุกที่ (any) หรือ IP/CIDR" autocomplete="off" spellcheck="false"
                                   class="{{ $field }} font-mono">
                        </label>
                        <div class="col-span-1 flex justify-end">
                            <button type="button" @click="remove(i)" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition" aria-label="ลบกฎ / Remove rule">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
                <p x-show="rows.length === 0" x-cloak class="px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                    <x-bi th="ยังไม่มีกฎ — ถ้าเปิดไฟร์วอลล์ตอนนี้ ทุกการเชื่อมต่อจะถูกบล็อก" en="No rules — turned on like this, every connection would be blocked." />
                </p>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="add()" class="{{ $btnGhost }}" :disabled="rows.length >= {{ \App\Support\VpsFirewall::MAX_RULES }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                    <x-bi th="เพิ่มกฎ" en="Add a rule" />
                </button>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    <x-bi th="ช่อง 'จากที่ไหน' เว้นว่าง = ทุกที่ · ใส่ IP ของบ้าน/ออฟฟิศเพื่อให้ SSH ได้เฉพาะคุณ"
                          en="Leave 'From' empty for anywhere, or put your home/office IP so only you can SSH in." />
                </p>
            </div>

            <div x-show="!allowsSsh" x-cloak class="mt-4 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-900 dark:text-red-200">
                <p class="font-semibold"><x-bi th="กฎชุดนี้ไม่เปิด SSH (พอร์ต 22)" en="These rules don't allow SSH (port 22)" /></p>
                <p class="mt-1"><x-bi th="ถ้าเปิดไฟร์วอลล์ไว้ คุณจะเข้าเครื่องด้วย SSH ไม่ได้ จนกว่าจะเพิ่มกฎกลับหรือปิดไฟร์วอลล์"
                                      en="With the firewall on you won't be able to SSH in until you add the rule back or turn the firewall off." /></p>
                <label class="mt-2 inline-flex items-center gap-2">
                    <input type="checkbox" x-model="confirmNoSsh" class="rounded border-red-300 text-red-600 focus:ring-red-500">
                    <span><x-bi th="เข้าใจแล้ว ตั้งใจปิด SSH" en="I understand — block SSH on purpose" /></span>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="{{ $btnPrimary }}" @disabled($busy)
                        :disabled="{{ $busy ? 'true' : '!allowsSsh && !confirmNoSsh' }}">
                    <x-bi th="บันทึกกฎ" en="Save rules" />
                </button>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    @if($fw['active'])
                        <x-bi th="มีผลกับเครื่องทันทีที่บันทึก" en="Applies to the server as soon as you save" />
                    @else
                        <x-bi th="บันทึกไว้ก่อนได้ มีผลเมื่อเปิดไฟร์วอลล์" en="Save now; it takes effect when you turn the firewall on" />
                    @endif
                </p>
            </div>
        </form>

        <div class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700">
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2"><x-bi th="ชุดกฎสำเร็จรูป" en="Ready-made rule sets" /></p>
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Support\VpsFirewall::PRESETS as $key => $preset)
                    <form method="POST" action="{{ route('customer.vps.firewall', $server->id) }}" data-once
                          onsubmit="return confirm(@js('แทนที่กฎทั้งหมดด้วยชุด ' . $preset['th'] . '?'))">
                        @csrf
                        <input type="hidden" name="action" value="preset">
                        <input type="hidden" name="preset" value="{{ $key }}">
                        <button type="submit" class="{{ $btnGhost }}" @disabled($busy)><x-bi :th="$preset['th']" :en="$preset['en']" /></button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- ══════════ SSH keys ══════════ --}}
    <div id="ssh-keys" class="{{ $card }} p-6 scroll-mt-24">
        <h2 class="{{ $h2 }}">SSH keys</h2>
        <p class="{{ $lead }}">
            <x-bi th="เข้าเครื่องด้วยกุญแจแทนรหัสผ่าน — ปลอดภัยกว่าและไม่ต้องพิมพ์รหัสทุกครั้ง"
                  en="Log in with a key instead of a password — safer, and nothing to type each time." />
        </p>

        @if($sshKeys === null)
            <p class="mt-4 rounded-lg bg-slate-100 dark:bg-slate-900/60 px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                <x-bi th="อ่านรายการคีย์ไม่ได้ในขณะนี้" en="Couldn't read the key list just now" />
            </p>
        @elseif($sshKeys === [])
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400"><x-bi th="ยังไม่มี SSH key บนเครื่องนี้" en="No SSH keys on this server yet" /></p>
        @else
            <ul class="mt-4 space-y-2">
                @foreach($sshKeys as $k)
                    <li class="rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-2.5 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white break-all">{{ $k['name'] ?: $k['comment'] ?: $k['type'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono break-all">{{ $k['type'] }} · {{ $k['fingerprint'] }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('customer.vps.ssh-keys', $server->id) }}" class="mt-5 space-y-3" data-once>
            @csrf
            <div>
                <label for="key_name" class="{{ $label }}"><x-bi th="ชื่อคีย์ (ไม่บังคับ)" en="Key name (optional)" /></label>
                <input type="text" id="key_name" name="key_name" value="{{ old('key_name') }}" maxlength="40" placeholder="เช่น macbook"
                       autocomplete="off" class="{{ $field }}">
            </div>
            <div>
                <label for="public_key" class="{{ $label }}">Public key</label>
                <textarea id="public_key" name="public_key" rows="3" maxlength="4096" required spellcheck="false" autocomplete="off"
                          placeholder="ssh-ed25519 AAAA… you@laptop" class="{{ $field }} font-mono text-xs">{{ old('public_key') }}</textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <x-bi th="วางเนื้อหาไฟล์ ~/.ssh/id_ed25519.pub (ไฟล์ .pub เท่านั้น ห้ามวางคีย์ส่วนตัว)"
                          en="Paste the contents of ~/.ssh/id_ed25519.pub (the .pub file only — never the private key)." />
                </p>
            </div>
            <button type="submit" class="{{ $btnDark }}" @disabled($busy)><x-bi th="เพิ่มคีย์ลงเครื่อง" en="Add to server" /></button>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <x-bi th="การลบคีย์ทำในเครื่อง: แก้ไฟล์ ~/.ssh/authorized_keys แล้วลบบรรทัดของคีย์นั้น"
                      en="To remove a key, edit ~/.ssh/authorized_keys on the server and delete its line." />
            </p>
        </form>
    </div>

    <div class="space-y-6">
        {{-- ══════════ Reverse DNS ══════════ --}}
        <div class="{{ $card }} p-6">
            <h2 class="{{ $h2 }}">Reverse DNS (PTR)</h2>
            <p class="{{ $lead }}">
                <x-bi th="ชื่อที่ IP นี้บอกกลับเมื่อมีคนถาม — จำเป็นถ้าส่งอีเมลจากเครื่องนี้ ควรเป็นชื่อที่ชี้มาที่ IP เดียวกัน เช่น mail.example.com"
                      en="The name this IP answers with — needed if the server sends email. Use a name that points back to the same IP, e.g. mail.example.com." />
            </p>
            <div class="mt-4 space-y-3">
                @foreach(['v4' => $network['ipv4'], 'v6' => $network['ipv6']] as $family => $ip)
                    @continue(! $ip['address'] || ! $ip['id'])
                    <form method="POST" action="{{ route('customer.vps.reverse-dns', $server->id) }}" class="rounded-lg border border-slate-200 dark:border-slate-700 p-3" data-once>
                        @csrf
                        <input type="hidden" name="ip" value="{{ $family }}">
                        <label for="ptr_{{ $family }}" class="block text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                            {{ $family === 'v4' ? 'IPv4' : 'IPv6' }} · <span class="font-mono break-all">{{ $ip['address'] }}</span>
                        </label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" id="ptr_{{ $family }}" name="ptr" value="{{ old('ip') === $family ? old('ptr') : $ip['ptr'] }}" maxlength="253"
                                   placeholder="mail.example.com" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="url"
                                   class="{{ $field }} font-mono">
                            <button type="submit" class="{{ $btnDark }} shrink-0"><x-bi th="บันทึก" en="Save" /></button>
                        </div>
                    </form>
                @endforeach
                @if(! $network['ipv4']['id'] && ! $network['ipv6']['id'])
                    <p class="text-sm text-slate-500 dark:text-slate-400"><x-bi th="อ่านข้อมูล IP ของเครื่องไม่ได้ในขณะนี้ — รีเฟรชอีกครั้ง" en="Couldn't read the server's addresses — refresh to try again." /></p>
                @endif
                <p class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="เว้นว่างแล้วบันทึกเพื่อลบ" en="Save it empty to remove it." /></p>
            </div>
        </div>

        {{-- ══════════ DNS resolver ══════════ --}}
        <div class="{{ $card }} p-6" x-data="{ ns1: @js(old('ns1', $network['ns1'] ?? '')), ns2: @js(old('ns2', $network['ns2'] ?? '')) }">
            <h2 class="{{ $h2 }}">DNS resolver</h2>
            <p class="{{ $lead }}">
                <x-bi th="ตัวที่เครื่องใช้ค้นหาชื่อโดเมนออกไปข้างนอก (ไม่ใช่ nameserver ของโดเมนคุณ) — เปลี่ยนเมื่อเครื่องหาชื่อเว็บอื่นไม่เจอ"
                      en="What the server uses to look names up (not your domain's nameservers). Change it if the server can't resolve other sites." />
            </p>
            <form method="POST" action="{{ route('customer.vps.resolvers', $server->id) }}" class="mt-4 space-y-3" data-once>
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><x-bi th="ตัวหลัก" en="Primary" /></span>
                        <input type="text" name="ns1" x-model="ns1" required maxlength="45" placeholder="1.1.1.1" autocomplete="off" class="{{ $field }} font-mono">
                    </label>
                    <label class="block">
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mb-1"><x-bi th="ตัวสำรอง" en="Secondary" /></span>
                        <input type="text" name="ns2" x-model="ns2" maxlength="45" placeholder="8.8.8.8" autocomplete="off" class="{{ $field }} font-mono">
                    </label>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="ns1 = '1.1.1.1'; ns2 = '1.0.0.1'" class="px-2.5 py-1 rounded-md text-xs border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Cloudflare</button>
                    <button type="button" @click="ns1 = '8.8.8.8'; ns2 = '8.8.4.4'" class="px-2.5 py-1 rounded-md text-xs border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Google</button>
                    <button type="button" @click="ns1 = '9.9.9.9'; ns2 = '149.112.112.112'" class="px-2.5 py-1 rounded-md text-xs border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Quad9</button>
                    <button type="submit" class="{{ $btnDark }} ml-auto"><x-bi th="บันทึก" en="Save" /></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- ══════════ สแกนมัลแวร์ ══════════ --}}
    <div class="{{ $card }} p-6">
        <h2 class="{{ $h2 }}"><x-bi th="สแกนมัลแวร์" en="Malware scanner" /></h2>
        <p class="{{ $lead }}">
            <x-bi th="ตัวสแกนฟรี ตรวจไฟล์เว็บและสคริปต์ที่ถูกฝังโค้ดอันตรายเป็นระยะ — เหมาะกับเครื่องที่รัน WordPress หรือเว็บ PHP"
                  en="A free scanner that checks web files and scripts for injected malicious code — good for WordPress and PHP sites." />
        </p>

        @if($malware)
            <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2.5">
                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ไฟล์ที่ตรวจ" en="Files scanned" /></dt>
                    <dd class="text-lg font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($malware['scanned']) }}</dd>
                </div>
                <div class="rounded-lg border px-3 py-2.5 {{ $malware['malicious'] > 0 ? 'border-red-300 dark:border-red-500/40 bg-red-50 dark:bg-red-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="อันตราย" en="Malicious" /></dt>
                    <dd class="text-lg font-bold tabular-nums {{ $malware['malicious'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($malware['malicious']) }}</dd>
                </div>
                <div class="rounded-lg border px-3 py-2.5 {{ $malware['compromised'] > 0 ? 'border-amber-300 dark:border-amber-500/40 bg-amber-50 dark:bg-amber-500/10' : 'border-slate-200 dark:border-slate-700' }}">
                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ถูกแก้ไข" en="Compromised" /></dt>
                    <dd class="text-lg font-bold tabular-nums {{ $malware['compromised'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($malware['compromised']) }}</dd>
                </div>
            </dl>
            @if($lastScan = $when($malware['ended_at'] ?? $malware['started_at'] ?? null))
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400"><x-bi th="สแกนล่าสุด" en="Last scan" /> {{ $lastScan }}</p>
            @endif
            <form method="POST" action="{{ route('customer.vps.malware', $server->id) }}" class="mt-4" data-once
                  onsubmit="return confirm(@js('ถอนตัวสแกนมัลแวร์ออกจาก ' . $server->hostname . '?'))">
                @csrf
                <input type="hidden" name="action" value="uninstall">
                <button type="submit" class="{{ $btnGhost }}"><x-bi th="ถอนการติดตั้ง" en="Uninstall" /></button>
            </form>
        @else
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                <x-bi th="ยังไม่ได้ติดตั้งบนเครื่องนี้ หรือยังไม่มีผลสแกน" en="Not installed on this server yet, or no scan results so far." />
            </p>
            <form method="POST" action="{{ route('customer.vps.malware', $server->id) }}" class="mt-4" data-once>
                @csrf
                <input type="hidden" name="action" value="install">
                <button type="submit" class="{{ $btnDark }}" @disabled($busy)><x-bi th="ติดตั้งตัวสแกน (ฟรี)" en="Install the scanner (free)" /></button>
            </form>
        @endif
    </div>

    {{-- ══════════ ชี้โดเมน ══════════ --}}
    <div class="{{ $card }} p-6">
        <h2 class="{{ $h2 }}"><x-bi th="ชี้โดเมนมาที่เครื่องนี้" en="Point a domain here" /></h2>
        <p class="{{ $lead }}">
            <x-bi th="ตั้ง A record ของตัวโดเมน (@) ไปที่ IP ของเครื่องนี้ และให้ www ชี้ไปที่โดเมนเดียวกัน — ระเบียนอีเมลและระเบียนอื่นไม่ถูกแตะ · มีผลใน 5–30 นาที"
                  en="Sets the domain's A record (@) to this server's IP and points www at the domain. Mail and every other record are left alone. Takes 5–30 minutes." />
        </p>
        @if(! $server->ipv4)
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                <x-bi th="เครื่องยังไม่ได้รับ IP — รอสักครู่แล้วรีเฟรช" en="The server has no IP yet — wait a moment and refresh." />
            </p>
        @elseif($domains->isNotEmpty())
            <form method="POST" action="{{ route('customer.vps.point-domain', $server->id) }}" class="mt-4 flex flex-col sm:flex-row gap-2" data-once
                  onsubmit="return vpsConfirmPoint(this, @js($server->ipv4))">
                @csrf
                <label for="domain_id" class="sr-only">โดเมน / Domain</label>
                {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for (x-model อ่านค่าก่อนตัวเลือกจะมีจริง) --}}
                <select id="domain_id" name="domain_id" required class="{{ $field }}">
                    @foreach($domains as $d)
                        <option value="{{ $d->id }}" @selected((int) old('domain_id') === (int) $d->id)>{{ $d->domain }}</option>
                    @endforeach
                </select>
                <button type="submit" class="{{ $btnDark }} shrink-0"><x-bi th="ชี้มาที่นี่" en="Point it here" /></button>
            </form>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                <x-bi :th="'ถ้าโดเมนใช้ nameserver ที่อื่น (เช่น Cloudflare) ให้ตั้ง A record ไปที่ ' . $server->ipv4 . ' ที่ผู้ให้บริการ DNS นั้นแทน'"
                      :en="'If the domain uses someone else\'s nameservers (e.g. Cloudflare), add an A record for ' . $server->ipv4 . ' there instead.'" />
            </p>
        @else
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <p class="text-sm text-slate-500 dark:text-slate-400 flex-1"><x-bi th="ยังไม่มีโดเมนในบัญชีนี้" en="No domains on this account yet" /></p>
                <a href="{{ route('domains.index') }}" class="{{ $btnGhost }} shrink-0"><x-bi th="จดโดเมนใหม่" en="Register a domain" /></a>
            </div>
        @endif
    </div>
</div>
