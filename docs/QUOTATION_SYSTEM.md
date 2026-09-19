# ระบบใบเสนอราคา & ใบแจ้งหนี้ — XMAN Studio

เอกสารนี้อธิบายเส้นทางทั้งเส้นของงานหนึ่งชิ้น: ลูกค้ากดเลือกที่ `/quote` →
ได้ใบเสนอราคา → ตอบรับ → กลายเป็นโครงการ + ใบแจ้งหนี้รายงวด → เก็บเงิน

---

## สารบัญ

1. [แผนที่ไฟล์](#1-แผนที่ไฟล์)
2. [สถานะของใบเสนอราคา](#2-สถานะของใบเสนอราคา)
3. [เงื่อนไขราคาที่แอดมินตั้งได้](#3-เงื่อนไขราคาที่แอดมินตั้งได้)
4. [เอกสาร PDF](#4-เอกสาร-pdf)
5. [ฉบับแก้ไข (เวอร์ชัน)](#5-ฉบับแก้ไข-เวอร์ชัน)
6. [ตอบรับ → โครงการ + ใบแจ้งหนี้](#6-ตอบรับ--โครงการ--ใบแจ้งหนี้)
7. [การตามงาน](#7-การตามงาน)
8. [กับดักที่เคยเจอจริง](#8-กับดักที่เคยเจอจริง)

---

## 1. แผนที่ไฟล์

| ชั้น | ไฟล์ | หน้าที่ |
|---|---|---|
| หน้าสั่งงาน | `resources/views/quote/index.blade.php` | ตัวสร้างใบเสนอราคา (Alpine `quoteBuilder`) + เก็บร่างใน localStorage |
| ตัวเลือก/ผลลัพธ์ | `app/Support/Quotation/Outcomes.php` | 8 ผลลัพธ์ปลายทาง แนะนำตัวเลือกให้เอง |
| ภาษี | `app/Support/Quotation/VatMode.php` | exclusive / inclusive / none + หัก ณ ที่จ่าย + อ่านจำนวนเงินเป็นตัวอักษร |
| ราคา | `app/Support/Quotation/Pricing.php` | **ส่วนลดตามยอด · ค่าเร่ง · อายุเอกสาร · งวดชำระ** อ่านจาก settings |
| คอนโทรลเลอร์หลัก | `app/Http/Controllers/QuotationController.php` | คิดราคา ออกใบ ส่งเมล หน้าเอกสารสาธารณะ รับคำตอบ |
| ตอบรับ | `app/Services/QuotationAcceptance.php` | สร้างโครงการ + ใบแจ้งหนี้รายงวด (ทางเดียวที่ทุกเส้นทางใช้ร่วมกัน) |
| ใบแจ้งหนี้ | `app/Http/Controllers/InvoiceController.php`, `app/Models/ProjectInvoice.php` | เอกสารรายงวด เปิดด้วยโทเคน |
| ตามงาน | `app/Console/Commands/QuotationFollowUpCommand.php` | เตือนลูกค้าหนึ่งครั้งก่อนหมดอายุ |
| แอดมิน | `app/Http/Controllers/Admin/QuotationController.php` | รายการ · รายละเอียด · เปลี่ยนสถานะ · ออกฉบับแก้ไข · จัดการงวด |
| แอดมิน (ราคา) | `app/Http/Controllers/Admin/QuotationPricingController.php` | `/admin/quotations/pricing` |
| แอดมิน (ตัวเลือก) | `app/Http/Controllers/Admin/QuotationOptionController.php` | ตัวเลือก + กฎการขาย (`is_core`, `requires`, `suggested_for`) |

เทสต์: `tests/Feature/QuotationFlowTest.php` (เส้นทางหลัก), `QuotationVatTest.php` (ภาษี),
`QuotationLifecycleTest.php` (หลังส่ง: ตาม · แก้ไข · ตอบรับ · ใบแจ้งหนี้ · settings)

---

## 2. สถานะของใบเสนอราคา

คอลัมน์ `quotations.status` เป็น **MySQL enum** ค่าที่รับได้มีเท่านี้:

```
draft · sent · viewed · accepted · rejected · expired · paid
```

รายการเดียวกันอยู่ใน `Quotation::STATUSES` โค้ดที่เขียนสถานะต้อง validate กับค่านี้
(`Rule::in(Quotation::STATUSES)`) **ห้ามคิดค่าใหม่เองโดยไม่แก้ migration ก่อน** —
ดูเหตุผลใน [กับดัก](#8-กับดักที่เคยเจอจริง)

| สถานะ | ความหมาย | ใครเขียน |
|---|---|---|
| `draft` | ยังไม่ส่ง (รวมฉบับแก้ไขที่เพิ่งออก) | ระบบ / ออกฉบับแก้ไข |
| `sent` | ส่งอีเมลแล้ว | `markAsSent()` |
| `viewed` | ลูกค้าเปิดลิงก์แล้ว (ครั้งแรกเท่านั้น) | `markAsViewed()` |
| `accepted` | ตอบรับ → มีโครงการ + ใบแจ้งหนี้ | `markAsAccepted()` |
| `rejected` | ไม่รับ (ถ้าลูกค้ากดเอง จะมี `declined_at` ด้วย) | `markAsDeclined()` / แอดมิน |
| `expired` | เลยวันยืนราคา | แอดมิน |
| `paid` | เก็บเงินครบ | `markAsPaid()` |

นอกจากสถานะยังมี `superseded_at` — ฉบับที่ถูกแทนด้วยเวอร์ชันใหม่
`awaitingResponse()` จะเป็น false ทันทีที่ฟิลด์นี้ไม่ว่าง (ตอบรับไม่ได้อีก แต่ยังเปิดอ่านได้)

`project_invoices.status` **ไม่ใช่ enum** ตั้งใจให้เป็น string + whitelist ใน
`ProjectInvoice::STATUSES` (`scheduled · issued · paid · void`) เพิ่มค่าใหม่ได้โดยไม่ต้อง migrate

---

## 3. เงื่อนไขราคาที่แอดมินตั้งได้

หน้า `/admin/quotations/pricing` เขียนลง `settings` ห้าแถว ทุกที่อ่านผ่าน
`App\Support\Quotation\Pricing` เท่านั้น (หน้าสั่งงาน + PDF + ใบแจ้งหนี้ ใช้ค่าเดียวกัน)

| setting | ชนิด | ค่าเริ่มต้น | ใช้ที่ไหน |
|---|---|---|---|
| `quote_discount_tiers` | json | 200k→5% · 500k→10% · 1M→15% | ยอดถึงขั้นไหนลดเท่าไร + ข้อความ "อีก x บาทจะได้ y%" |
| `quote_rush_percent` | string | `25` | ค่าเร่งงาน คิดจากยอดหลังหักส่วนลด · ใส่ 0 = ปิด |
| `quote_valid_days` | integer | `30` | อายุใบเสนอราคา |
| `quote_due_days` | integer | `7` | กำหนดชำระงวดแรก นับจากวันตอบรับ |
| `quote_payment_split` | json | 50/25/25 | งวดชำระ **ต้องรวมได้ 100%** |

**ทุกการอ่านผ่านตัวกรอง** ค่าที่พังหรือแก้มือมาผิด จะถอยไปใช้ค่าเริ่มต้นแทนที่จะทำให้
หน้าเว็บสาธารณะพัง — ส่วนฝั่งบันทึกจะ **ปฏิเสธ** แผนที่รวมไม่ได้ 100% พร้อมบอกว่าตอนนี้รวมได้เท่าไร
(ถ้าปล่อยผ่านแล้วให้ระบบถอยไปใช้ค่าเริ่มต้น แอดมินจะเห็นหน้าเขียน 100 แต่เอกสารพิมพ์ 50/25/25)

VAT ยังคงที่ 7% ใน `VatMode::DEFAULT_RATE` เพราะเป็นอัตราตามกฎหมาย ไม่ใช่โปรโมชัน
แต่ละใบเก็บ `vat_rate` ของตัวเองไว้ เอกสารเก่าจึงอ่านถูกแม้อัตราจะเปลี่ยนในอนาคต

---

## 4. เอกสาร PDF

| เอกสาร | view | เข้าถึง |
|---|---|---|
| ใบเสนอราคา | `resources/views/quotation/pdf.blade.php` | `/quote/d/{token}/pdf` |
| ใบแจ้งหนี้ | `resources/views/invoice/pdf.blade.php` | `/invoice/{token}/pdf`, `/admin/invoices/{id}/pdf` |

**ทุกเอกสารต้องออกผ่าน `App\Support\ThaiPdf::view()` ห้ามเรียก `Pdf::loadView()` ตรง ๆ**
(มีเทสต์ไล่ทุกคอนโทรลเลอร์ไม่ให้หลุด) เพราะ DomPDF ไม่ shape ภาษาไทย — ดู [ข้อ 8.6](#86-วรรณยุกต์ไทยซ้อนสระในเอกสาร-pdf)

ข้อบังคับของ DomPDF:

- **ไม่รองรับ flexbox และ grid** ใช้ `<table>` เท่านั้น — ใส่ `display:flex` จะไม่ error
  แต่กล่องจะทับกันเงียบ ๆ
- รูปต้องเป็น **path บนดิสก์** ไม่ใช่ URL (`companyInfo()['logo_path']`)
- ฟอนต์ที่เอกสารฝังคือ **`storage/fonts/Sarabun-PUA-*.ttf`** ไม่ใช่ `Sarabun-*.ttf` ธรรมดา
  (สร้างด้วย `resources/fonts/build_thai_pua_font.py`) — `App\Support\FontFile` ตรวจ magic bytes ตอนบูต
- ข้อมูลบริษัททั้งหมดมาจาก `settings` ไม่มี hardcode:
  `company_name`, `contact_address`, `contact_email`, `contact_phone`, `contact_line_id`,
  `company_tax_id`, `site_logo` — แก้ที่ `/admin/contact-settings`
- `company_tax_id` ว่างได้ แล้วเอกสารจะ **ไม่พิมพ์บรรทัดนั้นเลย** (เลขปลอมบนเอกสารภาษีแย่กว่าไม่มีเลข)

ตัวเลขบนเอกสารที่ออกไปแล้วต้อง **ไม่เปลี่ยน** — `documentData()` อ่านจากแถวในตาราง
ไม่เรียกเครื่องคิดเลขซ้ำ และ `ProjectInvoice` แช่ยอดของงวดไว้ตอนตอบรับ

---

## 5. ฉบับแก้ไข (เวอร์ชัน)

ลูกค้าขอต่อรอง → แอดมินกด "ออกฉบับแก้ไข" ที่หน้ารายละเอียด

```
QT-20260919-A1B2 (v1, superseded) ──> QT-20260919-A1B2 Rev.2 (v2, draft)
```

- **เลขที่ใบเดิม** เวอร์ชันเพิ่ม → บัญชีและอีเมลยังเห็นเป็นเรื่องเดียว
- `quote_number` ไม่ unique เดี่ยว ๆ แล้ว — unique เป็นคู่ `(quote_number, version)`
- `revision_of` ชี้ไปที่ฉบับแรกสุดของสาย (ไม่ใช่ฉบับก่อนหน้า) `revision_note` เก็บเหตุผล
- ฉบับเก่าได้ `superseded_at` → เปิดอ่านได้ ตอบรับไม่ได้ และหน้าเอกสารบอกว่ามีฉบับใหม่แล้ว
- ใบที่ `accepted` หรือ `paid` ออกฉบับแก้ไขไม่ได้ (ให้ออกใบใหม่)
- `displayNumber()` คือสิ่งที่ต้องพิมพ์ทุกที่ — v1 พิมพ์เลขเปล่า v2 ขึ้นไปต่อท้าย `Rev.N`

---

## 6. ตอบรับ → โครงการ + ใบแจ้งหนี้

มีสามทางที่ทำให้ใบเสนอราคากลายเป็นงาน และ **ทั้งสามเรียก `QuotationAcceptance::projectFor()`**

1. ลูกค้ากด "ตอบรับ" ที่ `/quote/d/{token}` (POST respond)
2. แอดมินเปลี่ยนสถานะเป็น `accepted`
3. แอดมินกด "สร้างโครงการจากใบเสนอราคา"

สิ่งที่เกิดขึ้น (ใน transaction เดียว):

```
ProjectOrder            ← ชื่อ/ประเภท/ยอดรวม/วันเริ่ม จากใบเสนอราคา
  ├── ProjectFeature[]  ← ทุกตัวเลือกที่ลูกค้าเลือก (service + additional)
  ├── ProjectTimeline   ← หมุดแรก "รับงาน"
  └── ProjectInvoice[]  ← ตาม Pricing::instalments()
        งวด 1 → issued  + due_date = วันนี้ + quote_due_days
        งวด 2..n → scheduled (ยังไม่เรียกเก็บ ไม่มีกำหนดชำระ)
```

- **idempotent** กดซ้ำ/ตอบรับซ้ำ ได้โครงการเดิมชุดเดิม ไม่เพิ่มใบแจ้งหนี้
- โครงการเก่าที่สร้างก่อนมีระบบงวด จะได้ใบแจ้งหนี้เติมให้ตอนเรียกซ้ำ
- **งวดสุดท้ายรับเศษสตางค์** เพื่อให้ทุกงวดรวมกันเท่ายอดสุทธิเป๊ะ
- `project.paid_amount` / `payment_status` คำนวณจากงวดที่ชำระจริง (`syncPayment()`)
  ไม่ใช่กรอกมือ · งวดที่ `void` ไม่นับเป็นยอดที่ต้องเก็บ
- ลูกค้าเห็นงวดที่เรียกเก็บแล้วในหน้าโครงการของตัวเอง (`/my-account/projects/{id}`)
  งวดที่ยัง `scheduled` **ไม่โชว์** — ยังไม่ได้เรียกเก็บ ไม่ต้องทำให้กังวล

---

## 7. การตามงาน

```bash
php artisan quotations:follow-up            # ส่งจริง
php artisan quotations:follow-up --dry-run  # ดูว่าจะส่งถึงใคร
php artisan quotations:follow-up --days=5   # เตือนล่วงหน้า 5 วัน
```

ตั้ง schedule ไว้แล้วใน `routes/console.php` — วันละครั้ง 09:00 (Asia/Bangkok)

เงื่อนไขที่จะได้รับเตือน (`Quotation::scopeNeedsFollowUp`) ต้องครบทุกข้อ:
สถานะ `sent`/`viewed` · มี `sent_at` · `follow_up_sent_at` ว่าง · ไม่ถูก superseded ·
`valid_until` อยู่ระหว่างวันนี้ถึง +N วัน (ไม่เตือนใบที่หมดอายุไปแล้ว)

**เตือนครั้งเดียวต่อใบ ตลอดกาล** — `follow_up_sent_at` เขียนหลังเมลออกสำเร็จเท่านั้น
เมลล่มแล้วรอบหน้าจะลองใหม่ ไม่ใช่ข้ามลูกค้าไปเลย

แอดมินดูรายการที่ต้องตามได้ที่ `/admin/quotations/manage?follow_up=1`
เรียงจากที่เงียบนานที่สุด บอกว่าเปิดดูแล้วหรือยัง และเตือนไปแล้วหรือยัง
หลังส่งเสร็จมีการ์ดสรุปเข้า Telegram (`BusinessAlerts::quotationsChased`) ใบเดียวต่อรอบ

---

## 8. กับดักที่เคยเจอจริง

### 8.1 สถานะที่ enum ไม่รู้จัก = 500 บนโปรดักชัน แต่เทสต์เขียว

`markAsDeclined()` เคยเขียน `status = 'declined'` ซึ่ง **ไม่มีใน enum**

- SQLite (เทสต์/เครื่อง dev) เก็บให้เฉย ๆ ไม่ฟ้อง → เทสต์ผ่านหมด
- MySQL โปรดักชันรัน `STRICT_TRANS_TABLES` → SQLSTATE error → ปุ่ม "ไม่รับข้อเสนอ" ของลูกค้าพัง 500

**กฎ:** ค่าที่เขียนลงคอลัมน์ enum ต้องอยู่ใน `Quotation::STATUSES` และต้อง validate ด้วย
`Rule::in(Quotation::STATUSES)` ตารางใหม่ให้ใช้ `string` + whitelist ในโมเดลแทน enum
(ดู `project_invoices.status`)

### 8.2 migration ที่แก้ enum/index ต้องเขียนแบบรู้ไดรเวอร์

SQLite ไม่มี ENUM (Laravel เรนเดอร์เป็น `varchar check (...)`) การขยายค่าจึงต้อง
`->change()` ส่วน MySQL ต้อง `ALTER TABLE ... MODIFY COLUMN` ดูตัวอย่างที่
`2026_03_17_120334_add_deleted_to_privacy_status_enum_in_metal_x_videos.php`

ก่อน `dropUnique('ชื่อ_index')` ให้เช็กชื่อจริงบนโปรดักชันก่อน — ชื่อไม่ตรง = deploy ล้ม

### 8.3 ตัวเลขบนหน้าเว็บกับบนเอกสารต้องมาจากที่เดียวกัน

JS ในหน้าสั่งงานคิดราคาโชว์สด ๆ ส่วน PHP คิดใหม่ตอนส่ง ถ้าสองฝั่งฝังค่าคนละชุด
(เช่น `* 0.25` ใน JS กับ settings ใน PHP) ลูกค้าจะเห็นเลขหนึ่ง แล้วได้เอกสารอีกเลข
ตอนนี้ `catalogue.rushPercent` / `catalogue.discountTiers` ส่งจาก `Pricing` ไปให้ JS ใช้

### 8.4 ปัดเศษงวดชำระ

`round(total * 25%)` สามครั้งอาจรวมกันขาดหรือเกินหนึ่งสตางค์
`Pricing::instalments()` ให้ **งวดสุดท้าย** รับเศษ (ไม่ใช่งวดแรก) เพราะงวดแรกที่เลขไม่ตรง
เปอร์เซ็นต์บนเอกสารคือตัวที่ลูกค้าทัก

### 8.6 วรรณยุกต์ไทยซ้อนสระในเอกสาร PDF

DomPDF วางทุก glyph ตามตำแหน่งเริ่มต้นของฟอนต์ และ **ไม่อ่าน GSUB/GPOS เลย** ผลคือ

- วรรณยุกต์บนสระบน (ใบแจ้ง**หนี้** งวด**ที่** **ชื่อ** **น้ำ**) ถูกวาดที่ความสูงเดียวกับสระ → ทับกันเป็นก้อน
  บางครั้งดูเหมือนวรรณยุกต์หายไปเลย
- มาร์กบนพยัญชนะสูง (**ป่า ฟ้า ฝั่ง ปิด**) ไปเกาะบนหางตัวอักษร
- ญ/ฐ ที่มีสระล่าง (**ญุ ฐุ**) หางชนสระ

เบราว์เซอร์แก้ให้เองด้วย GPOS — PDF ไม่มีให้

**วิธีแก้ (ทำแล้ว):** วิธีเดียวกับที่ฟอนต์ไทยเคยใช้กับ renderer ที่ shape ไม่ได้ —
เอา glyph มาร์กที่ "วางตำแหน่งไว้แล้ว" ใส่ไว้ใน Private Use Area แล้วสลับเข้าไปตอนจะวาด

| ชิ้นส่วน | ไฟล์ |
|---|---|
| สร้างฟอนต์ | `resources/fonts/build_thai_pua_font.py` → `storage/fonts/Sarabun-PUA-{Regular,Bold}.ttf` |
| สลับตัวอักษร | `App\Support\ThaiShaper::shape()` / `shapeHtml()` |
| ทางออก PDF ทางเดียว | `App\Support\ThaiPdf::view()` (shape HTML ที่เรนเดอร์เสร็จแล้ว ค่อยส่งให้ DomPDF) |
| รูป OG (GD ก็ไม่ shape เหมือนกัน) | `OgImageController` shape ก่อนวัดและก่อนวาด |

**ผัง PUA ในสคริปต์สร้างฟอนต์กับใน ThaiShaper ต้องตรงกันเสมอ** แก้ที่เดียวไม่ได้ —
`ThaiPdfFontTest::test_every_cluster_the_shaper_produces_exists_in_both_fonts` ไล่ทุกคลัสเตอร์
ที่ shaper สร้างได้ แล้วเช็กว่าฟอนต์ทั้งสองน้ำหนักมี code point นั้นจริง

**ข้อแลกเปลี่ยน:** ข้อความที่ถูกสลับเป็น PUA ถ้า copy ออกจาก PDF จะได้ code point ของ PUA
ไม่ใช่ตัวอักษรไทยเดิม (ตัวเลข/ภาษาอังกฤษ/คำที่ไม่มีมาร์กซ้อนไม่กระทบ) จึง **shape ให้ช้าที่สุด**
คือบน HTML ที่เรนเดอร์เสร็จแล้วเท่านั้น ห้าม shape ก่อนเก็บลงฐานข้อมูลหรือก่อนเปรียบเทียบ

ถ้าวันหนึ่งต้องการให้ copy ได้ครบ ทางเลือกคือย้ายไปใช้ mPDF ซึ่งมี OTL engine ของตัวเอง
(ต้องรื้อเทมเพลตทั้งสองใบ)

### 8.5 โทเคนของเอกสารต้องไม่หลุดออก API

`Quotation` และ `ProjectInvoice` ใส่ `public_token` ไว้ใน `$hidden` แล้ว
เลขที่เอกสาร (`QT-วันที่-สุ่ม4ตัว`) ไล่เดาได้ในหนึ่งบ่าย ลิงก์สาธารณะจึงใช้ 64 hex แยกของตัวเอง
และเช็ครูปแบบด้วย regex ก่อนยิง query ทุกครั้ง
