# SMS Checker Integration Guide

ระบบ SMS Payment Verification สำหรับเชื่อมต่อกับแอพ SmsChecker Android

## สถาปัตยกรรม

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Android App   │────▶│   Laravel API   │────▶│    Database     │
│  (SmsChecker)   │◀────│   (xmanstudio)  │◀────│    (MySQL)      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
        │                       │
        │                       │
        ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│  Polling Sync   │     │  LINE Notify    │
│  (30 seconds)   │     │   (Optional)    │
└─────────────────┘     └─────────────────┘
```

## Sync Mechanism (Polling-Based)

ระบบใช้ polling-based sync แทน real-time push notifications เพื่อความเป็นส่วนตัวและการควบคุมระบบเอง:

### How It Works

1. **Version Tracking**: Server เก็บ `sync_version` number ที่เพิ่มขึ้นทุกครั้งที่มีการเปลี่ยนแปลง
2. **Polling**: Android app เรียก `/sync-version` ทุก 30 วินาที (configurable)
3. **Delta Sync**: ถ้า version เปลี่ยน app เรียก `/sync?since_version=X` เพื่อดึงข้อมูลที่เปลี่ยนแปลง

### Sync Events

| Event | When | Version Incremented |
|-------|------|---------------------|
| New Order | Order ใหม่รอชำระเงิน | ✅ |
| Payment Matched | SMS จับคู่กับ Order | ✅ |
| Order Status Changed | สถานะ Order เปลี่ยน | ✅ |
| Order Approved/Rejected | Admin อนุมัติ/ปฏิเสธ | ✅ |

## Configuration

### Environment Variables

```env
# SMS Checker
SMSCHECKER_ENABLED=true
SMSCHECKER_SYNC_INTERVAL=30
SMSCHECKER_UNIQUE_AMOUNT_EXPIRY=30
SMSCHECKER_AUTO_CONFIRM_MATCHED=true
SMSCHECKER_DEFAULT_APPROVAL_MODE=auto
SMSCHECKER_ORPHAN_RETENTION_DAYS=7
SMSCHECKER_ORPHAN_MATCH_WINDOW=60

# LINE Notify (Optional)
LINE_NOTIFY_TOKEN=your_line_notify_token
SMSCHECKER_LINE_ON_MATCH=true
```

## API Endpoints

### Device Endpoints (Android App)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/notify` | ส่ง SMS notification |
| GET | `/status` | ตรวจสอบสถานะ device |
| POST | `/register-device` | ลงทะเบียน device |
| GET | `/orders` | ดึงรายการ orders |
| POST | `/orders/{id}/approve` | อนุมัติ order |
| POST | `/orders/{id}/reject` | ปฏิเสธ order |
| GET | `/device-settings` | ดึงการตั้งค่า |
| PUT | `/device-settings` | อัพเดทการตั้งค่า |
| GET | `/dashboard-stats` | สถิติ dashboard |
| GET | `/sync` | ดึงข้อมูลที่เปลี่ยนแปลง |
| GET | `/sync-version` | ดึง version ปัจจุบัน |

### Admin Endpoints (Web)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/generate-amount` | สร้างยอดเฉพาะ |
| GET | `/notifications` | ดูประวัติ notifications |

## Security

### Encryption

- **AES-256-GCM**: ใช้สำหรับ encrypt SMS data
- **HMAC-SHA256**: ใช้สำหรับ signature verification
- **Nonce**: ป้องกัน replay attacks

### Authentication Headers

| Header | Description |
|--------|-------------|
| `X-Api-Key` | Device API key |
| `X-Device-Id` | Device identifier |
| `X-Timestamp` | Request timestamp |
| `X-Signature` | HMAC signature |

## Services

### SmsPaymentService

จัดการ SMS payment notifications

```php
use App\Services\SmsPaymentService;

// Process incoming SMS
$result = $smsPaymentService->processNotification($payload, $device, $ip);

// Generate unique amount
$uniqueAmount = $smsPaymentService->generateUniqueAmount($baseAmount);

// Send LINE notification
$smsPaymentService->notifyPaymentMatched($order, $notification);
```

## Database Tables

### sms_checker_devices

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| device_id | string | Device identifier |
| device_name | string | Device display name |
| api_key | string | API key |
| secret_key | string | Secret key for encryption |
| status | enum | active/inactive/blocked |
| approval_mode | enum | auto/manual/smart |
| last_active_at | timestamp | Last activity |

### sms_payment_notifications

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| bank | string | Bank code (KBANK, SCB, etc.) |
| type | enum | credit/debit |
| amount | decimal | Transaction amount |
| status | enum | pending/matched/confirmed/rejected |
| device_id | string | Source device |
| matched_transaction_id | bigint | Linked order ID |

### unique_payment_amounts

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| base_amount | decimal | Original amount |
| unique_amount | decimal | Amount with decimal suffix |
| status | enum | reserved/used/expired |
| expires_at | timestamp | Expiration time |

## WordPress Integration

WordPress plugin พร้อมใช้งานแล้วที่ `wordpress-plugin/sms-payment-checker/`

### Plugin Structure

