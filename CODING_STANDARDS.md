# Laravel Code Style Guidelines for xmanstudio

This document outlines the coding standards to ensure all code passes Laravel Pint validation.

## 1. Line Length

**Rule:** Maximum 120 characters per line

### ✅ Good Examples:

```php
// Break long method chains
$table->json('crop_data')->nullable()->after('image')
    ->comment('Image crop position data');

// Break long validation rules
$request->validate([
    'email' => [
        'required',
        'email',
        'max:255',
        'unique:users,email',
    ],
]);

// Break long strings with implode()
$message = implode("\n", [
    'First line of message',
    'Second line of message',
    'Third line of message',
]);
```

### ❌ Bad Examples:

```php
// Too long - will fail Laravel Pint
$table->json('crop_data')->nullable()->after('image')->comment('Image crop position data');

// Too long validation rule
$request->validate(['email' => 'required|email|max:255|unique:users,email|confirmed']);
```

## 2. Negation Operator Spacing

**Rule:** Always add space after negation operator `!`

### ✅ Good:

```php
if (! $user) {
    return false;
}

if (! empty($data)) {
    process($data);
}

$enabled = ! $setting->disabled;
```

### ❌ Bad:

```php
if (!$user) {
    return false;
}

if (!empty($data)) {
    process($data);
}

$enabled = !$setting->disabled;
```

## 3. Type Declarations

**Rule:** Always declare types for class properties (PHP 7.4+)

### ✅ Good:

```php
class MyComponent extends Component
{
    public string $name;

    public ?User $user;

    public array $items;

    public int $count = 0;
}
```

### ❌ Bad:

```php
class MyComponent extends Component
{
    public $name;  // Missing type

    public $user;  // Missing type

    public $items;  // Missing type
}
```

## 4. Import Statements

**Rule:** Always import classes that you use

### ✅ Good:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return DB::table('users')->get();
    }
}
```

### ❌ Bad:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
// Missing: use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return DB::table('users')->get();  // Using unimported class
    }
}
```

## 5. Method Chaining

**Rule:** Break long method chains across multiple lines

### ✅ Good:

```php
$users = User::where('active', true)
    ->where('verified', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

$query = Banner::where('enabled', true)
    ->where(function ($q) {
        $q->whereNull('start_date')
            ->orWhere('start_date', '<=', now());
    })
    ->orderBy('priority', 'desc')
    ->first();
```

### ❌ Bad:

```php
$users = User::where('active', true)->where('verified', true)->orderBy('created_at', 'desc')->limit(10)->get();
```

## 6. Array Formatting

**Rule:** Multi-line arrays should have trailing comma

### ✅ Good:

```php
$config = [
    'name' => 'value',
    'email' => 'test@example.com',
    'active' => true,  // Trailing comma
];

$fillable = [
    'title',
    'description',
    'enabled',  // Trailing comma
];
```

### ❌ Bad:

```php
$config = [
    'name' => 'value',
    'email' => 'test@example.com',
    'active' => true  // Missing trailing comma
];
```

## 7. Comments

**Rule:** Keep comments under 120 characters or break them

### ✅ Good:

```php
/**
 * Process the banner image and save crop data.
 * This method handles both new uploads and updates.
 */
public function processBanner(Request $request)
{
    // Process image
}

// This is a single-line comment that should be
// broken if it exceeds 120 characters in length
```

### ❌ Bad:

```php
// This is a very long single-line comment that exceeds the 120 character limit and will fail Laravel Pint validation
```

## 8. Validation Rules

**Rule:** Break long validation rules into array format

### ✅ Good:

```php
$request->validate([
    'email' => [
        'required',
        'email',
        'max:255',
        'unique:users,email',
    ],
    'password' => [
        'required',
        'min:8',
        'confirmed',
    ],
]);
```

### ❌ Bad:

```php
$request->validate([
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|min:8|confirmed',
]);
```

## 9. Long Strings

**Rule:** Use `implode()` for multi-line strings

