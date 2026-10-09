@extends($publicLayout ?? 'layouts.app')

@section('title', 'จดโดเมน ' . $domain . ' | XMAN Studio')

@section('content')
{{--
    ฟอร์มจดทะเบียนโดเมน

    ข้อมูลในหน้านี้กลายเป็น "ผู้ถือครอง" ของโดเมนตามทะเบียนสากล ไม่ใช่แค่
    ที่อยู่ส่งของ — ชื่อที่กรอกคือชื่อที่จะปรากฏว่าเป็นเจ้าของ และเป็นชื่อที่
    ใช้ยืนยันสิทธิ์ตอนย้ายโดเมนออก จึงบอกไว้ตรง ๆ บนหน้าแทนที่จะซ่อนใน
    เงื่อนไขการใช้งาน

    กฎของทะเบียน (ที่อยู่บรรทัดเดียวไม่เกิน 50 ตัว, จังหวัดต้องตรงรายการ,
    รหัสไปรษณีย์ตามรูปแบบประเทศ) มาจาก config/domain_whois.php — สคริปต์
    ท้ายหน้าตรวจตามกฎชุดเดียวกับ App\Support\WhoisContact ฝั่งเซิร์ฟเวอร์
    เพื่อให้ลูกค้ารู้ก่อนจ่าย ไม่ใช่รู้จากการคืนเงิน

    ทุกช่องมี autocomplete ตามมาตรฐาน เพื่อให้เบราว์เซอร์เติมชื่อ/ที่อยู่ที่
    บันทึกไว้ได้ในคลิกเดียว — จังหวัดเป็นช่องพิมพ์ (ไม่ใช่ dropdown) เพราะ
    autofill เติม "กรุงเทพมหานคร" ลง dropdown ที่ค่าเป็น "Bangkok" ไม่ได้
--}}
@php
    $firstUsable = $contacts->first(fn ($c) => empty($contactProblems[$c->id]));
    $defaultUsable = $contacts->first(fn ($c) => $c->is_default && empty($contactProblems[$c->id]));
    $startContact = optional($defaultUsable ?? $firstUsable)->id;
    $hasOldNew = old('first_name') !== null || old('address1') !== null;
    $startMode = (! $hasOldNew && $startContact) ? 'saved' : 'new';

    $initial = [
        'first_name' => old('first_name', ''),
        'last_name' => old('last_name', ''),
        'organization' => old('organization', ''),
        'email' => old('email', auth()->user()->email ?? ''),
        'phone_country_code' => old('phone_country_code', '+66'),
        'phone' => old('phone', ''),
        'address1' => old('address1', ''),
        'address2' => old('address2', ''),
        'city' => old('city', ''),
        'state' => old('state', ''),
        'zip' => old('zip', ''),
        'country' => old('country', 'TH'),
    ];

    $serverErrors = collect(['first_name', 'last_name', 'organization', 'email', 'phone_country_code', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'country'])
        ->mapWithKeys(fn ($f) => [$f => $errors->first($f)])
        ->filter()
        ->all();

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

