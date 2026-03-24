<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayDonation;
use App\Models\Product;
use Illuminate\Http\Request;

class AiprayDonationController extends Controller
{
    public function index(Request $request)
    {
        $product = Product::where('slug', 'aipray')->first();
        if (! $product) {
            return view('admin.aipray.donations.index', [
                'donations' => collect(),
                'stats' => ['total' => 0, 'pending' => 0, 'completed' => 0, 'total_amount' => 0],
            ]);
        }

        $query = AiprayDonation::where('product_id', $product->id)->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate(30);

        $stats = [
            'total' => AiprayDonation::where('product_id', $product->id)->count(),
            'pending' => AiprayDonation::where('product_id', $product->id)->pending()->count(),
            'completed' => AiprayDonation::where('product_id', $product->id)->completed()->count(),
            'total_amount' => AiprayDonation::where('product_id', $product->id)->completed()->sum('amount'),
        ];

        return view('admin.aipray.donations.index', compact('donations', 'stats'));
    }

    public function approve(AiprayDonation $donation)
    {
        $donation->update([
            'status' => 'completed',
        ]);

        return back()->with('success', "อนุมัติการบริจาค #{$donation->id} แล้ว");
    }
}
