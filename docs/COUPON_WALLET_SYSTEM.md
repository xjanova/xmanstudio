# Coupon & Wallet System Documentation

## Overview

ระบบ Coupon และ Wallet สำหรับ XmanStudio ช่วยให้ผู้ใช้สามารถใช้คูปองส่วนลดและเติมเงินเพื่อชำระค่าบริการได้สะดวกยิ่งขึ้น

---

## 1. Coupon System (ระบบคูปองส่วนลด)

### 1.1 คุณสมบัติหลัก

- **ประเภทส่วนลด**:
  - `percentage` - ส่วนลดเป็นเปอร์เซ็นต์ (เช่น 10%, 20%)
  - `fixed` - ส่วนลดคงที่ (เช่น ฿100, ฿500)

- **การจำกัดการใช้งาน**:
  - จำกัดจำนวนครั้งที่ใช้ได้ทั้งหมด (`usage_limit`)
  - จำกัดจำนวนครั้งต่อผู้ใช้ (`usage_limit_per_user`)
  - กำหนดยอดสั่งซื้อขั้นต่ำ (`min_order_amount`)
  - กำหนดส่วนลดสูงสุด (`max_discount`)

- **เงื่อนไขเพิ่มเติม**:
  - ระบุวันเริ่มต้น/สิ้นสุด
  - จำกัดสินค้าที่ใช้ได้
  - ยกเว้นสินค้าบางรายการ
  - สำหรับการสั่งซื้อครั้งแรกเท่านั้น
  - จำกัดผู้ใช้เฉพาะ

### 1.2 Database Schema

```sql
-- coupons table
CREATE TABLE coupons (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    discount_type ENUM('percentage', 'fixed'),
    discount_value DECIMAL(10,2),
    max_discount DECIMAL(10,2) NULL,
    min_order_amount DECIMAL(10,2) NULL,
    min_items INT NULL,
    usage_limit INT NULL,
    usage_limit_per_user INT DEFAULT 1,
    used_count INT DEFAULT 0,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    applicable_products JSON NULL,
    applicable_categories JSON NULL,
    excluded_products JSON NULL,
    applicable_license_types JSON NULL,
    first_order_only BOOLEAN DEFAULT FALSE,
    allowed_user_ids JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- coupon_usages table
CREATE TABLE coupon_usages (
    id BIGINT PRIMARY KEY,
    coupon_id BIGINT REFERENCES coupons(id),
    user_id BIGINT REFERENCES users(id),
    order_id BIGINT NULL REFERENCES orders(id),
    discount_amount DECIMAL(10,2),
    order_amount DECIMAL(10,2),
    created_at TIMESTAMP
);
```

### 1.3 API Endpoints

#### Validate Coupon
```http
POST /api/v1/coupons/validate
Authorization: Bearer {token}

{
    "code": "SAVE20",
    "amount": 1000,
    "product_ids": [1, 2, 3]
}
```

Response:
```json
{
    "valid": true,
    "message": "ใช้คูปองสำเร็จ",
    "coupon": {
        "code": "SAVE20",
        "name": "ส่วนลด 20%",
        "discount_type": "percentage",
        "discount_value": 20,
        "discount_label": "20%"
    },
    "discount_amount": 200,
    "final_amount": 800
}
```

#### Apply Coupon to Session
```http
POST /api/v1/coupons/apply
Authorization: Bearer {token}

{
    "code": "SAVE20"
}
```

#### Remove Coupon
```http
DELETE /api/v1/coupons/remove
Authorization: Bearer {token}
```

### 1.4 Admin Management

- **URL**: `/admin/coupons`
- **Features**:
  - สร้าง/แก้ไข/ลบ คูปอง
  - ดูสถิติการใช้งาน
  - Generate random code
  - ดูประวัติการใช้งาน

---

## 2. Wallet System (ระบบกระเป๋าเงิน)

### 2.1 คุณสมบัติหลัก

- **ยอดเงินคงเหลือ**: เก็บเงินไว้ในระบบเพื่อชำระค่าบริการ
- **ประวัติธุรกรรม**: บันทึกทุกการเคลื่อนไหวของเงิน
- **ระบบเติมเงิน**: รองรับหลายช่องทาง (โอนเงิน, PromptPay, TrueMoney)
- **โบนัสเติมเงิน**: รับโบนัสเพิ่มเมื่อเติมเงินตามเงื่อนไข

### 2.2 ประเภทธุรกรรม

| Type | Description |
|------|-------------|
| `deposit` | เติมเงินเข้า Wallet |
| `payment` | ชำระค่าสินค้า/บริการ |
| `refund` | คืนเงิน |
| `bonus` | โบนัสจากการเติมเงิน |
| `adjustment` | ปรับยอดโดย Admin |