<script>
    document.addEventListener('alpine:init', () => {
        // The same rules as App\Support\WhoisContact: change one, change both.
        const TITLE = /^(?:นางสาว|นาง|นาย|น\.ส\.|ด\.ช\.|ด\.ญ\.|mrs\.?|mr\.?|ms\.?|miss)\s+/iu;
        const ADDRESS_FORBIDDEN = /[~!@#$%^&*`+_=()|'"\[\]{}°º]/gu;
        const CITY_FORBIDDEN = /[~<>`!.@#$%^&*+_=()|'"\[\]{}°º\/\\-]/gu;
        const NAME_OK = /^[\p{L}\p{M}]+(?:[ -][\p{L}\p{M}]+)*$/u;
        const TH_DIGITS = { '๐': '0', '๑': '1', '๒': '2', '๓': '3', '๔': '4', '๕': '5', '๖': '6', '๗': '7', '๘': '8', '๙': '9' };
        const TH_ALIASES = {
            'กรุงเทพ': 'Bangkok', 'กรุงเทพฯ': 'Bangkok', 'กทม': 'Bangkok', 'bangkokmetropolis': 'Bangkok',
            'krungthep': 'Bangkok', 'krungthepmahanakhon': 'Bangkok', 'อยุธยา': 'Phra Nakhon Si Ayutthaya',
            'ayutthaya': 'Phra Nakhon Si Ayutthaya', 'โคราช': 'Nakhon Ratchasima', 'korat': 'Nakhon Ratchasima',
            'sukhothai': 'Sukhothai Thani', 'srisaket': 'Sisaket', 'srisaketh': 'Sisaket',
            'samutprakarn': 'Samut Prakan', 'chainath': 'Chai Nat', 'nongbualamphu': 'Nong Bua Lam Phu',
        };
        // Longest first, so "หมู่ที่" is not left as "ม.ที่".
        const ABBREVIATIONS = [
            ['หมู่บ้าน', 'มบ.'], ['หมู่ที่', 'ม.'], ['หมู่', 'ม.'], ['ซอย', 'ซ.'], ['ถนน', 'ถ.'],
            ['ตำบล', 'ต.'], ['อำเภอ', 'อ.'], ['ชั้นที่', 'ชั้น'], ['Moo ', 'M.'], ['Road', 'Rd.'], ['Street', 'St.'],
        ];

        const arabic = (v) => String(v ?? '').replace(/[๐-๙]/g, (d) => TH_DIGITS[d]);
        const squash = (v) => String(v ?? '').replace(/\s+/gu, ' ').trim();
        const regionKey = (v) => squash(v).toLowerCase()
            .replace(/^(?:จังหวัด|จ\.)\s*/u, '')
            .replace(/\s+(?:province|prefecture|state)$/u, '')
            .replace(/[\s.'’-]+/gu, '');

        Alpine.data('domainRegistrant', (cfg) => ({
            mode: cfg.mode,
            contactId: cfg.contactId,
            form: cfg.initial,
            countries: cfg.countries,
            postcodes: cfg.postcodes,
            account: cfg.account,
            addressMax: cfg.addressMax,
            serverErrors: cfg.serverErrors,
            touched: {},
            submitted: false,
            submitting: false,
            termsMissing: false,
            note: { name: '', zip: '', account: '' },

            init() {
                // A server error is shown until the person edits that field.
                Object.keys(this.serverErrors).forEach((f) => { this.touched[f] = true; });
                Object.keys(this.form).forEach((f) => {
                    this.$watch('form.' + f, () => { delete this.serverErrors[f]; });
                });
                this.$watch('form.zip', () => this.zipChanged());
                this.$watch('form.country', (now, before) => this.countryChanged(now, before));
            },

            get spec() { return this.countries[this.form.country] || null; },
            get regionOptions() {
                if (!this.spec) return [];
                return Object.entries(this.spec.regions).map(([value, label]) => ({ value, label }));
            },
            get phoneCodes() {
                return [...new Set(Object.values(this.countries).map((c) => '+' + c.phone_cc))];
            },

            // ---- cleaning, as the registrar will receive it ----
            cleanName(v) { return squash(v).replace(TITLE, '').trim(); },
            addressLine() {
                return squash(arabic((this.form.address1 || '') + ' ' + (this.form.address2 || ''))
                    .replace(/[\/\\]/g, '-').replace(ADDRESS_FORBIDDEN, ' '));
            },
            cityLine() {
                return squash(String(this.form.city || '').trim()
                    .replace(/^อ\.\s*/u, 'อำเภอ').replace(/^ข\.\s*/u, 'เขต').replace(CITY_FORBIDDEN, ' '));
            },
            phoneCc() {
                const d = arabic(this.form.phone_country_code).replace(/\D/g, '');
                return d || (this.spec ? this.spec.phone_cc : '');
            },
            phoneDigits() {
                let d = arabic(this.form.phone).replace(/\D/g, '');
                const cc = this.phoneCc();
                if (cc && d.startsWith(cc) && d.length > cc.length + 7) d = d.slice(cc.length);
                return d.replace(/^0+/, '');
            },
            zipValue() {
                let z = squash(arabic(this.form.zip)).toUpperCase();
                if (this.form.country === 'JP' && /^\d{7}$/.test(z)) z = z.slice(0, 3) + '-' + z.slice(3);
                if (this.form.country === 'GB' && !z.includes(' ') && z.length >= 5) z = z.slice(0, -3) + ' ' + z.slice(-3);
                if (this.form.country === 'HK') z = z.replace(/[^A-Z0-9]/g, '');
                return z;
            },
            region(value) {
                const spec = this.spec;
                const needle = regionKey(value ?? this.form.state);
                if (!spec || !needle) return null;
                for (const [registrar, label] of Object.entries(spec.regions)) {
                    if (regionKey(registrar) === needle || regionKey(label) === needle) return registrar;
                }
                if (this.form.country === 'TH' && TH_ALIASES[needle] && spec.regions[TH_ALIASES[needle]]) {
                    return TH_ALIASES[needle];
                }
                return null;
            },
            regionLabel(registrar) {
                if (!registrar || !this.spec) return '';
                const label = this.spec.regions[registrar];
                return label && label !== registrar ? label + ' · ' + registrar : registrar;
            },
            provinceForZip(zip) {
                if (!/^\d{5}$/.test(zip)) return null;
                return this.postcodes.exceptions[zip] || this.postcodes.prefixes[zip.slice(0, 2)] || null;
            },

            // ---- the rules ----
            get problems() {
                const p = {};
                const f = this.form;
                [['first_name', 'ชื่อ'], ['last_name', 'นามสกุล']].forEach(([key, label]) => {
                    const v = this.cleanName(f[key]);
                    if (!v) p[key] = 'กรุณากรอก' + label;
                    else if ([...v].length < 2 || [...v].length > 64) p[key] = label + 'ต้องยาว 2–64 ตัวอักษร';
                    else if (!NAME_OK.test(v)) p[key] = label + 'ใช้ได้เฉพาะตัวอักษรไทยหรืออังกฤษ ห้ามมีตัวเลขหรือเครื่องหมาย (ยกเว้นขีด -)';
                });
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(squash(f.email))) p.email = 'กรุณากรอกอีเมลให้ถูกต้อง เช่น name@gmail.com';
                const cc = this.phoneCc();
                if (!cc || cc.length > 4) p.phone_country_code = 'รหัสประเทศของเบอร์โทรไม่ถูกต้อง';
                const phone = this.phoneDigits();
                if (phone.length < 6 || phone.length > 14) p.phone = 'กรุณากรอกเบอร์โทรให้ครบ เช่น 081 234 5678';
                const address = this.addressLine();
                if (!address) p.address1 = 'กรุณากรอกที่อยู่';
                else if ([...address].length > this.addressMax) p.address1 = 'ที่อยู่รวมกันยาว ' + [...address].length + ' ตัวอักษร ทะเบียนรับได้ไม่เกิน ' + this.addressMax + ' — กดปุ่ม "ย่อให้อัตโนมัติ" ในกรอบด้านบน';
                else if (!/\d/.test(address)) p.address1 = 'ที่อยู่ต้องมีบ้านเลขที่ (ตัวเลข) อย่างน้อยหนึ่งตัว';
                else if (!/\p{L}/u.test(address)) p.address1 = 'ที่อยู่ต้องมีตัวอักษรด้วย เช่น ชื่อถนนหรือตำบล';
                const city = this.cityLine();
                if (!city) p.city = 'กรุณากรอกเขต/อำเภอ';
                if (!this.spec) {
                    p.country = 'ยังไม่รองรับประเทศนี้ กรุณาเลือกจากรายการ';
                    return p;
                }
                if (!this.region()) p.state = squash(f.state) ? 'ไม่พบ "' + squash(f.state) + '" ในรายการ กรุณาเลือกจากรายการที่ขึ้นมา' : 'กรุณาเลือก' + this.spec.state_label.th;
                const zipRule = new RegExp(this.spec.zip_pattern.slice(1, -1));
                if (!zipRule.test(this.zipValue())) p.zip = 'รหัสไปรษณีย์ไม่ถูกต้อง ตัวอย่างที่ถูก: ' + this.spec.zip_example;
                return p;
            },
            error(field) {
                if (this.serverErrors[field]) return this.serverErrors[field];
                return (this.touched[field] || this.submitted) ? (this.problems[field] || '') : '';
            },
            ok(field) {
                return (this.touched[field] || this.submitted) && !this.problems[field] && !this.serverErrors[field];
            },
            touch(field) { this.touched[field] = true; },

            // ---- helpers ----
            nameBlur(field) {
                const before = squash(this.form[field]);
                const after = this.cleanName(before);
                if (after !== before && before.length > after.length) {
                    this.form[field] = after;
                    this.note.name = 'ตัดคำนำหน้าชื่อออกให้แล้ว — ทะเบียนใช้ชื่อจริงเท่านั้น';
                }
                this.touch(field);
            },
            phoneBlur() {
                // "+66 81 234 5678" pasted or autofilled into the number box.
                const raw = squash(this.form.phone);
                const m = raw.match(/^\+(\d{1,4})[\s-]*(.*)$/);
                if (m && this.phoneCodes.includes('+' + m[1])) {
                    this.form.phone_country_code = '+' + m[1];
                    this.form.phone = m[2];
                }
                this.touch('phone');
            },
            get phonePreview() {
                const d = this.phoneDigits();
                return d ? '+' + this.phoneCc() + ' ' + d : '';
            },
            zipChanged() {
                if (this.form.country !== 'TH') return;
                const zip = arabic(this.form.zip).trim();
                const province = this.provinceForZip(zip);
                if (!province) { this.note.zip = ''; return; }
                const current = this.region();
                if (!current) {
                    this.form.state = this.spec.regions[province] || province;
                    this.note.zip = 'เลือกจังหวัด ' + this.regionLabel(province) + ' ให้จากรหัสไปรษณีย์แล้ว';
                } else if (current !== province) {
                    this.note.zip = 'mismatch:' + province;
                } else {
                    this.note.zip = '';
                }
            },
            useZipProvince() {
                const province = this.note.zip.replace('mismatch:', '');
                this.form.state = this.spec.regions[province] || province;
                this.note.zip = '';
            },
            countryChanged(now, before) {
                const prev = this.countries[before];
                const next = this.countries[now];
                // Move the calling code with the country, unless it was set by hand.
                if (next && (!prev || this.form.phone_country_code === '+' + prev.phone_cc)) {
                    this.form.phone_country_code = '+' + next.phone_cc;
                }
                if (!this.region()) this.form.state = '';
                this.note.zip = '';
            },
            fillFromAccount() {
                let filled = 0;
                ['first_name', 'last_name', 'email', 'phone'].forEach((f) => {
                    if (!squash(this.form[f]) && squash(this.account[f])) {
                        this.form[f] = this.account[f];
                        filled++;
                    }
                });
                this.note.account = filled
                    ? 'เติมจากบัญชีให้ ' + filled + ' ช่อง — ตรวจชื่อ-นามสกุลให้ตรงกับบัตรประชาชนอีกครั้ง'
                    : 'ช่องที่บัญชีมีข้อมูลถูกกรอกไว้แล้ว ไม่ได้เขียนทับ';
                if (this.phoneDigits()) this.phoneBlur();
            },
            get addressLength() { return [...this.addressLine()].length; },
            get addressHasSlash() { return /[\/\\]/.test((this.form.address1 || '') + (this.form.address2 || '')); },
            shortenAddress() {
                ['address1', 'address2'].forEach((f) => {
                    let v = this.form[f] || '';
                    ABBREVIATIONS.forEach(([from, to]) => { v = v.split(from).join(to); });
                    // "ถ. สุขุมวิท" → "ถ.สุขุมวิท": a Thai abbreviation needs no space.
                    this.form[f] = squash(v.replace(/([ก-ฮ])\.\s+/gu, '$1.'));
                });
                this.touch('address1');
            },

            onSubmit(event) {
                const form = event.target;
                const stop = (target) => {
                    event.preventDefault();
                    this.$nextTick(() => {
                        const bad = target || form.querySelector('[aria-invalid="true"]');
                        if (bad) {
                            bad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            bad.focus({ preventScroll: true });
                        }
                    });
                };
                if (this.submitting) {
                    event.preventDefault();
                    return;
                }
                if (this.mode === 'new') {
                    this.submitted = true;
                    if (Object.keys(this.problems).length) return stop();
                } else if (!this.contactId) {
                    return stop(form.querySelector('input[name=contact_id]:not(:disabled)'));
                }
                this.termsMissing = !this.$refs.terms.checked;
                if (this.termsMissing) return stop(this.$refs.terms);
                this.submitting = true;

                // A form left open past the session's lifetime carries a dead
                // token and comes back as 419 with everything typed lost. Ask
                // for a fresh one first; if that fails, send what we have.
                event.preventDefault();
                fetch(cfg.csrfUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' })
                    .then((r) => (r.ok ? r.json() : null))
                    .then((j) => { if (j && j.token) form.querySelector('input[name=_token]').value = j.token; })
                    .catch(() => {})
                    .finally(() => HTMLFormElement.prototype.submit.call(form));
            },
        }));
    });
</script>

<div class="bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <a href="{{ route('domains.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <x-bi th="ค้นหาชื่ออื่น" en="Search another name" />
        </a>

        @if (session('error'))
            <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm">
                <p class="font-semibold mb-1"><x-bi th="กรุณาตรวจสอบข้อมูลต่อไปนี้" en="Please check the following" /></p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- สรุปรายการ --}}
        <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 mb-6 shadow-xl relative overflow-hidden">
            <x-page-art art="hero-domains" :opacity="25" :scrim="false" />
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-indigo-300/80 text-xs font-semibold tracking-[0.2em] uppercase mb-1.5">
                        <x-bi th="กำลังจด" en="Registering" />
                    </p>
                    <p class="text-2xl sm:text-3xl font-bold break-all">{{ $domain }}</p>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">
                        <x-bi th="ราคาปีแรก" en="First year" />
                    </p>
                    <p class="text-3xl font-bold">{{ $priceDisplay }}</p>
                    <p class="text-sm text-indigo-200/70"><x-bi th="สำหรับ 1 ปี" en="for 1 year" /></p>
                </div>
            </div>
            {{-- ราคาปีต่อไปแสดงเสมอ ไม่ใช่เฉพาะตอนแพงกว่า — ลูกค้าต้องรู้ทั้งสองตัวเลข
                 ก่อนกดจ่าย และวันที่เราแจ้งเตือนอ่านจากหลังบ้าน ไม่เขียนตายว่า 30 วัน --}}
            <p class="relative mt-4 pt-4 border-t border-white/10 text-sm {{ $renewalIsDearer ? 'text-amber-200' : 'text-indigo-200/80' }}">
                <x-bi th="ปีต่อไป (ต่ออายุ)" en="Following years (renewal)" />
                <span class="font-bold text-white">{{ $renewDisplay }}</span>
                <x-bi th="ต่อปี" en="per year" />
                @if($renewalIsDearer)
                    <span class="block text-xs mt-1 text-amber-200/80">
                        <x-bi th="ราคาปีแรกเป็นราคาโปรโมชันของนามสกุลนี้ — เราแจ้งเตือนล่วงหน้าก่อนถึงกำหนดต่ออายุทุกครั้ง"
                              en="This extension's first year is promotional — we always remind you before a renewal is due." />
                    </span>
                @endif
            </p>
        </div>

        {{-- ยอดเงินไม่พอ: หยุดตรงนี้ ไม่ต้องให้กรอกฟอร์มยาวแล้วค่อยบอก --}}
        @if(! $sufficient)
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 p-6 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-amber-900 dark:text-amber-200 mb-1">
                            <x-bi th="ยอดเงินในกระเป๋าไม่พอ" en="Not enough in your wallet" />
                        </p>
                        <p class="text-sm text-amber-800 dark:text-amber-300/90 mb-3">
                            <x-bi th="ต้องใช้" en="Needs" /> <span class="font-semibold">{{ $priceDisplay }}</span> ·
                            <x-bi th="มีอยู่" en="you have" /> <span class="font-semibold">{{ $balanceDisplay }}</span> ·
                            <x-bi th="ขาดอีก" en="short by" /> <span class="font-semibold">{{ \App\Support\DomainPricing::format($shortfall) }}</span>
                        </p>
                        <a href="{{ route('user.wallet.index') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-sm transition">
                            <x-bi th="เติมเงิน" en="Top up" />
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('domains.register.store', $domain) }}" novalidate
              x-data="domainRegistrant(@js([
                  'mode' => $startMode,
                  'contactId' => $startContact,
                  'initial' => $initial,
                  'countries' => $whoisCountries,
                  'postcodes' => $thPostcodes,
                  'account' => $accountPrefill,
                  'addressMax' => $addressMax,
                  'serverErrors' => (object) $serverErrors,
                  'csrfUrl' => route('csrf.refresh'),
              ]))"
              @submit="onSubmit($event)">
            @csrf

            {{-- ผู้ถือครอง --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">
                            <x-bi th="ผู้ถือครองโดเมน" en="Domain registrant" />
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            <x-bi th="ชื่อนี้จะถูกบันทึกเป็นเจ้าของโดเมนตามทะเบียนสากล และใช้ยืนยันสิทธิ์เวลาย้ายโดเมน กรุณากรอกข้อมูลจริง"
                                  en="This goes on the international registry as the domain's owner, and is what proves it is yours if you move it. Please use real details."
                                  layout="stack" />
                        </p>
                    </div>
                    <button type="button" x-show="mode === 'new'" @click="fillFromAccount()"
                            class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-500/40 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-sm font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['user'] }}"/></svg>
                        <x-bi th="ใช้ข้อมูลจากบัญชีของฉัน" en="Use my account" />
                    </button>
                </div>

                <p x-show="note.account" x-text="note.account" x-cloak
                   class="mb-5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-200 text-sm px-4 py-3"></p>

                @if($contacts->isNotEmpty())
                    <div class="flex gap-2 mb-6 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-900/60">
                        <button type="button" @click="mode = 'saved'"
                                :class="mode === 'saved' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition">
                            <x-bi th="ใช้ข้อมูลที่บันทึกไว้" en="Use saved details" />
                        </button>
                        <button type="button" @click="mode = 'new'"
                                :class="mode === 'new' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition">
                            <x-bi th="กรอกใหม่" en="Enter new" />
                        </button>
                    </div>

                    <div x-show="mode === 'saved'" class="space-y-3" x-bind:inert="mode !== 'saved'">
                        @foreach($contacts as $c)
                            @php $broken = ! empty($contactProblems[$c->id]); @endphp
                            <label class="flex items-start gap-3 p-4 rounded-2xl border-2 transition {{ $broken ? 'opacity-70 cursor-not-allowed border-slate-200 dark:border-slate-700' : 'cursor-pointer' }}"
                                   @unless($broken) :class="contactId === {{ $c->id }} ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'" @endunless>
                                <input type="radio" name="contact_id" value="{{ $c->id }}" x-model.number="contactId"
                                       x-bind:disabled="{{ $broken ? 'true' : "mode !== 'saved'" }}" class="mt-1">
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900 dark:text-white">{{ $c->fullName() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->summary() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->email }}</span>
                                    @if($broken)
                                        <span class="mt-2 block text-sm text-amber-700 dark:text-amber-300">
                                            <x-bi th="ข้อมูลชุดนี้ยังไม่ครบตามที่ทะเบียนต้องการ — กรุณากดกรอกใหม่" en="Missing details the registry now needs — please enter new" />
                                            <span class="block text-xs mt-0.5">{{ $contactProblems[$c->id][0] }}</span>
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                {{-- ฟอร์มใหม่ — ตอนอยู่โหมด saved ต้อง disable ไม่ใช่แค่ซ่อน
                     ไม่งั้นค่าที่ค้างอยู่จะถูกส่งไปพร้อมกันและกฎ required_without
                     จะเห็นทั้งสองชุด --}}
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

                @if($extraFields)
                    <div class="mt-6 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 p-4 text-sm text-blue-900 dark:text-blue-200">
                        <x-bi th="นามสกุลนี้ต้องใช้เอกสารเพิ่มเติม ทีมงานจะติดต่อกลับหลังสั่งซื้อเพื่อขอข้อมูล"
                              en="This extension needs extra documents — we'll contact you after ordering to collect them." />
                    </div>
                @endif
            </div>

            {{-- ตัวเลือก --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7 mb-5 space-y-5">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                    <x-bi th="ตัวเลือก" en="Options" />
                </h2>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="privacy" value="1" @checked(old('privacy', true)) class="mt-1">
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">
                            <x-bi th="ปกปิดข้อมูลส่วนตัวใน WHOIS (ฟรี)" en="Hide my details in public WHOIS (free)" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ชื่อ ที่อยู่ และเบอร์โทรของคุณจะไม่แสดงในฐานข้อมูลสาธารณะ ลดสแปมและการถูกติดต่อโดยไม่ต้องการ"
                                  en="Your name, address and phone stay out of the public database — less spam, fewer cold calls." />
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', false)) class="mt-1">
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">
                            <x-bi th="ต่ออายุอัตโนมัติ" en="Renew automatically" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            {{-- The day count comes from settings. Written out, this sentence
                                 becomes a false promise the moment an operator moves it. --}}
                            <x-bi th="ตัดจากกระเป๋าเงินก่อนหมดอายุ {{ \App\Support\DomainReminders::chargeDays() }} วัน เราแจ้งล่วงหน้าทุกครั้ง และปิดได้ตลอดเวลา"
                                  en="Charged from your wallet {{ \App\Support\DomainReminders::chargeDays() }} days before expiry. We always warn you first, and you can turn it off any time." />
                        </span>
                    </span>
                </label>
            </div>

            {{-- ยืนยัน --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7">
                <label class="flex items-start gap-3 cursor-pointer mb-5 rounded-xl p-3 -m-3 transition"
                       :class="termsMissing ? 'bg-red-50 dark:bg-red-500/10 ring-2 ring-red-300 dark:ring-red-500/40' : ''">
                    <input type="checkbox" name="accept_terms" value="1" required x-ref="terms"
                           @change="termsMissing = false" class="mt-1">
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        <x-bi th="ข้าพเจ้ายืนยันว่าข้อมูลผู้ถือครองเป็นความจริง และรับทราบว่าค่าจดทะเบียนโดเมนไม่สามารถขอคืนได้หลังจดสำเร็จ ตามข้อกำหนดของผู้ดูแลทะเบียนโดเมนสากล"
                              en="I confirm the registrant details are accurate, and understand that domain registration fees are non-refundable once the name is registered, per international registry rules."
                              layout="stack" />
                        <span x-show="termsMissing" x-cloak class="field-error">
                            <x-bi th="กรุณาติ๊กยืนยันก่อนกดจด" en="Please tick to confirm first" />
                        </span>
                    </span>
                </label>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-5 border-t border-slate-200 dark:border-slate-700">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><x-bi th="ยอดชำระวันนี้ (ปีแรก)" en="Due today (first year)" /></p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $priceDisplay }}</p>
                        <p class="text-xs {{ $renewalIsDearer ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">
                            <x-bi th="ปีต่อไป" en="Following years" /> {{ $renewDisplay }}/<x-bi th="ปี" en="yr" />
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <x-bi th="หักจากกระเป๋าเงิน คงเหลือ" en="From your wallet — balance" /> {{ $balanceDisplay }}
                        </p>
                    </div>

                    {{-- กดซ้ำไม่ได้: ปุ่มถูกปิดทันทีที่ส่ง และฝั่งเซิร์ฟเวอร์ยัง
                         มีคีย์กันซ้ำอีกชั้นเผื่อ JS ไม่ทำงาน --}}
                    <button type="submit"
                            @disabled(! $sufficient)
                            x-bind:disabled="submitting || @js(! $sufficient)"
                            class="px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-lg font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <span x-show="!submitting">
                            <x-bi th="ยืนยันและจดโดเมน" en="Confirm and register" />
                        </span>
                        <span x-show="submitting" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <x-bi th="กำลังดำเนินการ อย่าปิดหน้านี้" en="Working — don't close this page" />
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
