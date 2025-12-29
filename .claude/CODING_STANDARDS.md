# XMAN Studio - Coding Standards

## PHP Coding Standards (PSR-12 + Laravel)

### File Structure

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    // Constants
    public const ITEMS_PER_PAGE = 12;

    // Properties
    private ProductService $productService;

    // Constructor
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    // Methods (public first, then protected, then private)
    public function index(Request $request): View
    {
        // Method body
    }
}
```

### Method Documentation

```php
/**
 * Display a listing of products with optional filtering.
 *
 * @param  Request  $request
 * @return View
 */
public function index(Request $request): View
{
    $products = Product::query()
        ->when($request->category, fn ($q, $cat) => $q->where('category_id', $cat))
        ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
        ->paginate(self::ITEMS_PER_PAGE);

    return view('products.index', compact('products'));
}
```

### Validation

```php
// Good - use Form Requests for complex validation
public function store(StoreProductRequest $request): RedirectResponse
{
    $product = Product::create($request->validated());

    return redirect()
        ->route('products.show', $product)
        ->with('success', 'Product created successfully');
}

// Acceptable - inline validation for simple cases
public function update(Request $request, Product $product): RedirectResponse
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'price' => ['required', 'numeric', 'min:0'],
        'is_active' => ['boolean'],
    ]);

    $product->update($validated);

    return back()->with('success', 'Product updated');
}
```

### Query Building

```php
// Good - use query builder methods
$products = Product::query()
    ->with(['category', 'images'])
    ->where('is_active', true)
    ->whereHas('category', fn ($q) => $q->where('slug', 'software'))
    ->orderBy('created_at', 'desc')
    ->paginate(12);

// Bad - raw SQL
$products = DB::select('SELECT * FROM products WHERE is_active = 1');

// Acceptable - complex queries only
$products = DB::table('products')
    ->join('categories', 'products.category_id', '=', 'categories.id')
    ->select('products.*', 'categories.name as category_name')
    ->get();
```

### Relationships

```php
// In Product model
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

public function orderItems(): HasMany
{
    return $this->hasMany(OrderItem::class);
}

public function licenseKeys(): HasMany
{
    return $this->hasMany(LicenseKey::class);
}

