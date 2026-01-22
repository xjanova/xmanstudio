<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        $products = $query->paginate(20);
        $categories = Category::all();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'with_license' => Product::where('requires_license', true)->count(),
        ];

        return view('admin.products.index', compact('products', 'categories', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = Category::all();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'image' => 'nullable|image|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:2048',
            'is_custom' => 'boolean',
            'requires_license' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_custom'] = $request->boolean('is_custom');
        $validated['requires_license'] = $request->boolean('requires_license');
        $validated['is_active'] = $request->boolean('is_active', true);

        // Handle main image
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Handle gallery images
        $galleryPaths = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $galleryPaths[] = $image->store('products/gallery', 'public');
            }
        }
        $validated['images'] = $galleryPaths;

        Product::create($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'เพิ่มผลิตภัณฑ์สำเร็จ');
    }

    /**
     * Show edit form
     */
    public function edit(Product $product)
    {
        $categories = Category::all();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:100|unique:products,sku,'.$product->id,
            'image' => 'nullable|image|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
            'is_custom' => 'boolean',
            'requires_license' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_custom'] = $request->boolean('is_custom');
        $validated['requires_license'] = $request->boolean('requires_license');
        $validated['is_active'] = $request->boolean('is_active');

        // Handle main image
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && \Storage::disk('public')->exists($product->image)) {
                \Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Handle gallery images
        $existingImages = $product->images ?? [];

        // Remove images marked for deletion
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $imageToRemove) {
                if (\Storage::disk('public')->exists($imageToRemove)) {
                    \Storage::disk('public')->delete($imageToRemove);
                }
                $existingImages = array_filter($existingImages, fn ($img) => $img !== $imageToRemove);
            }
        }

        // Add new gallery images
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $existingImages[] = $image->store('products/gallery', 'public');
            }
        }

        $validated['images'] = array_values($existingImages);

        $product->update($validated);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'อัพเดทผลิตภัณฑ์สำเร็จ');
    }

    /**
     * Toggle product active status
     */
    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status} '{$product->name}' แล้ว");
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', "ลบ '{$name}' แล้ว");
    }

    /**
     * Preview product page
     */
    public function preview(Product $product)
    {
        // Get related products
        $relatedProducts = Product::where('id', '!=', $product->id)
            ->where('is_active', true)
            ->when($product->category_id, function ($query) use ($product) {
                $query->where('category_id', $product->category_id);
            })
            ->limit(4)
            ->get();

        return view('admin.products.preview', compact('product', 'relatedProducts'));
    }
}
