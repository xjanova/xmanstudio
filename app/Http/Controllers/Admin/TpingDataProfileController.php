<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDataProfile;
use Illuminate\Http\Request;

class TpingDataProfileController extends Controller
{
    private function getProduct(): Product
    {
        return Product::where('slug', 'tping')->firstOrFail();
    }

    public function index(Request $request)
    {
        $product = $this->getProduct();

        $query = ProductDataProfile::where('product_id', $product->id)
            ->with('user')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $profiles = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => ProductDataProfile::where('product_id', $product->id)->count(),
            'users' => ProductDataProfile::where('product_id', $product->id)->distinct('user_id')->count('user_id'),
        ];

        return view('admin.tping.data-profiles.index', compact('profiles', 'stats'));
    }

    public function show(ProductDataProfile $profile)
    {
        $profile->load('user');
        $fields = json_decode($profile->fields_json, true) ?? [];

        return view('admin.tping.data-profiles.show', compact('profile', 'fields'));
    }

    public function destroy(ProductDataProfile $profile)
    {
        $profile->delete();

        return redirect()->route('admin.tping.data-profiles.index')
            ->with('success', 'ลบ Data Profile สำเร็จ');
    }
}
