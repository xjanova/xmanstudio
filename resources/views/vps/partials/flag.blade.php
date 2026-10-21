{{--
    ธงประเทศแบบ SVG ในตัว — ใช้แทนอีโมจิธง เพราะ Windows ไม่วาดอีโมจิธง
    (ลูกค้าเห็นเป็นตัวอักษร "MY" "SG" เฉย ๆ) และลูกค้าส่วนใหญ่ใช้ Windows

    @include('vps.partials.flag', ['country' => 'my', 'class' => 'w-6 h-4'])

    ทุกธงวาดในกรอบ 3:2 เพื่อให้เรียงกันแล้วเท่ากัน ธงที่ไม่มีในรายการจะเป็น
    ป้ายรหัสประเทศตัวพิมพ์ใหญ่ ไม่มี id ภายใน SVG (ไม่ใช้ clipPath / gradient)
    จึงวางซ้ำกี่ครั้งในหน้าเดียวก็ได้ และแสดงได้แม้อยู่ใต้ x-show ที่ซ่อนอยู่
--}}
@php
    $flagCode = strtolower(trim((string) ($country ?? '')));
    $flagCode = $flagCode === 'uk' ? 'gb' : $flagCode;
    $flagCode = preg_match('/^[a-z]{2}$/', $flagCode) ? $flagCode : '';
    $flagClass = $class ?? 'w-6 h-4';
