# ระบบ License & Trial Protection - XMAN Studio

เอกสารนี้อธิบายมาตรฐานการป้องกันระบบ License สำหรับผลิตภัณฑ์ทั้งหมดของ XMAN Studio

---

## สารบัญ

1. [ภาพรวมระบบ](#1-ภาพรวมระบบ)
2. [การลงทะเบียน Device](#2-การลงทะเบียน-device)
3. [การป้องกัน Trial Abuse](#3-การป้องกัน-trial-abuse)
4. [การป้องกัน Fake Server](#4-การป้องกัน-fake-server)
5. [API Endpoints](#5-api-endpoints)
6. [Database Schema](#6-database-schema)
7. [Flowcharts](#7-flowcharts)
8. [Best Practices](#8-best-practices)

---

## 1. ภาพรวมระบบ

### 1.1 หลักการทำงาน

```
┌─────────────────────────────────────────────────────────────────┐
│                        แอปพลิเคชัน                              │
├─────────────────────────────────────────────────────────────────┤
│  1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต                              │
│  2. ตรวจสอบ Fake Server (hosts file, DNS, SSL)                  │
│  3. ลงทะเบียน Device อัตโนมัติ                                   │
│  4. ตรวจสอบสถานะ License/Trial                                  │
│  5. เริ่มทำงาน                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      License Server (Web)                        │
├─────────────────────────────────────────────────────────────────┤
│  - บันทึก Device ทุกเครื่องที่เปิดแอป                             │
│  - ตรวจจับ Trial Abuse                                          │
│  - จัดการ License Keys                                          │
│  - ป้องกันการใช้งานซ้ำ                                           │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 ประเภท License

| ประเภท | ระยะเวลา | คุณสมบัติ |
|--------|----------|-----------|
| Trial/Demo | 7 วัน | ฟีเจอร์พื้นฐาน, 1 เครื่อง |
| Monthly | 30 วัน | ฟีเจอร์เต็ม, 1-2 เครื่อง |
| Yearly | 365 วัน | ฟีเจอร์เต็ม + โบนัส, 2-3 เครื่อง |
| Lifetime | ตลอดชีพ | ฟีเจอร์ทั้งหมด, 3-5 เครื่อง |

### 1.3 สถานะ Device

| สถานะ | ความหมาย |
|-------|----------|
| `pending` | เพิ่งลงทะเบียน รอ activate |
| `trial` | กำลังใช้งาน trial |
| `licensed` | มี license ใช้งานอยู่ |
| `expired` | Trial/License หมดอายุ |
| `blocked` | ถูกบล็อก (abuse detected) |

---

## 2. การลงทะเบียน Device

### 2.1 ข้อมูลที่เก็บ

```php
// ข้อมูลที่ส่งจากแอป
[
    'machine_id'    => 'SHA256 hash ของ hardware IDs',      // 32-64 ตัวอักษร
    'machine_name'  => 'ชื่อเครื่อง',
    'os_version'    => 'เวอร์ชัน OS',
    'app_version'   => 'เวอร์ชันแอป',
    'hardware_hash' => 'SHA256(CPU_ID + Motherboard_Serial)', // สำหรับ abuse detection
]

// ข้อมูลที่เซิร์ฟเวอร์เก็บเพิ่ม
[
    'first_ip'      => 'IP แรกที่เห็น',
    'last_ip'       => 'IP ล่าสุด',
    'first_seen_at' => 'เวลาแรกที่เห็น',
    'last_seen_at'  => 'เวลาล่าสุดที่เห็น',
]
```

### 2.2 การสร้าง Machine ID (ฝั่งแอป)

```csharp
// C# Example
private string GetMachineId()
{
    var sb = new StringBuilder();

    // 1. CPU ID
    sb.Append(GetWmiProperty("Win32_Processor", "ProcessorId"));

    // 2. Motherboard Serial
    sb.Append(GetWmiProperty("Win32_BaseBoard", "SerialNumber"));

    // 3. BIOS Serial
    sb.Append(GetWmiProperty("Win32_BIOS", "SerialNumber"));

    // 4. Disk Serial (C: drive)
    sb.Append(GetWmiProperty("Win32_DiskDrive", "SerialNumber"));

    // Hash it
    using var sha256 = SHA256.Create();
    var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(sb.ToString()));
    return Convert.ToHexString(hashBytes); // 64 characters
}
```

### 2.3 Hardware Hash (สำหรับ Abuse Detection)

```csharp
private string GetHardwareHash()
{
    var sb = new StringBuilder();

    // CPU + Motherboard เท่านั้น (ไม่เปลี่ยนบ่อย)
    sb.Append(GetWmiProperty("Win32_Processor", "ProcessorId"));
    sb.Append(GetWmiProperty("Win32_BaseBoard", "SerialNumber"));

    using var sha256 = SHA256.Create();
    var hashBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(sb.ToString()));
    return Convert.ToHexString(hashBytes)[..32]; // 32 characters
}
```

---

## 3. การป้องกัน Trial Abuse

### 3.1 รูปแบบ Abuse ที่ตรวจจับ

| รูปแบบ | วิธีตรวจจับ | การดำเนินการ |
|--------|-------------|--------------|
| เปลี่ยน Machine ID | ตรวจ Hardware Hash เดิม | Block trial |
| ใช้หลายเครื่องจาก IP เดียว | นับ devices ต่อ IP | แจ้งเตือน / Block |
| Reset trial หลายครั้ง | นับ trial_attempts | Block หลังจาก 3 ครั้ง |
| Trial เพิ่งหมด พยายามใหม่ | ตรวจระยะเวลาหลังหมดอายุ | Block ถ้าน้อยกว่า 14 วัน |

### 3.2 การตรวจจับ (ฝั่ง Server)

```php
// app/Models/AutoTradeXDevice.php

public function checkTrialAbuse(): array
{
    $reasons = [];

    // 1. Hardware Hash เดิมที่มี trial หมดอายุ
    $hardwareRelated = $this->findRelatedByHardware();
    $expiredTrials = $hardwareRelated->where('status', 'expired')->count();
    if ($expiredTrials > 0) {
        $reasons[] = "Same hardware found with {$expiredTrials} expired trial(s)";
    }

    // 2. IP เดียวกันมีหลาย trial
    $ipRelated = $this->findRelatedByIp();
    $ipTrials = $ipRelated->whereIn('status', ['trial', 'expired'])->count();
    if ($ipTrials >= 2) {
        $reasons[] = "Same IP found with {$ipTrials} trial device(s)";
    }

    // 3. พยายามขอ trial หลายครั้ง
    if ($this->trial_attempts >= 2) {
        $reasons[] = "Device has {$this->trial_attempts} trial attempts";
    }

    // 4. Trial เพิ่งหมด พยายามใหม่
    if ($this->trial_expires_at && $this->isTrialExpired()) {
        $daysSinceExpiry = $this->trial_expires_at->diffInDays(now());
        if ($daysSinceExpiry < 14) {
            $reasons[] = 'Trial expired recently, possible reset attempt';
        }
    }

    return [
        'is_abuse' => count($reasons) > 0,
        'reasons' => $reasons,
    ];
}
```

### 3.3 การหา Related Devices

```php
// หาจาก IP
public function findRelatedByIp(): Collection
{
    return self::where('id', '!=', $this->id)
        ->where(function ($query) {
            $query->where('first_ip', $this->first_ip)
                ->orWhere('last_ip', $this->last_ip)
                ->orWhere('first_ip', $this->last_ip)
                ->orWhere('last_ip', $this->first_ip);
        })
        ->get();
}

// หาจาก Hardware
public function findRelatedByHardware(): Collection
{
    if (!$this->hardware_hash) {
        return collect();
    }

    return self::where('id', '!=', $this->id)
        ->where('hardware_hash', $this->hardware_hash)
        ->get();
}
```

---

## 4. การป้องกัน Fake Server

### 4.1 การตรวจสอบฝั่งแอป

```
┌──────────────────────────────────────────────────────────────┐
│                   Fake Server Detection                       │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐    │
│  │ Hosts File  │ --> │ DNS Check   │ --> │ SSL/HTTPS   │    │
│  │   Check     │     │             │     │   Check     │    │
│  └─────────────┘     └─────────────┘     └─────────────┘    │
│         │                   │                   │            │
│         ▼                   ▼                   ▼            │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐    │
│  │   Domain    │ --> │  Challenge  │ --> │  Response   │    │
│  │   Check     │     │  Response   │     │  Structure  │    │
│  └─────────────┘     └─────────────┘     └─────────────┘    │
│                                                               │
│              ถ้าผ่านทั้งหมด = Server ถูกต้อง                    │
│              ถ้าไม่ผ่าน = Block การทำงาน                       │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

### 4.2 รายละเอียดการตรวจสอบ

#### 4.2.1 Hosts File Check

```csharp
private bool CheckForFakeServerIndicators()
{
    // ตรวจว่ามีการ override domain ใน hosts file หรือไม่
    var hostsPath = Path.Combine(
        Environment.GetFolderPath(Environment.SpecialFolder.System),
        "drivers", "etc", "hosts"
    );

    if (File.Exists(hostsPath))
    {
        var hostsContent = File.ReadAllText(hostsPath).ToLower();
        if (hostsContent.Contains("xman4289.com"))
        {
            // พบ domain ใน hosts file = สงสัย fake server
            return true;
        }
    }

    return false;
}
```

#### 4.2.2 DNS Resolution Check

```csharp
private async Task<bool> VerifyDnsResolutionAsync()
{
    var addresses = await Dns.GetHostAddressesAsync("xman4289.com");

    // Trusted IP Ranges (Cloudflare)
    var trustedRanges = new[] { "104.21.", "172.67.", "188.114." };

    foreach (var address in addresses)
    {
        var ipString = address.ToString();
        foreach (var range in trustedRanges)
        {
            if (ipString.StartsWith(range))
            {
                return true; // IP อยู่ใน trusted range
            }
        }
    }

    // ถ้าไม่อยู่ใน trusted range ให้ตรวจสอบเพิ่มเติม
    return await VerifyServerResponseFromIpAsync(addresses[0].ToString());
}
```

#### 4.2.3 Challenge-Response Verification

```csharp
// แอปส่ง challenge
var challengeRequest = new {
    challenge = Convert.ToBase64String(randomBytes),
    timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds(),
    app_name = "AutoTradeX",
    app_version = "0.2.0"
};

// Server ต้องตอบกลับ
{
    "success": true,
    "challenge": "<same challenge>",  // ต้องเหมือนกับที่ส่งไป
    "timestamp": 1706097601,          // ต้องห่างไม่เกิน 5 นาที
    "signature": "<hmac_sha256>",
    "server_version": "1.0.0"
}
```

#### 4.2.4 Response Structure Check

```csharp
// ตรวจ pricing endpoint
var response = await httpClient.GetAsync($"{ApiBaseUrl}/pricing");
var json = await response.Content.ReadAsStringAsync();

// ต้องมีโครงสร้างที่ถูกต้อง
if (!json.Contains("\"success\"") ||
    !json.Contains("\"plans\"") ||
    !json.Contains("autotradex"))
{
    return false; // โครงสร้างไม่ถูกต้อง = Fake server
}
```

### 4.3 Server-Side Verification Endpoint

```php
// POST /api/v1/{product}/verify-server

public function verifyServer(Request $request)
{
    $validated = $request->validate([
        'challenge' => 'required|string|min:32|max:64',
        'timestamp' => 'required|integer',
        'app_name' => 'required|string|max:50',
        'app_version' => 'required|string|max:20',
    ]);

    // ตรวจ timestamp (ต้องไม่เกิน 5 นาที)
    $currentTimestamp = time();
    if (abs($currentTimestamp - $validated['timestamp']) > 300) {
        return response()->json([
            'success' => false,
            'error_code' => 'TIMESTAMP_INVALID',
        ], 400);
    }

    // สร้าง signature
    $signatureData = $validated['challenge'] . $currentTimestamp . $productSlug;
    $signature = hash_hmac('sha256', $signatureData, config('app.key'));

    return response()->json([
        'success' => true,
        'challenge' => $validated['challenge'],
        'timestamp' => $currentTimestamp,
        'signature' => $signature,
        'server_version' => '1.0.0',
    ])->withHeaders([
        'X-License-Signature' => $signature,
        'X-License-Timestamp' => (string) $currentTimestamp,
        'X-License-Nonce' => bin2hex(random_bytes(16)),
    ]);
}
```

---

## 5. API Endpoints

### 5.1 Endpoint Reference

| Method | Endpoint | คำอธิบาย | Rate Limit |
|--------|----------|----------|------------|
| POST | `/api/v1/{product}/register-device` | ลงทะเบียน device | 60/min |
| POST | `/api/v1/{product}/verify-server` | ตรวจสอบ server | 60/min |
| POST | `/api/v1/{product}/activate` | Activate license | 60/min |
| POST | `/api/v1/{product}/validate` | Validate license | 60/min |
| POST | `/api/v1/{product}/deactivate` | Deactivate license | 60/min |
| GET | `/api/v1/{product}/status/{key}` | เช็คสถานะ license | 60/min |
| POST | `/api/v1/{product}/demo` | เริ่ม trial | 10/min |
| POST | `/api/v1/{product}/demo/check` | เช็คสถานะ trial | 10/min |
| GET | `/api/v1/{product}/pricing` | ดูราคา | 60/min |
| GET | `/api/v1/{product}/purchase-url` | URL สำหรับซื้อ | 60/min |

### 5.2 Response Formats

#### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        "license_type": "yearly",
        "expires_at": "2027-01-24T00:00:00Z",
        "features": ["feature1", "feature2"],
        "days_remaining": 365
    }
}
```

#### Error Response

```json
{
    "success": false,
    "message": "Human readable error message",
    "error_code": "ERROR_CODE",
    "purchase_url": "https://xman4289.com/product/buy"
}
```

#### Error Codes

| Code | ความหมาย |
|------|----------|
| `INVALID_LICENSE` | License key ไม่ถูกต้อง |
| `LICENSE_EXPIRED` | License หมดอายุ |
| `LICENSE_REVOKED` | License ถูกยกเลิก |
| `DEVICE_MISMATCH` | Device ไม่ตรงกับที่ลงทะเบียน |
| `MAX_ACTIVATIONS` | Activate ครบจำนวนแล้ว |
| `TRIAL_EXPIRED` | Trial หมดอายุ |
| `TRIAL_ABUSE_DETECTED` | ตรวจพบการ abuse trial |
| `DEVICE_BLOCKED` | Device ถูกบล็อก |
| `TRIAL_NOT_AVAILABLE` | ไม่สามารถเริ่ม trial ได้ |
| `TIMESTAMP_INVALID` | Timestamp ไม่ถูกต้อง |

---

## 6. Database Schema

### 6.1 devices Table (ตัวอย่าง: autotradex_devices)

```sql
CREATE TABLE {product}_devices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- Device identification
    machine_id VARCHAR(64) UNIQUE NOT NULL,
    machine_name VARCHAR(255),
    os_version VARCHAR(255),
    app_version VARCHAR(50),

    -- Hardware fingerprint
    hardware_hash VARCHAR(64),
    first_ip VARCHAR(45),
    last_ip VARCHAR(45),

    -- Status
    status ENUM('pending', 'trial', 'licensed', 'blocked', 'expired') DEFAULT 'pending',
    license_id BIGINT REFERENCES license_keys(id),

    -- Trial tracking
    trial_attempts INT DEFAULT 0,
    first_trial_at TIMESTAMP NULL,
    trial_expires_at TIMESTAMP NULL,
    is_suspicious BOOLEAN DEFAULT FALSE,
    abuse_reason TEXT,

    -- Related devices (JSON)
    related_devices JSON,

    -- Timestamps
    first_seen_at TIMESTAMP NULL,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_hardware_hash (hardware_hash),
    INDEX idx_first_ip (first_ip),
    INDEX idx_last_ip (last_ip),
    INDEX idx_status (status)
);
```

### 6.2 license_keys Table

```sql
CREATE TABLE license_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT REFERENCES products(id),
    order_id BIGINT REFERENCES orders(id),

    license_key VARCHAR(255) UNIQUE NOT NULL,
    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
    license_type ENUM('demo', 'monthly', 'yearly', 'lifetime', 'product'),

    -- Machine binding
    machine_id VARCHAR(64),
    machine_fingerprint VARCHAR(1024),
    device_id VARCHAR(255),

    -- Activation
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    last_validated_at TIMESTAMP NULL,

    -- Limits
    max_activations INT DEFAULT 1,
    activations INT DEFAULT 0,

    -- Metadata (JSON)
    metadata JSON,

    -- Timestamps
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Indexes
    INDEX idx_license_machine (license_key, machine_id),
    INDEX idx_type_status (license_type, status)
);
```

---

## 7. Flowcharts

### 7.1 App Startup Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         App Startup                                  │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │ Check Internet        │
                    │ Connection            │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │                       │
               No Internet            Has Internet
                    │                       │
                    ▼                       ▼
            ┌───────────────┐   ┌───────────────────────┐
            │ Show Error    │   │ Check Fake Server     │
            │ Dialog        │   │ Indicators            │
            └───────┬───────┘   └───────────┬───────────┘
                    │                       │
                    ▼               ┌───────┴───────┐
                  EXIT              │               │
                               Detected        Not Found
                                    │               │
                                    ▼               ▼
                            ┌───────────┐   ┌───────────────┐
                            │ Block     │   │ Verify Server │
                            │ & Exit    │   │ Authenticity  │
                            └───────────┘   └───────┬───────┘
                                                    │
                                            ┌───────┴───────┐
                                            │               │
                                          Failed         Verified
                                            │               │
                                            ▼               ▼
                                    ┌───────────┐   ┌───────────────┐
                                    │ Block     │   │ Register      │
                                    │ & Exit    │   │ Device        │
                                    └───────────┘   └───────┬───────┘
                                                            │
                                                            ▼
                                                ┌───────────────────┐
                                                │ Check License     │
                                                │ Status            │
                                                └───────────┬───────┘
                                                            │
                                    ┌───────────────────────┼───────────────────────┐
                                    │                       │                       │
                               Has License             Has Trial              No License
                                    │                       │                       │
                                    ▼                       ▼                       ▼
                            ┌───────────────┐   ┌───────────────────┐   ┌───────────────┐
                            │ Validate      │   │ Check Trial       │   │ Show Trial    │
                            │ License       │   │ Expiry            │   │ or Purchase   │
                            └───────┬───────┘   └─────────┬─────────┘   └───────┬───────┘
                                    │                     │                     │
                                    ▼                     ▼                     ▼
                            ┌───────────────────────────────────────────────────────┐
                            │                    Start App                          │
                            └───────────────────────────────────────────────────────┘
```

### 7.2 Trial Request Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      POST /api/v1/{product}/demo                     │
└───────────────────────────────┬─────────────────────────────────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │ Get/Create Device     │
                    │ Record                │
                    └───────────┬───────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │ Check Trial Abuse     │
                    │ Patterns              │
                    └───────────┬───────────┘
                                │
                    ┌───────────┴───────────┐
                    │                       │
               Abuse Detected         No Abuse
                    │                       │
                    ▼                       ▼
            ┌───────────────┐   ┌───────────────────────┐
            │ Mark Device   │   │ Check Device Status   │
            │ Suspicious    │   │                       │
            └───────┬───────┘   └───────────┬───────────┘
                    │                       │
                    ▼               ┌───────┴───────────────┐
            ┌───────────────┐       │           │           │
            │ Return Error  │   Blocked    Active Trial  Expired
            │ 403           │       │           │           │
            └───────────────┘       ▼           ▼           ▼
                            ┌─────────┐ ┌───────────┐ ┌───────────┐
                            │ Error   │ │ Return    │ │ Error     │
                            │ BLOCKED │ │ Existing  │ │ EXPIRED   │
                            └─────────┘ └───────────┘ └───────────┘
                                                │
                                    ┌───────────┴───────────┐
                                    │                       │
                               Can Start              Cannot Start
                                    │                       │
                                    ▼                       ▼
                            ┌───────────────┐       ┌───────────────┐
                            │ Create Trial  │       │ Error         │
                            │ License       │       │ NOT_AVAILABLE │
                            └───────┬───────┘       └───────────────┘
                                    │
                                    ▼
                            ┌───────────────┐
                            │ Link to       │
                            │ Device        │
                            └───────┬───────┘
                                    │
                                    ▼
                            ┌───────────────┐
                            │ Return        │
                            │ Success       │
                            └───────────────┘
```

---

## 8. Best Practices

### 8.1 สำหรับนักพัฒนาแอป

1. **เรียก register-device ทุกครั้งที่เปิดแอป**
   - ทำก่อนการ validate license
   - ส่ง hardware_hash ทุกครั้ง

2. **ตรวจสอบ fake server ก่อนเชื่อมต่อ**
   - ตรวจ hosts file
   - ตรวจ DNS resolution
   - ใช้ challenge-response

3. **เก็บ license ไว้ local แบบเข้ารหัส**
   - รองรับ offline mode
   - มี grace period 7 วัน

4. **อย่าเก็บ license key ใน plain text**
   - ใช้ encryption
   - เก็บใน secure storage

### 8.2 สำหรับ Server

1. **Log ทุก request ที่สงสัย**
   - IP ที่มีหลาย devices
   - Hardware hash ซ้ำ
   - Trial attempts มาก

2. **Rate limit อย่างเข้มงวด**
   - Demo endpoints: 10/min
   - Other endpoints: 60/min

3. **ส่ง signature headers**
   - X-License-Signature
   - X-License-Timestamp
   - X-License-Nonce

4. **ตรวจสอบ timestamp ทุกครั้ง**
   - ป้องกัน replay attacks
   - ยอมรับ ±5 นาที

### 8.3 การจัดการ Abuse

1. **ถ้าพบ abuse ครั้งแรก**
   - Mark เป็น suspicious
   - Log เหตุผล
   - ยังอนุญาตให้ใช้งาน

2. **ถ้าพบ abuse ซ้ำ**
   - Block device
   - แจ้ง admin
   - ให้ติดต่อ support

3. **ถ้าผู้ใช้ร้องเรียน**
   - ตรวจสอบ logs
   - พิจารณา unblock
   - อาจให้ trial ใหม่

---

## เวอร์ชัน

| เวอร์ชัน | วันที่ | การเปลี่ยนแปลง |
|----------|--------|----------------|
| 1.0.0 | 2026-01-24 | เอกสารเริ่มต้น |

---

© 2024 XMAN Studio - All Rights Reserved
