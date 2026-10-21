<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use App\Services\ImageService;
use App\Support\Quotation\Outcomes;
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

        return view('admin.quotations.options.create', [
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'allOptions' => $this->optionsForPicker(),
            'outcomes' => Outcomes::all(),
        ]);
    }

    /**
     * Tidy the selling rules before they are stored.
     *
     * Three things the form cannot guarantee on its own: unchecked boxes post
     * nothing at all (so the flags have to be read back explicitly, or editing
     * an option could never turn one OFF), an empty multi-select should be
     * null rather than an empty array, and an option must never require
     * itself — that would be a rule the builder can never satisfy.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normaliseSellingRules(array $validated, Request $request): array
    {
        $validated['is_core'] = $request->boolean('is_core');
        $validated['is_active'] = $request->boolean('is_active');

        $selfKey = $validated['key'] ?? null;
        $requires = array_values(array_filter(
            array_unique($validated['requires'] ?? []),
            fn ($k) => $k !== null && $k !== '' && $k !== $selfKey
        ));
        $suggested = array_values(array_filter(
            array_unique($validated['suggested_for'] ?? []),
            fn ($k) => $k !== null && $k !== ''
        ));

        $validated['requires'] = $requires ?: null;
        $validated['suggested_for'] = $suggested ?: null;
        $validated['duration_days'] = (int) ($validated['duration_days'] ?? 0);

        return $validated;
    }

    /**
     * Every other option, grouped by category, for the "requires" picker.
     *
     * @return array<string, array<int, array{key: string, label: string}>>
     */
    protected function optionsForPicker(?int $exceptId = null): array
    {
        $rows = QuotationOption::with('category')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->orderBy('quotation_category_id')
            ->orderBy('order')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $group = $row->category?->name_th ?: ($row->category?->name ?: 'อื่น ๆ');
            $grouped[$group][] = [
                'key' => $row->key,
                'label' => ($row->name_th ?: $row->name) . ' (' . $row->key . ')',
            ];
        }

        return $grouped;
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
            'icon' => 'nullable|string|max:50',
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

            // ตรรกะการเสนอขาย — หน้า /quote อ่านจากตรงนี้ ไม่ได้ฝังไว้ในโค้ด
            'is_core' => 'boolean',
            'requires' => 'nullable|array',
            // nullable, not bare string: ConvertEmptyStringsToNull turns an
            // empty entry into null before validation, so a stray blank in the
            // posted array would 422 the whole save. normaliseSellingRules
            // drops them straight after.
            'requires.*' => 'nullable|string|max:255',
            'suggested_for' => 'nullable|array',
            'suggested_for.*' => 'nullable|string|in:' . implode(',', Outcomes::keys()),
            'reason_th' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'duration_days' => 'nullable|integer|min:0|max:3650',
        ]);

        $validated = $this->normaliseSellingRules($validated, $request);

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
            $validated['image'] = app(ImageService::class)->storeAsWebp(
                $request->file('image'), 'quotations/options', maxWidth: 800,
            );
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

        return view('admin.quotations.options.edit', [
            'option' => $option,
            'categories' => $categories,
            'allOptions' => $this->optionsForPicker($option->id),
            'outcomes' => Outcomes::all(),
        ]);
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
            'icon' => 'nullable|string|max:50',
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

            // ตรรกะการเสนอขาย — หน้า /quote อ่านจากตรงนี้ ไม่ได้ฝังไว้ในโค้ด
            'is_core' => 'boolean',
            'requires' => 'nullable|array',
            // nullable, not bare string: ConvertEmptyStringsToNull turns an
            // empty entry into null before validation, so a stray blank in the
            // posted array would 422 the whole save. normaliseSellingRules
            // drops them straight after.
            'requires.*' => 'nullable|string|max:255',
            'suggested_for' => 'nullable|array',
            'suggested_for.*' => 'nullable|string|in:' . implode(',', Outcomes::keys()),
            'reason_th' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'duration_days' => 'nullable|integer|min:0|max:3650',
        ]);

        $validated = $this->normaliseSellingRules($validated, $request);

        // Convert text to arrays (one item per line)
        $validated['features'] = $this->textToArray($validated['features_text'] ?? '');
        $validated['features_th'] = $this->textToArray($validated['features_th_text'] ?? '');
        $validated['steps'] = $this->textToArray($validated['steps_text'] ?? '');
        $validated['steps_th'] = $this->textToArray($validated['steps_th_text'] ?? '');

        unset($validated['features_text'], $validated['features_th_text'], $validated['steps_text'], $validated['steps_th_text']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = app(ImageService::class)->replaceWithWebp(
                $request->file('image'), $option->image, 'quotations/options', maxWidth: 800,
            );
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