@endphp
@switch($flagCode)
    @case('my')
        {{-- มาเลเซีย: แถบแดงขาว 14 แถบ มุมน้ำเงิน จันทร์เสี้ยวและดาว 14 แฉกสีเหลือง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <path d="M0 0h30v1.43H0zM0 2.86h30v1.43H0zM0 5.71h30v1.43H0zM0 8.57h30V10H0zM0 11.43h30v1.43H0zM0 14.29h30v1.43H0zM0 17.14h30v1.43H0z" fill="#CC0001"/>
            <rect width="15" height="11.43" fill="#010066"/>
            <circle cx="5.9" cy="5.71" r="4.05" fill="#FC0"/>
            <circle cx="7.05" cy="5.71" r="3.45" fill="#010066"/>
            <polygon points="11.40,2.61 11.70,4.39 12.75,2.92 12.24,4.65 13.82,3.78 12.62,5.12 14.42,5.02 12.75,5.71 14.42,6.40 12.62,6.30 13.82,7.64 12.24,6.77 12.75,8.50 11.70,7.03 11.40,8.81 11.10,7.03 10.05,8.50 10.56,6.77 8.98,7.64 10.18,6.30 8.38,6.40 10.05,5.71 8.38,5.02 10.18,5.12 8.98,3.78 10.56,4.65 10.05,2.92 11.10,4.39" fill="#FC0"/>
        </svg>
        @break
    @case('sg')
        {{-- สิงคโปร์: แดงบน ขาวล่าง จันทร์เสี้ยวกับดาวห้าดวงสีขาว --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="30" height="10" fill="#EF3340"/>
            <circle cx="5.7" cy="5" r="3.5" fill="#fff"/>
            <circle cx="7.05" cy="5" r="3.3" fill="#EF3340"/>
            <g fill="#fff">
                <polygon points="9.35,2.78 9.50,3.20 9.94,3.21 9.59,3.48 9.71,3.90 9.35,3.65 8.99,3.90 9.11,3.48 8.76,3.21 9.20,3.20"/>
                <polygon points="10.82,3.85 10.97,4.27 11.41,4.28 11.06,4.55 11.19,4.97 10.82,4.72 10.46,4.97 10.59,4.55 10.23,4.28 10.68,4.27"/>
                <polygon points="10.26,5.58 10.41,6.00 10.85,6.01 10.50,6.28 10.63,6.71 10.26,6.45 9.90,6.71 10.02,6.28 9.67,6.01 10.11,6.00"/>
                <polygon points="8.44,5.58 8.59,6.00 9.03,6.01 8.68,6.28 8.80,6.71 8.44,6.45 8.07,6.71 8.20,6.28 7.85,6.01 8.29,6.00"/>
                <polygon points="7.88,3.85 8.02,4.27 8.47,4.28 8.11,4.55 8.24,4.97 7.88,4.72 7.51,4.97 7.64,4.55 7.29,4.28 7.73,4.27"/>
            </g>
        </svg>
        @break
    @case('in')
        {{-- อินเดีย: หญ้าฝรั่น ขาว เขียว และธรรมจักรสีน้ำเงินตรงกลาง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="30" height="6.67" fill="#FF9933"/>
            <rect y="13.33" width="30" height="6.67" fill="#138808"/>
            <circle cx="15" cy="10" r="2.85" fill="none" stroke="#000080" stroke-width=".45"/>
            <circle cx="15" cy="10" r=".6" fill="#000080"/>
            <path d="M15.7 10L17.75 10M15.68 10.18L17.66 10.71M15.61 10.35L17.38 11.38M15.49 10.49L16.94 11.94M15.35 10.61L16.38 12.38M15.18 10.68L15.71 12.66M15 10.7L15 12.75M14.82 10.68L14.29 12.66M14.65 10.61L13.62 12.38M14.51 10.49L13.06 11.94M14.39 10.35L12.62 11.38M14.32 10.18L12.34 10.71M14.3 10L12.25 10M14.32 9.82L12.34 9.29M14.39 9.65L12.62 8.62M14.51 9.51L13.06 8.06M14.65 9.39L13.62 7.62M14.82 9.32L14.29 7.34M15 9.3L15 7.25M15.18 9.32L15.71 7.34M15.35 9.39L16.38 7.62M15.49 9.51L16.94 8.06M15.61 9.65L17.38 8.62M15.68 9.82L17.66 9.29" stroke="#000080" stroke-width=".2"/>
        </svg>
        @break
    @case('id')
        {{-- อินโดนีเซีย: แดงบน ขาวล่าง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="30" height="10" fill="#CE1126"/>
        </svg>
        @break
    @case('jp')
        {{-- ญี่ปุ่น: วงกลมแดงบนพื้นขาว --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <circle cx="15" cy="10" r="6" fill="#BC002D"/>
        </svg>
        @break
    @case('de')
        {{-- เยอรมนี: ดำ แดง ทอง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="6.67" fill="#000"/>
            <rect y="6.67" width="30" height="6.67" fill="#DD0000"/>
            <rect y="13.33" width="30" height="6.67" fill="#FFCE00"/>
        </svg>
        @break
    @case('fr')
        {{-- ฝรั่งเศส: น้ำเงิน ขาว แดง แนวตั้ง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="10" height="20" fill="#0055A4"/>
            <rect x="20" width="10" height="20" fill="#EF4135"/>
        </svg>
        @break
    @case('gb')
        {{-- สหราชอาณาจักร: ยูเนียนแจ็ก --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 60 40" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            {{-- แถบแดงทแยงเยื้องไปข้างหนึ่ง วาดเป็นสี่เหลี่ยมด้านขนานแทน clipPath --}}
            <rect width="60" height="40" fill="#012169"/>
            <path d="M0 0L60 40M60 0L0 40" stroke="#fff" stroke-width="8"/>
            <path d="M30 20L60 40L61.48 37.78L31.48 17.78ZM30 20L0 40L1.48 42.22L31.48 22.22ZM30 20L0 0L-1.48 2.22L28.52 22.22ZM30 20L60 0L58.52 -2.22L28.52 17.78Z" fill="#C8102E"/>
            <path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="13.3"/>
            <path d="M30 0V40M0 20H60" stroke="#C8102E" stroke-width="8"/>
        </svg>
        @break
    @case('nl')
        {{-- เนเธอร์แลนด์: แดง ขาว น้ำเงิน --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="30" height="6.67" fill="#AE1C28"/>
            <rect y="13.33" width="30" height="6.67" fill="#21468B"/>
        </svg>
        @break
    @case('lt')
        {{-- ลิทัวเนีย: เหลือง เขียว แดง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="6.67" fill="#FDB913"/>
            <rect y="6.67" width="30" height="6.67" fill="#006A44"/>
            <rect y="13.33" width="30" height="6.67" fill="#C1272D"/>
        </svg>
        @break
    @case('us')
        {{-- สหรัฐอเมริกา: แถบแดงขาว 13 แถบ มุมน้ำเงินกับดาว 50 ดวง (จุดเรียงเป็นแถว) --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <path d="M0 0h30v1.54H0zM0 3.08h30v1.54H0zM0 6.15h30v1.54H0zM0 9.23h30v1.54H0zM0 12.31h30v1.54H0zM0 15.38h30v1.54H0zM0 18.46h30V20H0z" fill="#B22234"/>
            <rect width="12" height="10.77" fill="#3C3B6E"/>
            <g stroke="#fff" stroke-width=".72" stroke-linecap="round" stroke-dasharray="0 2" fill="none">
                <path d="M1 1.08H11.5"/><path d="M2 2.15H10.5"/><path d="M1 3.23H11.5"/>
                <path d="M2 4.31H10.5"/><path d="M1 5.38H11.5"/><path d="M2 6.46H10.5"/>
                <path d="M1 7.54H11.5"/><path d="M2 8.62H10.5"/><path d="M1 9.69H11.5"/>
            </g>
        </svg>
        @break
    @case('br')
        {{-- บราซิล: พื้นเขียว สี่เหลี่ยมขนมเปียกปูนเหลือง วงกลมน้ำเงินกับแถบขาว --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#009C3B"/>
            <polygon points="2.6,10 15,2.45 27.4,10 15,17.55" fill="#FFDF00"/>
            <circle cx="15" cy="10" r="4.9" fill="#002776"/>
            <path d="M10.25 8.9C13.4 8.2 17 8.9 19.75 11.1" stroke="#fff" stroke-width=".85" fill="none"/>
        </svg>
        @break
    @case('ca')
        {{-- แคนาดา: แถบแดงสองข้าง ใบเมเปิลแดงกลางพื้นขาว --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#fff"/>
            <rect width="7.5" height="20" fill="#D52B1E"/>
            <rect x="22.5" width="7.5" height="20" fill="#D52B1E"/>
            <path d="M15 4.1l1.05 2.05 1.2-.62-.4 3.02 1.75-1.9.4 1.08 1.9-.36-.62 2.08.82.36-2.9 2.36.34 1.08-2.75-.34.1 3.19h-.58l.1-3.19-2.75.34.34-1.08-2.9-2.36.82-.36-.62-2.08 1.9.36.4-1.08 1.75 1.9-.4-3.02 1.2.62z" fill="#D52B1E"/>
        </svg>
        @break
    @case('au')
        {{-- ออสเตรเลีย: ยูเนียนแจ็กที่มุม ดาวเครือจักรภพ และกางเขนใต้ --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#012169"/>
            {{-- มุมธงเป็น svg ซ้อน: ตัดส่วนที่ล้นขอบมุมให้เอง ไม่ต้องใช้ clipPath --}}
            <svg width="15" height="10" viewBox="0 0 60 40" preserveAspectRatio="none">
                <rect width="60" height="40" fill="#012169"/>
                <path d="M0 0L60 40M60 0L0 40" stroke="#fff" stroke-width="8"/>
                <path d="M30 20L60 40L61.48 37.78L31.48 17.78ZM30 20L0 40L1.48 42.22L31.48 22.22ZM30 20L0 0L-1.48 2.22L28.52 22.22ZM30 20L60 0L58.52 -2.22L28.52 17.78Z" fill="#C8102E"/>
                <path d="M30 0V40M0 20H60" stroke="#fff" stroke-width="13.3"/>
                <path d="M30 0V40M0 20H60" stroke="#C8102E" stroke-width="8"/>
            </svg>
            <g fill="#fff">
                <polygon points="7.50,12.00 8.09,13.78 9.85,13.13 8.82,14.70 10.42,15.67 8.56,15.84 8.80,17.70 7.50,16.35 6.20,17.70 6.44,15.84 4.58,15.67 6.18,14.70 5.15,13.13 6.91,13.78"/>
                <polygon points="22.50,2.15 22.74,2.89 23.48,2.62 23.05,3.27 23.72,3.68 22.94,3.75 23.04,4.53 22.50,3.96 21.96,4.53 22.06,3.75 21.28,3.68 21.95,3.27 21.52,2.62 22.26,2.89"/>
                <polygon points="22.50,15.25 22.76,16.05 23.56,15.76 23.09,16.46 23.82,16.90 22.97,16.98 23.09,17.82 22.50,17.21 21.91,17.82 22.03,16.98 21.18,16.90 21.91,16.46 21.44,15.76 22.24,16.05"/>
                <polygon points="18.90,7.75 19.14,8.49 19.88,8.22 19.45,8.87 20.12,9.28 19.34,9.35 19.44,10.13 18.90,9.56 18.36,10.13 18.46,9.35 17.68,9.28 18.35,8.87 17.92,8.22 18.66,8.49"/>
                <polygon points="26.00,6.40 26.23,7.11 26.94,6.85 26.53,7.48 27.17,7.87 26.42,7.94 26.52,8.68 26.00,8.14 25.48,8.68 25.58,7.94 24.83,7.87 25.47,7.48 25.06,6.85 25.77,7.11"/>
                <polygon points="24.20,10.55 24.36,10.97 24.82,11.00 24.47,11.29 24.58,11.73 24.20,11.48 23.82,11.73 23.93,11.29 23.58,11.00 24.04,10.97"/>
            </g>
        </svg>
        @break
    @case('th')
        {{-- ไทย: แดง ขาว น้ำเงิน (กว้างสองเท่า) ขาว แดง --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
            <rect width="30" height="20" fill="#A51931"/>
            <rect y="3.33" width="30" height="13.34" fill="#F4F5F8"/>
            <rect y="6.67" width="30" height="6.66" fill="#2D2A4A"/>
        </svg>
        @break
    @default
        {{-- ประเทศที่ไม่มีธงในรายการ: ป้ายรหัสประเทศ (ไม่มีรหัสก็เป็นรูปโลก) --}}
        <svg class="{{ $flagClass }} block shrink-0 rounded-[3px] ring-1 ring-black/10 dark:ring-white/15 text-slate-500 dark:text-slate-300" viewBox="0 0 30 20" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
            <rect width="30" height="20" class="fill-slate-200 dark:fill-slate-700"/>
            @if($flagCode !== '')
                <text x="15" y="10" dy=".36em" text-anchor="middle" font-size="9.5" font-weight="700" letter-spacing=".4" fill="currentColor" font-family="Inter, ui-sans-serif, system-ui, sans-serif">{{ strtoupper($flagCode) }}</text>
            @else
                <g fill="none" stroke="currentColor" stroke-width="1.1">
                    <circle cx="15" cy="10" r="5.6"/>
                    <path d="M9.4 10h11.2M15 4.4c1.5 1.6 2.2 3.5 2.2 5.6s-.7 4-2.2 5.6c-1.5-1.6-2.2-3.5-2.2-5.6s.7-4 2.2-5.6z"/>
                </g>
            @endif
        </svg>
@endswitch
