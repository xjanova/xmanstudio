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

**Remember:** These rules ensure consistent, readable code that passes CI/CD checks automatically!
