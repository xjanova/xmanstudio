<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\ThemeService;

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

        // Retro theme gets its own Tron-styled landing page; classic/premium
        // keep the original marketing home (sale banner, services grid,
        // Metal-X, tech stack, reviews, CTA).
        $view = ThemeService::getCurrentTheme() === 'retro' ? 'home-retro' : 'home';

        return view($view, compact('featuredProducts', 'categories', 'featuredReviews'));
    }
}