### 2.3 Database Schema

```sql
-- wallets table
CREATE TABLE wallets (
    id BIGINT PRIMARY KEY,
    user_id BIGINT UNIQUE REFERENCES users(id),
    balance DECIMAL(12,2) DEFAULT 0,
    total_deposited DECIMAL(12,2) DEFAULT 0,
    total_spent DECIMAL(12,2) DEFAULT 0,
    total_refunded DECIMAL(12,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- wallet_transactions table
CREATE TABLE wallet_transactions (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT REFERENCES wallets(id),
    type ENUM('deposit', 'payment', 'refund', 'bonus', 'adjustment'),
    amount DECIMAL(12,2),
    balance_before DECIMAL(12,2),
    balance_after DECIMAL(12,2),
    description VARCHAR(500),
    reference_type VARCHAR(100) NULL,
    reference_id BIGINT NULL,
    metadata JSON NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled'),
    admin_id BIGINT NULL,
    admin_note TEXT NULL,
    created_at TIMESTAMP
);

-- wallet_topups table
CREATE TABLE wallet_topups (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT REFERENCES wallets(id),
    user_id BIGINT REFERENCES users(id),
    topup_id VARCHAR(20) UNIQUE,
    amount DECIMAL(12,2),
    bonus_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2),
    payment_method ENUM('bank_transfer', 'promptpay', 'truemoney'),
    payment_reference VARCHAR(100) NULL,
    payment_proof VARCHAR(255) NULL,
    payment_display_amount DECIMAL(12,2) NULL,  -- unique amount for SMS matching
    status ENUM('pending', 'approved', 'rejected', 'expired', 'cancelled'),
    reject_reason VARCHAR(255) NULL,             -- เหตุผลการปฏิเสธ (auto/manual)
    sms_verification_status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    admin_id BIGINT NULL,
    admin_note TEXT NULL,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- wallet_bonus_tiers table
CREATE TABLE wallet_bonus_tiers (
    id BIGINT PRIMARY KEY,
    name VARCHAR(100),
    min_amount DECIMAL(12,2),
    max_amount DECIMAL(12,2) NULL,
    bonus_type ENUM('percentage', 'fixed'),
    bonus_value DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2.4 User Features

#### Wallet Dashboard
- **URL**: `/user/wallet`
- แสดงยอดคงเหลือ
- ประวัติธุรกรรมล่าสุด
- รายการเติมเงินที่รออนุมัติ
- สรุปการใช้งาน

#### Top-up
- **URL**: `/user/wallet/topup`
- เลือกจำนวนเงิน (ขั้นต่ำ ฿10, **จำนวนเต็มเท่านั้น** — ไม่รับทศนิยม)
- เลือกช่องทางชำระเงิน
- แสดงโบนัสที่จะได้รับ (preview)

#### Top-up Status (สถานะการเติมเงิน)
- **URL**: `/user/wallet/topup/{id}/status`
- แสดงยอดเงินที่ต้องโอน (unique amount) พร้อม countdown
- AJAX polling ทุก 5 วินาที ตรวจสอบสถานะอัตโนมัติ
- เมื่อ **อนุมัติ**: แสดงข้อความสำเร็จ → redirect ไปหน้า wallet
- เมื่อ **ปฏิเสธ/หมดอายุ**: แสดง "รายการถูกปฏิเสธ" พร้อมเหตุผล → redirect หลัง 3 วินาที
- **AJAX Endpoint**: `GET /wallet/topup/{id}/check-status`
  ```json
  {
    "status": "pending|approved|rejected|expired",
    "sms_verification_status": "pending|confirmed|rejected",
    "reject_reason": "หมดเวลาโอนเงิน - ระบบปฏิเสธอัตโนมัติ"
  }
  ```

#### Transaction History
- **URL**: `/user/wallet/transactions`
- ดูประวัติทุกธุรกรรม
- กรองตามประเภท

### 2.5 Admin Features

#### Wallet Dashboard
- **URL**: `/admin/wallets`
- สถิติรวม (total balance, deposits, spending)
- รายการเติมเงินที่รออนุมัติ
- ธุรกรรมล่าสุด

#### Topup Management
- **URL**: `/admin/wallets/topups`
- ดูรายการเติมเงินทั้งหมด
- อนุมัติ/ปฏิเสธ การเติมเงิน
- ดูหลักฐานการชำระเงิน

#### Wallet Details
- **URL**: `/admin/wallets/{id}`
- ดูรายละเอียด Wallet ของผู้ใช้
- ปรับยอดเงิน (เพิ่ม/หัก)
- ดูประวัติธุรกรรม

#### Bonus Tier Management
- **URL**: `/admin/wallets/bonus-tiers`
- สร้าง/แก้ไข/ลบ เงื่อนไขโบนัส
- ตัวอย่าง:
  - เติม ฿500-999 รับ 5%
  - เติม ฿1,000-2,999 รับ 10%
  - เติม ฿3,000+ รับ 15%

---

## 3. Checkout Integration

### 3.1 Coupon Integration

คูปองถูกนำมาคำนวณหักส่วนลดจากยอดรวมก่อนชำระเงิน

```php
// In OrderController::checkout()
$appliedCoupon = null;
$couponDiscount = 0;

