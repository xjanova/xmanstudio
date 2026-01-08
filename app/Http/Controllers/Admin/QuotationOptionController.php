<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = QuotationOption::with('category')->ordered();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_th', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('quotation_category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $options = $query->paginate(20);
        $categories = QuotationCategory::active()->ordered()->get();

        return view('admin.quotations.options.index', compact('options', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = QuotationCategory::active()->ordered()->get();
        $selectedCategoryId = $request->get('category_id');

        return view('admin.quotations.options.create', compact('categories', 'selectedCategoryId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quotation_category_id' => 'required|exists:quotation_categories,id',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'key' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'long_description' => 'nullable|string',
            'long_description_th' => 'nullable|string',
            'features_text' => 'nullable|string',
            'features_th_text' => 'nullable|string',
            'steps_text' => 'nullable|string',
            'steps_th_text' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Auto-generate key from name if needed
        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['name']);
        }

        // Convert text to arrays (one item per line)
        $validated['features'] = $this->textToArray($validated['features_text'] ?? '');
        $validated['features_th'] = $this->textToArray($validated['features_th_text'] ?? '');
        $validated['steps'] = $this->textToArray($validated['steps_text'] ?? '');
        $validated['steps_th'] = $this->textToArray($validated['steps_th_text'] ?? '');

        unset($validated['features_text'], $validated['features_th_text'], $validated['steps_text'], $validated['steps_th_text']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_'.$image->getClientOriginalName();
            $path = $image->storeAs('quotations/options', $filename, 'public');
            $validated['image'] = $path;
        }

        QuotationOption::create($validated);

        return redirect()->route('admin.quotations.options.index')
            ->with('success', 'Option created successfully.');
    }

    /**
     * Convert textarea text to array (one item per line)
     */
    private function textToArray(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn ($item) => ! empty($item)
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(QuotationOption $option)
    {
        $option->load('category');

        return view('admin.quotations.options.show', compact('option'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuotationOption $option)
    {
        $categories = QuotationCategory::active()->ordered()->get();

        return view('admin.quotations.options.edit', compact('option', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuotationOption $option)
    {
        $validated = $request->validate([
            'quotation_category_id' => 'required|exists:quotation_categories,id',
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'key' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'long_description' => 'nullable|string',
            'long_description_th' => 'nullable|string',
            'features_text' => 'nullable|string',
            'features_th_text' => 'nullable|string',
            'steps_text' => 'nullable|string',
            'steps_th_text' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Convert text to arrays (one item per line)
        $validated['features'] = $this->textToArray($validated['features_text'] ?? '');
        $validated['features_th'] = $this->textToArray($validated['features_th_text'] ?? '');
        $validated['steps'] = $this->textToArray($validated['steps_text'] ?? '');
        $validated['steps_th'] = $this->textToArray($validated['steps_th_text'] ?? '');

        unset($validated['features_text'], $validated['features_th_text'], $validated['steps_text'], $validated['steps_th_text']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($option->image && Storage::disk('public')->exists($option->image)) {
                Storage::disk('public')->delete($option->image);
            }

            $image = $request->file('image');
            $filename = time().'_'.$image->getClientOriginalName();
            $path = $image->storeAs('quotations/options', $filename, 'public');
            $validated['image'] = $path;
        }

        $option->update($validated);

        return redirect()->route('admin.quotations.options.index')
            ->with('success', 'Option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuotationOption $option)
    {
        // Delete option image if exists
        if ($option->image && Storage::disk('public')->exists($option->image)) {
            Storage::disk('public')->delete($option->image);
        }

        $option->delete();

        return redirect()->route('admin.quotations.options.index')
            ->with('success', 'Option deleted successfully.');
    }
}