```
wp-content/plugins/sms-payment-checker/
├── sms-payment-checker.php          # Main plugin file
├── readme.txt                       # WordPress readme
├── includes/
│   ├── class-spc-api.php            # REST API endpoints
│   ├── class-spc-device.php         # Device management
│   ├── class-spc-notification.php   # SMS notification handling
│   ├── class-spc-matching.php       # Order matching logic
│   ├── class-spc-encryption.php     # AES-256-GCM encryption
│   └── class-spc-wc-gateway.php     # WooCommerce payment gateway
├── admin/
│   └── class-spc-admin.php          # Admin pages & settings
├── assets/
│   ├── css/
│   │   └── admin.css                # Admin styles
│   └── js/
│       └── admin.js                 # Admin JavaScript
└── languages/
    └── sms-payment-checker.pot      # Translation template
```

### Admin Menu Structure

เมนูหลังติดตั้ง plugin:

```
📱 SMS Checker (Main Menu)
├── 📊 Dashboard           - ภาพรวมสถิติและ Quick Setup
├── ⚙️ Settings            - ตั้งค่าการเชื่อมต่อ
├── 📱 Devices             - จัดการอุปกรณ์
├── 📨 Notifications       - ประวัติ SMS ที่ได้รับ
└── ⏳ Pending Orders      - คำสั่งซื้อรอตรวจสอบ
```

### Installation

1. Copy `wordpress-plugin/sms-payment-checker/` to `wp-content/plugins/`
2. Activate plugin in WordPress admin
3. Go to SMS Checker > Settings to configure
4. Create device at SMS Checker > Devices
5. Scan QR code with Android app

### API Endpoints (WordPress)

API namespace: `sms-payment/v1`

| Method | Path | Description |
|--------|------|-------------|
| POST | `/notify` | Receive SMS notification |
| GET | `/status` | Check device status |
| POST | `/register-device` | Register device info |
| GET | `/orders` | Get orders list |
| POST | `/orders/{id}/approve` | Approve order |
| POST | `/orders/{id}/reject` | Reject order |
| GET | `/device-settings` | Get device settings |
| PUT | `/device-settings` | Update device settings |
| GET | `/dashboard-stats` | Get dashboard statistics |
| GET | `/sync` | Get changes since last sync |
| GET | `/sync-version` | Get current sync version |
| POST | `/generate-amount` | Generate unique amount |
| GET | `/notifications` | Get notification history |

### Database Tables (WordPress)

- `{prefix}_spc_devices` - Device credentials and settings
- `{prefix}_spc_notifications` - SMS notifications
- `{prefix}_spc_unique_amounts` - Unique payment amounts
- `{prefix}_spc_nonces` - Used nonces for replay prevention

### WooCommerce Payment Gateway

Plugin includes a WooCommerce payment gateway that:
- Generates unique payment amounts automatically
- Shows payment instructions on thank you page
- Sends email with payment details
- Auto-confirms payment when SMS matches

Enable at WooCommerce > Settings > Payments > Bank Transfer (SMS Verified)

## Testing

### Test Sync Endpoint

```bash
curl -X GET "https://your-domain.com/api/v1/sms-payment/sync?since_version=0" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID"
```

### Test Sync Version

```bash
curl -X GET "https://your-domain.com/api/v1/sms-payment/sync-version" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID"
```

### Test SMS Notification

```bash
curl -X POST "https://your-domain.com/api/v1/sms-payment/notify" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "bank": "KBANK",
    "type": "credit",
    "amount": 1000.55,
    "timestamp": "2024-01-01T12:00:00Z",
    "nonce": "unique-nonce-123"
  }'
```

## Troubleshooting

### Sync Issues

- ตรวจสอบว่า `sync_interval` ไม่เร็วเกินไป (แนะนำ 30 วินาที)
- ตรวจสอบ network connectivity
- ดู logs ที่ `storage/logs/laravel.log`

### Order Matching Issues

- ตรวจสอบ amount tolerance ใน config
- ดู orphan transactions ที่อาจต้อง match
- ตรวจสอบว่า unique_payment_amount ยังไม่ expired

### Device Connection Issues

- ตรวจสอบ API key และ Device ID ถูกต้อง
- ตรวจสอบ timestamp ไม่เกิน tolerance (5 นาที)
- ตรวจสอบ signature calculation

## Supported Banks

ระบบรองรับ SMS จากธนาคารเหล่านี้:

| Code | Bank Name |
|------|-----------|
| KBANK | ธนาคารกสิกรไทย |
| SCB | ธนาคารไทยพาณิชย์ |
| KTB | ธนาคารกรุงไทย |
| BBL | ธนาคารกรุงเทพ |
| GSB | ธนาคารออมสิน |
| BAY | ธนาคารกรุงศรี |
| TTB | ธนาคารทหารไทยธนชาต |
| PROMPTPAY | พร้อมเพย์ |
| CIMB | ธนาคาร ซีไอเอ็มบี ไทย |
| KKP | ธนาคารเกียรตินาคินภัทร |
| LH | ธนาคารแลนด์ แอนด์ เฮ้าส์ |
| TISCO | ธนาคารทิสโก้ |
| UOB | ธนาคารยูโอบี |
| ICBC | ธนาคารไอซีบีซี (ไทย) |
| BAAC | ธ.ก.ส. |
