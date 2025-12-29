# XMAN Studio - Development Guide for Claude AI

This document provides comprehensive guidelines for Claude AI assistants working on the XMAN Studio project.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [Project Structure](#project-structure)
4. [Coding Standards](#coding-standards)
5. [Development Workflow](#development-workflow)
6. [Common Tasks](#common-tasks)
7. [Troubleshooting](#troubleshooting)
8. [Deployment](#deployment)

---

## Project Overview

**XMAN Studio** is a comprehensive IT solutions showcase and e-commerce platform built with Laravel 11.

### Core Features
- **E-commerce System**: Shopping cart, orders, payments
- **License Management**: Key generation, activation tracking
- **Support Tickets**: Priority-based ticket system
- **Line OA Integration**: Automatic notifications
- **Admin Backend**: Complete management dashboard
- **Custom Software Orders**: Category-based ordering with downloadable forms

### Target Users
- IT service customers
- Software license purchasers
- Custom software clients
- Support ticket requesters

---

## Technology Stack

### Backend
- **Framework**: Laravel 11.47.0
- **PHP**: 8.2+ (8.3 recommended)
- **Database**: MySQL 8.0+ / SQLite (development)
- **Symfony Components**: 7.x (for PHP 8.3 compatibility)

### Frontend
- **CSS Framework**: Tailwind CSS v4
- **Build Tool**: Vite 7.x
- **JavaScript**: Vanilla JS / Alpine.js (if needed)
- **Icons**: Heroicons / Font Awesome

### DevOps
- **Version Control**: Git + GitHub
- **CI/CD**: GitHub Actions
- **Deployment**: Shell scripts (deploy.sh)
- **Server**: Nginx/Apache on Ubuntu
- **Domain**: xman4289.com

---

## Project Structure

```
xmanstudio/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── HomeController.php
│   │       ├── ProductController.php
│   │       ├── CartController.php
│   │       ├── OrderController.php
│   │       └── SupportTicketController.php
│   └── Models/
│       ├── Category.php
│       ├── Product.php
│       ├── Order.php
│       ├── OrderItem.php
│       ├── LicenseKey.php
│       ├── SupportTicket.php
│       ├── Cart.php
│       └── CartItem.php
├── database/
│   └── migrations/
│       ├── *_create_categories_table.php
│       ├── *_create_products_table.php
│       ├── *_create_orders_table.php
│       ├── *_create_order_items_table.php
│       ├── *_create_license_keys_table.php
│       ├── *_create_support_tickets_table.php
│       ├── *_create_carts_table.php
│       └── *_create_cart_items_table.php
├── resources/
│   ├── views/
│   │   ├── home.blade.php
│   │   └── layouts/
│   │       └── app.blade.php
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
├── routes/
│   └── web.php
├── .github/
│   └── workflows/
│       ├── ci.yml
│       ├── release.yml
│       └── deploy.yml
├── deploy.sh
├── install.sh
├── VERSION
└── CHANGELOG.md
```

---

## Coding Standards

### PHP Code Style

**Follow Laravel/PSR-12 Standards:**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $products = Product::where('is_active', true)
            ->when($request->category, function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->paginate(12);

        return view('products.index', compact('products'));
    }
}
```

**Key Rules:**
- Use **type hints** for all parameters and return types
- Add **PHPDoc blocks** for all public methods
- Use **named parameters** for clarity when calling methods
- Follow **PSR-12** formatting (use Laravel Pint to auto-format)
- Use **Eloquent ORM** - never raw SQL unless absolutely necessary
- Use **route model binding** instead of manual ID lookups

### Database Conventions

**Migration Naming:**
```php
// Good
2025_12_29_040743_create_categories_table.php
2025_12_29_040744_create_products_table.php

// Bad
create_categories.php
categories_migration.php
```

**Table Design:**
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();                           // Primary key
    $table->foreignId('category_id')        // Foreign key
          ->constrained()
          ->onDelete('cascade');
    $table->string('name');                 // Required string
    $table->string('slug')->unique();       // Unique slug
    $table->text('description');            // Long text
    $table->decimal('price', 10, 2);        // Decimal with precision
    $table->boolean('is_active')            // Boolean with default
          ->default(true);
    $table->timestamps();                   // created_at, updated_at
});
```

**Foreign Key Rules:**
1. Parent tables must be created BEFORE child tables
2. Use `constrained()` for automatic FK setup
3. Specify `onDelete` behavior explicitly
4. Order migrations by timestamp to ensure correct sequence

### Blade Templates

```blade
{{-- Use components where possible --}}
<x-layout>
    <x-slot:title>Products</x-slot>

    {{-- Always escape output with {{ }} --}}
    <h1>{{ $product->name }}</h1>

    {{-- Use @foreach for iteration --}}
    @foreach($products as $product)
        <div class="product-card">
            <h2>{{ $product->name }}</h2>
            <p>{{ $product->description }}</p>
        </div>
    @endforeach

    {{-- Use @auth for authentication checks --}}
    @auth
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    @endauth
</x-layout>
```

### JavaScript/CSS

**Tailwind CSS v4 Conventions:**
```css
/* app.css - Use new @import syntax */
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', system-ui, sans-serif;
}
```

**Important:** Tailwind v4 uses `@tailwindcss/vite` plugin. Do NOT add `tailwindcss` to postcss.config.js.

---

## Development Workflow

### 1. Branch Strategy

```bash
main              # Production branch (protected)
├── develop       # Development branch
├── feature/*     # New features
├── hotfix/*      # Production bug fixes
└── claude/*      # Claude AI development branches
```

### 2. Making Changes

```bash
# 1. Create feature branch
git checkout -b feature/add-payment-gateway

# 2. Make changes
# - Edit code
# - Run tests: php artisan test
# - Check style: ./vendor/bin/pint

# 3. Commit with conventional commits
git add .
git commit -m "feat: add PayPal payment gateway integration"

# 4. Push and create PR
git push origin feature/add-payment-gateway
gh pr create

# 5. Wait for CI to pass
# 6. Get approval and merge
```

### 3. Commit Message Format

Use **Conventional Commits**:

```
type(scope): subject

body

footer
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Build/tooling changes

**Examples:**
```bash
feat(cart): add quantity update functionality
fix(orders): resolve payment calculation error
docs(api): update API documentation for v2
refactor(models): extract license validation logic
```

### 4. Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter ProductTest

# Run with coverage
php artisan test --coverage

# Run in parallel (faster)
php artisan test --parallel
```

---

## Common Tasks

### Adding a New Model

```bash
# 1. Create model with migration
php artisan make:model Review -m

# 2. Define migration
# database/migrations/*_create_reviews_table.php
public function up()
{
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->integer('rating');
        $table->text('comment')->nullable();
        $table->timestamps();
    });
}

# 3. Define model relationships
// app/Models/Review.php
public function product()
{
    return $this->belongsTo(Product::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

# 4. Add inverse relationships
// app/Models/Product.php
public function reviews()
{
    return $this->hasMany(Review::class);
}

# 5. Run migration
php artisan migrate
```

### Adding a New Controller

```bash
# 1. Create controller
php artisan make:controller ReviewController --resource

# 2. Define methods
// app/Http/Controllers/ReviewController.php
public function store(Request $request, Product $product)
{
    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:1000',
    ]);

    $product->reviews()->create([
        ...$validated,
        'user_id' => auth()->id(),
    ]);

    return redirect()->back()->with('success', 'Review submitted!');
}

# 3. Add route
// routes/web.php
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
    ->name('reviews.store')
    ->middleware('auth');
```

### Adding a New View

```bash
# 1. Create view file
touch resources/views/products/show.blade.php

# 2. Use layout
@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold">{{ $product->name }}</h1>
        <p class="text-gray-600">{{ $product->description }}</p>
    </div>
@endsection

# 3. Return from controller
public function show(Product $product)
{
    return view('products.show', compact('product'));
}
```

---

## Troubleshooting

### Common Errors and Solutions

#### 1. Route Method Not Allowed

**Error:**
```
MethodNotAllowedHttpException
The GET method is not supported for route /. Supported methods: HEAD.
```

**Cause:** Route cache is corrupted or outdated

**Solution:**
```bash
php artisan route:clear
php artisan route:cache  # Only on production
php artisan config:clear

# If persists, clear all caches
php artisan optimize:clear
```

#### 2. Migration Foreign Key Error

**Error:**
```
SQLSTATE[HY000]: General error: 1005 Can't create table
(errno: 150 "Foreign key constraint is incorrectly formed")
```

**Cause:** Migration order is wrong - child table runs before parent

**Solution:**
```bash
# Rename migrations to correct order
# Parent tables must have EARLIER timestamps than child tables

# Example:
2025_12_29_040743_create_categories_table.php   # First
2025_12_29_040744_create_products_table.php     # Second (references categories)
2025_12_29_040745_create_orders_table.php       # Third
2025_12_29_040747_create_order_items_table.php  # Fourth (references orders + products)
```

#### 3. Tailwind CSS Build Error

**Error:**
```
[postcss] It looks like you're trying to use `tailwindcss` directly as a PostCSS plugin
```

**Cause:** Using old Tailwind v3 PostCSS configuration with Tailwind v4

**Solution:**
```js
// postcss.config.js - Remove tailwindcss plugin
export default {
  plugins: {
    autoprefixer: {},  // Only this
  },
}

// Tailwind v4 uses @tailwindcss/vite plugin in vite.config.js instead
```

#### 4. Vite Not Found

**Error:**
```
sh: 1: vite: not found
```

**Cause:** NPM dependencies not installed

**Solution:**
```bash
npm install
npm run build
```

#### 5. Migration "Table Already Exists"

**Error:**
```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'products' already exists
```

**Cause:** Partial migration from previous failed run

**Solution:**
```bash
# Fresh install (WARNING: Deletes all data)
php artisan migrate:fresh --force

# Or manually drop tables
mysql -u root -p
DROP TABLE IF EXISTS order_items, orders, products, categories;
EXIT;
php artisan migrate
```

---

## Deployment

### Manual Deployment

```bash
# On server
cd /home/admin/domains/xman4289.com/public_html

git pull origin claude/main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

### Automated Deployment

```bash
# Using deploy script
./deploy.sh claude/main

# Or via GitHub Actions
# Push a version tag:
git tag v1.0.1
git push origin v1.0.1

# Deploy workflow runs automatically
```

### Deployment Checklist

Before deploying:
- [ ] Run tests: `php artisan test`
- [ ] Check code style: `./vendor/bin/pint --test`
- [ ] Build assets locally: `npm run build`
- [ ] Review migrations: ensure correct order
- [ ] Update VERSION and CHANGELOG.md
- [ ] Commit all changes
- [ ] Create git tag
- [ ] Push to repository

After deploying:
- [ ] Check application health
- [ ] Verify database migrations ran
- [ ] Test critical user flows
- [ ] Check error logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor performance

---

## Best Practices for Claude AI

### When Helping with Development

1. **Always read existing code first** before making changes
2. **Follow existing patterns** in the codebase
3. **Test changes locally** if possible
4. **Use Laravel conventions** - don't reinvent the wheel
5. **Document why**, not just what, in code comments
6. **Consider backwards compatibility** for database changes
7. **Think about performance** - avoid N+1 queries
8. **Security first** - validate input, escape output, use prepared statements

### Code Review Checklist

Before committing code, verify:
- [ ] Follows PSR-12 coding standards
- [ ] Uses type hints and return types
- [ ] Has proper validation for user input
- [ ] Uses Eloquent instead of raw SQL
- [ ] Follows existing naming conventions
- [ ] Has no security vulnerabilities
- [ ] Works with PHP 8.3+ and Laravel 11
- [ ] Doesn't break existing tests
- [ ] Includes appropriate error handling

### When Unsure

- Read Laravel documentation: https://laravel.com/docs/11.x
- Check existing similar code in the project
- Ask the user for clarification
- Suggest multiple approaches with pros/cons
- Test thoroughly before recommending

---

## Quick Reference

### Laravel Artisan Commands

```bash
# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear  # Clear all caches

# Database
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback
php artisan db:seed

# Code Generation
php artisan make:model Product -m
php artisan make:controller ProductController --resource
php artisan make:migration create_products_table
php artisan make:seeder ProductSeeder

# Testing
php artisan test
php artisan test --filter ProductTest
php artisan test --coverage

# Optimization
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Git Commands

```bash
# Branch management
git checkout -b feature/new-feature
git checkout main
git pull origin main
git merge feature/new-feature

# Committing
git add .
git commit -m "feat: add new feature"
git push origin feature/new-feature

# Tagging
git tag v1.0.0
git push origin v1.0.0
```

---

**Last Updated:** 2025-12-29
**Project Version:** 1.0.0
**Laravel Version:** 11.47.0
**PHP Version:** 8.3.28
