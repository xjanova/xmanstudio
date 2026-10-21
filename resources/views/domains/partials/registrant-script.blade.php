{{--
    Alpine component "domainRegistrant" — กฎเดียวกับ App\Support\WhoisContact ฝั่งเซิร์ฟเวอร์
    ใช้ทั้งหน้าจดโดเมนและหน้าแก้ไขผู้ถือครอง: แก้ที่นี่ที่เดียว
--}}
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
