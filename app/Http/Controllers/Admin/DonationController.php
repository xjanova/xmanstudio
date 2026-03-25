<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayDonation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = AiprayDonation::with('product')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $donations = $query->paginate(30);

        $stats = [
            'total_count' => AiprayDonation::count(),
            'pending_count' => AiprayDonation::pending()->count(),
            'completed_count' => AiprayDonation::completed()->count(),
            'total_amount' => AiprayDonation::completed()->sum('amount'),
            'this_month_amount' => AiprayDonation::completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        return view('admin.donations.index', compact('donations', 'stats'));
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
