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
│    FCM Push     │     │    Pusher/WS    │
│  Notifications  │     │   Broadcasting  │
└─────────────────┘     └─────────────────┘
```

## Real-time Features

### 1. Pusher Broadcasting

Events จะถูก broadcast ผ่าน Pusher เมื่อมีการเปลี่ยนแปลง:

| Event | Channel | Trigger |
|-------|---------|---------|
| `payment.matched` | `sms-checker.broadcast` | เมื่อจับคู่การโอนสำเร็จ |
| `order.created` | `sms-checker.broadcast` | เมื่อมี order ใหม่ |
| `order.status_changed` | `sms-checker.broadcast` | เมื่อสถานะ order เปลี่ยน |

### 2. FCM Push Notifications

Push notifications จะถูกส่งไปยังแอพ Android:

| Type | When | Visible |
|------|------|---------|
| `new_order` | Order ใหม่รอชำระ | ✅ |
| `payment_matched` | จับคู่สำเร็จ | ✅ |
| `order_update` | สถานะเปลี่ยน | ✅ |
| `sync` | ขอให้ sync | ❌ (Silent) |

## Configuration

### Environment Variables

```env
# Broadcasting
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap1

# Firebase FCM
FIREBASE_CREDENTIALS=/path/to/firebase-service-account.json
FIREBASE_PROJECT_ID=your-project-id

# SMS Checker
SMSCHECKER_FCM_ON_MATCH=true
SMSCHECKER_FCM_ON_NEW_ORDER=true
SMSCHECKER_WEBSOCKET_ENABLED=true
SMSCHECKER_ORPHAN_RETENTION_DAYS=7
SMSCHECKER_ORPHAN_MATCH_WINDOW=60
```

### Firebase Setup

1. ไปที่ [Firebase Console](https://console.firebase.google.com)
2. สร้าง project หรือเลือก project ที่มีอยู่
3. ไปที่ Project Settings > Service Accounts
4. คลิก "Generate New Private Key"
5. บันทึกไฟล์ JSON และ set path ใน `FIREBASE_CREDENTIALS`

### Pusher Setup

1. ไปที่ [Pusher Dashboard](https://dashboard.pusher.com)
2. สร้าง Channels app
3. Copy credentials ไปใส่ใน `.env`

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
| POST | `/register-fcm-token` | ลงทะเบียน FCM token |
| POST | `/pusher/auth` | Auth สำหรับ Pusher |
| GET | `/sync` | ดึงข้อมูลที่เปลี่ยนแปลง |
| GET | `/sync-version` | ดึง version ปัจจุบัน |

### Admin Endpoints (Web)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/generate-amount` | สร้างยอดเฉพาะ |
| GET | `/notifications` | ดูประวัติ notifications |

## Events

### PaymentMatched

Fired เมื่อจับคู่ SMS กับ order สำเร็จ

```php
use App\Events\PaymentMatched;

event(new PaymentMatched($order, $smsNotification));
```

### NewOrderCreated

Fired เมื่อสร้าง order ใหม่ที่ต้องการ SMS verification

```php
use App\Events\NewOrderCreated;

event(new NewOrderCreated($order));
```

### OrderStatusChanged

Fired เมื่อสถานะ order เปลี่ยน

```php
use App\Events\OrderStatusChanged;

event(new OrderStatusChanged($order, $oldStatus, $newStatus));
```

## Services

### FcmNotificationService

ส่ง push notifications ไปยัง Android app

```php
use App\Services\FcmNotificationService;

// Inject via constructor
public function __construct(
    private FcmNotificationService $fcmService
) {}

// New order notification
$this->fcmService->notifyNewOrder($order);

// Payment matched notification
$this->fcmService->notifyPaymentMatched($order, $smsNotification);

// Order status update
$this->fcmService->notifyOrderUpdate($order, 'confirmed');

// Silent sync trigger
$this->fcmService->triggerSync();

// Register FCM token
$this->fcmService->registerToken($device, $fcmToken);
```

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
| fcm_token | string | FCM token for push |
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
│   ├── class-spc-fcm.php            # Firebase Cloud Messaging
│   ├── class-spc-pusher.php         # Pusher broadcasting
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
| POST | `/register-fcm-token` | Register FCM token |
| POST | `/pusher/auth` | Pusher authentication |
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

### Test FCM Notification

```bash
curl -X POST https://your-domain.com/api/v1/sms-payment/register-fcm-token \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID" \
  -H "Content-Type: application/json" \
  -d '{"fcm_token": "YOUR_FCM_TOKEN"}'
```

### Test Pusher Auth

```bash
curl -X POST https://your-domain.com/api/v1/sms-payment/pusher/auth \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID" \
  -H "Content-Type: application/json" \
  -d '{"socket_id": "123.456", "channel_name": "sms-checker.broadcast"}'
```

### Test Sync Endpoint

```bash
curl -X GET "https://your-domain.com/api/v1/sms-payment/sync?since_version=0" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "X-Device-Id: YOUR_DEVICE_ID"
```

## Troubleshooting

### FCM Token Issues

- ตรวจสอบว่า Firebase credentials ถูกต้อง
- ตรวจสอบว่า token ไม่ expired
- ดู logs ที่ `storage/logs/laravel.log`

### Pusher Connection Issues

- ตรวจสอบ Pusher credentials
- ตรวจสอบ cluster ตรงกัน (ap1, us2, etc.)
- ใช้ Pusher Debug Console ตรวจสอบ events

### Order Matching Issues

- ตรวจสอบ amount tolerance ใน config
- ดู orphan transactions ที่อาจต้อง match
- ตรวจสอบว่า unique_payment_amount ยังไม่ expired
