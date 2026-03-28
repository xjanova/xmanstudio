<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BtCategory;
use App\Models\BtFile;
use App\Models\BtFileSeeder;
use App\Models\BtKycRequest;
use App\Models\BtTrophy;
use App\Models\BtUserStats;
use App\Models\BtUserTrophy;
use Illuminate\Http\Request;

class BitTorrentController extends Controller
{
    /**
     * Dashboard with stats and overview.
     */
    public function dashboard()
    {
        $totalFiles = BtFile::count();
        $activeFiles = BtFile::where('is_active', true)->count();
        $totalCategories = BtCategory::count();
        $onlineSeeders = BtFileSeeder::where('is_online', true)->count();
        $totalDownloads = (int) BtFile::sum('download_count');
        $pendingKyc = BtKycRequest::pending()->count();
        $totalUsers = BtUserStats::count();

        $topUploaders = BtUserStats::orderByDesc('total_files_shared')
            ->limit(5)
            ->get();

        $recentFiles = BtFile::with('category')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.localvpn.torrent-dashboard', compact(
            'totalFiles',
            'activeFiles',
            'totalCategories',
            'onlineSeeders',
            'totalDownloads',
            'pendingKyc',
            'totalUsers',
            'topUploaders',
            'recentFiles'
        ));
    }

    /**
     * List all categories with file counts.
     */
    public function categories(Request $request)
    {
        $categories = BtCategory::withCount('files')
            ->orderBy('sort_order')
            ->get();

        return view('admin.localvpn.torrent-categories', compact('categories'));
    }

    /**
     * Create a new category.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:bt_categories,slug',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_adult' => 'boolean',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        BtCategory::create([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'icon' => $request->input('icon'),
            'description' => $request->input('description'),
            'is_adult' => $request->boolean('is_adult'),
            'sort_order' => $request->input('sort_order'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = BtCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:bt_categories,slug,' . $category->id,
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_adult' => 'boolean',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'icon' => $request->input('icon'),
            'description' => $request->input('description'),
            'is_adult' => $request->boolean('is_adult'),
            'sort_order' => $request->input('sort_order'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'อัปเดตหมวดหมู่สำเร็จ');
    }

    /**
     * Delete a category (only if no files attached).
     */
    public function deleteCategory($id)
    {
        $category = BtCategory::findOrFail($id);

        if ($category->files()->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ได้ เนื่องจากยังมีไฟล์อยู่ในหมวดหมู่นี้');
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "ลบหมวดหมู่ \"{$name}\" สำเร็จ");
    }

    /**
     * List files with search and category filter.
     */
    public function files(Request $request)
    {
        $query = BtFile::with('category')
            ->withCount(['seeders', 'onlineSeeders']);

        if ($search = $request->get('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('uploader_display_name', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $files = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $categories = BtCategory::orderBy('sort_order')->pluck('name', 'id');

        return view('admin.localvpn.torrent-files', compact('files', 'categories'));
    }

    /**
     * Show file details with seeders.
     */
    public function showFile($id)
    {
        $file = BtFile::with(['category', 'seeders' => function ($q) {
            $q->orderByDesc('is_online')->orderByDesc('last_seen_at');
        }])->findOrFail($id);

        return view('admin.localvpn.torrent-show-file', compact('file'));
    }

    /**
     * Toggle file active/inactive.
     */
    public function toggleFile($id)
    {
        $file = BtFile::findOrFail($id);
        $file->is_active = ! $file->is_active;
        $file->save();

        $status = $file->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "ไฟล์ \"{$file->file_name}\" ถูก{$status}แล้ว");
    }

    /**
     * Delete a file (cascades seeders).
     */
    public function deleteFile($id)
    {
        $file = BtFile::findOrFail($id);
        $name = $file->file_name;

        $file->seeders()->delete();
        $file->delete();

        return back()->with('success', "ลบไฟล์ \"{$name}\" สำเร็จ");
    }

    /**
     * List KYC requests with status filter.
     */
    public function kycRequests(Request $request)
    {
        $query = BtKycRequest::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $kycRequests = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        $pendingCount = BtKycRequest::pending()->count();

        return view('admin.localvpn.torrent-kyc', compact('kycRequests', 'pendingCount'));
    }

    /**
     * Show KYC request details.
     */
    public function showKyc($id)
    {
        $kyc = BtKycRequest::findOrFail($id);

        return view('admin.localvpn.torrent-show-kyc', compact('kyc'));
    }

    /**
     * Approve a KYC request.
     */
    public function approveKyc(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $kyc = BtKycRequest::findOrFail($id);
        $kyc->update([
            'status' => 'approved',
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "อนุมัติ KYC ของ \"{$kyc->display_name}\" สำเร็จ");
    }

    /**
     * Reject a KYC request.
     */
    public function rejectKyc(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'required|string|max:500',
        ]);

        $kyc = BtKycRequest::findOrFail($id);
        $kyc->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "ปฏิเสธ KYC ของ \"{$kyc->display_name}\" สำเร็จ");
    }

    /**
     * Leaderboard - top users by score.
     */
    public function leaderboard(Request $request)
    {
        $users = BtUserStats::orderByDesc('score')
            ->limit(50)
            ->get();

        // Count trophies per machine_id for displayed users
        $machineIds = $users->pluck('machine_id')->toArray();
        $trophyCounts = BtUserTrophy::whereIn('machine_id', $machineIds)
            ->selectRaw('machine_id, COUNT(*) as trophies_count')
            ->groupBy('machine_id')
            ->pluck('trophies_count', 'machine_id');

        return view('admin.localvpn.torrent-leaderboard', compact('users', 'trophyCounts'));
    }

    /**
     * List all trophies.
     */
    public function trophies(Request $request)
    {
        $trophies = BtTrophy::withCount('userTrophies')
            ->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard')")
            ->orderBy('sort_order')
            ->get();

        return view('admin.localvpn.torrent-trophies', compact('trophies'));
    }

    /**
     * Create a new trophy.
     */
    public function storeTrophy(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:100|unique:bt_trophies,slug',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'badge_text' => 'nullable|string|max:50',
            'difficulty' => 'required|in:easy,medium,hard',
            'requirement_type' => 'required|string|max:100',
            'requirement_value' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        BtTrophy::create([
            'slug' => $request->input('slug'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'icon' => $request->input('icon'),
            'badge_text' => $request->input('badge_text'),
            'difficulty' => $request->input('difficulty'),
            'requirement_type' => $request->input('requirement_type'),
            'requirement_value' => $request->input('requirement_value'),
            'sort_order' => $request->input('sort_order'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'สร้างถ้วยรางวัลสำเร็จ');
    }

    /**
     * Update an existing trophy.
     */
    public function updateTrophy(Request $request, $id)
    {
        $trophy = BtTrophy::findOrFail($id);

        $request->validate([
            'slug' => 'required|string|max:100|unique:bt_trophies,slug,' . $trophy->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'badge_text' => 'nullable|string|max:50',
            'difficulty' => 'required|in:easy,medium,hard',
            'requirement_type' => 'required|string|max:100',
            'requirement_value' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $trophy->update([
            'slug' => $request->input('slug'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'icon' => $request->input('icon'),
            'badge_text' => $request->input('badge_text'),
            'difficulty' => $request->input('difficulty'),
            'requirement_type' => $request->input('requirement_type'),
            'requirement_value' => $request->input('requirement_value'),
            'sort_order' => $request->input('sort_order'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'อัปเดตถ้วยรางวัลสำเร็จ');
    }
}
