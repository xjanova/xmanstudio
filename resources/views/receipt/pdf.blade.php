{{--
    ใบเสร็จรับเงินของคำสั่งซื้อในร้าน A4 — แบบเดียวกับใบเสนอราคาและใบแจ้งหนี้

    เรนเดอร์ด้วย DomPDF ซึ่ง **ไม่รองรับ flexbox และ grid** เลย์เอาต์ทั้งหน้าจึง
    ใช้ตารางเท่านั้น ถ้าเผลอใส่ display:flex กล่องจะเรียงทับกันเงียบ ๆ ไม่ฟ้อง

    ตัวเลขทุกตัวมาจากแถว orders / order_items (App\Support\OrderReceipt::data)
    ไม่ได้คำนวณใหม่ตอนพิมพ์ — ใบที่อยู่ในอีเมลลูกค้าแล้ว โหลดซ้ำเมื่อไรต้องได้ตัวเลขเดิม

    ออกผ่าน App\Support\ThaiPdf เท่านั้น และต้องฝังฟอนต์ Sarabun-PUA ไม่งั้น
    วรรณยุกต์ซ้อนสระ (ดู docs/QUOTATION_SYSTEM.md ข้อ 8.6)
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบเสร็จรับเงิน / Receipt {{ $doc['number'] }}</title>
    <style>
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/Sarabun-PUA-Regular.ttf') }}) format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: bold;
            src: url({{ storage_path('fonts/Sarabun-PUA-Bold.ttf') }}) format('truetype');
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

        /* แถบสีบนสุดของกระดาษ — บอกชนิดเอกสารได้ตั้งแต่ยังไม่อ่าน */
        .band { height: 7px; background: #047857; }
        .band-thin { height: 2px; background: #6ee7b7; }

        .sheet { padding: 28px 40px 26px 40px; }

        /* ── หัวกระดาษ ───────────────────────────────── */
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .brand-logo { height: 34px; width: auto; }
        .brand-mark {
            width: 42px; height: 42px; background: #047857; color: #fff;
            text-align: center; font-size: 22px; font-weight: bold;
            line-height: 1; padding-top: 7px; border-radius: 8px;
        }
        .brand-name { font-size: 17px; font-weight: bold; letter-spacing: .04em; color: #111827; }
        .brand-sub { font-size: 10.5px; color: #4b5563; }
        .brand-lines { font-size: 10px; color: #4b5563; line-height: 1.5; padding-top: 5px; }

        .doc-title { font-size: 25px; font-weight: bold; color: #047857; text-align: right; line-height: 1.1; }
        .doc-title-en { font-size: 10.5px; font-weight: bold; letter-spacing: .2em; color: #6b7280; text-align: right; }
        .meta { border-collapse: collapse; float: right; margin-top: 9px; }
        .meta td { font-size: 10.5px; padding: 1px 0 1px 8px; text-align: right; }
        .meta .k { color: #4b5563; }
        .meta .v { font-weight: bold; color: #111827; }

        .rule { border-bottom: 2.5px solid #047857; margin: 12px 0 14px 0; }

        /* ── กล่องข้อมูล ─────────────────────────────── */
        .party { border-collapse: separate; border-spacing: 12px 0; margin-left: -12px; width: 103%; }
        .party td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 11px; }
        .box-label { font-size: 9px; font-weight: bold; color: #047857; padding-bottom: 3px; }
        .box-name { font-size: 13px; font-weight: bold; color: #111827; }
        .box-lines { font-size: 10.5px; color: #374151; line-height: 1.55; padding-top: 2px; }
        .kv { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .kv td { padding: 1px 0; vertical-align: top; }
        .kv .k { color: #6b7280; width: 42%; }
        .kv .v { color: #111827; font-weight: bold; text-align: right; }

        /* ── รายการ ──────────────────────────────────── */
        .items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items th {
            background: #047857; color: #fff; font-size: 10px; font-weight: bold;
            padding: 7px 8px; border: 1px solid #047857;
        }
        .items td { padding: 7px 8px; border: 1px solid #d1d5db; font-size: 11.5px; vertical-align: top; }
        .items tr.alt td { background: #f9fafb; }
        .it-name { font-weight: bold; color: #111827; }
        .it-desc { font-size: 9.5px; color: #6b7280; }
        .num { text-align: right; }
        .mid { text-align: center; }

        /* ── สรุปยอด ─────────────────────────────────── */
        .foot { width: 100%; border-collapse: collapse; margin-top: 13px; }
        .foot > tbody > tr > td { vertical-align: top; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { font-size: 11.5px; padding: 4px 9px; }
        .totals .k { text-align: right; color: #374151; }
        .totals .v { text-align: right; width: 108px; color: #111827; }
        .totals .grand td { background: #047857; color: #fff; font-weight: bold; font-size: 13px; padding: 8px 9px; }
        .totals .muted td { color: #6b7280; font-size: 10px; }

        .words { margin-top: 9px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 6px 11px; }
        .words-k { font-size: 10px; font-weight: bold; color: #047857; }
        .words-v { font-size: 12px; font-weight: bold; color: #064e3b; }

        /* ตราประทับ "ชำระเงินแล้ว" — ตัวบอกว่าเอกสารนี้คือหลักฐานการรับเงิน */
        .stamp {
            border: 2.5px solid #059669; border-radius: 8px;
            padding: 7px 10px; text-align: center; color: #059669;
        }
        .stamp-big { font-size: 20px; font-weight: bold; letter-spacing: .06em; line-height: 1.15; }
        .stamp-en { font-size: 9.5px; font-weight: bold; letter-spacing: .3em; }
        .stamp-date { font-size: 10px; color: #047857; padding-top: 2px; }

        /* ── หมายเหตุ + ลายเซ็น ──────────────────────── */
        .terms-label { font-size: 9px; font-weight: bold; color: #047857; padding-bottom: 3px; }
        .terms { font-size: 10px; color: #374151; line-height: 1.65; padding-left: 14px; }
        .sign { width: 100%; border-collapse: collapse; }
        .sign td { text-align: center; vertical-align: bottom; }
        .sign-space { height: 40px; }
        .sign-line { border-top: 1px solid #6b7280; padding-top: 4px; font-size: 10px; color: #4b5563; margin: 0 14px; }
        .sign-date { font-size: 9.5px; color: #6b7280; }

        .tail {
            margin-top: 12px; padding-top: 7px; border-top: 1px solid #e5e7eb;
            font-size: 9px; color: #4b5563;
        }
        .thanks { text-align: center; font-size: 12px; font-weight: bold; color: #047857; margin-top: 14px; }
        .thanks .en { color: #059669; }

        /* ── สองภาษา: ไทยนำ อังกฤษตามด้วยตัวเล็กสีจาง ── */
        .en { font-size: .82em; font-weight: normal; color: #6b7280; }
        .en-line { display: block; font-size: 8.5px; font-weight: normal; letter-spacing: .02em; }
        .items th .en-line { color: #d1fae5; }
        .totals .grand .en { color: #d1fae5; }
        .box-label .en { color: #6b7280; letter-spacing: .04em; }
    </style>
</head>
<body>
@php
    $c = $companyInfo;
    $money = fn ($n) => number_format((float) $n, 2);
@endphp

<div class="band"></div>
<div class="band-thin"></div>

<div class="sheet">

    {{-- ══ หัวกระดาษ ══ --}}
    <table class="head">
        <tr>
            <td style="width: 58%;">
                @if (!empty($c['logo_path']))
                    {{-- path บนดิสก์ ไม่ใช่ URL — DomPDF ดึง URL ไม่ได้ถ้าไม่เปิด isRemoteEnabled --}}
                    <img src="{{ $c['logo_path'] }}" alt="{{ $c['name'] }}" class="brand-logo">
                    <div class="brand-lines" style="padding-top: 7px;">
                        @if ($c['address']){{ $c['address'] }}<br>@endif
                        @if ($c['phone'])โทร/Tel. {{ $c['phone'] }}@endif
                        @if ($c['phone'] && $c['email']) · @endif
                        @if ($c['email']){{ $c['email'] }}@endif
                        @if ($c['line'])<br>LINE {{ $c['line'] }}@endif
                        @if ($c['tax_id'])<br>เลขประจำตัวผู้เสียภาษี / Tax ID {{ $c['tax_id'] }}@endif
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
                                    @if ($c['phone'])โทร/Tel. {{ $c['phone'] }}@endif
                                    @if ($c['phone'] && $c['email']) · @endif
                                    @if ($c['email']){{ $c['email'] }}@endif
                                    @if ($c['line'])<br>LINE {{ $c['line'] }}@endif
                                    {{-- เลขผู้เสียภาษีขึ้นเฉพาะเมื่อตั้งค่าไว้จริง เลขปลอมบนเอกสารแย่กว่าไม่มีเลข --}}
                                    @if ($c['tax_id'])<br>เลขประจำตัวผู้เสียภาษี / Tax ID {{ $c['tax_id'] }}@endif
                                </div>
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
            <td style="width: 42%;">
                <div class="doc-title">ใบเสร็จรับเงิน</div>
                <div class="doc-title-en">RECEIPT</div>
                <table class="meta">
                    <tr><td class="k">เลขที่ <span class="en">No.</span></td><td class="v">{{ $doc['number'] }}</td></tr>
                    <tr><td class="k">วันที่ <span class="en">Date</span></td><td class="v">{{ $doc['issued'] }}</td></tr>
                    <tr><td class="k">อ้างอิงคำสั่งซื้อ <span class="en">Order ref.</span></td><td class="v">#{{ $doc['order_number'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="clear: both;"></div>
    <div class="rule"></div>

    {{-- ══ ผู้ชำระ + การชำระเงิน ══ --}}
    <table class="party">
        <tr>
            <td>
                <div class="box">
                    <div class="box-label">ได้รับเงินจาก <span class="en">RECEIVED FROM</span></div>
                    <div class="box-name">{{ $doc['customer']['name'] }}</div>
                    <div class="box-lines">
                        {{ $doc['customer']['phone'] }}@if ($doc['customer']['phone'] && $doc['customer']['email']) · @endif{{ $doc['customer']['email'] }}
                    </div>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="box-label">รายละเอียดการชำระเงิน <span class="en">PAYMENT DETAILS</span></div>
                    <table class="kv">
                        <tr>
                            <td class="k">ชำระโดย<span class="en-line">Paid by</span></td>
                            <td class="v">
                                {{ $doc['payment_method'] }}
                                @if ($doc['payment_method_en'] !== $doc['payment_method'])<span class="en-line" style="color:#6b7280;">{{ $doc['payment_method_en'] }}</span>@endif
                            </td>
                        </tr>
                        <tr><td class="k">วันเวลาที่ชำระ<span class="en-line">Paid on</span></td><td class="v">{{ $doc['paid_at'] }}</td></tr>
                        @if ($doc['ordered'])
                            <tr><td class="k">วันที่สั่งซื้อ<span class="en-line">Ordered on</span></td><td class="v">{{ $doc['ordered'] }}</td></tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ รายการ ══ --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="text-align: left;">รายการ<span class="en-line">Description</span></th>
                <th style="width: 52px;">จำนวน<span class="en-line">Qty</span></th>
                <th style="width: 92px;">ราคา/หน่วย<span class="en-line">Unit price</span></th>
                <th style="width: 100px;">จำนวนเงิน<span class="en-line">Amount</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($doc['items'] as $i => $item)
                <tr class="{{ $i % 2 ? 'alt' : '' }}">
                    <td class="mid">{{ $i + 1 }}</td>
                    <td>
                        <div class="it-name">{{ $item['name'] }}</div>
                        @if (!empty($item['sku']))
                            <div class="it-desc">รหัสสินค้า / SKU {{ $item['sku'] }}</div>
                        @endif
                    </td>
                    <td class="mid">{{ $item['quantity'] }}</td>
                    <td class="num">{{ $money($item['price']) }}</td>
                    <td class="num" style="font-weight: bold;">{{ $money($item['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="mid" style="color:#6b7280; padding: 16px;">ไม่มีรายการ / No items</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ══ ตราประทับ + สรุปยอด ══ --}}
    <table class="foot">
        <tr>
            <td style="width: 50%; padding-right: 14px;">
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="width: 190px;">
                            <div class="stamp">
                                <div class="stamp-big">ชำระเงินแล้ว</div>
                                <div class="stamp-en">PAID</div>
                                <div class="stamp-date">{{ $doc['paid_at'] }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="totals">
                    <tr>
                        <td class="k">รวมเป็นเงิน <span class="en">Subtotal</span></td>
                        <td class="v">{{ $money($doc['subtotal']) }}</td>
                    </tr>
                    @if ($doc['tax'] > 0)
                        <tr>
                            <td class="k">{{ $doc['vat_label'] }} <span class="en">{{ $doc['vat_label_en'] }}</span></td>
                            <td class="v">{{ $money($doc['tax']) }}</td>
                        </tr>
                    @endif
                    @if ($doc['discount'] > 0)
                        <tr>
                            <td class="k" style="color:#15803d;">ส่วนลด <span class="en">Discount</span>{{ $doc['coupon_code'] ? ' (' . $doc['coupon_code'] . ')' : '' }}</td>
                            <td class="v" style="color:#15803d;">&minus;{{ $money($doc['discount']) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td style="text-align: right;">จำนวนเงินที่ได้รับทั้งสิ้น <span class="en">Total received</span></td>
                        <td style="text-align: right;">{{ $money($doc['total']) }}</td>
                    </tr>
                    @if ($doc['paid_amount'] !== null)
                        <tr class="muted">
                            <td class="k">ยอดที่โอนจริง (รวมเศษสตางค์สำหรับตรวจยอด)<span class="en-line">Amount transferred (incl. matching satang)</span></td>
                            <td class="v" style="color:#6b7280;">{{ $money($doc['paid_amount']) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="words">
        <span class="words-k">ตัวอักษร</span>
        <span class="words-v">&nbsp; ( {{ $doc['total_words'] }} )</span>
        <br>
        <span class="words-k" style="color:#6b7280;">In words</span>
        <span class="words-v" style="font-size: 11px; color:#065f46;">&nbsp; ( {{ $doc['total_words_en'] }} )</span>
    </div>

    {{-- ══ หมายเหตุ + ลายเซ็น ══ --}}
    <table style="width: 100%; border-collapse: collapse; margin-top: 14px;">
        <tr>
            <td style="width: 58%; vertical-align: top; padding-right: 14px;">
                <div class="terms-label">หมายเหตุ <span class="en">NOTES</span></div>
                <ol class="terms">
                    <li>ได้รับเงินตามรายการข้างต้นไว้ถูกต้องครบถ้วนแล้ว
                        <span class="en-line" style="color:#6b7280;">Payment for the items above has been received in full.</span></li>
                    <li>เอกสารนี้ออกโดยระบบอัตโนมัติ ใช้เป็นหลักฐานการชำระเงินได้โดยไม่ต้องลงนาม
                        <span class="en-line" style="color:#6b7280;">This receipt is system-generated and valid without a signature.</span></li>
                    <li>ต้องการใบกำกับภาษีเต็มรูปแบบ ติดต่อ{{ $c['email'] ? ' ' . $c['email'] : 'ทีมงาน' }} พร้อมแจ้งเลขที่ {{ $doc['number'] }}
                        <span class="en-line" style="color:#6b7280;">For a full tax invoice, contact {{ $c['email'] ?: 'us' }} quoting {{ $doc['number'] }}.</span></li>
                </ol>
            </td>
            <td style="width: 42%; vertical-align: bottom;">
                <table class="sign">
                    <tr>
                        <td>
                            <div class="sign-space"></div>
                            <div class="sign-line">ผู้รับเงิน <span class="en">Received by</span> · {{ $c['name'] }}</div>
                            <div class="sign-date">วันที่ / Date {{ $doc['issued'] }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="thanks">ขอบคุณที่ไว้วางใจ {{ $c['name'] }} <span class="en">· Thank you for your business</span></div>

    <div class="tail">
        @if (!empty($doc['order_url']))
            ดูคำสั่งซื้อและดาวน์โหลดใบเสร็จนี้ย้อนหลังได้ที่ / View this order and download the receipt again at {{ $doc['order_url'] }}
        @else
            เอกสารนี้ออกโดยระบบอัตโนมัติจาก / Issued automatically by {{ $c['website'] }}
        @endif
    </div>

</div>
</body>
</html>
