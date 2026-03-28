<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuotationCategory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true);

        // Search by name or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categorySlug = $request->input('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Filter by status
        $status = $request->input('status', 'all');
        if ($status === 'available') {
            $query->where(function ($q) {
                $q->where('is_coming_soon', false)
                    ->orWhere(function ($q2) {
                        $q2->where('is_coming_soon', true)
                            ->whereNotNull('coming_soon_until')
                            ->where('coming_soon_until', '<=', now());
                    });
            });
        } elseif ($status === 'coming_soon') {
            $query->where('is_coming_soon', true)
                ->where(function ($q) {
                    $q->whereNull('coming_soon_until')
                        ->orWhere('coming_soon_until', '>', now());
                });
        }

        // Sort: available products first, then coming soon
        $query->orderByRaw('CASE WHEN is_coming_soon = 1 AND (coming_soon_until IS NULL OR coming_soon_until > NOW()) THEN 1 ELSE 0 END ASC')
            ->orderBy('name');

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();

        // Get user's licenses for products if logged in
        $userLicenses = [];
        if (Auth::check()) {
            $userLicenses = LicenseKey::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id());
            })
                ->with('product')
                ->get()
                ->groupBy('product_id')
                ->map(function ($licenses) {
                    // Return the best license (active first, then by expiry)
                    return $licenses->sortByDesc(function ($license) {
                        if ($license->isValid()) {
                            return 2 + ($license->daysRemaining() / 1000000);
                        }

                        return $license->isExpired() ? 0 : 1;
                    })->first();
                });
        }

        return view('products.index', compact('products', 'categories', 'userLicenses'));
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

        // Get user's license for this product if logged in
        $userLicense = null;
        $hasPurchased = false;
        if (Auth::check()) {
            $userLicense = LicenseKey::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id());
            })
                ->where('product_id', $product->id)
                ->orderByDesc('created_at')
                ->first();

            // Check if user has purchased this product (has a completed order)
            $hasPurchased = Order::where('user_id', Auth::id())
                ->whereHas('items', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->where('status', 'completed')
                ->exists();
        }

        // Redirect products with dedicated pages
        if ($slug === 'tping') {
            return redirect()->route('tping.detail');
        }
        if ($slug === 'smschecker') {
            return redirect()->route('smschecker.detail');
        }
        if ($slug === 'localvpn') {
            return redirect()->route('localvpn.detail');
        }

        // Custom views for each product
        $customViews = [
            'autotradex' => 'products.autotradex',
            'spiderx' => 'products.spiderx',
            'xcluadeagent' => 'products.xcluadeagent',
            'phonex-manager' => 'products.phonexmanager',
            'live-x-shop-pro' => 'products.livexshoppro',
            'winxtools' => 'products.winxtools',
            'postxagent' => 'products.postxagent',
            'gpusharx' => 'products.gpusharx',
            'skidrow-killer' => 'products.skidrowkiller',
            'sms-payment-checker' => 'products.smspaymentchecker',
        ];

        if (isset($customViews[$slug])) {
            return view($customViews[$slug], compact('product', 'relatedProducts', 'userLicense', 'hasPurchased'));
        }

        return view('products.show', compact('product', 'relatedProducts', 'userLicense', 'hasPurchased'));
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
