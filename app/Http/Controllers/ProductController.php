<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\QuotationCategory;
use App\Models\Service;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->paginate(12);

        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    public function services()
    {
        $categories = QuotationCategory::with('activeOptions')
            ->active()
            ->ordered()
            ->get();

        return view('services.index', compact('categories'));
    }

    public function serviceDetail($slug)
    {
        $service = Service::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $relatedServices = Service::active()
            ->where('id', '!=', $service->id)
            ->ordered()
            ->take(3)
            ->get();

        return view('services.show', compact('service', 'relatedServices'));
    }
}
