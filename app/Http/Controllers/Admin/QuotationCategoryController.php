<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = QuotationCategory::with('options')->ordered();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_th', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $categories = $query->paginate(20);

        return view('admin.quotations.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.quotations.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'key' => 'required|string|max:255|unique:quotation_categories,key',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Auto-generate key from name if not provided
        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('quotations/categories', $filename, 'public');
            $validated['image'] = $path;
        }

        QuotationCategory::create($validated);

        return redirect()->route('admin.quotations.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(QuotationCategory $category)
    {
        $category->load('options');
        return view('admin.quotations.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuotationCategory $category)
    {
        return view('admin.quotations.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuotationCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'key' => 'required|string|max:255|unique:quotation_categories,key,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image && Storage::disk('public')->exists($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('quotations/categories', $filename, 'public');
            $validated['image'] = $path;
        }

        $category->update($validated);

        return redirect()->route('admin.quotations.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuotationCategory $category)
    {
        // Delete category image if exists
        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        // Delete all option images
        foreach ($category->options as $option) {
            if ($option->image && Storage::disk('public')->exists($option->image)) {
                Storage::disk('public')->delete($option->image);
            }
        }

        $category->delete();

        return redirect()->route('admin.quotations.categories.index')
            ->with('success', 'Category and all its options deleted successfully.');
    }
}
