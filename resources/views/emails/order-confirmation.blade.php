<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันคำสั่งซื้อ</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
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
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
            background: #f9fafb;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .payment-info {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
        <h1>✓ ยืนยันคำสั่งซื้อ</h1>
        <p>คำสั่งซื้อของคุณได้รับการบันทึกเรียบร้อยแล้ว</p>
    </div>

    <div class="content">
        <h2>สวัสดีคุณ {{ $order->user->name ?? 'ลูกค้า' }}</h2>

        <p>ขอบคุณที่สั่งซื้อสินค้ากับเรา เราได้รับคำสั่งซื้อของคุณเรียบร้อยแล้ว</p>

        <div class="order-details">
            <h3>รายละเอียดคำสั่งซื้อ</h3>
            <p>
                <strong>หมายเลขคำสั่งซื้อ:</strong> #{{ $order->order_number }}<br>
                <strong>วันที่:</strong> {{ $order->created_at->addHours(7)->format('d/m/Y H:i') }} น.<br>
                <strong>สถานะ:</strong> {{ $order->payment_status === 'pending' ? 'รอชำระเงิน' : 'ชำระเงินแล้ว' }}
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
                <tfoot>
                    @if($order->tax > 0)
                    <tr>
                        <td colspan="2" style="text-align: right;">ยอดรวมสินค้า</td>
                        <td style="text-align: right;">฿{{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right;">ภาษี VAT 7%</td>
                        <td style="text-align: right;">฿{{ number_format($order->tax, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right;">ยอดรวมทั้งหมด</td>
                        <td style="text-align: right;">฿{{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($order->payment_status === 'pending')
        <div class="payment-info">
            <h3>⚠️ รอดำเนินการชำระเงิน</h3>
            <p>กรุณาชำระเงินตามวิธีการที่เลือกไว้:</p>
            <p><strong>วิธีการชำระเงิน:</strong>
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
            <p><strong>ยอดชำระ:</strong> ฿{{ number_format($order->total, 2) }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/my-account/orders/{{ $order->id }}" class="button">
                ดูรายละเอียดคำสั่งซื้อ
            </a>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>หากมีคำถามหรือต้องการความช่วยเหลือ กรุณาติดต่อเรา</p>
        <p>{{ config('app.name') }} | {{ config('mail.from.address') }}</p>
    </div>
</body>
</html>
