{{--
    ใบแจ้งหนี้รายงวด A4 — เอกสารมาตรฐานของ XMAN Studio

    เรนเดอร์ด้วย DomPDF ซึ่ง **ไม่รองรับ flexbox และ grid** เลย์เอาต์ทั้งหน้าจึง
    ใช้ตารางเท่านั้น ถ้าเผลอใส่ display:flex กล่องจะเรียงทับกันเงียบ ๆ ไม่ฟ้อง

    ตัวเลขทุกตัวมาจากแถวในตาราง project_invoices ไม่ได้คำนวณใหม่ตอนพิมพ์
    ใบที่ส่งให้ลูกค้าไปแล้วต้องพิมพ์ออกมาเหมือนเดิมทุกครั้ง แม้แอดมินจะไปแก้
    สัดส่วนงวดในหน้าตั้งค่าแล้วก็ตาม
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบแจ้งหนี้ {{ $doc['number'] }}</title>
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

        .sheet { padding: 34px 40px 30px 40px; }

        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .brand-logo { height: 34px; width: auto; }
        .brand-mark {
            width: 42px; height: 42px; background: #0f766e; color: #fff;
            text-align: center; font-size: 22px; font-weight: bold;
            line-height: 42px; border-radius: 8px;
        }
        .brand-name { font-size: 17px; font-weight: bold; letter-spacing: .04em; color: #111827; }
        .brand-sub { font-size: 10.5px; color: #4b5563; }
        .brand-lines { font-size: 10px; color: #4b5563; line-height: 1.5; padding-top: 5px; }

        .doc-title { font-size: 25px; font-weight: bold; color: #0f766e; text-align: right; line-height: 1.1; }
        .doc-title-en { font-size: 10px; letter-spacing: .22em; color: #6b7280; text-align: right; margin-bottom: 7px; }
        .meta { width: 100%; border-collapse: collapse; font-size: 11px; }
        .meta .k { color: #6b7280; text-align: right; padding: 1px 8px 1px 0; width: 46%; }
        .meta .v { font-weight: bold; text-align: right; }

        .rule { height: 2px; background: #0f766e; margin: 12px 0 14px 0; }

        .party { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 14px; }
        .party td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 9px 11px; background: #f9fafb; }
        .box-label { font-size: 9.5px; letter-spacing: .12em; color: #6b7280; text-transform: uppercase; }
        .box-name { font-size: 13px; font-weight: bold; color: #111827; padding-top: 2px; }
        .box-lines { font-size: 10.5px; color: #4b5563; line-height: 1.5; padding-top: 3px; }

        .items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .items th {
            background: #0f766e; color: #fff; font-size: 10.5px; font-weight: bold;
            text-align: left; padding: 7px 10px;
        }
        .items td { padding: 9px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11.5px; vertical-align: top; }
        .num { text-align: right; }

        .totals { width: 100%; border-collapse: collapse; }
        .totals td { vertical-align: top; }
        .sum { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        .sum .k { color: #4b5563; padding: 3px 10px 3px 0; }
        .sum .v { text-align: right; font-weight: bold; padding: 3px 0; }
        .sum .grand-k { font-size: 13px; font-weight: bold; color: #0f766e; padding-top: 7px; }
        .sum .grand-v { font-size: 15px; font-weight: bold; color: #0f766e; text-align: right; padding-top: 7px; }

        .stamp {
            display: inline-block; border: 2px solid #059669; color: #059669;
            font-size: 15px; font-weight: bold; padding: 5px 16px; border-radius: 6px;
        }
        .stamp-due { border-color: #b45309; color: #b45309; }
        .stamp-over { border-color: #dc2626; color: #dc2626; }

        .terms { font-size: 10px; color: #4b5563; line-height: 1.6; }
        .terms li { margin-left: 13px; margin-bottom: 2px; }
        .sign { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .sign td { width: 50%; text-align: center; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #9ca3af; margin: 0 22px; padding-top: 5px; font-size: 10.5px; color: #4b5563; }
        .foot { text-align: center; font-size: 9.5px; color: #9ca3af; margin-top: 18px; }
    </style>
</head>
<body>
@php
    $c = $companyInfo;
    $money = fn ($n) => number_format((float) $n, 2);
@endphp

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
                                    {{-- เลขผู้เสียภาษีขึ้นเฉพาะเมื่อตั้งค่าไว้จริง --}}
                                    @if ($c['tax_id'])<br>เลขประจำตัวผู้เสียภาษี {{ $c['tax_id'] }}@endif
                                </div>
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
            <td style="width: 42%;">
                <div class="doc-title">ใบแจ้งหนี้</div>
                <div class="doc-title-en">INVOICE</div>
                <table class="meta">
                    <tr><td class="k">เลขที่</td><td class="v">{{ $doc['number'] }}</td></tr>
                    <tr><td class="k">วันที่</td><td class="v">{{ $doc['issued'] }}</td></tr>
                    @if ($doc['due'])
                        <tr><td class="k">กำหนดชำระ</td><td class="v" style="color:{{ $doc['overdue'] ? '#dc2626' : '#b45309' }};">{{ $doc['due'] }}</td></tr>
                    @endif
                    @if ($doc['quote_number'])
                        <tr><td class="k">อ้างอิงใบเสนอราคา</td><td class="v">{{ $doc['quote_number'] }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ══ คู่สัญญา ══ --}}
    <table class="party">
        <tr>
            <td>
                <div class="box">
                    <div class="box-label">เรียกเก็บจาก</div>
                    <div class="box-name">{{ $doc['customer']['company'] ?: $doc['customer']['name'] }}</div>
                    <div class="box-lines">
                        @if ($doc['customer']['company']){{ $doc['customer']['name'] }}<br>@endif
                        @if ($doc['customer']['address']){{ $doc['customer']['address'] }}<br>@endif
                        {{ $doc['customer']['phone'] }}@if ($doc['customer']['phone'] && $doc['customer']['email']) · @endif{{ $doc['customer']['email'] }}
                    </div>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="box-label">โครงการ</div>
                    <div class="box-name">{{ $doc['project']['name'] ?: '-' }}</div>
                    <div class="box-lines">
                        @if ($doc['project']['number'])เลขที่โครงการ {{ $doc['project']['number'] }}<br>@endif
                        งวดที่ {{ $doc['installment'] }} ของงานทั้งหมด
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ รายการ ══ --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 38px;">ที่</th>
                <th>รายการ</th>
                <th class="num" style="width: 62px;">สัดส่วน</th>
                <th class="num" style="width: 104px;">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>
                    <strong>{{ $doc['title'] }}</strong>
                    @if ($doc['project']['name'])
                        <div style="color:#6b7280; font-size:10.5px;">{{ $doc['project']['name'] }}</div>
                    @endif
                </td>
                <td class="num">{{ $doc['percent'] }}%</td>
                <td class="num">{{ $money($doc['amount']) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ══ สรุปยอด ══ --}}
    <table class="totals">
        <tr>
            <td style="width: 52%; padding-right: 14px;">
                <div class="box" style="background:#f0fdfa; border-color:#99f6e4;">
                    <div class="box-label">จำนวนเงินเป็นตัวอักษร</div>
                    <div style="font-size:11.5px; font-weight:bold; color:#0f766e; padding-top:2px;">{{ $doc['amount_words'] }}</div>
                </div>
                <div style="padding-top: 12px;">
                    @if ($doc['status'] === 'paid')
                        <span class="stamp">ชำระแล้ว{{ $doc['paid_at'] ? ' ' . $doc['paid_at'] : '' }}</span>
                    @elseif ($doc['overdue'])
                        <span class="stamp stamp-over">เกินกำหนดชำระ</span>
                    @elseif ($doc['status'] === 'void')
                        <span class="stamp stamp-over">ยกเลิก</span>
                    @else
                        <span class="stamp stamp-due">{{ $doc['status_label'] }}</span>
                    @endif
                </div>
            </td>
            <td style="width: 48%;">
                <table class="sum">
                    @if ($doc['vat_mode'] === 'none')
                        <tr><td class="k">จำนวนเงิน</td><td class="v">{{ $money($doc['amount']) }}</td></tr>
                        <tr><td class="k" style="font-size:10px; color:#6b7280;">ไม่มีภาษีมูลค่าเพิ่ม</td><td class="v"></td></tr>
                    @else
                        <tr><td class="k">มูลค่าสินค้า/บริการ</td><td class="v">{{ $money($doc['base']) }}</td></tr>
                        <tr><td class="k">ภาษีมูลค่าเพิ่ม {{ $doc['vat_rate'] }}%</td><td class="v">{{ $money($doc['vat']) }}</td></tr>
                    @endif
                    <tr>
                        <td class="grand-k">ยอดชำระงวดนี้</td>
                        <td class="grand-v">{{ $money($doc['amount']) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ เงื่อนไข ══ --}}
    <div style="margin-top: 16px;">
        <div class="box-label" style="margin-bottom: 4px;">เงื่อนไขการชำระเงิน</div>
        <ul class="terms">
            <li>โอนเข้าบัญชีบริษัทตามที่แจ้งไว้ในอีเมล หรือสแกนพร้อมเพย์ที่ทีมงานส่งให้</li>
            <li>กรุณาส่งหลักฐานการโอนกลับมาที่{{ $c['email'] ? ' ' . $c['email'] : 'อีเมลของเรา' }} เพื่อออกใบเสร็จรับเงิน</li>
            <li>ใบเสร็จรับเงิน/ใบกำกับภาษีจะออกให้หลังได้รับเงินเรียบร้อยแล้ว</li>
            @if ($doc['due'])<li>กำหนดชำระภายใน {{ $doc['due'] }} หากเลยกำหนดงานในงวดถัดไปอาจถูกเลื่อนออกไป</li>@endif
        </ul>
    </div>

    <table class="sign">
        <tr>
            <td><div class="sign-line">ผู้รับวางบิล / วันที่</div></td>
            <td><div class="sign-line">ผู้มีอำนาจลงนาม · {{ $c['name'] }}</div></td>
        </tr>
    </table>

    <div class="foot">
        {{ $c['name'] }} · {{ $c['website'] }}@if ($c['email']) · {{ $c['email'] }}@endif
    </div>
</div>
</body>
</html>