### ✅ Good:

```php
$message = implode("\n", [
    'Hello,',
    'This is a multi-line message',
    'that is easier to read.',
]);

$sql = implode(' ', [
    'SELECT * FROM users',
    'WHERE active = 1',
    'AND verified = 1',
    'ORDER BY created_at DESC',
]);
```

### ❌ Bad:

```php
$message = "Hello,\nThis is a multi-line message that is easier to read.";

$sql = "SELECT * FROM users WHERE active = 1 AND verified = 1 ORDER BY created_at DESC";
```

## 10. Blade Templates

**Rule:** Keep PHP logic clean in Blade files

### ✅ Good:

```blade
@php
    $displayWidth = $banner->display_width ?? 1200;
    $displayHeight = $banner->display_height ?? 630;
    $cropData = $banner->crop_data;
@endphp

<div style="width: {{ $displayWidth }}px; height: {{ $displayHeight }}px;">
    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}">
</div>
```

### ❌ Bad:

```blade
<div style="width: {{ $banner->display_width ?? 1200 }}px; height: {{ $banner->display_height ?? 630 }}px;">
    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}">
</div>
```

## Quick Checklist Before Commit

- [ ] All lines under 120 characters
- [ ] Space after `!` operator
- [ ] Type declarations for all properties
- [ ] All classes imported
- [ ] Method chains properly formatted
- [ ] Arrays have trailing commas
- [ ] Validation rules in array format
- [ ] Long strings use `implode()`
- [ ] Comments are properly formatted
- [ ] No unused imports

## Running Laravel Pint Locally

Always run Laravel Pint before committing:

```bash
# Check for style issues
./vendor/bin/pint --test

# Auto-fix style issues
./vendor/bin/pint
```

## Common Fixes

### Fix 1: Line too long
**Problem:** `Line exceeds 120 characters`
**Solution:** Break into multiple lines using proper indentation

### Fix 2: Missing space after !
**Problem:** `Negation operator without space`
**Solution:** Change `!$var` to `! $var`

### Fix 3: Missing type declaration
**Problem:** `Property type missing`
**Solution:** Add type: `public string $name;`

### Fix 4: Missing import
**Problem:** `Class used without import`
**Solution:** Add `use` statement at top of file

---

## Real-world Issues from This Project

These are actual problems that occurred in xmanstudio project and how they were fixed.

### Issue 1: Missing DB Facade Import (2026-01-21)

**File:** `database/migrations/2026_01_21_140000_create_ad_placements_table.php`

**Problem:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// Missing: use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            // ... table definition
        });

        // ❌ Using DB without importing it
        DB::table('ad_placements')->insert([...]);
    }
}
```

**Laravel Pint Error:** `Class 'DB' not found`

**Solution:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;  // ✅ Added
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            // ... table definition
        });

        DB::table('ad_placements')->insert([...]);  // ✅ Now works
    }
}
```

**Lesson:** Always import facades before using them, even in migrations.

---

### Issue 2: Missing Type Declarations in Components (2026-01-21)

**File:** `app/View/Components/GoogleAd.php`

**Problem:**
```php
class GoogleAd extends Component
{
    public $position;      // ❌ No type
    public $page;          // ❌ No type
    public $ad;            // ❌ No type

    public function __construct(string $position, string $page = 'all')
    {
        $this->position = $position;
        $this->page = $page;
        $this->ad = AdPlacement::getForPosition($position, $page);
    }
}
```

**Laravel Pint Error:** `Property type declaration is missing`

**Solution:**
```php
class GoogleAd extends Component
{
    public string $position;           // ✅ Added type
    public string $page;               // ✅ Added type
    public ?AdPlacement $ad;           // ✅ Added nullable type

    public function __construct(string $position, string $page = 'all')
    {
        $this->position = $position;
        $this->page = $page;
        $this->ad = AdPlacement::getForPosition($position, $page);
    }
}
```

