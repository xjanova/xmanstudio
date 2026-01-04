<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>ใบเสนอราคา {{ $quotation['quote_number'] }}</title>
    <style>
        @font-face {
            font-family: 'THSarabun';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/THSarabunNew.ttf') }}) format('truetype');
        }
        @font-face {
            font-family: 'THSarabun';
            font-style: normal;
            font-weight: bold;
            src: url({{ storage_path('fonts/THSarabunNew Bold.ttf') }}) format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'THSarabun', 'DejaVu Sans', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1f2937;
            background: #fff;
        }

        .container {
            padding: 40px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #0ea5e9;
            padding-bottom: 20px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #0ea5e9;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 14px;
            color: #6b7280;
        }

        .quote-title {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .quote-number {
            font-size: 16px;
            color: #0ea5e9;
            font-weight: bold;
        }

        /* Info Section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .info-box.right {
            margin-left: 4%;
        }

        .info-title {
            font-size: 12px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .info-content {
            font-size: 14px;
            color: #1f2937;
        }

        .info-content strong {
            font-size: 16px;
        }

        /* Service Badge */
        .service-badge {
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .service-badge h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .service-badge p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table th {
            background: #1f2937;
            color: #fff;
            padding: 12px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
        }

        .items-table th:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .items-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .items-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .items-table .item-name {
            font-weight: bold;
            color: #1f2937;
        }

        .items-table .item-desc {
            font-size: 12px;
            color: #6b7280;
        }

        .items-table .service-item td:first-child {
            border-left: 3px solid #0ea5e9;
        }

        .items-table .additional-item td:first-child {
            border-left: 3px solid #10b981;
        }

        /* Summary */
        .summary-section {
            display: table;
            width: 100%;
        }

        .summary-notes {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-right: 30px;
        }

        .summary-totals {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .notes-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }

        .notes-title {
            font-size: 14px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 8px;
        }

        .notes-content {
            font-size: 12px;
            color: #78350f;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            font-size: 14px;
        }

        .totals-table .label {
            color: #6b7280;
        }

        .totals-table .value {
            text-align: right;
            font-weight: bold;
            color: #1f2937;
        }

        .totals-table .discount .value {
            color: #10b981;
        }

        .totals-table .rush-fee .value {
            color: #f59e0b;
        }

        .totals-table .grand-total {
            background: #0ea5e9;
            color: #fff;
        }

        .totals-table .grand-total td {
            padding: 15px 12px;
            font-size: 18px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .validity {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }

        .validity strong {
            color: #dc2626;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            padding: 0 20px;
        }

        .signature-line {
            border-top: 1px solid #1f2937;
            margin-top: 60px;
            padding-top: 10px;
        }

        .signature-label {
            font-size: 14px;
            color: #6b7280;
        }

        /* Company Footer */
        .company-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            padding-top: 20px;
            border-top: 2px solid #0ea5e9;
        }

        .company-footer strong {
            color: #0ea5e9;
        }

        /* Terms */
        .terms {
            margin-top: 30px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 11px;
            color: #6b7280;
        }

        .terms-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .terms ul {
            padding-left: 15px;
        }

        .terms li {
            margin-bottom: 3px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $companyInfo['name'] }}</div>
                <div class="company-tagline">{{ $companyInfo['tagline'] }}</div>
                <div style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                    {{ $companyInfo['address'] }}<br>
                    Email: {{ $companyInfo['email'] }}<br>
                    Line: {{ $companyInfo['line'] }}
                </div>
            </div>
            <div class="header-right">
                <div class="quote-title">QUOTATION</div>
                <div class="quote-title" style="font-size: 20px; color: #6b7280;">ใบเสนอราคา</div>
                <div class="quote-number">#{{ $quotation['quote_number'] }}</div>
            </div>
        </div>

        <!-- Info Section -->
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 48%; vertical-align: top; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div class="info-title">ข้อมูลลูกค้า / Customer Information</div>
                    <div class="info-content">
                        <strong>{{ $quotation['customer']['name'] }}</strong><br>
                        @if($quotation['customer']['company'])
                            {{ $quotation['customer']['company'] }}<br>
                        @endif
                        {{ $quotation['customer']['email'] }}<br>
                        {{ $quotation['customer']['phone'] }}
                        @if($quotation['customer']['address'])
                            <br>{{ $quotation['customer']['address'] }}
                        @endif
                    </div>
                </td>
                <td style="width: 4%;"></td>
                <td style="width: 48%; vertical-align: top; padding: 20px; background: #f8fafc; border-radius: 8px;">
                    <div class="info-title">รายละเอียดใบเสนอราคา / Quote Details</div>
                    <div class="info-content">
                        <strong>วันที่:</strong> {{ $quotation['quote_date'] }}<br>
                        <strong>ใช้ได้ถึง:</strong> {{ $quotation['valid_until'] }}<br>
                        <strong>Timeline:</strong>
                        @if($quotation['timeline'] === 'urgent')
                            <span style="color: #dc2626;">เร่งด่วน</span>
                        @elseif($quotation['timeline'] === 'normal')
                            ปกติ (2-3 เดือน)
                        @else
                            ยืดหยุ่น
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Service Badge -->
        <div class="service-badge">
            <h3>{{ $quotation['service']['icon'] }} {{ $quotation['service']['name_th'] }}</h3>
            <p>{{ $quotation['service']['name'] }}</p>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 60%;">รายการ / Description</th>
                    <th style="width: 15%;">ประเภท</th>
                    <th style="width: 20%;">ราคา (บาท)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation['items'] as $index => $item)
                <tr class="{{ $item['type'] === 'service' ? 'service-item' : 'additional-item' }}">
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item['name_th'] }}</div>
                        <div class="item-desc">{{ $item['name'] }}</div>
                    </td>
                    <td>
                        @if($item['type'] === 'service')
                            <span style="color: #0ea5e9;">บริการหลัก</span>
                        @else
                            <span style="color: #10b981;">เสริม</span>
                        @endif
                    </td>
                    <td>{{ number_format($item['price'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Project Description -->
        @if($quotation['project_description'])
        <div style="margin-bottom: 25px; padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <div style="font-weight: bold; color: #1e40af; margin-bottom: 8px;">รายละเอียดโปรเจค</div>
            <div style="font-size: 13px; color: #1e3a8a;">{{ $quotation['project_description'] }}</div>
        </div>
        @endif

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-notes">
                <div class="notes-box">
                    <div class="notes-title">หมายเหตุ</div>
                    <div class="notes-content">
                        - ราคานี้ยังไม่รวม VAT 7%<br>
                        - ราคาอาจเปลี่ยนแปลงตามขอบเขตงานจริง<br>
                        - ใบเสนอราคานี้มีอายุ 30 วัน
                        @if($quotation['discount_percent'] > 0)
                        <br>- ได้รับส่วนลดพิเศษ {{ $quotation['discount_percent'] }}%
                        @endif
                    </div>
                </div>
            </div>
            <div class="summary-totals">
                <table class="totals-table">
                    <tr>
                        <td class="label">รวมก่อนส่วนลด</td>
                        <td class="value">{{ number_format($quotation['subtotal'], 2) }}</td>
                    </tr>
                    @if($quotation['discount'] > 0)
                    <tr class="discount">
                        <td class="label">ส่วนลด ({{ $quotation['discount_percent'] }}%)</td>
                        <td class="value">-{{ number_format($quotation['discount'], 2) }}</td>
                    </tr>
                    @endif
                    @if($quotation['rush_fee'] > 0)
                    <tr class="rush-fee">
                        <td class="label">ค่าเร่งด่วน (+25%)</td>
                        <td class="value">+{{ number_format($quotation['rush_fee'], 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">รวมก่อน VAT</td>
                        <td class="value">{{ number_format($quotation['total_before_vat'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">VAT 7%</td>
                        <td class="value">{{ number_format($quotation['vat'], 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>รวมทั้งสิ้น</td>
                        <td style="text-align: right;">{{ number_format($quotation['grand_total'], 2) }} บาท</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Terms -->
        <div class="terms">
            <div class="terms-title">เงื่อนไขและข้อกำหนด</div>
            <ul>
                <li>ชำระเงินงวดแรก 50% ก่อนเริ่มงาน งวดที่สอง 50% เมื่องานเสร็จสมบูรณ์</li>
                <li>ระยะเวลาการทำงานเริ่มนับหลังจากได้รับการยืนยันและชำระเงินงวดแรก</li>
                <li>ลูกค้าจะได้รับการ Revise ไม่เกิน 3 ครั้ง หากเกินจะคิดค่าใช้จ่ายเพิ่มเติม</li>
                <li>ราคานี้ไม่รวมค่า Hosting, Domain และค่าบริการ Third-party อื่นๆ</li>
                <li>บริษัทสงวนสิทธิ์ในการเปลี่ยนแปลงราคาหากขอบเขตงานเปลี่ยนแปลง</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="validity">
                ใบเสนอราคานี้มีผลถึงวันที่ <strong>{{ $quotation['valid_until'] }}</strong>
            </div>

            <table class="signature-section">
                <tr>
                    <td class="signature-box">
                        <div class="signature-line">
                            <div class="signature-label">ผู้เสนอราคา / Authorized Signature</div>
                            <div style="margin-top: 5px; font-weight: bold;">XMAN STUDIO</div>
                        </div>
                    </td>
                    <td style="width: 10%;"></td>
                    <td class="signature-box">
                        <div class="signature-line">
                            <div class="signature-label">ผู้อนุมัติ / Customer Approval</div>
                            <div style="margin-top: 5px; font-weight: bold;">{{ $quotation['customer']['name'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Company Footer -->
        <div class="company-footer">
            <strong>{{ $companyInfo['name'] }}</strong> | {{ $companyInfo['tagline'] }}<br>
            {{ $companyInfo['email'] }} | {{ $companyInfo['website'] }} | Line: {{ $companyInfo['line'] }}
        </div>
    </div>
</body>
</html>
