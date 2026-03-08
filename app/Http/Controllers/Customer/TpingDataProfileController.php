<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDataProfile;
use App\Models\ProductWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TpingDataProfileController extends Controller
{
    private function getProduct(): Product
    {
        return Product::where('slug', 'tping')->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $product = $this->getProduct();

        $query = ProductDataProfile::byUser($user->id)
            ->where('product_id', $product->id)
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $profiles = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => ProductDataProfile::byUser($user->id)->where('product_id', $product->id)->count(),
            'categories' => ProductDataProfile::byUser($user->id)->where('product_id', $product->id)
                ->where('category', '!=', '')->whereNotNull('category')
                ->distinct()->pluck('category')->count(),
            'workflows' => ProductWorkflow::byUser($user->id)->where('product_id', $product->id)->count(),
        ];

        $categories = ProductDataProfile::byUser($user->id)
            ->where('product_id', $product->id)
            ->where('category', '!=', '')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();

        return view('customer.tping.data-profiles.index', compact('profiles', 'stats', 'categories'));
    }

    public function show(ProductDataProfile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        $fields = json_decode($profile->fields_json, true) ?? [];

        return view('customer.tping.data-profiles.show', compact('profile', 'fields'));
    }

    public function edit(ProductDataProfile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        $fields = json_decode($profile->fields_json, true) ?? [];

        return view('customer.tping.data-profiles.edit', compact('profile', 'fields'));
    }

    public function update(Request $request, ProductDataProfile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        // Rebuild fields_json from dynamic form inputs
        $fields = [];
        if ($request->has('field_keys') && $request->has('field_values')) {
            $keys = $request->input('field_keys', []);
            $values = $request->input('field_values', []);
            foreach ($keys as $i => $key) {
                if (!empty(trim($key))) {
                    $fields[trim($key)] = $values[$i] ?? '';
                }
            }
        }

        $profile->update([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? '',
            'fields_json' => json_encode($fields, JSON_UNESCAPED_UNICODE),
        ]);

        return redirect()->route('customer.tping.data-profiles.show', $profile)
            ->with('success', 'อัปเดต Data Profile สำเร็จ');
    }

    public function destroy(ProductDataProfile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        $profile->delete();

        return redirect()->route('customer.tping.data-profiles.index')
            ->with('success', 'ลบ Data Profile สำเร็จ');
    }
}