**Lesson:** All public/protected properties must have type declarations. Use `?Type` for nullable values.

---

### Issue 3: Negation Operator Missing Space (2026-01-21)

**File:** Multiple files (`AdsTxtSetting.php`, `routes/web.php`, etc.)

**Problem:**
```php
if (!$setting) {                    // ❌ No space after !
    return false;
}

$enabled = !$service->is_active;    // ❌ No space after !

if (!empty($data)) {                // ❌ No space after !
    process($data);
}
```

**Laravel Pint Error:** `Space required after negation operator`

**Solution:**
```php
if (! $setting) {                   // ✅ Space added
    return false;
}

$enabled = ! $service->is_active;   // ✅ Space added

if (! empty($data)) {               // ✅ Space added
    process($data);
}
```

**Lesson:** Laravel coding style requires space after `!` operator. This is different from other PHP projects.

---

### Issue 4: Lines Exceeding 120 Characters (2026-01-21)

**File:** `database/migrations/2026_01_21_160000_add_crop_data_to_banners_table.php`

**Problem:**
```php
public function up(): void
{
    Schema::table('banners', function (Blueprint $table) {
        // ❌ Line too long (exceeds 120 chars)
        $table->json('crop_data')->nullable()->after('image')->comment('Image crop position data');
        $table->integer('display_width')->nullable()->after('crop_data')->comment('Banner display width in pixels');
        $table->integer('display_height')->nullable()->after('display_width')->comment('Banner display height in pixels');
    });
}
```

**Laravel Pint Error:** `Line exceeds 120 characters`

**Solution:**
```php
public function up(): void
{
    Schema::table('banners', function (Blueprint $table) {
        // ✅ Lines broken properly
        $table->json('crop_data')->nullable()->after('image')
            ->comment('Image crop position data');
        $table->integer('display_width')->nullable()->after('crop_data')
            ->comment('Banner display width in pixels');
        $table->integer('display_height')->nullable()->after('display_width')
            ->comment('Banner display height in pixels');
    });
}
```

**Lesson:** Method chaining should be broken when it exceeds 120 characters. Indent continuation lines with one level.

---

### Issue 5: Long Strings in Migrations (2026-01-21)

**File:** `database/migrations/2026_01_21_130000_create_seo_settings_table.php`

**Problem:**
```php
$table->text('robots_txt_content')->default("User-agent: *\nDisallow: /admin\nDisallow: /api\nAllow: /api/docs");
```

**Laravel Pint Warning:** Long line, hard to read

**Solution:**
```php
$table->text('robots_txt_content')->default(
    implode("\n", [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /api',
        'Allow: /api/docs',
    ])
);
```

**Lesson:** Use `implode()` for multi-line strings in PHP. It's more readable and easier to maintain.

---

## Project-Specific Patterns to Follow

Based on issues encountered in this project, always:

1. **Import all facades explicitly** - Don't rely on auto-loading
2. **Type everything** - Properties, parameters, return types
3. **Use `! $var`** - Not `!$var` (Laravel style)
4. **Break long lines** - At method chains, arrays, validation rules
5. **Use implode()** - For multi-line strings
6. **Run Pint before commit** - Catch issues early

## CI/CD Pipeline

Every push triggers:
1. Laravel Pint (code style check)
2. PHPUnit (tests)
3. Build validation

If Laravel Pint fails, the entire pipeline fails. **Always run locally first:**

```bash
./vendor/bin/pint --test  # Check
./vendor/bin/pint         # Fix
```

---

## Quick Reference Cheat Sheet

Print this and keep it visible while coding:

```
✓ Line length: Max 120 chars
✓ Negation: ! $var (with space)
✓ Type everything: public string $name
✓ Import facades: use Illuminate\Support\Facades\DB
✓ Break chains: ->method()
                    ->method()
✓ Array comma: [..., 'last',]
✓ Use implode(): implode("\n", [...])
✓ Run Pint: ./vendor/bin/pint before commit
```

---

