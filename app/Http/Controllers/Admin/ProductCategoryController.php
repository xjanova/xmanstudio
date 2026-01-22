<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->withCount('products');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $categories = $query->orderBy('order')->orderBy('name')->paginate(20);

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'total_products' => \App\Models\Product::count(),
        ];

        return view('admin.products.categories.index', compact('categories', 'stats'));
    }

    public function create()
    {
        return view('admin.products.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        // Check for duplicate slug
        $count = Category::where('slug', $validated['slug'])->count();
        if ($count > 0) {
            $validated['slug'] = $validated['slug'].'-'.($count + 1);
        }

        Category::create($validated);

        return redirect()->route('admin.products.categories.index')
            ->with('success', 'เพิ่มหมวดหมู่เรียบร้อยแล้ว');
    }

    public function edit(Category $category)
    {
        return view('admin.products.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        // Update slug if name changed
        if ($category->name !== $validated['name']) {
            $newSlug = Str::slug($validated['name']);
            $count = Category::where('slug', $newSlug)->where('id', '!=', $category->id)->count();
            if ($count > 0) {
                $newSlug = $newSlug.'-'.($count + 1);
            }
            $validated['slug'] = $newSlug;
        }

        $category->update($validated);

        return redirect()->route('admin.products.categories.index')
            ->with('success', 'อัปเดตหมวดหมู่เรียบร้อยแล้ว');
    }

    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return redirect()->route('admin.products.categories.index')
                ->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีผลิตภัณฑ์ได้');
        }

        $category->delete();

        return redirect()->route('admin.products.categories.index')
            ->with('success', 'ลบหมวดหมู่เรียบร้อยแล้ว');
    }

    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->route('admin.products.categories.index')
            ->with('success', ($category->is_active ? 'เปิด' : 'ปิด').'ใช้งานหมวดหมู่เรียบร้อยแล้ว');
    }
}