if (session()->has('applied_coupon')) {
    $coupon = Coupon::where('code', session('applied_coupon'))->first();
    if ($coupon && $coupon->canBeUsedBy(auth()->user(), $subtotal, $productIds)['valid']) {
        $appliedCoupon = $coupon;
        $couponDiscount = $coupon->calculateDiscount($subtotal);
    }
}
```

### 3.2 Wallet Payment

ผู้ใช้สามารถเลือกชำระด้วย Wallet ได้หากมียอดเพียงพอ

```php
// In OrderController::store()
if ($request->payment_method === 'wallet') {
    $wallet = Wallet::getOrCreateForUser(auth()->id());

    if ($wallet->balance < $total) {
        return redirect()->back()->with('error', 'ยอดเงินใน Wallet ไม่เพียงพอ');
    }

    $wallet->pay($total, "ชำระคำสั่งซื้อ #{$order->order_number}", $order);

    $order->update([
        'payment_status' => 'paid',
        'status' => 'processing',
        'paid_at' => now(),
    ]);
}
```

### 3.3 Payment Flow

1. User adds items to cart
2. User goes to checkout
3. User applies coupon (optional)
4. User selects payment method (PromptPay/Bank Transfer/Wallet)
5. If Wallet: Check balance → Deduct → Mark as paid
6. If Other: Create pending order → Wait for payment confirmation

---

## 4. Models & Methods

### 4.1 Coupon Model

```php
// Check if coupon is valid
$coupon->isValid(): bool

// Check if user can use coupon
$coupon->canBeUsedBy($user, $amount, $productIds): array

// Calculate discount
$coupon->calculateDiscount($amount): float

// Record usage
$coupon->recordUsage($user, $order, $discountAmount, $orderAmount): CouponUsage

// Generate unique code
Coupon::generateCode($length = 8): string
```

### 4.2 Wallet Model

```php
// Get or create wallet for user
Wallet::getOrCreateForUser($userId): Wallet

// Add money (topup)
$wallet->deposit($amount, $description, $reference = null): WalletTransaction

// Pay for order
$wallet->pay($amount, $description, $order = null): WalletTransaction

// Refund money
$wallet->refund($amount, $description, $order = null): WalletTransaction

// Add bonus
$wallet->addBonus($amount, $description): WalletTransaction

// Admin adjustment
$wallet->adjust($amount, $description, $adminId, $note = null): WalletTransaction
```

### 4.3 WalletTopup Model

```php
// Generate unique topup ID
WalletTopup::generateTopupId(): string

// Calculate bonus based on tiers
WalletTopup::calculateBonus($amount): float

// Approve topup (admin)
$topup->approve($adminId, $note = null): bool

// Reject topup (admin)
$topup->reject($adminId, $reason): bool
```

---

## 5. Routes Summary

### Web Routes

```php
// User Wallet Routes
Route::prefix('user/wallet')->middleware(['auth'])->group(function () {
    Route::get('/', [WalletController::class, 'index'])->name('user.wallet.index');
    Route::get('/topup', [WalletController::class, 'topup'])->name('user.wallet.topup');
    Route::post('/topup', [WalletController::class, 'submitTopup'])->name('user.wallet.submit-topup');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('user.wallet.transactions');
    Route::delete('/topup/{topup}', [WalletController::class, 'cancelTopup'])->name('user.wallet.cancel-topup');
    Route::get('/bonus-preview', [WalletController::class, 'bonusPreview'])->name('user.wallet.bonus-preview');
});

// Admin Coupon Routes
Route::prefix('admin/coupons')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [CouponController::class, 'index'])->name('admin.coupons.index');
    Route::get('/create', [CouponController::class, 'create'])->name('admin.coupons.create');
    Route::post('/', [CouponController::class, 'store'])->name('admin.coupons.store');
    Route::get('/{coupon}', [CouponController::class, 'show'])->name('admin.coupons.show');
    Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('admin.coupons.edit');
    Route::put('/{coupon}', [CouponController::class, 'update'])->name('admin.coupons.update');
    Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('admin.coupons.destroy');
});

