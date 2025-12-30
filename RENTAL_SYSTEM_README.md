# XMAN Studio Rental & License System

ระบบเช่าใช้งานและ License สำหรับ XMAN Studio

## Overview

ระบบนี้ย้ายมาจาก PostXAgent และปรับให้เข้ากับโครงสร้างของ xmanstudio โดยใช้:
- Laravel 11.x
- Blade Templates + Tailwind CSS
- Session-based Authentication
- Thai Payment Methods (PromptPay, Bank Transfer)

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Configure Environment

เพิ่มใน `.env`:

```env
# PromptPay
PROMPTPAY_NUMBER=0812345678

# Bank Accounts
KBANK_ACCOUNT_NUMBER=XXX-X-XXXXX-X
SCB_ACCOUNT_NUMBER=XXX-X-XXXXX-X
BANK_ACCOUNT_NAME="XMAN Studio Co., Ltd."

# Credit Card (optional)
CARD_PAYMENT_ENABLED=false
CARD_GATEWAY=omise
CARD_PUBLIC_KEY=
CARD_SECRET_KEY=
```

### 3. Seed Initial Data (optional)

```bash
php artisan db:seed --class=RentalPackageSeeder
```

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── RentalController.php          # User rental pages
│   │   ├── Admin/
│   │   │   └── RentalController.php      # Admin rental management
│   │   └── Api/
│   │       └── LicenseApiController.php  # License API for desktop apps
│   └── Middleware/
│       └── AdminMiddleware.php           # Admin role check
├── Models/
│   ├── RentalPackage.php                 # Rental package definitions
│   ├── UserRental.php                    # User rental subscriptions
│   ├── RentalPayment.php                 # Payment records
│   ├── RentalInvoice.php                 # Invoices/receipts
│   ├── PromoCode.php                     # Promo/discount codes
│   ├── PromoCodeUsage.php                # Promo code usage tracking
│   ├── LicenseKey.php                    # License key management
│   └── User.php                          # Updated with rental relations
└── Services/
    ├── RentalService.php                 # Rental business logic
    ├── LicenseService.php                # License validation logic
    └── ThaiPaymentService.php            # Thai payment methods

database/migrations/
├── 2025_12_30_000001_create_rental_packages_table.php
├── 2025_12_30_000002_create_user_rentals_table.php
├── 2025_12_30_000003_create_rental_payments_table.php
├── 2025_12_30_000004_create_promo_codes_table.php
├── 2025_12_30_000005_create_rental_invoices_table.php
├── 2025_12_30_000006_enhance_license_keys_table.php
└── 2025_12_30_000007_add_role_to_users_table.php

resources/views/
├── rental/
│   ├── packages.blade.php               # Package listing
│   ├── checkout.blade.php               # Checkout page
│   ├── payment.blade.php                # Payment page (QR/Bank)
│   ├── status.blade.php                 # User rental status
│   └── invoice.blade.php                # Invoice view
├── admin/rentals/
│   ├── index.blade.php                  # Rental list
│   ├── show.blade.php                   # Rental details
│   ├── payments.blade.php               # Payment management
│   ├── packages.blade.php               # Package list
│   ├── package-form.blade.php           # Package create/edit
│   └── reports.blade.php                # Revenue reports
└── layouts/
    └── admin.blade.php                  # Admin layout

routes/
├── web.php                              # Web routes (rental + admin)
└── api.php                              # API routes (license)

config/
└── payment.php                          # Payment configuration
```

## Routes

### Public Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/rental` | Package listing |

### Authenticated Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/rental/status` | User rental status |
| GET | `/rental/checkout/{package}` | Checkout page |
| POST | `/rental/subscribe/{package}` | Subscribe to package |
| POST | `/rental/validate-promo` | Validate promo code |
| GET | `/rental/payment/{payment}` | Payment page |
| POST | `/rental/payment/{payment}/confirm` | Confirm payment |
| GET | `/rental/invoice/{invoice}` | View invoice |

### Admin Routes (requires admin role)

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/rentals` | Rental list |
| GET | `/admin/rentals/{rental}` | Rental details |
| POST | `/admin/rentals/{rental}/extend` | Extend rental |
| POST | `/admin/rentals/{rental}/suspend` | Suspend rental |
| POST | `/admin/rentals/{rental}/reactivate` | Reactivate rental |
| GET | `/admin/rentals/payments` | Payment list |
| POST | `/admin/rentals/payments/{payment}/verify` | Verify payment |
| POST | `/admin/rentals/payments/{payment}/reject` | Reject payment |
| GET | `/admin/rentals/packages` | Package list |
| GET | `/admin/rentals/packages/create` | Create package form |
| POST | `/admin/rentals/packages` | Store package |
| GET | `/admin/rentals/packages/{package}/edit` | Edit package form |
| PUT | `/admin/rentals/packages/{package}` | Update package |
| POST | `/admin/rentals/packages/{package}/toggle` | Toggle package status |
| GET | `/admin/rentals/reports` | Revenue reports |

### License API Routes

| Method | URI | Description |
|--------|-----|-------------|
| POST | `/api/v1/license/activate` | Activate license |
| POST | `/api/v1/license/validate` | Validate license |
| POST | `/api/v1/license/deactivate` | Deactivate license |
| GET | `/api/v1/license/status/{licenseKey}` | Check license status |

## Features

### Rental System
- Multiple package types (hourly, daily, weekly, monthly, yearly)
- Thai payment methods (PromptPay QR, Bank Transfer, Credit Card)
- Promo code support (percentage/fixed discount)
- Auto-expiry handling
- Usage tracking and limits
- Admin management panel
- Revenue reports

### License System
- Machine fingerprint activation
- Demo license support
- Multiple license types (monthly, yearly, lifetime)
- Desktop app validation API

## Thai Localization

ระบบรองรับภาษาไทยเต็มรูปแบบ:
- UI texts ภาษาไทย
- Thai Baht (฿) currency
- Thai date formats
- Thai payment methods

## Migration from PostXAgent

Files removed from PostXAgent:
- app/Http/Controllers/Api/RentalController.php
- app/Http/Controllers/Api/AdminRentalController.php
- app/Http/Controllers/Api/LicenseController.php
- app/Models/RentalPackage.php
- app/Models/UserRental.php
- app/Models/Payment.php
- app/Models/Invoice.php
- app/Models/License.php
- app/Models/PromoCode.php
- app/Services/RentalService.php
- app/Http/Middleware/CheckActiveRental.php
- app/Http/Middleware/CheckRentalLimits.php
- Related migrations, seeders, and notifications

---

Migrated: December 30, 2025
