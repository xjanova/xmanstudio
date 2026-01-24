# XMAN Studio Development Guidelines

## Table of Contents
1. [Menu Structure](#menu-structure)
2. [Premium Theme System](#premium-theme-system)
3. [Color Schemes by Module](#color-schemes-by-module)
4. [Component Patterns](#component-patterns)
5. [Dark Mode Support](#dark-mode-support)
6. [Responsive Design](#responsive-design)

---

## Menu Structure

### Admin Sidebar Menu

เมนู Admin อยู่ที่ `resources/views/layouts/admin.blade.php`

#### การเพิ่มเมนูใหม่

```blade
<!-- เมนูเดี่ยว -->
<a href="{{ route('admin.your-route') }}"
   class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white {{ request()->routeIs('admin.your-route*') ? 'bg-gray-800 text-white' : '' }}">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- SVG path here -->
    </svg>
    ชื่อเมนู
</a>
```

#### การเพิ่มกลุ่มเมนู (Section Header)

```blade
<div class="px-4 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
    ชื่อกลุ่มเมนู
</div>
<!-- Menu items here -->
```

#### ลำดับกลุ่มเมนู (ปัจจุบัน)

1. **Dashboard** - Analytics, Premium Dashboard
2. **การเช่า** - รายการเช่า, การชำระเงิน, แพ็กเกจ, รายงาน
3. **จัดการเนื้อหา** - บริการ
4. **ผลิตภัณฑ์ & โปรแกรม** - หมวดหมู่, รายการผลิตภัณฑ์
5. **License & Devices** - จัดการ License, จัดการ Devices
6. **Line OA** - ส่งข้อความ, จัดการ Line UID
7. **Wallet & Promotion** - Wallet, Coupons
8. **คำสั่งซื้อ** - รายการคำสั่งซื้อ
9. **ผู้ใช้งาน** - จัดการผู้ใช้
10. **ตั้งค่า** - ตั้งค่าระบบ

### User Navigation

เมนู User อยู่ที่ `resources/views/layouts/app.blade.php`

รูปแบบเมนูผู้ใช้:
```blade
<a href="{{ route('user.your-route') }}"
   class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
    ชื่อเมนู
</a>
```

---

## Premium Theme System

### Page Background Pattern

ทุกหน้าควรใช้ gradient background:

```blade
<!-- สำหรับ User-facing pages -->
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Content here -->
    </div>
</div>
```

### Premium Header Banner (Admin Pages)

```blade
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-{color}-500 via-{color2}-500 to-{color3}-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-{accent}-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <!-- Icon SVG path -->
                    </svg>
                </div>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-white/20 text-white backdrop-blur-sm">
                    Module Name
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">Page Title</h1>
            <p class="text-white/80 mt-1">Page description</p>
        </div>
        <!-- Action buttons -->
        <a href="#" class="inline-flex items-center px-5 py-2.5 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Action Button
        </a>
    </div>
</div>
```

### Card Container Header (Tables/Lists)

```blade
<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="bg-gradient-to-r from-{color}-600 to-{color2}-600 px-6 py-4">
        <h2 class="text-lg font-semibold text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <!-- Icon -->
            </svg>
            Section Title
            <span class="ml-2 px-2 py-0.5 text-xs bg-white/20 rounded-full">{{ $count }} รายการ</span>
        </h2>
    </div>
    <!-- Table/Content here -->
</div>
```

---

## Color Schemes by Module

| Module | Primary Gradient | Secondary Color | Use Case |
|--------|------------------|-----------------|----------|
| **Coupon** | `from-amber-500 via-orange-500 to-red-500` | Yellow/Gold | Promotions, Discounts |
| **Wallet** | `from-purple-600 via-violet-600 to-indigo-600` | Purple/Indigo | Money, Balance |
| **Rental** | `from-pink-500 via-rose-500 to-red-500` | Pink/Rose | Subscriptions |
| **Order** | `from-blue-600 to-indigo-600` | Blue/Indigo | Transactions |
| **License** | `from-cyan-500 to-blue-600` | Cyan/Blue | Keys, Authentication |
| **User** | `from-teal-500 to-emerald-600` | Teal/Green | Profiles, Members |
| **Analytics** | `from-indigo-600 to-purple-600` | Indigo/Purple | Reports, Stats |
| **Settings** | `from-gray-600 to-slate-700` | Gray/Slate | Configuration |

### Gradient Usage Example

```blade
<!-- Coupon Module Header -->
<div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500">

<!-- Wallet Module Header -->
<div class="bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600">

<!-- Order Module Header -->
<div class="bg-gradient-to-r from-blue-600 to-indigo-600">
```

---

## Component Patterns

### Stats Cards

```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Label</p>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $value }}</h3>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-{color}-400 to-{color}-600 flex items-center justify-center shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <!-- Icon -->
                </svg>
            </div>
        </div>
    </div>
</div>
```

### Status Badges

```blade
<!-- Success/Completed -->
<span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-green-500 to-emerald-500 text-white">
    เสร็จสมบูรณ์
</span>

<!-- Pending -->
<span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-yellow-500 to-amber-500 text-white">
    รอดำเนินการ
</span>

<!-- Processing -->
<span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white">
    กำลังดำเนินการ
</span>

<!-- Error/Cancelled -->
<span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-red-500 to-rose-500 text-white">
    ยกเลิก
</span>
```

### Payment Method Badges

```blade
<!-- Wallet -->
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300">
    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
    </svg>
    Wallet
</span>

<!-- PromptPay -->
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
    PromptPay
</span>

<!-- Bank Transfer -->
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
    โอนเงิน
</span>
```

### Buttons

```blade
<!-- Primary Action -->
<button class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-medium transition shadow-lg">
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Primary Action
</button>

<!-- Secondary/Ghost Button -->
<button class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
    Secondary Action
</button>

<!-- Danger Button -->
<button class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 transition shadow-lg">
    Delete
</button>
```

### Form Inputs

```blade
<!-- Text Input -->
<input type="text" name="field"
       class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition"
       placeholder="Placeholder text">

<!-- Select -->
<select class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition">
    <option value="">Select option</option>
</select>

<!-- Textarea -->
<textarea rows="4"
          class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition resize-none"
          placeholder="Description"></textarea>
```

### Empty State

```blade
<div class="text-center py-16 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700">
    <div class="w-20 h-20 mx-auto bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Empty state icon -->
        </svg>
    </div>
    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ไม่พบข้อมูล</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">คำอธิบาย</p>
    <a href="#" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 font-semibold transition shadow-lg">
        Action Button
    </a>
</div>
```

---

## Dark Mode Support

ทุก element ต้องมี dark mode classes:

```blade
<!-- Background -->
bg-white dark:bg-gray-800

<!-- Text -->
text-gray-900 dark:text-white
text-gray-500 dark:text-gray-400

<!-- Borders -->
border-gray-200 dark:border-gray-700

<!-- Hover States -->
hover:bg-gray-50 dark:hover:bg-gray-700

<!-- Semi-transparent backgrounds -->
bg-white/80 dark:bg-gray-800/80
```

### Dark Mode Pattern Checklist

- [ ] Background colors have dark variants
- [ ] Text colors have dark variants
- [ ] Border colors have dark variants
- [ ] Hover states have dark variants
- [ ] Form inputs have dark variants
- [ ] Badges/pills have dark variants

---

## Responsive Design

### Breakpoint Reference

| Breakpoint | Min Width | Usage |
|------------|-----------|-------|
| `sm:` | 640px | Small tablets |
| `md:` | 768px | Tablets |
| `lg:` | 1024px | Laptops |
| `xl:` | 1280px | Desktops |
| `2xl:` | 1536px | Large screens |

### Mobile-First Approach

```blade
<!-- Grid: 1 col mobile, 2 cols tablet, 4 cols desktop -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

<!-- Hide on mobile, show on desktop -->
<div class="hidden md:block">

<!-- Show on mobile, hide on desktop -->
<div class="md:hidden">

<!-- Responsive padding -->
<div class="p-4 sm:p-6 lg:p-8">

<!-- Responsive text size -->
<h1 class="text-2xl sm:text-3xl lg:text-4xl">
```

### Mobile Card View Pattern

สำหรับ tables ที่ต้อง responsive ให้ใช้ pattern นี้:

```blade
<!-- Desktop Table -->
<div class="hidden md:block">
    <table>...</table>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4">
    @foreach($items as $item)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <!-- Card content -->
        </div>
    @endforeach
</div>
```

---

## Common Icons (Heroicons)

```blade
<!-- Plus/Add -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>

<!-- Edit/Pencil -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>

<!-- Delete/Trash -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>

<!-- View/Eye -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>

<!-- Search -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>

<!-- Check/Success -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>

<!-- X/Close/Error -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>

<!-- Chevron Right -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>

<!-- Chevron Left -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>

<!-- Download -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>

<!-- Wallet -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>

<!-- Coupon/Tag -->
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
```

---

## New Page Checklist

เมื่อสร้างหน้าใหม่ ให้ตรวจสอบรายการนี้:

### Admin Page
- [ ] เพิ่มเมนูใน `layouts/admin.blade.php`
- [ ] ใช้ Premium Header Banner ตาม color scheme ของ module
- [ ] มี Stats Cards ถ้าเหมาะสม
- [ ] Table มี gradient header
- [ ] รองรับ Dark Mode
- [ ] Responsive (Desktop + Mobile view)

### User Page
- [ ] ใช้ gradient background
- [ ] Cards มี `backdrop-blur-sm` และ `rounded-2xl`
- [ ] มี breadcrumb/back link
- [ ] Status badges ใช้ gradient
- [ ] รองรับ Dark Mode
- [ ] Responsive (Desktop + Mobile view)

### Form Page
- [ ] Input fields มี rounded-xl และ focus ring
- [ ] Buttons ใช้ gradient
- [ ] Validation errors styled properly
- [ ] Loading states
- [ ] Dark Mode support

---

## File Naming Convention

```
resources/views/
├── admin/
│   └── {module}/
│       ├── index.blade.php      # List page
│       ├── create.blade.php     # Create form
│       ├── edit.blade.php       # Edit form
│       └── show.blade.php       # Detail view
├── user/
│   └── {module}/
│       ├── index.blade.php
│       └── ...
└── layouts/
    ├── admin.blade.php          # Admin layout
    ├── app.blade.php            # User layout
    └── ...
```

---

## Animation Classes (Custom)

เพิ่มใน CSS ถ้ายังไม่มี:

```css
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}
```

---

## Quick Reference Card

| Element | Classes |
|---------|---------|
| Page bg | `bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950` |
| Card | `bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700` |
| Header gradient | `bg-gradient-to-r from-{color}-600 to-{color2}-600` |
| Primary button | `bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl` |
| Input | `rounded-xl focus:ring-2 focus:ring-blue-500` |
| Badge | `px-2.5 py-1 rounded-full text-xs font-semibold` |
| Icon container | `w-14 h-14 rounded-2xl bg-gradient-to-br flex items-center justify-center` |
