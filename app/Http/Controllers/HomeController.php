<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\ThemeService;
use App\Support\UniverseHome;

class HomeController extends Controller
{
    public function index()
    {
        // onWebsite(): avatar packs are sold inside the GigGok app and would
        // otherwise show up here as "newest products", since they are the most
        // recently created rows in the table.
        $featuredProducts = Product::onWebsite()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $featuredReviews = Review::approved()
            ->featured()
            ->with(['user', 'reviewable'])
            ->latest()
            ->limit(6)
            ->get();

        // The 3D XMAN Universe, for browsers that can run it. Its own page
        // sends a device that cannot back here with ?view=classic, and that
        // visitor gets the theme's home below, unchanged.
        if (UniverseHome::shouldServe(request())) {
            return view('home-universe', compact('featuredProducts', 'featuredReviews'));
        }

        $categories = Category::onWebsite()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        // Retro and Nova each get their own landing page; classic/premium
        // keep the original marketing home (sale banner, services grid,
        // Metal-X, tech stack, reviews, CTA).
        $view = match (ThemeService::getCurrentTheme()) {
            'retro' => 'home-retro',
            'nova' => 'home-nova',
            default => 'home',
        };

        return view($view, compact('featuredProducts', 'categories', 'featuredReviews'));
    }
}
