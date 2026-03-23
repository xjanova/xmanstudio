<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;

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

        $featuredReviews = Review::approved()
            ->featured()
            ->with(['user', 'reviewable'])
            ->latest()
            ->limit(6)
            ->get();

        return view('home', compact('featuredProducts', 'categories', 'featuredReviews'));
    }
}
