<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('home', compact('featuredProducts', 'categories'));
    }
}