## Pre-commit Hook Setup

Automatically run Laravel Pint before every commit:

### Setup Instructions:

```bash
# 1. Create pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash

echo "Running Laravel Pint..."

# Run Pint on staged PHP files
./vendor/bin/pint --test

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Laravel Pint found style issues!"
    echo "Run './vendor/bin/pint' to fix them automatically"
    echo "Or fix manually and commit again"
    exit 1
fi

echo "✅ Code style check passed!"
exit 0
EOF

# 2. Make it executable
chmod +x .git/hooks/pre-commit

# 3. Test it
git commit -m "test"
```

### Benefits:
- Catches style issues before they reach CI/CD
- Prevents failed pipeline builds
- Enforces consistency automatically
- Team members can't forget to run Pint

---

## IDE Configuration

### PHPStorm / IntelliJ IDEA

#### 1. Install Laravel Pint Plugin
- Go to: `Settings` → `Plugins`
- Search: "Laravel Pint"
- Install and restart

#### 2. Enable Pint on Save
- Go to: `Settings` → `Tools` → `Laravel Pint`
- Check: "Run Pint on Save"
- Path: `./vendor/bin/pint`

#### 3. Code Style Settings
- Go to: `Settings` → `Editor` → `Code Style` → `PHP`
- Set tab size: 4 spaces
- Line length: 120
- Import: Laravel style from `pint.json` if available

### Visual Studio Code

#### 1. Install Extensions
```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "onecentlin.laravel-blade",
    "amirrizal.laravel-extra-intellisense"
  ]
}
```

#### 2. Settings (`.vscode/settings.json`)
```json
{
  "editor.rulers": [120],
  "editor.tabSize": 4,
  "editor.formatOnSave": true,
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  },
  "php.validate.executablePath": "/usr/bin/php",
  "php.suggest.basic": false
}
```

#### 3. Tasks for Pint (`.vscode/tasks.json`)
```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "Laravel Pint",
      "type": "shell",
      "command": "./vendor/bin/pint",
      "problemMatcher": [],
      "group": {
        "kind": "build",
        "isDefault": true
      }
    },
    {
      "label": "Laravel Pint (Check)",
      "type": "shell",
      "command": "./vendor/bin/pint --test",
      "problemMatcher": []
    }
  ]
}
```

Run with: `Cmd/Ctrl + Shift + B`

---

## Git Commit Message Standards

Follow **Conventional Commits** format:

### Format:
```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `style:` Code style (formatting, missing semicolons)
- `refactor:` Code change that neither fixes nor adds feature
- `perf:` Performance improvement
- `test:` Adding tests
- `chore:` Maintenance (dependencies, config)

### Examples:

```bash
# Good commits
git commit -m "feat: add banner image cropper with drag support"
git commit -m "fix: add missing DB facade import in migrations"
git commit -m "docs: add coding standards documentation"
git commit -m "refactor: extract banner display logic to component"

# With scope
git commit -m "feat(banners): add Facebook-style image repositioner"
git commit -m "fix(migrations): break long lines for Laravel Pint"

# Multi-line
git commit -m "feat: add comprehensive banner management system

- Upload banner images with cropping
- Schedule start/end dates
- Track views and clicks
- Multiple position support
- Admin CRUD interface"
```

### Bad Examples:
```bash
# ❌ Too vague
git commit -m "update"
git commit -m "fix bug"
git commit -m "changes"

# ❌ No type prefix
git commit -m "added new feature"
git commit -m "fixed the issue"
```

---

## Database & Migration Standards

### Table Naming:
- **Plural, snake_case**: `users`, `ad_placements`, `user_rentals`
- **Pivot tables**: Alphabetical order: `post_tag` not `tag_post`

### Column Naming:
- **snake_case**: `created_at`, `user_id`, `display_width`
- **Boolean prefix**: `is_active`, `has_permission`, `can_edit`
- **Foreign keys**: `{table}_id` → `user_id`, `banner_id`

### Migration Best Practices:

```php
// ✅ Good
Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->boolean('enabled')->default(false);
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index('enabled');
    $table->index('slug');
});

