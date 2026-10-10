{{--
    ช่องกรอกข้อมูลผู้ถือครอง (โหมด "กรอกใหม่") — ใช้ทั้งหน้าจดโดเมนและหน้าแก้ไขผู้ถือครอง
    ต้องอยู่ภายใน x-data="domainRegistrant(...)" จาก partials/registrant-script
    ตัวแปรจากหน้าแม่: $initial, $whoisCountries
--}}
@php
    $icon = [
        'user' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.68 0-5.22-.59-7.5-1.65z',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.38c0-.62.5-1.12 1.13-1.12h3.75c.62 0 1.12.5 1.12 1.12V21',
        'mail' => 'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.24a2.25 2.25 0 01-1.07 1.92l-7.5 4.61a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.91V6.75',
        'phone' => 'M2.25 6.75c0 8.28 6.72 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.37c0-.52-.35-.97-.85-1.09l-4.42-1.1a1.13 1.13 0 00-1.17.41l-.97 1.29a1.13 1.13 0 01-1.21.38 12.04 12.04 0 01-7.14-7.14 1.13 1.13 0 01.38-1.21l1.3-.97c.36-.27.52-.73.4-1.17l-1.1-4.42A1.13 1.13 0 005.37 2.25H4A2.25 2.25 0 001.75 4.5v2.25z',
        'home' => 'M2.25 12l8.95-8.96c.44-.44 1.15-.44 1.59 0L21.75 12M4.5 9.75v10.13c0 .62.5 1.12 1.13 1.12H9.75v-4.87c0-.63.5-1.13 1.13-1.13h2.25c.62 0 1.12.5 1.12 1.13V21h4.13c.62 0 1.12-.5 1.12-1.12V9.75M8.25 21h8.25',
        'pin' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1115 0z',
        'map' => 'M9 6.75V15m6-6v8.25m.5 3.73l4.87-2.43c.38-.2.63-.59.63-1.01V4.82c0-.84-.88-1.38-1.63-1.01l-3.87 1.94a1.13 1.13 0 01-1 0L9.5 3.77a1.13 1.13 0 00-1 0L3.62 6.2C3.25 6.4 3 6.79 3 7.21v11.97c0 .84.88 1.38 1.63 1.01l3.87-1.94c.32-.16.69-.16 1 0l4.5 2.25c.32.16.69.16 1 0z',
        'hash' => 'M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5',
        'globe' => 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
    ];
@endphp

