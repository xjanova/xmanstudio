{{--
    ใบเสนอราคา A4 — เอกสารมาตรฐานของ XMAN Studio

    เรนเดอร์ด้วย DomPDF ซึ่ง **ไม่รองรับ flexbox และ grid** เลย์เอาต์ทั้งหน้าจึง
    ใช้ตารางกับ float เท่านั้น ถ้าเผลอใส่ display:flex เข้าไป มันจะไม่พังให้เห็น
    แต่กล่องจะเรียงทับกันเงียบ ๆ

    ฟอนต์ Sarabun ต้องเป็นไฟล์จริงใน storage/fonts เท่านั้น ไฟล์ปลอมเคยทำให้
    ภาษาไทยทั้งใบกลายเป็นสี่เหลี่ยมมาแล้ว — App\Support\FontFile ตรวจ magic bytes
    ให้ตอนบูต
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบเสนอราคา {{ $quotation['quote_number'] }}</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/Sarabun-Regular.ttf') }}) format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: bold;
            src: url({{ storage_path('fonts/Sarabun-Bold.ttf') }}) format('truetype');
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 0; }

        body {
            font-family: 'Sarabun', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.55;
            color: #1f2937;
            background: #fff;
        }

        .sheet { padding: 34px 40px 30px 40px; }

        /* ── หัวกระดาษ ───────────────────────────────── */
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        /* โลโก้จริงเป็นเวิร์ดมาร์กแนวนอน (919x243) จึงล็อกความสูงแล้วปล่อยกว้างเอง */
        .brand-logo { height: 34px; width: auto; }
        /* ใช้เฉพาะตอนที่ยังไม่ได้อัปโหลดโลโก้ในหน้าแอดมิน */
        .brand-mark {
            width: 42px; height: 42px; background: #1d4ed8; color: #fff;
            text-align: center; font-size: 22px; font-weight: bold;
            line-height: 42px; border-radius: 8px;
        }
        .brand-name { font-size: 17px; font-weight: bold; letter-spacing: .04em; color: #111827; }
        .brand-sub { font-size: 10.5px; color: #4b5563; }
        .brand-lines { font-size: 10px; color: #4b5563; line-height: 1.5; padding-top: 5px; }

        .doc-title { font-size: 25px; font-weight: bold; color: #1d4ed8; text-align: right; line-height: 1.1; }
        .doc-title-en { font-size: 10.5px; font-weight: bold; letter-spacing: .2em; color: #6b7280; text-align: right; }
        .meta { border-collapse: collapse; float: right; margin-top: 9px; }
        .meta td { font-size: 10.5px; padding: 1px 0 1px 8px; text-align: right; }
        .meta .k { color: #4b5563; }
        .meta .v { font-weight: bold; color: #111827; }

        .rule { border-bottom: 2.5px solid #1d4ed8; margin: 12px 0 14px 0; }

        /* ── กล่องข้อมูล ─────────────────────────────── */
        .party { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin-left: -12px; width: 103%; }
        .party td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 11px; }
        .box-label { font-size: 9px; font-weight: bold; letter-spacing: .09em; color: #1d4ed8; padding-bottom: 3px; }
        .box-name { font-size: 13px; font-weight: bold; color: #111827; }
        .box-lines { font-size: 10.5px; color: #374151; line-height: 1.55; padding-top: 2px; }

        /* ── รายการ ──────────────────────────────────── */
        .items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items th {
            background: #1d4ed8; color: #fff; font-size: 10px; font-weight: bold;
            padding: 7px 8px; border: 1px solid #1d4ed8;
        }
        .items td { padding: 7px 8px; border: 1px solid #d1d5db; font-size: 11.5px; vertical-align: top; }
        .items tr.alt td { background: #f9fafb; }
        .it-name { font-weight: bold; color: #111827; }
        .it-desc { font-size: 10px; color: #4b5563; }
        .num { text-align: right; }
        .mid { text-align: center; }

        /* ── สรุปยอด ─────────────────────────────────── */
        .foot { width: 100%; border-collapse: collapse; margin-top: 13px; }
        .foot > tbody > tr > td { vertical-align: top; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { font-size: 11.5px; padding: 4px 9px; }
        .totals .k { text-align: right; color: #374151; }
        .totals .v { text-align: right; width: 108px; color: #111827; }
        .totals .sep td { border-top: 1px solid #d1d5db; }
        .totals .grand td { background: #1d4ed8; color: #fff; font-weight: bold; font-size: 13px; padding: 8px 9px; }
        .totals .muted td { color: #6b7280; font-size: 10.5px; }

        .words { margin-top: 9px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 6px 11px; }
        .words-k { font-size: 10px; font-weight: bold; color: #1e40af; }
        .words-v { font-size: 12px; font-weight: bold; color: #1e3a8a; }

        /* ── เงื่อนไข + ลายเซ็น ──────────────────────── */
        .terms-label { font-size: 9px; font-weight: bold; letter-spacing: .09em; color: #1d4ed8; padding-bottom: 3px; }
        .terms { font-size: 10px; color: #374151; line-height: 1.65; padding-left: 14px; }
        .sign { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
        .sign td { width: 50%; text-align: center; vertical-align: bottom; }
        .sign-space { height: 40px; }
        .sign-line { border-top: 1px solid #6b7280; padding-top: 4px; font-size: 10px; color: #4b5563; }
        .sign-date { font-size: 9.5px; color: #6b7280; }

        .tail {
            margin-top: 11px; padding-top: 7px; border-top: 1px solid #e5e7eb;
            font-size: 9px; color: #4b5563;
        }
    </style>
</head>
<body>
@php
    $c = $companyInfo;
    $q = $quotation;
    $mode = $q['vat_mode'] ?? 'exclusive';
    $rate = rtrim(rtrim(number_format((float) ($q['vat_rate'] ?? 7), 2), '0'), '.');
    $money = fn ($n) => number_format((float) $n, 2);

    // งวดชำระมาตรฐาน 50/25/25 คิดจากยอดสุทธิ แล้วให้งวดแรกรับเศษ
    // ไม่งั้นสามงวดรวมกันขาดไปหนึ่งสตางค์
    $g = (float) $q['grand_total'];
    $p2 = round($g * 0.25, 2);
    $p3 = round($g * 0.25, 2);
    $p1 = round($g - $p2 - $p3, 2);
@endphp

<div class="sheet">

    @if (!empty($isPreview))
        {{-- ดูก่อนส่ง — ต้องบอกให้ชัดว่ายังไม่ใช่เอกสารจริง ไม่งั้นมีคนเซฟไปใช้ตั้งเบิก --}}
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:6px; padding:7px 12px; margin-bottom:12px;">
            <span style="font-size:11px; font-weight:bold; color:#92400e;">ตัวอย่างเอกสาร</span>
            <span style="font-size:11px; color:#92400e;"> &nbsp;— ยังไม่ได้ออกเลขที่จริง ยังไม่มีผลผูกพัน กดส่งเพื่อรับใบเสนอราคาฉบับจริงทางอีเมล</span>
        </div>
    @endif

    {{-- ══ หัวกระดาษ ══ --}}
    <table class="head">
        <tr>
            <td style="width: 58%;">
                @if (!empty($c['logo_path']))
                    {{-- โลโก้ที่อัปโหลดไว้จริงในหน้าแอดมิน (setting `site_logo`)
                         ส่งเป็น path บนดิสก์ ไม่ใช่ URL — DomPDF ดึง URL ไม่ได้ถ้าไม่เปิด
                         isRemoteEnabled และถึงเปิดก็ช้าและพังง่ายกว่า --}}
                    <img src="{{ $c['logo_path'] }}" alt="{{ $c['name'] }}" class="brand-logo">
                    <div class="brand-lines" style="padding-top: 7px;">
                        @if ($c['address']){{ $c['address'] }}<br>@endif
                        @if ($c['phone'])โทร {{ $c['phone'] }}@endif
                        @if ($c['phone'] && $c['email']) · @endif
                        @if ($c['email']){{ $c['email'] }}@endif
                        @if ($c['line'])<br>LINE {{ $c['line'] }}@endif
                        @if ($c['tax_id'])<br>เลขประจำตัวผู้เสียภาษี {{ $c['tax_id'] }}@endif
                    </div>
                @else
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 42px; vertical-align: top;"><div class="brand-mark">X</div></td>
                        <td style="padding-left: 11px; vertical-align: top;">
                            <div class="brand-name">{{ $c['name'] }}</div>
                            <div class="brand-sub">{{ $c['tagline'] }}</div>
                            <div class="brand-lines">
                                @if ($c['address']){{ $c['address'] }}<br>@endif
                                @if ($c['phone'])โทร {{ $c['phone'] }}@endif
                                @if ($c['phone'] && $c['email']) · @endif
                                @if ($c['email']){{ $c['email'] }}@endif
                                @if ($c['line'])<br>LINE {{ $c['line'] }}@endif
                                {{-- เลขผู้เสียภาษีจะขึ้นก็ต่อเมื่อตั้งค่าไว้จริง
                                     เลขปลอมบนเอกสารภาษีแย่กว่าไม่มีเลข --}}
                                @if ($c['tax_id'])<br>เลขประจำตัวผู้เสียภาษี {{ $c['tax_id'] }}@endif
                            </div>
                        </td>
                    </tr>
                </table>
                @endif
            </td>
            <td style="width: 42%;">
                <div class="doc-title">ใบเสนอราคา</div>
                <div class="doc-title-en">QUOTATION</div>
                <table class="meta">
                    <tr><td class="k">เลขที่</td><td class="v">{{ $q['quote_number'] }}</td></tr>
                    <tr><td class="k">วันที่</td><td class="v">{{ $q['quote_date'] }}</td></tr>
                    <tr><td class="k">ยืนราคาถึง</td><td class="v" style="color:#b45309;">{{ $q['valid_until'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="clear: both;"></div>
    <div class="rule"></div>

    {{-- ══ คู่สัญญา ══ --}}
    <table class="party">
        <tr>
            <td>
                <div class="box">
                    <div class="box-label">เสนอราคาแก่</div>
                    <div class="box-name">{{ $q['customer']['company'] ?: $q['customer']['name'] }}</div>
                    <div class="box-lines">
                        @if ($q['customer']['company']){{ $q['customer']['name'] }}<br>@endif
                        @if ($q['customer']['address']){{ $q['customer']['address'] }}<br>@endif
                        {{ $q['customer']['phone'] }} · {{ $q['customer']['email'] }}
                        @if (!empty($q['customer']['tax_id']))<br>เลขผู้เสียภาษี {{ $q['customer']['tax_id'] }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="box-label">รายละเอียดงาน</div>
                    <div class="box-name">{{ $q['service']['name_th'] ?: $q['service']['name'] }}</div>
                    <div class="box-lines">
                        ระยะเวลา:
                        @switch($q['timeline'])
                            @case('urgent') เร่งด่วน @break
                            @case('flexible') ยืดหยุ่นได้ @break
                            @default ตามปกติ
                        @endswitch
                        @if (!empty($q['project_description']))
                            <br>{{ \Illuminate\Support\Str::limit(trim($q['project_description']), 150) }}
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ รายการ ══ --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="text-align: left;">รายการ</th>
                <th style="width: 48px;">จำนวน</th>
                <th style="width: 92px;">ราคา/หน่วย</th>
                <th style="width: 100px;">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($q['items'] as $i => $item)
                <tr class="{{ $i % 2 ? 'alt' : '' }}">
                    <td class="mid">{{ $i + 1 }}</td>
                    <td>
                        <div class="it-name">{{ $item['name_th'] ?? $item['name'] ?? '-' }}</div>
                        @if (!empty($item['description_th']) || !empty($item['description']))
                            <div class="it-desc">{{ $item['description_th'] ?? $item['description'] }}</div>
                        @endif
                    </td>
                    <td class="mid">1</td>
                    <td class="num">{{ $money($item['price'] ?? 0) }}</td>
                    <td class="num" style="font-weight: bold;">{{ $money($item['price'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="mid" style="color:#6b7280; padding: 16px;">ไม่มีรายการ</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ══ งวดชำระ + สรุปยอด ══ --}}
    <table class="foot">
        <tr>
            <td style="width: 54%; padding-right: 12px;">
                <div class="box">
                    <div class="box-label">งวดการชำระเงิน</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5px; color: #374151;">
                        <tr><td style="padding: 2px 0;">งวดที่ 1 · เริ่มงาน</td><td class="num">50%</td><td class="num" style="width: 78px;">{{ $money($p1) }}</td></tr>
                        <tr><td style="padding: 2px 0;">งวดที่ 2 · ส่งมอบงานออกแบบ</td><td class="num">25%</td><td class="num">{{ $money($p2) }}</td></tr>
                        <tr><td style="padding: 2px 0;">งวดที่ 3 · ส่งมอบระบบ</td><td class="num">25%</td><td class="num">{{ $money($p3) }}</td></tr>
                    </table>
                </div>
            </td>
            <td style="width: 46%;">
                <table class="totals">
                    <tr>
                        <td class="k">รวมเป็นเงิน</td>
                        <td class="v">{{ $money($q['subtotal']) }}</td>
                    </tr>

                    @if ($q['discount'] > 0)
                        <tr>
                            <td class="k" style="color:#15803d;">ส่วนลดขนาดงาน {{ $q['discount_percent'] }}%</td>
                            <td class="v" style="color:#15803d;">&minus;{{ $money($q['discount']) }}</td>
                        </tr>
                    @endif

                    @if ($q['rush_fee'] > 0)
                        <tr>
                            <td class="k" style="color:#b45309;">ค่าเร่งงาน 25%</td>
                            <td class="v" style="color:#b45309;">{{ $money($q['rush_fee']) }}</td>
                        </tr>
                    @endif

                    {{-- ราคารวม VAT แล้ว: ต้องโชว์ "มูลค่าสินค้า/บริการ" ที่ถอดออกมา
                         ไม่ใช่ยอดก่อนภาษีที่ลูกค้าไม่เคยเห็น --}}
                    @if ($mode === 'inclusive')
                        <tr class="sep">
                            <td class="k">มูลค่าสินค้า/บริการ</td>
                            <td class="v">{{ $money($q['amount_before_vat']) }}</td>
                        </tr>
                        <tr>
                            <td class="k">ภาษีมูลค่าเพิ่ม {{ $rate }}%</td>
                            <td class="v">{{ $money($q['vat']) }}</td>
                        </tr>
                    @elseif ($mode === 'exclusive')
                        @if ($q['discount'] > 0 || $q['rush_fee'] > 0)
                            <tr class="sep">
                                <td class="k">ราคาหลังหักส่วนลด</td>
                                <td class="v">{{ $money($q['amount_before_vat']) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="k">ภาษีมูลค่าเพิ่ม {{ $rate }}%</td>
                            <td class="v">{{ $money($q['vat']) }}</td>
                        </tr>
                    @else
                        <tr class="muted">
                            <td class="k" colspan="2" style="text-align: right;">ราคานี้ไม่มีภาษีมูลค่าเพิ่ม</td>
                        </tr>
                    @endif

                    <tr class="grand">
                        <td style="text-align: right;">จำนวนเงินรวมทั้งสิ้น</td>
                        <td style="text-align: right;">{{ $money($q['grand_total']) }}</td>
                    </tr>

                    @if ($q['withholding_amount'] > 0)
                        <tr class="muted">
                            <td class="k">หัก ณ ที่จ่าย {{ rtrim(rtrim(number_format((float) $q['withholding_pct'], 2), '0'), '.') }}%</td>
                            <td class="v">&minus;{{ $money($q['withholding_amount']) }}</td>
                        </tr>
                        <tr>
                            <td class="k" style="font-weight: bold;">ยอดโอนสุทธิ</td>
                            <td class="v" style="font-weight: bold;">{{ $money($q['net_payable']) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="words">
        <span class="words-k">ตัวอักษร</span>
        <span class="words-v">&nbsp; ( {{ $q['grand_total_words'] }} )</span>
    </div>

    {{-- ══ เงื่อนไข + ลายเซ็น ══ --}}
    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 14px;">
                <div class="terms-label">เงื่อนไข</div>
                <ol class="terms">
                    <li>ราคานี้ยืนยัน 30 วันนับจากวันที่ออกเอกสาร</li>
                    <li>ราคารวมการแก้ไขตามขอบเขตงาน 2 รอบต่อการส่งมอบแต่ละงวด</li>
                    <li>งานนอกขอบเขตคิดเพิ่มตามที่ตกลงเป็นลายลักษณ์อักษรก่อนเริ่มทำ</li>
                    <li>ค่าโดเมน ค่าเช่าเซิร์ฟเวอร์ และค่าบริการภายนอกรายปี ลูกค้าเป็นผู้รับผิดชอบ</li>
                    <li>หักภาษี ณ ที่จ่ายตามที่กฎหมายกำหนด กรุณาแนบหนังสือรับรองการหักภาษี</li>
                </ol>
            </td>
            <td style="width: 42%; vertical-align: bottom;">
                <table class="sign">
                    <tr>
                        <td>
                            <div class="sign-space"></div>
                            <div class="sign-line">ผู้เสนอราคา</div>
                            <div class="sign-date">วันที่ ...... / ...... / ......</div>
                        </td>
                        <td>
                            <div class="sign-space"></div>
                            <div class="sign-line">ผู้อนุมัติสั่งซื้อ</div>
                            <div class="sign-date">วันที่ ...... / ...... / ......</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="tail">
        @if (!empty($q['public_url']))
            เอกสารนี้ออกโดยระบบอัตโนมัติ ตรวจสอบสถานะและตอบรับได้ที่ {{ $q['public_url'] }}
        @else
            เอกสารนี้ออกโดยระบบอัตโนมัติจาก {{ $c['website'] }}
        @endif
    </div>

</div>
</body>
</html>
