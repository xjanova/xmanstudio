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
                'stats' => ['total_count' => 0, 'pending_count' => 0, 'completed_count' => 0, 'total_amount' => 0, 'this_month_amount' => 0],
            ]);
        }

        $query = AiprayDonation::where('product_id', $product->id)->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate(30);

        $stats = [
            'total_count' => AiprayDonation::where('product_id', $product->id)->count(),
            'pending_count' => AiprayDonation::where('product_id', $product->id)->pending()->count(),
            'completed_count' => AiprayDonation::where('product_id', $product->id)->completed()->count(),
            'total_amount' => AiprayDonation::where('product_id', $product->id)->completed()->sum('amount'),
            'this_month_amount' => AiprayDonation::where('product_id', $product->id)
                ->completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
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

    public function reject(AiprayDonation $donation)
    {
        $donation->update([
            'status' => 'rejected',
        ]);

        return back()->with('success', "ปฏิเสธการบริจาค #{$donation->id} แล้ว");
    }
}
