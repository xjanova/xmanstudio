# CLAUDE.md - XMAN Studio Codebase Guide

## Project Overview

XMAN Studio is a comprehensive business management platform built with **Laravel 11** for selling digital products, managing software licenses, rental packages, and Metal-X YouTube channel content. The site is primarily in **Thai** (th) with English fallback.

## Tech Stack

- **Backend:** PHP 8.2+, Laravel 11
- **Frontend:** Blade templates, Tailwind CSS v4, Alpine.js v3
- **Build:** Vite v7 with `laravel-vite-plugin`
- **Database:** SQLite (dev), MySQL (production)
- **PDF:** barryvdh/laravel-dompdf
- **Linting:** Laravel Pint
- **Testing:** PHPUnit 10

## Key Architecture Decisions

- **Public directory:** `public_html/` (not the default `public/`). Vite config uses `publicDir: 'public_html'`.
- **Static assets** stored in `public/` (for `asset()` helper) and `public_html/` (for web root).
- **Dark mode:** Class-based (`darkMode: 'class'`), toggled via `localStorage.darkMode`.
- **Settings system:** Dynamic key-value settings via `App\Models\Setting` with caching. SEO managed by `App\Models\SeoSetting` (singleton pattern).
- **Fonts:** Noto Sans Thai + Inter (primary), Sarabun TTF in `storage/fonts/` for image generation.

## Directory Structure

```
app/
  Http/Controllers/
    Admin/           # ~20 admin controllers (analytics, products, SEO, etc.)
    Api/             # API endpoints
    Auth/            # Authentication (Breeze-based)
    User/            # User wallet, etc.
    OgImageController.php  # Dynamic OG image generation
  Models/            # ~57 Eloquent models
  Services/          # Business logic
  Mail/              # Email classes
  Jobs/              # Queue jobs

resources/views/
  layouts/
    app.blade.php           # Main public layout
    app-premium.blade.php   # Premium theme variant
    guest.blade.php         # Auth pages (login/register)
    admin.blade.php         # Admin panel
    admin-premium.blade.php # Premium admin
    customer.blade.php      # Customer dashboard
    customer-premium.blade.php
    navigation.blade.php    # Shared navigation
  components/
    seo-meta.blade.php      # OG/Twitter/SEO meta tags component
  admin/                    # Admin panel views
  home.blade.php            # Homepage

routes/
  web.php            # All web routes (~500 lines)
  api.php            # API routes
database/
  migrations/        # ~67 migrations
config/              # Laravel config files
```

## Common Commands

```bash
# Development
composer install
npm install
npm run dev          # Start Vite dev server
npm run build        # Build for production

# Setup (first time)
composer setup       # Full setup: install, env, key, migrate, npm build

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Code quality
./vendor/bin/pint    # Laravel Pint (PHP CS Fixer)
php artisan test     # Run PHPUnit tests

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Layout System

Public-facing pages use `layouts.app` or `layouts.app-premium` (selected by theme setting).
Pages set title/description via Blade sections:

```blade
@extends('layouts.app')
@section('title', 'Page Title')
@section('meta_description', 'Description for SEO')
@section('og_image', 'https://example.com/image.png')  {{-- optional --}}
```

The `<x-seo-meta>` component in each layout automatically generates Open Graph, Twitter Card, canonical URLs, and structured data.

## SEO & Social Media Preview

- **Component:** `resources/views/components/seo-meta.blade.php`
- **Model:** `App\Models\SeoSetting` - manages site-wide SEO settings (title, description, OG image, Twitter handles, Google Analytics, structured data)
- **Admin UI:** `/admin/seo` route for managing all SEO settings
- **OG Image:** Dynamic generation via `OgImageController` using PHP GD (`/og-image/default`), with fallback chain: page-specific image -> database og_image -> dynamic generator
- **Sitemap:** Auto-generated at `/sitemap.xml`
- **Robots.txt:** Dynamic via `/robots.txt` route

## Settings System

Two settings models:
1. `App\Models\Setting` - General key-value settings (site_logo, site_favicon, custom_code_head, etc.) with encryption for sensitive keys
2. `App\Models\SeoSetting` - SEO-specific singleton (og_image, twitter_site, google_analytics_id, etc.)

```php
// Reading settings
Setting::getValue('site_logo');
Setting::getValue('custom_code_head', '');

// SEO settings
$seo = SeoSetting::getInstance();
$seo->site_title;
```

## Coding Conventions

- **Language:** UI text primarily in Thai, code in English
- **Blade components:** Use `<x-component-name>` syntax
- **CSS:** Tailwind utility classes, custom animations defined in `tailwind.config.js`
- **Alpine.js:** Used for interactive UI (dropdowns, modals, dark mode toggle)
- **Route naming:** `admin.*`, `customer.*`, `products.*`, etc.
- **Controllers:** Resource controllers with explicit method routes
- **Models:** Use `$fillable` arrays, casts for booleans/arrays

## Important Notes

- The `public_html/` directory is the web root (not `public/`). Both `vite.config.js` and the server should point here.
- Font files for image generation are in `storage/fonts/` (Sarabun-Bold.ttf, Sarabun-Regular.ttf).
- The setup wizard (`/setup`) runs automatically if no admin user exists.
- Custom tracking code (Google Analytics, Facebook Pixel, etc.) is injected via `Setting::getValue('custom_code_head')` in layouts.
- Theme selection between standard and premium layouts is handled dynamically.

## Pre-flight Checks (MUST follow before writing code)

- **Routes:** Before using `route('name')` in Blade templates, always read `routes/web.php` first to verify the exact route name exists.
- **Models/Controllers:** Before referencing any model, controller, or service, read the actual file to confirm the correct class name, method, and namespace.
- **Blade Components:** Before using `<x-component>`, verify the component exists under `resources/views/components/`.
- **Database columns:** Before accessing `$model->column`, check the migration or model's `$fillable` to confirm the column exists.
- **Do not guess names** — always check the source of truth in the codebase.