// Usage
$product = Product::with(['category', 'orderItems'])->find(1);
```

### Error Handling

```php
// Good - use try-catch for expected errors
public function processPayment(Order $order): RedirectResponse
{
    try {
        $payment = $this->paymentService->charge($order);

        $order->update(['payment_status' => 'paid']);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Payment processed successfully');
    } catch (PaymentException $e) {
        Log::error('Payment failed', [
            'order_id' => $order->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->withInput()
            ->with('error', 'Payment failed. Please try again.');
    }
}
```

---

## Database Standards

### Migration Structure

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->foreignId('category_id')
                ->constrained()
                ->onDelete('cascade');

            // Required fields
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');

            // Optional fields
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('image')->nullable();

            // Boolean flags
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_license')->default(false);

            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Optional

            // Indexes
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Naming Conventions

- **Tables**: plural, snake_case (`products`, `order_items`)
- **Columns**: snake_case (`created_at`, `is_active`)
- **Primary Keys**: `id`
- **Foreign Keys**: `{model}_id` (`category_id`, `user_id`)
- **Pivot Tables**: alphabetical order (`order_product`)
- **Timestamps**: `created_at`, `updated_at`, `deleted_at`

---

## Blade Template Standards

### Layout Structure

```blade
{{-- layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'XMAN Studio' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('partials.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ $header ?? 'Dashboard' }}
                </h1>
            </div>
        </header>

        <main>
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>

        @include('partials.footer')
    </div>

    @stack('scripts')
</body>
</html>
```

### Components

```blade
{{-- components/product-card.blade.php --}}
@props(['product'])

<div {{ $attributes->merge(['class' => 'product-card rounded-lg shadow-md']) }}>
    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">

    <div class="p-4">
        <h3 class="text-lg font-semibold">{{ $product->name }}</h3>
        <p class="text-gray-600 text-sm mt-2">{{ Str::limit($product->description, 100) }}</p>

        <div class="mt-4 flex justify-between items-center">
            <span class="text-xl font-bold">฿{{ number_format($product->price, 2) }}</span>

            <a href="{{ route('products.show', $product) }}" class="btn btn-primary">
                View Details
            </a>
        </div>
    </div>
</div>

{{-- Usage --}}
<x-product-card :product="$product" class="hover:shadow-lg" />
```

### Directives

```blade
{{-- Conditionals --}}
@if ($user->isAdmin())
    <a href="{{ route('admin.dashboard') }}">Admin</a>
@elseif ($user->isModerator())
    <a href="{{ route('moderator.dashboard') }}">Moderator</a>
@else
    <a href="{{ route('dashboard') }}">Dashboard</a>
@endif

{{-- Loops --}}
@forelse ($products as $product)
    <x-product-card :product="$product" />
@empty
    <p>No products found.</p>
@endforelse

{{-- Authentication --}}
@auth
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
@endauth

@guest
    <a href="{{ route('login') }}">Login</a>
@endguest

{{-- Authorization --}}
@can('update', $product)
    <a href="{{ route('products.edit', $product) }}">Edit</a>
@endcan

{{-- Include with data --}}
@include('partials.alerts', ['type' => 'success', 'message' => 'Success!'])
```

---

## JavaScript Standards

### ES6+ Modern JavaScript

```javascript
// Use const/let, never var
const API_URL = '/api/products';
let currentPage = 1;

// Arrow functions
const fetchProducts = async (page = 1) => {
    try {
        const response = await fetch(`${API_URL}?page=${page}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching products:', error);
        throw error;
    }
};

// Destructuring
const { name, price, category } = product;
const [first, second, ...rest] = products;

// Template literals
const message = `Product ${name} costs ${price} THB`;

// Spread operator
const updatedProduct = { ...product, price: newPrice };
const allProducts = [...featuredProducts, ...regularProducts];

// Optional chaining
const categoryName = product?.category?.name ?? 'Uncategorized';
```

### DOM Manipulation

```javascript
// Query selectors
const button = document.querySelector('#submit-btn');
const items = document.querySelectorAll('.product-item');

// Event listeners
button.addEventListener('click', (e) => {
    e.preventDefault();
    handleSubmit();
});

// Creating elements
const createProductCard = (product) => {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
        <h3>${product.name}</h3>
        <p>${product.price} THB</p>
    `;
    return card;
};
```

### AJAX with Fetch

```javascript
// GET request
const getProduct = async (id) => {
    const response = await fetch(`/api/products/${id}`, {
        headers: {
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
};

// POST request
const createProduct = async (productData) => {
    const response = await fetch('/api/products', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(productData),
    });

    return await response.json();
};

// Form submission
const form = document.querySelector('#product-form');
form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const result = await createProduct(data);
        window.location.href = `/products/${result.id}`;
    } catch (error) {
        alert('Error creating product');
    }
});
```

---

## CSS/Tailwind Standards

### Tailwind Class Organization

```html
<!-- Order: Layout → Size → Spacing → Typography → Colors → Effects → States -->
<div class="
    flex flex-col
    w-full max-w-7xl
    mx-auto px-4 py-6
    text-lg font-semibold
    bg-white text-gray-900
    rounded-lg shadow-md
    hover:shadow-lg transition-shadow
">
    Content
</div>
```

### Custom CSS (when needed)

```css
/* Use @layer to organize custom CSS */
@layer components {
    .btn {
        @apply px-4 py-2 rounded-lg font-semibold transition-colors;
    }

    .btn-primary {
        @apply bg-blue-600 text-white hover:bg-blue-700;
    }

    .btn-secondary {
        @apply bg-gray-200 text-gray-900 hover:bg-gray-300;
    }
}

@layer utilities {
    .text-balance {
        text-wrap: balance;
    }
}
```

---

## Testing Standards

### Feature Tests

```php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_products(): void
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee($products[0]->name);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Test Product',
            'price' => 99.99,
            'category_id' => Category::factory()->create()->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }
}
```

---

## Security Standards

### Input Validation

```php
// Always validate user input
$request->validate([
    'email' => ['required', 'email', 'max:255'],
    'password' => ['required', 'string', 'min:8', 'confirmed'],
    'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
]);
```

### SQL Injection Prevention

```php
// Good - use query builder/Eloquent
Product::where('id', $request->id)->first();

// Bad - raw SQL with user input
DB::select("SELECT * FROM products WHERE id = {$request->id}");

// Acceptable - parameterized query
DB::select("SELECT * FROM products WHERE id = ?", [$request->id]);
```

### XSS Prevention

```blade
{{-- Good - escaped by default --}}
<h1>{{ $product->name }}</h1>

{{-- Bad - unescaped (only use for trusted content) --}}
<div>{!! $product->description !!}</div>

{{-- Better - sanitize first --}}
<div>{!! Str::markdown($product->description) !!}</div>
```

### CSRF Protection

```blade
{{-- All POST forms must have CSRF token --}}
<form method="POST" action="{{ route('products.store') }}">
    @csrf

    {{-- For PUT/PATCH/DELETE --}}
    @method('PUT')

    <button type="submit">Submit</button>
</form>
```

---

## Performance Best Practices

### N+1 Query Prevention

```php
// Bad - N+1 queries
$products = Product::all();
foreach ($products as $product) {
    echo $product->category->name; // Separate query for each
}

// Good - eager loading
$products = Product::with('category')->get();
foreach ($products as $product) {
    echo $product->category->name; // No additional queries
}
```

### Caching

```php
// Cache expensive operations
$products = Cache::remember('featured_products', 3600, function () {
    return Product::where('is_featured', true)
        ->with('category')
        ->get();
});

// Cache with tags
Cache::tags(['products'])->remember('all_products', 3600, function () {
    return Product::all();
});

// Clear tagged cache
Cache::tags(['products'])->flush();
```

### Pagination

```php
// Always paginate large datasets
$products = Product::paginate(12); // Not ->get()

// Cursor pagination for better performance
$products = Product::cursorPaginate(12);
```

---

**Last Updated:** 2025-12-29
**Version:** 1.0.0