// Admin Wallet Routes
Route::prefix('admin/wallets')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminWalletController::class, 'index'])->name('admin.wallets.index');
    Route::get('/wallets', [AdminWalletController::class, 'wallets'])->name('admin.wallets.wallets');
    Route::get('/wallets/{wallet}', [AdminWalletController::class, 'show'])->name('admin.wallets.show');
    Route::post('/wallets/{wallet}/adjust', [AdminWalletController::class, 'adjust'])->name('admin.wallets.adjust');
    Route::get('/topups', [AdminWalletController::class, 'topups'])->name('admin.wallets.topups');
    Route::get('/topups/{topup}', [AdminWalletController::class, 'showTopup'])->name('admin.wallets.topups.show');
    Route::post('/topups/{topup}/approve', [AdminWalletController::class, 'approveTopup'])->name('admin.wallets.topups.approve');
    Route::post('/topups/{topup}/reject', [AdminWalletController::class, 'rejectTopup'])->name('admin.wallets.topups.reject');
    Route::get('/transactions', [AdminWalletController::class, 'transactions'])->name('admin.wallets.transactions');
    Route::get('/bonus-tiers', [AdminWalletController::class, 'bonusTiers'])->name('admin.wallets.bonus-tiers');
    Route::post('/bonus-tiers', [AdminWalletController::class, 'storeBonusTier'])->name('admin.wallets.bonus-tiers.store');
    Route::put('/bonus-tiers/{tier}', [AdminWalletController::class, 'updateBonusTier'])->name('admin.wallets.bonus-tiers.update');
    Route::delete('/bonus-tiers/{tier}', [AdminWalletController::class, 'destroyBonusTier'])->name('admin.wallets.bonus-tiers.destroy');
});
```

### API Routes

```php
Route::prefix('v1/coupons')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/validate', [CouponController::class, 'validate']);
    Route::post('/apply', [CouponController::class, 'apply']);
    Route::delete('/remove', [CouponController::class, 'remove']);
});
```

---

## 6. Security Considerations

1. **Authorization**: ตรวจสอบสิทธิ์ผู้ใช้ก่อนทำธุรกรรม
2. **Transaction Integrity**: ใช้ Database Transaction เมื่อทำการเปลี่ยนแปลงยอดเงิน
3. **Audit Trail**: บันทึกทุกการเคลื่อนไหวของเงินพร้อม reference
4. **Rate Limiting**: จำกัดจำนวน request สำหรับ API endpoints
5. **Coupon Abuse Prevention**: จำกัดการใช้งานต่อ user, ตรวจสอบเงื่อนไข

---

## 7. Recent Changes (v1.0.225)

### Topup Expiry → Auto-Reject
- ✅ บิลเติมเงินที่หมดอายุจะถูก **ปฏิเสธอัตโนมัติ** พร้อมเหตุผล "หมดเวลาโอนเงิน - ระบบปฏิเสธอัตโนมัติ"
- ✅ AJAX polling แสดง reject_reason แบบ real-time
- ✅ Grace period recovery: ถ้า SMS มาหลัง auto-reject ระบบ recover กลับเป็น pending ได้
- ✅ รองรับ backward compatibility กับ status `expired` เดิม

### Input Validation
- ✅ ป้องกันยอดทศนิยมในฟอร์มเติมเงิน (frontend + backend `integer` validation)
- ✅ `inputmode="numeric"` สำหรับ mobile keyboard

### แนวทางสำหรับ Plugin (Laravel/WordPress)
การเปลี่ยนแปลงเหล่านี้ต้องนำไปปรับใน plugin ด้วย:

1. **Cleanup Job**: เปลี่ยนจาก `expired` เป็น `rejected` + set `reject_reason`
2. **API Response**: endpoint check-status ต้องคืน `reject_reason`
3. **Frontend Polling**: JS handler ต้องแสดง reject_reason เมื่อ status = rejected
4. **Grace Period**: query recovery ต้องรองรับทั้ง `expired` และ `rejected` ที่มี reject_reason ตรง
5. **Amount Validation**: ใช้ `integer` validation, block decimal input บน frontend

---

## 8. Future Improvements

- [ ] Email notifications สำหรับ topup status
- [ ] Automatic topup approval via payment gateway
- [ ] Wallet transfer between users
- [ ] Auto-apply coupon based on cart value
- [ ] Expired coupon cleanup job
- [ ] Wallet low balance notification
