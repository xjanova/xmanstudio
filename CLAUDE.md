# CLAUDE.md - Development Guidelines for Claude

This file provides guidance to Claude Code (claude.ai/code) when working with this codebase.

## Project Overview

XMAN Studio is a Laravel-based web application with a dual-theme system (Classic/Premium). The application includes:
- **Public pages** - Landing page, products, services, rental packages
- **Customer portal** - Dashboard, licenses, subscriptions, orders, support
- **Admin panel** - Full management dashboard with analytics

## Theme System

### Important: Dual Theme Architecture

The application supports two themes that are selected via Settings:
- **Classic** - Light mode theme
- **Premium** - Dark mode theme with animations and gradients

### Theme Files Structure

```
resources/views/layouts/
├── app.blade.php              # Public - Classic
├── app-premium.blade.php      # Public - Premium
├── customer.blade.php         # Customer - Classic
├── customer-premium.blade.php # Customer - Premium
├── admin.blade.php            # Admin - Classic
├── admin-premium.blade.php    # Admin - Premium
└── guest.blade.php            # Auth pages (login/register)
```

### How Theme Selection Works

The `ThemeService` (`app/Services/ThemeService.php`) determines which layout to use:

```php
// In controllers, views receive the layout variable:
$customerLayout = ThemeService::getCustomerLayout(); // 'layouts.customer' or 'layouts.customer-premium'
$adminLayout = ThemeService::getAdminLayout();       // 'layouts.admin' or 'layouts.admin-premium'
$publicLayout = ThemeService::getPublicLayout();     // 'layouts.app' or 'layouts.app-premium'
```

Views extend the layout dynamically:
```blade
@extends($customerLayout ?? 'layouts.customer')
```

## Development Rules

### 1. CSS Class Usage for Premium Theme Compatibility

**CRITICAL**: When writing content views, use standard Tailwind classes. The Premium layouts include CSS overrides that automatically convert light theme classes to dark theme.

The following classes are automatically overridden in Premium theme:

| Light Theme Class | Premium Override |
|-------------------|------------------|
| `bg-white` | Dark purple `rgba(30, 27, 75, 0.6)` |
| `bg-gray-50` | `rgba(49, 46, 129, 0.4)` |
| `bg-gray-100` | `rgba(99, 102, 241, 0.2)` |
| `text-gray-900` | Light indigo `#e0e7ff` |
| `text-gray-800` | `#c7d2fe` |
| `text-gray-700` | `#a5b4fc` |
| `text-gray-600` | `#a5b4fc` |
| `text-gray-500` | `rgba(165, 180, 252, 0.7)` |
| `border-gray-*` | `rgba(99, 102, 241, 0.2)` |

### 2. Writing Views

When creating new views:

1. **Always use the dynamic layout variable:**
   ```blade
   @extends($customerLayout ?? 'layouts.customer')
   @extends($adminLayout ?? 'layouts.admin')
   @extends($publicLayout ?? 'layouts.app')
   ```

2. **Use standard Tailwind classes** - Don't use custom dark mode classes, the Premium layout handles conversion automatically:
   ```blade
   <!-- CORRECT -->
   <div class="bg-white rounded-xl shadow-sm p-6">
       <h2 class="text-gray-900 font-bold">Title</h2>
       <p class="text-gray-600">Description</p>
   </div>

   <!-- INCORRECT - Don't add dark: classes -->
   <div class="bg-white dark:bg-gray-800">...</div>
   ```

3. **Cards and containers** - Use these standard patterns:
   ```blade
   <div class="bg-white rounded-xl shadow-sm p-5 sm:p-6">
       <!-- Content -->
   </div>
   ```

4. **Form inputs** - Standard classes work in both themes:
   ```blade
   <input type="text" class="w-full rounded-lg border-gray-300 focus:border-primary-500">
   ```

5. **Tables** - Use standard table classes:
   ```blade
   <table class="min-w-full divide-y divide-gray-200">
       <thead class="bg-gray-50">
           <tr>
               <th class="text-gray-500">Column</th>
           </tr>
       </thead>
   </table>
   ```

### 3. Logo System

Both themes use the same logo system. The logo is fetched from Settings:

```blade
@php
    $siteLogo = \App\Models\Setting::getValue('site_logo');
@endphp

@if($siteLogo)
    <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-10 w-auto">
@else
    <!-- Fallback: gradient box with "X" letter -->
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
        <span class="text-white font-bold text-lg">X</span>
    </div>
@endif
```

### 4. Footer Requirements

All layouts must include a footer with:
- Copyright notice: `© {year} XMAN Studio. สงวนลิขสิทธิ์`
- License: `MIT License`
- Version: Read from `VERSION` file using `trim(file_get_contents(base_path('VERSION')))`

### 5. Admin Header Requirements

Admin layouts must include:
- "กลับหน้าเว็บ" (Back to website) button in the header bar
- Logout button
- User name display

### 6. Color Palette Reference

**Classic Theme (Light):**
- Background: White/Gray
- Text: Gray-900 to Gray-400
- Primary: Primary-600 (customizable)
- Borders: Gray-100 to Gray-300

**Premium Theme (Dark):**
- Background: `#1e1b4b` (dark indigo/purple)
- Text: `#e0e7ff` to `#a5b4fc` (light indigo)
- Accent: Indigo-500 to Purple-600 gradients
- Borders: `rgba(99, 102, 241, 0.2)`

### 7. Animations (Premium Only)

Premium theme includes these animations:
- `animate-blob` - Floating blob animation
- `animate-slide-in` - Slide in from left
- `animate-fade-in` - Fade in with slight upward motion
- `animate-shimmer` - Shimmer effect

### 8. Version File

The application version is stored in `/VERSION` file. Update this file when releasing new versions.

Current format: `X.Y.Z` (e.g., `1.0.120`)

## Common Patterns

### Stats Cards
```blade
<div class="bg-white rounded-xl shadow-sm p-5 sm:p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <div class="flex-shrink-0 p-3 bg-blue-100 rounded-xl">
            <svg class="w-6 h-6 text-blue-600">...</svg>
        </div>
        <div class="text-right">
            <p class="text-sm font-medium text-gray-500">Label</p>
            <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
        </div>
    </div>
</div>
```

### Section Headers
```blade
<div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center">
    <div>
        <h3 class="text-lg font-semibold text-gray-900">Title</h3>
        <p class="text-sm text-gray-500">Description</p>
    </div>
    <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
        Action
    </a>
</div>
```

### Alert Banners
```blade
<div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-4 sm:p-5">
    <div class="flex">
        <div class="flex-shrink-0">
            <div class="p-2 bg-amber-100 rounded-lg">
                <svg class="h-6 w-6 text-amber-600">...</svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h3 class="text-base font-semibold text-amber-800">Alert Title</h3>
            <p class="text-sm text-amber-700">Alert message</p>
        </div>
    </div>
</div>
```

## Build Commands

```bash
# Development
npm run dev

# Production build
npm run build

# Run tests
php artisan test

# Clear caches
php artisan optimize:clear
```

## Key Files

- `/VERSION` - Application version number
- `/app/Services/ThemeService.php` - Theme selection logic
- `/resources/views/layouts/` - All layout templates
- `/app/Models/Setting.php` - Settings model for logo, theme, etc.
