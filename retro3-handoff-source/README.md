# XMAN Studio — Retro Tron Theme · Laravel Handoff (Full Replace)

**วิธีใช้ — แทนที่ home เดิมด้วย retro ไปเลย** (ไม่ต้องมี route ใหม่ ไม่ต้องเลือก theme)

## ไฟล์ที่ต้อง copy → วางในตำแหน่งเดิมของ repo

```
handoff-laravel/resources/css/retro-theme.css
  → resources/css/retro-theme.css   [ไฟล์ใหม่]

handoff-laravel/resources/views/layouts/retro.blade.php
  → resources/views/layouts/retro.blade.php   [ไฟล์ใหม่]

handoff-laravel/resources/views/partials/retro-hero.blade.php
  → resources/views/partials/retro-hero.blade.php   [ไฟล์ใหม่]

handoff-laravel/resources/views/partials/retro-services.blade.php
  → resources/views/partials/retro-services.blade.php   [ไฟล์ใหม่]

handoff-laravel/resources/views/partials/retro-metalx.blade.php
  → resources/views/partials/retro-metalx.blade.php   [ไฟล์ใหม่]

handoff-laravel/resources/views/partials/retro-footer.blade.php
  → resources/views/partials/retro-footer.blade.php   [ไฟล์ใหม่]

handoff-laravel/resources/views/home.blade.php
  → resources/views/home.blade.php   ⚠️ เขียนทับของเดิม! สำรองก่อน:
     cp resources/views/home.blade.php resources/views/home.blade.php.backup
```

## 2. Import CSS เข้า Vite bundle

แก้ `resources/css/app.css` — เพิ่ม **2 บรรทัด** ที่ **ท้ายไฟล์** (ลำดับสำคัญ):

```css
/* Retro Tron theme — ต้องอยู่ท้ายสุดเพื่อ override Tailwind v4 */
@import './retro-theme.css';
@import './retro-overrides.css';   /* ← ตัวนี้ยัด !important ทับ Tailwind base */
```

## 3. Build + Clear cache (ขั้นตอนที่ขาดไม่ได้!)

```bash
npm run build
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

> ถ้าใช้ OPcache ด้วย: `php artisan optimize:clear` หรือ restart php-fpm

## 4. Hard reload เบราว์เซอร์

`Cmd+Shift+R` (Mac) หรือ `Ctrl+Shift+F5` (Windows) — กัน CSS เก่าในแคช

---

## ถ้าเข้าหน้าแล้วยังเห็นของเดิม

เช็คตามนี้ตามลำดับ:

### A. ดู `<body>` ใน DevTools
เปิด DevTools → Elements → ดู `<body class="...">`:
- class เป็น `tron-body` ✅ = layout ใหม่ทำงาน
- class ยังเป็น `bg-gray-50 ...` ❌ = **home.blade.php ยังเป็นไฟล์เก่า** ตรวจว่า copy ทับถูกที่

### A2. ดู `<head>` มีบรรทัด `<link ... retro-theme.css>` หรือเปล่า
- มี ✅ = CSS bundle โหลดแล้ว
- ไม่มี ❌ = **ยังไม่ได้ `npm run build`** หรือลืม import

### B. เช็ค Vite manifest
`public/build/manifest.json` ควรมี hash ใหม่ ถ้า timestamp ยังเก่า = build ไม่ผ่าน

### C. เช็ค view cache
```bash
ls -la storage/framework/views/
# ถ้ามีไฟล์ .php timestamp เก่า → php artisan view:clear อีกครั้ง
```

### D. Font ไม่เปลี่ยน
Google Fonts ถูก block? เช็ค Network tab ดู request ไป `fonts.googleapis.com` — ถ้า block ให้ download ฟอนต์ลง `public/fonts/` แล้วเปลี่ยน `@import` ใน `retro-theme.css` เป็น `@font-face` local

### E. Page ขึ้นแต่สีเพี้ยน / ทับซ้อน
- Tailwind v4 อาจ override custom CSS → ตรวจลำดับใน `app.css` ว่า `@import './retro-theme.css';` อยู่ **หลัง** Tailwind imports
- หรือเพิ่ม `!important` ชั่วคราว test

---

## ถ้าจะ rollback

```bash
cp resources/views/home.blade.php.backup resources/views/home.blade.php
# ลบบรรทัด @import './retro-theme.css'; ใน app.css
npm run build
php artisan view:clear
```

---

## ทำไม retro ที่แล้วเอาไปใส่ไม่ได้

`home.blade.php` เดิมของคุณ **59KB** — มี hero / services / Metal-X / tech stack / stats ฝังเองทั้งหมด พร้อม `@extends('layouts.app')`. ถ้า Claude Code เอาแค่ CSS ไปใส่ → layout + markup เก่ายังคงอยู่ → CSS ใหม่ override ไม่ได้เพราะ Tailwind classes บน markup เก่ามี specificity สูงกว่า.

**วิธีนี้** (เขียนทับ `home.blade.php` + `@extends('layouts.retro')`) = เริ่มต้นใหม่จาก layout + partials ที่เป็นของ retro ทั้งหมด → **เห็นผลแน่นอน**
