<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'reviewable'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate(20)->withQueryString();
        $pendingCount = Review::pending()->count();

        return view('admin.reviews.index', compact('reviews', 'pendingCount'));
    }

    public function approve(Review $review)
    {
        $review->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'อนุมัติรีวิวแล้ว');
    }

    public function reject(Request $request, Review $review)
    {
        $review->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
        ]);

        return back()->with('success', 'ปฏิเสธรีวิวแล้ว');
    }

    public function toggleFeatured(Review $review)
    {
        $review->update(['is_featured' => ! $review->is_featured]);

        return back()->with('success', $review->is_featured ? 'เพิ่มเป็นรีวิวแนะนำแล้ว' : 'ยกเลิกรีวิวแนะนำแล้ว');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'ลบรีวิวแล้ว');
    }
}
