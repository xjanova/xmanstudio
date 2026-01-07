<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงินเรียบร้อย</title>
    <style>
        body {
            font-family: 'Sarabun', 'Noto Sans Thai', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .order-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .license-box {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .license-key {
            background: white;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            text-align: center;
            margin: 10px 0;
            border: 1px solid #3b82f6;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .order-table th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        .order-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
        }
        .button-secondary {
            background: #6b7280;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 0.9em;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="success-icon">✓</div>
        <h1>ชำระเงินสำเร็จ!</h1>
        <p>การชำระเงินของคุณได้รับการยืนยันแล้ว</p>
    </div>

    <div class="content">
        <h2>สวัสดีคุณ {{ $order->user->name ?? 'ลูกค้า' }}</h2>

        <p>เรายืนยันการรับชำระเงินสำหรับคำสั่งซื้อของคุณเรียบร้อยแล้ว ขอบคุณที่ไว้วางใจเลือกใช้บริการของเรา</p>

        <div class="order-details">
            <h3>รายละเอียดการชำระเงิน</h3>
            <p>
                <strong>หมายเลขคำสั่งซื้อ:</strong> #{{ $order->order_number }}<br>
                <strong>วันที่ชำระเงิน:</strong> {{ $order->updated_at->addHours(7)->format('d/m/Y H:i') }} น.<br>
                <strong>จำนวนเงิน:</strong> ฿{{ number_format($order->total, 2) }}<br>
                <strong>วิธีการชำระเงิน:</strong>
                @switch($order->payment_method)
                    @case('promptpay')
                        พร้อมเพย์
                        @break
                    @case('bank_transfer')
                        โอนเงินผ่านธนาคาร
                        @break
                    @case('credit_card')
                        บัตรเครดิต
                        @break
                    @default
                        {{ $order->payment_method }}
                @endswitch
            </p>

            <table class="order-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th style="text-align: center;">จำนวน</th>
                        <th style="text-align: right;">ราคา</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? $item->product_name }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">฿{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $licenseKeys = $order->items->flatMap(fn($item) => $item->product->licenseKeys ?? collect())
                ->where('order_id', $order->id)
                ->where('status', 'active');
        @endphp

        @if($licenseKeys->count() > 0)
        <div class="license-box">
            <h3>🔑 License Key ของคุณ</h3>
            <p>กรุณาเก็บรักษา License Key เหล่านี้ไว้อย่างดี คุณจะต้องใช้สำหรับเปิดใช้งานซอฟต์แวร์</p>

            @foreach($licenseKeys as $license)
            <div style="margin: 15px 0;">
                <strong>{{ $license->product->name ?? 'ผลิตภัณฑ์' }}:</strong>
                <div class="license-key">{{ $license->license_key }}</div>
                <p style="font-size: 0.9em; color: #6b7280; margin: 5px 0;">
                    ประเภท: {{ ucfirst($license->license_type) }}
                    @if($license->expires_at)
                    | หมดอายุ: {{ $license->expires_at->addHours(7)->format('d/m/Y') }}
                    @endif
                </p>
            </div>
            @endforeach
        </div>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/my-account/orders/{{ $order->id }}" class="button">
                ดูรายละเอียดคำสั่งซื้อ
            </a>
            @if($licenseKeys->count() > 0)
            <a href="{{ config('app.url') }}/my-account/licenses" class="button button-secondary">
                จัดการ License Keys
            </a>
            @endif
        </div>

        <p style="background: #f3f4f6; padding: 15px; border-radius: 6px; font-size: 0.95em;">
            <strong>หมายเหตุ:</strong> ใบเสร็จรับเงินแนบมาพร้อมอีเมลนี้ หรือคุณสามารถดาวน์โหลดได้ที่หน้าบัญชีของคุณ
        </p>
    </div>

    <div class="footer">
        <p>หากมีคำถามหรือต้องการความช่วยเหลือ กรุณาติดต่อเรา</p>
        <p>{{ config('app.name') }} | {{ config('mail.from.address') }}</p>
    </div>
</body>
</html>
