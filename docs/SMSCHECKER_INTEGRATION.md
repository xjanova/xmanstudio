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

สำหรับ WordPress/WooCommerce ให้สร้าง plugin ที่ implement API เดียวกัน:

### Plugin Structure

```
wp-content/plugins/sms-payment-checker/
├── sms-payment-checker.php      # Main plugin file
├── includes/
│   ├── class-api.php            # REST API endpoints
│   ├── class-device.php         # Device management
│   ├── class-notification.php   # SMS notification handling
│   └── class-matching.php       # Order matching logic
├── admin/
│   ├── class-admin.php          # Admin pages
│   └── views/
│       └── device-qr.php        # QR code page
└── assets/
    └── js/
        └── admin.js             # Admin JavaScript
```

### Required WordPress Hooks

```php
// Register REST API routes
add_action('rest_api_init', function() {
    register_rest_route('sms-payment/v1', '/notify', [...]);
    register_rest_route('sms-payment/v1', '/orders', [...]);
    // ... other endpoints
});

// WooCommerce order completed hook
add_action('woocommerce_order_status_completed', function($order_id) {
    // Fire events for SMS verification
});

// Webhook for payment confirmation
add_action('woocommerce_payment_complete', function($order_id) {
    // Handle SMS verified payment
});
```

### Key Implementation Points

1. **API Compatibility** - ใช้ format เดียวกับ Laravel API
2. **Security** - Implement HMAC verification และ encryption
3. **Device Management** - Store device credentials ใน custom table
4. **Order Matching** - Match SMS amounts กับ WooCommerce orders
5. **FCM Integration** - ใช้ Firebase SDK หรือ HTTP API
6. **Pusher Integration** - ใช้ Pusher PHP SDK

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