// ❌ Bad
Schema::create('Banner', function (Blueprint $table) {  // Wrong case
    $table->id();
    $table->string('Title');  // Wrong case
    $table->boolean('isEnabled');  // Wrong naming
    // Missing indexes
});
```

### Column Order Convention:
1. `id`
2. Foreign keys (`user_id`, `category_id`)
3. Core data columns
4. Status/boolean columns
5. JSON columns
6. Text/longText columns
7. Timestamps (`created_at`, `updated_at`)
8. Soft deletes (`deleted_at`)

---

## Security Best Practices

### 1. Always Validate Input

```php
// ✅ Good
$request->validate([
    'email' => ['required', 'email', 'max:255'],
    'url' => ['nullable', 'url', 'max:500'],
    'amount' => ['required', 'numeric', 'min:0'],
]);

// ❌ Bad
$email = $request->input('email');  // No validation
```

### 2. Use Mass Assignment Protection

```php
// ✅ Good
class User extends Model
{
    protected $fillable = ['name', 'email'];
    // or
    protected $guarded = ['id', 'role'];
}

// ❌ Bad
class User extends Model
{
    protected $guarded = [];  // Everything is fillable!
}
```

### 3. Prevent SQL Injection

```php
// ✅ Good - Use Query Builder/Eloquent
User::where('email', $email)->first();
DB::table('users')->where('id', $id)->get();

// ❌ Bad - Raw SQL without bindings
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### 4. Prevent XSS

```blade
{{-- ✅ Good - Auto-escaped --}}
<p>{{ $user->name }}</p>

{{-- ❌ Bad - Unescaped HTML --}}
<p>{!! $user->name !!}</p>
```

### 5. Use CSRF Protection

```blade
{{-- ✅ Always include in forms --}}
<form method="POST">
    @csrf
    ...
</form>
```

### 6. Protect Routes

```php
// ✅ Good
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// ❌ Bad
Route::get('/admin/users', [UserController::class, 'index']);  // No auth!
```

---

## Laravel Performance Best Practices

### 1. Avoid N+1 Queries

```php
// ❌ Bad - N+1 Problem
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->bio;  // Query for each user
}

// ✅ Good - Eager Loading
$users = User::with('profile')->get();
foreach ($users as $user) {
    echo $user->profile->bio;  // No extra queries
}
```

### 2. Use Chunking for Large Datasets

```php
// ❌ Bad - Loads everything in memory
User::all()->each(function ($user) {
    // Process
});

// ✅ Good - Process in chunks
User::chunk(200, function ($users) {
    foreach ($users as $user) {
        // Process
    }
});
```

### 3. Select Only Required Columns

```php
// ❌ Bad
$users = User::all();  // Selects all columns

// ✅ Good
$users = User::select('id', 'name', 'email')->get();
```

### 4. Use Caching

```php
// ✅ Good
$settings = Cache::remember('settings', 3600, function () {
    return Setting::all();
});

// ❌ Bad
$settings = Setting::all();  // Query every time
```

---

## Code Review Checklist

Before submitting PR, check:

- [ ] Run `./vendor/bin/pint` - no style violations
- [ ] All tests pass
- [ ] No `dd()`, `dump()`, `var_dump()` left in code
- [ ] No commented-out code blocks
- [ ] All facades imported
- [ ] Type declarations on properties
- [ ] Validation on all user inputs
- [ ] SQL injection prevention (use Query Builder)
- [ ] XSS prevention (use `{{ }}` not `{!! !!}`)
- [ ] CSRF tokens on forms
- [ ] N+1 queries prevented (eager loading)
- [ ] Proper error handling
- [ ] Meaningful variable names
- [ ] Clear commit messages
- [ ] Documentation updated if needed

---

**Remember:** These rules ensure consistent, readable code that passes CI/CD checks automatically!
