<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reviewable_type' => 'required|in:product,service',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        $morphMap = [
            'product' => Product::class,
            'service' => Service::class,
        ];

        $type = $morphMap[$request->reviewable_type];
        $model = $type::findOrFail($request->reviewable_id);

        // Check for duplicate review
        $exists = Review::where('user_id', auth()->id())
            ->where('reviewable_id', $model->id)
            ->where('reviewable_type', $type)
            ->exists();

        if ($exists) {
            return back()->with('error', 'คุณได้รีวิวสินค้า/บริการนี้ไปแล้ว');
        }

        Review::create([
            'user_id' => auth()->id(),
            'reviewable_id' => $model->id,
            'reviewable_type' => $type,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'ส่งรีวิวเรียบร้อยแล้ว รอ Admin อนุมัติ');
    }
}
