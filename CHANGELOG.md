# Changelog

All notable changes to XMAN Studio will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.225] - 2026-02-12

### Changed
- **Wallet Topup: Expired → Rejected** — เปลี่ยนจากสถานะ `expired` เป็น `rejected` เมื่อบิลเติมเงินหมดอายุ
  - `SmsPaymentService::cleanup()` ตอนนี้ set `STATUS_REJECTED` + `reject_reason` = "หมดเวลาโอนเงิน - ระบบปฏิเสธอัตโนมัติ" แทน `STATUS_EXPIRED`
  - User เห็นเหตุผลการปฏิเสธชัดเจนบนหน้า topup-status
  - AJAX polling (`checkTopupStatus`) คืนค่า `reject_reason` ให้ frontend แสดงผลแบบ real-time
  - Grace period recovery รองรับทั้ง `expired` (เดิม) และ `rejected` ที่มี `reject_reason` ตรงกัน (ใหม่)

### Fixed
- **ป้องกันยอดทศนิยมในฟอร์มเติมเงิน** — User ไม่สามารถกรอกยอดเงินที่มีจุดทศนิยมได้อีกต่อไป
  - Frontend: บล็อกปุ่ม `.` และ `,` ด้วย `keydown` listener, ตัดทศนิยมเมื่อ paste, เพิ่ม `inputmode="numeric"` สำหรับมือถือ
  - Backend: เปลี่ยน validation จาก `numeric` เป็น `integer` ใน `WalletController::submitTopup()`
- **Foreign key constraint** — แก้ `created_by=0` และ `approved_by=0` ที่ละเมิด foreign key ใน wallet_transactions และ wallet_topups

### Files Modified
- `app/Services/SmsPaymentService.php` — cleanup() method: `STATUS_EXPIRED` → `STATUS_REJECTED` + `reject_reason`
- `app/Http/Controllers/User/WalletController.php` — checkTopupStatus() เพิ่ม `reject_reason` ใน JSON, submitTopup() เปลี่ยน `numeric` → `integer`
- `app/Models/SmsPaymentNotification.php` — attemptMatch() + matchWalletTopup(): grace period recovery รองรับ rejected
- `app/Http/Controllers/Api/V1/SmsPaymentController.php` — matchOrderByAmount(): query + recovery รองรับ rejected
- `resources/views/user/wallet/topup-status.blade.php` — เพิ่ม UI สำหรับ rejected/expired + AJAX polling แสดง reject_reason
- `resources/views/user/wallet/topup.blade.php` — ป้องกันทศนิยม: keydown block, input sanitize, inputmode="numeric"

---

## [1.0.0] - 2025-12-29

### Added
- Initial release of XMAN Studio
- E-commerce system with shopping cart and order management
- License key management system with activation tracking
- Custom software ordering with category separation
- Line OA integration for automatic order notifications
- Support ticket system with priority management
- Admin backend for complete management
- Comprehensive deployment system with auto-repair capabilities
- Laravel 11 with Symfony 7.x for PHP 8.3 compatibility
- Tailwind CSS v4 for modern UI design
- GitHub Actions workflows for CI/CD, versioning, and deployment

### Changed
- Migrated from Laravel 12 to Laravel 11 for better PHP 8.3 compatibility
- Updated Symfony packages to v7.x

### Fixed
- Migration execution order to resolve foreign key constraints
- Tailwind CSS v4 PostCSS configuration
- Deploy script to accept 'y' for production confirmation
- Auto-repair capability for migration errors
- NPM dependencies installation for vite

### Security
- PHP 8.2+ requirement with all security patches
- Proper input validation and sanitization
- CSRF protection on all forms
- SQL injection prevention through Eloquent ORM