<div x-show="mode === 'new'" x-bind:inert="mode !== 'new'" class="space-y-8">

    {{-- 1 · ชื่อ --}}
    <fieldset>
        <legend class="flex items-center gap-2.5 mb-4 text-base font-bold text-slate-900 dark:text-white">
            <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center">1</span>
            <x-bi th="ชื่อเจ้าของ" en="Owner" />
        </legend>
        <div class="grid sm:grid-cols-2 gap-x-5 gap-y-5">
            <div>
                <label for="reg-first-name" class="field-label">ชื่อ <span class="field-en">First name</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['user'] }}"/></svg>
                    <input id="reg-first-name" type="text" name="first_name" x-model="form.first_name"
                           x-bind:disabled="mode !== 'new'" @blur="nameBlur('first_name')"
                           x-bind:aria-invalid="error('first_name') ? 'true' : 'false'"
                           autocomplete="given-name" autocapitalize="words" spellcheck="false" enterkeyhint="next"
                           placeholder="เช่น สมชาย" class="w-full pl-11">
                </div>
                <p class="field-error" x-show="error('first_name')" x-text="error('first_name')" x-cloak></p>
            </div>
            <div>
                <label for="reg-last-name" class="field-label">นามสกุล <span class="field-en">Last name</span> <span class="text-red-500">*</span></label>
                <input id="reg-last-name" type="text" name="last_name" x-model="form.last_name"
                       x-bind:disabled="mode !== 'new'" @blur="nameBlur('last_name')"
                       x-bind:aria-invalid="error('last_name') ? 'true' : 'false'"
                       autocomplete="family-name" autocapitalize="words" spellcheck="false" enterkeyhint="next"
                       placeholder="เช่น ใจดี" class="w-full">
                <p class="field-error" x-show="error('last_name')" x-text="error('last_name')" x-cloak></p>
            </div>
            <p class="sm:col-span-2 -mt-2 field-hint">
                <span x-show="note.name" x-text="note.name" class="block text-indigo-600 dark:text-indigo-400 font-medium" x-cloak></span>
                <x-bi th="ภาษาไทยหรืออังกฤษก็ได้ ไม่ต้องใส่คำนำหน้า (นาย/นาง/นางสาว) — ให้ตรงกับบัตรประชาชน"
                      en="Thai or English, without a title — as on your ID card." />
            </p>
            <div class="sm:col-span-2">
                <label for="reg-org" class="field-label">ชื่อองค์กร <span class="field-en">Organisation · ไม่บังคับ / optional</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['building'] }}"/></svg>
                    <input id="reg-org" type="text" name="organization" x-model="form.organization"
                           x-bind:disabled="mode !== 'new'" autocomplete="organization" enterkeyhint="next"
                           placeholder="ถ้าจดในนามบริษัท/ร้าน" class="w-full pl-11">
                </div>
            </div>
        </div>
    </fieldset>

    {{-- 2 · ติดต่อ --}}
    <fieldset>
        <legend class="flex items-center gap-2.5 mb-4 text-base font-bold text-slate-900 dark:text-white">
            <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center">2</span>
            <x-bi th="ช่องทางติดต่อ" en="Contact" />
        </legend>
        <div class="grid sm:grid-cols-2 gap-x-5 gap-y-5">
            <div>
                <label for="reg-email" class="field-label">อีเมล <span class="field-en">Email</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['mail'] }}"/></svg>
                    <input id="reg-email" type="email" name="email" x-model.trim="form.email"
                           x-bind:disabled="mode !== 'new'" @blur="touch('email')"
                           x-bind:aria-invalid="error('email') ? 'true' : 'false'"
                           autocomplete="email" inputmode="email" spellcheck="false" enterkeyhint="next"
                           placeholder="name@gmail.com" class="w-full pl-11">
                </div>
                <p class="field-error" x-show="error('email')" x-text="error('email')" x-cloak></p>
                <p class="field-hint"><x-bi th="ทะเบียนส่งอีเมลยืนยันมาที่นี่ — ต้องเปิดอ่านได้จริง" en="The registry sends a verification e-mail here." /></p>
            </div>
            <div>
                <label for="reg-phone" class="field-label">โทรศัพท์มือถือ <span class="field-en">Phone</span> <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <select name="phone_country_code" x-model="form.phone_country_code" x-bind:disabled="mode !== 'new'"
                            aria-label="รหัสประเทศ / Country code" autocomplete="tel-country-code"
                            x-bind:aria-invalid="error('phone_country_code') ? 'true' : 'false'"
                            class="w-[8.5rem] shrink-0">
                        @foreach(collect($whoisCountries)->unique('phone_cc') as $code => $c)
                            <option value="+{{ $c['phone_cc'] }}" title="{{ $c['name']['th'] }} / {{ $c['name']['en'] }}" @selected($initial['phone_country_code'] === '+' . $c['phone_cc'])>+{{ $c['phone_cc'] }} · {{ $code }}</option>
                        @endforeach
                    </select>
                    <div class="relative flex-1 min-w-0">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['phone'] }}"/></svg>
                        <input id="reg-phone" type="tel" name="phone" x-model="form.phone"
                               x-bind:disabled="mode !== 'new'" @blur="phoneBlur()"
                               x-bind:aria-invalid="error('phone') ? 'true' : 'false'"
                               autocomplete="tel-national" inputmode="tel" enterkeyhint="next"
                               placeholder="081 234 5678" class="w-full pl-11">
                    </div>
                </div>
                <p class="field-error" x-show="error('phone') || error('phone_country_code')" x-text="error('phone') || error('phone_country_code')" x-cloak></p>
                <p class="field-hint" x-show="phonePreview && !error('phone')">
                    <x-bi th="ทะเบียนบันทึกเป็น" en="Saved as" /> <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="phonePreview"></span>
                </p>
            </div>
        </div>
    </fieldset>

    {{-- 3 · ที่อยู่ --}}
    <fieldset>
        <legend class="flex items-center gap-2.5 mb-4 text-base font-bold text-slate-900 dark:text-white">
            <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-sm flex items-center justify-center">3</span>
            <x-bi th="ที่อยู่ผู้ถือครอง" en="Registrant address" />
        </legend>
        <div class="grid sm:grid-cols-2 gap-x-5 gap-y-5">
            <div class="sm:col-span-2">
                <label for="reg-country" class="field-label">ประเทศ <span class="field-en">Country</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['globe'] }}"/></svg>
                    <select id="reg-country" name="country" x-model="form.country" x-bind:disabled="mode !== 'new'"
                            autocomplete="country" class="w-full pl-11">
                        @foreach($whoisCountries as $code => $c)
                            <option value="{{ $code }}" @selected($initial['country'] === $code)>{{ $c['name']['th'] }} / {{ $c['name']['en'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label for="reg-address1" class="field-label">บ้านเลขที่ หมู่ ซอย ถนน <span class="field-en">Address</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['home'] }}"/></svg>
                    <input id="reg-address1" type="text" name="address1" x-model="form.address1"
                           x-bind:disabled="mode !== 'new'" @blur="touch('address1')"
                           x-bind:aria-invalid="error('address1') ? 'true' : 'false'"
                           autocomplete="address-line1" enterkeyhint="next"
                           placeholder="เช่น 99/12 ม.5 ซ.สุขุมวิท 101" class="w-full pl-11">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="reg-address2" class="field-label">แขวง / ตำบล <span class="field-en">Sub-district · ไม่บังคับ / optional</span></label>
                <input id="reg-address2" type="text" name="address2" x-model="form.address2"
                       x-bind:disabled="mode !== 'new'" @blur="touch('address1')"
                       autocomplete="address-line2" enterkeyhint="next"
                       placeholder="เช่น บางจาก" class="w-full">

                {{-- ทะเบียนรับที่อยู่บรรทัดเดียว ≤ 50 ตัว: แสดงสิ่งที่จะถูกส่งจริง --}}
                <div class="mt-2.5 rounded-xl border px-4 py-3 text-sm"
                     :class="addressLength > addressMax ? 'border-red-300 bg-red-50 dark:border-red-500/40 dark:bg-red-500/10' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40'">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500 dark:text-slate-400"><x-bi th="บรรทัดที่ส่งเข้าทะเบียน" en="Line sent to the registry" /></span>
                        <span class="font-mono text-xs font-semibold"
                              :class="addressLength > addressMax ? 'text-red-600 dark:text-red-400' : (addressLength > addressMax - 8 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400')"
                              x-text="addressLength + ' / ' + addressMax"></span>
                    </div>
                    <p class="mt-1 font-medium text-slate-800 dark:text-slate-100 break-words" x-text="addressLine() || '—'"></p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="addressHasSlash" x-cloak>
                        <x-bi th="ทะเบียนสากลไม่รับเครื่องหมาย / จึงเปลี่ยนเป็น - ให้ (เช่น 99/12 → 99-12)" en="The registry refuses “/”, so it becomes “-” (99/12 → 99-12)." />
                    </p>
                    <button type="button" x-show="addressLength > addressMax" x-cloak @click="shortenAddress()"
                            class="mt-2 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-semibold transition">
                        <x-bi th="ย่อให้อัตโนมัติ (ถนน → ถ. ซอย → ซ. หมู่ → ม.)" en="Shorten for me" />
                    </button>
                </div>
                <p class="field-error" x-show="error('address1')" x-text="error('address1')" x-cloak></p>
            </div>

            <div>
                <label for="reg-zip" class="field-label">รหัสไปรษณีย์ <span class="field-en">Postal code</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['hash'] }}"/></svg>
                    <input id="reg-zip" type="text" name="zip" x-model.trim="form.zip"
                           x-bind:disabled="mode !== 'new'" @blur="touch('zip')"
                           x-bind:aria-invalid="error('zip') ? 'true' : 'false'"
                           autocomplete="postal-code" enterkeyhint="next"
                           x-bind:inputmode="['GB', 'HK'].includes(form.country) ? 'text' : 'numeric'"
                           x-bind:placeholder="spec ? 'เช่น ' + spec.zip_example : ''"
                           maxlength="10" class="w-full pl-11 tracking-wider">
                </div>
                <p class="field-error" x-show="error('zip')" x-text="error('zip')" x-cloak></p>
                <p class="field-hint" x-show="form.country === 'TH' && !error('zip')">
                    <x-bi th="กรอกรหัสไปรษณีย์ แล้วระบบเลือกจังหวัดให้" en="Type it and we pick the province for you." />
                </p>
            </div>
            <div>
                <label for="reg-state" class="field-label">
                    <span x-text="spec ? spec.state_label.th : 'จังหวัด'">จังหวัด</span>
                    <span class="field-en" x-text="spec ? spec.state_label.en : 'Province'">Province</span>
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['map'] }}"/></svg>
                    <input id="reg-state" type="text" name="state" x-model="form.state" list="reg-state-options"
                           x-bind:disabled="mode !== 'new'" @blur="touch('state')"
                           x-bind:aria-invalid="error('state') ? 'true' : 'false'"
                           autocomplete="address-level1" enterkeyhint="next"
                           placeholder="พิมพ์หรือเลือก เช่น กรุงเทพมหานคร" class="w-full pl-11 pr-11">
                    <svg x-show="region()" x-cloak class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <datalist id="reg-state-options">
                        <template x-for="o in regionOptions" :key="o.value">
                            <option :value="o.label" x-text="o.label !== o.value ? o.value : ''"></option>
                        </template>
                    </datalist>
                </div>
                <p class="field-error" x-show="error('state')" x-text="error('state')" x-cloak></p>
                <p class="field-hint" x-show="region() && !note.zip.startsWith('mismatch:')" x-cloak>
                    <span x-show="note.zip" x-text="note.zip" class="block text-emerald-600 dark:text-emerald-400 font-medium"></span>
                    <x-bi th="ทะเบียนบันทึกเป็น" en="Saved as" /> <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="region()"></span>
                </p>
                <div class="mt-1.5 text-sm text-amber-700 dark:text-amber-300" x-show="note.zip.startsWith('mismatch:')" x-cloak>
                    <span x-text="'รหัสไปรษณีย์นี้อยู่ใน ' + regionLabel(note.zip.replace('mismatch:', ''))"></span>
                    <button type="button" @click="useZipProvince()" class="ml-1 font-semibold underline underline-offset-2">
                        <x-bi th="เปลี่ยนให้" en="Use it" />
                    </button>
                </div>
            </div>

            <div class="sm:col-span-2">
                <label for="reg-city" class="field-label">เขต / อำเภอ <span class="field-en">District / City</span> <span class="text-red-500">*</span></label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['pin'] }}"/></svg>
                    <input id="reg-city" type="text" name="city" x-model="form.city"
                           x-bind:disabled="mode !== 'new'" @blur="touch('city')"
                           x-bind:aria-invalid="error('city') ? 'true' : 'false'"
                           autocomplete="address-level2" enterkeyhint="done"
                           placeholder="เช่น พระโขนง หรือ เมืองเชียงใหม่" class="w-full pl-11">
                </div>
                <p class="field-error" x-show="error('city')" x-text="error('city')" x-cloak></p>
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-start gap-3 cursor-pointer rounded-xl p-3 -m-3 hover:bg-slate-50 dark:hover:bg-slate-900/40 transition">
                    <input type="checkbox" name="save_contact" value="1" checked x-bind:disabled="mode !== 'new'" class="mt-0.5">
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        <x-bi th="เสนอข้อมูลชุดนี้ให้เลือกในครั้งต่อไป" en="Offer these details again next time" />
                        {{-- พูดตรง ๆ ว่าข้อมูลถูกเก็บอยู่แล้ว เพราะมันคือ
                             ผู้ถือครองโดเมนตามทะเบียน ไม่ใช่ที่อยู่ส่งของ
                             ที่จะลบทิ้งได้ · ช่องนี้คุมแค่การแสดงในตัวเลือก --}}
                        <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            <x-bi th="ข้อมูลผู้ถือครองถูกเก็บไว้กับโดเมนเสมอตามข้อกำหนดทะเบียนสากล ช่องนี้เลือกแค่ว่าจะให้ขึ้นเป็นตัวเลือกให้กดเลือกซ้ำหรือไม่"
                                  en="Registrant details are always kept with the domain, as the registry requires. This only chooses whether they appear as a saved option." />
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </fieldset>

    {{-- ตรวจทานก่อนจ่าย: สิ่งที่จะถูกส่งเข้าทะเบียนจริง หลังทำความสะอาดแล้ว --}}
    <div class="rounded-2xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/60 dark:bg-emerald-500/5 p-5">
        <p class="flex items-center gap-2 font-bold text-emerald-900 dark:text-emerald-200 mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <x-bi th="ตรวจทาน — ข้อมูลที่จะบันทึกในทะเบียน" en="Review — what the registry will record" />
        </p>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ชื่อ" en="Name" /></dt><dd class="font-medium text-slate-900 dark:text-white break-words" x-text="(cleanName(form.first_name) + ' ' + cleanName(form.last_name)).trim() || '—'"></dd></div>
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="อีเมล" en="Email" /></dt><dd class="font-medium text-slate-900 dark:text-white break-all" x-text="form.email || '—'"></dd></div>
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="โทร" en="Phone" /></dt><dd class="font-medium text-slate-900 dark:text-white" x-text="phonePreview || '—'"></dd></div>
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ที่อยู่" en="Address" /></dt><dd class="font-medium text-slate-900 dark:text-white break-words" x-text="addressLine() || '—'"></dd></div>
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="เขต/จังหวัด" en="City" /></dt><dd class="font-medium text-slate-900 dark:text-white break-words" x-text="[cityLine(), regionLabel(region())].filter(Boolean).join(', ') || '—'"></dd></div>
            <div class="flex gap-2"><dt class="w-24 shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="รหัส/ประเทศ" en="Zip" /></dt><dd class="font-medium text-slate-900 dark:text-white" x-text="[zipValue(), spec ? spec.name.th : ''].filter(Boolean).join(' · ') || '—'"></dd></div>
        </dl>
        <p class="mt-3 text-sm font-semibold" x-show="submitted || Object.keys(touched).length > 5"
           :class="Object.keys(problems).length ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'"
           x-text="Object.keys(problems).length ? 'ยังมี ' + Object.keys(problems).length + ' ช่องที่ต้องแก้' : 'ครบถ้วน พร้อมจด ✓'"></p>
    </div>
</div>
